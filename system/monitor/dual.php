<?php
/**
 * ç›‘æŽ§å™¨ - å�ŒæŒ–æ¨¡å¼�
 */

//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];

openlog("dual_monitor", LOG_PID, LOG_LOCAL0);

$sysSettings = ConfigurationManager::instance()->getSystemSettings();

$count = 0;
foreach($process['btc'] as $pid => $proc) {
	$count++;
	if($count > 1) {
		//another btc process exists
		Miner::shutdownBtcProc($pid);
		syslog(LOG_INFO, "BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
		unset($process['btc'][$pid]);
	}
}

//check the elapsed time. restart miners after x hours

$stats = $cache->get(CACHE_STATS);
if (isset($stats["summary"]) && $systemSettings->restartevery > 0){
	$elapsed = intval($stats["summary"]["elapsed"]);
	if ($elapsed > ($systemSettings->restartevery * 60 * 60 ) ) {
		syslog(LOG_INFO, "Maintenance restart started");
		Miner::shutdownBtcProc();
		Miner::shutdownCPUMinerProc();
		sleep(10);
	}
}

//shutdown excess CPU miner processes
if(empty($process['btc'])) {
	foreach($process['ltc'] as $pid => $proc) {
		Miner::shutdownCPUMinerProc($pid);
		writeLog("LTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
	}
	$process['ltc'] = array();
} else {
	$excess = array();
	$count = 0;
	foreach($process['ltc'] as $pid => $proc) {
		//excess devices
		if($count > count($devices["devids"])) {
			$excess[] = $proc;
		}
		$count++;
	}
	foreach ($excess as $proc) {
		Miner::shutdownCPUMinerProc($proc['pid']);
		syslog(LOG_INFO, " Excess LTC process shutdown: Pid={$proc['pid']} Worker={$proc['worker']}");
		unset($process['ltc'][$pid]);
	}
}


//startup BTC
if(empty($process['btc'])) {
	//startup BTC
	$re = Miner::startupBtcProc($config['btc_url'], $config['btc_worker'], $config['btc_pass'], $config['freq'], $sysSettings->btccoresdual);
	//Log
	syslog(LOG_INFO, "BTC process startup: Pid={$re['pid']} Worker={$config['btc_worker']} Frequency={$config['freq']} Devices=".implode(',',$re['devids'])." Bus=".implode(',',$devices['bus']));
}


$freeMinersCount = count($devices["devids"]) - count($process["ltc"]);
if($freeMinersCount > 0) {
	//found free miners
	for($i = 0 ; $i < $freeMinersCount ; $i++) {
		//starting cpu miner
		$pid = Miner::startupCPUMinerProc(
			$bus,
			$config['ltc_url'],
			$config['ltc_worker'],
			$config['ltc_pass'],
			$config['freq'],
			true
		);
		syslog(LOG_INFO, "LTC process startup: Bus={$bus} Pid={$pid} Worker={$worker} Frequency={$config['freq']}");
	}
}
