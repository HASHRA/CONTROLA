<?php

error_reporting(E_ALL ^ E_STRICT);


//define('DEBUG', true);
define('DEBUG', false);

//ç›®å½•
define('PATH_ROOT', dirname(dirname(__FILE__)));

define('VERSION' , file_get_contents(PATH_ROOT.'/version'));

define('PATH_CONFIG', PATH_ROOT.'/config');
define('PATH_CONTROL', PATH_ROOT.'/controller');
define('PATH_VIEW', PATH_ROOT.'/view');
define('PATH_CLASS', PATH_ROOT.'/class');
define('PATH_CACHE', PATH_ROOT.'/cache');


define('PATH_TEMP', '/tmp');


define('PATH_LOG', '/var/log');

define('FILE_CONFIG', PATH_CONFIG.'/config.ini');
define('FILE_SYSTEM_SETTNGS', PATH_CONFIG.'/systemsettings.json');
define('FILE_POOLSETTINGS', PATH_CONFIG.'/poolsettings.json');
define('FILE_USERS', PATH_CONFIG.'/users.json');

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

define('FREQ_TABLE', array(600, 650,
    700, 706, 713, 719, 725, 731, 738, 744,
    750, 756, 763, 769, 775, 781, 788, 794,
    800, 813, 825, 838, 850, 863, 875, 888,
    900, 913, 925, 938, 950, 963, 975, 988,
    1000, 1013, 1025, 1038, 1050, 1063, 1075, 1088,
    1100, 1113, 1125, 1138, 1150, 1163, 1175, 1188,
    1200, 1213, 1225, 1238, 1250, 1263, 1275, 1288,
    1300, 1313, 1325, 1338, 1350, 1363, 1375, 1388,
    1400));

//-------------------product settings
define('SCRYPT' , 1);
define('SHA' , 2);

define('BY_CORE', 1);
define('BY_DIFF1', 2);

define('KHS', 1);
define('MHS', 2);
define('GHS' , 3);


define('SUPPORTS' , SCRYPT | SHA);
define('PRODUCT_NAME' , 'MINI CONTROLA');
define('CALCULATE_HASHRATE_SCRYPT', BY_DIFF1);
define('CALCULATE_HASHRATE_SHA' , BY_DIFF1);
define('SCRYPT_UNIT', KHS);
define('SHA_UNIT', GHS);
define('MINER_NAME' , 'MINI');
define ('MINER_MAX_HASHRATE' , 500);
define('CHIP_AMOUNT' , 5);
define('DUAL_SUPPORT', supportedAlgo(SCRYPT) && supportedAlgo(SHA));

define('DEFAULT_UPDATE_URL' , 'https://hashracustomer:hashra1@bitbucket.org/purplefox/hashra-firmware.git');

//-----------------end product settings
/**
 * @param int $algoMask
 * @return boolean
 */
function supportedAlgo($algoMask) {
	return (SUPPORTS & $algoMask);
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