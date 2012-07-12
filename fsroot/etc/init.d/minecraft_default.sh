#!/bin/sh

CMD="/var/lib/minecraft/servers/default/minecraft.sh \"$1\" \"$2\" \"$3\" \"$4\" \"$5\" \"$6\" \"$7\" \"$8\" \"$9\""
SU_TO_USER="www-data"

if [ ! -z "$SU_TO_USER" ]; then
    if [ `whoami` != "$SU_TO_USER" ]; then
	su - www-data -c "$CMD"
    else
	$CMD
    fi
fi
