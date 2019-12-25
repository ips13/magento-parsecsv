<?php

class CsvFiles{

	public $error = array();
	public $result = array();

	function __construct($config=array()){
		global $con;
		$this->conn = $con;
		$this->config 	= $this->config($config);
		$this->folder	= $this->config['folder'];
	}

	function config($config) {

		$defaults = [
        	'store' 		=> 'us',
        	'folder' 		=> 'files/',
        	'csvfileTbl'  	=> 'csvfiles',
        	'csvStockTbl'  	=> 'csv_stocks'
    	];

		if(empty($config) || !is_array($config)){
	    	$fullConfig = $defaults;
	    }
	    else{
		    $fullConfig = array_merge(
		        $defaults,
		        array_intersect_key($config, $defaults)
		    );
		}

    	return $fullConfig;
	}

	function saveinDB($store,$cfiles,$sfiles){
		$cols = array('stockfiles','catalogfiles','store');
	    $vals = array(serialize($sfiles),serialize($cfiles),$store);

	    $csvfileTbl = $this->config['csvfileTbl'];
	    $pCols = "`".implode("`, `", $cols)."`";
	    $pVals = "'".implode("', '", $vals)."'";
		$insertUpQuery = "INSERT INTO {$csvfileTbl} ({$pCols}) VALUES ($pVals)";
		
		$inserted = mysqli_query($this->conn,$insertUpQuery);

		if($inserted){
			$lastId = mysqli_insert_id($this->conn);
			$this->setResult($lastId,'fileid');
		}
		else{
			$this->setError('Insertion Failed!');
		}
	}

	function saveStock($pid){
		// echo 'start..'; die;

		$csvfileTbl = $this->config['csvfileTbl'];
		$findQuery="SELECT stockfiles FROM `{$csvfileTbl}` WHERE `id`='{$pid}'";

		if ($result=mysqli_query($this->conn,$findQuery)){
			if(mysqli_num_rows($result) > 0){

				$found = mysqli_fetch_assoc($result);
				$sfiles = unserialize($found['stockfiles']);

				print_r($sfiles); die;

				if(is_array($sfiles) && sizeof($sfiles)>0){

					$csvStockTbl = $this->config['csvStockTbl'];
					$delQuery = "TRUNCATE table {$csvStockTbl}";
					mysqli_query($this->conn,$delQuery);

					$insertedStocks = [];
					foreach($sfiles as $stockStore => $stockfile){
						if (($handle = fopen($this->folder.$stockfile, "r")) !== FALSE) {
							while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
								if(empty($header)){
						    		$header = array_map('trim',$data);
						    		continue;
						    	}
						    	$rowData = array_map('trim',array_combine($header, $data));

						    	// print_r($rowData); die;

						    	$cols = array('sku','qty','store');
							    $vals = array($rowData['New Code'],$rowData['Quantity'],$stockStore);

							    $pCols = "`".implode("`, `", $cols)."`";
							    $pVals = "'".implode("', '", $vals)."'";

								$insertStockQuery = "INSERT INTO {$csvStockTbl} ({$pCols}) VALUES ($pVals)";
								mysqli_query($this->conn,$insertStockQuery) or die(mysqli_error($this->conn));
						 	}
						 	fclose($handle);
					 	}
					 	$insertedStocks[] = $stockStore;
				 	}
				 	return $this->setResult($lastId,'stocksaved');
				}
				else{
					return $this->setError('Not valid array!');
				}
			}
		}	

		return $this->setError('Not found!');
	}

	function files($id){
		$csvfileTbl = $this->config['csvfileTbl'];
		$findQuery="SELECT * FROM `{$csvfileTbl}` WHERE `id`='{$id}'";

		if ($result=mysqli_query($this->conn,$findQuery)){
			if(mysqli_num_rows($result) > 0){
				$found = mysqli_fetch_assoc($result);
				return [
					'store'	=> $found['store'],
					'stockfile'	=> unserialize($found['stockfiles']),
					'catalogfile'	=> unserialize($found['catalogfiles']),
					'catalogtbl'	=>'csv_catalog'
				];
			}
		}

		return array();
	}

	function setResult($data,$type){
		if(!isset($this->result[$type])){
			$this->result[$type] = $data;
		}
		else{
			array_push($this->result[$type],$data);
		}
	}

	function setError($error){
		array_push($this->error,$error);
	}
}