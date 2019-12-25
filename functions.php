<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

include_once('config.php');

function allFiles($name,$type){
	$selectBox = "<select name='{$name}' class='form-control mb-2'>";
		$selectBox .= "<option value=''>Select {$type} File</option>";
	if ($handle = opendir('files')) {
	    while (false !== ($entry = readdir($handle))) {
	        if ($entry != "." && $entry != "..") {
	            $selectBox .= "<option value='{$entry}'>{$entry}</option>";
	        }
	    }
	    closedir($handle);
	}
	$selectBox .= "</select>";
	return $selectBox;
}