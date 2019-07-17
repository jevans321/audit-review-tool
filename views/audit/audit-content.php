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
    
    <div id="top-container">
    <span class="audit-id"></span><span class="audit-title"></span>
        <div id="test-menu">
            <div><span class="actionButton" onclick="toggleContainers('#forward-check-container')">Forward Check</span></div>
            <div><span class="actionButton" onclick="toggleContainers('#reverse-check-container')">Reverse Check</span></div>
            <div><span class="submitFinalDisabled"><button class="submitFinalButton" onclick="openSubmitFinalModal(createdMasterId)" disabled>Submit Final Results</button></span></div>
        </div>
    </div>
    <div class="left-container">
        <div class="left-loader"></div>
        <div id="forward-check-container">
            <div><div class="serial-check-header sideHeaderTitle">System Check <span id="current-asset"></span> of <span id="total-assets"></span></div></div> 
            <div><form id="searchForm" onsubmit="openSerialSearchModal($('#search').val(), createdMasterId, 'audit_forward'); return false;" method="post">
                <input type="text" id="search" placeholder="Enter Serial Number" name="search">
            </form></div>
            <div class="forward-serial-list">
            </div>
        </div>

        <div id="reverse-check-container">
            <div class="gen-serials-btn" onclick="generateReverseCheckAssets(createdMasterId, selectedSite)">Generate Serials</div>
            <div class="reverse-serial-list">
            </div>
        </div>
    </div> <!-- End of left-container  -->
        <div id="error-container"></div>
        <div id="content-forward-check">
            <div class="general-content-forward-check">
            </div>
            <div class="location-content-forward-check">
            </div>
            <div class="asset-score-forward-check">
            </div>
            <div class="submit-asset-forward-check">
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
    var isEdit; // Variable that signifies if a user has clicked on one of the dashboard row to view previous tests
    var serialCheckCount = 0; // Increment count on submit
    var serialObj = {};
    /* Variables for any existing 'Forward Check' grades in db. Used in the 'openPurpose' and openLegacy' modal functions */
    var purpose1Grade, purpose2Grade, legacy1Grade, legacy2Grade;

    function getSiteResults(selectedSite){
		// console.log("getSiteResults selectedSite: ", selectedSite);
		// if(!selectedSite) return;
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
                // var code = "<div class='site-results'></div>";
                console.log("get site results: ", data);
                if(data === "no results") {
                    $(".dash-content").html("<div>No tests available</div>");
                } else {
                    $(".dash-content").html(data);
                }
                
			} //success
		}); //end ajax
    }

    function retrieveOldTest(masterId, site) { // pull masterId from UI row
        // set createdMasterId variable
        createdMasterId = masterId;
        // set selectedSite variable, get site from UI row as well
        selectedSite = site;
        // set 'isEdit' variable to true
        isEdit = true;
        // hide the dashboard container
        $("#dash-container").hide();
        // show top menu
        $("#top-container, #test-menu").show();
        $(".audit-id").text("id: " + masterId);
        isForwardAndReverseCheckComplete(masterId);
    }

    function toggleContainers(elementId) {
        // $("#forward-check-container, #reverse-check-container, #content-forward-check, #content-reverse-check").hide();
        $("#error-container").hide();
        $("#error-container").empty();
        /* If the Forward Check menu item selected, then calculate the Sites total number of systems that need to be checked */
        if(elementId === "#forward-check-container") {
            $(".audit-title").text("Forward Check");
            $("#reverse-check-container, #content-reverse-check").hide();
            $("#forward-check-container, #content-forward-check").show();
            getForwardCheckSystemsTotal(selectedSite);
            // displayedSerial = $(".general-content-forward-check .serialTitle").text();

        } else { // if Reverse Check
            $(".audit-title").text("Reverse Check");
            $("#forward-check-container, #content-forward-check").hide();
            $("#reverse-check-container, #content-reverse-check").show();
            /* create serial-list menu from DB if it exists */
            if(isEdit) {
                createSerialList(createdMasterId, "audit_reverse");
            }
            /* add highlight to left menu item */
            var displayedSerial = $(".general-content-reverse-check .serialTitle").text();
            if(displayedSerial.length) {
                $( "div.serial-container:contains("+displayedSerial+")" ).addClass( "active" );
            }

        }

    }

    function getForwardCheckSystemsTotal(site) {
        $(".left-loader").addClass("icon-loader-center");
        var data = {
			"action": "getForwardCheckSystemsTotal",
            "site": site,
            "masterId": createdMasterId
        };
        
		data = $(this).serialize() + "&" + $.param(data);

		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                $(".left-loader").removeClass("icon-loader-center");
                // console.log("getForwardCheckSystemsTotal data: ", data);
                $("#total-assets").text(data[0]); // display total asset count in UI
                
                if(data[1]) { // If an 'assets checked' count exists then display it in UI
                    $("#current-asset").text(data[1]);
                    if($("#current-asset").text() === $("#total-assets").text()) {
                        $("#searchForm, .system-found-div").hide();
                    }

                    if(data[2]) { // If an html serial list exists then display it in UI
                        $(".forward-serial-list").html(data[2]);
                        // add highlight to left menu item
                        var displayedSerial = $(".general-content-forward-check .serialTitle").text();       
                        if(displayedSerial.length) {
                            $( "div.serial-container:contains("+displayedSerial+")" ).addClass( "active" );
                        }
                    }
                } else { // if no 'assets checked' count exists then just display 0
                    $("#current-asset").text(0);
                }
                
			} //success
		}); //end ajax
    }

    function getAssetGradeData(serial, masterId, table, noAlert){
        $("#error-container").hide();
        if(table === "audit_forward") {
            removePopup();
            // remove any previous-grade variables set for Test 4 & 5 (Purpose & Legacy Check)
            legacy1Grade = "";
            legacy2Grade = "";
        }
        if(table === "audit_reverse") {
            $("#content-forward-check").hide();
            $(".asset-score-reverse-check, .general-content-reverse-check, .location-content-reverse-check").empty();
        } else {
            // If Forward Check is being done, empty content area before each query to retrieve new content from DB
            $(".asset-score-forward-check, .general-content-forward-check, .location-content-forward-check").empty();
        }

        $("#error-container").empty();
       
        submittedSerial = serial;

		var data = {
			"action": "getAssetGradeData",
            "serial": serial,
            "masterId": masterId,
            "table": table,
            "site": selectedSite
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                console.log("getAssetGradeData data: ", data);

                if(data[1] === "duplicates" || data[1] === "empty") { // if duplicate or non-existent serial numbers found for forward or reverse check            
                    $("#error-container").html(data[0]);
                    $("#error-container").show();
  
                    /* In the case a user doing a Forward Check clicks on a serial number menu item but the serial does not exist in CEP:
                       An array with 3 values will be returned and the indeces will be [0] = html, [1] = "empty", and [5] = "record exists".
                       Because of index [1] = "empty" the current conditional scope is accessed. Because the table will be "audit_forward"
                       the below conditional would be accessed and the Forward Check asset total would be incremented. But the asset already exists
                       and has been counted so incrementing is not necessary, so I added the data[5] check. Which basically says, if the record exists don't go into this conditional
                       and increment the total count */
                    if(table === "audit_forward" && !data[5]) { // if table equals 'audit_forward AND data[5] ("record exists") Does Not exist
                        incrementAssetCheckedTotalAndFoundStatus('fail', table);

                    } else {
                        /* The below conditional will re-add the 'active' class to the serial numbers
                        div container (if it exists in the element). This is done becuase the active
                        class is removed when the serial list is re-populated.
                        - the serial list is re-populated from the backend whenever it's status changes to 'complete' */
                        if($( "div.serial-container:contains("+serial+")" ).hasClass( "active" )) {
                            $(".reverse-serial-list").html(data[2]);
                            $( "div.serial-container:contains("+serial+")" ).addClass( "active" );
                        } else {
                            $(".reverse-serial-list").html(data[2]);
                        }
                    }
                    /* Check if all assets are marked complete in the case that the last asset checked in the UI is a duplicate */
                    areAllAssetChecksComplete(table, masterId);
                    // I can probablly replace data[2] with 'table' variable
                } else if(data[2] === "audit_forward") { // if successful 'Forward Check' serial search..
                    $(".general-content-forward-check").html(data[0]);
                    $(".location-content-forward-check").html(data[1]);
                    $("#content-forward-check").show();
                    /* Conditional for if the searched for serial already exists in table.
                        - retrieve the systems previous grades from db
                        - the user should be alerted
                        - the sytem should not be counted */
                    if(data[4] === "record exists") {
                        
                        getAssetGrades(serial, table, masterId);
                        if(!noAlert) {
                            bootstrapAlert('info', 'This system has already been searched and counted');
                        }
                        return;
                    }
                    incrementAssetCheckedTotalAndFoundStatus('pass', table);
                
                    
                } else { // if successful 'Reverse Check' serial search..
                    $(".general-content-reverse-check").html(data[0]);
                    $(".location-content-reverse-check").html(data[1]);
                    $("#content-reverse-check").show();
                    
                }
                      
                /*  Forward Check db returns that are not duplicates or empty should Not make it down to this block of code.
                    They will 'return' in the 'else if' statement just above.

                    Only Reverse Checks and All duplicates and empty returns will hit the conditional below.

                    Conditional below retrieves past asset pass/fail grades only if serial link has been clicked already.
                    There is an object above function, serialObj, that stores the serial #'s of each serial clicked.
                    If the object has the serial already it means the serial link has been clicked, so a query is sent
                    to retrieve previous grades selected to show in the UI. If serial is not in object, it means serial
                    has not been clicked, so the serial is added, however, retrieval query is not invoked.

                    !data[5] means if a Forward Check was done, return TRUE if a record Does Not exist in 'audit_forward' table
                    So let's say a Forward Check was done and a record Exists in the 'audit_forward' table. Again, only
                    the duplicate and empty responses make it down to this conditional.. So in the 'exists' case, I don't want
                    to go into the conditional and run the 'getAssetGrades' function to get the assets grades, because the
                    grades don't exist in the records of serials that are duplicates or empty.
                */
               console.log("right above seriablObj conditional");
                if((serialObj[serial] || isEdit) && !data[5]) { 
                    console.log("Inside seriablObj isEdit conditional");
                    // run getAssetGrades function
                    getAssetGrades(serial, table, masterId);
                } else {
                    console.log("Inside seriablObj conditional");
                    // add id to object
                    serialObj[serial] = 1;
                }
			} //success
        }); //end ajax
        $("#search").val('');
    }

    /* Helper function */
    function incrementAssetCheckedTotalAndFoundStatus(grade, table) {     
        /* If Column Is 'asset_found_grade' And Grade is 'fail' the below function will auto update 'review_status' to 'complete'.
            - this function is called first so that in the above case, the database can be updated first with a 'complete' value. A new serial
              menu list will be generated and inserted in the ajax 'success' function and if the review status is 'complete' a check mark
              will be added next to the list item. */
        updateAssetValue(table, "asset_found_grade", grade, createdMasterId, submittedSerial);

        // If Forward-check: update total count of serial numbers searched
        if(table === "audit_forward") {
            // serialCheckCount++;
            var data = {
                "action": "incrementAssetCheckedTotalAndFoundStatus",
                "table": table,
                "masterId": createdMasterId
            };
        
            data = $(this).serialize() + "&" + $.param(data);

            $.ajax({
                type: "POST",
                url: "./routes/audit_class.php",
                data: data,
                success: function(data) {
                    console.log("incrementAssetCheckedTotalAndFoundStatus data: ", data);
                    $(".forward-serial-list").html(data[0]);
                    $( "div.serial-container:contains("+submittedSerial+")" ).addClass( "active" ); // highlight serial number menu item that was just searched
                    $("#current-asset").text(data[1]);
                    /* Below conditional: if data[2] exists it means all required assets for
                       Forward Check have been searched for */
                    if(data[2]) {
                        bootstrapAlert('info', 'This is the last of the Forward Check systems required.');
                        // hide search box
                        // replace search box with "Forward Check Complete"
                        // "Total number of systems checked"
                        // Completed systems should already be shown
                    }
                } //success
            }); //end ajax
        }
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
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                console.log("grades data array: ", data);

                
                $(".sys-owner-select-box option[value='"+data[1]+"']").attr('selected','selected');
                $(".system-select-box option[value='"+data[2]+"']").attr('selected','selected'); // manufacturer
                $(".host-select-box option[value='"+data[3]+"']").attr('selected','selected');
                $(".ip-select-box option[value='"+data[4]+"']").attr('selected','selected');   
                $(".room-select-box option[value='"+data[5]+"']").attr('selected','selected');
                $(".grid-select-box option[value='"+data[6]+"']").attr('selected','selected');
                $(".ssh-select-box option[value='"+data[7]+"']").attr('selected','selected');

                if(table === "audit_reverse") {
                    if(!!data[8] && !!$(".general-content-reverse-check").html()) { // if data[8] is true (if score exists) and the asset exists properly (it's not a duplicate)
                        $(".asset-score-reverse-check").html(data[8]);
                        
                    }
                    $(".update-date").text(data[9]);         
                    $(".asset-select-box option[value='"+data[0]+"']").attr('selected','selected');       
                } else if(table === "audit_forward") { // if table is 'audit_forward', retrieve additional test values'
                     // Update 'asset found' value below forward-check search field
                    $(".system-found-select-box option[value='"+data[0]+"']").attr('selected','selected');
                    legacy1Grade = data[8];
                    legacy2Grade = data[9];
                    $(".update-date").text(data[10]);
                    // Test 2 through 5 scores can be added here..
                }
                $(".ssh-loader").removeClass("icon-loader");

			} //success
		}); //end ajax
    }

    function updateAssetValue(table, column, value, masterId, serial){
        // $('.icon-loader').removeClass('hide');

        if(column === "asset_found_grade" && value === "fail") {
            // run updateAssetValue for all Reverse Check grade columns
            updateAssetValue(table, "system_owner_grade", value, masterId, serial);
            updateAssetValue(table, "system_type_grade", value, masterId, serial);
            updateAssetValue(table, "hostname_grade", value, masterId, serial);
            updateAssetValue(table, "ip_grade", value, masterId, serial);
            updateAssetValue(table, "room_grade", value, masterId, serial);
            updateAssetValue(table, "grid_grade", value, masterId, serial);
            updateAssetValue(table, "sshable_grade", value, masterId, serial);
 
            $(".sys-owner-select-box option[value='fail']").attr('selected','selected');
            $(".system-select-box option[value='fail']").attr('selected','selected');
            $(".host-select-box option[value='fail']").attr('selected','selected');
            $(".ip-select-box option[value='fail']").attr('selected','selected');  
            $(".room-select-box option[value='fail']").attr('selected','selected');
            $(".grid-select-box option[value='fail']").attr('selected','selected');
            $(".ssh-select-box option[value='fail']").attr('selected','selected');
        }
		
		var data = {
			"action": "updateAssetValue",
			"table": table,
            "column": column,
            "value": value,
            "masterId": masterId,
            "serial": serial
        };
        
		data = $(this).serialize() + "&" + $.param(data);

		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                // console.log("updateAssetValue data: ", data);
                // $('.icon-loader').addClass('hide');
                if(column === "sshable_grade") {
                    getAssetGrades(serial, table, masterId);
                }
                // getAllRequests();	
                
			} //success
		}); //end ajax
    }

    function updateGradeInMaster(masterId, selectedGrade, column){
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
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                console.log("updateGradeInMaster data: ", data);
                // getAllRequests();	
                
			} //success
		}); //end ajax
    }
    
    function createAuditMasterRecord(site){
        if(site === 'cdl') {
            bootstrapAlert('warning', 'CDL site has no Grid Locations');
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
                selectedSite = data[0];
                createdMasterId = data[1];
                $("#top-container, #test-menu").show();
                $(".audit-id").text("id: " + data[1]);
                             
 				// $(".loader").hide();	
			} //success
		}); //end ajax
    }

    
    function generateReverseCheckAssets(masterId, site){
        $(".left-loader").addClass("icon-loader-center");
        $('.gen-serials-btn').hide();
        
        var data = {
            "action": "generateReverseCheckAssets",
            "masterId": masterId,
            "site": site
        };
        data = $(this).serialize() + "&" + $.param(data);
        $.ajax({
            type: "POST",
            url: "./routes/audit_class.php",
            data: data,
            success: function(data) {
                $(".left-loader").removeClass("icon-loader-center");
                console.log("generateReverseCheckAssets data: ", data);
                $(".reverse-serial-list").html(data);
                // $(".loader").hide();	

            } //success
        }); //end ajax
    }
    
    function submitAsset(serial, masterId, table){
        /* Below Conditional: check if all CEP data fields have been graded. */
        if(table === 'audit_reverse') {
            var isTrue = true;
            $( ".general-content-reverse-check select" ).each(function() {
                if(!$( this ).val()) {
                    bootstrapAlert('info', 'Please grade all data points');
                    isTrue = false;
                    return false;
                }
            })
            if(!isTrue) return;
        }

		var data = {
			"action": "submitAsset",
            "serial": serial,
            "masterId": masterId,
            "table": table
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {	
                console.log("submitAsset data: ", data);
                if(!data) {
                    bootstrapAlert('info', 'Please grade all data points');
                    return;
                }
                if(table === "audit_forward") { // Forward Check
                    $(".general-content-forward-check, .location-content-forward-check").empty();
                    /* First conditional: If the system count matches the total count of systems
                       needing to be checked, end Forward-Check */
                    if($("#current-asset").text() === $("#total-assets").text()) {
                        $("#searchForm, .system-found-div").hide();
                        /* Run the below function to check if all asset reviews are complete for the Forward/Reverse Check.
                           If so, the master table will be updated with the scores from the completed test. */
                        areAllAssetChecksComplete(table, masterId);
                    } else {
                        // Reset the system-found select box to the unselected value at index 0
                        // $(".system-found-select-box").get(0).selectedIndex = 0;
                        bootstrapAlert('info', 'Asset Graded, Please search for next asset');
                    }
                    // $(".asset-score-forward-check").html(data[1]);
                } else { // Reverse Check
                    // display the Asset Score div with score retrieved from database.
                    $(".asset-score-reverse-check").html(data[1]);
                   /* Run the below function to check if all asset reviews are complete for the Forward/Reverse Check.
                      If so, the master table will be updated with the scores from the completed test. */
                    areAllAssetChecksComplete(table, masterId);
                }

                /* Below conditional: re-add's the 'active' class to the serial numbers
                   div container (if it exists in the element). This is done becuase the active
                   class is removed when the serial list is re-populated.
                   - the serial list is re-populated from the backend whenever it's status changes to 'complete' */
                if($("div.serial-container:contains("+serial+")" ).hasClass( "active" )) {
                    if(table === "audit_forward") {
                        $(".forward-serial-list").html(data[0]);
                    } else {
                        $(".reverse-serial-list").html(data[0]);
                    }
                    // now add active class back to serial div
                    $( "div.serial-container:contains("+serial+")" ).addClass( "active" );
                } else { // if the active class doesn't exist only add the updated html from the back-end
                    if(table === "audit_forward") {
                        $(".forward-serial-list").html(data[0]);
                    } else {
                        $(".reverse-serial-list").html(data[0]);
                    }
                }

             
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function areAllCepFieldsGraded(serial, masterId, table) {
        var data = {
            "action": "areAllCepFieldsGraded",
            "serial": serial,
            "masterId": masterId,
            "table": table
        };
        
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {	
                console.log("areAllCepFieldsGraded data: ", data);
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }
    
    /* This Function no longer being used */
    function checkSerial(serial, masterId){
        $(".general-content-forward-check, .location-content-forward-check, .submit-asset-forward-check").empty();
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
			url: "./routes/audit_class.php",
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

                    bootstrapAlert('warning', 'serial could not be found');
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
        removePopup();
        var data = {
            "action": "submitFinalResults",
            "masterId": masterId
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {	
                console.log("submitFinalResults data: ", data);
                $("#top-container, #test-menu, #forward-check-container, #reverse-check-container, #content-forward-check, #error-container, .system-found-div").hide();
                bootstrapAlert('info', 'Audit test is complete');
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function createSerialList(masterId, table) {
        $(".left-loader").addClass("icon-loader-center");
        var data = {
            "action": "createSerialList",
            "masterId": masterId,
            "table": table,
            "fromFrontend": true
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                $(".left-loader").removeClass("icon-loader-center");
                console.log("createSerialList data: ", data);
                if(!data) { // if no records exists
                    // return out of function?
                    return;
                }
                if(table === "audit_reverse") {
                    // remove generate button div
                    $(".gen-serials-btn").remove();
                    $(".reverse-serial-list").html(data);
                }
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function areAllAssetChecksComplete(table, masterId) {
        var data = {
            "action": "areAllAssetChecksComplete",
            "table": table,
            "masterId": masterId
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                // data is a boolean
                console.log("areAllAssetChecksComplete data: ", data);
                if(data) { // if true
                    if(table === "audit_reverse") {
                        bootstrapAlert('info', 'You have completed the Reverse Check');
                    } else if(table === "audit_forward") {
                        bootstrapAlert('info', 'You have completed the Forward Check');
                    }
                    /* The function below will un-gray the submit Final button if all checks complete */
                    isForwardAndReverseCheckComplete(masterId);
                }
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function isForwardAndReverseCheckComplete(masterId) {
        console.log("inside isForwardAndReverseCheckComplete");
        var data = {
            "action": "isForwardAndReverseCheckComplete",
            "masterId": masterId
		};
		data = $(this).serialize() + "&" + $.param(data);
		$.ajax({
			type: "POST",
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                // data is a boolean
                console.log("isForwardAndReverseCheckComplete data: ", data);
                if(data === true) { // if true
                    // un-gray submit final button
                    $("#test-menu > div:last-child span").removeClass("submitFinalDisabled").addClass("actionButton");
                    $(".submitFinalButton").css("cursor", "pointer");
                    $(".submitFinalButton").removeAttr("disabled");
                }

				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function runSsh(databaseIp, serial, masterId, table) {
        // $("#requirementsNoticeDiv").css("display", "block");
        $(".ssh-loader").addClass("icon-loader");
        // $("#sshResults").html("Checking SSH Status....");
        console.log("V1 Running ssh for " + databaseIp);
      
        $.ajax({
            type: "GET",
            url: "api-data.php?"+databaseIp,
            dataType: 'json',
            success: function(res) {
                console.log("Cep Response & connected: ", res, res.connected);
                if(res.connected === "true") {
                    updateAssetValue(table, "sshable_grade", "pass", masterId, serial);
                } else {
                    updateAssetValue(table, "sshable_grade", "fail", masterId, serial);
                }
                // var resLabel = '<label>' + res + "</label>";
                // $("#sshResults").html("Connected: ", res.connected);
                // $("#loadingApi").hide();		
            },
            error: function(res) {
                console.log("Ajax Error: ", res);
                updateAssetValue(table, "sshable_grade", "fail", masterId, serial);
                // alert("There was an error is your request. Please try again.");
                // $("#sshResults").html(res.responseText);
            }
        }); //end ajax
        // $("#requirementsNoticeDiv").css("display", "none");


    }

    function openSubmitFinalModal(masterId) {
        var itemContent = "<div class='popup poplock confirmEnvAdd'>"+
				"<span class='closeButton' onclick='removePopup()'>x</span>"+
                "<div class='popupIcon'></div>"+
                "<h5>Please confirm you wish complete and finalize this test.</h5>" +
                "<div><button class='modalConfirmButton' onclick='submitFinalResults("+masterId+")'>Confirm</button></div>"+
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
            bootstrapAlert('info', 'You have reached your search limit. Please complete the systems you have searched for.');
            return;
        } 
        var regex = new RegExp(/[~`!#$%\^&*+=\-\[\]\\';,/{}|\\":<>\?\s]/g);
        if(serial === "" || regex.test(serial)) {
            bootstrapAlert('info', 'Please only use standard alphanumerics with no spaces');
            return;
        }
        var itemContent = "<div class='popup poplock serialConfirm confirmEnvAdd'>"+
				"<span class='closeButton' onclick='removePopup()'>x</span>"+
                "<div class='popupIcon'></div>"+
                "<h5>Please confirm this is the serial you wish to search</h5>" +
                "<h5>Serial: "+serial+"</h5>" +
                "<div><button class='modalConfirmButton' onclick='getAssetGradeData(\""+serial+"\", "+masterId+", \""+table+"\")'>Confirm</button></div>"+
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
                        "<select class='purpose1-select-box'  onchange='updateAssetValue(\"audit_forward\", \"purpose1_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
                        "<option  value='' disabled='' selected=''>Select Grade</option>" +
                        "<option value='pass'>Yes</option>" +
                        "<option value='fail'>No</option>" +
                        "</select>" +
                    "</div>" +
                "</div>" +
                "<div class='data-point-select'>" +
                    "<h4>Is User Information Available?&nbsp;</h4>" +
                    "<div>" +
                        "<select class='purpose2-select-box'  onchange='updateAssetValue(\"audit_forward\", \"purpose2_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
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
                "<div>If a system is considered legacy, local team to provide plan (including prerequisites)</div>" +
                "<div>to scrap/consolidate the system or business justification to retain</div>" +
                "<div class='data-point-select'>" +
                    "<h5>Is a plan with prerequisites available?&nbsp;</h5>" +
                    "<div>" +
                        "<select class='legacy1-select-box'  onchange='updateAssetValue(\"audit_forward\", \"legacy1_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
                        "<option  value='' disabled='' selected=''>Select Grade</option>" +
                        "<option value='pass'>Yes</option>" +
                        "<option value='fail'>No</option>" +
                        "</select>" +
                    "</div>" +
                "</div>" +
                "<div class='data-point-select'>" +
                    "<h5>Is business justification available?&nbsp;</h5>" +
                    "<div>" +
                        "<select class='legacy1-select-box'  onchange='updateAssetValue(\"audit_forward\", \"legacy2_grade\", $(this).val(), "+masterId+", \""+serial+"\")'>"+
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

        $("#top-container, #test-menu, #forward-check-container, #reverse-check-container, #content-forward-check, #error-container, .system-found-div").hide();

        $('body').on('click', '.actionButton, .serial-container', function() {

            if($(this).attr("class").includes("actionButton")) {
                $( ".actionButton" ).removeClass( "active" );
            } else {
                $( ".serial-container" ).removeClass( "active" );
            }
            
            $( this ).addClass( "active" );
        });

    });
  </script>