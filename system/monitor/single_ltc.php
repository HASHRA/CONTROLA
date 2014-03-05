<?php

if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];

$excess = array();
foreach($process['ltc'] as $pid => $proc)
{
	$devid = $proc['devid'];
	$excess[$devid][] = $pid;
	if(!in_array($devid, $devices['devids']))
	{
		Miner::shutdownLtcProc($pid);
		writeLog("LTC process shutdown: DeviceID={$proc['devid']} Pid={$pid} Worker={$proc['worker']}");
		unset($process['ltc'][$pid]);
	}
}
foreach($excess as $devid => $pids)
{
	if(count($pids) > 1)
	{
		for($i=1; $i<count($pids); $i++)
		{
			$pid = $pids[$i];
			$proc = $process['ltc'][$pid];
			Miner::shutdownLtcProc($pid);
			writeLog("LTC process shutdown: DeviceID={$proc['devid']} Pid={$pid} Worker={$proc['worker']}");
			unset($process['ltc'][$pid]);
		}
	}
}

$workers = $unusedWorkers = explode(',', $config['ltc_workers']);
foreach($unusedWorkers as $k => $worker)
{
	$unusedWorkers[$k] = trim($worker);
	if(empty($unusedWorkers[$k]))
	{
		unset($unusedWorkers[$k]);
	}
}
foreach($process['ltc'] as $proc)
{
	$idx = array_search($proc['worker'], $unusedWorkers);
	if($idx !== false)
	{
		unset($unusedWorkers[$idx]);
	}
}
sort($unusedWorkers);

$usedDevids = array();
$unusedDevids = array();
foreach($process['ltc'] as $proc)
{
	$usedDevids[] = $proc['devid'];
}
foreach($devices['devids'] as $devid)
{
	if(!in_array($devid, $usedDevids))
	{
		$unusedDevids[] = $devid;
	}
}
if(!empty($unusedDevids))
{
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['ltc'] = array();
	$cache->set(CACHE_STATS, $stats);
	
	if (empty($process['btc']) && !empty($devices['bus'])){
		//have to start btc process
		$runtime = array('runtime' => time());
		$cache->set(CACHE_RUNTIME, $runtime);
		$stats = $cache->get(CACHE_STATS);
		$stats['lastcommit']['btc'] = array();
		$cache->set(CACHE_STATS, $stats);
		//startup btc background process
		$re = Miner::startupBtcProc($config['btc_url'], $config['btc_worker'], $config['btc_pass'], $config['freq']);
		if($re === false) {
			writeLog("BTC process fails to start");
			//reset usb
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
	
	foreach($unusedDevids as $devid)
	{
		$worker = !empty($unusedWorkers) ? array_shift($unusedWorkers) : $workers[0];
		$pid = Miner::startupLtcProc(
			$devid,
			$config['ltc_url'],
			$worker,
			$config['ltc_pass'],
			$config['freq'],
			false
		);
		writeLog("LTC process startup: DeviceID={$devid} Pid={$pid} Worker={$worker} Frequency={$config['freq']}");
	}
}

$freezeTime = 450;
$timeNow = time();
$runtime = $cache->get(CACHE_RUNTIME);
if($runtime === false)
{
	$runtime = array('runtime' => $timeNow);
	$cache->set(CACHE_RUNTIME, $runtime);
}
if(($runtime['runtime'] + $freezeTime) > $timeNow)
{
	return;
}
$stats = $cache->get(CACHE_STATS);
$diedDevids = array();
foreach($devices['devids'] as $devid)
{
	if(!isset($stats['lastcommit']['ltc'][$devid]))
	{
		writeLog("Machine never commited DeviceID={$proc['devid']} Pid={$pid} Worker={$proc['worker']}");
		continue;
	}
	if(($stats['lastcommit']['ltc'][$devid] + $freezeTime) < $timeNow)
	{
		foreach($process['ltc'] as $pid => $proc)
		{
			$currentDev = $proc['devid'];
			if($currentDev == $devid)
			{
				Miner::shutdownLtcProc($pid);
				writeLog("Last commit took longer than 7,5 mins, restarting machine DeviceID={$proc['devid']} Pid={$pid} Worker={$proc['worker']}");
				unset($process['ltc'][$pid]);
			}
		}
		continue;
	}
}

