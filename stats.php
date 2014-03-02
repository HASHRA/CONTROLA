<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';

$cache = new Cache(PATH_CACHE);
$jsarr = "";
$jslabel1 = "";
$jslabel2 = "";
$minarr1 = 0;
$maxarr1 = 0;
$minarr2 = 0;
$maxarr2 = 0;
$time = time();
$stats = $cache->get("graphui");
echo time() - $time;
$arr = array();
$arr["total"] = array();
if(!empty($stats["total"]) && count($stats["total"]) >= 2)
{
	$minarr1 = 1000;
	foreach($stats["total"] as $time => $stat)
	{
		$arr["total"][] = "[".($time * 1000).", ".$stat."]";
	}
	$jsarr .= "var d1 = [".join(", ", $arr["total"])."];";
	$jslabel1 .= '{ label: "Total Hashrate (Kh/s)", data: d1 },';
	$minarr1 = round(0.95 * min($stats["total"]));
	$maxarr1 = round(1.05 * max($stats["total"]));
	$maxarr2 = 0;
	$minarr2 = 1000;
	foreach($stats["individual"] as $dev => $stats)
	{
		$arr[$dev] = array();
		foreach($stats as $time => $stat)
		{
			$arr[$dev][] = "[".($time * 1000).", ".$stat."]";
		}
		$jsarr .= "var d".($dev + 2)." = [".join(", ", $arr[$dev])."];";
		$jslabel2 .= '{ label: "LTC '.$dev.' (Kh/s)", data: d'.($dev + 2).' },';
		$minarr2 = min(array($minarr2, min($stats)));
		$maxarr2 = max(array($maxarr2, max($stats)));
	}
	$minarr2 = round(0.95 * $minarr2);
	$maxarr2 = round(1.05 * $maxarr2);
}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Dashboard - Lightning Asic Admin</title>

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
			#navbar
			{
				background: #262626; /* Old browsers */
				background: -moz-linear-gradient(top, #262626 0%, #545454 100%); /* FF3.6+ */
				background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#262626), color-stop(100%,#545454)); /* Chrome,Safari4+ */
				background: -webkit-linear-gradient(top, #262626 0%,#545454 100%); /* Chrome10+,Safari5.1+ */
				background: -o-linear-gradient(top, #262626 0%,#545454 100%); /* Opera 11.10+ */
				background: -ms-linear-gradient(top, #262626 0%,#545454 100%); /* IE10+ */
				background: linear-gradient(to bottom, #262626 0%,#545454 100%); /* W3C */
				filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#262626', endColorstr='#545454',GradientType=0 ); /* IE6-9 */
				height: 100px;
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
								Statistics
								<small>
									<i class="icon-double-angle-right"></i>
									overview &amp; stats
								</small>
							</h1>
						</div><!-- /.page-header -->

								<!-- PAGE CONTENT ENDS -->
							<!-- /.col -->
							<div class="row">
								<div class="col-sm-8">
									<div class="widget-box transparent">
										<div class="widget-header widget-header-flat">
											<h4 class="lighter">
												<i class="icon-signal"></i>
												Total Hashrate
											</h4>

											<div class="widget-toolbar">
												<a href="#" data-action="collapse">
													<i class="icon-chevron-up"></i>
												</a>
											</div>
										</div>

										<div class="widget-body">
											<div class="widget-main padding-4">
												<div id="totalhash-chart"></div>
											</div><!-- /widget-main -->
										</div><!-- /widget-body -->
									</div><!-- /widget-box -->
								</div>
							</div>
							<div class="row">
								<div class="col-sm-8">
									<div class="widget-box transparent">
										<div class="widget-header widget-header-flat">
											<h4 class="lighter">
												<i class="icon-signal"></i>
												Individual Hashrate
											</h4>

											<div class="widget-toolbar">
												<a href="#" data-action="collapse">
													<i class="icon-chevron-up"></i>
												</a>
											</div>
										</div>

										<div class="widget-body">
											<div class="widget-main padding-4">
												<div id="individualhash-chart"></div>
											</div><!-- /widget-main -->
										</div><!-- /widget-body -->
									</div><!-- /widget-box -->
								</div>
							</div>
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div><!-- /.main-content -->
			</div><!-- /.main-container-inner -->

			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="icon-double-angle-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->
				<script type="text/javascript">
			jQuery(function($) {
				<?php echo $jsarr ?>
			
				var totalhash_chart = $('#totalhash-chart').css({'width':'100%' , 'height':'200px'});
				$.plot("#totalhash-chart", [
					<?php echo $jslabel1 ?>
				], {
					hoverable: true,
					shadowSize: 0,
					series: {
						lines: { show: true },
						points: { show: false },
						hoverable: true,
					},
					xaxis: {
						mode: "time",
						timeformat: "%H:%M",
						tickSize: [60, "minute"],
					},
					yaxis: {
						min: <?php echo $minarr1 ?>,
						max: <?php echo $maxarr1 ?>,
					},
					grid: {
						backgroundColor: { colors: [ "#fff", "#fff" ] },
						borderWidth: 1,
						borderColor:'#555'
					},
					legend: {
						position: "nw",
					},
				});
				
				var individualhash_chart = $('#individualhash-chart').css({'width':'100%' , 'height':'500px'});
				$.plot("#individualhash-chart", [
					<?php echo $jslabel2 ?>
				], {
					hoverable: true,
					shadowSize: 0,
					series: {
						lines: { show: true },
						points: { show: false },
						hoverable: true,
					},
					xaxis: {
						mode: "time",
						timeformat: "%H:%M",
						tickSize: [60, "minute"],
					},
					yaxis: {
						min: <?php echo $minarr2 ?>,
						max: <?php echo $maxarr2 ?>,
					},
					grid: {
						backgroundColor: { colors: [ "#fff", "#fff" ] },
						borderWidth: 1,
						borderColor:'#555'
					},
					legend: {
						position: "nw",
					},
				});
			});
		</script>
	</body>
</html>
