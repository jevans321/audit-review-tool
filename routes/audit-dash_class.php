<?php   
// session_start();

class Index
{


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
                WHERE review_status = 'complete' && site = '$site'";


         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("getSiteResultsFromDb ROWS: ". print_r($rows, true));
            $display = "<div><span>Site</span><span>Test 1</span><span>Test 2</span><span>Test 3</span><span>Test 4</span><span>Test 5</span><span>Test 6</span><span>Date</span></div>";
            for ($i = 0; $i < count($rows); ++$i) {
                $date = date('m-d-Y', strtotime($rows[$i]['updated']));
                // capitalize site name
                $site = strtoupper(htmlentities($rows[$i]['site']));
                // Below, change colored dot color to green if test score is greater than given number, else dot color is red
                $test1DotClass = htmlentities($rows[$i]['test_1']) >= 70 ? 'dot-green' : 'dot-red';
                $test2DotClass = htmlentities($rows[$i]['test_2']) >= 70 ? 'dot-green' : 'dot-red';
                $test3DotClass = htmlentities($rows[$i]['test_3']) >= 70 ? 'dot-green' : 'dot-red';
                $test4DotClass = htmlentities($rows[$i]['test_4']) >= 70 ? 'dot-green' : 'dot-red';
                $test5DotClass = htmlentities($rows[$i]['test_5']) >= 70 ? 'dot-green' : 'dot-red';
                $test6DotClass = htmlentities($rows[$i]['test_6']) >= 70 ? 'dot-green' : 'dot-red';
                $display .= "<div><span>". $site ."</span>";
                $display .=  "<span>".$rows[$i]['test_1']."<span class='".$test1DotClass."'></span></span><span>".$rows[$i]['test_2']."<span class='".$test2DotClass."'></span></span>";
                $display .=  "<span>".$rows[$i]['test_3']."<span class='".$test3DotClass."'></span></span><span>".$rows[$i]['test_4']."<span class='".$test4DotClass."'></span></span>";
                $display .=  "<span>".$rows[$i]['test_5']."<span class='".$test5DotClass."'></span></span><span>".$rows[$i]['test_6']."<span class='".$test6DotClass."'></span></span>";
                $display .= "<span>". $date ."</span><span>id: ". $rows[$i]['id'] ."</span></div>";
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

        case "getSiteResults":
            $activePmr = new Index();
                $activePmr->getSiteResultsFromDb($_POST["site"]);
            break;

    }
  }

?>