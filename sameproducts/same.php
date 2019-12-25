<?php
include_once('../config.php');

$totalProducts = 0;
$allSameProducts = array();
$findQuery = "SELECT id,name FROM csv_catalog GROUP BY name HAVING count(*) > 1";
if ($result=mysqli_query($con,$findQuery)){

	$handle = createSameProductsCSV();
	fputcsv($handle, ['name','skus']);

	if(mysqli_num_rows($result) > 0){
		while($row = mysqli_fetch_assoc($result)){
			$allSkus = array();
			$findName  = $row['name'];
			$findQuery2 = "SELECT sku FROM csv_catalog WHERE name='{$findName}'";
			if ($result2=mysqli_query($con,$findQuery2)){
				if(mysqli_num_rows($result2) > 0){
					while($row2 = mysqli_fetch_assoc($result2)){
						// print_r($row2);
						$allSkus[] = $row2['sku'];
					}
				}
			}

			$totalProducts += count($allSkus);
			$allSameProducts[] = array('name'=>$findName,'skus'=>$allSkus);

			fputcsv($handle,[$findName,implode(',',$allSkus)]);
		}
	}
}

function createSameProductsCSV(){
	$fileName = "SameProducts.csv";
	if(file_exists($fileName)){
		unlink($fileName);
	}
	return fopen($fileName, "a");
}

echo "File Generated!, Total Products: {$totalProducts}";
// echo '<pre>'.print_r($allSameProducts,true); die;
