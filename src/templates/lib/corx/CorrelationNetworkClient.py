import argparse
import json
import rpyc
import Datasets
import configparser

# Parse config
config = configparser.ConfigParser()
config.read('../../config.ini')

conn = rpyc.connect(config['rpyc']['host'].strip("'"), int(config['rpyc']['port'].strip("'")), config={"allow_public_attrs": True,
                                               "allow_pickle": True})
server = conn.root

parser = argparse.ArgumentParser(description="Interface with the correlation network server.")
sp = parser.add_subparsers()

sp_submit = sp.add_parser("submit", help="submit a correlation network job")
sp_submit.set_defaults(action="submit")
sp_submit.add_argument("dataset", help="the dataset",
                       choices=[k for k, _ in Datasets.all_by_name.items()])
sp_submit.add_argument("--candidates", dest="candidates", default="", type=str,
                       help="the candidate genes or probes (separated by ';')")
sp_submit.add_argument("--threshold", dest="threshold", default=0.9, type=float,
                       help="the threshold for when two genes are correlated")
sp_submit.add_argument("--minimum-cluster-size", dest="minimum_cluster_size", default=5, type=int,
                       help="the threshold for when to include a cluster in the output")
sp_submit.add_argument("--columns", dest="columns", help="the conditions to consider (separated by ';')",
                       type=str, default="")
sp_submit.add_argument("--verbose", dest="verbose", help="print verbose information such as timing information",
                       type=bool, default=False)
sp_submit.add_argument("--owner", dest="owner", help="email of the job owner",
                       type=str, default="")
sp_submit.add_argument("--owner-salt", dest="owner_salt", help="internal unique identifier of user that exists in the system",
                       type=str, default="")

sp_status = sp.add_parser("status", help="query the status of a correlation network job")
sp_status.set_defaults(action="status")
sp_status.add_argument("job_hash_id", metavar="job-hash-id", help="get the status of this job by its hash key", type=str)

sp_status = sp.add_parser("queue", help="retrieve queued jobs on the server")
sp_status.set_defaults(action="queue")
sp_status.add_argument("job_hash_id", metavar="job-hash-id", help="get the status of this job by its hash key", type=str)

args = parser.parse_args()

if args.action == "submit":
    job_data = server.submit_job(args.dataset, candidates=args.candidates, columns=args.columns,
                               threshold=args.threshold, minimum_cluster_size=args.minimum_cluster_size,
                               verbose=args.verbose, owner=args.owner, owner_salt=args.owner_salt)
    print(job_data)

elif args.action == "status":
    try:
        status = server.job_status(args.job_hash_id)
        # Zip to dict
        print(json.dumps(dict(zip(['hash_id', 'status', 'status_reason', 'owner', 'owner_salt', 'dataset'], status))))
    except ValueError:
        print("{error: 404}")

elif args.action == "queue":
    try:
        status = server.job_queue(args.job_hash_id)
        print(json.dumps([dict(zip(['hash_id', 'status', 'status_reason', 'owner', 'owner_salt', 'dataset'], j)) for j in status]))
    except ValueError:
        print("{error: True, errorCode: 404}")
