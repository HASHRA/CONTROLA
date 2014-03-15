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

$runmode = "IDLE";


if ($iniArr["model"] == 1) {$runmode = 'SHA256';}
if ($iniArr["model"] == 2) {$runmode = 'SCRYPT';}
if ($iniArr["model"] == 3) {$runmode = 'DUAL';}

$devices = $cache->get(CACHE_DEVICE);

$success = false;   
 
if($_POST)
{
	
	$valid = true;
    $ltc_url = preg_replace('/\s+/', '', $_POST["ltc_url"]);
    $ltc_worker = preg_replace('/\s+/', '', $_POST["ltc_worker"]);
    $ltc_pass = preg_replace('/\s+/', '', $_POST["ltc_pass"]);
    $ltc_enable = isset($_POST["ltc_enable"]);
    if(empty($ltc_url) || empty($ltc_worker) || empty($ltc_pass))
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
        $ltc_worker != $iniArr["ltc_worker"] ||
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
        $iniStr .= "ltc_worker = \"{$ltc_worker}\"\n";
        $iniStr .= "ltc_pass = \"{$ltc_pass}\"\n";
        
        $outfile = fopen(FILE_CONFIG,"w");
        fwrite($outfile, $iniStr);
        fclose($outfile);
		
        syslog(LOG_INFO, " selected mode is " .$iniArr["model"]);
		if(($model == 1 || $model == 3) && $model == 2)
		{
			exec("sudo reboot &");
			header('Location: /?i=1');
			exit;
		}
		else
		{
			exec('wget http://localhost/system/monitor.php > /dev/null');
			header('Location: /?i=2');
			exit;
		}
    }
}
else
{
    $ltc_url = $iniArr["ltc_url"];
    $ltc_worker = $iniArr["ltc_worker"];
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
$totalhashbtc = 0;
$info = "";
$table = 'No devices found';
if(!empty($devices))
{
	$table = "";
	$tablebtc = "";

	$statsui = Miner::getCGMinerStats();
	foreach($statsui["devices"] as $stat)
	{
		$totalhash += $stat["hashrate"];
	}
	$color = $runmode === 'SHA256' || $runmode === 'DUAL' ? '1F8A70' : 'F94743';
	foreach($devices["devids"] as $devid)
	{
		if(isset($statsui[$devid]))
		{
			$hash = isset($statsui[$devid]["hashrate"]) ? $statsui[$devid]["hashrate"] : 0;
			$valids = isset($statsui[$devid]["valid"]) ? $statsui[$devid]["valid"] : 0;
			$invalids = isset($statsui[$devid]["invalid"]) ? $statsui[$devid]["invalid"] : 0;
			$totals = $valids + $invalids;
			$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
			$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="ltc_'.$devid.'" class="pie-chart" data-percent="'.(($hash/500) * 100).'" data-bar-color="#'.$color.'"><span><b class="value"> '.$hash.' </b> Kh/s</span></div><div>Scrypt Miner '.($devid + 1).' </div> <a class="minerLink" href="#LTC'.$devid.'"> Mining...'.$valids.'/'.$totals.' ('.$rejrate.'%)</a></div>';
		}
		else
		{
			$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="ltc_'.$devid.'" class="pie-chart" data-percent="0" data-bar-color="#'.$color.'"><span><b class="value"> 0 </b> Kh/s</span></div><div>Scrypt Miner '.($devid + 1).' </div> <a class="minerLink" href="#'.$devid.'"> Offline :(</a></div>';
			//$table .= '<tr><td>LTC Miner '.$devid.'</td><td class="hidden-480"><span class="label label-danger arrowed">Offline</span></td><td>0</td><td>0/0</td></tr>';
			$offline++;
		}
	}

	if(count($devices) == $offline)
	{
		$uptime = 0;
	}
}
// $uptime = formatTime($uptime);
// if($iniArr["model"] == 1 || $iniArr["model"] == 3)
// {
	
	
// 	//btc part
	
// 	foreach($devices as $devid)
// 	{
	
// 		$hash = isset($btcstatsui[$devid]["hashrate"]) ? $btcstatsui[$devid]["hashrate"] : 0;
// 		$valids = isset($btcstatsui[$devid]["valid"]) ? $btcstatsui[$devid]["valid"] : 0;
// 		$invalids = isset($btcstatsui[$devid]["invalid"]) ? $btcstatsui[$devid]["invalid"] : 0;
// 		$totals = $valids + $invalids;
// 		$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
	
// 		$tablebtc .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="btc_'.$devid.'" class="pie-chart" data-percent="'.(($hash/10) * 100).'" data-bar-color="#1F8A70"><span><b class="value"> '.$hash.' </b> Gh/s</span></div><div>BTC Miner '.$devid.' </div> <a href="log.php#CGMinerLog">'.$valids.'/'.$totals.' ('.$rejrate.'%)</a></div>';
	
// 		//$table .= '<tr><td>LTC Miner '.$devid.' ('.$devproc[$devid].')</td><td class="hidden-480"><span class="label label-info arrowed-right arrowed-in">Running</span></td><td>'.$hash.'</td><td>'.$valids.'/'.$totals.' ('.$rejrate.'%)</td></tr>';
	
// 	}
	
	

// }

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
    <li class="active"></li>
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
        
        <?php if(isset($_GET["i"])) {?>
		<div class="alert alert-success">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="fa fa-check-circle"></i>
                          <?php  echo $info ?>
       </div>
       <?php }?>
        
        <div class="panel">
        		<div class="panel-heading">
                    <h3 class="panel-title">Mining Session Overview (<span id="stat-state">LOADING..</span>)</h3>
                </div>
                <div class="panel-body">
                    <ul class="col-md-12 stats">
                    	 <li id ="stat-mh" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Mh/s current hashrate</span>
                        </li>
                        <li id ="stat-avgmh" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Mh/s avg hashrate in last 5m</span>
                        </li>
                        <li id ="stat-acc" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Acc./Rej.</span>
                            <em>LOADING..</em>
                        </li>
                        <li id = "stat-wu" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Work utility</span>
                        </li>
                    </ul>
                </div>
                <div class="panel-body">
                    <ul class="col-md-12 stats">
                    	 <li id ="stat-found" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Found blocks.</span>
                        </li>
                        <li id ="stat-discarded" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Work discarded</span>
                        </li>
                        <li id ="stat-stale" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Work stale</span>
                        </li>
                        <li id = "stat-hw" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Hardware errors</span>
                        </li>
                    </ul>
                </div>
            </div>
        	
        	<?php if ($runmode == "SHA256" || $runmode == "DUAL") {?>
                    <div class="jumbotron">
                        <h1>SHA256 Mining Active</h1>
                        <p>Per device mining statistics are currently not available when mining in SHA256 or DUAL Mode</p>
                        <p>Sorry for this. We are still working on it.</p>
                    </div> 
                <div class="panel-body">
                </div>
           <?php }?>
        
            <div class="panel ">
            	 <?php if ($runmode == "SCRYPT" || $runmode == "DUAL") {?>
                <div class="panel-heading">
                    <h3 class="panel-title">SCRYPT Miners hashrate <b id="ltc_totalhash" class="value"><?php echo $totalhash ?></b> Kh/s</h3>
                </div>
                <div class="panel-body">
                	<?php echo $table ?>
                </div>
                <?php }?>

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
		                    <label for="ltc_worker">Scrypt worker name</label>
		                    <input class="form-control" id="ltc_worker" name="ltc_worker" value="<?php echo $ltc_worker?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter Worker name here. Some pools uses your BTC address.">
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
		                <div class="form-group" style="display:none">
		                	<div class="checkbox">
		                        <label>
		                            <input type="checkbox" name="ltc_enable" <?php $tmpstring = $ltc_enable ? 'checked' : ''; echo $tmpstring; ?> >
		                           	Enable
		                        </label>
		                    </div>
		                </div>
		                <button type="submit" class="btn btn-primary">Save and restart</button>
                </div> 
                
                 <div class="panel-heading" style="display: none">
                	 <h4 class="panel-title">BTC pool configuration</h4>
                </div>
                <div class="panel-body" style="display:none">
		                <div class="form-group">
		                    <label for="btc_url">BTC Pool address</label>
		                    <input class="form-control" id="btc_url" name="btc_url" placeholder="stratum+tcp://..." value="<?php echo $btc_url?>"  data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter the pool URL here">
		                </div>
		                <div class="form-group">
		                    <label for="btc_worker">BTC worker name</label>
		                    <input class="form-control" id="btc_worker" name="btc_worker" value="<?php echo $btc_worker?>" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="Enter Worker name here. Some pools uses your BTC address.">
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
                <div class="panel">
                <div class="panel-heading">
                	 <h4 class="panel-title">System logs:</h4>
                </div>
            	<div class="panel-body">
		        	<div class="form-group">
		                    <label>Log</label>
		                    
		                    <pre id="logger" class="prettyprint linenum" style="white-space:nowrap; overflow:auto;height:150px">
		                    	Loading...
		                    </pre>
		                    
		            </div>
		            <em>version : <?php echo VERSION?></em>
	            </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- END: CONTENT -->

            </div>
            <!-- END: BODY --> 
       <?php include 'includes/footer.php';?>
       <script>
			//update stats script
			function updateScreen() {
				$.ajax({
					  dataType: "json",
					  url: "ajaxController.php?action=GetStats"
					}).done(function (data) {
								var totalHashLTC = 0;
								for (i = 0 ; i < data.LTCDevices.length ; i++) {
									var ltcdevice = data.LTCDevices[i];
									if (ltcdevice) {
										var hash = ltcdevice.hash;
										var percentage = (hash / 500) * 100;
										var totals = ltcdevice.totals;
										var valids = ltcdevice.valids;
										var rejrate = ltcdevice.rejectrate;
										$("#" + ltcdevice.dev).data('easyPieChart').update(percentage);
										$("#" + ltcdevice.dev + " b.value").html(hash);
										$("#" + ltcdevice.dev ).siblings("a").html("Mining..."+valids+"/"+totals+" ("+rejrate+"%)");
										$("#" + ltcdevice.dev ).siblings("a").attr("title" , "lastcommit " + ltcdevice.lastcommit + " minutes ago ");
										totalHashLTC += parseInt(hash);
									}
								}
								$("#ltc_totalhash").html(totalHashLTC);	

								var totalHashBTC = 0;
								for (i = 0 ; i < data.BTCDevices.length ; i++) {
									var btcdevice = data.BTCDevices[i];
									if (btcdevice) {
										var hash = btcdevice.hash;
										var percentage = (hash / 500) * 100;
										var totals = btcdevice.totals;
										var valids = btcdevice.valids;
										var rejrate = btcdevice.rejectrate;
										$("#" + btcdevice.dev).data('easyPieChart').update(percentage);
										$("#" + btcdevice.dev + " b.value").html(hash);
										$("#" + btcdevice.dev ).siblings("a").html("Mining..."+valids+"/"+totals+" ("+rejrate+"%)");
										$("#" + btcdevice.dev ).siblings("a").attr("title" , "lastcommit " + btcdevice.lastcommit.toFixed(2) + " minutes ago ");
										totalHashBTC += parseInt(hash);
									}
								}
								$("#btc_totalhash").html(totalHashBTC);	

								//do summary
								$("#stat-mh b.value").html(data.Summary.mh);
								$("#stat-avgmh b.value").html(data.Summary.avgmh);
								$("#stat-acc b.value").html( data.Summary.acc + " / " + data.Summary.rej);
								$("#stat-acc em").html( (data.Summary.rej / data.Summary.acc * 100).toFixed(2) + " % rejection rate");
								$("#stat-wu b.value").html(data.Summary.wu);

								$("#stat-found b.value").html(data.Summary.found);
								$("#stat-discarded b.value").html(data.Summary.discarded);
								$("#stat-stale b.value").html(data.Summary.stale);
								$("#stat-hw b.value").html(data.Summary.hw);
								$("#stat-state").html(parseTime(data.Summary.elapsed));
								
							}) ;
				
				$("#logger").load("ajaxController.php?action=GetSysLog", function() {
					$("#logger").scrollTop($("#logger").prop("scrollHeight"));
					});
			}

			updateScreen();
			setInterval(function(){
					updateScreen();
				}, 5000);


			function parseTime (seconds) {
				var result = 0
				if (seconds > 0){
					var hours = parseInt( seconds / 3600 ) % 24;
					var minutes = parseInt( seconds / 60 ) % 60;
					var seconds = seconds % 60;
					result = (hours < 10 ? "0" + hours : hours) + " hrs " + (minutes < 10 ? "0" + minutes : minutes) + " mins " + (seconds  < 10 ? "0" + seconds : seconds) + " seconds ";
				}	

				return result;		
			}
			
        </script>
    </body>
</html>