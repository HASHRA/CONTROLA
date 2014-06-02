<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/configmanager.class.php';
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

$success = false;   
 
if($_POST)
{

    $model = $_POST["mode"];
    $freq = $_POST["freq"];
  
    if($freq < 200 || $freq > 400)
    {
        $freq = 291;
    }

    $iniStr = "[config]\n";
    $iniStr .= "model = {$model}\n";
    $iniStr .= "freq = {$freq}\n";
      
    $outfile = fopen(FILE_CONFIG,"w");
    fwrite($outfile, $iniStr);
    fclose($outfile);
		
	exec('wget http://localhost/system/monitor.php > /dev/null &');
	header('Location: /?i=2');
	exit;
}
else
{
    $freq = $iniArr["freq"];
}
$li = '';
$totalhash = 0;
$totalhashbtc = 0;
$info = "";
$table = 'No devices found';
$devices = Miner::getAvailableDevice();

syslog(LOG_INFO , 'devices -  ' . json_encode($devices));

if(!empty($devices))
{
	$table = "";

	$statsui = Miner::getBFGMinerStats();
	foreach($statsui["devices"] as $stat)
	{
		$totalhash += $stat["hashrate"];
	}
	$color = $runmode === 'SHA256' || $runmode === 'DUAL' ? '1F8A70' : 'F94743';
	foreach($devices as $devid)
	{
		$unit = (SCRYPT_UNIT === KHS) ? 'Kh/s' : 'Mh/s'; 
		$table .= '<div class="col-md-4 col-sm-4 col-xs-6 text-center pie-box"><div id="ltc_'.$devid.'" class="pie-chart" data-percent="0" data-bar-color="#'.$color.'"><span><b class="value"> 0 </b> '.$unit.'</span></div><div>'.MINER_NAME.' '.($devid + 1).' </div> <a class="minerLink" href="#'.$devid.'"> Offline :(</a></div>';
		
	}

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
$prodSettings = ConfigurationManager::instance()->getProductSettings();
$prodname = PRODUCT_NAME;
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
        <h3 class="header-title"><?php echo $prodname;?></h3>
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
                	 <h4 class="panel-title"><?php if (DUAL_SUPPORT) { echo 'SCRYPT'; }?> Pools</h4>
                </div>
                <div class="panel-body">
            <a href="#"  data-add-action="scrypt"><i class="fa fa-plus"></i> add pool</a>
			<table class="table table-striped table-hover" data-component="PoolTable" <?php if($runmode == 'SCRYPT') echo 'data-alive="true"' ?> data-pooltype="scrypt">
                <thead>
                    <tr>
                        <th>Prio</th>
                        <th>Url</th>
                        <th>Stat</th>
                        <th>Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                   <tr><td colspan="5">Loading...</td></tr>
                </tbody>
            </table>		               
                </div> 
                
                 <div class="panel-heading" <?php if (!supportedAlgo(SHA)) {echo 'style="display:none"';}?>>
                	 <h4 class="panel-title">SHA pools</h4>
                </div>
                <div class="panel-body" <?php if (!supportedAlgo(SHA)) {echo 'style="display:none"';}?>>
                		<a href="#" data-add-action="sha"><i class="fa fa-plus"></i> add pool</a>
		                <table class="table table-striped table-hover" data-component="PoolTable" <?php if($runmode == 'SHA256' || $runmode == 'DUAL') echo 'data-alive="true"' ?> data-pooltype="sha">
			                <thead>
			                    <tr>
			                        <th>Prio</th>
			                        <th>Url</th>
			                        <th>Stat</th>
			                        <th>Order</th>
			                        <th>Action</th>
			                    </tr>
			                </thead>
			                <tbody>
			                    <tr><td colspan="5">Loading...</td></tr>
			                </tbody>
			            </table>	
                </div>
                 <div class="panel-body">
                 	<div class="form-group" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="The default clock speed setting for the <?= $prodname ?> is <?= DEFAULT_CLOCK ?>. Change clock speed at your own risk. Increasing Clock speed voids warranty.">
		                	<label for="freq">Core clock speed (Mhz)</label>
		                	<select class="form-control select2" id="freq" name="freq">
								<?php for($i = 250 ; $i <= 350 ; $i++) {?>
									<option value="<?php echo $i?>" <?php $tbool = $freq == $i ? 'selected="selected"' : ''; echo $tbool; ?> ><?php echo $i?></option>
								<?php }?>
		                     </select>
		                </div>
		                
		                <div class="btn-group" data-component="BtnSwitch" <?php if (!DUAL_SUPPORT) echo "style='display:none;'" ?>>
		                    <button type="button" value="2" class="btn btn-default <?php if ($runmode == "SCRYPT") echo 'active' ?>">Scrypt</button>
		                    <button type="button" value="1" class="btn btn-default <?php if ($runmode == "SHA256") echo 'active' ?>">SHA256</i></button>
		                    <button type="button" value="3" class="btn btn-default <?php if ($runmode == "DUAL") echo 'active' ?>">Dual</button>
		                    <input type="hidden" id="mode" name="mode" value="2"/>
		                </div>
		                
		                <button type="submit" class="btn btn-default">Save and restart</button>
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

<!-- END: CONTENT -->

            </div>
            
            <!-- Modals -->
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title" id="myModalLabel"></h4>
                                </div>
                                <div id="modalContent" class="modal-body">
                                   
                                </div>
                                <div class="modal-footer">
                                    <button type="button" id="update-button-close" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->
            
                    <div class="modal fade" id="poolFormModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title" id="poolModalLable"></h4>
                                </div>
                                <div id="modalContent" class="modal-body">
                                   <form role="form" data-pooltype="">
                                   	<input type="hidden" id="pool_id" name="pool_id" value="-1">
					                <div class="form-group">
					                    <label for="pool_url">Pool URL</label>
					                    <input type="text" class="form-control" id="pool_url" name="pool_url" placeholder="stratum+tcp://" data-validate="mandatory">
					                    <span class="help-block">
					                        
					                    </span>
					                </div>
					                <div class="form-group">
					                    <label for="worker_name">Worker name</label>
					                    <input type="text" class="form-control" id="worker_name" name="worker_name" data-validate="mandatory">
					                    <span class="help-block">
					                        
					                    </span>
					                </div>	
					                <div class="form-group">
					                    <label for="worker_password">Worker password</label>
					                    <input type="text" class="form-control" id="worker_password" name="worker_password" data-validate="mandatory">
					                    <span class="help-block">
					                        
					                    </span>
					                </div>					               
					            </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" data-component="PoolFormButton" id="update-button-close" class="btn btn-primary">Save</button> <button type="button" id="update-button-close" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->
            
            <!-- END: BODY --> 
       <?php include 'includes/footer.php';?> 
       
       <script>
			var myModal = $("#myModal");
			var myModalLabel = $("#myModal #myModalLabel");
			var modalContent = $("#myModal #modalContent");

			//serialize form to object
			$.fn.serializeObject = function()
			{
			    var o = {};
			    var a = this.serializeArray();
			    $.each(a, function() {
			        if (o[this.name] !== undefined) {
			            if (!o[this.name].push) {
			                o[this.name] = [o[this.name]];
			            }
			            o[this.name].push(this.value || '');
			        } else {
			            o[this.name] = this.value || '';
			        }
			    });
			    return o;
			};

			$.fn.validate = function() {
				$(this).find("[data-validate]").each( function (idx){
					var validator = $(this).data('validate');
					var res = window[validator].apply(null, [this]);
					if (!res.valid) {
						$(this).parent().addClass("has-error");
						$(this).next(".help-block").html(res.message);
					}else{
						$(this).parent().removeClass("has-error");
						$(this).next(".help-block").html("");
					}
				});
				return !$("div.form-group").hasClass('has-error');
			};


			$.fn.prefill = function (pool) {
				$(this).find(".form-group .help-block").html('');
				$(this).find(".has-error").removeClass("has-error");
				$(this).find("[name='pool_url']").val(pool.url);
				$(this).find("[name='pool_id']").val(pool.id);
				$(this).find("[name='worker_name']").val(pool.worker);
				$(this).find("[name='worker_password']").val(pool.password);								
			}
			
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

            $(".minerLink").on("click", function () {

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

								//detect which pool is alive
								for (var i = 0 ; i < data.pools.length ; i++) {
									var pool = data.pools[i];
									if (pool.Status =='ALIVE'){
										$("table[data-alive] tbody tr").each(function () {
											var url = $(this).find("[data-component='URLCell']").text();
											var span = $(this).find('span');
											if (pool.URL == url) {
												span.removeClass('label-default');
												span.addClass('label-success');
												span.text("Running");
											}else{
												span.addClass('label-default');
												span.removeClass('label-success');
												span.text("Sleeping");
											}
										});	
									}else if (pool.Status == 'DEAD') {
										$("table[data-alive] tbody tr").each(function () {
											var url = $(this).find("[data-component='URLCell']").text();
											var span = $(this).find('span');
											if (pool.URL == url) {
												span.removeClass('label-default');
												span.removeClass('label-success');
												span.addClass('label-danger');
												span.text("Dead");
											}
										});	
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


			function PoolTable (el) {
				this.poolTable = $(el);
				this.poolTableBody = this.poolTable.find('tbody');
				this.pools;
				this.poolType = this.poolTable.data('pooltype');
				var ctx = this;
				this.loadTable = function () {
					$.post('/ajaxController.php?action=GetPools' , {pooltype : ctx.poolType}, function(data) {
						if (data.STATUS == 'NOTOK') {
							//application error handling
							console.log("Failed at getting pools");
						}else{
							//load the table
							ctx.pools = data.PAYLOAD;
							ctx.populate();
						}
					}, 'json').fail(function() {
						//error handling
						console.log("http fail");
					});
				};
				this.bind = function () {
					ctx.poolTableBody.find("[data-pooltable-action]").on('click' , function (ev) {
						ev.preventDefault();
						var id = $(ev.target).closest('tr').data('id');
						$(document).trigger('action-'+ $(ev.target).closest('a').data('pooltable-action'), [ctx.poolType, ctx.pools[id]]);
				});	
				};

				this.populate = function () {
					var tbody = ctx.poolTableBody;
					var rows = '';
					for (var i = 0 ; i < ctx.pools.length ; i++) {
						ctx.pools[i].id = i;
						var isFirst = (i == 0);
						var isLast = (i == ctx.pools.length -1);
						rows += "<tr data-id='"+i+"'><td>"+i+"</td><td data-component='URLCell'>"+ctx.pools[i].url+"</td><td><span class='label label-default'>not active</span></td><td class='actions'>";
						if (!isFirst){
							rows += "<a data-pooltable-action='re-up' href='#'><li class='fa fa-arrow-up'></li></a>";
						}
						if (!isLast) {
							rows +=	"<a data-pooltable-action='re-down' href='#'><li class='fa fa-arrow-down'></li></a>";
						}
						rows += "</td><td class='actions'><a data-pooltable-action='edit' href='#'><li class='fa fa-pencil'></li></a><a data-pooltable-action='delete' href='#'><li class='fa fa-fw fa-times'></li></a></td></tr>";
					}
					tbody.html(rows);
					ctx.bind();
				};
				
				this.loadTable();
				$(document).on('add-pool', function (evt, pooltype, pool) {
					if(pooltype == ctx.poolType){

						$.post('/ajaxController.php?action=SetPool' , 
								{pool : {id:pool.pool_id, type: pooltype , url:pool.pool_url, worker:pool.worker_name, password: pool.worker_password  }}, function(data) {
							if (data.STATUS == 'NOTOK') {
								//application error handling
								console.log("Failed at adding pool");
							}else{
								//load the table
								ctx.pools = data.PAYLOAD;
								ctx.populate();
							}
						}, 'json').fail(function() {
							//error handling
							console.log("http fail");
						});
					}
				});

				$(document).on('delete-pool', function (evt, pooltype, pool) {
					if(pooltype == ctx.poolType){

						$.post('/ajaxController.php?action=DeletePool' , 
								{type : pooltype , id:pool.id}, function(data) {
							if (data.STATUS == 'NOTOK') {
								//application error handling
								console.log("Failed at deleting pool");
							}else{
								//load the table
								ctx.pools = data.PAYLOAD;
								ctx.populate();
							}
						}, 'json').fail(function() {
							//error handling
							console.log("http fail");
						});
					}
				});
				
				$(document).on('rearrange-pool', function (evt, pooltype, pool, target) {
					if(pooltype == ctx.poolType){

						$.post('/ajaxController.php?action=ReorderPool' , 
								{type : pooltype , old:pool.id, target:target}, function(data) {
							if (data.STATUS == 'NOTOK') {
								//application error handling
								console.log("Failed at deleting pool");
							}else{
								//load the table
								ctx.pools = data.PAYLOAD;
								ctx.populate();
							}
						}, 'json').fail(function() {
							//error handling
							console.log("http fail");
						});
					}
				});
			} 

			
			$(document).on('action-re-up', function (evt, poolType, payload) {
				$(document).trigger('rearrange-pool', [poolType, payload, payload.id - 1]);
			});
			$(document).on('action-re-down', function (evt, poolType, payload) {
				$(document).trigger('rearrange-pool', [poolType, payload, payload.id + 1]);
			});

			$(document).on('action-edit', function (evt, poolType,  payload) {
				var poolFormModal = $("#poolFormModal");
				$("#poolModalLable").html("Edit " + poolType + " Pool " );
				poolFormModal.find("[data-pooltype]").data('pooltype' , poolType);
				var form = poolFormModal.find("form");
				form.prefill(payload);
				poolFormModal.modal();
			});

			$(document).on('action-delete', function (evt, poolType, payload) {
				if(confirm("Are you sure you want to delete " + payload.url +  ' ?'  )){
					$(document).trigger('delete-pool', [poolType, payload]);
				}
			});
			
			$("[data-component='PoolFormButton']").on('click', function (evt) {
				modal = $("#poolFormModal");
				form = modal.find("form");
				var valid = form.validate();
				if (valid) {
					modal.modal('hide');
					$(document).trigger('add-pool', [form.data('pooltype'),form.serializeObject()]);
				}
			});
			
			//activate add pool buttons
			$("[data-add-action]").each(function (idx, element) {
				$(element).on('click', function (ev) {
					ev.preventDefault();
					var poolFormModal = $("#poolFormModal");
					$("#poolModalLable").html("Add " + $(this).data('add-action')+ " Pool " );
					poolFormModal.find("[data-pooltype]").data('pooltype' , $(this).data('add-action'));
					poolFormModal.find("input.form-control").val('');
					poolFormModal.find("input[name='pool_id']").val('-1');
					poolFormModal.find(".form-group .help-block").html('');
					poolFormModal.find(".has-error").removeClass("has-error");
					poolFormModal.modal();
	        	});
			});
			//validator
			var mandatory = function (field) {
				if ($.trim(field.value) == '') {
					var labeltext = $("label[for='"+$(field).attr('id')+"']").text();
					return {valid:false, message: labeltext + " is mandatory "};
				}
				return {valid:true};
			}
			
			//init all pooltables
			var arrPoolTables = [];
			$("[data-component='PoolTable']").each(function (idx, element) {
				arrPoolTables.push(new PoolTable(element));
			});	

			$("[data-component='BtnSwitch']").each(function (idx, element){
				$(this).find("button").on('click' , function () {
					//do switch
					$(this).siblings('button').removeClass('active');
					$(this).addClass('active');
					$(this).siblings("input[type='hidden']").val($(this).val());
				});
			});		
        </script>
      
        
    </body>
</html>