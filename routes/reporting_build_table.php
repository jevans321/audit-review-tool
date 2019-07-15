<?php

	#Table for OS & HW
	function buildTable($response,$searchValues,$infotype){
		$output = NULL;
		//error_log();
		if($response != ''){
			if(count($response) < 5000){
				$output = "<span><input type='text' class='filterSearch form-control' placeholder='filter search'></span>";
			}
			#$output .= "<span class='multiUpdate'>Multi Update</span>";
			
			// downloadTableData()
			#$output .= "<span class='csvDownload' onclick='csvDownload(\"".$searchValues."\")'>Download CSV</span>";
			$output .= "<span class='csvDownload' onclick='downloadTableData()'>Download CSV</span>";
			$output .= "<div class='recordCount'>Records: ".count($response)."</div>";	
			$output .= "<table class='contentTable' id='reportingTable'>";
			$output .= "<thead>";
			$output .= "<tr>";
			#$output .= "<th></th>";
			#$output .= "<th></th>";
			/*if($_SESSION['role'] == 'edit'){
				$output .= "<th></th>";
			}*/
			$output .= "<th>ID</th>";
			$output .= "<th>Hostname</th>";
            $output .= "<th>Type</th>";
			$output .= "<th>Serial</th>";
			$output .= "<th>Site</th>";
			$output .= "<th>IP</th>";
			//$output .= "<th>Owner</th>";
			$output .= "<th>Managed</th>";
			$output .= "<th>Business Unit</th>";
			$output .= "<th>Classification</th>";
			$output .= "<th>DevContact</th>";
			//$output .= "<th>Kind</th>";
			//$output .= "<th>Room</th>";
			//$output .= "<th>Grid</th>";
			//$output .= "<th>SSH</th>";
			//$output .= "<th>Ping</th>";
			$output .= "</tr>";
			$output .= "</thead>";
			$output .= "<tbody>";
				foreach ($response as $response) {
					$output .= "<tr class='assetItem' data-id='".$response['id']."'>";
					#$output .= "<td class='selectItem'><label class='checkContainer'><input type='checkbox'><span class='checkmark'></span></label></td>";
					#$output .= "<td><div class='editItem' onclick='updateAssets(".$response['id'].",\"$infotype\")'></div></td>";
					/*if($_SESSION['role'] == 'edit'){
						$output .= "<td><div class='deleteItem' onclick='deleteAsset(\"".$response['id']."\",\"\")'></div></td>";
					}*/
					$output .= "<td>".$response['id']."</td>";
					$output .= "<td>".$response['system_hostname']."</td>";
                    $output .= "<td>".$response['system_type']."</td>";
					$output .= "<td>".$response['system_serial']."</td>";
					$output .= "<td>".$response['system_site']."</td>";
					$output .= "<td>".$response['ip_address']."</td>";
					//$output .= "<td>".$response['system_owner']."</td>";
					$output .= "<td>".$response['managed_by']."</td>";
					$output .= "<td>".$response['business_unit']."</td>";
					$output .= "<td>".$response['system_classification']."</td>";
					$output .= "<td>".$response['development_contact']."</td>";
					//$output .= "<td>".$response['system_kind']."</td>";
					//$output .= "<td>".$response['room']."</td>";
					//$output .= "<td>".$response['grid']."</td>";
					//$output .= "<td>".$response['system_accessible']."</td>";
					//$output .= "<td>".$response['system_online']."</td>";
					$output .= "</tr>";
				}
			$output .= "</tbody>";
			$output .= "<table>";
				return  $output;
			}else{
				header('Content-Type: application/json');
				return json_encode(array('status' => 'error','details' => 'No records found'));
			}
	}

	#Table for Hardware Only Searches
	function buildTableHardware($response,$searchValues,$infotype){
		$output = NULL;
		//error_log();
		if($response['records_array'] != ''){
			if(count($response['records_array']) < 5000){
				$output = "<span><input type='text' class='filterSearch form-control' placeholder='filter search'></span>";
			}
			
			$output .= "<span class='multiUpdate'>Multi Update</span>";
			$output .= "<span class='csvDownload' onclick='csvDownload(\"".$searchValues."\")'>Download CSV</span>";
			$output .= "<div class='recordCount'>Records: ".count($response['records_array'])."</div>";	
			$output .= "<table class='contentTable'>";
			$output .= "<thead>";
			$output .= "<tr>";
			$output .= "<th></th>";
			$output .= "<th></th>";
			/*if($_SESSION['role'] == 'edit'){
				$output .= "<th></th>";
			}*/
			$output .= "<th>Type</th>";
			$output .= "<th>Serial</th>";
			$output .= "<th>Status</th>";
			$output .= "<th>Eamt #</th>";
			$output .= "<th>Mfg Serial</th>";
			$output .= "<th>Description</th>";
			$output .= "<th>Site</th>";
			$output .= "<th>Owner</th>";
			$output .= "<th>Kind</th>";
			$output .= "<th>Room</th>";
			$output .= "<th>Grid</th>";
			$output .= "</tr>";
			$output .= "</thead>";
			$output .= "<tbody>";
				foreach ($response['records_array'] as $response) {
					$desc = substr($response['system_description'],0,40);
					
					$output .= "<tr class='assetItem' data-id='".$response['system_type']."~".$response['system_serial']."'>";
					$output .= "<td class='selectItem'><label class='checkContainer'><input type='checkbox'><span class='checkmark'></span></label></td>";
					$output .= "<td><div class='editItem' onclick='updateAssets(\"".$response['system_type']."~".$response['system_serial']."\",\"$infotype\")'></div></td>";
					/*if($_SESSION['role'] == 'edit'){
						$output .= "<td><div class='deleteItem' onclick='deleteAsset(\"".$response['system_type']."~".$response['system_serial']."\",\"hardware\")'></div></td>";
					}*/
					$output .= "<td>".$response['system_type']."</td>";
					$output .= "<td>".$response['system_serial']."</td>";
					$output .= "<td>".$response['hw_status']."</td>";
					$output .= "<td>".$response['eamt_number']."</td>";
					$output .= "<td>".$response['mfg_serial']."</td>";
					$output .= "<td>".$desc."</td>";
					$output .= "<td>".$response['hw_site']."</td>";
					$output .= "<td>".$response['system_owner']."</td>";
					$output .= "<td>".$response['system_kind']."</td>";
					$output .= "<td>".$response['room']."</td>";
					$output .= "<td>".$response['grid']."</td>";

					$output .= "</tr>";
				}
			$output .= "</tbody>";
			$output .= "<table>";
				return  $output;
			}else{
				header('Content-Type: application/json');
				return json_encode(array('status' => 'error','details' => 'No records found'));
			}
	}

?>