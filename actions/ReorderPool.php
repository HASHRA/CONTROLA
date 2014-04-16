<?php 
	if (AccessControl::hasAccess()) {
		$configManager = ConfigurationManager::instance();
		$configManager->rearrangePool($_POST['type'], $_POST['old'], $_POST['target']);
		$pools = $configManager->getPools($_POST['type']);
		AjaxUtils::printStatusMessage(OK, count($pools). ' pools found ', $pools);
	}else{
		AjaxUtils::printAccessDenied();
	}	

?>