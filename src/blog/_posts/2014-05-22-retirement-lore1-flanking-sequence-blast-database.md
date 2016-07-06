---
layout: post
title: Retirement of LORE1 flanking sequences BLAST database
categories: announcement
tags:
- blast
- lore1
- tools
author: Terry Mun
---
After much deliberation, we have decided to retire the LORE1 flanking sequences database because it does not contain LORE1 original reads, but only sequences that were extracted from the reference genome. As we increasingly saturate the *Lotus* genome with LORE1 insertions, there will be an impractical level of redundancy in the LORE1 flanking sequence database as the &plusmn;1000bp regions around the insert overlaps.

When you intend to search for LORE1 lines based on a candidate amino acid sequence from another organism (e.g. *Arabidopsis*), you can copy the amino acid sequence and perform either:

- a **BLAST** search against *Lotus japonicus* protein database; or
- a **tBLASTN** search against *Lotus japonicus* genome database and use the start and end positions of listed hits in the [LORE1 line search](/lore1/search), or in the [genome browser](/genome)