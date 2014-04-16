<?php 
define("OK", "OK");
define("NOTOK" , "NOTOK");
class AjaxUtils{
	
	static function printStatusMessage($status, $msg, $payLoad = null) {
		$jsonObj = array("STATUS" => $status, "MESSAGE"=> $msg );
		if (!empty($payLoad)) {
			$jsonObj["PAYLOAD"] = $payLoad;
		}
		echo json_encode($jsonObj);
	}
	
	static function printAccessDenied () {
		AjaxUtils::printStatusMessage(NOTOK, "Access denied");
	}
}
?>