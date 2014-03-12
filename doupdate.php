<?php
header ( 'Content-type: text/html; charset=utf-8' );
?>
Wait for it!!!.....Close this dialog at your own risk!<br/>
	<?php
	flush();
	ob_flush();	
// 		exec('sudo tar -cvzf  /var/www/backup/backup-' . time () . '.zip /var/www/ --exclude=\'backup\' --exclude=\'.git\'', $output);
// 		echo "done with tarring<br/>";

		echo "Killing all miner processes<br/>";
		flush();
		ob_flush();
		exec ('sudo killall -9 cgminer');
		usleep('1000');
		exec('sudo rm -rf /var/tmp/updatework/; sudo mkdir /var/tmp/updatework ; sudo git clone --depth=1 -b pi-controller https://bitbucket.org/purplefox/hashra-public-firmware.git /var/tmp/updatework', $output);
		echo "done with downloading <br/>";
		flush();
		ob_flush();
		exec('sudo cp -Rfv /var/tmp/updatework/* /var/www');
		flush();
		ob_flush();
		exec('sudo rm -rf /var/www/git; sudo rm -rf /var/www/.git');
		
		require 'config/define.php';
		echo "Update completed! you now have version <strong> ". VERSION ." </strong><br/>";
		
		flush();
		ob_flush();
	?>

	The system is rebooting, when you close this dialog, you will be redirected to the homepage. <br/>
	<h3>Have a good day.</h3>
<script type="text/javascript">
$("#update-button-close").html("Ah, updated, you can close me now");
$("#update-button-close").removeAttr('disabled');

<?php 
	flush();
	ob_flush();
	
	exec("sudo reboot");
	
	?>

</script>

