<!-- Header Navigation Section -->
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
		<span class="icon-main"></span>
        <a class="navbar-brand" href="#">Capital Request</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
		<?PHP if((isset($_SESSION["version"]))){ ?>	
          <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
              <a class="nav-link" href="#">Link1<span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Link2</a>
            </li>
          </ul>

          	<a class="nav-link btn btn-outline-success" href="#" id='logOut' onclick="logOut('logoutframe','https://w3id.alpha.sso.ibm.com/pkmslogout')">Sign out</a>
			<iframe name='iFrameName' height='0' width='0' style='display:none;' id='logoutframe' sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
		  <?PHP } ?>

        </div>
      </nav>
	
    
	<div id="alert_placeholder"></div>
	<!-- End Header Navigation Section -->
    <div class="container-fluid">
      <div class="row" style="margin-left: 100px; ">


<!-- <div class='hide' id="infoMessage">
	<ul>
	<li class'messageTitle'>2018 IBM Silicon Valley Lab Network Outage</li>
    <li class='messageDetails'>Outage is scheduled for Feb 2nd-4th<br> Starting Friday, Feb 2nd at 12pm (PST) we will begin the remediation<br> action to address the security vulnerabilites known as Spectre/Metldown</li>
	</ul>
</div> -->
<!-- <script>
hideMessage();
</script> -->