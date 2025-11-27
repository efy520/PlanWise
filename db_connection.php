<?php
//database connection variables
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "planwise_db";

//create connection
$conn = new mysqli($servername, $username, $password, $dbname);

//check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// else {
//     // Uncomment the line below for debugging purposes
//      echo "Connected successfully";
// }
?>