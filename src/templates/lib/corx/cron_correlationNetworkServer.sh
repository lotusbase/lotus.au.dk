#!/bin/sh
echo "Starting CORNEA server from cron"
python /var/www/html/lib/corx/CorrelationNetworkServer.py &
