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
			  
			<?PHP if($_SESSION["admin"] == 'yes'){ ?>
			<li class="nav-item" data-nav="admin">
              <a class="nav-link" href="admin" onclick="navSelect('admin')">Admin</a>
            </li>
			<?PHP } ?>
			  
            <li class="nav-item" data-nav="index">
              <a class="nav-link" href="index">Home<span class="sr-only" onclick="navSelect('index')">(current)</span></a>
            </li>
			  
          </ul>

          	<a style="margin-right: 112px;" class="btn btn-outline-primary" href="#" id='logOut' onclick="logOut('logoutframe','https://w3id.alpha.sso.ibm.com/pkmslogout')">Sign out</a>
			<iframe name='iFrameName' height='0' width='0' style='display:none;' id='logoutframe' sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
      <?PHP } ?>
        </div>
        <!-- <div class="search-form">
            <form onsubmit="getRackNumber($('#search').val(), $('#search-select-box').val()); return false;" method="post" class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" id="search" type="search" placeholder="Search Requests" aria-label="Search">
                <button class="btn btn-outline-primary my-2 my-sm-0" type="submit">Search</button>
            </form>
        </div> -->
    </nav>

	<div id="alert_placeholder"></div>
	<!-- End Header Navigation Section -->
    <div class="container-fluid">
      <div class="row" style="">

<!-- <div class='hide' id="infoMessage">
	<ul>
	<li class'messageTitle'>2018 IBM Silicon Valley Lab Network Outage</li>
    <li class='messageDetails'>Outage is scheduled for Feb 2nd-4th<br> Starting Friday, Feb 2nd at 12pm (PST) we will begin the remediation<br> action to address the security vulnerabilites known as Spectre/Metldown</li>
	</ul>
</div> -->
<!-- <script>
hideMessage();
</script> -->