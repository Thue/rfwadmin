#!/bin/sh

### BEGIN INIT INFO
# Provides:          rfwadmin
# Required-Start:    $remote_fs $network $time
# Required-Stop:     $remote_fs $network
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: rfwadmin Minecraft server control script
# Description:       rfwadmin runs any number of configured Minecraft servers on boot, which can then be controlled by the web interface and/or calling this init script command line.
### END INIT INFO

PATH_BASE="/var/lib/minecraft"
CMD="$PATH_BASE/servers/default/minecraft.sh"
for ARG in "$@"; do
  CMD="$CMD \"$ARG\""
done

#SU_TO_USER is adjusted on installation by the install.sh script
SU_TO_USER="www-data"

if [ ! -z "$SU_TO_USER" ]; then
    if [ `whoami` != "$SU_TO_USER" ]; then
	su -s /bin/bash - $SU_TO_USER -c "$CMD"
    else
	$CMD
    fi
fi
