rfwadmin
========

A web interface for controlling a MineCraft server, optimized for quickly restarting with custom maps such as Race For Wool (RFW).


To install:
- Only tested on Ubuntu 12.04
- The files inside fsroot are in their filesystem locations; move then into the real file system at corresponding locations.
- Everything below /var/lib/minecraft should be owned by the web server user (www-data:www-data)
- /etc/init.d/minecraft_default.sh should not be owned by the web server
- Right now it is set up to run one server - by adding 1) a /etc/init.d/minecraft2.sh file, 2) a copy of the www files (yes, ugly duplication), and 3) a /var/lib/minecraft/servers/2 dir, you can run more than one server
- Get the apache web server with PHP5 by installing the Ubuntu package libapache2-mod-php5
- The PHP script uses curl, so you should install php5-curl

There are a few configuration files:
- /etc/init.d/minecraft_default.sh is an extremely simple file set to point to /var/lib/minecraft/servers/default/minecraft.sh . To make it start and stop with the server, run the Ubuntu command "update-rc.d minecraft_1234.sh defaults" (will probably differ for other distributions)
- /var/lib/minecraft/servers/default/minecraft.sh contains the actual configuration of the script. When upgrading to a new Minecraft version, adjust $FILE_JAR here.
- The files below /var/lib/minecraft/servers/default/server , such as /var/lib/minecraft/servers/1234/server/server.properties , is the normal minecraft configuration
- Inside /var/www/include.php there are a single reference to "/var/lib/minecraft/servers/default" and "/etc/init.d/minecraft_default.sh", which you might need to change if you run multiple servers

Some directories and files explained:
- /var/lib/minecraft/maps : The web interface will save uploaded maps here
- /var/lib/minecraft/jars : The server doesn't really know about this, but I use it to manually upload and save jars
- /var/lib/minecraft/minecraft_base.sh : The shell backend, used by init.d and the web interface
- /var/lib/minecraft/servers/default/server : A normal minecraft server dir for the server I called "default" (The name "default" is for internal use only)

About security:
- I *think* it is safe
- The only possibly fishy thing is the "Or fetch from a direct link to a zip file:" feature, which will happily fetch a file from the internal network (fx 192.168.1.x), thereby bypassing any firewall. Though I don't see how it would benefit them to get this file fetched into the maps folder.
- There is currently no access controls to the www-interface - set up fx apache's build in auth support

About quality:
- The PHP code should be mostly ok (might need to escape some more in the server responses)
- There is currently no same-origin check of posts, which might be a security hole.
- I make no claims to be an expert shell programmer

Craftbukkit:
You might want to install the custom server from bukkit
if you want to use plugins: http://dl.bukkit.org/ . Place the jar
somewhere in /var/lib/minecraft.sh, and adjust $FILE_JAR in
/var/lib/minecraft/servers/default/minecraft.sh .

One useful plugin is NoSpawnChunks (
http://dev.bukkit.org/server-mods/nospawnchunks/ ), which makes the
server restart much faster.

Regards, Thue
