<?php 
  include ("routes/checkSession.php"); 
?>
<!doctype html>
<html lang="en">
  <head>
	<?php include ("views/templates/head.php"); ?>
	<!--<script type="text/javascript" src="public/lib/dropzone.js"></script>-->
  </head>

  <body>
  		<!-- (isset($_SESSION["version"]))? include ("views/templates/navigation.php"): include ("views/templates/navigation-login.php") -->
		<?php include ("views/templates/navigation.php") ?>
		
		<!-- Body Content -->
		<!-- (isset($_SESSION["version"]))? include ("views/index/index-content.php"): include ("views/index/index-content-login.php") -->
		<?php include ("views/reporting/reporting-content.php") ?>
		
		<!-- End Body Content -->
      </div>
    </div>

	 <!-- scripts Section -->

    <!-- <script src="./public/lib/popper.min.js"></script>
    <script src="./public/lib/bootstrap/js/bootstrap.min.js"></script> -->
 
    <script>
	
	function logOut(iframeName, url) {
		$('#' + iframeName).attr('src',url).on('load', function(){
			//console.log('calling log out');
			$.get("/callback/clearSession.php",function(data){
				window.location.replace("http://<?php echo $_SERVER['HTTP_HOST'];?>");
			});
		});
	}
		
	function toolTipInfo(type){
		switch(type){
			case 'add':
				$('.icon-pmr').attr("data-original-title","My PMR's");
				break;
			case 'remove':
				$('.icon-pmr').attr("data-original-title","");
				break;
		}
	}
			
	$(function () {
  		$('[data-toggle="tooltip"]').tooltip()
	})
		
	$(document).ready(function() {
		<?php (isset($_SESSION["version"]))? $sessionCheck = $_SESSION["version"] : $sessionCheck = "no";?>
		if('<?php echo $sessionCheck;?>' != 'castledog') logOut('logoutframe','https://w3id.alpha.sso.ibm.com/pkmslogout');

	});
		
    </script>
</body></html>