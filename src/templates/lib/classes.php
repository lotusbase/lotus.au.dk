<?php

/* class: MailGenerator */
/* Generates the standard Lotus Base email template */
require_once('classes/mail-generator.php');

/* class: ErrorCatcher */
/* Catches errors */
require_once('classes/error-catcher.php');

/* class: DataReturn */
/* Returns data from the API */
require_once('classes/data-return.php');

/* class: LjGenomeVersion */
/* Checks if a given string matches currently used Lj genome version(s) */
require_once('classes/lj-genome-version.php');

/* class: ExpAt\Query */
/* Performs query and data manipulation for ExpAt */
require_once('classes/expat/query.php');

/* class: ExpAt\Dataset */
/* Generates HTML select element for expat dataset selection */
require_once('classes/expat/dataset.php');

/* class: BLAST\DBMetadata */
/* Retrieves metadata for BLAST databases */
require_once('classes/blast/db-metadata.php');

/* class: BLAST\Query */
/* Performs BLAST queries */
require_once('classes/blast/query.php');

/* class: CORx\CORNEA\Download */
/* Returns the data for CORNEA\Download */
require_once('classes/corx/cornea-download.php');

/* class: Users\AuthToken */
/* Creates and returns authentication token upon login */
require_once('classes/users/auth-token.php');

/* class: Users\Integrate */
/* Integrates OAuth users into current database */
require_once('classes/users/integrate.php');

/* class: PhyAlign\Submit */
/* Submits FASTA sequences with other Clustal Omega settings to the EMBL-EBI server */
require_once('classes/phyalign/submit.php');

/* class: PhyAlign\Data*/
/* Get status and data (if any) of ClustalO job on EMBL-EBI server */
require_once('classes/phyalign/data.php');

/* class: EBI\EBeye */
/* General class for retrieving data from EMBI-EBI server */
require_once('classes/ebi/eb-eye.php');

/* class: View\GO */
/* Classes for Gene Ontology */
require_once('classes/view/go.php');

/* class: Component */
/* Load site components */
require_once('classes/component/document-header.php');
require_once('classes/component/page-header.php');
require_once('classes/component/breadcrumbs.php');
require_once('classes/component/site-search-form.php');
require_once('classes/component/paginate.php');
require_once('classes/component/dropdown.php');

?>