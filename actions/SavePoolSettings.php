<?php 
require_once 'class/accesscontrol.class.php';

	if ( AccessControl::hasAccess() ) {
		$arrPoolType = $_REQUEST["pool_type"];
		$arrPoolUrl = $_REQUEST["pool_url"];
		$arrPoolWorker = $_REQUEST["pool_worker"];
		$arrPoolPassword = $_REQUEST["pool_password"];
		
		$configMan = ConfigurationManager::instance();
		$counter = 0;
		foreach ($arrPoolType as $poolType) {
			if (!empty(trim($arrPoolUrl))) {
				$configMan->setPoolSettings($poolType, $arrPoolUrl[$counter], $arrPoolWorker[$counter], $arrPoolPassword[$counter]);
			}
			$counter ++;
		}
		
		AjaxUtils::printStatusMessage(OK, "Pool settings saved");	
	}else {
		AjaxUtils::printAccessDenied();
	}
	
?>