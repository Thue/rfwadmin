#!/bin/bash
#If a previous installation exists, this script installs a new code version while preserverving configuration and data

echo "This script should work, but has not been tested. Edit this file to remove the 'exit' below to run it anyway"
exit

### Settings ###

#You can also use WEBINTERFACE_DIR=/var/www/ to put the web interface in the www root
WEBINTERFACE_DIR="/var/www/rfwadmin"

#rfwadmin runs as the web server user. If $WEBSERVER_USER is empty, this script will try to guess the web server user from the owner of the process currently using port 80
WEBSERVER_USER=

PATH_BASE="/var/lib/minecraft"



### Checks ###

function error_exit
{
    PROGNAME=$(basename $0)
    echo "${PROGNAME}: ${1:-"Unknown Error"}" 1>&2
    exit 1
}

if [ "`whoami`" != "root" ]; then
  echo "Script must be run as root"
  exit 1
fi

#determine web server user by looking at who is using port 80
if [ -z "$WEBSERVER_USER" ]; then
  #which process is listening to port 80?
  WEB_PID=`netstat -tulpn |grep ":80[^0-9]"|head -n1|sed 's/.*\s\([0-9]\+\)\/\w\+\s*/\1/'`
  if [[ ! "$WEB_PID" =~ ^[0-9]+ ]]; then
    error_exit "Failed to guess web server user. Edit install.sh to manually specify \$WEBSERVER_USER"
  fi

  WEBSERVER_USER=`ps --pid $WEB_PID -o euser |tail -n 1`
  echo "Using '$WEBSERVER_USER' as the HTTP server user, which will also run the Minecraft server."
fi
if ! [[ "$WEBSERVER_USER" =~ ^[a-zA-Z_-]+$ ]] ; then
    error_exit "error: HTTP server user ($WEBSERVER_USER) looks wrong"
fi




### Customize files to install ###

#If there is already stuff in /servers, then we are updating an old installation, and don't want to overwrite with a blank server configuration
if  [ ! -d "$PATH_BASE/servers" ] || [ ! "$(ls -A $PATH_BASE/servers)" ]; then
  CONFIGURE_SERVER="1"
else
  CONFIGURE_SERVER="0"
  echo "Not configuring any new Minecraft servers, since there already seem to be a configured server in $PATH_BASE/servers . Delete that directory and rerun the install script if you want to configure a new server."
fi

#Configure a default server if no previous configuration exists
if  [ $CONFIGURE_SERVER == "1" ]; then
  #I would like to name the file after the version, but Mojang doesn't give me a way to determine the version number of the download
  LATEST_SERVER_BINARY="/minecraft_server_downloaded_`date +%Y-%m-%d`.jar"
  LATEST_SERVER="\$PATH_BASE/jars/serverjars/$LATEST_SERVER_BINARY"
  #If we are re-running the install script on the same day, no need to re-download the server
  if [ ! -f "fsroot/var/lib/minecraft/$LATEST_SERVER_BINARY" ]; then
    echo "Downloading latest minecraft server jar from Mojang."
    wget -O "fsroot/var/lib/minecraft/$LATEST_SERVER_BINARY" "https://s3.amazonaws.com/MinecraftDownload/launcher/minecraft_server.jar" || error_exit "Failed to download minecraft server"
  fi
  cat fsroot/var/lib/minecraft/servers/default/minecraft.sh | sed "s|^FILE_JAR=.*\$|FILE_JAR=\"$LATEST_SERVER\"|" | sed "s|^PATH_BASE=.*\$|PATH_BASE=\"$PATH_BASE\"|" > fsroot/var/lib/minecraft/servers/default/minecraft.sh.customized
fi

if [ ! -f fsroot/var/lib/minecraft/jars/converter/AnvilConverter.jar -a ! -f $PATH_BASE/jars/converter/AnvilConverter.jar ]; then
  echo "Downloading Anvil converter used to convert old maps."
  wget -O "fsroot/var/lib/minecraft/jars/converter/Minecraft.AnvilConverter.zip" "http://assets.minecraft.net/12w07a/Minecraft.AnvilConverter.zip"
  unzip -q -d "fsroot/var/lib/minecraft/jars/converter" "fsroot/var/lib/minecraft/jars/converter/Minecraft.AnvilConverter.zip" || echo "Failed to extract anvil converter because 'unzip' is not installed. You probably don't need that anyway."
fi

#configure index.php to use correct PATH_BASE
cat fsroot/var/www/index.php | sed "s|^.include_base = .*\$|\$include_base = \"$PATH_BASE\";|" > fsroot/var/www/index.php.customized

#Configure init script to use correct userid and PATH_BASE
cat fsroot/etc/init.d/minecraft_default.sh | sed "s/^SU_TO_USER=.*$/SU_TO_USER=\"$WEBSERVER_USER\"/" | sed "s|^PATH_BASE=.*\$|PATH_BASE=\"$PATH_BASE\"|" > fsroot/etc/init.d/minecraft_default.sh.customized


### ACTUALLY INSTALL ###
#No changes made outside current directory until this point

#install /var/lib
mkdir -p $PATH_BASE
mkdir -p $PATH_BASE/maps/
mkdir -p $PATH_BASE/jars/converter
mkdir -p $PATH_BASE/jars/plugins
mkdir -p $PATH_BASE/jars/serverjars
mkdir -p $PATH_BASE/servers

cp fsroot/var/lib/minecraft/jars/plugins/README $PATH_BASE/jars/plugins
cp fsroot/var/lib/minecraft/minecraft_base.sh $PATH_BASE
cp -r fsroot/var/lib/minecraft/web $PATH_BASE
if [ -f  fsroot/var/lib/minecraft/jars/converter/AnvilConverter.jar ]; then
  #If downloaded for installation above, then install it
  cp fsroot/var/lib/minecraft/jars/converter/AnvilConverter.jar $PATH_BASE/jars/converter
fi

#If no server config exists from previous install, then configure a server
if  [ $CONFIGURE_SERVER == "1" ]; then
  cp -r fsroot/var/lib/minecraft/servers/default $PATH_BASE/servers
  mkdir $PATH_BASE/servers/default/backups
  mv $PATH_BASE/servers/default/minecraft.sh.customized $PATH_BASE/servers/default/minecraft.sh
  cp fsroot/var/lib/minecraft/jars/serverjars/* $PATH_BASE/jars/serverjars

  #Install init script
  cp fsroot/etc/init.d/minecraft_default.sh.customized /etc/init.d/minecraft_default.sh
  ##Set to stop in ( http://refspecs.linuxbase.org/LSB_3.0.0/LSB-Core-generic/LSB-Core-generic/runlevels.html ):
  #halt
  ln -s /etc/init.d/minecraft_default.sh /etc/rc0.d/K01minecraft_default.sh
  #single user mode
  ln -s /etc/init.d/minecraft_default.sh /etc/rc1.d/K01minecraft_default.sh
  #reboot
  ln -s /etc/init.d/minecraft_default.sh /etc/rc6.d/K01minecraft_default.sh
  #without network
  ln -s /etc/init.d/minecraft_default.sh /etc/rc2.d/K01minecraft_default.sh
  ##set to start in the more or less normal levels
  ln -s /etc/init.d/minecraft_default.sh /etc/rc3.d/S99minecraft_default.sh
  ln -s /etc/init.d/minecraft_default.sh /etc/rc4.d/S99minecraft_default.sh
  ln -s /etc/init.d/minecraft_default.sh /etc/rc5.d/S99minecraft_default.sh
fi

chown -R $WEBSERVER_USER:root $PATH_BASE

#install web interface files
if [ ! -d "$WEBINTERFACE_DIR" ]; then
  mkdir -v --parents "$WEBINTERFACE_DIR"
fi
if [ ! -e "$WEBINTERFACE_DIR"/index.php ]; then
  cp -v fsroot/var/www/index.php.customized "$WEBINTERFACE_DIR/index.php"
else
  if [ ! -e "$WEBINTERFACE_DIR"/rfwadmin_files ]; then
     echo "Refusing to overwrite existing $WEBINTERFACE_DIR/index.php file, but I don't think it belongs to rfwadmin. Do a manual 'cp fsroot/var/www/index.php $WEBINTERFACE_DIR/index.php' if you really want to."
  fi
fi
if [ ! -e "$WEBINTERFACE_DIR"/rfwadmin_files ]; then
  ln -s /var/lib/minecraft/web/visible "$WEBINTERFACE_DIR"/rfwadmin_files
fi

/etc/init.d/minecraft_default.sh start
