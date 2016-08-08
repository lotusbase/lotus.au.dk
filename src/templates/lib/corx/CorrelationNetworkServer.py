import os, random, binascii, json, subprocess, configparser

import datetime as dt
from queue import Queue

import threading
from threading import Thread

import pony.orm as pny
import rpyc
from rpyc.utils.server import ThreadedServer

import Datasets

# Parse config
config = configparser.ConfigParser()
config.read(os.path.join(os.path.dirname(__file__), os.pardir, os.pardir, 'config.ini'))

#db = pny.Database("sqlite", "correlation_network_jobs.db", create_db=True)
db = pny.Database('mysql', host=config['general']['host'].strip("'"), user=config['general']['user'].strip("'"), passwd=config['general']['pass'].strip("'"), db=config['general']['db'].strip("'"))

# Get directory of writing data file to
# Data directory is located at the web root's /data/cornea/jobs
# Current file directory is located at the web root's /lib/corx
data_dir = os.path.join(os.path.abspath(__file__ + "/../../../"), 'data', 'cornea', 'jobs')

# Try making the data directory first
try:
    os.makedirs(data_dir)
except:
    pass


# noinspection PyClassHasNoInit

# Job statuses
class JobStatus:
    Submitted, Running, Done, Error, Expired = range(1, 6)

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

    # Standard job
    standard_job = pny.Required(int, default=0)

db.generate_mapping(create_tables=False)


class RPYCServer:
    @pny.db_session
    def __init__(self, server_lock):
        self.server_lock = server_lock

        running_jobs = pny.select(job for job in CorrelationNetworkJob if job.status == JobStatus.Running)
        for job in running_jobs:
            # Remove the out_file of the job if it exists.
            try:
                os.remove(os.path.join(data_dir, "{0}.json.gz".format(job.hash_id)))
            except:
                pass
            finally:
                job.status = JobStatus.Error
                job.status_reason = "unexpected_server_crash"

        submitted_jobs = pny.select(j for j in CorrelationNetworkJob
                                    if j.status == JobStatus.Submitted).order_by(CorrelationNetworkJob.submit_time.asc)
        self.queue = Queue()
        for job in submitted_jobs:
            self.queue.put(job.id)

    @pny.db_session
    def submit_job(self, dataset, candidates, columns, verbose, threshold, minimum_cluster_size, owner, owner_salt):
        # Generate 16bit hash
        hash_id = binascii.b2a_hex(os.urandom(16)).decode('utf-8');

        # Store columns by index to save space
        _dataset = Datasets.all_by_name[dataset]
        column_indexes = []
        if (columns != ''):
            for c in columns.split(','):
                column_indexes.append(_dataset.columns.index(c))

        # Create job
        job = CorrelationNetworkJob(dataset=dataset, candidates=candidates, columns=','.join(str(x) for x in column_indexes), verbose=verbose,
                                    threshold=threshold, minimum_cluster_size=minimum_cluster_size, owner=owner, owner_salt=owner_salt, hash_id=hash_id)
        try:
            self.server_lock.acquire()
            pny.commit()
            self.queue.put(job.id)
            return "{{\"job_hash_id\": \"{0}\", \"job_owner\": \"{1}\"}}".format(hash_id, owner);
        except:
            raise
        finally:
            self.server_lock.release()

    @staticmethod
    @pny.db_session
    def job_status(job_hash_id):
        job = pny.get(j for j in CorrelationNetworkJob if j.hash_id == job_hash_id)
        return (job.hash_id, job.status, job.status_reason, job.owner, j.owner_salt, job.dataset) if job is not None else (-1, 4, "", "", "")

    @staticmethod
    @pny.db_session
    def job_queue(job_hash_id):
        # Retrieve job ID by hash
        jobID = pny.get(j.id for j in CorrelationNetworkJob if j.hash_id == job_hash_id)

        # Look for uncompleted jobs
        queue = pny.select(j for j in CorrelationNetworkJob if j.id < jobID and (j.status == 1 or j.status == 2))

        # Return data
        return [(j.hash_id, j.status, j.status_reason, j.owner, j.owner_salt, j.dataset) for j in queue] if queue is not None else [(-1, 4, "", "", "")]


@pny.db_session
def main():
    class RPYCService(rpyc.Service):
        @staticmethod
        def exposed_job_status(job_hash_id):
            return server_obj.job_status(job_hash_id)

        @staticmethod
        def exposed_submit_job(dataset, candidates="", columns="", verbose=False, threshold=0.9,
                               minimum_cluster_size=5, owner="", owner_salt=""):
            return server_obj.submit_job(dataset, candidates, columns, verbose, threshold, minimum_cluster_size, owner, owner_salt)

        @staticmethod
        def exposed_job_queue(job_hash_id):
            return server_obj.job_queue(job_hash_id)

    try:
        server = ThreadedServer(RPYCService, port=3030, protocol_config={"allow_public_attrs": True,
                                                                         "allow_pickle": True})
    except OSError:
        # The port is in use. Exit gracefully.
        print("CorrelationNetworkServer.py: The port is in use (the server is already running).")
        return

    t = Thread(target=server.start)
    t.daemon = True
    t.start()

    server_lock = threading.Lock()
    server_obj = RPYCServer(server_lock)

    while True:
        job_id = server_obj.queue.get()
        print("Got job no. {0} from the queue. Loading job from DB.".format(job_id))
        job = pny.get(j for j in CorrelationNetworkJob if j.id == job_id)
        print("Starting job no. {0} with the hash {1} using dataset {2}.".format(job.id, job.hash_id, job.dataset))
        job.status = JobStatus.Running
        job.start_time = dt.datetime.today()
        pny.commit()
        try:
            dataset = Datasets.all_by_name[job.dataset]
            candidates = None
            if job.candidates != "":
                candidates = job.candidates.split(",")

            columns = []
            if job.columns == "":
                columns.extend(dataset.columns)
                columns_mapped = [dataset.name_column]
                columns_mapped.extend(dataset.columns)
            else:
                columns.extend(job.columns.split(","))
                # Reverse map columns by index
                columns_mapped = [dataset.name_column]
                for c in columns:
                    columns_mapped.append(dataset.columns[int(c)])

            out_file = os.path.join(data_dir, "{0}.json.gz".format(job.hash_id))
            import CorrelationNetwork
            CorrelationNetwork.create_correlation_network(dataset, candidates, columns_mapped, job.verbose, out_file,
                                                          job.threshold, job.minimum_cluster_size, job.owner, job.owner_salt, job.hash_id)
            job.status = JobStatus.Done
            job.end_time = dt.datetime.today()
            print("Job no. {0} (hash: {1}) done.".format(job.id, job.hash_id))
        except Exception as e:
            job.status = JobStatus.Error
            job.status_reason = json.dumps(e.args)
            print("Job no. {0} (hash: {1}) crashed: {1}".format(job.id, job.hash_id, e.args))
        finally:
            pny.commit()

            if job.owner != None or job.owner != False or job.owner != '':
                print('Sending mail to job owner at ' + str(job.owner))
                mailer = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), 'lib', 'corx', 'mail.php')
                script_response = subprocess.check_output(["php", mailer, json.dumps({ 'owner': job.owner, 'hash_id': job.hash_id })])
                print(str(script_response))

if __name__ == '__main__':
    main()
