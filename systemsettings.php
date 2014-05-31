<?php
require_once 'class/accesscontrol.class.php';
require_once 'config/define.php';
require_once 'class/configmanager.class.php';

if (!AccessControl::hasAccess()){
	header('Location: login.php');
	die();
}

$configMan = ConfigurationManager::instance();
$sysSettings = $configMan->getSystemSettings();
$prodSettings = $configMan->getProductSettings();

?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php';?>
    
    <body class="cover">

        <div class="wrapper">

           <?php include 'includes/banner.php';?>

            <!-- BODY -->
            <div class="body">

                <?php require_with('includes/menu.php', array('selected' => 'settings'));?>

                <section class="content">
                    
<ol class="breadcrumb">
    <li class="active"></li>
</ol>

<div class="header">
    <div class="col-md-12">
        <h3 class="header-title">System settings</h3>
        <p class="header-info">Change system wide settings</p>
    </div>
</div>

<!-- CONTENT -->
<div class="main-content">
	 <div class="row">
        <div class="col-md-6">
            <div class="panel ">
                <div class="panel-heading">
                    <h3 class="panel-title">User Settings</h3>
                </div>
                <div class="panel-body">
                	<form id="usform" action="/ajaxController.php?action=ChangePassword">
                		<div class="form-group">
		                    <label for="us_password">Change password</label>
		                    <input type="password" class="form-control" id="us_password" name="us_password" >
		                    <span class="help-block"> 
		                    </span>
		                </div>
		                <div class="form-group">
		                    <label for="us_confirm">Confirm password</label>
		                    <input type="password" class="form-control" id="us_confirm" name="us_password" >
		                    <span class="help-block"> 
		                    </span>
		                </div>
		                <div class="form-group">
		                	<button type="submit" class="btn btn-primary">Save password</button>
		                </div>
                	</form>                	
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel ">
                <div class="panel-heading">
                    <h3 class="panel-title">System Settings</h3>
                </div>
                <div class="panel-body">
                	<form id="ssform" action="/ajaxController.php?action=SaveSystemSettings">
                        <div class="form-group">
                            <label for="ss_updateurl">Chip count</label>
                            <input class="form-control" id="ss_chipcount" value="<?= $prodSettings->chipcount;?>" name="ss_chipcount" value="" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="The chipcount of the machine. do not change this if you don't know the value">
		                    <span class="help-block"> 
		                    </span>
                        </div>
		                <div class="form-group">
		                    <label for="ss_updateurl">Update URL</label>
		                    <input class="form-control" id="ss_updateurl" name="ss_updateurl" value="" data-toggle="tooltip" data-trigger="focus" title="" data-placement="auto left" data-container="body" type="text" data-original-title="The url of the update location. When you're a BETA user you will get development builds">
		                    <span class="help-block"> 
		                    </span>
		                </div>
		                
		                <div class="form-group">
		                	<button type="submit" class="btn btn-primary">Save settings</button>
		                </div>
                	</form>                	
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
       <script type="text/javascript">
			$("#usform").submit(function (event) {
				event.preventDefault();
				$("div").removeClass("has-error");
				if ($("#us_password").val() == '' && $("#us_password").val() == '') {
					$("#us_password").parent().addClass("has-error");
					$("#us_password").next(".help-block").html("Both password Fields are mandatory");
				}else if ($("#us_password").val() != $("#us_confirm").val()){
					$("#us_password").parent().addClass("has-error");
					$("#us_confirm").parent().addClass("has-error");
					$("#us_confirm").next(".help-block").html("Passwords doesn't match");
				}else{
					//validated
					//ajax call
					$.post( $("#usform").attr("action"), {
						password: $("#us_password").val()
						} , function( data ) {
					  if (data.STATUS == 'NOTOK') {
						$("#us_confirm").parent().addClass("has-error");
						$("#us_confirm").next(".help-block" ).html( data.MESSAGE );
					  }else{
							$("#us_confirm").next(".help-block" ).html("Password has been changed");
						}
					}, "json").fail(function() {
						$("#us_confirm").parent().addClass("has-error");
						$("#us_confirm").next(".help-block" ).html("System error, CONTROLA died?");
					  });
				} 
			});

			$("#ssform").submit(function (event) {
					event.preventDefault();
					var chipcount = $("#ss_chipcount");
					var updateUrl = $("#ss_updateurl");
					var prodname = $("#ss_prodname");
					var warp = $("#ss_warp");
					$("div").removeClass("has-error");
					if (chipcount.val() == '' || isNaN(chipcount.val())) {
						chipcount.parent().addClass("has-error");
						chipcount.next(".help-block").html("Must not be empty and must be numeric value");
					}else{

						//validated, save
						$.post( $("#ssform").attr("action"), {
							chipcount: chipcount.val(),
							updateurl : updateUrl.val(),
							prodname : prodname.val(),
							warp : warp.val()
							} , function( data ) {
						  if (data.STATUS == 'NOTOK') {
							  updateUrl.parent().addClass("has-error");
							  updateUrl.next(".help-block" ).html( data.MESSAGE );
						  }else{
							  updateUrl.next(".help-block" ).html("System settings saved");
							}
						}, "json").fail(function() {
							  updateUrl.parent().addClass("has-error");
							  updateUrl.next(".help-block" ).html( 'System error, CONTROLA died?' );
						  });
					}
					
				});
       </script>
    </body>
</html>