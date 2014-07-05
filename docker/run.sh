#!/bin/bash
set -x

echo "Starting at "`/bin/date`

# Set screen dir perms if they aren't already set
/bin/chmod 777 /var/run/screen
/bin/chown root:root /var/run/screen

# Ensure the standard files exist
/bin/cp -a /var/lib/minecraft/servers/default/server.stock/* /var/lib/minecraft/servers/default/server/
/bin/mkdir -p /var/log/httpd
/bin/chown apache:apache /var/log/httpd

/usr/sbin/apachectl -D FOREGROUND
exit $?
