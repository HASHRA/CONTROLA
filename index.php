<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';
require_once 'class/accesscontrol.class.php';

if (!AccessControl::hasAccess()){
	header('Location: login.php');
	die();
}

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
    $ltc_enable = (isset($_POST["check_ltc"]));
    if(empty($ltc_url) || empty($ltc_worker) || empty($ltc_pass))
    {
        $valid = false;
    }
    
    
    $btc_url = preg_replace('/\s+/', '', $_POST["btc_url"]);
    $btc_worker = preg_replace('/\s+/', '', $_POST["btc_worker"]);
    $btc_pass = preg_replace('/\s+/', '', $_POST["btc_pass"]);
    $btc_enable = (isset($_POST["check_btc"]));
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
    if($freq < 600 || $freq > 1300)
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
		
		exec('wget http://localhost/system/monitor.php > /dev/null &');
		header('Location: /?i=2');
		exit;
		
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
		$unit = (SCRYPT_UNIT === KHS) ? 'Kh/s' : 'Mh/s'; 
		$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="ltc_'.$devid.'" class="pie-chart" data-percent="0" data-bar-color="#'.$color.'"><span><b class="value"> 0 </b> '.$unit.'</span></div><div>Scrypt '.MINER_NAME.' '.($devid + 1).' </div> <a class="minerLink" href="#'.$devid.'"> Offline :(</a></div>';
		
	}

}

foreach($devices["devids"]  as $devid)
{

	$tablebtc .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="btc_'.$devid.'" class="pie-chart" data-percent="0" data-bar-color="#'.$color.'"><span><b class="value"> 0 </b> Gh/s</span></div><div>SHA256 '.MINER_NAME.' '.($devid + 1).' </div> <a class="minerLink" href="#'.$devid.'"> Offline :(</a></div>';
}

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

                <?php require_with('includes/menu.php', array('selected' => 'index'));?>

                <section class="content">
                    
<ol class="breadcrumb">
    <li class="active"></li>
</ol>

<div class="header">
    <div class="col-md-12">
        <h3 class="header-title"><?php echo PRODUCT_NAME;?></h3>
        <?php if (DUAL_SUPPORT) {?>
        <p class="header-info">Running in <b class="value"> <?php echo $runmode?> </b> mode</p>
        <?php }?>
        <?php if ($runmode == "DUAL"){?>
         <div class="alert alert-warning">
                          
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>Warning!</strong> Only SHA256 mining statistics are available in DUAL mode. Please check your remote pool for Scrypt mining statistics.
                        </div> 
        <?php }?>
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
                            <span><b class="value">LOADING..</b> <?php if($runmode == "SHA256" || $runmode == "DUAL") {?>G<?php } else {?>M<?php }?>h/s current hashrate</span>
                        </li>
                        <li id ="stat-avgmh" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> <?php if($runmode == "SHA256" || $runmode == "DUAL") {?>G<?php } else {?>M<?php }?>h/s avg hashrate</span>
                        </li>
                        <li id ="stat-acc" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Acc./Rej.</span>
                            <em>LOADING..</em>
                        </li>
                        <li id = "stat-hw" class="stat col-md-3 col-sm-3 col-xs-6">
                            <span><b class="value">LOADING..</b> Hardware errors</span>
                        </li>
                    </ul>

                    <button type="button" id="actionbutton-restart" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="Only restarts the current mining session, not the whole system!"><i class="fa fa-retweet"></i> Restart </button>
		            <button type="button" id="actionbutton-reboot" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="Reboots CONTROLA, may take a while!"><i class="fa fa-power-off"></i> Reboot </button>
                </div>
              
            </div>
            <div class="panel ">
            	 <?php if ($runmode == "SCRYPT") {?>
                <div class="panel-heading">
                    <h3 class="panel-title">SCRYPT Miners hashrate <b id="ltc_totalhash" class="value"><?php echo $totalhash ?></b> Kh/s</h3>
                </div>
                <div class="panel-body">
                	<?php echo $table ?>
                </div>
                <?php }?>
                
                <?php if ($runmode == "SHA256" || $runmode == "DUAL") {?>
                <div class="panel-heading">
                    <h3 class="panel-title">SHA256 Miners hashrate <b id="btc_totalhash" class="value"><?php echo ($totalhash / 1000000) ?></b> Gh/s</h3>
                </div>
                <div class="panel-body">
                	<?php echo $tablebtc ?>
                </div>
                <?php }?>

            </div>
            
        </div>
        <div class="col-md-6">
            <div class="panel ">
            
            
                <form role="form" action="." method="post" id="config_form">
                <div class="panel-heading">
                	 <h4 class="panel-title"><?php if (DUAL_SUPPORT) { echo 'SCRYPT'; }?> pool configuration</h4>
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
								<?php for ($i = 600 ; $i <= 1300 ; $i += 50) {?>
									<option value="<?php echo $i?>" <?php $tbool = $freq == $i ? 'selected="selected"' : ''; echo $tbool; ?> ><?php echo $i?></option>
								<?php }?>
		                     </select>
		                </div>
		                
		                <div class="form-group"  <?php if (!DUAL_SUPPORT) {echo 'style="display:none"';}?>>
		                	 <div class="form-control-static">
			                	<div class="checkbox">
			                        <label>
			                            <input type="checkbox" name="check_ltc" <?php $tmpstring = ((!DUAL_SUPPORT && supportedAlgo(SCRYPT)) || $ltc_enable) ? 'checked' : ''; echo $tmpstring; ?> value='ltc'>
			                           	Enable Scrypt mining
			                        </label>
			                    </div>
		                    </div>
		                </div>
		                <div class="form-group" <?php if (DUAL_SUPPORT) {echo 'style="display:none"';}?>>
		                	<button type="submit" class="btn btn-primary">Save and restart</button>
		                </div>
                </div> 
                
                 <div class="panel-heading" <?php if (!supportedAlgo(SHA)) {echo 'style="display:none"';}?>>
                	 <h4 class="panel-title">BTC pool configuration</h4>
                </div>
                <div class="panel-body" <?php if (!supportedAlgo(SHA)) {echo 'style="display:none"';}?>>
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
		                	 <div class="form-control-static">
			                	<div class="checkbox">
			                        <label>
			                            <input type="checkbox" name="check_btc" <?php $tmpstring = $btc_enable ? 'checked' : ''; echo $tmpstring; ?> value="btc">
			                           	Enable SHA256 Mining
			                        </label>
			                    </div>
		                    </div>
		                </div>
		                <div class="form-group">
		                	<button type="submit" class="btn btn-primary">Save and restart</button>
		                </div>
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
    </section>
</div>

 <!-- Modal -->
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
<!--                                     <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> -->
                                    <h4 class="modal-title" id="myModalLabel"></h4>
                                </div>
                                <div id="modalContent" class="modal-body">
                                   
                                </div>
                                <div class="modal-footer">
                                    <button type="button" id="update-button-close" class="btn btn-default" data-dismiss="modal">Close this dialog</button>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

<!-- END: CONTENT -->

            </div>
            <!-- END: BODY --> 
       <?php include 'includes/footer.php';?>
       <script>
			var myModal = $("#myModal");
			var myModalLabel = $("#myModalLabel");
			var modalContent = $("#modalContent");
			
			$("#actionbutton-restart").on("click" , function(event) {
					event.preventDefault();
					myModalLabel.html("Restarting miner");
					modalContent.html("Sending restart signal");
					myModal.modal();
					$.post('/ajaxController.php?action=RestartMiner' , function(data) {
						if (data.STATUS == 'NOTOK') {
							myModalLabel.html("Restarting miner failed");
							modalContent.html(data.MESSAGE);
							
						}else{
							myModalLabel.html("Miner is restarting");
							modalContent.html("The miner is restarting right now. Check the syslog panel for progress");
						}
					}, 'json').fail(function() {
						myModalLabel.html("Restarting miner failed");
						modalContent.html("System error has occured");
					}); 
				});

			$("#actionbutton-reboot").on("click" , function(event) {
				event.preventDefault();
				myModalLabel.html("Rebooting CONTROLA");
				modalContent.html("Sending reboot signal");
				myModal.modal();
				$.post('/ajaxController.php?action=Reboot' , function(data) {
					if (data.STATUS == 'NOTOK') {
						myModalLabel.html("Rebooting CONTROLA failed");
						modalContent.html(data.MESSAGE);
						
					}else{
						myModalLabel.html("CONTROLA is rebooting");
						modalContent.html("The CONTROLA device is rebooting right now. This may take a while");
					}
				}, 'json').fail(function() {
					myModalLabel.html("Rebooting CONTROLA failed");
					modalContent.html("System error has occured");
				}); 
			});
       
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
										var percentage = (hash / <?php echo MINER_MAX_HASHRATE?>) * 100;
										var totals = ltcdevice.totals;
										var valids = ltcdevice.valids;
										var rejrate = ltcdevice.rejectrate;
										$("#" + ltcdevice.dev).data('easyPieChart').update(percentage);
										$("#" + ltcdevice.dev + " b.value").html(hash);
										$("#" + ltcdevice.dev ).siblings("a").html("Mining..."+valids+"/"+totals+" ("+rejrate+"%)");
										$("#" + ltcdevice.dev ).siblings("a").attr("title" , "lastcommit " + ltcdevice.lastcommit + " minutes ago ");
										totalHashLTC += parseFloat(hash);
									}
								}
								<?php if (SCRYPT_UNIT === KHS) {?>
								$("#ltc_totalhash").html(totalHashLTC);	
								<?php } else { ?>
								$("#ltc_totalhash").html(parseInt(totalHashLTC * 1000));
								<?php }?>

								var totalHashBTC = 0;
								for (i = 0 ; i < data.BTCDevices.length ; i++) {
									var btcdevice = data.BTCDevices[i];
									if (btcdevice) {
										var hash = btcdevice.hash;
										var percentage = (hash / 15) * 100;
										var totals = btcdevice.totals;
										var valids = btcdevice.valids;
										var rejrate = btcdevice.rejectrate;
										$("#" + btcdevice.dev).data('easyPieChart').update(percentage);
										$("#" + btcdevice.dev + " b.value").html(hash);
										$("#" + btcdevice.dev ).siblings("a").html("Mining..."+valids+"/"+totals+" ("+rejrate+"%)");
										$("#" + btcdevice.dev ).siblings("a").attr("title" , "lastcommit " + btcdevice.lastcommit + " minutes ago ");
										totalHashBTC += parseFloat(hash);
									}
								}
								$("#btc_totalhash").html(totalHashBTC.toFixed(2));	

								//do summary
								$("#stat-mh b.value").html((data.Summary.mh < 1000) ?data.Summary.mh : (data.Summary.mh / 1000).toFixed(2));
								$("#stat-avgmh b.value").html((data.Summary.avgmh < 1000) ? data.Summary.avgmh : (data.Summary.avgmh / 1000).toFixed(2));
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