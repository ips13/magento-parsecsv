<?php
include_once('config.php');

class SaveInventory{

	public $error = array();
	public $result = array();
	public $curProduct = array();

	function __construct($config){
		global $con;
		$this->conn = $con;
		$this->config 	= $this->config($config);
		$this->store 	= $this->config['store'];
		$this->folder	= $this->config['folder'];
		$this->chunk 	= $this->config['chunk'];	
		$this->totalrows= $this->totalRows();	
	}

	function config($config) {

		$defaults = [
        	'store' 		=> 'us',
        	'chunk' 		=> true,
        	'folder' 		=> 'files/',
        	'catalogtbl'  	=> 'csv_catalog',
        	'csvStockTbl'  	=> 'csv_stocks',
        	'stockfile' 	=> ['us'=>'stock.csv','int'=>'stock.csv'],
        	'catalogfile'	=> ['us'=>'catalog.csv','int'=>'catalog.csv']
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

	function totalRows(){
		$store = $this->store;
		$fp = file($this->folder.$this->config['catalogfile'][$store]);
		return count($fp);
	}

	function process($sk,$tk,$pre=false){
		$this->readCatalogFile($sk,$tk,$pre);
		return empty($this->error)? $this->result : $this->error;
	}

	function progress($processed){
		$total = $this->totalrows;
		return ($processed*100) / $total;
	}

	private function readCatalogFile($skip,$take,$pre=false){

		$row = 1;
		$header = array();
		$configurable = array();
		$lastProduct = '';
		$currentSKU = '';
		$simpleProducts = array();
		$store = $this->store;

		if (($handle = fopen($this->folder.$this->config['catalogfile'][$store], "r")) !== FALSE) {
		 	while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
		    	$num = count($data);

		    	if(empty($header)){
		    		$header = array_map('trim',$data);
		    		continue;
		    	}

		    	if($this->chunk){
			    	if($row <= $skip){
			    		$row++;
			    		continue;
			    	}
		    	}

				$rowData = array_map('trim',array_combine($header, $data));
				$currentSKU = $rowData['Stock Code'];
				// $this->pre($rowData);				

				/*if($data[5] == 'Candle with Metal Sculpture Inside'){
					$this->pre($data);
					$this->pre($rowData); die;
				}*/

				/*if($row == 3840){
					$this->pre($header);					
					$this->pre($data);					
					$this->pre($rowData);					
					die;
				}
				else{
					$row++;
					continue;
				}*/

				// echo $row.'.'.$simpleProducts[0]['name'].' = '.$lastPName.' //';

		    	if(!empty($simpleProducts) && $this->parseSKU($currentSKU) != $this->parseSKU($lastProduct['Stock Code'])){
		    		$configurable[] = $this->configurableProduct($lastProduct,$simpleProducts);
		    		unset($simpleProducts);

		    		if($this->chunk){
				    	if($row >= $take){
				    		break;
				    	}
			    	}
		    	}

		    	//simple products for configurable
		    	$prdctName = $this->generateName($rowData);
		    	$this->curProduct = ['sku'=>$this->parseSKU($currentSKU),'name'=>$prdctName];
		    	$variantName = $prdctName.' - '.$rowData['Variant'];
		    	$variantNameSku = $prdctName.'-'.$rowData['Variant'].'-'.$rowData['Stock Code'];
		    	$additional_images = '';
		    	if(!empty($rowData['ImageBack'])){
		    		$additional_images .= $rowData['ImageBack'];
		    		if(!empty($rowData['ImageModel'])){
		    			$additional_images .= ','.$rowData['ImageModel'];
		    		}
		    	}
		    	
		    	$us_inventory  	= $this->stockCSV($rowData['Stock Code'],'us');
		    	$int_inventory 	= $this->stockCSV($rowData['Stock Code'],'int');
		    	$total_qty 		= $us_inventory + $int_inventory;

		    	$allVariants = array(
					'sku' 			=> $rowData['Stock Code'], 
					$this->parseAttr($rowData['Variant Type']) => $rowData['Variant'], 
					'color'		=> $rowData['Colour'], 
					'design'		=> $rowData['Design'], 
					'material'		=> $rowData['Material'],
					'us_inventory'	=> $us_inventory,
					'international_inventory' => $int_inventory
				);

		    	$simpleProducts[] = array(
					'sku'  => $rowData['Stock Code'],
					'barcode'  => $rowData['Barcode\StockID'],
					'store_view_code'  => '',
					'product_type'  => 'virtual',
					'categories' => array($rowData['Category']),
					'subcategories' => array($rowData['Sub category']),
					'name'  => $variantName,
					'description'  => $rowData['Product description'],
					'short_description'  => $rowData['Design description'],
					'weight' => $rowData['Weight'],
					'gender' => $rowData['Gender'],
					'price' => $this->parsePrice($rowData),
					'special_price' => ($this->store=='us')? $rowData['Price'] : $rowData['Sale Price'],
					'url_key' => $this->slugify($variantNameSku),			
					'meta_title' => $rowData['Description'],			
					'meta_keywords' => implode(',',array($rowData['Product description'],$rowData['TAG1'],$rowData['TAG2'],$rowData['TAG3'],$rowData['TAG4'])),
					'meta_description' => $rowData['Design description'],
					'base_image' => $rowData['ImageFront'],
					'created_at' => $rowData['Date Created'],
					'updated_at' => date('d-m-y H:i:s'),
					'qty' => $total_qty,
					'out_of_stock_qty' => 0,
					'additional_images' => $additional_images,
					'tags' => array_filter(array($rowData['TAG1'],$rowData['TAG2'],$rowData['TAG3'],$rowData['TAG4'])),
					'additional_attributes' => $this->parseVariations($allVariants),
					'variants' => $allVariants
				);

		    	$lastProduct = $rowData;

		    	// echo '(ii).'.$lastPName.'<br/>';

		    	$row++;
		  	}
		 	fclose($handle);
		}

		$this->insertProducts($configurable,$pre);
	}

	function parseSKU($sku){
		$sku = explode('-',$sku);
		return $sku[0];
	}

	function parsePrice($rowdata){
		$price = ($this->store=='us')? $rowdata['StrikeThrough Price'] : $rowdata['Strikethrough Price'];
		$price = (strlen(trim($price)) > 0)? $price : 0;
		return $price;
	}

	function parseAttr($str) {
	    $slug = strtolower(
			trim(
				preg_replace(
					array('/[^A-Za-z0-9-]+/', '/[ \-]/'),
					array('_','_'),
					$str
				)
			)
		);
		return $slug;
	}

	function pre($var){
		echo '<pre>'.print_r($var,true).'</pre>';
	}

	function trimmed($str){
		return trim(preg_replace('/[^A-Za-z0-9-]+/', '-',$str));
	}

	function slugify($text){
	  	$text = preg_replace('~[^\pL\d]+~u', '-', $text);
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	  	$text = preg_replace('~[^-\w]+~', '', $text);
	  	$text = trim($text, '-');
		$text = preg_replace('~-+~', '-', $text);
	  	$text = strtolower($text);

	  	if(empty($text)){
	    	return 'n-a';
	  	}
	  	return $text;
	}

	function generateName($prod){
		$prodName = "{$prod['Design']} {$prod['Description']} by Spiral";
		return $prodName;
	}

	function parseVariations($variants){
		return implode(',', array_map(
		    function ($v, $k) { return sprintf("%s=%s", strtolower($k), $v); },
		    $variants,
		    array_keys($variants)
		));
	}

	function configurableProduct($product,$simProducts){
		$variantions = '';
		$allCategories = array();
		$allSubCategories = array();
		$description = '';
		$mainImage = '';
		foreach($simProducts as $simProduct){
			if(isset($simProduct['variants']['us_inventory'])){
				unset($simProduct['variants']['us_inventory']);
				unset($simProduct['variants']['international_inventory']);
			}
			if(empty($mainImage)){
				$mainImage = $simProduct['base_image'];
			}
			$variantions 	 .= $this->parseVariations($simProduct['variants']).'|';
			$allCategories 	  = array_merge($allCategories,$simProduct['categories']);
			$allSubCategories = array_merge($allSubCategories,$simProduct['subcategories']);
			$description 	  = !empty($simProduct['description'])? $simProduct['description'] : $description;
		}

		$configProduct = array(
			'sku' 			=> $this->parseSKU($product['Stock Code']),
			'name' 			=> $this->curProduct['name'],
			'design' 		=> $product['Design'],
			'product_type' 	=> 'configurable',
			'design' 		=> $product['Design'],
			'categories' 	=> array_filter(array_unique($allCategories)),
			'subcategories' => array_filter(array_unique($allSubCategories)),
			'description' 	=> $description,
			'images' 		=> $mainImage,
			'virtual' 		=> $simProducts,
			'configurable_variations' => rtrim($variantions,'|')
		);
		return $configProduct;
	}

	private function stockCSV($find,$type){

		$csvStockTbl = $this->config['csvStockTbl'];
		$findQuery="SELECT qty FROM `{$csvStockTbl}` WHERE `sku`='{$find}' AND `store`='{$type}'";

		if ($result=mysqli_query($this->conn,$findQuery)){
			if(mysqli_num_rows($result) > 0){
				$found = mysqli_fetch_row($result);
				return $found[0];
			}
		}

		return 0;
		/*if (($handle = fopen($this->folder.$this->config['stockfile'][$type], "r")) !== FALSE) {
			$result = 0;
		 	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				if(empty($header)){
		    		$header = array_map('trim',$data);
		    		continue;
		    	}
		    	$rowData = array_map('trim',array_combine($header, $data));

		    	if($rowData['New Code'] == $find){
		    		$result = !empty($rowData['Quantity'])? $rowData['Quantity'] : 0;
		    		break;
		    		// return $rowData;
		    	}
		 	}
		 	fclose($handle);
		 	return $result;
	 	}*/
	}

	function createCatalogTbl(){
	  	$data = array('sku','name','product_type','unique_id','categories','subcategories','description','images','virtual','configurable_variations');
	  	
		$fields = array();
		$field_count = 0;
		for($i=0;$i<count($data); $i++) {
		    $f = strtolower(trim($data[$i]));
		    if ($f) {
		        // normalize the field name, strip to 20 chars if too long
		        $f = preg_replace ('/[^0-9a-z]/', '_', $f);
		        $field_count++;
		        $fields[] = '`'.$f.'` text NOT NULL';
		    }
		}

		// Perform queries
		$catalogTbl = $this->config['catalogtbl'];
		$createQuery = "CREATE TABLE IF NOT EXISTS `{$catalogTbl}` ( `id` int(11) NOT NULL AUTO_INCREMENT, " . implode(', ', $fields) . ', PRIMARY KEY (`id`))';
		// echo $createQuery . "<br/>";
				
		mysqli_query($this->conn,$createQuery);
	}

	function insertProducts($products,$pre=false){
		
		if($pre){
			$this->pre($products);  return;
		}

		$catalogTbl = $this->config['catalogtbl'];
		foreach($products as $pcolumn => $product){
			//$this->pre($product);

		    $foundId = 0;
		    $findQuery="SELECT id,store FROM `{$catalogTbl}` WHERE `sku`='{$product['sku']}'";

			if ($result=mysqli_query($this->conn,$findQuery)){
				if(mysqli_num_rows($result) > 0){
					$found = mysqli_fetch_row($result);
					$foundId = $found[0];
					$foundStore = $found[1];
					
					$updatedata = array();
					foreach ($product as $column => $value) {
				        $convertedVal = is_array($value)? serialize($value) : $value;
				        $updatedata[] = "`".$column."`='".mysqli_real_escape_string($this->conn, $convertedVal)."'";
				    }

				    $updatedata[] = "`store`='{$this->store}'";

				    $updata = implode(", ", $updatedata);
					$insertUpQuery = ($foundStore == 'int' && $this->store == 'us')? '' : "UPDATE {$catalogTbl} SET {$updata} WHERE id={$foundId}";
				}
				else{
					$cols = array();
					$vals = array();
					foreach ($product as $column => $value) {
				        $cols[] = $column;
				        $convertedVal = is_array($value)? serialize($value) : $value;
				        $vals[] = mysqli_real_escape_string($this->conn, $convertedVal);
				    }

				    $cols[] = 'store';
				    $vals[] = $this->store;

				    $pCols = "`".implode("`, `", $cols)."`";
				    $pVals = "'".implode("', '", $vals)."'";

					$insertUpQuery = "INSERT INTO {$catalogTbl} ({$pCols}) VALUES ($pVals)";
				}

				// echo $insertUpQuery.'<br/><br/><br/><br/><br/>';
				// $inserted = true;
				$inserted = !empty($insertUpQuery)? mysqli_query($this->conn,$insertUpQuery) or die(mysqli_error($this->conn)) : true;
				
				if($inserted){
					$lastId = ($foundId!=0)? $foundId : mysqli_insert_id($this->conn);
					$this->setResult((int) $lastId,'products');
				}
				else{
					$this->setError('Insertion Failed: '.$product['name'].' - '.$product['design']);
				}
			}
		
		}
	}

	function setResult($data,$type){
		if(!isset($this->result[$type])){
			$this->result[$type] = array($data);
		}
		else{
			array_push($this->result[$type],$data);
		}
	}

	function setError($error){
		array_push($this->error,$error);
	}
}