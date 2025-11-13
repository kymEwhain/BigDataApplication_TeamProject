<?php

function connectDB() {
  $mysqli = mysqli_connect("localhost", "team01", "team01", "team01");
  if (mysqli_connect_errno()) {
    die("ERROR: Could not connect DB. " . mysqli_connect_error());
  }
  return $mysqli;
}


?>
