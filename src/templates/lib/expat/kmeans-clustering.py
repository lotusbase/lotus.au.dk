import sys, csv, json, numpy, scipy, math
from numpy import array
from scipy.cluster.vq import vq, kmeans2, whiten

# Parse incoming JSON data
#feed = json.loads(sys.argv[1])
with open(sys.argv[1]) as tempfile:
	for line in tempfile:
		feed = json.loads(line)

# Assign variables
data = json.loads(feed['melted'])
rowHeaders = feed['row']
colHeaders = feed['condition']
config = feed['config']

#data = json.loads('[{"rowID":"Lj4g0281040","condition":"WT_control1","value":"25.57"},{"rowID":"Lj4g0281040","condition":"WT_Drought1","value":"18.59"},{"rowID":"Lj4g0281040","condition":"Ljgln2_2_Control1","value":"23.47"},{"rowID":"Lj4g0281040","condition":"Ljgln2_2_Drought1","value":"16.68"},{"rowID":"Lj4g0281040","condition":"root_4dpicontrol1B","value":"246.55"},{"rowID":"Lj4g0281040","condition":"root_4dpimycorrhized1D","value":"372.75"},{"rowID":"Lj4g0281040","condition":"root_28dpicontrol1A","value":"439.95"},{"rowID":"Lj4g0281040","condition":"root_28dpimycorrhized1C","value":"297.99"},{"rowID":"Lj4g0281040","condition":"WT_root_tip_3w_uninocul_1","value":"513.29"},{"rowID":"Lj4g0281040","condition":"WT_root_3w_uninocul_1","value":"223.95"},{"rowID":"Lj4g0281040","condition":"WT_root_3w_5mM_nitrate_1","value":"160.22"},{"rowID":"Lj4g0281040","condition":"WT_root_6w_5mM_nitrate_1","value":"189.1"},{"rowID":"Lj4g0281040","condition":"WT_shoot_3w_5mM_nitrate_1","value":"28.38"},{"rowID":"Lj4g0281040","condition":"WT_shoot_3w_uninocul_1","value":"32.23"},{"rowID":"Lj4g0281040","condition":"WT_shoot_3w_inocul3_1","value":"43.98"},{"rowID":"Lj4g0281040","condition":"WT_leaf_6w_5mM_nitrate_1","value":"28.83"},{"rowID":"Lj4g0281040","condition":"WT_stem_6w_5mM_nitrate_1","value":"22.06"},{"rowID":"Lj4g0281040","condition":"WT_flower_13w_5mM_nitrate_1","value":"21.81"},{"rowID":"Lj4g0281040","condition":"har1_root_3w_uninocul_2","value":"181.08"},{"rowID":"Lj4g0281040","condition":"har1_root_3w_inocul3_2","value":"229.83"},{"rowID":"Lj4g0281040","condition":"har1_shoot_3w_uninocul_1","value":"34.09"},{"rowID":"Lj4g0281040","condition":"har1_shoot_3w_inocul3_1","value":"34.99"},{"rowID":"Lj4g0281040","condition":"WT_root_3w_nodC_inocul1_1","value":"133.21"},{"rowID":"Lj4g0281040","condition":"WT_root_3w_inocul1_1","value":"421.14"},{"rowID":"Lj4g0281040","condition":"WT_root_3w_inocul3_1","value":"233.9"},{"rowID":"Lj4g0281040","condition":"WT_nodule_3w_inocul14_1","value":"21.13"},{"rowID":"Lj4g0281040","condition":"WT_nodule_3w_inocul21_1","value":"17.63"},{"rowID":"Lj4g0281040","condition":"WT_root_nodule_3w_inocul7_1","value":"116.23"},{"rowID":"Lj4g0281040","condition":"WT_root_nodule_3w_inocul21_1","value":"209.03"},{"rowID":"Lj4g0281040","condition":"WT_rootSZ_3w_uninocul_1","value":"1364.79"},{"rowID":"Lj4g0281040","condition":"WT_rootSZ_3w_Nod_inocul1_1","value":"781.02"},{"rowID":"Lj4g0281040","condition":"WT_rootSZ_3w_inocul1_1","value":"1374.33"},{"rowID":"Lj4g0281040","condition":"nfr5_rootSZ_3w_uninocul_1","value":"960.57"},{"rowID":"Lj4g0281040","condition":"nfr5_rootSZ_3w_inocul1_1","value":"943.42"},{"rowID":"Lj4g0281040","condition":"nfr1_rootSZ_3w_uninocul_1","value":"429.5"},{"rowID":"Lj4g0281040","condition":"nfr1_rootSZ_3w_inocul1_1","value":"984.18"},{"rowID":"Lj4g0281040","condition":"nup133_rootSZ_3w_uninocul_1","value":"387.56"},{"rowID":"Lj4g0281040","condition":"nup133_rootSZ_3w_inocul1_1","value":"604.27"},{"rowID":"Lj4g0281040","condition":"cyclops_root_3w_uninocul","value":"222.2"},{"rowID":"Lj4g0281040","condition":"cyclops_root_nodule_3w_inocul21","value":"108.9"},{"rowID":"Lj4g0281040","condition":"nin_rootSZ_3w_uninocul_1","value":"862"},{"rowID":"Lj4g0281040","condition":"nin_rootSZ_3w_inocul1_1","value":"1525.77"},{"rowID":"Lj4g0281040","condition":"sen1_root_3w_uninocul_1","value":"143.26"},{"rowID":"Lj4g0281040","condition":"sen1_nodule_3w_inocul21_1","value":"20"},{"rowID":"Lj4g0281040","condition":"sst1_root_3w_uninocul_1","value":"173.66"},{"rowID":"Lj4g0281040","condition":"sst1_nodule_3w_inocul21_1","value":"16.42"},{"rowID":"Lj4g0281040","condition":"cyclops_root_3w_inocul","value":"122.66"},{"rowID":"Lj4g0281040","condition":"Shoot_0mM_sodiumChloride_1","value":"34.75"},{"rowID":"Lj4g0281040","condition":"Shoot_25mM_sodiumChloride_Initial_1","value":"21.69"},{"rowID":"Lj4g0281040","condition":"Shoot_50mM_sodiumChloride_Initial_1","value":"21.78"},{"rowID":"Lj4g0281040","condition":"Shoot_75mM_sodiumChloride_Initial_1","value":"20.74"},{"rowID":"Lj4g0281040","condition":"Shoot_50mM_sodiumChloride_Gradual_1","value":"21.66"},{"rowID":"Lj4g0281040","condition":"Shoot_100mM_sodiumChloride_Gradual_1","value":"30.28"},{"rowID":"Lj4g0281040","condition":"Shoot_150mM_sodiumChloride_Gradual_1","value":"26.16"},{"rowID":"Lj4g0281040","condition":"Lburttii_Ctrol_A","value":"55.24"},{"rowID":"Lj4g0281040","condition":"Lburttii_Salt_A","value":"23.15"},{"rowID":"Lj4g0281040","condition":"Lcorniculatus_Ctrol_A","value":"22.18"},{"rowID":"Lj4g0281040","condition":"Lcorniculatus_Salt_A","value":"18.1"},{"rowID":"Lj4g0281040","condition":"Lfilicaulis_Ctrol_A","value":"34.55"},{"rowID":"Lj4g0281040","condition":"Lfilicaulis_Salt_A","value":"28.83"},{"rowID":"Lj4g0281040","condition":"Lglaber_Ctrol_A","value":"23.51"},{"rowID":"Lj4g0281040","condition":"Lglaber_Salt_A","value":"20.69"},{"rowID":"Lj4g0281040","condition":"Ljaponicus_Gifu_Ctrol_A","value":"29.96"},{"rowID":"Lj4g0281040","condition":"Ljaponicus_Gifu_Salt_A","value":"20.01"},{"rowID":"Lj4g0281040","condition":"Ljaponicus_MG20_Ctrol_A","value":"26.72"},{"rowID":"Lj4g0281040","condition":"Ljaponicus_MG20_Salt_A","value":"22.37"},{"rowID":"Lj4g0281040","condition":"Luliginosus_Ctrol_A","value":"21.02"},{"rowID":"Lj4g0281040","condition":"Luliginosus_Salt_A","value":"27.58"},{"rowID":"Lj4g0281040","condition":"Fl_1","value":"13.92"},{"rowID":"Lj4g0281040","condition":"Pod20_1","value":"17.5"},{"rowID":"Lj4g0281040","condition":"Seed10d_1","value":"13.65"},{"rowID":"Lj4g0281040","condition":"Seed12d_1","value":"14.05"},{"rowID":"Lj4g0281040","condition":"Seed14d_1","value":"12.84"},{"rowID":"Lj4g0281040","condition":"Seed16d_1","value":"16.87"},{"rowID":"Lj4g0281040","condition":"Seed20d_1","value":"16.81"},{"rowID":"Lj4g0281040","condition":"Leaf_1","value":"21.41"},{"rowID":"Lj4g0281040","condition":"Pt_1","value":"21.7"},{"rowID":"Lj4g0281040","condition":"Stem_1","value":"20.45"},{"rowID":"Lj4g0281040","condition":"Root_1","value":"310.7"},{"rowID":"Lj4g0281040","condition":"Root0h_1","value":"167.22"},{"rowID":"Lj4g0281040","condition":"Nod21_1","value":"17.24"}]')
#rowHeaders = json.loads('["Lj4g0281040"]')
#colHeaders = json.loads('["WT_control1","WT_Drought1","Ljgln2_2_Control1","Ljgln2_2_Drought1","root_4dpicontrol1B","root_4dpimycorrhized1D","root_28dpicontrol1A","root_28dpimycorrhized1C","WT_root_tip_3w_uninocul_1","WT_root_3w_uninocul_1","WT_root_3w_5mM_nitrate_1","WT_root_6w_5mM_nitrate_1","WT_shoot_3w_5mM_nitrate_1","WT_shoot_3w_uninocul_1","WT_shoot_3w_inocul3_1","WT_leaf_6w_5mM_nitrate_1","WT_stem_6w_5mM_nitrate_1","WT_flower_13w_5mM_nitrate_1","har1_root_3w_uninocul_2","har1_root_3w_inocul3_2","har1_shoot_3w_uninocul_1","har1_shoot_3w_inocul3_1","WT_root_3w_nodC_inocul1_1","WT_root_3w_inocul1_1","WT_root_3w_inocul3_1","WT_nodule_3w_inocul14_1","WT_nodule_3w_inocul21_1","WT_root_nodule_3w_inocul7_1","WT_root_nodule_3w_inocul21_1","WT_rootSZ_3w_uninocul_1","WT_rootSZ_3w_Nod_inocul1_1","WT_rootSZ_3w_inocul1_1","nfr5_rootSZ_3w_uninocul_1","nfr5_rootSZ_3w_inocul1_1","nfr1_rootSZ_3w_uninocul_1","nfr1_rootSZ_3w_inocul1_1","nup133_rootSZ_3w_uninocul_1","nup133_rootSZ_3w_inocul1_1","cyclops_root_3w_uninocul","cyclops_root_nodule_3w_inocul21","nin_rootSZ_3w_uninocul_1","nin_rootSZ_3w_inocul1_1","sen1_root_3w_uninocul_1","sen1_nodule_3w_inocul21_1","sst1_root_3w_uninocul_1","sst1_nodule_3w_inocul21_1","cyclops_root_3w_inocul","Shoot_0mM_sodiumChloride_1","Shoot_25mM_sodiumChloride_Initial_1","Shoot_50mM_sodiumChloride_Initial_1","Shoot_75mM_sodiumChloride_Initial_1","Shoot_50mM_sodiumChloride_Gradual_1","Shoot_100mM_sodiumChloride_Gradual_1","Shoot_150mM_sodiumChloride_Gradual_1","Lburttii_Ctrol_A","Lburttii_Salt_A","Lcorniculatus_Ctrol_A","Lcorniculatus_Salt_A","Lfilicaulis_Ctrol_A","Lfilicaulis_Salt_A","Lglaber_Ctrol_A","Lglaber_Salt_A","Ljaponicus_Gifu_Ctrol_A","Ljaponicus_Gifu_Salt_A","Ljaponicus_MG20_Ctrol_A","Ljaponicus_MG20_Salt_A","Luliginosus_Ctrol_A","Luliginosus_Salt_A","Fl_1","Pod20_1","Seed10d_1","Seed12d_1","Seed14d_1","Seed16d_1","Seed20d_1","Leaf_1","Pt_1","Stem_1","Root_1","Root0h_1","Nod21_1"]')

# Construct output
output = {}
output['data'] = {'row': {}, 'condition': {}}
output['meta'] = {'type': 'k-means', 'wiki': 'https://en.wikipedia.org/wiki/K-means_clustering'}

# Strip everything but value
dataOnly = []
for index, cell in enumerate(data):
	dataOnly.append(float(cell['value']))

# Convert data to numpy array
dataOnly = numpy.array(dataOnly)

# Convert data to data matrix
k_col = kmeans2(dataOnly, int(math.sqrt(len(colHeaders))), iter=10, thresh=1e-05)
k_row = kmeans2(dataOnly, int(math.sqrt(len(rowHeaders))), iter=10, thresh=1e-05)

# Sort columns based on their clustering order
ind_col = k_col[1].tolist()
sorted_col = sorted(ind_col)
unique_col = []
unique_col_counts = []
for i in sorted_col:
	if i not in unique_col:
		unique_col.append(i)
		unique_col_counts.append(0)
		unique_col_counts[len(unique_col_counts)-1] += 1
	else:
		unique_col_counts[len(unique_col_counts)-1] += 1

output['data']['condition']['order'] = [colHeaders for (ind_col, colHeaders) in sorted(zip(ind_col, colHeaders))]
output['data']['condition']['clusterData'] = unique_col_counts

# Sort rows based on their clustering order
ind_row = k_row[1].tolist()
sorted_row = sorted(ind_row)
unique_row = []
unique_row_counts = []
for i in sorted_row:
	if i not in unique_row:
		unique_row.append(i)
		unique_row_counts.append(0)
		unique_row_counts[len(unique_row_counts)-1] += 1
	else:
		unique_row_counts[len(unique_row_counts)-1] += 1

output['data']['row']['order'] = [rowHeaders for (ind_row, rowHeaders) in sorted(zip(ind_row, rowHeaders))]
output['data']['row']['clusterData'] = unique_row_counts

# Print JSON response
print('Content-Type: application/json\n')
print(json.dumps(output, sort_keys=True))
