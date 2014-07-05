#!/bin/bash

echo "Starting at "`/bin/date`

/bin/cp -a /var/lib/minecraft/servers/default/server.stock/* /var/lib/minecraft/servers/default/server/

/usr/sbin/apachectl -D FOREGROUND
exit $?
