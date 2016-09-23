# -*- coding: utf-8 -*-
import time, json, pymysql, configparser, os
import numpy as np

__author__ = 'Asger Bachmann (agb@birc.au.dk)'

# Parse config
config = configparser.ConfigParser()
#config.read('../../config.ini')
config.read(os.path.join(os.path.dirname(__file__), os.pardir, os.pardir, 'config.ini'))

# Retrieve data
def get_data(print_debug, database, columns, gene_limit=None, genes=None, clean=True):
    dbconfig = {
        'user': config['general']['user'].strip("'"),
        'passwd': config['general']['pass'].strip("'"),
        'host': config['general']['host'].strip("'"),
        'db': config['general']['db'].strip("'")
    }

    time_start = time.perf_counter()
    time_elapsed = []

    con = pymysql.connect(**dbconfig)
    cursor = con.cursor()

    sql_to_execute = "SELECT {0} FROM {1}".format(", ".join(columns), database)
    if genes is not None:
        sql_to_execute = sql_to_execute + " WHERE GeneId IN ('{0}')".format("', '".join(genes))
    if gene_limit is not None:
        sql_to_execute = sql_to_execute + " LIMIT {0}".format(gene_limit)

    cursor.execute(sql_to_execute)

    full_data = np.array(cursor.fetchall())
    con.close()

    total_genes = full_data.shape[0]

    # Step updates
    time_loading_data = time.perf_counter() - time_start
    time_elapsed.append({ 'step': 'loading_data', 'label': 'Loading data from MySQL', 'time_elapsed': time_loading_data })
    if print_debug:
        print("Loading data ({0} rows, {1} conditions) took: {2} seconds".format(total_genes,
                                                                                 len(columns)-1,
                                                                                 time_loading_data))
        time_start = time.perf_counter()

    # Use float32 to save memory. Note that using float16 will seriously impact performance because of the way NumPy
    # handles overflows (to 0) and the resulting division by 0.
    data = full_data[:, 1:].astype(np.float32)
    if clean:
        # Clean the data. Do not include a gene with log(var(log(data)) less than the mean unless max / mean > 2.
        log_data = np.log(data)
        var_log_data = np.var(log_data, axis=1)
        log_var_log_data = np.log(var_log_data)
        log_var_log_data_mean = np.mean(log_var_log_data)
        max_mean_ratio = np.max(data, axis=1) / np.mean(data, axis=1)
        full_data = full_data[(log_var_log_data > log_var_log_data_mean) | (max_mean_ratio > 2)]
        data = full_data[:, 1:].astype(np.float32)

    time_cleaning_data = time.perf_counter() - time_start
    time_elapsed.append({ 'step': 'cleaning_data', 'label': 'Cleaning data', 'time_elapsed': time_cleaning_data })
    if print_debug:
        print("Cleaning data took: {0} seconds".format(time_cleaning_data))
        print("\tRemoved {0} rows".format(total_genes - full_data.shape[0]))

    return data, full_data[:, 0], time_elapsed


def get_common_correlation_matrix_data(data):
    # data.mean gives us an array of shape (x,). We must manually reshape the array to shape (x,1) for the subtraction
    # to work. Note also that we use axis 1 because we wish to average over the rows and not the columns.
    data_mean = data.mean(1).reshape((len(data), 1))
    diff_from_mean = data - data_mean
    # noinspection PyTypeChecker
    sqrt_sum_diff_from_mean_sq = np.sqrt(np.sum(diff_from_mean ** 2, 1))

    return diff_from_mean, sqrt_sum_diff_from_mean_sq


def get_correlation_matrix_in_memory(print_debug, database, columns, gene_limit=None, genes=None, clean=True):
    data, labels, time_elapsed = get_data(print_debug, database, columns, gene_limit=gene_limit, genes=genes, clean=clean)

    time_start = time.perf_counter()

    # We calculate a Pearson's correlation coefficient matrix as per the first formula at
    # https://en.wikipedia.org/wiki/Pearson_product-moment_correlation_coefficient#For_a_sample.
    # Note, however, that we use a vectorized version of the formula to improve performance.
    diff_from_mean, sqrt_sum_diff_from_mean_sq = get_common_correlation_matrix_data(data)

    # The dot product below is equal to calculating np.sum(diff_from_mean[i, :] * diff_from_mean[j, :]) in a loop.
    # The sum is in the dot product's (i, j) entry.
    P = diff_from_mean.dot(diff_from_mean.T)

    # Multiply each entry in the vector by all other entries one at a time. This produces a large array.
    # Entry (i,j) of the array is sqrt_sum_diff_from_mean_sq[i] * sqrt_sum_diff_from_mean_sq[j].
    # Afterwards, do a division with P.
    # To save memory, use "/=" and inline the array to allow inplace division.
    P /= (sqrt_sum_diff_from_mean_sq * sqrt_sum_diff_from_mean_sq[:, np.newaxis])

    # Raising P to a power b where b > 1 can serve to improve confidence in the connections in the network.
    # Values close to 1 stays closer to 1 than values further away from 1.
    # Note that this also means that we do not have to do np.abs(P).
    P **= 2

    time_calculating_p = time.perf_counter() - time_start
    time_elapsed.append({ 'step': 'calculating_p', 'label': 'Calculating Pearson\'s correlation coefficient matrix', 'time_elapsed': time_calculating_p })
    if print_debug:
        print("Calculating P took: {0} seconds".format(time_calculating_p))

    return P, labels, time_elapsed


def get_correlation_matrix_on_disk(print_debug, database, columns, gene_limit=None, genes=None, clean=True):
    """
    Calculates the correlation matrix a window of rows at a time to preserve memory.
    Stores the intermediate and final result on disk. Returns a memory mapped correlation matrix.
    """
    data, labels, time_elapsed = get_data(print_debug, database, columns, gene_limit=gene_limit, genes=genes, clean=clean)
    total_genes = len(labels)

    time_start = time.perf_counter()

    diff_from_mean, sqrt_sum_diff_from_mean_sq = get_common_correlation_matrix_data(data)

    # If you end up using this, perhaps you should replace temp.dat with something else.
    P = np.memmap("temp.dat", dtype='float32', mode='w+', shape=(total_genes, total_genes))

    # Calculate the correlated pairs in windows of size window_size.
    # For each window, calculate the correlation between the window and all the other rows (incl. the window itself).
    window_size = 10000
    for i in range(0, total_genes, window_size):
        idx_end = min(i + window_size, total_genes)
        # See the description of the steps in get_correlation_matrix_in_memory().
        P[i:idx_end, :] = diff_from_mean[i:idx_end, :].dot(diff_from_mean[:, :].T)
        P[i:idx_end, :] /= (
            sqrt_sum_diff_from_mean_sq[i:idx_end, np.newaxis] * sqrt_sum_diff_from_mean_sq[np.newaxis, ...])
        P[i:idx_end, :] **= 2

    time_calculating_p = time.perf_counter() - time_start
    time_elapsed.append({ 'step': 'calculating_p', 'label': 'Calculating Pearson\'s correlation coefficient matrix', 'time_elapsed': time_calculating_p })
    if print_debug:
        print("Calculating P took: {0} seconds".format(time.perf_counter() - time_start))

    return P, labels, time_elapsed


def get_correlated_pairs(print_debug, database, columns, threshold, gene_limit=None, genes=None, clean=True):
    data, labels, time_elapsed = get_data(print_debug, database, columns, gene_limit=gene_limit, genes=genes, clean=clean)
    total_genes = len(labels)

    time_start = time.perf_counter()

    diff_from_mean, sqrt_sum_diff_from_mean_sq = get_common_correlation_matrix_data(data)

    pairs = ([], [])

    # Calculate the correlated pairs in windows of size window_size.
    # For each window, calculate the correlation between the window and all the other rows (incl. the window itself).
    window_size = 10000
    for i in range(0, total_genes, window_size):
        idx_end = min(i + window_size, total_genes)
        # See the description of the steps in get_correlation_matrix_in_memory().
        partial_P = diff_from_mean[i:idx_end, :].dot(diff_from_mean[:, :].T)
        partial_P /= (sqrt_sum_diff_from_mean_sq[i:idx_end, np.newaxis] * sqrt_sum_diff_from_mean_sq[np.newaxis, ...])
        partial_P **= 2

        # Zero-out all values below the given threshold.
        partial_P[partial_P < threshold] = 0

        # The relevant pairs correspond to nonzero indices.
        # Filter out duplicates and genes compared to themselves by requiring that row index > column index (imagine a
        # triangular upper matrix but keep in mind that we use windows).
        window_pairs = partial_P.nonzero()
        window_pairs = (window_pairs[0] + i, window_pairs[1])
        window_pairs_zipped = [p for p in zip(window_pairs[0], window_pairs[1]) if p[0] > p[1]]
        window_pairs = list(zip(*window_pairs_zipped))
        pairs[0].extend(window_pairs[0])
        pairs[1].extend(window_pairs[1])

    time_calculating_pairs = time.perf_counter() - time_start
    time_elapsed.append({ 'step': 'calculating_pairs', 'label': 'Calculating correlated pairs', 'time_elapsed': time_calculating_pairs })
    if print_debug:
        print("Calculating pairs took: {0} seconds".format(time_calculating_pairs))

    return pairs, labels, total_genes, time_elapsed


def get_correlation_matrix_row(print_debug, database, columns, query, gene_limit=None, genes=None, clean=True):
    data, labels, time_elapsed = get_data(print_debug, database, columns, gene_limit=gene_limit, genes=genes, clean=clean)

    time_start = time.perf_counter()

    diff_from_mean, sqrt_sum_diff_from_mean_sq = get_common_correlation_matrix_data(data)

    # See the description of the steps in get_correlation_matrix_in_memory().
    # Here we calculate a single row in the matrix, though.
    query_row_idx = np.where(labels == query)[0]
    if len(query_row_idx) == 0:
        return None, None

    P_row = diff_from_mean[query_row_idx, :].dot(diff_from_mean[:, :].T)
    P_row /= (sqrt_sum_diff_from_mean_sq[query_row_idx, np.newaxis] * sqrt_sum_diff_from_mean_sq[np.newaxis, ...])
    P_row **= 2

    time_calculating_p = time.perf_counter() - time_start
    time_elapsed.append({ 'step': 'calculating_p', 'label': 'Calculating Pearson\'s correlation coefficient matrix', 'time_elapsed': time_calculating_p })
    if print_debug:
        print("Calculating a row in P took: {0} seconds".format(time.perf_counter() - time_start))

    return P_row[0], labels, time_elapsed
