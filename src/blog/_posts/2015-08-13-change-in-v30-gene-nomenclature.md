---
layout: post
title: Change in v3.0 gene nomenclature
categories: announcement
tags:
- gene
- nomenclature
author: Terry Mun
summary: We are updating gene nomenclatures in <em>Lotus japonicus</em> to make gene names compatible with future genome versions.
---
Due to the upcoming release of version 4 of the *Lotus* genome and gene accession IDs, and that we are expecting coordinates to change drastically, we pre-empted a possible clash in the namespace of gene accessions. Therefore, we have implemented a change in version 3.0 gene accessions for *Lotus japonicus* with immediate effect.

For example, the old accessions ID for the gene "ATP synthase D chain-related protein" is `Lj1g2536050.1`. With the updated nomenclature where the version number is appended after the chromosome name, the new accession ID for the same gene will be <code>Lj1g<strong>3v</strong>2536050.1</code>.

A quick way to convert your existing gene IDs, should you want to search them against our databases, would be to append `3v` after the `Lj[…]g[…]` text in your gene accession ID so that it becomes `Lj[…]g3v[…]`. The databases and site features affected by this update in nomenclature:

- [LORE1 search](/lore1/search)
- [BLAST](/blast/) databases
- Gene annotations (&ge;v3.0)
- Genic and exonic insertions databases (&ge;v3.0)
- [Expression Atlas (ExpAt)](/expat/) databases