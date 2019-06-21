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
    <div id="top-container">
        <div>  
            <span></span><select class="site-select-box" onchange="createAuditMasterRecord($(this).val())"> <!-- can you simply change this function on the content change? -->
            <option value="" disabled="" selected="">Select Site</option>
            <option value="can">CAN</option>
            <option value="cdl">CDL</option>
            <option value="hur">HUR</option>
            <option value="isl">ISL</option>
            <option value="rtp">RTP</option>
            <option value="svl">SVL</option>
            </select>
        </div>
        <div id="test-menu">
            <div><span onclick="toggleContainers('#random-check-container')">Random Check</span><span>dot</span></div>
            <div><span onclick="toggleContainers('#reverse-check-container')">Reverse Check</span><span>dot</span></div>
            <div><span>Test 3</span><span>dot</span></div>
            <div><span>Test 4</span><span>dot</span></div>
            <div><span onclick="openSubmitFinalModal(createdMasterId)">Submit Final Results</span></div>
        </div>
    </div>
    <div class="left-container">
        <div id="random-check-container">
            <div class="sideHeaderTitle"><span class="testHeaderText">Test 1:</span> Random System Check</div>
            <div><h5 class="serial-check-header">System Check <span id="current-asset"></span> of <span id="total-assets"></span></h5></div> 
            <div><form id="searchForm" onsubmit="openSerialSearchModal($('#search').val(), createdMasterId, 'audit_forward'); return false;" method="post">
                <input type="text" id="search" placeholder="Enter Serial Number" name="search"><button class="searchButton" type="submit">Submit<img id="" src="public/images/search_icon_2.png" alt=""></button>
            </form></div>
            <div class="top-serial-list">
            </div>
            <div class="system-found-div">
                <h3>System Found?</h3>
                <!-- <select class="system-found-select-box" onchange="updateGradeInMaster(createdMasterId, $(this).val(), 'test_1')"> -->
                <select class="system-found-select-box" onchange="updateAssetGrade('audit_forward', 'asset_found_grade', $(this).val(), createdMasterId, submittedSerial);">
                    <option value="" disabled="" selected="">Select</option>
                    <option value="pass">Yes</option>
                    <option value="fail">No</option>
                </select>
            </div>
        </div>

        <div id="reverse-check-container">
            <h4>Test 6: <span>Reverse Data Integrity Check</span></h4>
            <div class="gen-serials-btn" onclick="addRandomAssets(createdMasterId, selectedSite)">Generate Serials</div>
            <div class="serial-list">
            </div>
        </div>
    </div> <!-- End of left-container  -->
        <div id="error-container"></div>
        <div id="content-random-check">
            <div class="general-content-random-check">
            </div>
            <div class="location-content-random-check">
            </div>
            <div class="additional-tests-random-check">
                <!-- <div id="test4-div">
                    <h3>Test 4</h3>
                    <div id="purpose-btn" onclick="openPurposeModal(createdMasterId, submittedSerial)">Purpose Check</div>
                </div>
                <div id="test5-div">
                    <h3>Test 5</h3>
                    <div id="legacy-btn" onclick="openLegacyModal(createdMasterId, submittedSerial)">Legacy Check</div>
                </div> -->
            </div>
            <div class="asset-score-random-check">
            </div>
            <div class="submit-asset-random-check">
            </div>
        </div>
        <div id="content-reverse-check">
            <div class="general-content-reverse-check">
            </div>
            <div class="location-content-reverse-check">
            </div>
            <div class="asset-score-reverse-check">
            </div>
            <div class="submit-asset-reverse-check">
            </div>
        </div>
    </div>


  <script>

    var createdMasterId = 0;
    var selectedSite;
    var submittedSerial;
    var serialCheckCount = 0; // Increment count on submit
    var serialObj = {};
    /* Variables for any existing 'Forward Check' grades in db. Used in the 'openPurpose' and openLegacy' modal functions */
    var purpose1Grade, purpose2Grade, legacy1Grade, legacy2Grade;

    function toggleContainers(id) {
        // $("#random-check-container, #reverse-check-container, #content-random-check, #content-reverse-check").hide();
        $("#error-container").empty();
        /* If the Forward Check menu item selected, then calculate the Sites total number of systems that need to be checked */
        if(id === "#random-check-container") {
            $("#reverse-check-container, #content-reverse-check").hide();
            $("#random-check-container, #content-random-check").show();
            getForwardCheckSystemsTotal(selectedSite);

        } else {
            $("#random-check-container, #content-random-check").hide();
            $("#reverse-check-container, #content-reverse-check").show();
        }
        // $(id).toggle();
    }

    function getForwardCheckSystemsTotal(site) {
        var data = {
			"action": "getForwardCheckSystemsTotal",
			"site": site
        };
        
		data = $(this).serialize() + "&" + $.param(data);

		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {
                console.log("getForwardCheckSystemsTotal data: ", data);
                $("#total-assets").text(data);
                $("#current-asset").text(serialCheckCount);
                
 
			} //success
		}); //end ajax
    }

    function getAssetGradeData(serial, masterId, table){
        $("#error-container").hide();
        if(table === "audit_forward") {
            removePopup();
            // remove any previous-grade variables set for Test 4 & 5 (Purpose & Legacy Check)
            purpose1Grade = "";
            purpose2Grade = "";
            legacy1Grade = "";
            legacy2Grade = "";
        }
        if(table === "audit_reverse") {
            $("#content-random-check").hide();
            $(".asset-score-reverse-check, .general-content-reverse-check, .location-content-reverse-check").empty();
        } else {
            // If Forward Check is being done, empty content area before each query to retrieve new content from DB
            $(".asset-score-random-check, .general-content-random-check, .location-content-random-check, .additional-tests-random-check").empty();
        }

        $("#error-container").empty();
       
        submittedSerial = serial;

		var data = {
			"action": "getAssetGradeData",
            "serial": serial,
            "masterId": masterId,
            "table": table
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {
                console.log("getAssetGradeData data: ", data);

                if(data[1] === "duplicates" || data[1] === "empty") { // if duplicate or non-existent serial numbers found for forward or reverse check            
                    $("#error-container").html(data[0]);
                    $("#error-container").show();
                    // $("#content-random-check").html(data[0]); 
                    // $("#content-reverse-check").show();
                    if(table === "audit_forward") {
                        updateAssetFoundGradeAndSelectBox('fail');
                    } else {
                        $(".serial-list").html(data[2]);
                    }

                    // I can probablly replace data[2] with 'table' variable
                } else if(data[2] === "audit_forward") { // if successful 'Forward Check' serial search..
                    $(".general-content-random-check").html(data[0]);
                    $(".location-content-random-check").html(data[1]);
                    $(".additional-tests-random-check").html(data[3]);
                    $("#content-random-check").show();
                    /* Conditional for if the searched for serial already exists in table.
                        - retrieve the systems previous grades from db
                        - the user should be alerted
                        - the sytem should not be counted */
                    if(data[4] === "record exists") {
                        getAssetGrades(serial, table, masterId);
                        alert("This system has already been searched and counted");
                        return;
                    }
                    updateAssetFoundGradeAndSelectBox('pass');
                    
                } else { // if successful 'Reverse Check' serial search..
                    $(".general-content-reverse-check").html(data[0]);
                    $(".location-content-reverse-check").html(data[1]);
                    $("#content-reverse-check").show();
                    // updateAssetFoundGradeAndSelectBox('pass');
                }

                function updateAssetFoundGradeAndSelectBox(grade) {        
                    // If Forward-check: update total count of serial numbers searched
                    if(table === "audit_forward") {
                        serialCheckCount++;
                        var assetTotal = $("#total-assets").text();
                        if(serialCheckCount === Number(assetTotal)) {
                            // hide search box
                            // replace search box with "Forward Check Complete"
                            // "Total number of systems checked"
                        }
                        $("#current-asset").text(serialCheckCount);
                    }
                    // updateGradeInMaster(createdMasterId, 'fail', 'test_1');
                    updateAssetGrade(table, "asset_found_grade", grade, masterId, serial);
                    $(".system-found-select-box").val(grade);

                }
                      
                /*  Conditional below retrieves past asset pass/fail grades only if serial link has been clicked already.
                    There is an object above function, serialObj, that stores the serial #'s of each serial clicked.
                    If the object has the serial already it means the serial link has been clicked, so a query is sent
                    to retrieve previous grades selected to show in the UI. If serial is not in object, it means serial
                    has not been clicked, so the serial is added, however, retrieval query is not invoked.
                */
                if(serialObj[serial]) {
                    // run getAssetGrades function
                    getAssetGrades(serial, table, masterId);
                } else {
                    // add id to object
                    serialObj[serial] = 1;
                }
			} //success
        }); //end ajax
        $("#search").val('');
    }

    function getAssetGrades(serial, table, masterId){

		var data = {
			"action": "getAssetGrades",
            "serial": serial,
            "table": table,
            "masterId": masterId
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {
                console.log("grades data array: ", data);

                
                $(".sys-owner-select-box option[value='"+data[1]+"']").attr('selected','selected');
                $(".system-select-box option[value='"+data[2]+"']").attr('selected','selected');
                $(".host-select-box option[value='"+data[3]+"']").attr('selected','selected');
                $(".ip-select-box option[value='"+data[4]+"']").attr('selected','selected');   
                $(".room-select-box option[value='"+data[5]+"']").attr('selected','selected');
                $(".grid-select-box option[value='"+data[6]+"']").attr('selected','selected');
                $(".ssh-select-box option[value='"+data[7]+"']").attr('selected','selected');

                if(table === "audit_reverse" && !!data[8]) {
                    // Update 'asset found' value
                    $(".asset-select-box option[value='"+data[0]+"']").attr('selected','selected');
                    $(".asset-score-reverse-check").html(data[8]);
                    $(".update-date").text(data[9]);

                } else if(table === "audit_forward") { // if table is 'audit_forward', retrieve additional test values'
                     // Update 'asset found' value below forward-check search field
                    $(".system-found-select-box option[value='"+data[0]+"']").attr('selected','selected');
                    purpose1Grade = data[8];
                    purpose2Grade = data[9];
                    legacy1Grade = data[10];
                    legacy2Grade = data[11];
                    $(".update-date").text(data[12]);
                    // Test 2 through 5 scores can be added here..
                }

			} //success
		}); //end ajax
    }

    function getSiteResults(id, selectedSite){
		console.log("getSiteResults id: ", id);
		console.log("getSiteResults status: ", selectedSite);
		if(!selectedSite) return;
		var data = {
			"action": "getSiteResults",
			"assetId": id,
			"site": selectedSite
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {
                // var code = "<div class='site-results'></div>";
                $("content").html(data);
			} //success
		}); //end ajax
    }

    function updateAssetGrade(table, column, grade, masterId, serial){
		// console.log("updateAssetGrade id: ", id);
        console.log("updateAssetGrade this value: ", grade);
        if(column === "asset_found_grade" && grade === "fail") {
            // run updateAssetGrade for all Reverse Check grade columns
            updateAssetGrade(table, "system_owner_grade", grade, masterId, serial);
            updateAssetGrade(table, "system_type_grade", grade, masterId, serial);
            updateAssetGrade(table, "hostname_grade", grade, masterId, serial);
            updateAssetGrade(table, "ip_grade", grade, masterId, serial);
            updateAssetGrade(table, "room_grade", grade, masterId, serial);
            updateAssetGrade(table, "grid_grade", grade, masterId, serial);
            updateAssetGrade(table, "sshable_grade", grade, masterId, serial);
 
            $(".sys-owner-select-box option[value='fail']").attr('selected','selected');
            $(".system-select-box option[value='fail']").attr('selected','selected');
            $(".host-select-box option[value='fail']").attr('selected','selected');
            $(".ip-select-box option[value='fail']").attr('selected','selected');  
            $(".room-select-box option[value='fail']").attr('selected','selected');
            $(".grid-select-box option[value='fail']").attr('selected','selected');
            $(".ssh-select-box option[value='fail']").attr('selected','selected');
        }
		
		var data = {
			"action": "updateAssetGrade",
			"table": table,
            "column": column,
            "grade": grade,
            "masterId": masterId,
            "serial": serial
        };
        
		data = $(this).serialize() + "&" + $.param(data);

		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {
                console.log("updateAssetGrade data: ", data);
                // getAllRequests();	
                
			} //success
		}); //end ajax
    }

    function updateGradeInMaster(masterId, selectedGrade, column){
		// console.log("updateAssetGrade id: ", id);
        console.log("updateGradeInMaster this value: ", selectedGrade);
        
        // Select column name based on how many serial checks have been submited
        // if(serialCheckCount === 0) {
        //     dataColumn = "serial_1_grade";
        // } else if (serialCheckCount === 1) {
        //     dataColumn = "serial_2_grade";
        // } else {
        //     console.log("You have checked and graded 2 assets already. No more can be graded.");
        // }

		
		var data = {
			"action": "updateGradeInMaster",
			"masterId": masterId,
            "grade": selectedGrade,
            "column": column
        };
        
		data = $(this).serialize() + "&" + $.param(data);

		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {
                console.log("updateGradeInMaster data: ", data);
                // getAllRequests();	
                
			} //success
		}); //end ajax
    }
    
    function createAuditMasterRecord(site){
        if(site === 'cdl') {
            alert("CDL site has no Grid Locations");
            return;
        }
        $(".serial-list").empty();
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
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {	
                console.log("Object from from createAuditMasterRecord: ", data);
                selectedSite = data[0];
                createdMasterId = data[1];
                $("#test-menu").show();
                             
 				// $(".loader").hide();	
			} //success
		}); //end ajax
    }

    
    function addRandomAssets(masterId, site){
        $('.gen-serials-btn').hide();

        var data = {
            "action": "addRandomAssets",
            "masterId": masterId,
            "site": site
        };
        data = $(this).serialize() + "&" + $.param(data);
        $.ajax({
            type: "POST",
            url: "./routes/index_class.php",
            data: data,
            success: function(data) {	
                console.log("addRandomAssets data: ", data);
                $(".serial-list").html(data);
                // $(".loader").hide();	

            } //success
        }); //end ajax
    }
    
    function submitAsset(serial, masterId, table){

		var data = {
			"action": "submitAsset",
            "serial": serial,
            "masterId": masterId,
            "table": table
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {	
                console.log("submitAsset data: ", data);
                if(table === "audit_forward") {
                    $(".general-content-random-check, .location-content-random-check, .additional-tests-random-check").empty();
                    /* First conditional: If the system count matches the total count of systems
                       needing to be checked, end Forward-Check */
                    if($("#current-asset").text() === $("#total-assets").text()) {
                        alert("You have completed the Forward Check");
                        $("#searchForm, .system-found-div").hide();

                    } else {
                        $(".system-found-select-box").get(0).selectedIndex = 0;
                        alert("Asset Graded, Please search for next asset");

                    }

                    // $(".asset-score-random-check").html(data[0]);
                } else {
                    $(".asset-score-reverse-check").html(data[0]);
                }
                $(".serial-list").html(data[1]);
             
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }
    
    /* This Function no longer being used */
    function checkSerial(serial, masterId){
        $(".general-content-random-check, .location-content-random-check, .submit-asset-random-check").empty();
        // console.log("Inside createAuditMasterRecord");
        submittedSerial = serial;
        $("#search").val('');
		var data = {
			"action": "checkSerial",
            "serial": serial,
            "masterId": masterId
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {	
                console.log("checkSerial data: ", data);
                console.log("checkSerial data html: ", $(data).html());
                if(!data) {
                    console.log("html is empty!");
                    
                    // update test_1 column in db to fail
                    updateGradeInMaster(createdMasterId, 'fail', 'test_1')
                    // update system-found-select-box selected value to 'No'
                    $(".system-found-select-box").val('fail');

                    alert("serial could not be found");
                    return;
                }
                // update test_1 column in db to pass
                updateGradeInMaster(createdMasterId, 'pass', 'test_1')
                // update system-found-select-box selected value to 'Pass'
                $(".system-found-select-box").val('pass');
                $(".top-serial-list").html(data);

                // $(".general-content").html(data[0]);
                // $(".location-content").html(data[1]);
                // $(".submit-asset").html(data[2]);

				// $(".loader").hide();	
				// $( ".admin-content" ).append( data );
			} //success
		}); //end ajax
    }

    function submitFinalResults(masterId) {
        var data = {
            "action": "submitFinalResults",
            "masterId": masterId
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/index_class.php",
			data: data,
			success: function(data) {	
                console.log("submitFinalResults data: ", data);
             
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function openSubmitFinalModal(masterId) {
        var itemContent = "<div class='popup poplock confirmEnvAdd'>"+
				"<span class='closeButton' onclick='removePopup()'>x</span>"+
                "<div class='popupIcon'></div>"+
                "<h4>Please confirm you wish complete and finalize this test.</h4>" +
                "<div><button class='' onclick='submitFinalResults("+masterId+")'>Confirm</button></div>"+
                "</div>";

        var $newdiv1 = $(itemContent);
        $('.row').addClass("blur-filter");
        appendOverlay();
        $( 'body').append( $newdiv1 );
    }

    function openSerialSearchModal(serial, masterId, table) {
        /* Below conditional checks if the user is currently on their last system to be checked.
            It won't allow them to search for anymore systems if they are on the last one. */
        if($("#current-asset").text() === $("#total-assets").text()) {
            alert("You have reached your search limit. Please complete the systems you have searched for.");
            return;
        } 
        var regex = new RegExp(/[~`!#$%\^&*+=\-\[\]\\';,/{}|\\":<>\?\s]/g);
        if(serial === "" || regex.test(serial)) {
            alert("Please only use standard alphanumerics with no spaces");
            return;
        }
        var itemContent = "<div class='popup poplock confirmEnvAdd'>"+
				"<span class='closeButton' onclick='removePopup()'>x</span>"+
                "<div class='popupIcon'></div>"+
                "<h4>Please confirm this is the serial you wish to search</h4>" +
                "<h3>Serial: "+serial+"</h3>" +
                "<div><button class='' onclick='getAssetGradeData(\""+serial+"\", "+masterId+", \""+table+"\")'>Confirm</button></div>"+
                "</div>";

        var $newdiv1 = $(itemContent);
        $('.row').addClass("blur-filter");
        appendOverlay();
        $( 'body').append( $newdiv1 );
    }

    function openPurposeModal(masterId, serial) {
        var itemContent = "<div class='popup poplock confirmEnvAdd'>"+
				"<span class='closeButton' onclick='removePopup()'>x</span>"+
                "<div class='popupIcon'></div>"+
                "<h3>Purpose of System:</h3>" +
                "<p>Ask local team about the purpose of the system and associated customers using it</p>" +
                "<div class='data-point-select'>" +
                    "<h4>Is Purpose Information Available?&nbsp;</h4>" +
                    "<div>" +
                        "<select class='purpose1-select-box'  onchange='updateAssetGrade(\"audit_forward\", \"purpose1_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
                        "<option  value='' disabled='' selected=''>Select Grade</option>" +
                        "<option value='pass'>Yes</option>" +
                        "<option value='fail'>No</option>" +
                        "</select>" +
                    "</div>" +
                "</div>" +
                "<div class='data-point-select'>" +
                    "<h4>Is User Information Available?&nbsp;</h4>" +
                    "<div>" +
                        "<select class='purpose2-select-box'  onchange='updateAssetGrade(\"audit_forward\", \"purpose2_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
                        "<option  value='' disabled='' selected=''>Select Grade</option>" +
                        "<option value='pass'>Yes</option>" +
                        "<option value='fail'>No</option>" +
                        "</select>" +
                    "</div>" +
                "</div></div>";

        var $newdiv1 = $(itemContent);
        $('.row').addClass("blur-filter");
        appendOverlay();
        $( 'body').append( $newdiv1 );
        /* Add previous grades from db to the select boxes if they exist */
        $(".purpose1-select-box option[value='"+purpose1Grade+"']").attr('selected','selected');
        $(".purpose2-select-box option[value='"+purpose2Grade+"']").attr('selected','selected');
    }

    function openLegacyModal(masterId, serial) {
        var itemContent = "<div class='popup poplock confirmEnvAdd'>"+
				"<span class='closeButton' onclick='removePopup()'>x</span>"+
                "<div class='popupIcon'></div>"+
                "<h3>Legacy Deep Dive:</h3>" +
                "<p>If system is considered legacy, local team to provide plan (including prerequisites) to scrap/consolidate the system or business justification to retain</p>" +
                "<div class='data-point-select'>" +
                    "<h4>Is Plan with prerequisites available?&nbsp;</h4>" +
                    "<div>" +
                        "<select class='legacy1-select-box'  onchange='updateAssetGrade(\"audit_forward\", \"legacy1_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
                        "<option  value='' disabled='' selected=''>Select Grade</option>" +
                        "<option value='pass'>Yes</option>" +
                        "<option value='fail'>No</option>" +
                        "</select>" +
                    "</div>" +
                "</div>" +
                "<div class='data-point-select'>" +
                    "<h4>Is business justification available?&nbsp;</h4>" +
                    "<div>" +
                        "<select class='legacy1-select-box'  onchange='updateAssetGrade(\"audit_forward\", \"legacy2_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
                        "<option  value='' disabled='' selected=''>Select Grade</option>" +
                        "<option value='pass'>Yes</option>" +
                        "<option value='fail'>No</option>" +
                        "</select>" +
                    "</div>" +
                "</div></div>";

        var $newdiv1 = $(itemContent);
        $('.row').addClass("blur-filter");
        appendOverlay();
        $( 'body').append( $newdiv1 );
        /* Add previous grades from db to the select boxes if they exist */
        $(".legacy1-select-box option[value='"+legacy1Grade+"']").attr('selected','selected');
        $(".legacy2-select-box option[value='"+legacy2Grade+"']").attr('selected','selected');
    }

    function appendOverlay(){
        var docHeight = $(document).height();

        $("body").append("<div id='overlay'></div>");

        $("#overlay")
            .height(docHeight)
            .css({
                'opacity' : 0.4,
                'position': 'absolute',
                'top': 0,
                'left': 0,
                'background-color': 'black',
                'width': '100%',
                'z-index': 5000
            });
    }

    function removePopup(){
        $('.blur-filter').removeClass("blur-filter");
        $('.popup,#overlay').remove();
    }

    $(function(){

        $("#test-menu, #random-check-container, #reverse-check-container, #content-random-check, #error-container").hide();
        // var timesClicked = 0;

        // $(".serial-div").click(function() {
        // timesClicked++;

        // if (timesClicked > 1) {
        // //run second function
        // } else {
        // //run first function
        //     Grade(cepId)
        // }

        // })

    });
  </script>
</body>
</html>