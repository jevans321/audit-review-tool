<?php   
// session_start();

class Index
{


    public function getIpsNotInCep($startIp, $endIp) {
        // error_log("Inside getIpsNotinCep : ". $site);
        include ("connection.php");
        header('Content-Type: application/json');

        $sql = "SELECT ip_address FROM cep
                WHERE INET_ATON(ip_address) BETWEEN INET_ATON('$startIp') AND INET_ATON('$endIp')";


         if ($result = mysqli_query($link, $sql)) {

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            // error_log("getSiteResultsFromDb ROWS: ". print_r($rows, true));

            $start_lastpart_array = explode('.', $startIp);
            $start_lastpart = $start_lastpart_array[3];

            $end_lastpart_array = explode('.', $endIp);
            $end_lastpart = $end_lastpart_array[3];

            // echo $start_lastpart . ' ' . $end_lastpart . PHP_EOL;

            /* Create array of full ip range from from start and end input-points */
            $range = array();
            $incrementor = $start_lastpart;
            for (; $incrementor <= $end_lastpart; $incrementor++) {
                    $range[] = $start_lastpart_array[0] . '.' . $start_lastpart_array[1] . '.' . $start_lastpart_array[2] . '.' . $incrementor;
            }

            /* Create array of IPs returned from query, since the query return is an array of nested arrays representing each row */
            $sqlQueryInRange = array();
            for ($i = 0; $i < count($rows); ++$i) {
                $sqlQueryInRange[] = $rows[$i]['ip_address'];
            }

            // echo print_r($range, true);

            // $sqlQueryInRange = array('9.30.166.36');
            error_log("sqlQueryInRange array: ". print_r($sqlQueryInRange, true));
            error_log("range array: ". print_r($range, true));
            /* The 'array_diff' function below compares the first array '$range' against the second array '$sqlQueryInRange'
               and returns the values in $range that are not present in $sqlQueryInRange. */
            $result = array_diff($range, $sqlQueryInRange);
            error_log("result array: ". print_r($result, true));
            // echo print_r($result, true);
            echo json_encode($result);

         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    }

    
    
} //End Class

  if (isset($_POST["action"]) && !empty($_POST["action"])) { //Checks if action value exists
    
    $action = $_POST["action"];
	  
    switch($action) { //Switch case for value of action

        case "getIpsNotInCep":
            $activePmr = new Index();
                $activePmr->getIpsNotInCep($_POST["start"], $_POST["end"]);
            break;

    }
  }

?>