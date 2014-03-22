<?php
require_once '/var/www/config/define.php';
require_once PATH_CLASS . '/miner.class.php';
require_once PATH_CLASS . '/cache.class.php';


define('DEF_MONITOR', 1);
$cache = new Cache(PATH_CACHE);
if(isset($_SERVER['argv'])){
	array_shift($_SERVER['argv']);
	if(!empty($_SERVER['argv']))
	{
		foreach($_SERVER['argv'] as $arg)
		{
			list($key, $value) = explode("=", $arg);
			if($key == "c")
			{
				if($value == 0)
				{
					$cache->set(CACHE_RUNTIME, false);
					$cache->set(CACHE_STATS, false);
					$cache->set(CACHE_STATSUI, false);
					//writeLog("Initializing... please wait");
					//return;
				}
			}
		}
	}	
}

if(!file_exists(FILE_CONFIG))
{
	Miner::shutdownBtcProc();
	Miner::shutdownLtcProc();
	return;
}

$config = parse_ini_file(FILE_CONFIG);

$arr = $cache->get(CACHE_CFGMTIME);

$mtime = filemtime(FILE_CONFIG);

if($arr === false)
{
	$arr['mtime'] = $mtime;
	$cache->set(CACHE_CFGMTIME, $arr);
}
	
$path = "/var/www/soft/";
$ls = exec("ls -1 {$path} | grep -v \\\.", $files);
if($ls && !empty($files))
{
	foreach($files as $file)
	{
		$perm = substr(sprintf("%o", fileperms($path.$file)), -3);
		if($perm != "755")
		{
			$cm = chmod($path, 0755);
			if($cm)
				writeLog("Chmod 0755 '".$path.$file."'");
			else
				writeLog("Failed to Chmod 0755 '".$path.$file."'");
		}
	}
}
$pathBfg = '/var/www/soft/bfg';
if (! file_exists($pathBfg)){
	//install bfg
	exec('sudo mkdir '.$pathBfg.' ; tar -xvf '.$path.'bfg-binary.tar -C '.$path);
} 

if($arr['mtime'] != $mtime)
{
	$arr['mtime'] = $mtime;
	$cache->set(CACHE_CFGMTIME, $arr);
	writeLog("Configuration file is modified");
	syslog(LOG_INFO, "Configuration file modified, shutting down");
	Miner::shutdownBtcProc();
	Miner::shutdownLtcProc();
	writeLog("All process shutdown - monitor (1)");
	sleep(2);
}

$process = $cache->get(CACHE_PROCESS);

$devices = $cache->get(CACHE_DEVICE);


if(empty($devices['bus']))
{
	writeLog("USB bus not found, try to open the USB controller power");
}

$GLOBALS['config']	= $config;
$GLOBALS['cache']	= $cache;
$GLOBALS['devices']	= $devices;
$GLOBALS['process']	= $process;

if(($config['model'] & RUN_MODEL_BTC) && ($config['model'] & RUN_MODEL_LTC))
{
	require_once 'monitor/dual.php';
}
elseif($config['model'] & RUN_MODEL_BTC)
{
	require_once 'monitor/single_btc.php';
}
elseif($config['model'] & RUN_MODEL_LTC)
{
	require_once 'monitor/single_ltc.php';
}

