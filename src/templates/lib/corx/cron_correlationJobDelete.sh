#!/bin/sh
echo "Finding files older than 30 days:"
find /var/www/html/data/cornea/jobs ! -name 'standard_*.json.gz' -type f -mtime +30
echo "Deleting these files..."
find /var/www/html/data/cornea/jobs ! -name 'standard_*.json.gz' -type f -mtime +30 -delete