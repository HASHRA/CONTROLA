<?php
header ( 'Content-type: text/html; charset=utf-8' );
?>

	<?php
		exec('sudo tar -cvzf  /var/www/backup/backup-' . time () . '.zip /var/www/ --exclude=\'backup\' --exclude=\'.git\'', $output);
		echo "done with tarring<br/>";
		flush();
		ob_flush();
		exec('sudo git clone --depth=1 -b pi-controller https://bitbucket.org/purplefox/hashra-public-firmware.git /var/www/git', $output);
		echo "done with downloading <br/>";
		flush();
		ob_flush();
		exec('sudo cd /var/www/git ; sudo cp -Rfv * ../');
		echo "updated, cleaning up <br/>";
		flush();
		ob_flush();
		exec('sudo rm -rf /var/www/git; sudo rm -rf /var/www/.git');
		flush();
		ob_flush();
		echo "Update completed!";
		
		
	?>
