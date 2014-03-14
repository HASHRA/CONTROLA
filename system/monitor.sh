#!/bin/ash
path='/var/www/system'
count=0
while true ; do
	sudo /usr/bin/php ${path}/update_cache.php device > /dev/null 2>&1
	sudo /usr/bin/php ${path}/update_cache.php stats > /dev/null 2>&1
	sudo /usr/bin/php ${path}/update_cache.php process > /dev/null 2>&1
	sudo /usr/bin/php ${path}/monitor.php c=${count} > /dev/null 2>&1
	count=$((count+1))
	sleep 20
done
exit 0