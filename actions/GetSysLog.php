<?php 
	exec('sudo tail -n50 /var/log/syslog | grep -v PHP | grep -v \'Share above target\'', $out);
	$pattern = '/hashra(\w+) /i';
	foreach	($out as $line) {
		echo preg_replace($pattern, '', $line) ."<br/>\n";
	}
?>