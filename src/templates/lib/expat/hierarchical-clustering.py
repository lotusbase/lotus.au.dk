import sys, csv, json, numpy, scipy, math, random
import scipy.cluster.hierarchy as hier
from functools import reduce

# Parse incoming JSON data
with open(sys.argv[1]) as tempfile:
	for line in tempfile:
		feed = json.loads(line)

# Assign variables
data = json.loads(feed['melted'])
rowHeaders = feed['row']
colHeaders = feed['condition']
config = feed['config']

# Configuration
rowClusterCutoff = float(config['rowClusterCutoff'])
colClusterCutoff = float(config['colClusterCutoff'])
linkageMethod = str(config['linkageMethod'])
linkageMetric = str(config['linkageMetric'])
fclusterCriterion = 'distance'
dataTransform = config['dataTransform']

# Configuration
#clusterCutoff = 0.25
#linkageMethod = 'complete'
#linkageMetric = 'euclidean'
#fclusterCriterion = 'distance'

# Construct output
output = {}
output['data'] = {'row': {}, 'condition': {}, 'dendrogram': {}}
output['meta'] = {'type': 'hierarchical agglomerative', 'wiki': 'https://en.wikipedia.org/wiki/Hierarchical_clustering', 'config': {'rowClusterCutoff': rowClusterCutoff, 'colClusterCutoff': colClusterCutoff, 'linkageMethod': linkageMethod, 'linkageMetric': linkageMetric, 'fclusterCriterion': fclusterCriterion, 'dataTransform': dataTransform}}

# Strip everything but value
dataOnly = []
for index, cell in enumerate(data):
	dataOnly.append(float(cell['value']))

# Convert data to data matrix
dataMatrix = numpy.mat(dataOnly)
rowMatrix = dataMatrix.reshape(len(rowHeaders), len(colHeaders))
colMatrix = rowMatrix.T

# Compute row dendrogram
distance_row = hier.distance.pdist(rowMatrix)
squareDistance_row = hier.distance.squareform(distance_row)
linkage_row = hier.linkage(squareDistance_row, method=linkageMethod, metric=linkageMetric)
ind_row = hier.fcluster(linkage_row, rowClusterCutoff*max(linkage_row[:,2]), criterion=fclusterCriterion)

# Compute column dendrogram
distance_col = hier.distance.pdist(colMatrix)
squareDistance_col = hier.distance.squareform(distance_col)
linkage_col = hier.linkage(squareDistance_col, method=linkageMethod, metric=linkageMetric)
ind_col = hier.fcluster(linkage_col, colClusterCutoff*max(linkage_col[:,2]), criterion=fclusterCriterion)

# Export dendrogram data for d3.js
# Create dictionary for labeling nodes by their IDs
row_to_name = dict(zip(range(len(feed['row'])), feed['row']))
col_to_name = dict(zip(range(len(feed['condition'])), feed['condition']))

# Create a nested dictionary from the ClusterNode's returned by SciPy
def add_node(node, parent):
	# First create the new node and append it to its parent's children
	# Also add node distance and count
	newNode = dict(node_id=node.id, children=[], dist=node.dist, count=node.count)
	parent["children"].append(newNode)

	# Recursively add the current node's children
	if node.left: add_node(node.left, newNode)
	if node.right: add_node(node.right, newNode)

# Label each node with the names of each leaf in its subtree
def label_row_tree(n):
	# If the node is a leaf, then we have its name
	if len(n["children"]) == 0:
		leafNames = [row_to_name[n["node_id"]]]
		n['cluster'] = dictRows[leafNames[0]]

	# If not, flatten all the leaves in the node's subtree
	else:
		leafNames = reduce(lambda ls, c: ls + label_row_tree(c), n["children"], [])
		clusters = []
		for leafName in leafNames:
			clusters.append(dictRows[leafName])
		if clusters[1:] == clusters[:-1]:
			n['cluster'] = clusters[0]

	# Delete the node id since we don't need it anymore and
	# it makes for cleaner JSON
	del n["node_id"]

	# Labeling convention: "-"-separated leaf names
	n["name"] = name = "-".join(map(str, leafNames))
	if len(leafNames) == len(rowHeaders):
		global sortedRowHeaders
		sortedRowHeaders = leafNames

	return leafNames

# Define clustering
rowClusters = ind_row.tolist()

# Sort rows
dictRows = dict(zip(rowHeaders, rowClusters))

# Dendrogram for rows
rowTree = scipy.cluster.hierarchy.to_tree(linkage_row, rd=False)
rowTree_dendrogram = dict(children=[], name="RowRoot")
add_node(rowTree, rowTree_dendrogram)
label_row_tree(rowTree_dendrogram["children"][0])
output['data']['dendrogram']['row'] = rowTree_dendrogram

# Sort row clusters
sortedRowClusters = [dictRows[v] for v in sortedRowHeaders]
unique_row = []
unique_row_counts = []
for i in sortedRowClusters:
	if i not in unique_row:
		unique_row.append(i)
		unique_row_counts.append(0)
		unique_row_counts[len(unique_row_counts)-1] += 1
	else:
		unique_row_counts[len(unique_row_counts)-1] += 1

# Write
output['data']['row']['order'] = sortedRowHeaders
output['data']['row']['cluster'] = sortedRowClusters
output['data']['row']['clusterData'] = unique_row_counts



# Label each node with the names of each leaf in its subtree
def label_col_tree(n):
	# If the node is a leaf, then we have its name
	if len(n['children']) == 0:
		leafNames = [col_to_name[n['node_id']]]
		n['cluster'] = dictCols[leafNames[0]]

	# If not, flatten all the leaves in the node's subtree
	else:
		leafNames = reduce(lambda ls, c: ls + label_col_tree(c), n['children'], [])
		clusters = []
		for leafName in leafNames:
			clusters.append(dictCols[leafName])
		if clusters[1:] == clusters[:-1]:
			n['cluster'] = clusters[0]

	# Delete the node id since we don't need it anymore and
	# it makes for cleaner JSON
	del n['node_id']

	# Labeling convention: '-'-separated leaf names
	n['name'] = name = '-'.join(map(str, leafNames))
	if len(leafNames) == len(colHeaders):
		global sortedColHeaders
		sortedColHeaders = leafNames

	return leafNames

# Define clustering
colClusters = ind_col.tolist()

# Sort cols
dictCols = dict(zip(colHeaders, colClusters))

# Dendrogram for conditions
colTree = scipy.cluster.hierarchy.to_tree(linkage_col, rd=False)
colTree_dendrogram = dict(children=[], name="ConditionRoot")
add_node(colTree, colTree_dendrogram)
label_col_tree(colTree_dendrogram["children"][0])
output['data']['dendrogram']['condition'] = colTree_dendrogram

# Sort col clusters
sortedColClusters = [dictCols[v] for v in sortedColHeaders]
unique_col = []
unique_col_counts = []
for i in sortedColClusters:
	if i not in unique_col:
		unique_col.append(i)
		unique_col_counts.append(0)
		unique_col_counts[len(unique_col_counts)-1] += 1
	else:
		unique_col_counts[len(unique_col_counts)-1] += 1

# Write
output['data']['condition']['order'] = sortedColHeaders
output['data']['condition']['cluster'] = sortedColClusters
output['data']['condition']['clusterData'] = unique_col_counts


# Print JSON response
print('Content-Type: application/json\n')
print(json.dumps(output, sort_keys=True))

#except:
#	print("Content-Type: application/json\n")
#	print(json.dumps({'error': 'Error in reading JSON input.'}, sort_keys=True))
#	sys.exit(1)