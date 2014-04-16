<?php 
	$uname  = $_REQUEST["username"];
	$password = $_REQUEST["password"];
	
	if (UserManager::instance()->login($uname, $password)) {
		session_start();
		$_SESSION["user"] =  $uname;
		AjaxUtils::printStatusMessage(OK, "Successfully logged in");
	}else{
		AjaxUtils::printAccessDenied();
	}
?>