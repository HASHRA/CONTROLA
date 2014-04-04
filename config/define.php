<?php

error_reporting(E_ALL ^ E_STRICT);

define('VERSION' , '1.3.1');


//define('DEBUG', true);
define('DEBUG', false);

//ç›®å½•
define('PATH_ROOT', dirname(dirname(__FILE__)));
define('PATH_CONFIG', PATH_ROOT.'/config');
define('PATH_CONTROL', PATH_ROOT.'/controller');
define('PATH_VIEW', PATH_ROOT.'/view');
define('PATH_CLASS', PATH_ROOT.'/class');
define('PATH_CACHE', PATH_ROOT.'/cache');


define('PATH_TEMP', '/tmp');


define('PATH_LOG', '/var/log');


define('FILE_CONFIG', PATH_CONFIG.'/config.ini');

define('FILE_LOG', PATH_LOG.'/monitor.log');

define('RUN_MODEL_BTC', 0x01);
define('RUN_MODEL_LTC', 0x02);


define('BIN_BTC', '/var/www/soft/sha/cgminer');
define('BIN_LTC', '/var/www/soft/bfg/bfgminer');
define('BIN_CPUMINER', '/var/www/soft/minerd');


define('CACHE_CFGMTIME', 'cfgmtime');
define('CACHE_PROCESS', 'process');
define('CACHE_DEVICE', 'device');
define('CACHE_STATS', 'stats');
define('CACHE_STATSUI', 'statsui');
define('CACHE_RUNTIME', 'runtime');


define('ERRNO_SUCC',	'A0000');	
define('ERRNO_MODEL',	'A0001');	
define('ERRNO_FREQ',	'A0002');	
define('ERRNO_BTC_URL',	'A0003');	
define('ERRNO_BTC_USER','A0004');	
define('ERRNO_BTC_PASS','A0005');	//BTC å¯†ç �è¾“å…¥é”™è¯¯
define('ERRNO_LTC_URL',	'A0006');	//LTC urlè¾“å…¥é”™è¯¯
define('ERRNO_LTC_USER','A0007');	//LTC ç”¨æˆ·å��è¾“å…¥é”™è¯¯
define('ERRNO_LTC_PASS','A0008');	//LTC å¯†ç �è¾“å…¥é”™

function writeLog($msg)
{
	$msg = sprintf("[%s] %s\r\n", date('Y-m-d H:i:s'), $msg);
	$fp = fopen(FILE_LOG, 'a');
	fwrite($fp, $msg);
	fclose($fp);
}

function require_with($pg, $vars)
{
	extract($vars);
	require $pg;
}

/**
 * Determine if a variable is iterable. i.e. can be used to loop over.
 *
 * @return bool
 */
function is_iterable($var)
{
	return $var !== null
	&& (is_array($var)
			|| $var instanceof Traversable
	);
}