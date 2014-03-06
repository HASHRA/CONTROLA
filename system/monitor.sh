#!/bin/ash
path='/www/system'
count=0
while true ; do
	/usr/bin/php-cgi ${path}/update_cache.php stats > /dev/null 2>&1
	/usr/bin/php-cgi ${path}/update_cache.php device > /dev/null 2>&1
	/usr/bin/php-cgi ${path}/update_cache.php process > /dev/null 2>&1
	/usr/bin/php-cgi ${path}/monitor.php c=${count} > /dev/null 2>&1
	count=$((count+1))
	bash ${path}/clearlogs.sh
	sleep 10
done
exit 0
