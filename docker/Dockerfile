FROM centos:centos6
MAINTAINER Paulson McIntyre, <paul+docker.rfwadmin@gpmidi.net>


##### Enviorement S&C #####
# Sane umask
RUN umask 022
ONBUILD RUN umask 022

# Set default env vars
#ENV http_proxy ""
ENV UI_HTML_TITLE "Minecraft Map Management"
ENV UI_ARMORY_ENABLED 1
ENV UI_PHP_TIMEZONE "UTC"
ENV MC_MEM_LOW "1024M"
ENV MC_MEM_HIGH "1024M"
ENV MC_WORLD "world"
ENV MC_SERVER_ARGS "-server"
ENV MC_USE_SAFEOFF 0


##### Actual Work #####
# Do an initial update
RUN yum -y update

# Fix local errors when logging in
RUN yum -y reinstall glibc-common

RUN yum -y install vim-enhanced wget zip unzip lsof \
  git python-devel screen java-1.7.0-openjdk php httpd php-curl

# Setup apache
RUN  chkconfig httpd on \
  && rm -Rf /etc/httpd/conf.d/welcome.conf \
  && echo "date.timezone = GMT" > /etc/php.d/timezone.ini \
  && sed -i 's/upload_max_filesize = [0-9]\{1,8\}M/upload_max_filesize = 512M/' /etc/php.ini

# Setup screen
RUN  chmod 777 /var/run/screen \
  && chown root:root /var/run/screen

# Get RFW Admin
RUN  cd /root/ \
  && git clone https://github.com/gpmidi/rfwadmin.git

RUN  service httpd start \
  && sleep 5 \
  && bash --login -c "cd /root/rfwadmin/ && /bin/bash /root/rfwadmin/install.sh && /etc/init.d/minecraft_default.sh stop" \
  && service httpd stop

# Save as it'll be mounted over
RUN  mkdir -p /var/lib/minecraft/servers/default/server.stock/ \
  && rm -Rf /var/lib/minecraft/servers/default/server/{logs,world} \
  && mkdir -p /var/lib/minecraft/servers/default/server/{logs,world} \
  && echo "" > /var/lib/minecraft/servers/default/server/logs/latest.log \
  && echo "" > /var/lib/minecraft/servers/default/screen.log \
  && chown -R apache:apache /var/lib/minecraft/servers/default/server/ \
  && chmod -R 644 /var/lib/minecraft/servers/default/server \
  && chmod 755 /var/lib/minecraft/servers/default/server/ /var/lib/minecraft/servers/default/server/{logs,world} \
  && cp -a /var/lib/minecraft/servers/default/server/* /var/lib/minecraft/servers/default/server.stock \
  && rm -Rf /var/lib/minecraft/servers/default/server.stock/world

# Add startup script
ADD run.sh /run.sh


##### Final #####
# Cleanup
RUN yum -y clean all
RUN rm -Rf /tmp/* /var/tmp/* /root/.bash_history /var/lib/minecraft/jars/{cache,converter,plugins,serverjars}/*

# Directories to share out
VOLUME [ "/var/log/httpd", "/var/lib/minecraft/servers/default/server", "/var/lib/minecraft/maps", "/var/lib/minecraft/jars" ]

EXPOSE 80 25565

ENTRYPOINT [ "/run.sh" ]
