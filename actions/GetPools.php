<?php 
	
	if (AccessControl::hasAccess()) {
		$pools = ConfigurationManager::instance()->getPools($_REQUEST['pooltype']);
		AjaxUtils::printStatusMessage(OK, count($pools). ' pools found ', $pools);
	}else{
		AjaxUtils::printAccessDenied();
	}	

?>