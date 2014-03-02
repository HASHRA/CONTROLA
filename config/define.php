<?php

error_reporting(E_ALL ^ E_STRICT);

function writeLog($msg)
{
	$msg = sprintf("[%s] %s\r\n", date('Y-m-d H:i:s'), $msg);
	$fp = fopen(FILE_LOG, 'a');
	fwrite($fp, $msg);
	fclose($fp);
}

//输出调试信息
//define('DEBUG', true);
define('DEBUG', false);

//目录
define('PATH_ROOT', dirname(dirname(__FILE__)));
define('PATH_CONFIG', PATH_ROOT.'/config');
define('PATH_CONTROL', PATH_ROOT.'/controller');
define('PATH_VIEW', PATH_ROOT.'/view');
define('PATH_CLASS', PATH_ROOT.'/class');
define('PATH_CACHE', PATH_ROOT.'/cache');

//tmp路径
define('PATH_TEMP', '/tmp');

//临时文件路径
define('PATH_LOG', '/tmp/log');

//用户配置文件路径
define('FILE_CONFIG', PATH_CONFIG.'/config.ini');

//监控日志路径
define('FILE_LOG', PATH_LOG.'/monitor.log');

//运行模式
define('RUN_MODEL_BTC', 0x01);
define('RUN_MODEL_LTC', 0x02);

//程序路径
define('BIN_BTC', '/www/soft/cgminer');
define('BIN_LTC', '/www/soft/minerd');

//缓存
define('CACHE_CFGMTIME', 'cfgmtime');
define('CACHE_PROCESS', 'process');
define('CACHE_DEVICE', 'device');
define('CACHE_STATS', 'stats');
define('CACHE_STATSUI', 'statsui');
define('CACHE_RUNTIME', 'runtime');

//错误代码
define('ERRNO_SUCC',	'A0000');	//成功
define('ERRNO_MODEL',	'A0001');	//模式输入错误
define('ERRNO_FREQ',	'A0002');	//运行频率输入错误
define('ERRNO_BTC_URL',	'A0003');	//BTC url输入错误
define('ERRNO_BTC_USER','A0004');	//BTC 用户名输入错误
define('ERRNO_BTC_PASS','A0005');	//BTC 密码输入错误
define('ERRNO_LTC_URL',	'A0006');	//LTC url输入错误
define('ERRNO_LTC_USER','A0007');	//LTC 用户名输入错误
define('ERRNO_LTC_PASS','A0008');	//LTC 密码输入错
