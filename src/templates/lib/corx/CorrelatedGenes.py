# -*- coding: utf-8 -*-
import argparse
import json
import Datasets
from CorrelationMatrix import get_correlation_matrix_row

__creators__ = ['Asger Bachmann (agb@birc.au.dk)']
__authors__ = ['Asger Bachmann (agb@birc.au.dk)', 'Terry Mun (terry@mbg.au.dk)']


def main(args):
    if args.verbose:
        print("Fetching top {0} correlated genes for {1}...".format(args.top, args.query))

    row, labels, time_elapsed = get_correlation_matrix_row(args.verbose, args.dataset.name, args.columns, args.query, clean=False)

    if row is None:
        # Return json
        print(json.dumps({"error": True, "errorCode": 404, "errorMessage": "{0} not found".format(args.query)}))
        #print("{0} not found".format(args.query))

    else:
        argsorted_row = row.argsort()[::-1]

        if args.top > len(argsorted_row):
            args.top = len(argsorted_row) - 2

        # To select top X, skip the first one because that is the query gene itself and select the following X genes.
        relevant_argsorted_row = argsorted_row[1:(args.top + 1)]
        relevant_row = row[relevant_argsorted_row]
        relevant_labels = labels[relevant_argsorted_row]

        # Return json
        # User itertools izip to prevent performance issues for excessively large lists
        print(json.dumps({'success': True, 'metadata': {'job': {'time_elapsed': time_elapsed}}, 'data': [{'id': i, 'score': p} for i, p in zip(relevant_labels, [x.item() for x in relevant_row])]}))

        #print("[{0}]".format(",".join(["[\"{0}\",{1}]".format(l, g) for (l, g) in zip(relevant_labels, relevant_row)])))


def get_args():
    parser = argparse.ArgumentParser(description="Create a network based on correlation data.")
    parser.add_argument("dataset", help="the dataset",
                        choices=[k for k, _ in Datasets.all_by_name.items()])
    parser.add_argument("query", default=None, type=str,
                        help="the query gene or probe")
    parser.add_argument("--candidates", dest="candidates", default=None, type=str,
                        help="the candidate genes or probes (separated by ',')")
    parser.add_argument("--top", dest="top", default=10, type=int,
                        help="the top X genes to select")
    parser.add_argument("--columns", dest="columns", help="the conditions to consider (separated by ',')",
                        type=str, default=None)
    parser.add_argument("--verbose", dest="verbose", help="print verbose information such as timing information",
                        type=bool, default=False)
    args = parser.parse_args()

    args.dataset = Datasets.all_by_name[args.dataset]

    if isinstance(args.candidates, str):
        args.candidates = args.candidates.split(",")

    if isinstance(args.columns, str):
        columns = [args.dataset.name_column]
        columns.extend(args.columns.split(","))
        args.columns = columns
    elif args.columns is None:
        columns = [args.dataset.name_column]
        columns.extend(args.dataset.columns)
        args.columns = columns

    return args


if __name__ == "__main__":
    main(get_args())
