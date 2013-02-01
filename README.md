rfwadmin
========

A web and/or command line interface for controlling a MineCraft server, optimized for quickly restarting with custom maps such as Race For Wool (RFW).

To install
----------

- Only tested on Ubuntu 12.04 and CentOS
- The files inside fsroot are in their filesystem locations; move then into the real file system at corresponding locations.
- /var/lib/minecraft/maps and /var/lib/minecraft/servers should be owned by the web server user (www-data:www-data in Ubuntu and Debian, httpd in redhat, RHEL, Centos).
- /etc/init.d/minecraft_default.debian.sh or /etc/init.d/minecraft_default.redhat.sh should not be owned by the web server, since it is run by root during boot.
- On Ubuntu, get the apache web server with PHP5 by installing the Ubuntu package libapache2-mod-php5 .
- On fx a default Ubuntu or CentOS install, upload_max_filesize in /etc/php5/apache2/php.ini is set too low for the map upload feature to work.
- The PHP script uses curl for fetching maps from a link, so you should install the ubuntu package php5-curl if you want to use that feature.
- Running multiple servers: Right now fsroot is set up to run one server - to run more than one server, 1) add a /etc/init.d/minecraft2.sh file, 2) add a copy of the /var/www/index.php file, and edit it, and 3) a /var/lib/minecraft/servers/2 dir.

Configuration files
-------------------

- /etc/init.d/minecraft_default.debian.sh and /etc/init.d/minecraft_default.redhat.sh is an extremely simple file set to point to /var/lib/minecraft/servers/default/minecraft.sh . To make it start and stop with the Ubuntu server on boot, run the Ubuntu command "update-rc.d minecraft_default.debian.sh defaults". For redhat based distributions, do "chkconfig --add minecraft_default.redhat.sh".
- /var/lib/minecraft/servers/default/minecraft.sh contains the actual configuration of the script. When upgrading to a new Minecraft server version, adjust $FILE_JAR here (Or use the "server version" tab in the web interface).
- The files below /var/lib/minecraft/servers/default/server , such as /var/lib/minecraft/servers/default/server/server.properties , is the normal minecraft configuration for a single Minecraft server.
- If you are running multiple servers, or don't use the default /var/lib/minecraft location, you need to tweak the settings inside /var/www/index.php

Some directories and files explained
------------------------------------

- /var/lib/minecraft/maps : The web interface will save uploaded maps here
- /var/lib/minecraft/jars : server jars and bukkit plugins jars.
- /var/lib/minecraft/minecraft_base.sh : The shell backend, used by init.d and the web interface
- /var/lib/minecraft/servers/default/server : A normal minecraft server dir for the server I called "default" (The name "default" is not displayed to end-users).

Using command line interface
----------------------------

You can use rfwadmin as a command line interface by doing "sudo /etc/init.d/minecraft_default.sh start". Commands include "start", "stop", "restart", "list", "send_command save-all", "send_command op thuejk"; look it the bottom of /var/lib/minecraft/minecraft_base.sh for a list. The command line interface is a heavily modified version of mc-manager, much improved; I recommend always using the rfwadmin version over mc-manager, even if you don't need the web interface. To only use the command line interface without installing the rfwadmin web interface, simply don't install /var/www/index.php .

About security
--------------

- I *think* it is safe.
- The only possibly fishy thing is the "Or fetch from a direct link to a zip file:" feature, which will happily fetch a file from the internal network (fx 192.168.1.x), thereby bypassing any firewall. Though I don't see how it would benefit them to get this file fetched into the maps folder. Could be a Cross-site scripting problems if you have any web interfaces on the local net making changes using GET (which is why everybody says you should always use POST for such interfaces).
- There is currently no built-in access controls to the www-interface - set up fx apache's built in auth support instead.

About quality
-------------

- The PHP code should be mostly ok.
- I make no claims to be an expert shell programmer.

Craftbukkit
-----------

You might want to install the custom server from bukkit
if you want to use plugins: http://dl.bukkit.org/ . Place the jar fx
somewhere in /var/lib/minecraft/jar, and adjust $FILE_JAR in
/var/lib/minecraft/servers/default/minecraft.sh . The web interface
has a simple enable/disable plugin functionality, if the plugins are
placed in the correct subdirectory of /var/lib/minecraft/jars/plugins/

Another useful plugin is WorldEdit ( http://www.sk89q.com/projects/worldedit/ ), for the /butcher command which removes all hostile mobs which spawned while you were waiting at the starting gate.

Regards, Thue
