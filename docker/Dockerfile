FROM centos:latest
MAINTAINER Paulson McIntyre, <paul+docker.rfwadmin@gpmidi.net>

RUN ls -alh /

# Sane umask
RUN umask 022
#ONBUILD RUN umask 022

RUN ls -alh /

# Do an initial update
RUN yum -y update

RUN ls -alh /

# Fix local errors when logging in
RUN yum -y reinstall glibc-common

RUN ls -alh /

RUN yum -y install vim-enhanced wget zip unzip lsof \
  git python-devel screen java-1.7.0-openjdk php httpd php-curl

RUN ls -alh /

# Setup apache
RUN  chkconfig httpd on \
  && rm -Rf /etc/httpd/conf.d/welcome.conf \
  && echo "date.timezone = GMT" > /etc/php.d/timezone.ini

RUN ls -alh /

# Setup screen
RUN  chmod 777 /var/run/screen \
  && chown root:root /var/run/screen

RUN ls -alh /

# Get RFW Admin
RUN  cd /root/ \
  && git clone https://github.com/Thue/rfwadmin.git

RUN ls -alh /

RUN  service httpd start \
  && su - -c "cd /root/rfwadmin/ && /bin/bash /root/rfwadmin/install.sh && /etc/init.d/minecraft_default.sh stop" \
  && service httpd stop

RUN ls -alh /

# Save as it'll be mounted over
RUN  mkdir -p /var/lib/minecraft/servers/default/server.stock/ \
  && rm -Rf /var/lib/minecraft/servers/default/server/{logs,world} \
  && mkdir -p /var/lib/minecraft/servers/default/server/{logs,world} \
  && echo "" > /var/lib/minecraft/servers/default/server/logs/latest.log \
  && echo "" > /var/lib/minecraft/servers/default/screen.log \
  && chown -R apache:apache /var/lib/minecraft/servers/default/server/ \
  && chmod -R 644 /var/lib/minecraft/servers/default/server \
  && chmod 755 /var/lib/minecraft/servers/default/server/ /var/lib/minecraft/servers/default/server/{logs,world} \
  && cp -a /var/lib/minecraft/servers/default/server /var/lib/minecraft/servers/default/server.stock

RUN ls -alh /

# Add startup script
ADD run.sh /run.sh

RUN ls -alh /

##### Final #####

# Cleanup
RUN yum -y clean all
RUN rm -Rf /tmp/* /var/tmp/* /root/.bash_history /var/lib/minecraft/jars/{cache,converter,plugins,serverjars}/*

RUN ls -alh /


# Directories to share out
VOLUME [ "/var/log", "/var/lib/minecraft/servers/default/server", "/var/lib/minecraft/maps" ]

EXPOSE 80 25565


CMD [ "/bin/bash", "/run.sh" ]


RUN ls -alh /
