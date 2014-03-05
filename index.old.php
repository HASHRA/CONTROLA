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
			$table .= '<tr><td>LTC Miner '.$devid.' ('.$devproc[$devid].')</td><td class="hidden-480"><span class="label label-info arrowed-right arrowed-in">Running</span></td><td>'.$hash.'</td><td>'.$valids.'/'.$totals.' ('.$rejrate.'%)</td></tr>';
		}
		else
		{
			$table .= '<tr><td>LTC Miner '.$devid.'</td><td class="hidden-480"><span class="label label-danger arrowed">Offline</span></td><td>0</td><td>0/0</td></tr>';
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
		$table .= '<tr><td>BTC Miner ('.$iniArr["btc_worker"].')</td><td class="hidden-480"><span class="label label-info arrowed-right arrowed-in">Running</span></td><td>N/A</td><td>N/A</td></tr>';
	}
	else
	{
		$table .= '<tr><td>BTC Miner ('.$iniArr["btc_worker"].')</td><td class="hidden-480"><span class="label label-danger arrowed">Offline</span></td><td>N/A</td><td>N/A</td></tr>';
	}
}
 
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
	<head>
		<meta charset="utf-8" />
		<title>Dashboard - Hashra webconsole</title>

		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		<!-- basic styles -->

		<link href="assets/css/bootstrap.min.css" rel="stylesheet" />
		<link rel="stylesheet" href="assets/css/font-awesome.min.css" />

		<!--[if IE 7]>
		  <link rel="stylesheet" href="assets/css/font-awesome-ie7.min.css" />
		<![endif]-->

		<!-- page specific plugin styles -->

		<!-- fonts -->

		<link rel="stylesheet" href="assets/css/ace-fonts.css" />

		<!-- ace styles -->

		<link rel="stylesheet" href="assets/css/ace.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />

		<!--[if lte IE 8]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->
		<style type="text/css">
			#navbar {
				background-color:white;
				height:200px;
			}
		</style>
		
		<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
		<script src="assets/js/flot/jquery.flot.min.js"></script>
		<script src="assets/js/flot/jquery.flot.time.js"></script>
	</head>

	<body>
		<div class="navbar navbar-default" id="navbar">
			<script type="text/javascript">
				try{ace.settings.check('navbar' , 'fixed')}catch(e){}
			</script>

			<div class="navbar-container" id="navbar-container">
				<div class="navbar-header pull-left">
					<a href="http://lightningasic.com/" class="navbar-brand" target="_blank">
						<small>
						<img src="logo.png" width="300" style="position: relative; top: 5px;" />
						</small>
					</a><!-- /.brand -->
				</div><!-- /.navbar-header -->

				<div class="navbar-header pull-right" role="navigation">
					
				</div><!-- /.navbar-header -->
			</div><!-- /.container -->
		</div>

		<div class="main-container" id="main-container">
			<script type="text/javascript">
				try{ace.settings.check('main-container' , 'fixed')}catch(e){}
			</script>

			<div class="main-container-inner">
				<a class="menu-toggler" id="menu-toggler" href="#">
					<span class="menu-text"></span>
				</a>

				<div class="sidebar" id="sidebar">
					<script type="text/javascript">
						try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
					</script>

					<div class="sidebar-shortcuts" id="sidebar-shortcuts">
						

						<div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
							<span class="btn btn-success"></span>

							<span class="btn btn-info"></span>

							<span class="btn btn-warning"></span>

							<span class="btn btn-danger"></span>
						</div>
					</div><!-- #sidebar-shortcuts -->

					<ul class="nav nav-list">

						<li class="active">
							<a href="/" class="dropdown-toggle">
								<i class="icon-dashboard"></i>
								<span class="menu-text"> Miners </span>

								<b class="arrow icon-angle-down"></b>
							</a>

							<ul class="submenu">
							<?php echo $li?>
							</ul>
						</li>
						<li>
							<a href="/stats.php">
								<i class="icon-bar-chart"></i>
								<span class="menu-text">Statistics</span>
							</a>
						</li>
						<li>
							<a href="/update.php">
								<i class="icon-cogs"></i>
								<span class="menu-text">Update Firmware</span>
							</a>
						</li>
						
					</ul><!-- /.nav-list -->

					<div class="sidebar-collapse" id="sidebar-collapse">
						<i class="icon-double-angle-left" data-icon1="icon-double-angle-left" data-icon2="icon-double-angle-right"></i>
					</div>

					<script type="text/javascript">
						try{ace.settings.check('sidebar' , 'collapsed')}catch(e){}
					</script>
				</div>

				<div class="main-content">
					<div class="breadcrumbs" id="breadcrumbs">
						<script type="text/javascript">
							try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
						</script>

						<ul class="breadcrumb">
							<li>
								<i class="icon-home home-icon"></i>
								<a href="/">Home</a>
							</li>
							<li class="active">Dashboard</li>
						</ul><!-- .breadcrumb -->

					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Dashboard
								<small>
									<i class="icon-double-angle-right"></i>
									overview &amp; stats
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->

								<div class="alert alert-block alert-success">
									<button type="button" class="close" data-dismiss="alert">
										<i class="icon-remove"></i>
									</button>

									<i class="icon-ok green"></i>

									Welcome to
									<strong class="green">
										LightningAsic Admin
										<small>(v1.0)</small>
									</strong>
								</div>
								
								<div class="alert alert-warning" <?php echo ($success ? 'style="display:block"' : 'style="display:none"') ?>>
									<button type="button" class="close" data-dismiss="alert">
										<i class="icon-remove"></i>
									</button>
									<strong>Warning -</strong>
									<?php echo $info?>
									<br>
								</div>

								<div class="hr hr32 hr-dotted"></div>

								<div class="row" id="minerconfig">
									<div class="col-sm-5">
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="lighter">
													<i class="icon-star orange"></i>
													Miner status <span style="font-size: 0.7em">(uptime: <?php echo $uptime ?>) (hashrate: <b><?php echo $totalhash ?> Kh/s</b>)</span>
												</h4>

												<div class="widget-toolbar">
													<a href="#" data-action="collapse">
														<i class="icon-chevron-up"></i>
													</a>
												</div>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
													<table class="table table-bordered table-striped">
														<thead class="thin-border-bottom">
															<tr>
																<th>
																	<i class="icon-caret-right blue"></i>
																	Name
																</th>

																<th class="hidden-480">
																	<i class="icon-caret-right blue"></i>
																	Status
																</th>
																
																<th class="hidden-480">
																	<i class="icon-caret-right blue"></i>
																	Khash/s
																</th>
																
																<th class="hidden-480">
																	<i class="icon-caret-right blue"></i>
																	Accepted
																</th>
															</tr>
														</thead>

														<tbody>
														<?php echo $table?>
														</tbody>
													</table>
												</div><!-- /widget-main -->
											</div><!-- /widget-body -->
										</div><!-- /widget-box -->
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="lighter">
													<i class="icon-signal"></i>
													System Log
												</h4>

												<div class="widget-toolbar">
													<a href="#" data-action="collapse">
														<i class="icon-chevron-up"></i>
													</a>
												</div>
											</div>

											<div class="widget-body">
												<div class="widget-main padding-4">
													<textarea rows="15" style="width: 100%"><?php echo $syslog ?></textarea>
												</div>
											</div><!-- /widget-body -->
										</div><!-- /widget-box -->

									</div>

									<div class="col-sm-7">
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="lighter">
													<i class="icon-signal"></i>
													Miner Configuration
												</h4>

												<div class="widget-toolbar">
													<a href="#" data-action="collapse">
														<i class="icon-chevron-up"></i>
													</a>
												</div>
											</div>

											<div class="widget-body">
												<div class="widget-main padding-4">
												
												<form class="form-horizontal" role="form" action="/" method="post" id="config_form">
														<h4 class="lighter">
															Scrypt Mining
														</h4>
														<div class="form-group">
		
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Scrypt Pool URL </label>

															<div class="col-sm-9">
																<input type="text" id="form-field-1" placeholder="Pool URL" class="col-xs-10 col-sm-5" name="ltc_url" value="<?php echo $ltc_url?>" />
																<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="left" data-content="More details." title="Url of your favorite pool. Usualy starts with stratum+tcp://...">?</span>
															</div>
														</div>

														<div class="space-4"></div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Worker(s) Name </label>

															<div class="col-sm-9">
																<input type="text" id="form-field-1" placeholder="Worker name" class="col-xs-10 col-sm-5" name="ltc_workers" value="<?php echo $ltc_workers?>" />
															</div>
														</div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Worker password </label>

															<div class="col-sm-9">
																<input type="text" id="form-field-1" placeholder="Worker password" class="col-xs-10 col-sm-5" name="ltc_pass" value="<?php echo $ltc_pass?>" />
															</div>
														</div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Core Frequency (MHz) </label>

															<div class="col-sm-9">
																<select id="form-field-1" class="col-xs-10 col-sm-5" name="freq">
																	<option value="600" <?php $tbool = $freq == 600 ? 'selected="selected"' : ''; echo $tbool; ?> >600</option>
																	<option value="650" <?php $tbool = $freq == 650 ? 'selected="selected"' : ''; echo $tbool; ?> >650</option>
																	<option value="700" <?php $tbool = $freq == 700 ? 'selected="selected"' : ''; echo $tbool; ?> >700</option>
																	<option value="750" <?php $tbool = $freq == 750 ? 'selected="selected"' : ''; echo $tbool; ?> >750</option>
																	<option value="800" <?php $tbool = $freq == 800 ? 'selected="selected"' : ''; echo $tbool; ?> >800</option>
																	<option value="850" <?php $tbool = $freq == 850 ? 'selected="selected"' : ''; echo $tbool; ?> >850</option>
																	<option value="900" <?php $tbool = $freq == 900 ? 'selected="selected"' : ''; echo $tbool; ?> >900</option>
																</select>
															</div>
														</div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Enable </label>

															<div class="col-sm-9">
																<input type="checkbox" id="form-field-1" class="col-xs-10 col-sm-5" name="ltc_enable" <?php $tmpstring = $ltc_enable ? 'checked' : ''; echo $tmpstring; ?> />
															</div>
														</div>

														<div class="space-4"></div>
														
														<div class="hr hr32 hr-dotted"></div>	
												
														<h4 class="lighter">
															SHA256 Mining
														</h4>
														
														<div class="form-group">
		
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">SHA256 Pool URL </label>

															<div class="col-sm-9">
																<input type="text" id="form-field-1" placeholder="Pool URL" class="col-xs-10 col-sm-5" name="btc_url" value="<?php echo $btc_url?>"  />
																<span class="help-button" data-rel="popover" data-trigger="hover" data-placement="left" data-content="More details." title="Url of your favorite pool. Usualy starts with stratum+tcp://...">?</span>
															</div>
														</div>

														<div class="space-4"></div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Worker Name </label>

															<div class="col-sm-9">
																<input type="text" id="form-field-1" placeholder="Worker name" class="col-xs-10 col-sm-5" name="btc_worker" value="<?php echo $btc_worker?>" />
															</div>
														</div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Worker password </label>

															<div class="col-sm-9">
																<input type="text" id="form-field-1" placeholder="Worker password" class="col-xs-10 col-sm-5" name="btc_pass" value="<?php echo $btc_pass?>" />
															</div>
														</div>

														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1">Enable </label>

															<div class="col-sm-9">
																<input type="checkbox" id="form-field-1" class="col-xs-10 col-sm-5" name="btc_enable" <?php $tmpstring = $btc_enable ? 'checked' : ''; echo $tmpstring; ?> />
															</div>
														</div>
														
														<div class="space-4"></div>



														<div class="clearfix form-actions">
															<div class="col-md-offset-3 col-md-9">
																<button class="btn btn-info" type="button" onclick="submit()">
																	<i class="icon-ok bigger-110"></i>
																	Submit and restart
																</button>

															</div>
														</div>
													</div>
												</form>
									
												</div><!-- /widget-main -->
											</div><!-- /widget-body -->
										</div><!-- /widget-box -->
									</div>
								
								</div>

								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div><!-- /.main-content -->
			</div><!-- /.main-container-inner -->

			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="icon-double-angle-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->
	</body>
</html>
