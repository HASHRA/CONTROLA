<?php 
	if (AccessControl::hasAccess()) {
		$configManager = ConfigurationManager::instance();
		$configManager->deletePool($_POST["type"], $_POST["id"]);
		$pools = $configManager->getPools($_POST["type"]);
		AjaxUtils::printStatusMessage(OK, count($pools). ' pools found ', $pools);
	}else{
		AjaxUtils::printAccessDenied();
	}	

?>