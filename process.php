<?php

include_once('parse.php');
include_once('savefiles.php');

$action = isset($_REQUEST['action'])? $_REQUEST['action'] : 'records';
$process_id = isset($_REQUEST['process_id'])? $_REQUEST['process_id'] : '';

/*$config = [
	'store'			=> 'us',
	'catalogtbl'  	=> 'csv_international_catalog',
	'stockfile' 	=> ['us'=>'USA Stock 11-7-17.csv','int'=>'International Stock 11-7-17.csv'],
	'catalogfile'	=> ['us'=>'USA Catalog 11-7.csv','int'=>'International Catalog Sheet 11-7.csv']
];*/

switch($action){
	case 'records':
		$config = loadCSVid($process_id);
		$SaveInventory = new SaveInventory($config);
		$SaveInventory->totalRows();
		$SaveInventory->createCatalogTbl();
		exit();
	break;
	case 'run':
		if(empty($process_id)){
			echo json_encode(['success'=>false,'error'=>'Process id required!']);	die;
		}
		$page  = isset($_REQUEST['page'])? $_REQUEST['page'] : 1;
		$limit = isset($_REQUEST['limit'])? $_REQUEST['limit'] : 10;
		$skip  = ($page-1)*$limit;
		$take  = $page*$limit;
		$nextpage  = $page+1;
		$config = loadCSVid($process_id);
		// print_r($config); die;
		$SaveInventory = new SaveInventory($config);
		$processed = $SaveInventory->progress($take);
		$samelink = "process.php?action=run&process_id={$process_id}&limit={$limit}&page={$page}";
		$nextlink = "process.php?action=run&process_id={$process_id}&limit={$limit}&page={$nextpage}";
		// echo $skip.'-'.$take;
		$SaveInventory->process($skip,$take);
		// $SaveInventory->process($skip,$take,true);
	
		if(empty($SaveInventory->error)){
			echo json_encode(['success'=>true,'processed'=>$processed,'link'=>$nextlink,'result'=>$SaveInventory->result]);
		}
		else{
			echo json_encode(['success'=>false,'link'=>$samelink,'error'=>implode(',',$SaveInventory->error)]);
		}
		exit();
	break;
	case 'savefiles':
		$store  = @$_POST['store'];
		$sfiles = @$_POST['stockfiles'];
		$cfiles = @$_POST['catalogfiles'];

		if(!empty($store) && !empty($sfiles) && !empty($cfiles)){
			$CsvFiles = new CsvFiles();
			$CsvFiles->saveinDB($store,$cfiles,$sfiles);
			// print_r($ImportForm->result);
			if(empty($CsvFiles->error)){
				echo json_encode(['success'=>true,'result'=>$CsvFiles->result['fileid']]);
			}
			else{
				echo json_encode(['success'=>false,'error'=>implode(',',$CsvFiles->error)]);	
			}
		}
		else{
			echo json_encode(['success'=>false,'error'=>'Please select all fields!']);	
		}
		
		exit();
	break;
	case 'savestocks':
		if(!empty($process_id)){
			$CsvFiles = new CsvFiles();
			$CsvFiles->saveStock($process_id);
			if(empty($CsvFiles->error)){
				echo json_encode(['success'=>true,'result'=>'Stock Updated!']);
			}
			else{
				echo json_encode(['success'=>false,'error'=>implode(',',$CsvFiles->error)]);	
			}
		}
		else{
			echo json_encode(['success'=>false,'error'=>'Process ID required!']);	
		}
	break;
}

function loadCSVid($pid){
	$CsvFiles = new CsvFiles();
	return $CsvFiles->files($pid);
}