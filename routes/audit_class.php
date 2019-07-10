<?php   
// session_start();

class Index
{
	public function updateAssetGradeInDb($table, $column, $grade, $masterId, $serial) {
		include ("connection.php");
		header('Content-Type: application/json');
		// $name = $_SESSION['firstName']. " " .$_SESSION['lastName'];

        /* The first conditional statement below sets the forward-checked systems review status
           to complete when a serial number is not found in CEP */
        if($table == "audit_forward" && $column == "asset_found_grade" && $grade === "fail") {
            $sql = "UPDATE $table
                    SET $column = ?, review_status = 'complete'
                    WHERE master_id = ? && system_serial = ?";
        } else {
            $sql = "UPDATE $table
                    SET $column = ?
                    WHERE master_id = ? && system_serial = ?";
        }
		
		if($stmt = mysqli_prepare($link, $sql)){
			mysqli_stmt_bind_param($stmt, "sis", $grade, $masterId, $serial);
            mysqli_stmt_execute($stmt);      
            // echo json_encode($stmt);

		} else {
			echo json_encode(array('status' => 'error','message' => $link->error));
		}
    } // end updateAssetGradeInDb

    public function createAuditMasterRecord($site) {
        // error_log("Inside back-end createAuditMasterRecord");
        include ("connection.php");
        header('Content-Type: application/json');
        $status = 'in-progress';
        $score = 10;
        $email = 'james@imb.com'; // $_SESSION['email'];
        
        $sql = "INSERT INTO audit_master (review_status, site, score, email)
                VALUES (?,?,?,?)";

        //error_log(name .' '. $description);

        if($stmt = $link->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssis", $status, $site, $score, $email);
            if($stmt->execute())
            {
                $id=$link->insert_id;  // or $ID_user=$stmt->insert_id;
                //printf("%d Row inserted.\n", $id);
            }
            $array = array(
                0 => ($site),
                1 => ($id)
            );
            echo json_encode($array);
            // echo json_encode(array('status' => 'success','newid' => $id));
            
        } else{
            echo json_encode(array('status' => 'error','message' => 'error inserting into the db'));
        }
    } //end createAuditMasterRecord
    
    public function addRandomAssetsToTable($masterId, $site) {
        // error_log("Inside back-end addRandomAssetsToTable ID: ". $id);

		include ("connection.php");
        header('Content-Type: application/json');

        /* Insert copied random assets from CEP into audit_reverse table with master ID...

          This selects records from cep that only match the '$site' argument provided.
          The '$masterId' argument is also added to the select so it's added to the insert.
          Then the select from cep is copied and then inserted into the audit_reverse table,
          ONLY if the cep record's serial IS NOT already in the audit_reverse table.
          
          Defined: COALESCE(grid_id, '') <> ''
          This says, if 'grid_id' column value is null, replace it with an empty string
          and then the value cannot equal an empty string. So this skips any null or empty string values.
        */

        $sql = "INSERT INTO audit_reverse ( master_id, system_type, system_serial, site, grid_id )
                SELECT ?, system_type, system_serial, hw_site, grid_id FROM cep_hw
                WHERE hw_site = ? && grid_id <> 0 && COALESCE(grid_id, '') <> '' && system_serial NOT IN
                (SELECT system_serial FROM audit_reverse)
                ORDER BY RAND()
                LIMIT 20";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "is", $masterId, $site);
            mysqli_stmt_execute($stmt);

            /* If the random assets from CEP are copied and inserted into the audit_reverse table successfully,
               based on the query above, then the function below will select the newly added assets from audit_reverse table based on the
               newly created master id and send them to front end for display. */
            $display = $this->createSerialListFromDb("audit_reverse", $masterId, false);
            echo json_encode($display);
            
           

        } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
                    
    } // end addRandomAssetsToTable

    /* This is a Helper function that retrieves the randomly generated cep assests 'serial numbers' from the
       audit_reverse table associated with the provided master id.
       - The serial numbers are then displayed in a list in the UI's left-side column */
    public function createSerialListFromDb($table, $masterId, $fromFrontend) {
        // error_log("Inside back-end createSerialListFromDb ID: ". $masterId);
		include ("connection.php");
        header('Content-Type: application/json');

        $sql = "SELECT system_serial, review_status FROM $table
                WHERE master_id = $masterId
                ORDER BY id DESC";
    

        if ($result = mysqli_query($link, $sql)) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            $display = "<div>";
            for ($i = 0; $i < count($rows); ++$i) {
                $serial = htmlentities($rows[$i]['system_serial']);
                $statusDotClass = htmlentities($rows[$i]['review_status']) === 'complete' ? 'dot-green-sml' : 'dot-orange-sml';
                // error_log("Inside createSerialListFromDb serial: ". $serial);
                if($table == "audit_forward") {
                    $display .=  "<div class='serial-container' onclick='getAssetGradeData(\"$serial\", $masterId, \"$table\", true)'><span class='$statusDotClass'></span><span class='serial-text'>$serial</span></div>";
                } else {
                    $display .=  "<div class='serial-container' onclick='getAssetGradeData(\"$serial\", $masterId, \"$table\")'><span class='$statusDotClass'></span><span class='serial-text'>$serial</span></div>";
                }
 
            }
            $display .= "</div>";

            if($fromFrontend) {
                // if records Do Not exist in the table associated with master Id return false, otherwise return serial-list html code
                $result = count($rows) < 1 ? false : $display;
                echo json_encode($result);
                exit;
            }
            return $display;
        

        } else {
            if($fromFrontend) {
                echo json_encode(array('status' => 'error','message' => $link->error));
                exit;
            }
            return (array('status' => 'error','message' => $link->error));
        
        }

    } // end createSerialListFromDb
    
    /* This function handles CEP data retrieval queries for the provided serial numbers for
       both forward and reverse checks. The conditionals in the function including audit_forward
       are for the foward check table and audit_reverse are for the reverse check table. */
    public function getAssetGradeDataFromCep($serial, $masterId, $table) {
        // error_log("getAssetGradeDataFromCep TABLE: ". $table);
        include ("connection.php");
        header('Content-Type: application/json');
        $array = array();
        if($table == "audit_forward") {
            /* Call HELPER function here, which will create a record in audit_forward table for this asset.
               So when user update the asset's grade in UI a record will exist where the changes will be executed.
               - since php is synchronous the record should be created first before the next query below. */
            $createRecordReturnValue = $this->createForwardCheckAssetRecord($serial, $masterId);
            $array[4] = $createRecordReturnValue;
            /* IF SERIAL IS ALREADY IN TABLE, NOTIFY USER & DO NOT INCREASE THE SYSTEM CHECK COUNT */
        }

            /* James Polo's query suggestion:
                Will return all records associated with serial number and system type.
                - the cep table can have multiple IP's and hostnames associated with the same serial
                  number, so these serial numbers would not be duplicates in cep.
                - the duplicates in cep_hw table are not necessarily duplicates but unique cases where
                  the serial number has separate hardware types or is a floater, a virtual machine etc
                  
                  James P. suggested joining cep on both serial and system type*/
            $sql = "SELECT hw.system_type,
                        hw.system_serial,
                        hw.system_owner,
                        hw.updated,
                        hw.hw_site,
                        c.system_hostname,
                        c.ip_address,
                        c.development_contact,
                        c.updated,
                        md.mfg_name,
                        loc.location,
                        loc.room,
                        loc.grid,
                        loc.rack_number
                        FROM cep_hw hw
                        LEFT JOIN cep c
                        ON hw.system_serial = c.system_serial AND hw.system_type = c.system_type
                        LEFT JOIN cep_model_desc md
                        ON hw.system_type = md.system_type
                        LEFT JOIN lab_locations loc
                        ON hw.grid_id = loc.id
                        WHERE hw.system_serial = '$serial'";


         if ($result = mysqli_query($link, $sql)) {
            // error_log("Inside getAssetGradeDataFromCep conditional");
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            /* Duplicate Records Conditional: handles if duplicate records are returned for same serial,
               which can be determined by more than one row returning from query */
            if(count($rows) > 1) {
                // return html with a List of all the records
                $display = "<div class='pageTitle'>Test Marked as Failed, Duplicate Records Found</div><div class='sideHeaderTitle'>Please check with local team</div>";
                $display .= "<div class='duplicate-content'><div><div>Serial</div><div>System Type</div><div>System Owner</div><div>Hostname</div><div>IP Address</div><div>Grid</div><div>Updated</div></div>";
                for ($i = 0; $i < count($rows); ++$i) {
                    $display .= "<div><div>$serial</div><div>".htmlentities($rows[$i]['system_type'])."</div><div>".htmlentities($rows[$i]['system_owner'])."</div>
                                 <div>".htmlentities($rows[$i]['system_hostname'])."</div><div>".htmlentities($rows[$i]['ip_address'])."</div><div>".htmlentities($rows[$i]['grid'])."</div><div>".htmlentities($rows[$i]['updated'])."</div></div>";
                }
                $display .= "</div>";
                
                $array = array(
                    0 => $display,
                    1 => "duplicates"
                );
                if($table == "audit_reverse") {
                    /* Updated the review_status in the audit_reverse table to 'complete'. I'm using the updateAssetGradeInDb function
                       because it does exactly what I need without needing to create another function.  */
                    $this->updateAssetGradeInDb($table, "review_status", "complete", $masterId, $serial);
                    $serialList = $this->createSerialListFromDb("audit_reverse", $masterId, false);
                    $array[2] = $serialList; //substr($serialList, 0, -3);
                }

                // error_log("serialList Entire ARRAY:..................... ". print_r($array, true));
                echo json_encode($array);
                exit;
              /* Serial Does Not Exist In CEP Conditional: handles if no records are returned for a serial, so the serial does not exist in CEP */
            } elseif(count($rows) < 1) {
                $array = array(
                    0 => "<h3>No records found for serial \"".$serial."\", Test Failed</h3>",
                    1 => "empty"
                );
                if($createRecordReturnValue == "record exists") $array[5] = $createRecordReturnValue;
                echo json_encode($array);
                exit;
            }
            $display = "<div class='pageTitle'>CEP System Info: <span class='serialTitle'>$serial</span></div>";

            $siteValue = $rows[0]['hw_site'] ? htmlentities($rows[0]['hw_site']) : "<i>Item not available</i>";
            $display .=  "<div>
                             <div>Site:&nbsp;<span id='site' class='span-static-val'>". $siteValue ."</span></div>
                        </div>";
            /* If record exists in DB already, add a last Updated field to UI. */
            if(isset($createRecordReturnValue) && $createRecordReturnValue == "record exists") {       
                $display .= "<div>
                                <div>Last Updated:&nbsp;<span id='site' class='update-date'></span></div>
                            </div>";
            }
            /* Below, add an 'asset found' data point only if a 'reverse check' is being made, where systems need to be found.
            This data point should not be necessary if a forward check is being made where a user is already at a system */
            if($table == "audit_reverse") {                        
                $display .= "<div>
                                <div>Asset Found:&nbsp;</div>
                                <div>
                                    <select class='asset-select-box'  onchange='updateAssetGrade(\"$table\", \"asset_found_grade\", $(this).val(), $masterId, \"$serial\")'>
                                    <option  value='' disabled='' selected=''>Select Grade</option>
                                    <option value='pass'>Yes</option>
                                    <option value='fail'>No</option>
                                    </select>
                                </div>
                            </div>";
            }
            $sytemOwnerValue = $rows[0]['system_owner'] ? htmlentities($rows[0]['system_owner']) : "<i>Item not available</i>";
            $display .=  "<div>
                            <div>System Owner:&nbsp;<span id='sys-owner-row' class='span-static-val'>". $sytemOwnerValue ."</span></div>
                            <div>
                                <select class='sys-owner-select-box'  onchange='updateAssetGrade(\"$table\", \"system_owner_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $manufacturerValue = $rows[0]['mfg_name'] ? htmlentities($rows[0]['mfg_name']) : "<i>Item not available</i>";
            $display .=  "<div>
                            <div>Manufacturer:&nbsp;<span id='system-row' class='span-static-val'>". $manufacturerValue ."</span></div>
                            <div>
                                <select class='system-select-box'  onchange='updateAssetGrade(\"$table\", \"system_type_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $hostnameValue = $rows[0]['system_hostname'] ? htmlentities($rows[0]['system_hostname']) : "<i>Item not available</i>";
            $display .=  "<div>
                            <div>Hostname:&nbsp;<span id='hostname-row' class='span-static-val'>".$hostnameValue."</span></div>
                            <div>
                                <select class='host-select-box'  onchange='updateAssetGrade(\"$table\", \"hostname_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $ipValue = $rows[0]['ip_address'] ? htmlentities($rows[0]['ip_address']) : "<i>Item not available</i>";
            $display .=  "<div>
                            <div>IP:&nbsp;<span id='ip-row' class='span-static-val'>".$ipValue."</span></div>
                            <div>
                                <select class='ip-select-box'  onchange='updateAssetGrade(\"$table\", \"ip_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $roomValue = $rows[0]['room'] ? htmlentities($rows[0]['room']) : "<i>Item not available</i>";
            $display .= "<div>
                            <div>Room Location:&nbsp;<span id='room-row' class='span-static-val'>". $roomValue ."</span></div>
                            <div>
                                <select class='room-select-box'  onchange='updateAssetGrade(\"$table\", \"room_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $gridValue = $rows[0]['grid'] ? htmlentities($rows[0]['grid']) : "<i>Item not available</i>";
            $display .= "<div>
                            <div>Grid Location:&nbsp;<span id='grid-row' class='span-static-val'>". $gridValue ."</span></div>
                            <div>
                                <select class='grid-select-box'  onchange='updateAssetGrade(\"$table\", \"grid_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $display .= "<div>
                            <div>SSHable:&nbsp; <strong>Test 3</strong>, AUTO FILL THIS</div>
                            <div>
                                <select class='ssh-select-box'  onchange='updateAssetGrade(\"$table\", \"sshable_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Yes</option>
                                <option value='fail'>No</option>
                                </select>
                            </div>
                        </div>";

            $display2 = "<div class='data-buttons text-center'><button style='margin: 10px 0; width: 100%;' onclick='getApi()' class='systemButton ajaxButton'>Automation Check</button>
                                <button style='margin-bottom: 25px; width: 100%;' onclick='submitAsset(\"$serial\", $masterId, \"$table\")' class='systemButton ajaxButton'>Complete Asset Check</button></div>";
            $display3 = "";
            if($table == "audit_forward") {
                $display3 .= "<div id='test5-div'>
                                <h3>Test 5</h3>
                                <div id='legacy-btn' onclick='openLegacyModal(createdMasterId, submittedSerial)'>Legacy Check</div>
                            </div>";
            }

            $array[0] = $display;
            $array[1] = $display2;
            $array[2] = $table;
            $array[3] = $display3;
            
            echo json_encode($array);
  
         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    } // end getAssetGradeDataFromCep

    public function getAssetGradesFromDb($serial, $table, $masterId) {
        // error_log("Inside getAssetGradesFromDb serial: ". $serial);
        include ("connection.php");
        header('Content-Type: application/json');

        $sql = "SELECT * FROM $table WHERE system_serial = '$serial' AND master_id = $masterId";

         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            
            // error_log("getAssetGradesFromDb ROWS: ". print_r($rows, true));
            // error_log("getAssetGradesFromDb DATE: ". $rows[0]['updated']);
            $date = date('m-d-Y, g:i a', strtotime($rows[0]['updated']));

            /* The values in the below array will populate the front-end select box values.
                If the value is null the select box value will be empty, otherwise it will send a value */
            if($table == "audit_reverse") {
                $display = "<h2>Asset Score</h2>";
                $display .= "<div>". $rows[0]['score'] ."</div>";
                $array = array(
                    0 => ($rows[0]['asset_found_grade'] == null ? "" : $rows[0]['asset_found_grade']),
                    1 => ($rows[0]['system_owner_grade'] == null ? "" : $rows[0]['system_owner_grade']),
                    2 => ($rows[0]['system_type_grade'] == null ? "" : $rows[0]['system_type_grade']),
                    3 => ($rows[0]['hostname_grade'] == null ? "" : $rows[0]['hostname_grade']),
                    4 => ($rows[0]['ip_grade'] == null ? "" : $rows[0]['ip_grade']), 
                    5 => ($rows[0]['room_grade'] == null ? "" : $rows[0]['room_grade']),
                    6 => ($rows[0]['grid_grade'] == null ? "" : $rows[0]['grid_grade']), 
                    7 => ($rows[0]['sshable_grade'] == null ? "" : $rows[0]['sshable_grade']),
                    8 => ($rows[0]['score'] == null ? "" : $display),
                    9 => $date
                );

            } else {
                $array = array(
                    0 => ($rows[0]['asset_found_grade'] == null ? "" : $rows[0]['asset_found_grade']),
                    1 => ($rows[0]['system_owner_grade'] == null ? "" : $rows[0]['system_owner_grade']),
                    2 => ($rows[0]['system_type_grade'] == null ? "" : $rows[0]['system_type_grade']),
                    3 => ($rows[0]['hostname_grade'] == null ? "" : $rows[0]['hostname_grade']),
                    4 => ($rows[0]['ip_grade'] == null ? "" : $rows[0]['ip_grade']), 
                    5 => ($rows[0]['room_grade'] == null ? "" : $rows[0]['room_grade']),
                    6 => ($rows[0]['grid_grade'] == null ? "" : $rows[0]['grid_grade']), 
                    7 => ($rows[0]['sshable_grade'] == null ? "" : $rows[0]['sshable_grade']),
                    8 => ($rows[0]['legacy1_grade'] == null ? "" : $rows[0]['legacy1_grade']),
                    9 => ($rows[0]['legacy2_grade'] == null ? "" : $rows[0]['legacy2_grade']),
                    10 => $date
                );
            }
            
            echo json_encode($array);

         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    }
    
    /* Function is used by both Forward and Reverse Checks to submit grades to DB for CEP data integrity check */
    public function submitAssetToDb($serial, $masterId, $table) {

        // check if all grades have been given:
        $allGraded = $this->areAllCepFieldsGraded($serial, $masterId, $table);
        if(!$allGraded) { // if false (not all graded), echo false
            echo json_encode($allGraded);
            exit;
        }

        // error_log("submitAssetToDb table: ". $table);
        include ("connection.php");
        header('Content-Type: application/json');

        /* The query below calculates a total asset score by adding up the total number of fails
            Breakdown:
            The Total score is 10
            The query adds all the 'fail' grade values. (If value is 'fail' it equals 1 else its 0)
            Then muliplies that sum times 1/8th of 10 since there are 8 grades in all, so 1.25.
            Then the final score is rounded.
            So Total - (sum * 1/8) = round(score)
                 10  - (sum * 1.25) = round(score)
            or Total - (sum * 1/11) = round(score)
                 10  - (sum * 0.9) = round(score)
        */
        if($table == "audit_forward") { // Forward Check Test
            $sql = "UPDATE $table origin
                    SET origin.cep_score = (SELECT 10 - ROUND((SELECT sum(COALESCE(temp.system_owner_grade ='fail', 0))
                        + COALESCE(temp.system_type_grade='fail', 0) + COALESCE(temp.hostname_grade ='fail', 0)
                        + COALESCE(temp.ip_grade ='fail', 0) + COALESCE(temp.room_grade ='fail', 0)
                        + COALESCE(temp.grid_grade ='fail', 0) + COALESCE(temp.sshable_grade ='fail', 0)
                        FROM (SELECT * FROM $table) temp WHERE temp.system_serial = ? && temp.master_id = ?) * 1.428)),

                        origin.legacy_score = (SELECT 10 - ROUND((SELECT sum(COALESCE(temp.legacy1_grade ='fail', 0))
                        + COALESCE(temp.legacy2_grade='fail', 0)
                        FROM (SELECT * FROM $table) temp WHERE temp.system_serial = ? && temp.master_id = ?) * 5)),
                        origin.review_status = 'complete'
                    WHERE origin.system_serial = ?";
        } else { // Reverse Check Test
            $sql = "UPDATE $table origin
                    SET origin.score = (SELECT 10 - ROUND((SELECT sum(COALESCE(temp.system_type_grade ='fail', 0))
                        + COALESCE(temp.hostname_grade ='fail', 0) + COALESCE(temp.ip_grade ='fail', 0)
                        + COALESCE(temp.system_owner_grade ='fail', 0) + COALESCE(temp.room_grade ='fail', 0)
                        + COALESCE(temp.grid_grade ='fail', 0) + COALESCE(temp.asset_found_grade ='fail', 0)
                        + COALESCE(temp.sshable_grade ='fail', 0)
                        FROM (SELECT * FROM $table) temp WHERE temp.system_serial = ? && temp.master_id = ?) * 1.25)),
                        origin.review_status = 'complete'
                    WHERE origin.system_serial = ?";
        }

        if($stmt = mysqli_prepare($link, $sql)){
            // error_log("Inside prepare");
            $array = array();
            if($table == "audit_forward") {
                mysqli_stmt_bind_param($stmt, "sisis", $serial, $masterId, $serial, $masterId, $serial);
                mysqli_stmt_execute($stmt);
                
            } else { // if a Reverse Check is being done
                mysqli_stmt_bind_param($stmt, "sis", $serial, $masterId, $serial);
                mysqli_stmt_execute($stmt);

                
                /* For a Reverse Check Submission, this query sends the score that was just created out to the UI */
                $sql = "SELECT score FROM $table WHERE system_serial = '$serial' && master_id = $masterId";

                if ($result = mysqli_query($link, $sql)) {
    
                    $rows = $result->fetch_all(MYSQLI_ASSOC);

                    $scoreDisplay = "<h2>Asset Score</h2>";
                    $scoreDisplay .= "<div>". $rows[0]['score'] ."</div>";
                
                    $array[1] = $scoreDisplay;
                    $array[2] = $stmt;
                
                }  else {
                    echo json_encode(array('status' => 'error','message' => $link->error));
                }
            }
            /* update the side-bar serial list to show newly completed serial numbers with green dot */
            $serialListHtml = $this->createSerialListFromDb($table, $masterId, false);
            $array[0] = $serialListHtml;
            echo json_encode($array);

        } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    } // end of submitAssetToDb

    public function checkIfComplete($table, $masterId) {
        // error_log("Inside checkSerialInCep serial: ". $serial);
        include ("connection.php");
        header('Content-Type: application/json');
        
        $sql = "SELECT count(*) FROM $table WHERE master_id = $masterId AND COALESCE(review_status, '') = ''";

         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("checkSerialInCep rows array: ". print_r($rows, true));
            
            /* if count is 0, meaning all complete
                Update master table saying that the current check is complete
            */
            echo json_encode();

         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    }

    /* REMOVE QUERY: This query does not need to check for duplicates.
       It thus has no purpose if it's not checking for duplicates. */
    public function checkSerialInCep($serial, $masterId) {
        // error_log("Inside checkSerialInCep serial: ". $serial);
        include ("connection.php");
        header('Content-Type: application/json');
        
        $sql = "SELECT system_serial FROM cep_hw WHERE system_serial = '$serial'";

         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("checkSerialInCep rows array: ". print_r($rows, true));
            if(count($rows) == 0) {
                echo json_encode(0);
                exit;
            }

            $display = "<h4>Check for duplicates:</h4><div>";
            $display .= "<div onclick='getAssetGradeData(\"$serial\", $masterId, \"audit_forward\")'>".htmlentities($rows[0]['system_serial'])."</div></div>";

            // for ($i = 0; $i < count($rows); ++$i) {
            //     // $serial = htmlentities($rows[$i]['system_serial']);
            //     $display .=  "<div onclick='getAssetGradeData(\"$serial\", $masterId, \"audit_forward\")'>".htmlentities($rows[$i]['system_serial'])."</div>";
            // }
            // $display .= "</div>";

            echo json_encode($display);

         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    }

    /* HELPER function for getAssetGradeDataFromCep() function.
       After a serial number is searched for during a 'forward check', right before
       the serial numbers asset data is retrieved from CEP, this function will insert
       a record into the audit_forward table with the serial and master Id numbers.
       So a record will exist to save grade changes to from the UI. */
    public function createForwardCheckAssetRecord($serial, $masterId) {
        // error_log("Inside back-end createForwardCheckAssetRecord");
        include ("connection.php");
        header('Content-Type: application/json');
        
        /* The below query will only insert if the system_serial does not already exist in table.
            'system_serial' is a unique key in the table, so if there's a duplicate key the insert is prevented. */
        $sql = "INSERT INTO audit_forward (system_serial, master_id)
                VALUES (?,?)
                ON DUPLICATE KEY UPDATE id=id";

        if($stmt = $link->prepare($sql)){
            mysqli_stmt_bind_param($stmt, "si", $serial, $masterId);
            mysqli_stmt_execute($stmt);
            // error_log("createForwardCheckAssetRecord AFFECTED ROWS: ". mysqli_affected_rows($link));
            // echo mysqli_affected_rows($stmt);

            // If affected rows is zero,
            if(mysqli_affected_rows($link) < 1) {
                return "record exists";
            } else {
                return $stmt;
            }
                // return a value saying "serial exists" or something
                // see if you can set the return value as a variable and execute function at same time
            
        } else{
            return array('status' => 'error','message' => 'error inserting into the db');
        }
    } //end createForwardCheckAssetRecord

    public function updateGradeInMaster($masterId, $grade, $column) {

        // error_log("Inside createSerialListFromDb serial: ". $serial);
		include ("connection.php");
		header('Content-Type: application/json');
		// $name = $_SESSION['firstName']. " " .$_SESSION['lastName'];

		$sql = "UPDATE audit_master
						SET $column = ?
						WHERE id = ?";
		
		if($stmt = mysqli_prepare($link, $sql)){
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "si", $grade, $masterId);
            mysqli_stmt_execute($stmt);
            
            echo json_encode($stmt);

		} else {
			echo json_encode(array('status' => 'error','message' => $link->error));
		}
    } // end updateAssetGradeInDb

    /* This function retrieves the count for half of the hardware systems of a given site */
    public function getForwardCheckSystemsTotal($site, $masterId) {
        // error_log("getForwardCheckSystemsTotal site: ". $site);
        // error_log("getForwardCheckSystemsTotal masterId: ". $masterId);
        include ("connection.php");
        header('Content-Type: application/json');
        
        $array = array();
        /* Query the audit_master table to check if asset total already exists.
           Then send that total along with the completion count to front-end and exit function */
        $sql1 = "SELECT forward_asset_total, forward_assets_checked FROM audit_master WHERE id = $masterId";
        if ($result = mysqli_query($link, $sql1)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
    
            if($rows[0]['forward_asset_total'] > 0) {
                $array[0] = $rows[0]['forward_asset_total'];
                $array[1] = $rows[0]['forward_assets_checked'];
                /* If any assets have already been checked, create an html serial list from them and send list to front end. */
                if($rows[0]['forward_assets_checked'] > 0) {
                    $serialList = $this->createSerialListFromDb("audit_forward", $masterId, false);
                    $array[2] = $serialList;
                }
                
                echo json_encode($array);
                exit;
            }
            // else the total does not exist, so move on to the query below to create the total
         } else {
            echo (array('status' => 'error','message' => $link->error));
        }        
        /* Create Asset Number Total for Forward Check:
           - Query the cep_hw table with site name.
           - Count only the systems with grid locations where
           - Divide the count by 2
           - Echo the result */
        $sqlAlt = "SELECT ROUND(count(*) / 100) as total FROM cep_hw WHERE hw_site = '$site'
                AND COALESCE(grid_id, '') <> '' AND grid_id <> 0";

         if ($result = mysqli_query($link, $sqlAlt)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("getForwardCheckSystemsTotal rows array: ". print_r($rows, true));
            // error_log("getForwardCheckSystemsTotal total: ". $rows[0]['total']);

            /* Below convert total value from a string to an integer, so it can be inserted into database as integer */
            $total = intval($rows[0]['total']);
            // error_log("getForwardCheckSystemsTotal total: ". $total);
            // error_log("getForwardCheckSystemsTotal getType total: ". gettype($total));

            $subSql = "UPDATE audit_master
                        SET forward_asset_total = ?
                        WHERE id = ?";

            if($stmt = mysqli_prepare($link, $subSql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ii", $total, $masterId);
                mysqli_stmt_execute($stmt);              
                // echo json_encode($stmt);

            } else {
                echo json_encode(array('status' => 'error','message' => $link->error));
            }
            /* Using an array here to keep echo output format consistent */
            $array[0] = $rows[0]['total'];
            echo json_encode($array);

         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    }

    
    public function submitFinalResultsToMaster($masterId) {
        include ("connection.php");
        header('Content-Type: application/json');
        // error_log("submitFinalResultsToMaster Inside");

        /* Below query calculates total counts and grades for a particular master id
           when the 'submit Final Results' button is clicked */
        $sql = "SELECT
                    100 / COUNT(*) GradePercentageOfEachSystemFound,    
                    sum(case when cep_score <= 6 then 1 else 0 end) CepFailedCount,
                    sum(case when legacy_score <= 6 then 1 else 0 end) LegacyFailedCount,
                    100 / (SELECT COUNT(*) FROM audit_forward WHERE master_id = $masterId AND review_status = 'complete') GradePercentageOfEachSystemChecked,
                    (SELECT sum(case when asset_found_grade = 'fail' then 1 else 0 end) FROM audit_forward WHERE master_id = $masterId AND review_status = 'complete') ForwardAssetsNotFoundCount,
                    (SELECT COUNT(*) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete' AND asset_found_grade = 'pass') ReverseFoundTotal,
                    (SELECT sum(case when score <= 6 then 1 else 0 end) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete') ReverseScoreFailedCount,
                    (SELECT sum(case when asset_found_grade = 'fail' then 1 else 0 end) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete') ReverseAssetsNotFoundCount
                FROM audit_forward
                WHERE master_id = $masterId AND review_status = 'complete' AND asset_found_grade = 'pass'";


         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("submitFinalResultsToMaster rows array: ". print_r($rows, true));
            // error_log("getForwardCheckSystemsTotal total: ". $rows[0]['total']);

            /* Calculate Final Test scores */
            /*  */
            $test1Score = ROUND(100 - ($rows[0]['GradePercentageOfEachSystemChecked'] * $rows[0]['ForwardAssetsNotFoundCount']));
            $test2Score = ROUND(100 - ($rows[0]['GradePercentageOfEachSystemFound'] * $rows[0]['CepFailedCount']));
            // SSHABLE: $test3Score = 100 - ($rows[0][''] * $rows[0]['']);
            $test5Score = ROUND(100 - ($rows[0]['GradePercentageOfEachSystemFound'] * $rows[0]['LegacyFailedCount']));
            $test6Score = ROUND(100 - ($rows[0]['ReverseFoundTotal'] * $rows[0]['ReverseScoreFailedCount']));

            // error_log("test1Score: ". $test1Score);
            // error_log("test2Score: ". $test2Score);
            // error_log("test4Score: ". $test4Score);
            // error_log("test5Score: ". $test5Score);
            // error_log("test6Score: ". $test6Score);

            // Need to add Test 3
            $sqlUpdate = "UPDATE audit_master                 
                            SET test_1 = ?,
                                test_2 = ?,
                                test_5 = ?,
                                test_6 = ?,
                                review_status = 'complete'
                            WHERE
                                id = $masterId";

            if($stmt = mysqli_prepare($link, $sqlUpdate)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "iiii", $test1Score, $test2Score, $test5Score, $test6Score);
                mysqli_stmt_execute($stmt);              
                // echo json_encode($stmt);

            } else {
                echo json_encode(array('status' => 'error','message' => $link->error));
            }

            // echo json_encode($rows);

         } else {
            echo (array('status' => 'error','message' => $link->error));
        }
    }

    public function incrementAssetCheckedTotalAndFoundStatus($table, $masterId) {

        // error_log("Inside createSerialListFromDb serial: ". $serial);
		include ("connection.php");
		header('Content-Type: application/json');
		// $name = $_SESSION['firstName']. " " .$_SESSION['lastName'];

		$sql = "UPDATE audit_master
                SET forward_assets_checked = forward_assets_checked + 1
                WHERE id = ?";
		
		if($stmt = mysqli_prepare($link, $sql)){
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "i", $masterId);
            mysqli_stmt_execute($stmt);
            $array = array();
            /* The created serial list below will allow user to immediately see the serial number in the side-bar menu after serial search. */
            $serialList = $this->createSerialListFromDb("audit_forward", $masterId, false);
            $array[0] = $serialList;
            /* Query that retrieves Forward Check asset totals*/
            $subSql = "SELECT forward_asset_total, forward_assets_checked FROM audit_master WHERE id = $masterId";
            if ($result = mysqli_query($link, $subSql)) {

                $rows = $result->fetch_all(MYSQLI_ASSOC);
                /* Below conditional: check if all assets assigned for the Forward Check have been checked or
                   searched for. This is done by checkinf if the checked count equals the asset total */
                if($rows[0]['forward_assets_checked'] === $rows[0]['forward_asset_total']) {
                    // echo a string or something
                    $array[1] = $rows[0]['forward_assets_checked'];
                    $array[2] = "complete";
                    echo json_encode($array);
                    exit;
                }
                // else the Forward Check is not complete so only echo the assets checked count to front-end
                $array[1] = $rows[0]['forward_assets_checked'];
                echo json_encode($array);
                exit;
            } else {
                echo (array('status' => 'error','message' => $link->error));
            }
            
            echo json_encode($stmt);

		} else {
			echo json_encode(array('status' => 'error','message' => $link->error));
		}
    } // end updateAssetGradeInDb

    /* Below Function checks if any of the seleced clumns is null or empty, if so a boolean is returned */
    public function areAllCepFieldsGraded($serial, $masterId, $table) {
        // error_log("Inside checkSerialInCep serial: ". $serial);
        include ("connection.php");
        header('Content-Type: application/json');
        
        /* The query below will select */
        $sql = "SELECT
                sum(case when COALESCE(legacy1_grade, '') = '' then 1 else 0 end) legacy1,
                sum(case when COALESCE(legacy2_grade, '') = '' then 1 else 0 end) legacy2,
                sum(case when COALESCE(system_owner_grade, '') = '' then 1 else 0 end) systemOwnre,
                sum(case when COALESCE(system_type_grade, '') = '' then 1 else 0 end) systemType,
                sum(case when COALESCE(hostname_grade, '') = '' then 1 else 0 end) hostname,
                sum(case when COALESCE(ip_grade, '') = '' then 1 else 0 end) ip,
                sum(case when COALESCE(room_grade, '') = '' then 1 else 0 end) room,
                sum(case when COALESCE(grid_grade, '') = '' then 1 else 0 end) grid,
                sum(case when COALESCE(sshable_grade, '') = '' then 1 else 0 end) sshable
                FROM $table WHERE system_serial = '$serial' AND master_id = $masterId";

         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("checkSerialInCep rows array: ". print_r($rows, true));
            if($rows[0]['legacy1'] == 1 || $rows[0]['legacy1'] == 1 || $rows[0]['systemOwnre'] == 1 || $rows[0]['systemType'] == 1 || $rows[0]['hostname'] == 1 || $rows[0]['ip'] == 1 || $rows[0]['room'] == 1 || $rows[0]['grid'] == 1 || $rows[0]['sshable'] == 1) {
                return false;
            } else {
                return true;
            }
    
         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    }
    
    public function getSiteResultsFromDb($site) {
        // error_log("Inside getAssetDataFromCep site: ". $site);
        include ("connection.php");
        header('Content-Type: application/json');

        // $sql = "SELECT audit_master.site, audit_master.score, audit_master.serialcheck_grade
        //         FROM audit_master
        //         JOIN audit_assets
        //         ON audit_assets.master_id = audit_master.id
        //         WHERE audit_master.status = 'complete' && audit_master.site = '$site'";
        $sql = "SELECT * FROM audit_master
                WHERE site = '$site'";


         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("getSiteResultsFromDb ROWS: ". print_r($rows, true));
            $display = "<div><span>Status</span><span>Site</span><span>Test 1</span><span>Test 2</span><span>Test 3</span><span>Test 5</span><span>Test 6</span><span>Date</span></div>";
            for ($i = 0; $i < count($rows); ++$i) {
                $date = date('m-d-Y', strtotime($rows[$i]['updated']));
                // capitalize site name
                $site = strtoupper(htmlentities($rows[$i]['site']));
                if($rows[$i]['review_status'] !== 'complete') {
                    $status = 'Incomplete';
                } else {
                    $status = 'Complete';
                }
                // Below, change colored dot color to green if test score is greater than given number, else dot color is red
                $test1DotClass = htmlentities($rows[$i]['test_1']) >= 70 ? 'dot-green' : 'dot-red';
                $test2DotClass = htmlentities($rows[$i]['test_2']) >= 70 ? 'dot-green' : 'dot-red';
                $test3DotClass = htmlentities($rows[$i]['test_3']) >= 70 ? 'dot-green' : 'dot-red';
                $test5DotClass = htmlentities($rows[$i]['test_5']) >= 70 ? 'dot-green' : 'dot-red';
                $test6DotClass = htmlentities($rows[$i]['test_6']) >= 70 ? 'dot-green' : 'dot-red';
                $display .= "<div onclick='retrieveOldTest($(\".row-id\", this).text(), $(\".row-site\", this).text())'><span>". $status ."</span><span class='row-site'>". $site ."</span>";
                $display .=  "<span>".$rows[$i]['test_1']."<span class='".$test1DotClass."'></span></span><span>".$rows[$i]['test_2']."<span class='".$test2DotClass."'></span></span>";
                $display .=  "<span>".$rows[$i]['test_3']."<span class='".$test3DotClass."'></span></span>";
                $display .=  "<span>".$rows[$i]['test_5']."<span class='".$test5DotClass."'></span></span><span>".$rows[$i]['test_6']."<span class='".$test6DotClass."'></span></span>";
                $display .= "<span>". $date ."</span><span>id: <span class='row-id'>". $rows[$i]['id'] ."</span></span></div>";
            }
            // error_log("DISPLAY: ". $display);
            echo json_encode($display);

         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    }

} //End Class


  if (isset($_POST["action"]) && !empty($_POST["action"])) { //Checks if action value exists
    
    $action = $_POST["action"];
	  
    switch($action) { //Switch case for value of action
        
        case "addRandomAssets":
        $activePmr = new Index();
            $activePmr->addRandomAssetsToTable($_POST["masterId"], $_POST["site"]);
            break;

		case "updateAssetGrade":
			$activePmr = new Index();
				$activePmr->updateAssetGradeInDb($_POST["table"], $_POST["column"], $_POST["grade"], $_POST["masterId"], $_POST["serial"]);
            break;
            
        case "createAuditMasterRecord":
			$activePmr = new Index();
				$activePmr->createAuditMasterRecord($_POST["site"]);
            break;
            
        case "getAssetGradeData":
            $activePmr = new Index();
                $activePmr->getAssetGradeDataFromCep($_POST["serial"], $_POST["masterId"], $_POST["table"]);
            break;

        case "getAssetGrades":
            $activePmr = new Index();
                $activePmr->getAssetGradesFromDb($_POST["serial"], $_POST["table"], $_POST["masterId"]);
            break;
        
        case "submitAsset":
            $activePmr = new Index();
                $activePmr->submitAssetToDb($_POST["serial"], $_POST["masterId"], $_POST["table"]);
            break;

        case "checkSerial":
            $activePmr = new Index();
                $activePmr->checkSerialInCep($_POST["serial"], $_POST["masterId"]);
            break;

        case "updateGradeInMaster":
            $activePmr = new Index();
                $activePmr->updateGradeInMaster($_POST["masterId"], $_POST["grade"], $_POST["column"]);
            break;
   
        case "getForwardCheckSystemsTotal":
            $activePmr = new Index();
                $activePmr->getForwardCheckSystemsTotal($_POST["site"], $_POST["masterId"]);
            break;
    
        case "submitFinalResults":
            $activePmr = new Index();
                $activePmr->submitFinalResultsToMaster($_POST["masterId"]);
            break;
            
        case "incrementAssetCheckedTotalAndFoundStatus":
            $activePmr = new Index();
                $activePmr->incrementAssetCheckedTotalAndFoundStatus($_POST["table"], $_POST["masterId"]);
            break;
            
        case "areAllCepFieldsGraded":
            $activePmr = new Index();
                $activePmr->areAllCepFieldsGraded($_POST["serial"], $_POST["masterId"], $_POST["table"]);
            break;
            
        case "createSerialList":
            $activePmr = new Index();
                $activePmr->createSerialListFromDb($_POST["table"], $_POST["masterId"], $_POST["fromFrontend"]);
            break;

        case "getSiteResults":
            $activePmr = new Index();
                $activePmr->getSiteResultsFromDb($_POST["site"]);
            break;
    }
  }

?>