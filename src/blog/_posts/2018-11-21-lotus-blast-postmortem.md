---
layout: post
title: Post-mortem on downtime experienced by BLAST-related tools
subtitle: 
categories: announcement
tags:
- site
- postmortem
author: Terry Mun
---
Over the course of the weekend of November 17&ndash;18 of 2018 and the subsequent working week that follows, all BLAST-related tools on *Lotus* Base became inaccessible. The affected modules were:

- *Lotus* BLAST, which runs SequenceServer v1.0.9 as a Passenger app
- The Sequence Retrieval tool (SeqRet), which relies on being able to sniff out BLAST database metadata by executing the `blastdbcmd` binary

### Diagnosing the issue

The issue was two-fold:

1. SequenceServer was running as a Phusion Passenger app initialized by an arbitrarily named user via the `PassengerUser` option in the `httpd.conf` file. The user should have been `apache`, so that the processes spawned by Apache will have the correct read permissions to access all app binaries.
2. The Sequence Retrieval tool calls an internal API endpoint which relies on being able to execute the `blastdbcmd` binary. However, since the binary belongs to a different user group, the API wil fail and return an empty array: this causes PHP to throw an error when attempting to display BLAST database-related metadata.

### What was done to fix it?

By updating the read permissions for the BLAST binaries and changing the `PassengerUser` for Sequence Server fixes the issue.