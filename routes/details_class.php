<?php   
// session_start();

class Index
{
	public function updateAssetValueInDb($table, $column, $value, $masterId, $serial) {
		include ("connection.php");
		header('Content-Type: application/json');
		// $name = $_SESSION['firstName']. " " .$_SESSION['lastName'];

        /* The first conditional statement below sets the forward-checked systems review status
           to complete when a serial number is not found in CEP */
        if($table == "audit_forward" && $column == "asset_found_grade" && $value === "fail") {
            $sql = "UPDATE $table
                    SET $column = ?, review_status = 'complete'
                    WHERE master_id = ? && system_serial = ?";
        } else {
            $sql = "UPDATE $table
                    SET $column = ?
                    WHERE master_id = ? && system_serial = ?";
        }
		
		if($stmt = mysqli_prepare($link, $sql)){
			mysqli_stmt_bind_param($stmt, "sis", $value, $masterId, $serial);
            mysqli_stmt_execute($stmt);
            /* This current function updateAssetValueInDb is also being used in the function getAssetGradeDataFromCep to
                update a 'review_status' column. This is done on the back-end and I don't want to echo. In the cases I'm calling
                this function from the front-end I want to echo back. This conditional allows me to do this. */
            if($column != "review_status" && $column != "score") { 
                echo json_encode($stmt);
            }

		} else {
			echo json_encode(array('status' => 'error','message' => $link->error));
		}
    } // end updateAssetValueInDb
    
    public function generateReverseCheckAssets($masterId, $site) {
        // error_log("Inside back-end generateReverseCheckAssets ID: ". $id);

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
                LIMIT 3";

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
                    
    } // end generateReverseCheckAssets

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
    public function getAssetGradeDataFromCep($serial, $masterId, $table, $site) {
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
                        WHERE hw.system_serial = '$serial' AND hw.hw_site = '$site'";


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
                    /* Updated the review_status in the audit_reverse table to 'complete'. */
                    $this->updateAssetValueInDb($table, "review_status", "complete", $masterId, $serial);
                    $this->updateAssetValueInDb($table, "score", 0, $masterId, $serial);
                    $serialList = $this->createSerialListFromDb("audit_reverse", $masterId, false);
                    $array[2] = $serialList; //substr($serialList, 0, -3);
                }
                if ($createRecordReturnValue == "record exists") {
                    $array[5] = $createRecordReturnValue;
                }
                // error_log("serialList Entire ARRAY:..................... ". print_r($array, true));
                echo json_encode($array);
                exit;
              /* Serial Does Not Exist In CEP Conditional: handles if no records are returned for a serial, so the serial does not exist in CEP */
            } elseif(count($rows) < 1) {
                $array = array(
                    0 => "<span class='audit-title'>No records found for serial \"".$serial."\"</span>",
                    1 => "empty"
                );
                if ($createRecordReturnValue == "record exists") {
                    $array[5] = $createRecordReturnValue;
                }
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
                                    <select class='asset-select-box'  onchange='updateAssetValue(\"$table\", \"asset_found_grade\", $(this).val(), $masterId, \"$serial\")'>
                                    <option  value='' disabled='' selected=''>Select Grade</option>
                                    <option value='pass'>Yes</option>
                                    <option value='fail'>No</option>
                                    </select>
                                </div>
                            </div>";
            }
            $display .= "<div>
                            <div class='ssh-row'>SSHable:</div><div class='ssh-loader'></div>
                            <div>
                                <select class='ssh-select-box'  onchange='updateAssetValue(\"$table\", \"sshable_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Yes</option>
                                <option value='fail'>No</option>
                                </select>
                            </div>
                        </div>";
            $sytemOwnerValue = $rows[0]['system_owner'] ? htmlentities($rows[0]['system_owner']) : "<i>Item not available</i>";
            $display .=  "<div>
                            <div>System Owner:&nbsp;<span id='sys-owner-row' class='span-static-val'>". $sytemOwnerValue ."</span></div>
                            <div>
                                <select class='sys-owner-select-box'  onchange='updateAssetValue(\"$table\", \"system_owner_grade\", $(this).val(), $masterId, \"$serial\")'>
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
                                <select class='system-select-box'  onchange='updateAssetValue(\"$table\", \"system_type_grade\", $(this).val(), $masterId, \"$serial\")'>
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
                                <select class='host-select-box'  onchange='updateAssetValue(\"$table\", \"hostname_grade\", $(this).val(), $masterId, \"$serial\")'>
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
                                <select class='ip-select-box'  onchange='updateAssetValue(\"$table\", \"ip_grade\", $(this).val(), $masterId, \"$serial\")'>
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
                                <select class='room-select-box'  onchange='updateAssetValue(\"$table\", \"room_grade\", $(this).val(), $masterId, \"$serial\")'>
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
                                <select class='grid-select-box'  onchange='updateAssetValue(\"$table\", \"grid_grade\", $(this).val(), $masterId, \"$serial\")'>
                                <option  value='' disabled='' selected=''>Select Grade</option>
                                <option value='pass'>Pass</option>
                                <option value='fail'>Fail</option>
                                <option value='na'>N/A</option>
                                </select>
                            </div>
                        </div>";
            $display2 = "";
            if ($table == "audit_forward") {
                $display2 .= "<div id='legacy-btn' onclick='openLegacyModal(createdMasterId, submittedSerial)'>Legacy Check</div>";
            }
            $display2 .= "<div class='data-buttons text-center'><button style='margin: 10px 0; width: 100%;' onclick='runSsh($(\"#ip-row\").text(), \"$serial\", $masterId, \"$table\")' class='systemButton ajaxButton'>SSHable Test</button>              
                          <button style='margin-bottom: 25px; width: 100%;' onclick='submitAsset(\"$serial\", $masterId, \"$table\")' class='systemButton ajaxButton'>Complete Asset Check</button></div>";

            $array[0] = $display;
            $array[1] = $display2;
            $array[2] = $table;
            
            echo json_encode($array);
  
         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
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
                $display = "<div><h2>Asset Score</h2></div>";
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

        // check if all grades have been given before allowing a user to submit the current asset as complete:
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
                    exit;
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
        // $sql1 = "SELECT * FROM audit_master WHERE site = $site";
        if ($result = mysqli_query($link, $sql1)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            
            // If a total count exists in database retrieve total and send to front-end, then exit function
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
            
        } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
            exit;
        }
        // If an asset total Does Not exist after query check above, move on to the query below to create the total
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
                exit;
            }
            /* Using an array here to keep echo output format consistent */
            $array[0] = $rows[0]['total'];
            echo json_encode($array);

        } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
            exit;
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
                echo json_encode(array('status' => 'error','message' => $link->error));
                exit;
            }         
            echo json_encode($stmt);

		} else {
			echo json_encode(array('status' => 'error','message' => $link->error));
		}
    } // end updateAssetValueInDb

    /* Below Function checks if all data fields for a reviewed system have been graded. Returns a boolean. */
    public function areAllCepFieldsGraded($serial, $masterId, $table) {
        include ("connection.php");
        header('Content-Type: application/json');
        
        /* The query below will check if a selected column is null or empty. A boolean is then returned */
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
            if($rows[0]['legacy1'] == 1 || $rows[0]['legacy1'] == 1 || $rows[0]['systemOwnre'] == 1 || $rows[0]['systemType'] == 1 || $rows[0]['hostname'] == 1 || $rows[0]['ip'] == 1 || $rows[0]['room'] == 1 || $rows[0]['grid'] == 1 || $rows[0]['sshable'] == 1) {
                return false;
            } else {
                return true;
            }
    
         } else {
            return (array('status' => 'error','message' => $link->error));
        }
    }

    public function markScoresCompleteInMaster($table, $masterId) {
        include ("connection.php");
        header('Content-Type: application/json');
        // error_log("submitFinalResultsToMaster Inside");
        if($table == "audit_forward") { // Forward Check
            $sql = "SELECT
                    100 / (SELECT COUNT(*) FROM audit_forward WHERE master_id = $masterId AND review_status = 'complete') GradePercentageOfEachFoundAssetOnTheFloor,
                    sum(case when cep_score <= 6 then 1 else 0 end) TotalFailedCepScoresFromForwardCheck,
                    sum(case when legacy_score <= 6 then 1 else 0 end) TotalFailedLegacyScoresFromForwardCheck,
                    (SELECT sum(case when asset_found_grade = 'fail' then 1 else 0 end) FROM audit_forward WHERE master_id = $masterId AND review_status = 'complete') TotalAssetsNotFoundInCep,
                    (SELECT sum(case when sshable_grade = 'fail' then 1 else 0 end) FROM audit_forward WHERE master_id = $masterId AND review_status = 'complete') TotalAssetsNotSShableInCep
                    FROM audit_forward WHERE master_id = $masterId";
        } else { // Reverse Check
            $sql = "SELECT
                    100 / (SELECT COUNT(*) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete') GradePercentageOfEachAsset,
                    (SELECT COUNT(*) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete' AND asset_found_grade = 'pass') TotalFoundOfReverseCheckedAssets,
                    (SELECT sum(case when score <= 6 then 1 else 0 end) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete') TotalFailedOfReverseCheckedAssets,
                    (SELECT sum(case when asset_found_grade = 'fail' then 1 else 0 end) FROM audit_reverse WHERE master_id = $masterId AND review_status = 'complete') TotalNotFoundOfReverseCheckedAssets
                    FROM audit_reverse
                    group by TotalFoundOfReverseCheckedAssets";
        }

        if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("submitFinalResultsToMaster rows array: ". print_r($rows, true));
            // error_log("getForwardCheckSystemsTotal total: ". $rows[0]['total']);

            /* Calculate Final Test scores */
            /*  */
            if($table == "audit_forward") { // Forward Check
                $test1Score = ROUND(100 - ($rows[0]['GradePercentageOfEachFoundAssetOnTheFloor'] * $rows[0]['TotalAssetsNotFoundInCep']));
                $test2Score = ROUND(100 - ($rows[0]['GradePercentageOfEachFoundAssetOnTheFloor'] * $rows[0]['TotalFailedCepScoresFromForwardCheck']));
                $test3Score = ROUND(100 - ($rows[0]['GradePercentageOfEachFoundAssetOnTheFloor'] * $rows[0]['TotalAssetsNotSShableInCep']));
                // SSHABLE: $test3Score = 100 - ($rows[0][''] * $rows[0]['']);
                $test4Score = ROUND(100 - ($rows[0]['GradePercentageOfEachFoundAssetOnTheFloor'] * $rows[0]['TotalFailedLegacyScoresFromForwardCheck']));

                $sqlUpdate = "UPDATE audit_master                 
                                SET test_1 = ?, test_2 = ?, test_3 = ?, test_4 = ?, is_forward_complete = 'yes'
                                WHERE id = $masterId";

                    if($stmt = mysqli_prepare($link, $sqlUpdate)){
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt, "iiii", $test1Score, $test2Score, $test3Score, $test4Score);
                        mysqli_stmt_execute($stmt);
                        
                    } else {
                        echo json_encode(array('status' => 'error','message' => $link->error));
                    }

            } else { // Reverse Check

                $test5Score = ROUND(100 - ($rows[0]['GradePercentageOfEachAsset'] * $rows[0]['TotalFailedOfReverseCheckedAssets']));
                $sqlUpdate = "UPDATE audit_master                 
                                SET test_5 = ?, is_reverse_complete = 'yes'
                                WHERE id = $masterId";

                    if($stmt = mysqli_prepare($link, $sqlUpdate)){
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt, "i", $test5Score);
                        mysqli_stmt_execute($stmt); 
                    
                        // error_log("test1Score: ". $test1Score);
                        // error_log("test2Score: ". $test2Score);
                        // error_log("test4Score: ". $test4Score);
                        // error_log("test5Score: ". $test5Score);
                        // error_log("test6Score: ". $test6Score);
                
                        // echo json_encode($stmt);

                    } else {
                        echo json_encode(array('status' => 'error','message' => $link->error));
                    }

                // echo json_encode($rows);
            }

        } else {
            echo (array('status' => 'error','message' => $link->error));
        }
    }

    public function areAllAssetChecksComplete($table, $masterId) {
        include ("connection.php");
        header('Content-Type: application/json');
        
        $sql = "SELECT count(*) FROM $table WHERE master_id = $masterId AND COALESCE(review_status, '') = ''";

         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $count = $rows[0]['count(*)'];
            
            /* if count is 0, meaning all complete
                Update completion test scores in master table saying that those tests are complete
            */
            if($count == 0) { // if no incomplete tests are found then all asset reviews are complete, so update master table test scores
                $this->markScoresCompleteInMaster($table, $masterId);
                echo json_encode(true);
                exit;
            }
            echo json_encode(false);

         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    }

    
    public function isForwardAndReverseCheckComplete($masterId) {
        // error_log("Inside checkSerialInCep serial: ". $serial);
        include ("connection.php");
        header('Content-Type: application/json');
        
        $sql = "SELECT is_forward_complete, is_reverse_complete FROM audit_master WHERE id = $masterId";

         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);

            if($rows[0]['is_forward_complete'] == 'yes' && $rows[0]['is_reverse_complete'] == 'yes') {
                echo json_encode(true);
                exit;
            }
            echo json_encode(false);

         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    }

    public function submitFinalResultsToMaster($masterId) {
        // error_log("Inside submitFinalResultsToMaster");
        include ("connection.php");
        header('Content-Type: application/json');
        
        $sql = "UPDATE audit_master                 
                        SET review_status = 'complete'
                        WHERE id = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "i", $masterId);
            mysqli_stmt_execute($stmt); 

            echo json_encode($stmt);

        } else {
        echo json_encode(array('status' => 'error','message' => $link->error));
        }
    }

} //End Class


  if (isset($_POST["action"]) && !empty($_POST["action"])) { //Checks if action value exists
    
    $action = $_POST["action"];
	  
    switch($action) { //Switch case for value of action

        case "areAllAssetChecksComplete":
            $activePmr = new Index();
                $activePmr->areAllAssetChecksComplete($_POST["table"], $_POST["masterId"]);
            break;

        case "areAllCepFieldsGraded":
            $activePmr = new Index();
                $activePmr->areAllCepFieldsGraded($_POST["serial"], $_POST["masterId"], $_POST["table"]);
            break;

        case "createSerialList":
            $activePmr = new Index();
                $activePmr->createSerialListFromDb($_POST["table"], $_POST["masterId"], $_POST["fromFrontend"]);
            break;

        case "generateReverseCheckAssets":
            $activePmr = new Index();
                $activePmr->generateReverseCheckAssets($_POST["masterId"], $_POST["site"]);
            break;
            
        case "getAssetGradeData":
            $activePmr = new Index();
                $activePmr->getAssetGradeDataFromCep($_POST["serial"], $_POST["masterId"], $_POST["table"], $_POST["site"]);
            break;

        case "getAssetGrades":
            $activePmr = new Index();
                $activePmr->getAssetGradesFromDb($_POST["serial"], $_POST["table"], $_POST["masterId"]);
            break;
        
        case "getForwardCheckSystemsTotal":
            $activePmr = new Index();
                $activePmr->getForwardCheckSystemsTotal($_POST["site"], $_POST["masterId"]);
            break;

        case "incrementAssetCheckedTotalAndFoundStatus":
            $activePmr = new Index();
                $activePmr->incrementAssetCheckedTotalAndFoundStatus($_POST["table"], $_POST["masterId"]);
            break;
            
        case "isForwardAndReverseCheckComplete":
            $activePmr = new Index();
                $activePmr->isForwardAndReverseCheckComplete($_POST["masterId"]);
            break;

        case "submitAsset":
            $activePmr = new Index();
                $activePmr->submitAssetToDb($_POST["serial"], $_POST["masterId"], $_POST["table"]);
            break;
    
        case "submitFinalResults":
            $activePmr = new Index();
                $activePmr->submitFinalResultsToMaster($_POST["masterId"]);
            break;

        case "updateAssetValue":
			$activePmr = new Index();
				$activePmr->updateAssetValueInDb($_POST["table"], $_POST["column"], $_POST["value"], $_POST["masterId"], $_POST["serial"]);
            break;
            
    }
  }

?>