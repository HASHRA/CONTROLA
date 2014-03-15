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
        <h3 class="header-title">CONTROLA </h3>
        <p class="header-info"></p>
    </div>
</div>


<!-- CONTENT -->
<div class="main-content">
	 <div class="row">
        <div class="col-md-12">
            <div class="panel ">
                <div class="panel-heading">
                    <h3 class="panel-title">Update</h3>
                </div>
                <div class="panel-body">

                	<div class="alert alert-success">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="fa fa-check-circle"></i>
                            <?php 
		                	exec("curl https://bitbucket.org/api/1.0/repositories/purplefox/hashra-public-firmware" , $out);
		                	$obj = json_decode($out[0]);
		                	
		                	$age =   getlastmod() - strtotime($obj->last_updated) ;
		                	
		                	if ($age > 0 ) {
								echo "<p>You have the most current version</p>";
								
							}else {
								echo "A newer version is available, do you want to Update? <br/>";
							}
		                	?>
		               </div>
					 <a class="btn btn-primary" id="updateLink" href="#">
                        Update me
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</div>
 <!-- Modal -->
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
<!--                                     <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> -->
                                    <h4 class="modal-title" id="myModalLabel">I'm now updating... Please wait for me.</h4>
                                </div>
                                <div id="modalContent" class="modal-body">
                                   Please hold on... Do not close this dialog box before I'm finished, Thanks.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" id="update-button-close" class="btn btn-default" disabled data-dismiss="modal">Cannot close this yet!</button>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->
<!-- END: CONTENT -->
                </section>
            </div>
            <!-- END: BODY -->
        </div>
        
        
       <?php include 'includes/footer.php';?>
       
         <script>
        $('#updateLink').click(function(e) {

    		
      	  e.preventDefault();
        	  var url = 'doupdate.php'
        	  $("#myModal").modal({
					backdrop:'static'
            	  });
        	  $("#myModal").on("hidden.bs.modal" , function () {
            	  	setTimeout(function(){
							document.location.href = 'index.php';
                	  	}, 10000);
            	  });
        	  $.get(url, function(data) {
        	      $('#modalContent').html(data);
        	  });
        	});
        </script>
    </body>
</html>