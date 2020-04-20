---
layout: post
title: <em>L. japonicus</em> Gifu v1.2 genome
subtitle: 
categories: announcement
tags:
- lore1
- gifu
- genome
author: Terry Mun
summary: <em>L. japonicus</em> Gifu v1.2 genome has been publicly released. It is now accessible via the Genome Browser, as well as other tools on the sites, such as the Expression Atlas (ExpAt) and View tools.
coverImage: /dist/images/content/20200420/expat.svg
socialMediaCoverImage: /dist/images/content/20200420/expat.jpg
---

The *Lotus japonicus* Gifu genome assembly v1.2 is now officially released and [an associated pre-publication manuscript has been submitted to bioXriv](https://www.biorxiv.org/content/10.1101/2020.04.17.042473v1). Datasets on *Lotus* Base has therefore seen some restructuring to support incoming data from a new genome assembly.

> **Note to users with elevated privileges that have access to the v1.1 assembly preview:**
> 
> v1.1 contains gene predictions that have been reworked and with a deprecated naming nomenclature, so there is no one-to-one mapping between the gene IDs from v1.1 to v1.2. We strongly encourage you to use the v1.2 data going forward.

### Gifu data available today

The following tools on the site have been updated to allow access to *L. japonicus* Gifu data.

#### Genome browser

The Gifu genome v1.2 is now accessible as a new dataset on our JBrowse implementation, [the Genome Browser](/genome/?data=genomes/lotus-japonicus/gifu/v1.2). At the time of writing, the new genome contains the following datasets:

* Gene model with human readable annotations, GO annotations, and InterPro domain predictions (GFF3 file is available for download, see below)
* Non-coding RNAs
* Genome gaps
* Repeats

#### Expression Atlas (ExpAt)

The ExpAt tool has now been updated with new RNAseq data mapped to the Gifu genome by Dugald Reid. Here is a [sample expression heatmap produced](/expat/?ids-input=&ids=LotjaGi3g1v0307700%2CLotjaGi2g1v0343300%2CLotjaGi1g1v0643700%2CLotjaGi3g1v0414350%2CLotjaGi1g1v0257100%2CLotjaGi4g1v0343900%2CLotjaGi5g1v0106700%2CLotjaGi1g1v0001500%2CLotjaGi3g1v0512000&dataset=reidd-2020-gifuatlas&conditions=&data_transform=normalize&idtype=geneid) using the candidate genes published in the manuscript:

<img src="/dist/images/content/20200420/expat.svg" alt="Sample heatmap generated using Gifu predicted proteins" title="Sample heatmap generated using Gifu predicted proteins" />

List of genes included in the heatmap:

* [LotjaGi3g1v0307700](/view/gene/LotjaGi3g1v0307700), *LjCCaMK*
* [LotjaGi2g1v0343300](/view/gene/LotjaGi2g1v0343300), *LjCyclops*
* [LotjaGi1g1v0643700](/view/gene/LotjaGi1g1v0643700), *LjErn1*
* [LotjaGi3g1v0414350](/view/gene/LotjaGi3g1v0414350), *LjNin*
* [LotjaGi1g1v0257100](/view/gene/LotjaGi1g1v0257100), *LjNsp2*
* [LotjaGi4g1v0343900](/view/gene/LotjaGi4g1v0343900), *LjNf-yb1*
* [LotjaGi5g1v0106700](/view/gene/LotjaGi5g1v0106700), *LjNf-ya1*
* [LotjaGi1g1v0001500](/view/gene/LotjaGi1g1v0001500), *LjNin*
* [LotjaGi3g1v0512000](/view/gene/LotjaGi3g1v0512000), *LjHar1*

#### View

The [View tool](/view/) is seen as a replacement for Transcript Explorer (TrEx), which allows you to have a quick overview for individual transcripts, genes, GO annotations and more. For example, if you are interested in all the data associated with the gene LjNin (LotjaGi1g1v0001500), you can search for it on the View page, or [access the link directly](/view/gene/LotjaGi1g1v0001500).

#### Downloadable data

All Gifu-related downloadable data can be [accessed from our data page](/data/download?search=Gifu). The newly published files are:

* FASTA files for the genome assembly, coding sequences, and predicted protein sequences
* GFF3 file for Gifu predicted gene annotations, containing human readable annotations, GO annotations, and InterPro domain predictions
* Gene Ontology file

### Future roadmap

In the next few months, we will be gradually mapping additional data to the Gifu genome:

* *LORE1* insertion data