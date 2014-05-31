<?php 
require_once 'class/accesscontrol.class.php';

	if ( AccessControl::hasAccess() ) {
		$config = ConfigurationManager::instance();
		$config->setSystemSettings(
			$_REQUEST["updateurl"]
		);
		$config->setProductSettings($_REQUEST["chipcount"]);
		AjaxUtils::printStatusMessage(OK, "System settings saved");
	}else{
		AjaxUtils::printAccessDenied(); 
	}
	
?>