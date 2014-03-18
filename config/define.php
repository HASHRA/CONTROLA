<?php

error_reporting(E_ALL ^ E_STRICT);

define('VERSION' , '1.1.1');

//è¾“å‡ºè°ƒè¯•ä¿¡æ�¯
//define('DEBUG', true);
define('DEBUG', false);

//ç›®å½•
define('PATH_ROOT', dirname(dirname(__FILE__)));
define('PATH_CONFIG', PATH_ROOT.'/config');
define('PATH_CONTROL', PATH_ROOT.'/controller');
define('PATH_VIEW', PATH_ROOT.'/view');
define('PATH_CLASS', PATH_ROOT.'/class');
define('PATH_CACHE', PATH_ROOT.'/cache');

//tmpè·¯å¾„
define('PATH_TEMP', '/tmp');

//ä¸´æ—¶æ–‡ä»¶è·¯å¾„
define('PATH_LOG', '/var/log');

//ç”¨æˆ·é…�ç½®æ–‡ä»¶è·¯å¾„
define('FILE_CONFIG', PATH_CONFIG.'/config.ini');

//ç›‘æŽ§æ—¥å¿—è·¯å¾„
define('FILE_LOG', PATH_LOG.'/monitor.log');

//è¿�è¡Œæ¨¡å¼�
define('RUN_MODEL_BTC', 0x01);
define('RUN_MODEL_LTC', 0x02);

//ç¨‹åº�è·¯å¾„
define('BIN_BTC', '/var/www/soft/sha/cgminer');
define('BIN_LTC', '/var/www/soft/scrypt/cgminer');
define('BIN_CPUMINER', '/var/www/soft/minerd');

//ç¼“å­˜
define('CACHE_CFGMTIME', 'cfgmtime');
define('CACHE_PROCESS', 'process');
define('CACHE_DEVICE', 'device');
define('CACHE_STATS', 'stats');
define('CACHE_STATSUI', 'statsui');
define('CACHE_RUNTIME', 'runtime');

//é”™è¯¯ä»£ç �
define('ERRNO_SUCC',	'A0000');	//æˆ�åŠŸ
define('ERRNO_MODEL',	'A0001');	//æ¨¡å¼�è¾“å…¥é”™è¯¯
define('ERRNO_FREQ',	'A0002');	//è¿�è¡Œé¢‘çŽ‡è¾“å…¥é”™è¯¯
define('ERRNO_BTC_URL',	'A0003');	//BTC urlè¾“å…¥é”™è¯¯
define('ERRNO_BTC_USER','A0004');	//BTC ç”¨æˆ·å��è¾“å…¥é”™è¯¯
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