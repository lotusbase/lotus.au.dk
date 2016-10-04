<?php

namespace LotusBase\BLAST;

/* BLAST\DBMetadata */
class DBMetadata {

	// Vars
	private $_vars = array(
		'blacklist' => array(),
		'whitelist' => array()
		);

	// Database metadata
	private $blast_db_metadata = array();
	private $blast_db_metadata_extra = array(
		'20161004_lj_r40.fa' => array(
			'gi_dropdown' => true,
			'category' => 'Lotus japonicus genome',
			'type' => 'genome',
			'description' => '<p>Version 4.0 of the <em>Lotus</em> genome Gifu.</p><p class="user-message warning">This is a raw PacBio assembly that is not curated</p>'
			),
		'lj_r30.fa' => array(
			'gi_dropdown' => true,
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => 'Version 3.0 of the <em>Lotus</em> genome including chr0 with unanchored contigs, chr1&ndash;6, chloroplast and mitochondrion sequences.'
			),
		'lj_pr28.fa' => array(
			'gi_dropdown' => true,
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => '<em>Lotus</em> genome pre-release 2.8. This is not an official release but the current state of the new Lotus genome assembly. Overlaps between contigs still need to be checked, and the coordinates will not match the version to be published. Contains only sequences anchored on the six Lotus chromosomes.'
			),
		'lj_r25.fa' => array(
			'gi_dropdown' => true,
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => '<em>Lotus</em> genome release 2.5, only sequences anchored on chromosomes, including chr0 long contigs with unknown position and the chloroplast sequence.'
			),
		'lj_r24.fa' => array(
			'gi_dropdown' => true,
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => '<em>Lotus</em> genome release 2.4. This is the genome version that matches the Apollo browser GFF files in the folder "-Apollo favourite genes/2010_09_06_GFF" on the commom server. According to Shusei Sato, there should only be minor changes to the coordinates on a part of chr5 between version 2.4 and 2.5. There are large differences with respect to the annotation between the two versions due to removal of a lot of retro-element-related sequence annotation in v2.5.'
			),
		'lj_r25_incl_SGA.fa' => array(
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => 'As lj_r25 but including also all the short sanger shotgun contigs (LjSGAs), which are not anchored on chromosomes.'
			),
		'LotusK31ver2GC_illumina_contigs.fa' => array(
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => 'Genomic MG20 contigs assembled using SOAP de novo based on four Illumina libraries with different insert sizes. Raw and uncurated assembly. '
			),
		'PCAPrep_Illumina_LjSGA_assembly.fa' => array(
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => 'The program PCAP.rep was used to assemble Illumina contigs (LotusK31ver2GC_illumina_contigs) with the Sanger shotgun data (LjSGA).'
			),
		'20130828_PacBio.ctg.fa' => array(
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => '.ctg are the high confidence contigs. PacBio is a sequencing technology that generates long (up to 10 kb) but error-prone (up to 20%) reads. The PacBio reads have been corrected using Illumina data prior to contig assembly.'
			),
		'20130828_PacBio.utg.fa' => array(
			'category' => '<em>Lotus japonicus</em> genome',
			'type' => 'genome',
			'description' => '.utg are all the assembled PacBio contigs (unitigs). PacBio is a sequencing technology that generates long (up to 10 kb) but error-prone (up to 20%) reads. The PacBio reads have been corrected using Illumina data prior to contig assembly.'
			),
		'lj_probes.fa' => array(
			'category' => '<em>Lotus japonicus</em> probes',
			'type' => 'probes',
			'description' => 'Probes for <em>L. japonicus</em> used in LjGEA.'
			),
		'20150304_Lj2.5_proteins.fa' => array(
			'category' => '<em>Lotus japonicus</em> proteins',
			'type' => 'protein',
			'description' => 'Protein library of Lotus japonicus MG20 v2.5.'
			),
		'20130521_Lj30_proteins.fa' => array(
			'category' => '<em>Lotus japonicus</em> proteins',
			'type' => 'protein',
			'description' => 'Protein library of Lotus japonicus MG20 v3.0.'
			),
		'Gifu_mRNA_illumina_denovo.fa' => array(
			'category' => '<em>Lotus japonicus</em> transcripts',
			'type' => 'transcript',
			'description' => 'De novo assembled transcript contigs from Illumina non-directional mRNA-seq data.'
			),
		'MG20_mRNA_illumina_denovo.fa' => array(
			'category' => '<em>Lotus japonicus</em> transcripts',
			'type' => 'transcript',
			'description' => 'De novo assembled transcript contigs from Illumina non-directional mRNA-seq data.'
			),
		'20130521_Lj30_cDNA.fa' => array(
			'category' => '<em>Lotus japonicus</em> transcripts',
			'type' => 'transcript',
			'description' => 'cDNA library of <em>Lotus japonicus</em> MG20 v3.0.'
			),
		'20150304_Lj2.5_CDS.fa' => array(
			'category' => '<em>Lotus japonicus</em> transcripts',
			'type' => 'transcript',
			'description' => 'CDS library of <em>Lotus japonicus MG20</em> v2.5. '
			),
		'20130521_Lj30_CDS.fa' => array(
			'category' => '<em>Lotus japonicus</em> transcripts',
			'type' => 'transcript',
			'description' => 'CDS library of <em>Lotus japonicus MG20</em> v3.0. '
			),
		'deepSAGE_TCs.fa' => array(
			'category' => '<em>Lotus japonicus</em>&mdash;miscellaneous',
			'type' => 'transcript',
			'description' => 'Transcript contigs from <em>L. japonicus</em> gene index 6.0. These TCs were used for annotation of tags from the DeepSAGE experiment.'
			),
		'KAW_Scaffolds.fa' => array(
			'gi_dropdown' => true,
			'category' => '<em>Lotus japonicus</em>&mdash;miscellaneous',
			'type' => 'genome',
			'description' => 'Genome sequence of the KAW endophyte worked on by Simona Radutoiu and Rafał Zgadzaj.'
			),
		'kaw12_translations.fa' => array(
			'category' => '<em>Lotus japonicus</em>&mdash;miscellaneous',
			'type' => 'protein',
			'description' => 'Amino acid sequence of the endophyte Rhizobium KAW12 genome. '
			),
		'201512008_barley_harunanijo_contigs.fa' => array(
			'category' => 'Others',
			'type' => 'genome',
			'description' => 'Barley (<em>Hordeum vulgare</em> cv. Haruna Nijo ) genome contigs.'
			),
		'Rcc_DK05_contigs_221014.fa' => array(
			'category' => 'Others',
			'type' => 'genome',
			'description' => 'Contig library of <em>Ramularia collo-cygni</em> from batch DK05.'
			),
		'20160305_MesorhizobiumLoti_MAFF303099_genome.fa' => array(
			'category' => '<em>Mesorhizoium loti</em>',
			'type' => 'genome',
			'description' => '<em>Mesorhizoium loti</em> MAFF303099 genome'
			),
		'20160305_MesorhizobiumLoti_MAFF303099_proteins.fa' => array(
			'category' => '<em>Mesorhizoium loti</em>',
			'type' => 'protein',
			'description' => '<em>Mesorhizoium loti</em> MAFF303099 proteins'
			),
		'20160305_MesorhizobiumLoti_R7A_genome.fa' => array(
			'category' => '<em>Mesorhizoium loti</em>',
			'type' => 'genome',
			'description' => '<em>Mesorhizoium loti</em> R7A genome'
			),
		'20160305_MesorhizobiumLoti_R7A_proteins.fa' => array(
			'category' => '<em>Mesorhizoium loti</em>',
			'type' => 'protein',
			'description' => '<em>Mesorhizoium loti</em> R7A proteins'
			)
	);

	// Filter database by blacklist
	public function set_db_blacklist($filter) {
		$this->_vars['blacklist'] = array_merge($this->_vars['blacklist'], $filter);
	}

	// Filter database by whitelist
	public function set_db_whitelist($filter) {
		$this->_vars['whitelist'] = array_merge($this->_vars['whitelist'], $filter);
	}

	// Get blast directory
	private function get_blast_db_dir() {
		if(is_intranet_client()) {
			return BLAST_DB_DIR_INTERNAL;
		} else {
			return BLAST_DB_DIR_PUBLIC;
		}
	}

	// Remove database
	private function remove_database($var) {
		$status = true;

		// Check if file path is specified
		if(!isset($var['abs_file_path'])) {
			$status = false;
		}

		// Check if database is blacklisted
		foreach ($this->_vars['blacklist'] as $key => $values) {
			if (!in_array($var[$key], $values)) {
				$status = true;
			} else {
				$status = false;
				break;
			}
		}

		// Check if database is whitelisted
		foreach ($this->_vars['whitelist'] as $key => $values) {
			if (in_array($var[$key], $values)) {
				$status = true;
				break;
			} else {
				$status = false;
			}
		}

		return $status;
	}

	// Get metadata
	public function get_metadata($db_file_name = null) {
		exec('perl '.DOC_ROOT.'/lib/blast-db-metadata.cgi'.' '.escapeshellarg($this->get_blast_db_dir()), $output);
		foreach($output as $db) {
			$db_raw_data = preg_split('/\t/', $db);
			$db_keys = array(
				'abs_file_path',
				'molecular_type',
				'title',
				'last_updated',
				'base_count',
				'sequence_count',
				'bytes_used'
			);
			foreach($db_raw_data as $i => $d) {
				$this->blast_db_metadata[basename($db_raw_data[0])][$db_keys[$i]] = $d;
			}
		}

		// Merge additional metadata
		$this->blast_db_metadata = array_merge_recursive($this->blast_db_metadata_extra, $this->blast_db_metadata);

		// Are we retrieving all databases, or a single DB?
		if(!isset($db_file_name) || !trim($db_file_name)) {
			$this->blast_db_metadata = array_filter($this->blast_db_metadata, array(__CLASS__, "remove_database"));
		} else {
			$this->blast_db_metadata = array_filter($this->blast_db_metadata, array(__CLASS__, "remove_database"))[$db_file_name];
		}

		return $this->blast_db_metadata;

	}

}

?>