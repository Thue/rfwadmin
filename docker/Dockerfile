FROM centos:latest
MAINTAINER Paulson McIntyre, <paul+docker.rfwadmin@gpmidi.net>

# Sane umask
RUN umask 0022 && umask 0022
ONBUILD RUN umask 0022 && umask 0022

# Do an initial update
RUN umask 0022 && yum -y update

# Fix local errors when logging in
RUN umask 0022 && yum -y reinstall glibc-common

RUN umask 0022 && yum -y install vim-enhanced wget zip unzip lsof \
  git python-devel screen java-1.7.0-openjdk php httpd php-curl

# Setup apache
RUN umask 0022 &&  chkconfig httpd on \
  && rm -Rf /etc/httpd/conf.d/welcome.conf \
  && echo "date.timezone = GMT" > /etc/php.d/timezone.ini

# Setup screen
RUN umask 0022 &&  chmod 777 /var/run/screen \
  && chown root:root /var/run/screen

# Get RFW Admin
RUN umask 0022 &&  cd /root/ \
  && git clone https://github.com/Thue/rfwadmin.git

RUN umask 0022 &&  service httpd start \
  && su - -c "cd /root/rfwadmin/ && /bin/bash /root/rfwadmin/install.sh && /etc/init.d/minecraft_default.sh stop" \
  && service httpd stop

# Save as it'll be mounted over
RUN umask 0022 &&  mkdir -p /var/lib/minecraft/servers/default/server.stock/ \
  && rm -Rf /var/lib/minecraft/servers/default/server/{logs,world} \
  && mkdir -p /var/lib/minecraft/servers/default/server/{logs,world} \
  && echo "" > /var/lib/minecraft/servers/default/server/logs/latest.log \
  && echo "" > /var/lib/minecraft/servers/default/screen.log \
  && chown -R apache:apache /var/lib/minecraft/servers/default/server/ \
  && chmod -R 644 /var/lib/minecraft/servers/default/server \
  && chmod 755 /var/lib/minecraft/servers/default/server/ /var/lib/minecraft/servers/default/server/{logs,world} \
  && cp -a /var/lib/minecraft/servers/default/server /var/lib/minecraft/servers/default/server.stock

# Add startup script
ADD run.sh /run.sh

##### Final #####

# Cleanup
RUN umask 0022 && yum -y clean all
RUN umask 0022 && rm -Rf /tmp/* /var/tmp/* /root/.bash_history /var/lib/minecraft/jars/{cache,converter,plugins,serverjars}/*

# Directories to share out
VOLUME [ "/var/log", "/var/lib/minecraft/servers/default/server", "/var/lib/minecraft/maps" ]

EXPOSE 80 25565


CMD [ "/bin/bash", "/run.sh" ]

