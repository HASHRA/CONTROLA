<?php 
	if (AccessControl::hasAccess()) {
		$configManager = ConfigurationManager::instance();
		$pool = $_POST["pool"];
		$configManager->setPoolSettings($pool['id'], $pool['type'], $pool['url'], $pool['worker'], $pool['password']);
		$pools = $configManager->getPools($pool['type']);
		AjaxUtils::printStatusMessage(OK, count($pools). ' pools found ', $pools);
	}else{
		AjaxUtils::printAccessDenied();
	}	

?>