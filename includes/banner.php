<!-- HEAD NAV -->
<?php session_start();?>
<div class="navbar navbar-default navbar-static-top navbar-main" role="navigation">
	<div class="navbar-header">
	<a class="navbar-brand" href="index.php"><div id="logo"></div></a>
	</div>
	<ul class="nav navbar-nav navbar-right">
		<li class="dropdown">
                        <a href="#" class="dropdown-toggle avatar pull-right" data-toggle="dropdown">
                            <span class="hidden-small"><i class="fa fa-user fa-fw"></i><?php echo $_SESSION["user"] ?><b class="caret"></b></span>
                        </a>
                        <ul class="dropdown-menu pull-right">
                            <li><a href="systemsettings.php"><i class="fa fa-gear"></i>Account Settings</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="fa fa-sign-out"></i>Logout</a></li>
                        </ul>
                    </li>
		<li class="visible-xs">
			<a href="#" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar">
			<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-bars"></i>
			</a>
		</li>
		
	</ul>
</div>
<!-- END: HEAD NAV -->