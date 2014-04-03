<?php 
define("OK", "OK");
define("NOTOK" , "NOTOK");
class AjaxUtils{
	
	static function printStatusMessage($status, $msg) {
		$jsonObj = array("STATUS" => $status, "MESSAGE"=> $msg );
		echo json_encode($jsonObj);
	}
	
	static function printAccessDenied () {
		AjaxUtils::printStatusMessage(NOTOK, "Access denied");
	}
}
?>