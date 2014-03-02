<?php
/**
 * 监控器 - 双挖模式
 */

//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];



//停止进程 - BTC
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


//停止进程 - LTC
if(empty($process['btc'])) {
	foreach($process['ltc'] as $pid => $proc) {
		Miner::shutdownLtcProc($pid);
		writeLog("LTC process shutdown: Bus={$proc['devid']} Pid={$pid} Worker={$proc['worker']}");
	}
	$process['ltc'] = array();
} else {
	$excess = array();
	foreach($process['ltc'] as $pid => $proc) {
		$busid = $proc['devid'];
		$excess[$busid][] = $pid;
		//对应无效设备的进程
		if(!in_array($busid, $devices['bus'])) {
			Miner::shutdownLtcProc($pid);
			writeLog("LTC process shutdown: Bus={$busid} Pid={$pid} Worker={$proc['worker']}");
			unset($process['ltc'][$pid]);
		}
	}
	foreach($excess as $busid => $pids) {
		//检查重复开启的进程
		if(count($pids) > 1) {
			for($i=1; $i<count($pids); $i++) {
				$pid = $pids[$i];
				$proc = $process['ltc'][$pid];
				Miner::shutdownLtcProc($pid);
				writeLog("LTC process shutdown: Bus={$busid} Pid={$pid} Worker={$proc['worker']}");
				unset($process['ltc'][$pid]);
			}
		}
	}
}


//启动进程 - BTC
if(empty($process['btc'])) {
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
		//关闭所有进程
		Miner::shutdownBtcProc();
		Miner::shutdownLtcProc();
		writeLog("All process shutdown");
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



//更新LTC可用矿工
$workers = $unusedWorkers = explode(',', $config['ltc_workers']);
foreach($unusedWorkers as $k => $worker) {
	$unusedWorkers[$k] = trim($worker);
	if(empty($unusedWorkers[$k])) {
		unset($unusedWorkers[$k]);
	}
}
foreach($process['ltc'] as $proc) {
	$idx = array_search($proc['worker'], $unusedWorkers);
	if($idx !== false) {
		unset($unusedWorkers[$idx]);
	}
}
sort($unusedWorkers);
//启动 - LTC
$usedBus = array();
$unusedBus = array();
foreach($process['ltc'] as $proc) {
	$usedBus[] = $proc['devid'];
}
foreach($devices['bus'] as $bus) {
	if(!in_array($bus, $usedBus)) {
		$unusedBus[] = $bus;
	}
}
if(!empty($unusedBus)) {
	//写缓存
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['ltc'] = array();
	$cache->set(CACHE_STATS, $stats);
	foreach($unusedBus as $bus) {
		//启动
		$worker = !empty($unusedWorkers) ? array_shift($unusedWorkers) : $workers[0];
		$pid = Miner::startupLtcProc(
			$bus,
			$config['ltc_url'],
			$worker,
			$config['ltc_pass'],
			$config['freq'],
			true
		);
		writeLog("LTC process startup: Bus={$bus} Pid={$pid} Worker={$worker} Frequency={$config['freq']}");
	}
}


//宕机检测 - BTC
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
	//关闭所有进程
	Miner::shutdownBtcProc();
	Miner::shutdownLtcProc();
	writeLog("All process shutdown");
	//重启USB电源
	Miner::closePower();
	writeLog("Close the USB controller power");
	usleep(1000000);
	Miner::openPowe();
	writeLog("Open the USB controller power");
	return;
}


//宕机检测 - LTC
$freezeTime = 600;
$timeNow = time();
$runtime = $cache->get(CACHE_RUNTIME);
if($runtime===false) {
	$runtime = array('runtime' => $timeNow);
}
if(($runtime['runtime'] + $freezeTime) > $timeNow) {
	return;
}
$stats = $cache->get(CACHE_STATS);
$diedBus = array();
foreach($devices['bus'] as $bus) {
	if(!isset($stats['lastcommit']['ltc'][$bus])) {
		$diedBus[] = $bus;
		continue;
	}
	if(($stats['lastcommit']['ltc'][$bus] + $freezeTime) < $timeNow) {
		$diedBus[] = $bus;
		continue;
	}
}
if(!empty($diedBus)) {
	writeLog("Device downtime (LTC): DiedBus=".implode(',',$diedBus));
	//关闭所有进程
	Miner::shutdownBtcProc();
	Miner::shutdownLtcProc();
	writeLog("All process shutdown");
	//重启USB电源
	Miner::closePower();
	writeLog("Close the USB controller power");
	usleep(1000000);
	Miner::openPower();
	writeLog("Open the USB controller power");
}

