---
layout: post
title: Redesigned LORE1 search form, and pan-version TREX searches
categories: announcement
tags:
- lore1
- trex
- tools
author: Terry Mun
coverImage: /dist/images/content/lore1.jpg
---
*Lotus* Base was originally conceived as a very simple web interface for the searching for, and ordering of, LORE1 lines, but over the years it gradually evolved into a fully-fledged *Lotus japonicus* online resource. Therefore, it is not surprising that the LORE1 search form is one of the most antiquated and complicated components of the site, which we never really got around to upgrading it.

Now the [LORE1 line search page](/lore1/search) has been revamped and brought up to date with the cleaner style of the site in general. To improve user experience, we have removed the step-form-like search flow, which complicated the decidedly simple purpose of the form anyway&mdash;to search for LORE1 lines of interest.

In other news, we have enabled pan-*Lj*-genome-version Transcript Explorer (TREX) searches. Although the form defaults to the **latest version of the genome** (at the time of writing, this would be **v3.0**), it is possible to select from other versions, either in a standalone or combinatory manner, of all hitherto published *L. japonicus* genomes. Do note that due to the way the genome is assembled, **genome coordinates are not preserved across versions**. For example, position 65,536 on chromosome 1 in v2.5 will not be position 65,536 on chromosome 1 in v3.0.