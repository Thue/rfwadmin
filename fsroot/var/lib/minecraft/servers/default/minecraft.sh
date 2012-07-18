#!/bin/bash

PATH_BASE="/var/lib/minecraft"


FILE_JAR="$PATH_BASE/jars/1.2.5/minecraft_server.jar"

#If running multiple servers, $SCREEN and $SERVER_SUBDIR should be unique
#The SCREEN=... is inserted into a regexp, so may only contain [a-zA-Z0-9]
SCREEN=mcsrv
SERVER_SUBDIR="default"

#save-off makes the server not write map changes to disk, but if the
#server crashes all changes are obviously lost. Also I think that even
#with the script doing "save-on; save-all; save-off", it sometimes
#doesn't save everything!
USE_SAVEOFF=false


. "$PATH_BASE"/minecraft_base.sh
