<?php 
//   include ("routes/checkSession.php"); 
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="./public/css/main.css" rel="stylesheet">
    <script src="./public/lib/jquery-3.3.1.min.js"></script>
	<script src="./public/lib/jquery-ui.min.js"></script>
    <title>Document</title>
</head>
<body>
    <div id="dash-container">
        <div>Dashboard Select Box</div>
        <select class="site-select-box"  onchange="getSiteResults($(this).val())"> <!-- can you simply change this function on the content change? -->
            <option value="" disabled="" selected="">Select Site</option>
            <option value="can">CAN</option>
            <option value="cdl">CDL</option>
            <option value="hur">HUR</option>
            <option value="isl">ISL</option>
            <option value="rtp">RTP</option>
            <option value="svl">SVL</option>      
        </select>
        <!-- <div id="generate-btn">G</div> -->
    
        <div class="dash-content">
        </div>
    </div>
  <script>
   
   function getSiteResults(selectedSite){
		console.log("getSiteResults selectedSite: ", selectedSite);
		// if(!selectedSite) return;
		var data = {
			"action": "getSiteResults",
			"site": selectedSite
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/dashboard_class.php",
			data: data,
			success: function(data) {
                // var code = "<div class='site-results'></div>";
                console.log("get site results: ", data);
                $(".dash-content").html(data);
			} //success
		}); //end ajax
    }

    $(function(){


    });
  </script>
</body>
</html>