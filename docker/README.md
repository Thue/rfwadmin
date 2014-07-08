RFW Admin + Minecraft + Docker
==============================
A Minecraft Server With Built-in Web Interface

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
Internal Port | Use
--------------|-----------------
TCP/80        | Web UI
TCP/25565     | Minecraft Server

Volumes To Persist
------------------
Directory Path                            | Required | Data Size  | Required Performance | Use
------------------------------------------|----------|------------|----------------------|---------------------------
/var/lib/minecraft/maps                   | Yes      | Large      | Slow                 | Saved map files
/var/lib/minecraft/servers                | Yes      | Small      | Fast                 | Minecraft server directory
/var/log                                  | No       | Medium     | Slow                 | System log files
/var/log/httpd                            | No       | Small      | Slow                 | Web interface log files

Each of the required volumes should be mapped to either a non-persistant volume
via the "--volume='<Volume Path>'" arguement or mapped persistantly via the
"--volume='<Real Path>:<Volume Path>" arguement.

For example, to use /var/lib/mcd/maps to persistantly store the map files:
```
--volume="/var/lib/mcd/maps:/var/lib/minecraft/maps"
```

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


Ideas, TODOs, FIXMEs, etc
=========================

Image
-----
 * Add a way to shutdown the instance gracefully
 * Multi-user perms for web interface
 * Define settings via env variables such as
  * Memory settings for MC server
  * Auth info for web UI
 * Consider adding one or more ways for server owners to access the raw MC files
 without going through the web UI. ex: SFTP, SCP, FTP(S), etc
 * Add support for other port export for things like
  * MC mgmt console
  * Plugin based ports/web interfaces/etc
 * Add optional automatic overviewer generation
 * Choice between openjdk and Oracle's JRE

Docs
----
 * Add cross-launch persistance to volume
 * Cover how to run multiple ones (not reallys special, just worth calling out)
 * How to put behind reverse proxy such as nginx, httpd, etc
 * How to set CPU and/or memory limits
