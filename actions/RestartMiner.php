<?php 

require_once 'class/accesscontrol.class.php';

if (AccessControl::hasAccess()) { 
	exec('sudo touch '. FILE_CONFIG);
	exec('wget http://localhost/system/monitor.php > /dev/null &');
	AjaxUtils::printStatusMessage(OK, "Miner is restarting");
}else{
	AjaxUtils::printAccessDenied();
}
?>