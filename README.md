rfwadmin
========

A web and/or command line interface for controlling a MineCraft server, optimized for quickly restarting with custom maps such as Race For Wool (RFW). It requires a Linux server.

To install
----------

Essential steps:

- Install supporting programs (tmux, java, apache, php, zip).
    - On Ubuntu/Debian/Mint: "sudo apt-get install tmux openjdk-7-jre libapache2-mod-php5 php5-curl wget zip unzip && /etc/init.d/apache2 restart". (The apache restart should be unnecessary, but is due to Debian bug 705350)
    - On Redhat/RHEL/CentOS the command should be "sudo yum -y install tmux java-1.7.0-openjdk php httpd php-curl wget zip unzip lsof". CentOS/RHEL 6 apparently doesn't have a tmux package, so you will have to install tmux in some other way; CentOS/RHEL 7 does have a tmux package. You also need to set "SELINUX=permissive" in /etc/sysconfig/selinux , or the web server will not be able to run the minecraft.sh shell script (any help on avoiding this is appreciated). Before running install.sh below, you also need to start the httpd daemon ("chkconfig httpd on && service httpd start").
- Run ./install.sh from inside the unpacked rfwadmin directory. Tested on Debian, Ubuntu, and CentOS, but should work on any Linux/Unix.
- If your server has the address http://example.com , then the web interface should now be available at http://example.com/rfwadmin
- The install script will generate a random 10-character password for the web interface; you can change it by editing /var/www/rfwadmin/index.php (or /var/www/html/rfwadmin/index.php for redhat)

Optional steps:

- On fx a default Ubuntu or CentOS install, upload_max_filesize in /etc/php5/apache2/php.ini is set too low for the map upload feature to work.
- Running multiple servers: Right now fsroot is set up to run one server - to run more than one server, 1) add a /etc/init.d/minecraft2.sh file, 2) add a copy of the /var/www/rfwadmin/index.php file, and edit it, and 3) a /var/lib/minecraft/servers/2 dir. To make it start and stop with an Ubuntu server on boot, run the Ubuntu command "update-rc.d minecraft2.sh defaults". For Redhat based distributions, do "chkconfig --add minecraft2.sh".
- You might want to set up a cron job to logrotate /var/lib/minecraft/servers/default/tmux.log . The web interface will automatically check and truncate this if it gets too big, but if the web interface is not loaded for months, this file can get 100s of MB big.

Configuration files
-------------------

Most users should not need to adjust any settings from the default.

- You can tweak where the files will be installed at the top of install.sh .
- /var/lib/minecraft/servers/default/minecraft.sh configures how the minecraft server is run, e.g. memory usage.
- The files below /var/lib/minecraft/servers/default/server , such as /var/lib/minecraft/servers/default/server/server.properties , is the normal minecraft configuration for a single Minecraft server.
- If you are running multiple servers, or don't use the default /var/lib/minecraft location, you need to tweak the settings inside /var/www/rfwadmin/index.php
- /var/www/rfwadmin/index.php also contains settings for the web interface.

Some directories and files explained
------------------------------------

- /var/lib/minecraft/maps : The web interface will save uploaded maps here
- /var/lib/minecraft/jars : server jars and bukkit plugins jars. (the server jars should be managed from the web interface)
- /var/lib/minecraft/minecraft_base.sh : The shell backend, used by init.d and the web interface
- /var/lib/minecraft/servers/default/server : A normal minecraft server dir for the server I called "default" (The name "default" is not displayed to end-users).

Using command line interface
----------------------------

You can use rfwadmin as a command line interface by doing commands such as "sudo /etc/init.d/minecraft_default.sh start". Commands include "start", "stop", "restart", "list", "send_command save-all", "send_command op thuejk"; look it the bottom of /var/lib/minecraft/minecraft_base.sh for a list. The command line interface is a heavily modified version of mc-manager, much improved; I recommend always using the rfwadmin version over mc-manager, even if you don't need the web interface. To only use the command line interface without installing the rfwadmin web interface, simply don't install /var/www/rfwadmin/index.php .

Non-vanilla servers
-------------------

rfwadmin works with Bukkit and Spigot servers. Other modded servers such as Feed the Beast should also work with rfwadmin, but I haven't tested that since running a FtB server in 2013.

About security
--------------

- I *think* it is safe.
- The web interface password is stored in unhashed plaintext in index.php, so if somebody can get shell access to your server, he can read it.
- One possibly fishy thing is the "Or fetch from a direct link to a zip file:" feature, which will happily fetch a file from the internal network (fx 192.168.1.x), thereby bypassing any firewall. Though I don't see how it would benefit them to get this file fetched into the maps folder. Could be a Cross-site scripting problems if you have any web interfaces on the local net making changes using GET (which is why everybody says you should always use POST for such interfaces).

About quality
-------------

- The PHP code should be mostly ok.
- I make no claims to be an expert shell programmer.

Regards, Thue
