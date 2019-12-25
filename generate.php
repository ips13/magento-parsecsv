<?php
set_time_limit(0);
include_once('config.php');

class GenerateInvFile{

	public $error = array();
	public $result = array();

	function __construct($config){
		global $con;
		$this->conn = $con;
		$this->config 	= $this->config($config);
		$this->folder	= $this->config['folder'];
		$this->deleteFile();
		$this->handle 	= $this->openFile();
	}

	function config($config) {

		$defaults = [
			'folder' 		=> 'inventory/',
        	'catalogtbl'  	=> 'csv_catalog',
        	'magentofile' 	=> 'magento-inventory.csv'	
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

	function process(){
		$limit 	= isset($_REQUEST['limit'])? $_REQUEST['limit'] : '';
		$offset = isset($_REQUEST['offset'])? $_REQUEST['offset'] : '';

		$catalogTbl = $this->config['catalogtbl'];
		$findQuery	= "SELECT * FROM `{$catalogTbl}`";
		$findQuery	.= (!empty($limit))? "LIMIT {$limit}" : "";
		$findQuery	.= (!empty($offset))? ",{$offset}" : "";

		if ($result=mysqli_query($this->conn,$findQuery)){
			if(mysqli_num_rows($result) > 0){
				while($row = mysqli_fetch_assoc($result)){
					$this->generateConfigProduct($row);
				}
			}
		}
	}

	function deleteFile(){
		$inventoryFile = $this->folder.$this->config['magentofile'];
		if(file_exists($inventoryFile)){
			unlink($inventoryFile);
		}
	}

	function pre($var){
		echo '<pre>'.print_r($var,true).'</pre>';
	}

	function generateConfigProduct($product){
		// $this->pre($product);
		if(strlen(trim($product['name'])) > 0 && trim($product['name']) != '-'){
			$virtuals = unserialize($product['virtual']);
			if(is_array($virtuals)){
				$this->generateVirtualProducts($virtuals);
				$this->appendRowConfig($product);
			}
			else{
				echo 'Corrupt ';
			}
			echo $product['id'].'<br>';
		}
		else{
			echo 'Skip: '.$product['id'].'<br>';
		}
	}

	function generateVirtualProducts($virtuals){
		foreach($virtuals as $virtual){
			// $this->pre($virtual);
			$this->lastVProduct = $virtual;
			$this->appendRowVirtual($virtual);
		}
	}

	function openFile(){
		return fopen($this->folder.$this->config['magentofile'], "a");
	}

	function generateHeader(){
		$header = array(
			'sku',
			'store_view_code',
			'attribute_set_code',
			'product_type',
			'categories',
			'product_websites',
			'name',
			'description',
			'short_description',
			'weight',
			'product_online',
			'tax_class_name',
			'visibility',
			'price',
			'special_price',
			'special_price_from_date',
			'special_price_to_date',
			'url_key',
			'meta_title',
			'meta_keywords',
			'meta_description',
			'base_image',
			'base_image_label',
			'small_image',
			'small_image_label',
			'thumbnail_image',
			'thumbnail_image_label',
			'swatch_image',
			'swatch_image_label',
			'created_at',
			'updated_at',
			'new_from_date',
			'new_to_date',
			'display_product_options_in',
			'map_price',
			'msrp_price',
			'map_enabled',
			'gift_message_available',
			'custom_design',
			'custom_design_from',
			'custom_design_to',
			'custom_layout_update',
			'page_layout',
			'product_options_container',
			'msrp_display_actual_price_type',
			'country_of_manufacture',
			'additional_attributes',
			'qty',
			'out_of_stock_qty',
			'use_config_min_qty',
			'is_qty_decimal',
			'allow_backorders',
			'use_config_backorders',
			'min_cart_qty',
			'use_config_min_sale_qty',
			'max_cart_qty',
			'use_config_max_sale_qty',
			'is_in_stock',
			'notify_on_stock_below',
			'use_config_notify_stock_qty',
			'manage_stock',
			'use_config_manage_stock',
			'use_config_qty_increments',
			'qty_increments',
			'use_config_enable_qty_inc',
			'enable_qty_increments',
			'is_decimal_divided',
			'website_id',
			'related_skus',
			'crosssell_skus',
			'upsell_skus',
			'additional_images',
			'additional_image_labels',
			'hide_from_product_page',
			'bundle_price_type',
			'bundle_sku_type',
			'bundle_price_view',
			'bundle_weight_type',
			'bundle_values',
			'configurable_variations',
			'configurable_variation_labels',
			'associated_skus'
		);
		fputcsv($this->handle, $header);
	}

	function appendRowVirtual($line){
		// $this->pre($line);
		
		$line['qty'] = $this->checkvalue($line['qty']);
		$allcategories = array_unique(array_merge($line['categories'],$line['subcategories']));
		$price = (strlen(trim($line['price'])) > 0)? $line['price'] : 0;
		$rowline = array(
			$line['sku'],
			$line['store_view_code'],
			'Default',
			$line['product_type'],
			'Default Category/'.implode('/',$allcategories),
			'base',
			$line['name'],
			$line['description'],
			$line['short_description'],
			$line['weight'],
			'1',
			'Taxable Goods',
			'Not Visible Individually',
			$price,
			'',
			'',
			'',
			$line['url_key'],
			$line['meta_title'],
			$line['meta_keywords'],
			$line['meta_description'],
			''/*$line['base_image']*/,
			'',
			''/*$line['base_image']*/,
			'',
			''/*$line['base_image']*/,
			'',
			''/*$line['base_image']*/,
			'',
			$line['created_at'],
			$line['updated_at'],
			'',
			'',
			'Block after Info Column',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			$line['additional_attributes'],
			$line['qty'],
			0,
			1,
			0,
			0,
			1,
			1,
			1,
			10000,
			1,
			1,
			1,
			1,
			1,
			1,
			1,
			0,
			0,
			0,
			0,
			1,
			'',
			'',
			'',
			''/*$line['additional_images']*/,
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			''
		);
		$rowline = array_map('utf8_encode',$rowline);
		fputcsv($this->handle, $rowline);
	}
  
	function appendRowConfig($prd){

		$vprd = $this->lastVProduct;

		$allcategories = unserialize($prd['categories']);
		// $allcategories = array_unique(array_merge(unserialize($prd['categories']),unserialize($prd['subcategories'])));
		$rowline = array(
			$prd['sku'],
			'',
			'Default',
			'configurable',
			'Default Category/'.implode('/',$allcategories),
			'base',
			$prd['name'],
			$prd['description'],
			$prd['description'],
			'',
			'1',
			'Taxable Goods',
			'Catalog, Search',
			'',
			'',
			'',
			'',
			$this->slugify($this->trimmed($prd['name']).'-'.$this->trimmed($prd['sku'])),
			$prd['name'],
			$vprd['meta_keywords'],
			$vprd['meta_description'],
			''/*@$prd['images']*/,
			'',
			''/*@$prd['images']*/,
			'',
			''/*@$prd['images']*/,
			'',
			''/*@$prd['images']*/,
			'',
			@$prd['created_at'],
			@$prd['updated_at'],
			'',
			'',
			'Block after Info Column',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			0,
			1,
			0,
			0,
			1,
			1,
			1,
			10000,
			1,
			1,
			1,
			1,
			1,
			1,
			1,
			0,
			0,
			0,
			0,
			1,
			'',
			'',
			'',
			@$prd['additional_images'],
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			$prd['configurable_variations'],
			'',
			''
		);

		$rowline = array_map('utf8_encode',$rowline);
		fputcsv($this->handle, $rowline);
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
	
	
	
	function checkvalue($qty){
		  
	  	if($qty < 0){
			$qty = 0;  
	  	}
		  
	  	return $qty;	
  	}
  	
}

$config = [
	'catalogtbl'  	=> 'csv_catalog',
	'magentofile' 	=> 'magento-inventory.csv'	
];
//generate inventory file
$GenerateInvFile = new GenerateInvFile($config);
$GenerateInvFile->generateHeader();
$GenerateInvFile->process();
// $GenerateInvFile->generateCsv();