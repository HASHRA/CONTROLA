<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';

$cache = new Cache(PATH_CACHE);

function formatTime($input)
{
	$hrs = ($input > 3600 ? floor($input / 3600) . ':' : '');
	$mins = ($hrs && $input % 3600 < 600 ? '0' : '') . floor($input % 3600 / 60) . ':';
	$secs = ($input % 60 < 10 ? '0' : '') . $input % 60;
	return $hrs.$mins.$secs;
}
 
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
 
if($_POST)
{
	$valid = true;
    $ltc_url = preg_replace('/\s+/', '', $_POST["ltc_url"]);
    $ltc_workers = preg_replace('/\s+/', '', $_POST["ltc_workers"]);
    $ltc_pass = preg_replace('/\s+/', '', $_POST["ltc_pass"]);
    $ltc_enable = isset($_POST["ltc_enable"]);
    if(empty($ltc_url) || empty($ltc_workers) || empty($ltc_pass))
    {
        $valid = false;
    }
    
    $btc_url = preg_replace('/\s+/', '', $_POST["btc_url"]);
    $btc_worker = preg_replace('/\s+/', '', $_POST["btc_worker"]);
    $btc_pass = preg_replace('/\s+/', '', $_POST["btc_pass"]);
    $btc_enable = isset($_POST["btc_enable"]);
    if(empty($btc_url) || empty($btc_worker) || empty($btc_pass))
    {
        $valid = false;
    }
    
    if(!$ltc_enable && !$btc_enable)
    {
        $model = 0;
        $runmode = "IDLE";
    }
    else if(!$ltc_enable && $btc_enable)
    {
        $model = 1;
    }
    else if($ltc_enable && !$btc_enable)
    {
        $model = 2;
    }
    else if($ltc_enable && $btc_enable)
    {
        $model = 3;
    }
    
    $freq = (int) $_POST["freq"];
    $freq = $freq - $freq % 25;
    if($freq < 600 || $freq > 900)
    {
        $freq = 600;
    }
    
    if( $valid &&
		$ltc_url != $iniArr["ltc_url"] || 
        $ltc_workers != $iniArr["ltc_workers"] ||
        $ltc_pass != $iniArr["ltc_pass"] ||
        $btc_url != $iniArr["btc_url"] ||
        $btc_worker != $iniArr["btc_worker"] ||
        $btc_pass != $iniArr["btc_pass"] ||
        $freq != $iniArr["freq"] ||
        $model != $iniArr["model"]
        )
    {
        $iniStr = "[config]\n";
        $iniStr .= "model = {$model}\n";
        $iniStr .= "freq = {$freq}\n";
        $iniStr .= "btc_url = \"{$btc_url}\"\n";
        $iniStr .= "btc_worker = \"{$btc_worker}\"\n";
        $iniStr .= "btc_pass = \"{$btc_pass}\"\n";
        $iniStr .= "ltc_url = \"{$ltc_url}\"\n";
        $iniStr .= "ltc_workers = \"{$ltc_workers}\"\n";
        $iniStr .= "ltc_pass = \"{$ltc_pass}\"\n";
        
        $outfile = fopen(FILE_CONFIG,"w");
        fwrite($outfile, $iniStr);
        fclose($outfile);
		
		if(($iniArr["model"] == 1 || $iniArr["model"] == 3) && $model == 2)
		{
			exec("sleep 1 && reboot");
			header('Location: /?i=1');
			exit;
		}
		else
		{
			require_once 'system/monitor.php';
			header('Location: /?i=2');
			exit;
		}
    }
}
else
{
    $ltc_url = $iniArr["ltc_url"];
    $ltc_workers = $iniArr["ltc_workers"];
    $ltc_pass = $iniArr["ltc_pass"];
    $ltc_enable = dechex($iniArr["model"]) & 0x2;
    $btc_url = $iniArr["btc_url"];
    $btc_worker = $iniArr["btc_worker"];
    $btc_pass = $iniArr["btc_pass"];
    $btc_enable = dechex($iniArr["model"]) & 0x1;
    $freq = $iniArr["freq"];
}

$runtime = $cache->get(CACHE_RUNTIME);
$uptime = time() - $runtime["runtime"];
$li = '';
$offline = 0;
$totalhash = 0;
$info = "";
if(!empty($devices))
{
	foreach($devices as $devid)
	{
		$li .= '<li>
			<a href="log.php#LTC'.$devid.'" target="_blank">
				<i class="icon-double-angle-right"></i>
				LTC Miner '.$devid.'
			</a>
		</li>';
	}

	$procs = Miner::getRunningLtcProcess();
	$devproc = array();
	foreach($procs as $proc)
	{
		$devproc[$proc["devid"]] = $proc["worker"];
	}
	$table = '';
	$statsui = Miner::getLtcStatsUI();
	foreach($statsui as $stat)
	{
		$totalhash += $stat["hashrate"];
	}
	foreach($devices as $devid)
	{
		if(isset($devproc[$devid]))
		{
			$hash = isset($statsui[$devid]["hashrate"]) ? $statsui[$devid]["hashrate"] : 0;
			$valids = isset($statsui[$devid]["valid"]) ? $statsui[$devid]["valid"] : 0;
			$invalids = isset($statsui[$devid]["invalid"]) ? $statsui[$devid]["invalid"] : 0;
			$totals = $valids + $invalids;
			$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
			
			$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="miner1" class="pie-chart" data-percent="'.(($hash/500) * 100).'" data-bar-color="#F94743"><span><b class="value"> '.$hash.' </b> Kh/s</span></div><div>LTC Miner '.$devid.' </div> <a href="log.php#LTC'.$devid.'"> Mining...'.$valids.'/'.$totals.' ('.$rejrate.'%)</a></div>';
			
			//$table .= '<tr><td>LTC Miner '.$devid.' ('.$devproc[$devid].')</td><td class="hidden-480"><span class="label label-info arrowed-right arrowed-in">Running</span></td><td>'.$hash.'</td><td>'.$valids.'/'.$totals.' ('.$rejrate.'%)</td></tr>';
		}
		else
		{
			$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="miner1" class="pie-chart" data-percent="0" data-bar-color="#F94743"><span><b class="value"> 0 </b> Kh/s</span></div><div>LTC Miner '.$devid.' </div> <a href="log.php#LTC'.$devid.'"> Offline :(</a></div>';
			//$table .= '<tr><td>LTC Miner '.$devid.'</td><td class="hidden-480"><span class="label label-danger arrowed">Offline</span></td><td>0</td><td>0/0</td></tr>';
			$offline++;
		}
	}
	if(count($devices) == $offline)
	{
		$uptime = 0;
	}
}
$uptime = formatTime($uptime);
if($iniArr["model"] == 1 || $iniArr["model"] == 3)
{
	$li .= '<li>
		<a href="log.php#BTC" target="_blank">
			<i class="icon-double-angle-right"></i>
			BTC Miner
		</a>
	</li>';
	$procs = Miner::getRunningBtcProcess();
	if(!empty($procs))
	{
		$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div class="pie-chart" data-percent="100" data-bar-color="#1F8A70"><span>SHA256 miner</span></div><a href="#" class="pie-title">Mining...</a></div>';
		
		//$table .= '<tr><td>BTC Miner ('.$iniArr["btc_worker"].')</td><td class="hidden-480"><span class="label label-info arrowed-right arrowed-in">Running</span></td><td>N/A</td><td>N/A</td></tr>';
	}
	else
	{
		$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div class="pie-chart" data-percent="0" data-bar-color="#1F8A70"><span>SHA256 miner</span></div><a href="#" class="pie-title">Offline :(</a></div>';
		//$table .= '<tr><td>BTC Miner ('.$iniArr["btc_worker"].')</td><td class="hidden-480"><span class="label label-danger arrowed">Offline</span></td><td>N/A</td><td>N/A</td></tr>';
	}
}

$runmode = "IDLE";

if ($iniArr["model"] == 1) {$runmode = 'BTC';}
if ($iniArr["model"] == 2) {$runmode = 'LTC';}
if ($iniArr["model"] == 3) {$runmode = 'DUAL';}

$syslog = file_exists(PATH_LOG."/monitor.log") ? file_get_contents(PATH_LOG."/monitor.log") : '';

if(isset($_GET["i"]))
{
	if($_GET["i"] == 1)
	{
		$info = "Successfully saved configuration, system will reboot...";
		$success = true;
	}
	else if($_GET["i"] == 2)
	{
		$info = "Successfully saved configuration, miners will restart...";
		$success = true;
	}
}

?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php';?>
    
    <body class="cover">

        <div class="wrapper">

           <?php include 'includes/banner.php';?>

            <!-- BODY -->
            <div class="body">

                <?php include 'includes/menu.php';?>

                <section class="content">
                    
<ol class="breadcrumb">
    <li class="active"><i class="fa fa-home fa-fw"></i> Home</li>
</ol>

<div class="header">
    <div class="col-md-12">
        <h3 class="header-title">CONTROLA</h3>
        <p class="header-info">Running in <b class="value"> <?php echo $runmode?> </b> mode</p>
    </div>
</div>

<!-- CONTENT -->
<div class="main-content">

	 <div class="row">
        <div class="col-md-6">
            <div class="panel ">
                <div class="panel-heading">
                    <h3 class="panel-title">SCRYPT Miners hashrate <b class="value"><?php echo $totalhash ?></b> Kh/s</h3>
                </div>
                <div class="panel-body">
                	
                	<?php echo $table ?>
                	
                    <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="0" data-bar-color="#F94743"><span><b class="value"> 0 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                    
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div><a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                     <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div id="miner1" class="pie-chart" data-percent="80" data-bar-color="#F94743"><span><b class="value"> 350 </b> Kh/s</span></div>
                        <div>LTC miner 1:1</div> <a href="#" class="pie-title">Mining...(10/10) 0%</a>
                    </div>
                    
                    <div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box">
                        <div class="pie-chart" data-percent="80" data-bar-color="#1F8A70"><span>SHA256  miner</span></div>
                        <a href="#" class="pie-title">Mining...</a>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col-md-6">
            <div class="panel ">
                <form role="form" action="." method="post" id="config_form">
                <div class="panel-heading">
                	 <h4 class="panel-title">SCRYPT pool configuration</h4>
                </div>
                <div class="panel-body">
		                <div class="form-group">
		                    <label for="ltc_url">Scrypt Pool address</label>
		                    <input class="form-control" id="ltc_url" name="ltc_url" placeholder="stratum+tcp://..." value="<?php echo $ltc_url?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter the pool URL here">
		                </div>
		                <div class="form-group">
		                    <label for="ltc_workers">Scrypt worker name</label>
		                    <input class="form-control" id="ltc_workers" name="ltc_workers" value="<?php echo $ltc_workers?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter Worker name here. Some pools uses your BTC address. This field is a comma delimited list! Ideally one should assign each miner a different worker name.">
		                </div> 
		                <div class="form-group">
		                    <label for="ltc_pass">Scrypt worker password</label>
		                    <input class="form-control" id="ltc_pass" name="ltc_pass" value="<?php echo $ltc_pass?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Worker password. Usually ignored by pools">
		                </div>
		                <div class="form-group">
		                	<label for="freq">Core clock speed (Mhz)</label>
		                	<select class="form-control" id="freq" name="freq" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Clock speed of your gridseed chips. Adjust at your own risk!">
		                        <option value="600" <?php $tbool = $freq == 600 ? 'selected="selected"' : ''; echo $tbool; ?> >600</option>
								<option value="650" <?php $tbool = $freq == 650 ? 'selected="selected"' : ''; echo $tbool; ?> >650</option>
								<option value="700" <?php $tbool = $freq == 700 ? 'selected="selected"' : ''; echo $tbool; ?> >700</option>
								<option value="750" <?php $tbool = $freq == 750 ? 'selected="selected"' : ''; echo $tbool; ?> >750</option>
								<option value="800" <?php $tbool = $freq == 800 ? 'selected="selected"' : ''; echo $tbool; ?> >800</option>
								<option value="850" <?php $tbool = $freq == 850 ? 'selected="selected"' : ''; echo $tbool; ?> >850</option>
								<option value="900" <?php $tbool = $freq == 900 ? 'selected="selected"' : ''; echo $tbool; ?> >900</option>
		                     </select>
		                </div>
		                <div class="form-group">
		                	<div class="checkbox">
		                        <label>
		                            <input type="checkbox" name="ltc_enable" <?php $tmpstring = $ltc_enable ? 'checked' : ''; echo $tmpstring; ?> >
		                           	Enable
		                        </label>
		                    </div>
		                </div>
                </div>
                
                 <div class="panel-heading">
                	 <h4 class="panel-title">SHA256 pool configuration</h4>
                </div>
                <div class="panel-body">
		                <div class="form-group">
		                    <label for="btc_url">SHA256 Pool address</label>
		                    <input class="form-control" id="btc_url" name="btc_url" placeholder="stratum+tcp://..." value="<?php echo $btc_url?>"  data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter the pool URL here">
		                </div>
		                <div class="form-group">
		                    <label for="btc_worker">SHA256 worker name</label>
		                    <input class="form-control" id="btc_worker" name="btc_worker" value="<?php echo $btc_worker?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter Worker name here. Some pools uses your BTC address. This field is a comma delimited list! Ideally one should assign each miner a different worker name.">
		                </div> 
		                <div class="form-group">
		                    <label for="btc_pass">SHA256 worker password</label>
		                    <input class="form-control" id="btc_pass" name="btc_pass" value="<?php echo $btc_pass?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Worker password. Usually ignored by pools">
		                </div>
		                <div class="form-group">
		                	<div class="checkbox">
		                        <label>
		                            <input type="checkbox" name="btc_enable" <?php $tmpstring = $btc_enable ? 'checked' : ''; echo $tmpstring; ?> >
		                           	Enable
		                        </label>
		                    </div>
		                </div>
		                <button type="submit" class="btn btn-primary">Save and restart</button>
                </div>
                </form>
                </div>
            </div>
        </div>
        <div class="row">
         <div class="col-md-6">
            <div class="panel ">
            	<div class="panel-heading">
                	 <h4 class="panel-title">System logs:</h4>
                </div>
            	<div class="panel-body">
		        	<div class="form-group">
		                    <label>Log</label>
		                    <textarea class="form-control" rows="4"><?php echo $syslog ?>
		                    </textarea>
		            </div>
	            </div>
	        </div>
	            
        	
        </div>
    </div>

</div>
<!-- END: CONTENT -->
                </section>
            </div>
            <!-- END: BODY -->
        </div>

       <?php include 'includes/footer.php';?>
    </body>
</html>