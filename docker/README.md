RFW Admin + Minecraft + Docker
==============================
Minecraft Managment UI

Intro
=====
This Docker image provides an easy and conveanant way to run one or more
instances of Minecraft managed by Thue's RFWAdmin web interface. It also
provides a highly secure way to isolate the Minecraft servers from each other
and limit their resources.


How To Run
=========

Ports To Export
---------------
TCP/80          Web UI
TCP/25565       Minecraft Server

Volumes To Persist
------------------
/var/lib/minecraft/maps
/var/lib/minecraft/servers/default/server
/var/log
/var/log/httpd

Interactive
-----------
```
docker.io run -i -t --publish="8080:80" --publish="25565:25565" --volume="/var/lib/minecraft/maps" --volume="/var/log" --volume="/var/lib/minecraft/servers/default/server" gpmidi/rfwadmin
```

Daemon
-----------
```
docker.io run -d --publish="8080:80" --publish="25565:25565" --volume="/var/lib/minecraft/maps" --volume="/var/log" --volume="/var/lib/minecraft/servers/default/server" gpmidi/rfwadmin
```


FIXMEs
======

Image
-----
 * Add a way to shutdown the instance gracefully
 * Multi-user perms
 * Define settings via env variables such as
 ** Memory settings for MC server
 ** Auth info for web UI
 * Consider adding one or more ways for server owners to access the raw MC files
 without going through the web UI. ex: SFTP, SCP, FTP(S), etc
 * Add support for other port export for things like
 ** MC mgmt console
 ** Plugin based ports/web interfaces/etc
 * Add optional automatic overviewer generation

Docs
----
 * Add cross-launch persistance to volume
 * Cover how to run multiple ones (not reallys special, just worth calling out)
 * How to put behind reverse proxy such as nginx, httpd, etc
 * How to set CPU and/or memory limits
