<?php

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
        <h3 class="header-title">UPDATE</h3>
        <p class="header-info">updating firmware software from git</p>
    </div>
</div>

<!-- CONTENT -->
<div class="main-content">
	 <div class="row">
        <div class="col-md-12">
            <div class="panel ">
                <div class="panel-heading">
                    <h3 class="panel-title">SCRYPT Miners hashrate <b class="value"><?php echo $totalhash ?></b> Kh/s</h3>
                </div>
                <div class="panel-body">
                	...fetching...
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