<?php
if (AccessControl::hasAccess()) {
    $configManager = ConfigurationManager::instance();
    $serial = $_POST["serial"];
    $clock = $_POST["clock"];
    $devId = $_POST["id"];

    $configManager->setClockSetting($serial,$clock);
    CGMinerClient::setClockSpeed($devId, $clock);

    AjaxUtils::printStatusMessage(OK, "clockspeed set to $clock for device with id $devId and serial $serial");
}else{
    AjaxUtils::printAccessDenied();
}

?>