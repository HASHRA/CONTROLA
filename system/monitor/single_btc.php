<?php
/**
 * 监控器 - BTC单挖模式
 */


//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];


//停止进程
if(!empty($process['btc']) && !empty($devices['devids'])) {
	writeLog("Please remove power, one of more miners are hanging");
	foreach($process['btc'] as $pid => $proc) {
		Miner::shutdownBtcProc($pid);
		writeLog("BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
	}
	$process['btc'] = array();
}
$count = 0;
foreach($process['btc'] as $pid => $proc) {
	$count++;
	if($count > 1) {
		//停止重复进程
		Miner::shutdownBtcProc($pid);
		writeLog("BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
		unset($process['btc'][$pid]);
	}
}


//启动进程
if(empty($process['btc']) && !empty($devices['bus'])) {
	//写缓存
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['btc'] = array();
	$cache->set(CACHE_STATS, $stats);
	//启动
	$re = Miner::startupBtcProc($config['btc_url'], $config['btc_worker'], $config['btc_pass'], $config['freq']);
	if($re === false) {
		writeLog("BTC process fails to start");
		//重启USB电源
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


//宕机检测
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
	//关闭所有miner进程
	Miner::shutdownBtcProc();
	writeLog("BTC process shutdown");
	//重启USB电源
	Miner::closePower();
	writeLog("Close the USB controller power");
	usleep(1000000);
	Miner::openPower();
	writeLog("Open the USB controller power");
}
