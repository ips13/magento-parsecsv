<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

global $con;
$con = mysqli_connect("localhost","root","","bony_stock");
// Check connection
if (mysqli_connect_errno()){
  	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

