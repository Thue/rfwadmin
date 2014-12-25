#!/bin/bash

read -p "This will delete saved maps and everything else under /var/lib/minecraft. Continue? (y/n)" -n 1 -r
echo    # (optional) move to a new line
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi

if [ -e /etc/init.d/minecraft_default.sh ]; then
    /etc/init.d/minecraft_default.sh stop
    /etc/init.d/minecraft_default.sh nuke
fi

if [ -d /var/www/rfwadmin ]; then
    rm -rfv /var/www/rfwadmin
elif [ -d /var/www/html/rfwadmin ]; then
    rm -rfv /var/www/html/rfwadmin
else
    echo Couldn\'t find any rfwadmin files in /var/www to delete
fi

rm -rfv /var/lib/minecraft
rm -fv /etc/init.d/minecraft_default.sh
rm -fv /etc/rc0.d/K01minecraft_default.sh
rm -fv /etc/rc1.d/K01minecraft_default.sh
rm -fv /etc/rc6.d/K01minecraft_default.sh
rm -fv /etc/rc2.d/K01minecraft_default.sh
rm -fv /etc/rc3.d/S99minecraft_default.sh
rm -fv /etc/rc4.d/S99minecraft_default.sh
rm -fv /etc/rc5.d/S99minecraft_default.sh

echo All rfwadmin files should now be deleted.
