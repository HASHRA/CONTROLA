#!/bin/ash

for log in $(ls -1 /var/log/ltc*/* 2> /dev/null; ls -1 /var/log/btc/* 2> /dev/null); do
	logpath="/var/log/$(echo $log | cut -d "/" -f4).log"
	cat $log >> $logpath && cat /dev/null > $log
	last=$(tail -c 10k $logpath)
	echo $last > $logpath
done