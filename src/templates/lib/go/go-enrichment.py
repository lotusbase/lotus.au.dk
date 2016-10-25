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
for go_node in ['leaf','ancestor']:
	for go_term, go_data in feed[go_node].items():
		oddsratio, pvalue = stats.fisher_exact(go_data['matrix'])

		# Parse infinity or NaN
		def _nan(f):
			if f == float('inf'):
				return str('+inf')
			elif f == float('-inf'):
				return str('-inf')
			elif math.isnan(f):
				return str('NaN')
			else:
				return float(f)

		output[go_node][go_term] = {'pvalue': _nan(pvalue), 'oddsratio': _nan(oddsratio)}

# Print JSON response
print('Content-Type: application/json\n')
print(json.dumps(output, sort_keys=True))
