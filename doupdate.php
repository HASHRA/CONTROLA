<?php

require_once 'class/accesscontrol.class.php';
require_once 'class/configmanager.class.php';

if (!AccessControl::hasAccess()){
	header('Location: login.php');
	die();
}
header ( 'Content-type: text/html; charset=utf-8' );
?>
Please hold on, this may take a couple of minutes... Do not close this dialog box before I'm finished, Thanks.<br/>
	<?php
	flush();
	ob_flush();	
// 		exec('sudo tar -cvzf  /var/www/backup/backup-' . time () . '.zip /var/www/ --exclude=\'backup\' --exclude=\'.git\'', $output);
// 		echo "done with tarring<br/>";

		echo "Killing all miner processes<br/>";
		flush();
		ob_flush();
		exec ('sudo killall -9 bfgminer');
		exec ('sudo killall -9 cgminer');
		exec ('sudo killall -9 cpuminer');
		usleep('1000');
		$config = ConfigurationManager::instance()->getSystemSettings();
		exec('sudo rm -rf /var/tmp/updatework/; sudo mkdir /var/tmp/updatework ; sudo git clone --depth=1 -b pi-controller '.$config->updateurl.' /var/tmp/updatework', $output);
		echo "Now done with downloading <br/>";
		flush();
		ob_flush();
		exec('sudo cp -Rfv /var/tmp/updatework/* /var/www');
		exec('sudo rm -rf /var/www/soft/bfg; sudo mkdir /var/www/soft/bfg');
		exec('sudo tar -xvf /var/www/soft/bfg-binary.tar -C /var/www/soft');
		exec('sudo chmod -R 755 /var/www/soft');
		exec('sudo chmod -R 755 /var/www/config');
		exec('sudo chown -R www-data /var/www/config/');
		flush();
		ob_flush();
		exec('sudo rm -rf /var/www/git; sudo rm -rf /var/www/.git; sudo rm -rf /var/tmp/updatework');
		
		require 'config/define.php';
		echo "Update is completed! you now have version <strong> ". VERSION ." </strong><br/>
			  System is rebooting. <br/>
				";
		
		flush();
		ob_flush();
	?>

	When you close this dialogue box you will be redirected to the Dashboard in 20 seconds.<br/>
	<h3>Happy Hashing!</h3>
<script type="text/javascript">
$("#update-button-close").html("All updated, you can close me now.");
$("#update-button-close").removeAttr('disabled');

<?php 
	flush();
	ob_flush();
	
	exec("sudo reboot");
	
	?>

</script>

