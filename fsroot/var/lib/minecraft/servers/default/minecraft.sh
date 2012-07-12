#!/bin/bash

PATH_BASE="/var/lib/minecraft"


FILE_JAR="$PATH_BASE/jars/1.2.5/minecraft_server.jar"

#If running multiple servers, $SCREEN and $SERVER_SUBDIR should be unique
#The SCREEN=... is inserted into a regexp, so may only contain [a-zA-Z0-9]
SCREEN=mc2srv
SERVER_SUBDIR="default"

. "$PATH_BASE"/minecraft_base.sh
