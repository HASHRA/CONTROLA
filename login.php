<!DOCTYPE html>
<html lang="en">
	<?php include_once 'config/define.php';?>
    <?php include 'includes/head.php';?>

           
<body class="cover">

	<div class="login-wrap">
		<div class="brand">
	        <div id="logowhite"></div>
	        <div class="center"><?php echo PRODUCT_NAME?></div> 
	    </div>
	    <div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">Sign In</h3>
				</div>
				<div class="panel-body">
					Default user name is hashra and password is hashra.
					<form action="index.html">
						<div class="form-group">
							<input class="form-control" id="username" name="username"
								placeholder="Enter user name">
						</div>
						<div class="form-group">
							<input type="password" class="form-control"
								id="password" name="password" placeholder="password">
							<span class="help-block">
		                       
		                    </span>
						</div>
						<button type="submit" id="loginButton" class="btn btn-primary">Login</button>
					</form>
				</div>
			</div>
			<div class="brand center"><em class="tiny">v<?php echo VERSION?></em></div>
	</div>
	
</body>
<?php include 'includes/footer.php';?>
<script type="text/javascript">


	//Attach a submit handler to the form
	$( "form" ).submit(function( event ) {
	
	// Stop form from submitting normally
	event.preventDefault();
	
		//form val
		if ($("#username").val() == '' || $("#password").val() == ''){
			$("#username").parent().addClass("has-error");
			$("#password").parent().addClass("has-error");
			$(".help-block").html("User name and password are mandatory fields");
		}else{
	
			//ajax call
			$.post( "ajaxController.php?action=Login", {
				username: $("#username").val() , 
				password : $("#password").val()
				} , function( data ) {
			  if (data.STATUS == 'NOTOK') {
				$("#username").parent().addClass("has-error");
				$("#password").parent().addClass("has-error");
				$( ".help-block" ).html( data.MESSAGE );
			  }else{
					document.location.href = "index.php";
				}
			}, "json").fail(function() {
				$("#username").parent().addClass("has-error");
				$("#password").parent().addClass("has-error");
				$( ".help-block" ).html( "System error, is the PI alive?" );
			  });
	
		}
		});
</script>
</html>