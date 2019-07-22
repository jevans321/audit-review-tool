<?php   
// session_start();

class Index
{

    public function getSiteResultsFromDb($site) {
        // error_log("Inside getAssetDataFromCep site: ". $site);
        include ("connection.php");
        header('Content-Type: application/json');

        $sql = "SELECT * FROM audit_master
                WHERE site = '$site'";


         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("getSiteResultsFromDb ROWS: ". print_r($rows, true));
            if(count($rows) < 1) {
                echo json_encode("no results");
                exit;
            }
            $display = "<div><span>Status</span><span>Site</span><span title='Forward Check: Confirm system exists in CEP'>Test 1</span><span title='Forward Check: Confirm data integrity in CEP'>Test 2</span><span title='Forward Check: SSH Connection'>Test 3</span><span title='Forward Check: Legacy Deep Dive'>Test 4</span><span title='Reverse Check: Confirm data integrity on the floor'>Test 5</span><span>Date</span></div>";
            for ($i = 0; $i < count($rows); ++$i) {
                $date = date('m-d-Y', strtotime($rows[$i]['updated']));
                // capitalize site name
                $site = strtoupper(htmlentities($rows[$i]['site']));
                if($rows[$i]['review_status'] !== 'complete') {
                    $status = 'Incomplete';
                } else {
                    $status = 'Complete';
                }

                $test1DotClass = htmlentities($rows[$i]['test_1']) != null ? 'dot-green-sml' : '';
                $test2DotClass = htmlentities($rows[$i]['test_2']) != null ? 'dot-green-sml' : '';
                $test3DotClass = htmlentities($rows[$i]['test_3']) != null ? 'dot-green-sml' : '';
                $test4DotClass = htmlentities($rows[$i]['test_4']) != null ? 'dot-green-sml' : '';
                $test5DotClass = htmlentities($rows[$i]['test_5']) != null ? 'dot-green-sml' : '';
                $display .= "<div onclick='retrieveOldTest($(\".row-id\", this).text(), $(\".row-site\", this).text())'><span>". $status ."</span><span class='row-site'>". $site ."</span>";
                $display .=  "<span>".$rows[$i]['test_1']."<span class='".$test1DotClass."'></span></span><span>".$rows[$i]['test_2']."<span class='".$test2DotClass."'></span></span>";
                $display .=  "<span>".$rows[$i]['test_3']."<span class='".$test3DotClass."'></span></span>";
                $display .=  "<span>".$rows[$i]['test_4']."<span class='".$test4DotClass."'></span></span><span>".$rows[$i]['test_5']."<span class='".$test5DotClass."'></span></span>";
                $display .= "<span>". $date ."</span><span>id: <span class='row-id'>". $rows[$i]['id'] ."</span></span></div>";
            }
            // error_log("DISPLAY: ". $display);
            echo json_encode($display);

         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    } //end getSiteResultsFromDb

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

} //End Class


  if (isset($_POST["action"]) && !empty($_POST["action"])) { //Checks if action value exists
    
    $action = $_POST["action"];
	  
    switch($action) { //Switch case for value of action
            
        case "createAuditMasterRecord":
			$activePmr = new Index();
				$activePmr->createAuditMasterRecord($_POST["site"]);
            break;

        case "getSiteResults":
            $activePmr = new Index();
                $activePmr->getSiteResultsFromDb($_POST["site"]);
            break;

    }
  }

?>