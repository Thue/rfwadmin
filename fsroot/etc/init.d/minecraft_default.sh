#!/bin/sh

CMD="/var/lib/minecraft/servers/default/minecraft.sh"
for ARG in "$@"; do
  CMD="$CMD \"$ARG\""
  echo $CMD
done

SU_TO_USER="www-data"

if [ ! -z "$SU_TO_USER" ]; then
    if [ `whoami` != "$SU_TO_USER" ]; then
	su - www-data -c "$CMD"
    else
	$CMD
    fi
fi
