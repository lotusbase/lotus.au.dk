# -*- coding: utf-8 -*-
import argparse
import time

import os, re, gzip, json, random, binascii, configparser
import numpy as np
import datetime as dt
import pony.orm as pny

import Datasets
from CorrelationMatrix import get_correlated_pairs
from SpringLayout import fruchterman_reingold_layout
from SquarifiedTreemap import SquarifiedTreemap

# from memory_profiler import profile

__author__ = 'Asger Bachmann (agb@birc.au.dk)'

# Parse config
config = configparser.ConfigParser()
config.read('../../config.ini')

# Job statuses
class JobStatus:
    Submitted, Running, Done, Error, Expired = range(1, 6)

# Job steps
class JobStep:
    GetData, FindingNodesEdges, ClusteringNodes, SquarifiedTreemap, FruchtermanReingoldLayout, WriteJSON = range(0, 6)

#db = pny.Database("sqlite", "correlation_network_jobs.db", create_db=True)
db = pny.Database('mysql', host=config['general']['host'].strip("'"), user=config['general']['user'].strip("'"), passwd=config['general']['pass'].strip("'"), db=config['general']['db'].strip("'"))
class CorrelationNetworkJob(db.Entity):
    # Basic job data
    dataset = pny.Required(str)
    candidates = pny.Optional(str)
    columns = pny.Optional(str)
    verbose = pny.Required(bool, default=False)
    threshold = pny.Required(float)
    minimum_cluster_size = pny.Required(int)
    submit_time = pny.Required(dt.datetime, sql_default='CURRENT_TIMESTAMP')
    start_time = pny.Optional(dt.datetime)
    end_time = pny.Optional(dt.datetime)

    # Timepoints
    time_elapsed_loading_data = pny.Optional(float)
    time_elapsed_cleaning_data = pny.Optional(float)
    time_elapsed_calculating_pairs = pny.Optional(float)
    time_elapsed_nodes_edges = pny.Optional(float)
    time_elapsed_node_clustering = pny.Optional(float)
    time_elapsed_squarified_treemap = pny.Optional(float)
    time_elapsed_fruchterman_reingold_layout = pny.Optional(float)

    # Counts
    edge_count = pny.Optional(int)
    node_count = pny.Optional(int)
    cluster_count = pny.Optional(int)

    # Last completed step
    last_completed_step = pny.Required(int, default=0)

    # Status
    status = pny.Required(int, default=JobStatus.Submitted)
    status_reason = pny.Optional(str)

    # Access counters
    view_count = pny.Required(int, default=0)
    download_count = pny.Required(int, default=0)

    # Identifiers
    owner = pny.Optional(str)
    owner_salt = pny.Optional(str)
    hash_id = pny.Required(str)

db.generate_mapping(create_tables=False)

# Get directory of writing data file to
# Data directory is located at the web root's /data/cornea/jobs
# Current file directory is located at the web root's /lib/python/corr
data_dir = os.path.join(os.path.abspath(__file__ + "/../../../../"), 'data', 'cornea', 'jobs')

# Try making the data directory first
try:
    os.makedirs(data_dir)
except:
    pass


def get_clusters(zipped_edges, minimum_cluster_size):
    def merge_clusters(c1, c2):
        c2_nodes = cluster_to_nodes_map.pop(c2)
        c2_connections = cluster_to_connections_map.pop(c2)
        cluster_to_nodes_map[c1] += c2_nodes
        cluster_to_connections_map[c1] += c2_connections
        for c2_n in c2_nodes:
            node_to_cluster_map[c2_n] = c1

    node_to_cluster_map = {}
    cluster_to_nodes_map = {}
    cluster_to_connections_map = {}

    num_clusters = -1

    for (i, j) in zipped_edges:
        if i in node_to_cluster_map:
            cluster = node_to_cluster_map[i]

            if j not in node_to_cluster_map:
                node_to_cluster_map[j] = cluster
                cluster_to_nodes_map[cluster].append(j)
            elif node_to_cluster_map[j] != cluster:
                merge_clusters(cluster, node_to_cluster_map[j])
        elif j in node_to_cluster_map:
            cluster = node_to_cluster_map[j]

            if i not in node_to_cluster_map:
                node_to_cluster_map[i] = cluster
                cluster_to_nodes_map[cluster].append(i)
            elif node_to_cluster_map[i] != cluster:
                merge_clusters(cluster, node_to_cluster_map[i])
        else:
            num_clusters += 1
            cluster = num_clusters
            node_to_cluster_map[i] = cluster
            node_to_cluster_map[j] = cluster
            cluster_to_nodes_map[cluster] = [i, j]
            cluster_to_connections_map[cluster] = []

        cluster_to_connections_map[cluster].append((i, j))

    preserved_nodes = []
    preserved_edges = []
    cluster_to_nodes_arr = []
    cluster_to_connections_arr = []
    for (c, nodes) in cluster_to_nodes_map.items():
        if len(nodes) >= minimum_cluster_size:
            cluster_to_nodes_arr.append(nodes)
            connections = cluster_to_connections_map[c]
            cluster_to_connections_arr.append(connections)

            preserved_nodes.extend(nodes)
            preserved_edges.extend(connections)

    return preserved_nodes, preserved_edges, cluster_to_nodes_arr, cluster_to_connections_arr


# Global time_start variables
first_time_start = 0
delta_time_start = 0


# Time logging
def time_logging(_checkpoint, checkpoint):
    current_time = time.perf_counter()

    # Global variables
    global delta_time_start

    # Add checkpoint
    if (checkpoint != None):
        time_dict = { 'step': _checkpoint, 'label': checkpoint, 'time_elapsed': current_time - delta_time_start }
    else:
        time_dict = { 'step': 'current', 'label': None, 'time_elapsed': current_time - delta_time_start }

    # Update delta
    delta_time_start = current_time

    # Return time
    return time_dict

# @profile
def create_correlation_network(dataset, candidates, columns, verbose, out_file, threshold, minimum_cluster_size, owner, owner_salt, hash_id):

    # The number of genes to fetch from the database. For testing purposes.
    gene_limit = 500000

    # Global variables
    global first_time_start
    global delta_time_start

    # Start time
    start_timestamp = dt.datetime.now()

    # Time logging
    time_points = []
    first_time_start = delta_time_start = time.perf_counter()
    current_time_elapsed = 0

    edges, node_labels, total_genes, time_elapsed_list = get_correlated_pairs(verbose, dataset.name, columns, threshold,
                                                           genes=candidates, gene_limit=gene_limit)
    # Append list of time_elapsed
    time_points = time_points + time_elapsed_list

    # Update step
    with pny.db_session:
        job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
        if job != None:
            job.last_completed_step = JobStep.GetData
            pny.commit()

    # The nodes correspond to the unique sources or targets of the edges (i.e. the unique nonzero indices).
    nodes = np.unique(edges)

    # Time for finding nodes and edges:
    current_checkpoint_time = time_logging('nodes_edges', 'Finding nodes and edges')
    time_points.append(current_checkpoint_time)
    with pny.db_session:
        job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
        if job != None:
            job.last_completed_step = JobStep.FindingNodesEdges
            pny.commit()

    if verbose:
        print("Finding nodes and edges took:", str(current_checkpoint_time['time_elapsed']), "seconds")
        print("\tNumber of edges:", len(edges[0]))
        print("\tNumber of nodes:", len(nodes))
        print("\tAverage number of edges per node:", (len(edges[0]) / len(nodes) if len(nodes) > 0 else 0))

    # Forced termination if there are too many nodes
    if(len(nodes) > 20000 or len(edges[0]) > 1000000):
        if verbose:
            print("Too many nodes and edges in network, job is terminated to prevent memory exhaustion.")

        with pny.db_session:
            job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
            if job != None:
                # Counts
                job.edge_count = len(zipped_edges)
                job.node_count = len(nodes)
                job.cluster_count = len(clusters)
                job.status = JobStatus.Error
                job.status_reason = 'Too many node and edges in calculated network, job terminated to prevent memory exhaustion.'

                # Commit
                pny.commit()

        return

    # Cluster the nodes with their neighbours.
    zipped_edges = list(zip(*edges))
    nodes, zipped_edges, clusters, clusters_connections = get_clusters(zipped_edges, minimum_cluster_size)

    # Sort the clusters based on their size, largest first.
    # cluster_order[i] is the i'th cluster's order from 0 to the number of clusters.
    cluster_sizes = [len(ns) for ns in clusters]
    argsorted_cluster_sizes = sorted(range(len(cluster_sizes)), key=cluster_sizes.__getitem__, reverse=True)
    cluster_order = [argsorted_cluster_sizes.index(i) for i in range(len(cluster_sizes))]

    # Time for clustering nodes
    current_checkpoint_time = time_logging('node_clustering', 'Clustering nodes')
    time_points.append(current_checkpoint_time)
    with pny.db_session:
        job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
        if job != None:
            job.last_completed_step = JobStep.ClusteringNodes
            pny.commit()

    if verbose:
        print("Clustering nodes took:", str(current_checkpoint_time['time_elapsed']), "seconds")
        print("\tNumber of edges:", len(zipped_edges))
        print("\tNumber of nodes:", len(nodes))
        print("\tAverage number of edges per node:", (len(edges[0]) / len(nodes) if len(nodes) > 0 else 0))
        print("\tNumber of clusters:", len(clusters))

    # Use a treemap to decide how to lay out the clusters.
    # Add the largest clusters first to increase chance of keeping them in the correct order (order is not stable).
    if len(nodes) == 0:

         # Time for squarified treemap
        current_checkpoint_time = time_logging('squarified_treemap', 'Squarified treemap')
        time_points.append(current_checkpoint_time)

         # Time for Fruchterman-Reingold layout
        current_checkpoint_time = time_logging('fruchterman_reingold_layout', 'Fruchterman-Reingold layout')
        time_points.append(current_checkpoint_time)

        if verbose:
            print("No nodes, therefore no SquarifiedTreemap or Fruchterman-Reingold.")
        processed_nodes = {}

    else:
        treemap = SquarifiedTreemap(sorted(cluster_sizes, reverse=True), 0, total_genes, 0, total_genes)
        layout_margin = total_genes / 20

        # Time for squarified treemap
        current_checkpoint_time = time_logging('squarified_treemap', 'Squarified treemap')
        time_points.append(current_checkpoint_time)
        with pny.db_session:
            job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
            if job != None:
                job.last_completed_step = JobStep.SquarifiedTreemap
                pny.commit()

        if verbose:
            print("SquarifiedTreemap took:", str(current_checkpoint_time['time_elapsed']), "seconds")

        pos = fruchterman_reingold_layout(clusters_connections, 4)

        if verbose:
            print("Fruchterman-Reingold layout took:", str(current_checkpoint_time['time_elapsed']), "seconds")

        processed_nodes = {}
        for i in range(len(clusters_connections)):
            ns = clusters[i]
            ps = pos[i]
            rectangle = treemap.layout[cluster_order[i]]
            rect_w = rectangle.w - layout_margin
            rect_h = rectangle.h - layout_margin
            rect_x = rectangle.x + layout_margin
            rect_y = rectangle.y + layout_margin
            for n in ns:
                p = ps[n]
                processed_nodes[n] = (n,
                                      node_labels[n],
                                      rect_w * p[0] + rect_x,
                                      rect_h * p[1] + rect_y)

        # Time for Fruchterman-Reingold layout
        current_checkpoint_time = time_logging('fruchterman_reingold_layout', 'Fruchterman-Reingold layout')
        time_points.append(current_checkpoint_time)
        with pny.db_session:
            job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
            if job != None:
                job.last_completed_step = JobStep.FruchtermanReingoldLayout
                pny.commit()

        if verbose:
            print("Layout according to Fruchterman-Reingold took:", str(current_checkpoint_time['time_elapsed']), "seconds")

    with gzip.open(out_file, "wb") as out:

        # Iterate through columns, remove first element and 'Mean_'
        columns_filtered = []
        for col in columns[1:]:
            columns_filtered.append(re.sub(r'Mean_', '', col))

        # Final time
        total_time_elapsed = time.perf_counter() - first_time_start

        # Check if job is present in the database
        # If it is, update all the time columns
        with pny.db_session:
            job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == hash_id)
            if job != None:
                if verbose:
                    print("Job entry found in database, updating metadata columns.")

                    # Timepoints
                    job.time_elapsed_loading_data = time_points[0]['time_elapsed']
                    job.time_elapsed_cleaning_data = time_points[1]['time_elapsed']
                    job.time_elapsed_calculating_pairs = time_points[2]['time_elapsed']
                    job.time_elapsed_nodes_edges = time_points[3]['time_elapsed']
                    job.time_elapsed_node_clustering = time_points[4]['time_elapsed']
                    job.time_elapsed_squarified_treemap = time_points[5]['time_elapsed']
                    job.time_elapsed_fruchterman_reingold_layout = time_points[6]['time_elapsed']

                    # Counts
                    job.edge_count = len(zipped_edges)
                    job.node_count = len(nodes)
                    job.cluster_count = len(clusters)

                    # Commit
                    pny.commit()
            else:
                if verbose:
                    print("Job entry not found in database, not writing anything.")

        # Generate output
        out.write("{\"metadata\":{\"settings\":{".encode('utf-8'))
        out.write("\"dataset\":\"{0}\",\"id_type\":\"{1}\",\"columns\":{2},\"threshold\":{3},\"minimum_cluster_size\":{4}".format(dataset.option, dataset.name_column, json.dumps(list(columns_filtered)), threshold, minimum_cluster_size).encode('utf-8'))
        out.write("},\"layout\": {".encode('utf-8'))
        out.write("\"edge_count\":{0},\"node_count\":{1},\"cluster_count\":{2}".format(len(zipped_edges), len(nodes), len(clusters)).encode('utf-8'))
        out.write("},\"job\":{".encode('utf-8'))
        out.write("\"id\":\"{0}\",\"owner\":\"{1}\",\"time_elapsed\":{2},\"total_time_elapsed\":{3},\"start_time\":\"{4}\",\"end_time\":\"{5}\"".format(hash_id, owner, json.dumps(time_points), total_time_elapsed, start_timestamp, dt.datetime.now()).encode('utf-8'))
        out.write("}".encode('utf-8'))
        out.write("},".encode('utf-8'))
        out.write("\"data\": {\"nodes\":[".encode('utf-8'))
        out.write(",".join(
            ["{{\"i\":\"{0}\",\"l\":\"{1}\",\"x\":{2},\"y\":{3}}}".format(n, label, x, y)
             for (n, label, x, y) in processed_nodes.values()]).encode('utf-8'))
        out.write("],\"edges\":[".encode('utf-8'))
        out.write(",".join(
            ["{{\"i\":\"e{0}_{1}\",\"s\":\"{0}\",\"t\":\"{1}\"}}".format(s, t) for (s, t) in zipped_edges]).encode('utf-8'))
        out.write("]}".encode('utf-8'))
        out.write("}".encode('utf-8'))

    if verbose:
        print("Writing network to a file took:", str(time.perf_counter() - delta_time_start), "seconds")
        print("Total time elapsed:", str(time.perf_counter() - first_time_start), "seconds")


def fix_candidates_columns_args(parsed_args):
    if isinstance(parsed_args.candidates, str):
        parsed_args.candidates = parsed_args.candidates.split(",")

    if isinstance(parsed_args.columns, str):
        columns = [parsed_args.dataset.name_column]
        columns.extend(parsed_args.columns.split(","))
        parsed_args.columns = columns
    elif parsed_args.columns is None:
        columns = [parsed_args.dataset.name_column]
        columns.extend(parsed_args.dataset.columns)
        parsed_args.columns = columns


def get_args():
    random_hash = binascii.b2a_hex(os.urandom(16)).decode('utf-8')
    parser = argparse.ArgumentParser(description="Create a network based on correlation data.")
    parser.add_argument("dataset", help="the dataset",
                        choices=[k for k, _ in Datasets.all_by_name.items()])
    parser.add_argument("--candidates", dest="candidates", default=None, type=str,
                        help="the candidate genes or probes (separated by ',')")
    parser.add_argument("--columns", dest="columns", default=None, type=str,
                        help="the conditions to consider (separated by ';')")
    parser.add_argument("--verbose", dest="verbose", help="print verbose information such as timing information",
                        type=bool, default=False)
    parser.add_argument("--out-file", dest="out_file", help="the file to write output to",
                        type=str, default=os.path.join(data_dir, "cli_{0}.json.gz".format(random_hash)))
    parser.add_argument("--threshold", dest="threshold", help="the threshold for when two genes are correlated",
                        type=float, default=0.95)
    parser.add_argument("--minimum-cluster-size", dest="minimum_cluster_size", type=int, default=5,
                        help="discard clusters of size less than this number")
    parser.add_argument("--hash-id", dest="hash_id", type=str, default=random_hash,
                        help="randomly generated 32-character hexadecimal identifier")
    parser.add_argument("--owner", dest="owner", help="email of the job owner",
                        type=str, default="")
    parser.add_argument("--owner-salt", dest="owner_salt", help="internal unique identifier of user that exists in the system",
                        type=str, default="")
    parsed_args = parser.parse_args()

    parsed_args.dataset = Datasets.all_by_name[parsed_args.dataset]

    fix_candidates_columns_args(parsed_args)
    return parsed_args


if __name__ == "__main__":
    args = get_args()
    print('Running CORNEA job from CLI')
    print("Starting job {0}".format(args.hash_id))
    create_correlation_network(args.dataset, args.candidates, args.columns, args.verbose, args.out_file, args.threshold, args.minimum_cluster_size, args.owner, args.owner_salt, args.hash_id)
