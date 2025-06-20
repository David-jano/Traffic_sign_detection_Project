<?php

$host="localhost";
$user="root";
$pass="";

$conn=mysqli_connect($host,$user,$pass,'traffic')or die(mysqli_error('connection failed'));

if($conn){
   // echo"connection success";
}
else
{
    //echo"Connect failed to connect";
}
?>