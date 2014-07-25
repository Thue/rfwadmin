#!/bin/bash
set -x

echo "Starting at "`/bin/date`

cd /var/lib/minecraft/

# Set screen dir perms if they aren't already set
/bin/chmod 777 /var/run/screen
/bin/chown root:root /var/run/screen


# FIXME: Pre-download latest MC server and anvilconverter
# AVC in /var/lib/minecraft/jars/converter/Minecraft.AnvilConverter.zip


# Ensure the standard files exist
/bin/cp -an /var/lib/minecraft/servers/default/server.stock/* \
    /var/lib/minecraft/servers/default/server/
/bin/mkdir -p /var/log/httpd /var/lib/minecraft/maps \
    /var/lib/minecraft/servers /var/lib/minecraft/jars \
    /var/lib/minecraft/jars/{cache,converter,plugins,serverjars}/
/bin/chown -R apache:apache /var/log/httpd /var/lib/minecraft/maps \
    /var/lib/minecraft/servers /var/lib/minecraft/jars


# Handle proxy settings
if [ "$http_proxy" == "" ]; then
    echo "No proxy set"
else
    echo "HTTP proxy set to ${http_proxy}"
fi

# Handle web UI title settings
if [ "${UI_HTML_TITLE}" == "" ]; then
    echo "UI_HTML_TITLE not set"
else
    echo "UI_HTML_TITLE set to ${UI_HTML_TITLE}"
fi

# Handle web UI armory settings
if [ "${UI_ARMORY_ENABLED}" == "" ]; then
    echo "UI_ARMORY_ENABLED not set"
else
    echo "UI_ARMORY_ENABLED set to ${UI_ARMORY_ENABLED}"
fi

# Handle web UI PHP timezone settings
if [ "${UI_PHP_TIMEZONE}" == "" ]; then
    echo "UI_PHP_TIMEZONE not set"
else
    echo "UI_PHP_TIMEZONE set to ${UI_PHP_TIMEZONE}"
fi

# Handle MC_MEM_LOW settings
if [ "${MC_MEM_LOW}" == "" ]; then
    echo "MC_MEM_LOW not set"
else
    echo "MC_MEM_LOW set to ${MC_MEM_LOW}"
    export MEM_LOW="${MC_MEM_LOW}"
fi

# Handle MC_MEM_HIGH settings
if [ "${MC_MEM_HIGH}" == "" ]; then
    echo "MC_MEM_HIGH not set"
else
    echo "MC_MEM_HIGH set to ${MC_MEM_HIGH}"
    export MEM_HIGH="${MC_MEM_HIGH}"
fi

# Handle MC_WORLD settings
if [ "${MC_WORLD}" == "" ]; then
    echo "MC_WORLD not set"
else
    echo "MC_WORLD set to ${MC_WORLD}"
    export WORLD="${MC_WORLD}"
fi

# Handle MC_SERVER_ARGS settings
if [ "${MC_SERVER_ARGS}" == "" ]; then
    echo "SERVER_ARGS not set"
else
    echo "SERVER_ARGS set to ${MC_SERVER_ARGS}"
    export SERVER_ARGS="${MC_SERVER_ARGS}"
fi

# Handle MC_USE_SAFEOFF settings
if [ "${MC_USE_SAFEOFF}" == "" ]; then
    echo "USE_SAFEOFF not set"
else
    echo "USE_SAFEOFF set to ${MC_USE_SAFEOFF}"
    export USE_SAFEOFF="${MC_USE_SAFEOFF}"
fi



/usr/sbin/apachectl -D FOREGROUND
exit $?
