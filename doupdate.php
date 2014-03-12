<?php
header ( 'Content-type: text/html; charset=utf-8' );
?>
Wait for it!!!.....<br/>
	<?php
	flush();
	ob_flush();	
		exec('sudo tar -cvzf  /var/www/backup/backup-' . time () . '.zip /var/www/ --exclude=\'backup\' --exclude=\'.git\'', $output);
		echo "done with tarring<br/>";
		flush();
		ob_flush();
		exec('sudo mkdir /var/tmp/updatework ; sudo git clone --depth=1 -b pi-controller https://bitbucket.org/purplefox/hashra-public-firmware.git /var/tmp/updatework', $output);
		echo "done with downloading <br/>";
		flush();
		ob_flush();
		exec('sudo cp -Rfv /var/tmp/updatework/* /var/www');
		flush();
		ob_flush();
		exec('sudo rm -rf /var/www/git; sudo rm -rf /var/www/.git');
		echo "Update completed!";
		
	?>
<script type="text/javascript">
$("#update-button-close").removeAttr('disabled');
</script>
