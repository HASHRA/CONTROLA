<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';

$cache = new Cache(PATH_CACHE);
 

$devices = $cache->get(CACHE_DEVICE);


$success = false;   

$iniArr = parse_ini_file(FILE_CONFIG);
 
$runtime = $cache->get(CACHE_RUNTIME);
$uptime = time() - $runtime["runtime"];
$li = '';
$offline = 0;
$totalhash = 0;
$totalhashbtc = 0;
$info = "";
$table = 'No devices found';
if(!empty($devices))
{
	
	$table = "";
	$tablebtc = "";
	
	$statsui = Miner::getCGMinerStats();
	foreach($statsui as $stat)
	{
		$totalhash += $stat["hashrate"];
	}
	$counter = 0;
	$runType = "ltc";
	if($iniArr["model"] == 1 || $iniArr["model"] == 3)
	{
		$runType = "btc";
	}
	foreach($devices["devids"] as $devid)
	{
		if(isset($statsui[$devid]))
		{
			$hash = isset($statsui[$devid]["hashrate"]) ? $statsui[$devid]["hashrate"] : 0;
			$valids = isset($statsui[$devid]["valid"]) ? $statsui[$devid]["valid"] : 0;
			$invalids = isset($statsui[$devid]["invalid"]) ? $statsui[$devid]["invalid"] : 0;
			$totals = $valids + $invalids;
			$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
			$comma = ($counter == 0)? '':',';
			$table .= $comma.'{"dev" : "'.$runType.'_'.$devid.'" , "hash" : "'.$hash.'", "valids" : "'.$valids.'" , "totals" : "'.$totals.'", "rejectrate" : "'.$rejrate.'"}';
			$counter++;
		}
		
	}
	
	if(count($devices) == $offline)
	{
		$uptime = 0;
	}
	
}

$runmode = "IDLE";

if ($iniArr["model"] == 1) {$runmode = 'BTC';}
if ($iniArr["model"] == 2) {$runmode = 'LTC';}
if ($iniArr["model"] == 3) {$runmode = 'DUAL';}

$syslog = file_exists(PATH_LOG."/monitor.log") ? file_get_contents(PATH_LOG."/monitor.log") : '';
?>

{
	"LTCdevices" : [<?php if($runmode === 'LTC') echo $table;?>],
	"BTCDevices" : [<?php if ($runmode === 'DUAL' || $runmode === 'BTC') echo $table?>]
}
