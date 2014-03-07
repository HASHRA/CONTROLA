<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';

$cache = new Cache(PATH_CACHE);
 
$iniArr = parse_ini_file(FILE_CONFIG);
if($iniArr["model"] == 2)
{
	$devices = Miner::getAvailableDevice();
}
else if($iniArr["model"] == 3)
{
	$devices = Miner::getUsbBus();
}

$success = false;   
 
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
	
	$procs = Miner::getRunningLtcProcess();
	$devproc = array();
	foreach($procs as $proc)
	{
		$devproc[$proc["devid"]] = $proc["worker"];
	}
	$btcstatsui = Miner::getBtcStatsUI();
	$statsui = Miner::getLtcStatsUI();
	foreach($statsui as $stat)
	{
		$totalhash += $stat["hashrate"];
	}
	foreach($btcstatsui as $stat)
	{
		$totalhashbtc += $stat["hashrate"];
	}
	$counter = 0;
	foreach($devices as $devid)
	{
		
		$tabid = str_replace(":", "-", $devid);
		$hash = isset($statsui[$devid]["hashrate"]) ? $statsui[$devid]["hashrate"] : 0;
		$valids = isset($statsui[$devid]["valid"]) ? $statsui[$devid]["valid"] : 0;
		$invalids = isset($statsui[$devid]["invalid"]) ? $statsui[$devid]["invalid"] : 0;
		$totals = $valids + $invalids;
		$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
		$comma = ($counter == 0)? '':',';
		$table .= $comma.'{"dev" : "ltc_'.$tabid.'" , "hash" : "'.$hash.'", "valids" : "'.$valids.'" , "totals" : "'.$totals.'", "rejectrate" : "'.$rejrate.'"}';
		$counter++;
	}

	if(count($devices) == $offline)
	{
		$uptime = 0;
	}
}
if($iniArr["model"] == 1 || $iniArr["model"] == 3)
{
	
	
	//btc part
	$counter = 0;
	foreach($devices as $devid)
	{
	
		$hash = isset($btcstatsui[$devid]["hashrate"]) ? $btcstatsui[$devid]["hashrate"] : 0;
		$valids = isset($btcstatsui[$devid]["valid"]) ? $btcstatsui[$devid]["valid"] : 0;
		$invalids = isset($btcstatsui[$devid]["invalid"]) ? $btcstatsui[$devid]["invalid"] : 0;
		$totals = $valids + $invalids;
		$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
		$comma = ($counter == 0)? '':',';
		$tablebtc .= $comma.'{"dev" : "btc_'.$tabid.'" , "hash" : "'.$hash.'", "valids" : "'.$valids.'" , "totals" : "'.$totals.'", "rejectrate" : "'.$rejrate.'"}';
		$counter++;
	}
	
	

}

$runmode = "IDLE";

if ($iniArr["model"] == 1) {$runmode = 'BTC';}
if ($iniArr["model"] == 2) {$runmode = 'LTC';}
if ($iniArr["model"] == 3) {$runmode = 'DUAL';}

$syslog = file_exists(PATH_LOG."/monitor.log") ? file_get_contents(PATH_LOG."/monitor.log") : '';
?>

{
	"LTCdevices" : [<?php echo $table?>],
	"BTCDevices" : [<?php echo $tablebtc?>]
}
