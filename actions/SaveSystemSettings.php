<?php 
require_once 'class/accesscontrol.class.php';

	if ( AccessControl::hasAccess() ) {
		ConfigurationManager::instance()->setSystemSettings(
			$_REQUEST["updateurl"],
            $_REQUEST["chipcount"]
		);
		AjaxUtils::printStatusMessage(OK, "System settings saved");
	}else{
		AjaxUtils::printAccessDenied(); 
	}
	
?>