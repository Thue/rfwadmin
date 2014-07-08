RFW Admin + Minecraft + Docker
==============================
A Minecraft Server With Built-in Web Interface

Intro
=====
This Docker image provides an easy and convenient way to run one or more
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

Interactive Example
-------------------
```
docker.io run -i -t --publish="8080:80" --publish="25565:25565" --volume="/var/lib/minecraft/maps" --volume="/var/log" --volume="/var/lib/minecraft/servers/default/server" gpmidi/rfwadmin
```

Daemon Example
--------------
```
docker.io run -d --publish="8080:80" --publish="25565:25565" --volume="/var/lib/minecraft/maps" --volume="/var/log" --volume="/var/lib/minecraft/servers/default/server" gpmidi/rfwadmin
```

Three Servers Example
---------------------
In this example three seperate Minecraft+RFWAdmin instances are created. Each
of these is isolated from the others and includes map files that persist across
docker restarts. The path "/var/lib/mcd/<server port number>/" is used to store
the maps, logs, and other server files. We'll also be putting all three RFWAdmin
interfaces behind a common Nginx server to keep the web interfaces simple.

Commands To Run
```
mkdir -p /var/lib/mcd/{25570,25571,25572}/
docker.io run -d --publish="8070:80" --publish="25570:25565" \
  --volume="/var/lib/mcd/25570/maps:/var/lib/minecraft/maps" \
  --volume="/var/lib/mcd/25570/servers:/var/lib/minecraft/servers" \
  --volume="/var/lib/mcd/25570/logs:/var/log" \
  gpmidi/rfwadmin
docker.io run -d --publish="8071:80" --publish="25571:25565" \
  --volume="/var/lib/mcd/25571/maps:/var/lib/minecraft/maps" \
  --volume="/var/lib/mcd/25571/servers:/var/lib/minecraft/servers" \
  --volume="/var/lib/mcd/25571/logs:/var/log" \
  gpmidi/rfwadmin
docker.io run -d --publish="8072:80" --publish="25572:25565" \
  --volume="/var/lib/mcd/25572/maps:/var/lib/minecraft/maps" \
  --volume="/var/lib/mcd/25572/servers:/var/lib/minecraft/servers" \
  --volume="/var/lib/mcd/25572/logs:/var/log" \
  gpmidi/rfwadmin
```

Nginx Config
```
server {
        listen                                  80 default_server;
        server_name                             www.example.com;
        access_log                              /var/log/nginx/example.com.access.log  main  buffer=32k;
        server_name_in_redirect                 on;
        root                                    /var/www/html;

        location / {
                allow                           all;
        }

        location /mc/25570/ {
                proxy_pass                      http://localhost:8070/rfwadmin/;
                proxy_set_header                X-Real-IP  $remote_addr;
                proxy_set_header                Host $host;
                proxy_cache                     off;
        }
        location /mc/25571/ {
                proxy_pass                      http://localhost:8071/rfwadmin/;
                proxy_set_header                X-Real-IP  $remote_addr;
                proxy_set_header                Host $host;
                proxy_cache                     off;
        }
        location /mc/25572/ {
                proxy_pass                      http://localhost:8072/rfwadmin/;
                proxy_set_header                X-Real-IP  $remote_addr;
                proxy_set_header                Host $host;
                proxy_cache                     off;
        }
}
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
 * How to put behind reverse proxy such as nginx, httpd, etc
 * How to set CPU and/or memory limits
 * Test nginx config
