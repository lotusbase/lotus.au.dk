#!/usr/bin/perl
=pod
This is a proof-of-concept demo for saving d3js graphics as PDF/PNG/SVG files.

Copyright (C) 2012,2014 by A. Gordon (assafgordon@gmail.com)
All code written by me is released under BSD license: http://opensource.org/licenses/BSD-3-Clause
(also uses several other libraries that have their own licenses).

See here for more details:
	https://github.com/agordon/d3export_demo

See here for online demo:
	http://d3export.housegordon.org/
=cut
use strict;
use warnings;
use CGI qw/:standard/;
use CGI::Carp qw/fatalsToBrowser/;
use File::Temp qw/tempfile/;
use File::Slurp qw/read_file write_file/;
use DateTime;

=pod
Minimal, bare-bores implementation of a CGI script,
which runs "rsvg-convert" on the submitted input data.

No fluff, no "frame-works", no pretty HTML/CSS.

Note about error checking:
autodie + CGI::Carp will take care of all the errors.
In a proper application, you'll want to replace those with proper error handling.
=cut


# Limit the size of the POST'd data - might need to increase it for hudge d3js drawings.
$CGI::POST_MAX = 1024 * 100000;

# Include system path
my $path = $ENV{'PATH'};

##
## Get date
##
my $dt = DateTime->now;

##
## Input validation
##
my $output_format = param('output_format')
	or die "Missing 'output_format' parameter";
die "Invalid output_format value"
	unless $output_format eq "svg" ||
		$output_format eq "jpg" ||
		$output_format eq "png";

my $data = param('svg_data')
	or die "Missing 'data' parameter";
die "Invalid data value"
	unless $data =~ /^[\x20-\x7E\t\n\r ]+$/;

my $filename_prefix;
if ( length(param('filename_prefix')) ) {
	$filename_prefix = param('filename_prefix')."_";
} else {
	$filename_prefix = '';
}

##
## Output Processing
##

## SVG output
if ($output_format eq "svg") {
	## If both input & output are SVG, simply return the submitted SVG
	## data to the user.
	## The only reason to use a server side script is to be able to offer
	## the user a friendly way to "download" a file as an attachment.
	print header(-type=>"image/svg+xml",
		     -attachment=>"lotusbase_".$filename_prefix.$dt->ymd."_".$dt->hms.".svg");
	print $data;
	exit(0);
}
## PDF/PNG output
elsif ($output_format eq "png" || $output_format eq "jpg") {
	# Create temporary files (will be used with 'rsvg-convert')
	my (undef, $input_file) = tempfile("image-export.XXXXXXX", TMPDIR=>1, UNLINK=>1, SUFFIX=>".svg");
	my (undef, $output_file) = tempfile("image-export.XXXXXXX", TMPDIR=>1, UNLINK=>1, SUFFIX=>".$output_format");

	# Write  the SVG data to a temporary file
	write_file( $input_file, $data );

	# Run "svgexport"
	if ($output_format eq "png") {
		print `export PATH=$path:/usr/local/bin && svgexport '$input_file' '$output_file' '$output_format' 2x`;
	}
	elsif ($output_format eq "jpg") {
		print `export PATH=$path:/usr/local/bin && svgexport '$input_file' '$output_file' '$output_format' 2x "svg {background: #fff;}"`;
	}

	# Read the binary output (PDF/PNG) file.
	my $pdf_data = read_file( $output_file, {binmode=>':raw'});

	## All is fine, send the data back to the user
	my $mime_type = ($output_format eq "jpg")?"image/jpg":"image/png";
	print header(-type=>$mime_type,
		     -attachment=>"lotusbase_".$filename_prefix.$dt->ymd."_".$dt->hms.".$output_format");
	print $pdf_data;
	exit(0);
}