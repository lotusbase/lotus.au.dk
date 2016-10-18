import sys, csv, json, numpy, scipy, math
from numpy import array
from scipy import stats
from scipy.cluster.vq import vq, kmeans2, whiten

# Parse incoming JSON data
with open(sys.argv[1]) as tempfile:
	for line in tempfile:
		feed = json.loads(line)

# Construct output
output = {}

# Perform Fisher's exact test, one way
for go_term, go_data in feed.items():
	oddsratio, pvalue = stats.fisher_exact(go_data['matrix'])
	output[go_term] = {'pvalue': pvalue, 'oddsratio': oddsratio}

# Print JSON response
print('Content-Type: application/json\n')
print(json.dumps(output, sort_keys=True))
