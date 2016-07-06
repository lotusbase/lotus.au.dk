---
layout: post
title: Adjusting BLAST algorithm for short sequences
categories: help
tags:
- blast
- tools
author: Terry Mun
---
If your BLAST queries are short (e.g. miRNA sequences), you might want to adjust the BLAST algorithm to get optimum results. Short sequences that are less than 20 bases will often not find any significant matches to the database entries under the standard nucleotide–nucleotide BLAST settings. The usual reasons for this are that the significance threshold governed by the expect value parameter is set too stringently and the default word size parameter is set too high.

Therefore, you can modify your default search parameters as follow, as per [NCBI's recommendation](http://www.ncbi.nlm.nih.gov/blast/Why.shtml):

<table>
	<thead>
		<tr>
			<td>Program</td>
			<td>Word Size</td>
			<td>Filter</td>
			<td>Expect Value</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Standard Nucleotide BLAST</td>
			<td>11</td>
			<td>On (DUST)</td>
			<td>10</td>
		</tr>
		<tr>
			<td>Short Queries (&lt; 20 bases)</td>
			<td>7</td>
			<td>Off</td>
			<td>1000</td>
		</tr>
	</tbody>
</table>

You may enter the above settings into the "advanced paramters" field in the BLAST search as:

```
-word_size 7 -dust no -evalue 1000
```