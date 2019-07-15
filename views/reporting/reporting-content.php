<div class="maincontainerfull">	
    <div id="message" class="hideMessage"></div>
    <div class="ipScan">
        <div class="pageTitle">Search for missing IP's in CEP by IP range</div>
        <div>
            <input id="startIp" type="text" placeholder="starting IP"> <input id="endIp" type="text" placeholder="ending IP"><span><button onclick="getIpsNotInCep($('#startIp').val(), $('#endIp').val())" class="reportButton ipScanButton">Search</button></span>
        </div>
    </div>
	<div class="actions">
		<div class="pageTitle">Select a Site &amp; Report Type</div>
        <span>
		  <select id="siteLocation" class="custom-select form-control">

          <option value="CAN">CAN</option><option value="CDL">CDL</option><option value="HUR">HUR</option><option value="ISL">ISL</option><option value="RTP">RTP</option><option value="SVL">SVL</option></select>
		</span>
        <span>
		  <select id="searchType" class="custom-select form-control">
              <option value="miss_dev_con_active">Missing development contacts on active systems</option>
              <option value="ip_no_data">Ips with no other data</option>
              <option value="miss_ip_status">Records with missing ip_address and missing system_status</option>
              <option value="ip_not_in_cep">Records with ips that are not in cep_ips</option>
              <option value="miss_vlan_def">Missing vlan spec definitions</option>
              <option value="dev_con_miss_bp">Development contact records that are missing from bluepages</option>
              <option value="no_access_reg_sec">No access, but registered with security</option>
            </select>
		</span>
        <span><button class="reportButton reportingSearch">Search</button></span>
       
	</div>
	<div class="icon-loader hide"></div>
	<div id="page-content">
	</div>
</div>
  <script>
   function getsites(){
		//returns list of faq's from the database
		var data = {
		  "action": "getAllSites"
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
		  type: "POST",
		  dataType: "json",	//only use if return type is json
		  url: "./routes/reporting_class.php", //Relative or absolute path to response.php file
		  data: data,
		  success: function(data) {
			 if(data[0]['system_site']){
				for(i=0;i< data.length; i++){
				 	$("#siteLocation").append(new Option(data[i]['system_site'], data[i]['system_site']));
			 	}
			 }
		  }
		});
		//return false;
	}

	Date.prototype.yyyymmdd = function() {
	  var mm = this.getMonth() + 1; // getMonth() is zero-based
	  var dd = this.getDate();
	  var hours = this.getHours().toString();
	  var minutes = this.getMinutes().toString();
	  var seconds = this.getSeconds().toString();

	  return [this.getFullYear(),
			  (mm>9 ? '' : '0') + mm,
			  (dd>9 ? '' : '0') + dd+'-',
			  + hours + minutes + seconds
			 ].join('');
	};


	// Lori Download:
	function downloadTableData() {

	    var tabName = "reportingTable";
	    var date = new Date();
	    var filename = "invision-report-data-"+date.yyyymmdd()+".csv"
    
    	var tabData = "";
    	$("#" + tabName).find('tr').each(function (rowIndex, r) {
       
        	$(this).find('th,td').each(function (colIndex, c) {
            	tabData += c.textContent + ",";
        	});
        
        	tabData += "\n";
    	});
    
        data = encodeURI(tabData);

        link = document.createElement('a');
        link.setAttribute('href', 'data:text/csv;charset=utf-8,' + data);
        link.setAttribute('download', filename);
        link.click();    
	}
	
	function csvDownload(searchvalues){
		var date = new Date();
		
		var data = {
				"action": "getDownloadInfo",
				"searchvalues": searchvalues
			};

			data = $(this).serialize() + "&" + $.param(data);
			$.ajax({
				type: "POST",
				url: "./routes/reporting_class.php", //Relative or absolute path to response.php file
				data: data,
				success: function(data) {
					//console.log(data);
					downloadCSV(data, { filename: "invision-search-data-"+date.yyyymmdd()+".csv" });
				}  
			});
	}
	
	function downloadCSV(csvData, args) {  
        var data, filename, link;
		
		var csv = convertAssetsCSV({
            data: csvData
        });

		
        if (csv == null) return;

        filename = args.filename || 'export.csv';

        if (!csv.match(/^data:text\/csv/i)) {
            csv = 'data:text/csv;charset=utf-8,' + csv;
        }
        data = encodeURI(csv);

        link = document.createElement('a');
        link.setAttribute('href', data);
        link.setAttribute('download', filename);
        link.click();
    }
	
	function convertAssetsCSV(args) {  
        var result, ctr, keys, columnDelimiter, lineDelimiter, data;
		//console.log(`converting: ${args}`)
        data = args.data || null;
        if (data == null || !data.length) {
            return null;
			//console.log(`null being returned`);
        }

        columnDelimiter = args.columnDelimiter || '","';
        lineDelimiter = args.lineDelimiter || '"\r\n"';
		//console.log(`data: ${data[0]}`);
        keys = Object.keys(data[0]);

        result = '"';
        result += keys.join(columnDelimiter);
        result += lineDelimiter;

		var count = 0;
        data.forEach(function(item) {
            ctr = 0;
            keys.forEach(function(key) {
                if (ctr > 0) result += columnDelimiter;
					//console.log(item[key]);
					//if (count == 0) {
					//	result += `No asset data found`;
					//}else{
						result += item[key];
					//console.log(`${item[key]}`);
					//}
                	
                ctr++;
				count++;
            });
			count = 0;
            result += lineDelimiter;
        });

        return result;
    } 
	
	function searchAssets(){
		//console.log(`type: ${infoType}`);
		var site = $('#siteLocation').val();
        var searchType = $('#searchType').val();
			
			
		if(site == '' & searchType == ''){
			animateMessage('Warning', 'Please site and search type','');
		}else{
			$('.pageTitle').addClass('hide');
			$('#page-content').html('');
			$('.icon-loader').removeClass('hide');
			var data = {
			  "action": "getAssets",
			  "site" : site,
			  "type" : searchType
			};
				data = $(this).serialize() + "&" + $.param(data);
				$.ajax({
				  type: "POST",
				  //dataType: "json",	//only use if return type is json
				  url: "./routes/reporting_class.php", //Relative or absolute path to response.php file
				  data: data,
				  success: function(data) {
					  $('.icon-loader').addClass('hide');
					  //console.log(data.status);
					if(data.status != 'error'){
						
						$('#page-content').html(data);

						$( "#page-content" ).on( "keyup", "input.filterSearch", function(event) {
							var key = event.keyCode || event.charCode;
							if( key == 8 || key == 46 ){
								//checking for backspace or delete key
								var $rows = $('.contentTable tbody tr');
							}else{
								var $rows = $('.contentTable tbody tr:visible');
							}
							//console.log( $( this ).val() );
							var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();
							$rows.show().filter(function() {
							 var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
							 return !~text.indexOf(val);
							}).hide();
						});

					}else{
						$('#page-content').html(data.details);
					}

				  } //Success End 
				});
			} //End check for selects 
    }
    
    function getIpsNotInCep(startIp, endIp){
        // getIpsNotInCep('9.30.36.152', '9.30.36.160')
        console.log("start & end: ", startIp, endIp);
		var data = {
				"action": "getIpsNotInCep",
                "start": startIp,
                "end": endIp
			};

			data = $(this).serialize() + "&" + $.param(data);
			$.ajax({
				type: "POST",
				url: "./routes/reporting_class.php", //Relative or absolute path to response.php file
				data: data,
				success: function(data) {
					console.log("getIpsNotInCep data: ", data);
					$("#page-content").html(data);
				}  
			});
	}
	
	$(document).ready(function() {
		// checkAccess(); //used for checking continued user access
        getsites();
		console.log('access: '+'read');
		
		$('.reportingSearch').on('click', function(){
			searchAssets();
		}); //infoSearch End

    }); //document ready
	
	document.body.addEventListener( 'keyup', function (e) {
	  if ( e.keyCode == 13 ) {
		// Simulate clicking on the submit button.
		searchDetailed();
	  }
	});
  </script>
</body>
</html>