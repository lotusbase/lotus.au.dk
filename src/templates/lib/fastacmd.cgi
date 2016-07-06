#!/usr/bin/perl
$ENV{PATH} = "/root/.sequenceserver/ncbi-blast-2.2.31+/bin:$ENV{PATH}";
use strict;
use warnings;
use CGI;
use File::Spec;

## Define variables
my $cgi_o = CGI->new();
my $err;
my $out;

## Fetch variables from php exec() command
my $db_root = $ARGV[0];
my $db = $ARGV[1];
my $id = $ARGV[2];
my $posfrom = $ARGV[3];
my $posto = $ARGV[4];
my $st = $ARGV[5];

## Check that the DB and ID are defined
## This is checked by fastacmd.php, but we do it again just in case
unless (defined $db) {
	$err = "The database parameter, <code>-db</code> was not specified.";
}
else {
	$db = File::Spec->catfile($db_root, $db);
}
unless (defined $id) {
	$err = "The search by ID parameter, <code>-entry</code> was not specified.";
}
else {
	if ($id =~ m/[;|>|<|'|"|\`|:|\/|\\|*|?|!|&]/) {
	$err = "The search by ID parameter, <code>-entry</code>, contains a non-valid character.";
	}
}

my $fastacmd = "nice -n 19 blastdbcmd -db " . $db . " -entry " . $id;

if (defined $posfrom && defined $posto) {
	if ($posfrom !~ m/^[\d]+$/) {
		$err = "ERROR: Start position is not an integer.";
	}
	elsif ($posto !~ m/^\d+$/) {
		$err = "ERROR: End position is not an integer."
	}
	elsif ($posto > 0 && $posfrom > 0) {
		$fastacmd .= " -range " . $posfrom . "-" . $posto;
	}
}

if (defined $st) {
	if ($st !~ m/^(plus)|(minus)$/) {
		$err = "ERROR: Strand can only be 'plus' or 'minus'."
	}
	else {
		$fastacmd .= " -strand " . $st;
	}
}

if (defined $err) {
    $out = "ERR\n";
    $out .= $err."\n";
    $out .= $fastacmd;
}
else {
    $out .= `$fastacmd`;

	if ($out !~ m/^>/ && $out =~ m/.+/) {
		$out = "ERR\n";
		$out .= "Command execution error\n";
		$out .= $fastacmd;
	}
	elsif ($out !~ m/^>/) {
		$out = "ERR\n";
		$out .= "No sequence was found with the ID <code>$id</code>\n";
		$out .= $fastacmd;
	}
}

print $out;