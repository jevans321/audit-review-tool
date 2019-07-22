    <div id="dash-container">
        <select class="site-select-box"  onchange="getSiteResults($(this).val())"> <!-- can you simply change this function on the content change? -->
            <option value="" disabled="" selected="">Select Site</option>
            <option value="can">CAN</option>
            <option value="cdl">CDL</option>
            <option value="hur">HUR</option>
            <option value="isl">ISL</option>
            <option value="rtp">RTP</option>
            <option value="svl">SVL</option>      
        </select>
        <div id="generateButton" title="Generate New Audit Test" onclick="createAuditMasterRecord($('.site-select-box').val())">G</div>
    
        <div class="dash-content">
        </div>
    </div>

  <script>

    function getSiteResults(selectedSite){

		var data = {
			"action": "getSiteResults",
			"site": selectedSite
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                console.log("getSiteResults data: ", data);

                if(data.status !== "error") {
                    if(data === "no results") {
                        $(".dash-content").html("<div>No audit review results listed</div>");
                    } else {
                        $(".dash-content").html(data);
                    }

                } else {
                    bootstrapAlert("danger", "Error: " + data.message);
                }
            
			} //success
		}); //end ajax
    }

    function retrieveOldTest(masterId, site) { // pull masterId from UI row
        // set createdMasterId variable
        var url = "details.php?id=" + masterId + "&site=" + site + "&edit=true";
        // redirect to created url
        window.location.replace(url);
        // isForwardAndReverseCheckComplete(masterId);
    }
    
    function createAuditMasterRecord(site){
        if(site === 'cdl') {
            bootstrapAlert('danger', 'CDL site has no Grid Locations');
            return;
        } else if(!site) {
            bootstrapAlert('info', 'Please select site location');
            return;
        }
        $("#dash-container").hide();
        $(".reverse-serial-list").empty();
		console.log("Inside createAuditMasterRecord");
		// $( ".admin-content" ).empty();
		// $(".loader").show();
		var data = {
			"action": "createAuditMasterRecord",
            "site": site
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {	
                console.log("Object from createAuditMasterRecord: ", data);
                if(data.status !== "error") {
                    var url = "details.php?id=" + data[1] + "&site=" + site;
                    // redirect to created url
                    window.location.replace(url);

                } else {
                    bootstrapAlert("danger", "Error: " + data.message);
                }

			} //success
		}); //end ajax
    }

    $(function(){

    });
  </script>