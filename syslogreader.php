<?php 
	exec('sudo tail -n20 /var/log/syslog | grep -v PHP', $out);
	foreach	($out as $line) {
		echo $line ."\n";
	}
?>
