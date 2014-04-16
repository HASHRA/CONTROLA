<?php 

require_once 'class/accesscontrol.class.php';

if(AccessControl::hasAccess()) {
	$user = UserManager::instance()->getUser(AccessControl::getLoggedInUserName());
	if ($user != null) {
		UserManager::instance()->changePassword($user->user, $_REQUEST["password"]);
	}
	AjaxUtils::printStatusMessage(OK, "User password changed");
}else{
	AjaxUtils::printAccessDenied();
}

?>