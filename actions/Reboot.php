<?php 

require_once 'class/accesscontrol.class.php';

if (AccessControl::hasAccess()) { 
	AjaxUtils::printStatusMessage(OK, "Rebooting, this might take a while");
	exec("sudo reboot &");
}else{
	AjaxUtils::printAccessDenied();
}
?>