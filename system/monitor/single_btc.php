<?php
/**
 * ç›‘æŽ§å™¨ - BTCå�•æŒ–æ¨¡å¼�
 */


//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];


// //å�œæ­¢è¿›ç¨‹
// if(!empty($process['btc']) && !empty($devices['devids'])) {
// 	writeLog("Please remove power, one of more miners are hanging");
// 	foreach($process['btc'] as $pid => $proc) {
// 		Miner::shutdownBtcProc($pid);
// 		writeLog("BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
// 	}
// 	$process['btc'] = array();
// }
$count = 0;
foreach($process['btc'] as $pid => $proc) {
	$count++;
	if($count > 1) {
		//å�œæ­¢é‡�å¤�è¿›ç¨‹
		Miner::shutdownBtcProc($pid);
		writeLog("BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
		unset($process['btc'][$pid]);
	}
}


//å�¯åŠ¨è¿›ç¨‹
if(empty($process['btc']) && !empty($devices['bus'])) {
	//å†™ç¼“å­˜
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['btc'] = array();
	$cache->set(CACHE_STATS, $stats);
	//å�¯åŠ¨
	$re = Miner::startupBtcProc($config['btc_url'], $config['btc_worker'], $config['btc_pass'], $config['freq'], 16);
	if($re === false) {
		writeLog("BTC process fails to start");
		//é‡�å�¯USBç”µæº�
		Miner::closePower();
		writeLog("Close the USB controller power");
		usleep(1000000);
		Miner::openPower();
		writeLog("Open the USB controller power");
		return;
	}
	//Log
	writeLog("BTC process startup: Pid={$re['pid']} Worker={$config['btc_worker']} Frequency={$config['freq']} Devices=".implode(',',$re['devids'])." Bus=".implode(',',$devices['bus']));
}


//å®•æœºæ£€æµ‹
$freezeTime = 900;
$timeNow = time();
$arr = $cache->get(CACHE_RUNTIME);
if($arr===false) {
	$arr = array('runtime' => $timeNow);
}
if(($arr['runtime'] + $freezeTime) > $timeNow) {
	return;
}
$arr = $cache->get(CACHE_STATS);
$diedBus = array();
foreach($devices['bus'] as $bus) {
	if(!isset($arr['lastcommit']['btc'][$bus])) {
		$diedBus[] = $bus;
		continue;
	}
	if(($arr['lastcommit']['btc'][$bus] + $freezeTime) < $timeNow) {
		$diedBus[] = $bus;
		continue;
	}
}
if(!empty($diedBus)) {
	writeLog("Device downtime (BTC): DiedBus=".implode(',',$diedBus));
	//å…³é—­æ‰€æœ‰minerè¿›ç¨‹
	Miner::shutdownBtcProc();
	writeLog("BTC process shutdown");
	//é‡�å�¯USBç”µæº�
	Miner::closePower();
	writeLog("Close the USB controller power");
	usleep(1000000);
	Miner::openPower();
	writeLog("Open the USB controller power");
}
