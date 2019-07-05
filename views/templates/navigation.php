<!-- Header Navigation Section -->
<nav class="navbar navbar-expand-md navbar-dark fixed-top">
		<span class="icon-main"></span>
        <span class="navbar-brand">DevIT Audit</span>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">

          <ul class="navbar-nav mr-auto">
			  
            <li class="nav-item" data-nav="audit">
              <a class="nav-link" href="audit.php">Audit<span class="sr-only" onclick="navSelect('audit')">(current)</span></a>
            </li>

            <li class="nav-item" data-nav="reporting">
              <a class="nav-link" href="reporting.php" onclick="navSelect('reporting')">Reporting</a>
            </li>
			  
          </ul>

        </div>
        <div>

            <!-- <div class="search-form">
              <form onsubmit="getGridLocation($('#search').val(), $('#search-select-box').val()); return false;" method="post" class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" id="search" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-primary my-2 my-sm-0" type="submit">Search</button>
              </form>
            </div> -->
        </div>
    </nav>

	<div id="alert_placeholder"></div>
	<!-- End Header Navigation Section -->
    <div>
      <div class="sub-container">

<!-- <div class='hide' id="infoMessage">
	<ul>
	<li class'messageTitle'>2018 IBM Silicon Valley Lab Network Outage</li>
    <li class='messageDetails'>Outage is scheduled for Feb 2nd-4th<br> Starting Friday, Feb 2nd at 12pm (PST) we will begin the remediation<br> action to address the security vulnerabilites known as Spectre/Metldown</li>
	</ul>
</div> -->
<!-- <script>
hideMessage();
</script> -->