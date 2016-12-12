---
layout: post
title: GateKeeper—Migrating away from IP-based controlled access
subtitle: We are moving away from IP-based authentication to fine-tune user access to data
categories: announcement
tags:
- site
- feature
- security
- users
author: Terry Mun
---
We are announcing in a change in user access to controlled, internal data available to CARB members **with immediate effect**. Traditionally, we have been offering access to CARB members based on their IP address (and VPN connection). This strategy worked out fine for quite awhile as there is no need to fine tune access to internal data.

However, in light of the changing personnel in the lab, coupled with collaborators who we wish to grant access to sensitive data, detecting IP addresses to restrict data access will become overwhelmingly tedious and unreliable.

### Ensuring continue, undisrupted acccess

Therefore, all CARB users will be required to [register for a *Lotus Base* account](/users/register) in order to access internal files, if they have not done so already. Features that are affected by this change are:
- [Expression Atlas](/expat/) (ExpAt)
- [Genome Browser](/genome/) (JBrowse)
- [*LORE1* genotyping primers](/tools/primers)
- [*Lotus* BLAST](/blast/)
- [Sequence Retriever](/tools/seqret) (SeqRet)

<p class="user-message note"><span class="icon-info-circled"></span>Registering for an account does not automatically grant access to internally-available resources.</p>

The current system administrator (Terry, [terry@mbg.au.dk](mailto:terry@mbg.au.dk)) will be responsible for adding verified/validated CARB members into a user group that has exclusive access to internal data. He will be notified when new CARB members have registered for accounts, and will perform necessary validation with new users before granting access.

If you are a CARB collaborator who wish to have access to internal data, [please do not hesitate to reach out to us](/meta/contact).

### How are you affected?
**Pre-existing CARB users with accounts with *Lotus* Base will not see any service disruptions**—you have been automatically migrated over to the new controlled access system. To access internally available data, simply remember to [log in](/users/login). Users that do not have an account with *Lotus* Base, however, are strongly encouraged to register. Terry will keep in touch with you once you have registered for an account.

### Your security is our priority
In order to prevent session hijacking, we recycle user sessions frequently. This means that you might be logged off within 24 hours of logging in, unless you have explicitly asked to be logged in for a week when signing in. You are encouraged not to save your login credentials on public terminals.

If you suspect your account is being compromised or you have misplaced your login credentials, you can [reset your password](/users/reset) and regain control over your account.