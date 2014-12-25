#!/bin/bash

#these two following lines (PATH_BASE and FILE_JAR) are regexp matched from serverjar.php . So don't make then too irregular.
#On initial installation, when running the install.sh script, the below 2 lines are adjusted
PATH_BASE="/var/lib/minecraft"
FILE_JAR="$PATH_BASE/jars/serverjars/craftbukkit-beta_1.3.2-R0.1.jar"

#save-off makes the server not write map changes to disk, but if the
#server crashes all changes are obviously lost. Also I think that even
#with the script doing "save-on; save-all; save-off", it sometimes
#doesn't save everything!
USE_SAVEOFF=false

#Server subdir of $PATH_BASE/servers
function get_current_subdir() {
    #copy-paste from http://stackoverflow.com/questions/59895/can-a-bash-script-tell-what-directory-its-stored-in
    local SOURCE="${BASH_SOURCE[0]}"
    while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
	local DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
	SOURCE="$(readlink "$SOURCE")"
	[[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
    done
    SUBDIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
    SUBDIR=`basename $SUBDIR`
}
get_current_subdir
SERVER_SUBDIR=$SUBDIR

. "$PATH_BASE"/minecraft_base.sh
