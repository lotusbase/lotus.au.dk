import sys, csv, json, scipy, math
import numpy as np
from scipy import stats

# Credits:
# FDR correction adopted from:
# https://github.com/statsmodels/statsmodels/blob/4b55fa4871cf3f1dbd2e30bfe00d80df87d4b340/statsmodels/stats/multitest.py

# Parse incoming JSON data
with open(sys.argv[1]) as tempfile:
	for line in tempfile:
		feed = json.loads(line)

# ECDF
# No frills empirical cdf used in fdrcorrection
def _ecdf(x):
	nobs = len(x)
	return np.arange(1,nobs+1)/float(nobs)

# Benjamini-Hochberg correction
def fdrcorrection(pvals, alpha=0.05, method='indep', is_sorted=False):

	if not is_sorted:
		pvals_sortind = np.argsort(pvals)
		pvals_sorted = np.take(pvals, pvals_sortind)
	else:
		pvals_sorted = pvals  # alias

	if method in ['i', 'indep', 'p', 'poscorr']:
		ecdffactor = _ecdf(pvals_sorted)
	elif method in ['n', 'negcorr']:
		cm = np.sum(1./np.arange(1, len(pvals_sorted)+1))   #corrected this
		ecdffactor = _ecdf(pvals_sorted) / cm
	else:
		raise ValueError('only indep and necorr implemented')
	reject = pvals_sorted <= ecdffactor*alpha
	if reject.any():
		rejectmax = max(np.nonzero(reject)[0])
		reject[:rejectmax] = True

	pvals_corrected_raw = pvals_sorted / ecdffactor
	pvals_corrected = np.minimum.accumulate(pvals_corrected_raw[::-1])[::-1]
	del pvals_corrected_raw
	pvals_corrected[pvals_corrected>1] = 1
	if not is_sorted:
		pvals_corrected_ = np.empty_like(pvals_corrected)
		pvals_corrected_[pvals_sortind] = pvals_corrected
		del pvals_corrected
		reject_ = np.empty_like(reject)
		reject_[pvals_sortind] = reject
		return reject_, pvals, pvals_corrected_
	else:
		return reject, pvals, pvals_corrected

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

# Construct output
output = {}

# Store p-values and GO terms
pvalues = []
go_terms = []

# Get settings
settings = feed['settings'];

# Perform Fisher's exact test, one way
for go_node in ['go_data']:

	output[go_node] = {}

	# Iterate through the first time to perform statistical test and collect all pvalues
	for go_term, go_data in feed[go_node].items():

		# Perform Fisher's exact test
		oddsratio, pvalue = stats.fisher_exact(go_data['matrix'])
		output[go_node][go_term] = {'pvalue': {'uncorrected': _nan(pvalue), 'corrected': {}}, 'oddsratio': _nan(oddsratio)}

		# Store pvalues
		if isinstance(_nan(pvalue), float):
			pvalues.append(pvalue)
			go_terms.append(go_term)

			# Add Bonferroni corrected value right now
			output[go_node][go_term]['pvalue']['corrected']['bonferroni'] = max(min(1, pvalue * len(feed[go_node])), 0)

	# Perform BH correction on all collected pvalues and zip with GO terms
	p_rejected, p_uncorrected, p_corrected = fdrcorrection(pvalues)
	_p_corrected = dict(zip(go_terms, p_corrected))

	# Iterate through the second time to correct pvalues using BH algorithm
	for go_term, go_data in feed[go_node].items():

		if go_data['data']['queryCount'] > 1:
			output[go_node][go_term]['pvalue']['corrected']['bh'] = _p_corrected[go_term]

# Print JSON response
print('Content-Type: application/json\n')
print(json.dumps(output, sort_keys=True))
