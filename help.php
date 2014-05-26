<?php
require_once 'config/define.php';
require_once 'class/configmanager.class.php';
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php';?>
    
    <body class="cover">

        <div class="wrapper">

           <?php include 'includes/banner.php';?>

            <!-- BODY -->
            <div class="body">

                <?php require_with('includes/menu.php', array('selected' => 'help'));?>

                <section class="content">
                    
<ol class="breadcrumb">
    <li class="active"><i class="fa fa-home fa-fw"></i> Home</li>
</ol>

<div class="header">
    <div class="col-md-12">
        <h3 class="header-title">Help</h3>
        <p class="header-info">Work in progress</p>
    </div>
</div>

<!-- CONTENT -->
<div class="main-content">
	 <div class="row">
        <div class="col-md-12">
            <div class="panel ">
                <div class="panel-heading">
                    <h3 class="panel-title">Work in progress</h3>
                </div>
                <div class="panel-body">
                	Work in progress                	
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