#!/bin/sh
echo "Starting CORNEA server from cron"
/home/terry/anaconda3/bin/python /var/www/html/lib/corx/CorrelationNetworkServer.py &
