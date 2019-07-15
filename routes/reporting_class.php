<?php   
// session_start();

class Library
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
            $output  = "<table class='contentTable' id='reportingTable'>";
            $output .= "<thead>";
            $output .= "<tr>";
            $output .= "<th>Count</th>";
            $output .= "<th>IP's Not in CEP</th>";
            $output .= "</tr>";
            $output .= "</thead>";
            $output .= "<tbody>";
            // for ($i = 0; $i < count($result); ++$i) {
            $i = 1;
            foreach ($result as $ip) {
                $output .= "<tr>";
                $output .= "<td>".$i."</td>";
                $output .= "<td>".$ip."</td>";
                $output .= "</tr>";
                $i++;  
            }
            $output .= "</tbody>";
            $output .= "</table>";
            echo json_encode($output);

         } else {
            echo json_encode(array('status' => 'error','message' => $link->error));
        }
    } //end getIpsNotInCep

    public function getAssets($type,$site)
	 {
		//error_log("getAllQuotes");
        switch($type){
            case 'miss_dev_con_active':
                //missing development contacts on active systems 
                $strSQL = "select * from cep where system_site = '$site' and coalesce(development_contact, '') = '' and system_status = 'Active'";
                break;
            case 'ip_no_data':
                // ips with no other data (no os causes unnecessary delay determining os and many of these have no login information)
                $strSQL = "select * from cep where system_site = '$site' and coalesce(ip_address, '') != '' and coalesce(operating_system_platform, '') = '' and coalesce(system_type, '') = '' and coalesce(system_serial, '') = ''";
                break;
            case 'miss_ip_status':
                // records with missing ip_address and missing system_status (this is an unkonwn factor of data)
                $strSQL = "select * from cep cep where system_site = '$site' and coalesce(system_status, '') = '' and coalesce(ip_address, '') = ''";
                break;
            case 'ip_not_in_cep':
                // records with ips that are not in cep_ips (causes records to be missed because of missing ping data and ssh data)
                $strSQL = "select c.id, c.system_hostname, c.system_type, c.system_serial, c.system_site, c.ip_address, c.managed_by, c.business_unit, c.system_classification,
                           c.development_contact from cep c left join cep_ips i on i.ip = c.ip_address 
                           where i.ip is null and coalesce(c.ip_address, '') != '' and system_site = '$site'";
                break;
            case 'miss_vlan_def':
                // missing vlan_spec definitions (we assume that it's permissive if missing)
                $strSQL = "select c.id, c.system_hostname, c.system_type, c.system_serial, c.system_site, c.ip_address, c.managed_by, c.business_unit, c.system_classification,
                           c.development_contact from cep c join cep_ips i on i.ip = c.ip_address left join vlan_spec v on v.id = i.vlan_id where v.id is null 
                           and c.system_site = '$site'";
                break;
            case 'dev_con_miss_bp':
                // development contact records that are missing from bluepages (useless development contact info)
                $strSQL = "select * from cep c left join bluepages_ldif b on c.development_contact = b.mail where c.system_site = '$site' and coalesce(development_contact, '') != '' 
                           and mail is null";
                break;
            case 'no_access_reg_sec':
                // no access, but registered with security 
                $strSQL = "select c.id, c.system_hostname, c.system_type, c.system_serial, c.system_site, c.ip_address, c.managed_by, c.business_unit, c.system_classification,
                           c.development_contact from cep c join cep_ips i on i.ip = c.ip_address where system_site = '$site' and coalesce(security_status, '') != '' 
                           and i.system_accessible = 'No' and system_online = 'Yes'";
                break;    
        }
        
        
		 	include ("connection.php"); 
            include('reporting_build_table.php');
        
			$userArray = array();
			$user = strtolower($_SESSION['email']);

            if ($result = mysqli_query($link, $strSQL)) {
				
                while ($row = $result->fetch_assoc()){
						 //var_dump($row);
						 //error_log($row["status"]);
						 $response[] = $row;
				}
				//var_dump($response);
                $output = buildTable($response,'','');
				echo $output;
            }
	 } //end getAssets
    
    public function getAllSites()
	 {
		include ("connection.php"); 
		$userArray = array();
		$strSQL = "select distinct system_site from cep where system_site is not null";

        if ($result = mysqli_query($link, $strSQL)) {			
		    //header('Content-Type: application/json');
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                //error_log("Data: " . $row['system_site']);
                $json[] = $row;
            }
			error_log("json: " . $json[0]);
            echo json_encode($json);		
        }else{
			 echo json_encode(array('status' => 'error'));
        }
	 } //end getAllSites

    
    
} //End Class

  if (isset($_POST["action"]) && !empty($_POST["action"])) { //Checks if action value exists
    
    $action = $_POST["action"];
	  
    switch($action) { //Switch case for value of action

        case "getIpsNotInCep":
            $activePmr = new Library();
                $activePmr->getIpsNotInCep($_POST["start"], $_POST["end"]);
            break;

        case "getAllSites":
			$active = new Library();
    		$active->getAllSites(); 
			break;
			//Gets Projects
            
         case "getAssets":
			$active = new Library();
    		$active->getAssets($_POST["type"],$_POST["site"]); 
			break;
			//Gets Projects

    } //end switch
  }

?>