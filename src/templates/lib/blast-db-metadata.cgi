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

## Variable
my $blast_db_dir = $ARGV[0];

## Get DB data
my $fastacmd = "nice -n 19 blastdbcmd -list " . $blast_db_dir . " -list_outfmt \"%f	%p	%t	%d	%l	%n	%U\"";

print `$fastacmd`;