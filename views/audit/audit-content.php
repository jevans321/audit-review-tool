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
    <span class="audit-title"></span>
        <div id="test-menu">
            <div><span class="actionButton" onclick="toggleContainers('#random-check-container')">Forward Check</span></div>
            <div><span class="actionButton" onclick="toggleContainers('#reverse-check-container')">Reverse Check</span></div>
            <div><span onclick="openSubmitFinalModal(createdMasterId)">Submit Final Results</span></div>
        </div>
    </div>
    <div class="left-container">
        <div id="random-check-container">
            <div><div class="serial-check-header sideHeaderTitle">System Check <span id="current-asset"></span> of <span id="total-assets"></span></div></div> 
            <div><form id="searchForm" onsubmit="openSerialSearchModal($('#search').val(), createdMasterId, 'audit_forward'); return false;" method="post">
                <input type="text" id="search" placeholder="Enter Serial Number" name="search">
            </form></div>
            <div class="forward-serial-list">
            </div>
        </div>

        <div id="reverse-check-container">
            <div class="gen-serials-btn" onclick="addRandomAssets(createdMasterId, selectedSite)">Generate Serials</div>
            <div class="reverse-serial-list">
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
    var isEdit; // Variable that signifies if a user has clicked on one of the dashboard row to view previous tests
    var serialCheckCount = 0; // Increment count on submit
    var serialObj = {};
    /* Variables for any existing 'Forward Check' grades in db. Used in the 'openPurpose' and openLegacy' modal functions */
    var purpose1Grade, purpose2Grade, legacy1Grade, legacy2Grade;

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
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {
                // var code = "<div class='site-results'></div>";
                console.log("get site results: ", data);
                $(".dash-content").html(data);
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
    }

    function toggleContainers(elementId) {
        // $("#random-check-container, #reverse-check-container, #content-random-check, #content-reverse-check").hide();
        $("#error-container").hide();
        $("#error-container").empty();
        /* If the Forward Check menu item selected, then calculate the Sites total number of systems that need to be checked */
        if(elementId === "#random-check-container") {
            $(".audit-title").text("Forward Check");
            $("#reverse-check-container, #content-reverse-check").hide();
            $("#random-check-container, #content-random-check").show();
            getForwardCheckSystemsTotal(selectedSite);
            // displayedSerial = $(".general-content-random-check .serialTitle").text();

        } else { // if Reverse Check
            $(".audit-title").text("Reverse Check");
            $("#random-check-container, #content-random-check").hide();
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
                console.log("getForwardCheckSystemsTotal data: ", data);
                $("#total-assets").text(data[0]); // display total asset count in UI
                
                if(data[1]) { // If an 'assets checked' count exists then display it in UI
                    $("#current-asset").text(data[1]);
                    if(data[2]) { // If an html serial list exists then display it in UI
                        $(".forward-serial-list").html(data[2]);
                        // add highlight to left menu item
                        var displayedSerial = $(".general-content-random-check .serialTitle").text();       
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
                    if(table === "audit_forward" && !data[5]) { // if table equals 'audit_forward AND data[5] ("record exists") does not exist
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
                        if(!noAlert) {
                            alert("This system has already been searched and counted");
                        }
                        return;
                    }
                    incrementAssetCheckedTotalAndFoundStatus('pass', table);
                
                    
                } else { // if successful 'Reverse Check' serial search..
                    $(".general-content-reverse-check").html(data[0]);
                    $(".location-content-reverse-check").html(data[1]);
                    $("#content-reverse-check").show();
                    
                }
                      
                /*  Conditional below retrieves past asset pass/fail grades only if serial link has been clicked already.
                    There is an object above function, serialObj, that stores the serial #'s of each serial clicked.
                    If the object has the serial already it means the serial link has been clicked, so a query is sent
                    to retrieve previous grades selected to show in the UI. If serial is not in object, it means serial
                    has not been clicked, so the serial is added, however, retrieval query is not invoked.
                    - data[5] is for the case that a test record already exists for a serial searched for but the serial does not
                    exist in CEP. So there is no need to query for any non existent grades.
                */
                if((serialObj[serial] || isEdit) && !data[5]) {
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

    /* Helper function */
    function incrementAssetCheckedTotalAndFoundStatus(grade, table) {     
        /* If Column Is 'asset_found_grade' And Grade is 'fail' the below function will auto update 'review_status' to 'complete'.
            - this function is called first so that in the above case, the database can be updated first with a 'complete' value. A new serial
              menu list will be generated and inserted in the ajax 'success' function and if the review status is 'complete' a check mark
              will be added next to the list item. */
        updateAssetGrade(table, "asset_found_grade", grade, createdMasterId, submittedSerial);

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
                        alert("You've completed all checks!");
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
                    if(!!data[8]) { // if data[8] is true (if score exists)
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
			url: "./routes/audit_class.php",
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
            alert("CDL site has no Grid Locations");
            return;
        } else if(!site) {
            alert("Please select site location");
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
            url: "./routes/audit_class.php",
            data: data,
            success: function(data) {	
                console.log("addRandomAssets data: ", data);
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
                    alert("Please grade all data points");
                    isTrue = false;
                    return false;
                }
            })
            if(!isTrue) return;
        }
            //alert("You must grade all data points including the 'Legacy Check' at the bottom");
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
                    alert("Please grade all data points");
                    return;
                }
                if(table === "audit_forward") {
                    $(".general-content-random-check, .location-content-random-check, .additional-tests-random-check").empty();
                    /* First conditional: If the system count matches the total count of systems
                       needing to be checked, end Forward-Check */
                    if($("#current-asset").text() === $("#total-assets").text()) {
                        alert("You have completed the Forward Check");
                        $("#searchForm, .system-found-div").hide();
                    } else {
                        // Reset the system-found select box to the unselected value at index 0
                        // $(".system-found-select-box").get(0).selectedIndex = 0;
                        alert("Asset Graded, Please search for next asset");
                    }
                    // $(".asset-score-random-check").html(data[1]);
                } else {
                    // display the Asset Score div with score retrieved from database.
                    $(".asset-score-reverse-check").html(data[1]);
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
                return data;
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
			url: "./routes/audit_class.php",
			data: data,
			success: function(data) {	
                console.log("submitFinalResults data: ", data);
             
				// $(".loader").hide();	

			} //success
		}); //end ajax
    }

    function createSerialList(masterId, table) {
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


    var xhr;
    function getApi(table){
        // $("#loadingApi").show();

        // var databaseIp = $("#ip-input").attr('placeholder');

		xhr = $.ajax({
            type: "GET",
            // url: "http://l1001.fyre.ibm.com/reval/api-data.php?ip=9.30.4.76&shortinfo=true", // Linux
            // url: "http://l1001.fyre.ibm.com/reval/api-data.php?ip=9.30.10.32&shortinfo=true", // Windows
            url: "http://l1001.fyre.ibm.com/reval/api-data.php?ip="+databaseIp+"&shortinfo=true",
            // build php url, then url calls mine php url then mine will call url
            dataType: 'json',
			// data: data,
			success: function(res) {
                console.log("Response: ", res);
                // console.log("Url: ", "http://l1001.fyre.ibm.com/reval/api-data.php?ip="+databaseIp+"&shortinfo=true");
                // $("#loadingApi").hide();		
                // if response has error prop
                if(res['error']) {
                    if(res['error'].indexOf('Invalid URL') > -1 || res['error'].indexOf('Unable to ping') > -1) {

                        // updateAssetGrade(table, column, grade, masterId, serial)
                        updateAssetGrade(table, sshable_grade, 'fail', createdMasterId, submittedSerial);

                    }
                    
                }  else {
                    // output code
                    updateAssetGrade(table, sshable_grade, 'pass', createdMasterId, submittedSerial);
                }
                $("#ajaxData").html(code);

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

        $("#top-container, #test-menu, #random-check-container, #reverse-check-container, #content-random-check, #error-container, .system-found-div").hide();


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