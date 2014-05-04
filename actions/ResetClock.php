<?php
if (AccessControl::hasAccess()) {
    $configManager = ConfigurationManager::instance();
    $iniArr = parse_ini_file(FILE_CONFIG);
    $clock = $iniArr["freq"];

    $clockSettings = $configManager->getClockSettings();
    $counter = 0;
    foreach($clockSettings as $serial=>$clockSetting) {
        $configManager->setClockSetting($serial,$clock);
        CGMinerClient::setClockSpeed($counter, $clock);
        $counter++;
    }

    AjaxUtils::printStatusMessage(OK, "clock speed reset to $clock");
}else{
    AjaxUtils::printAccessDenied();
}

?>