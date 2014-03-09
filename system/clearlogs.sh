#!/bin/ash

while true ; do
	sleep 3600
	for log in $(ls -1 /var/log/btc.log 2> /dev/null); do
		> $log;
	done
done
exit 0