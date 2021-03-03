<?php
$host = 'localhost';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password);

$cipher = 'AES-128-CBC';
$key = 'eimhinSecret';

if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}

//creates and then uses database 
$sql = 'CREATE DATABASE IF NOT EXISTS covidchecklist;';
if (!$conn->query($sql) === TRUE) {
  die('Error creating database: ' . $conn->error);
}

$sql = 'USE covidchecklist;';
if (!$conn->query($sql) === TRUE) {
  die('Error using database: ' . $conn->error);
}

//creating table in database and parameters
$sql = 'CREATE TABLE IF NOT EXISTS tracker (
id int NOT NULL AUTO_INCREMENT,
iv varchar(32) NOT NULL,
symptoms varchar(256) NOT NULL,
contact varchar(256) NOT NULL,
name varchar(256) NOT NULL,
dob varchar(256) NOT NULL,
practitioner varchar(256) NOT NULL,
medcard varchar(256) NOT NULL,
PRIMARY KEY (id));';
if (!$conn->query($sql) === TRUE) {
  die('Error creating table: ' . $conn->error);
}
?>
<html>
<head>
<title>COVID-19 Checklist Survey</title> </head>
<body>
<h1>Survey</h1>
<?php
if (isset($_POST['new-note'])) {
  $iv = random_bytes(16);
  
  //escaping helps prevent xss
  $escaped_sym = $conn -> real_escape_string($_POST['symptoms']);
  $escaped_con = $conn -> real_escape_string($_POST['contact']);
  $escaped_name = $conn -> real_escape_string($_POST['name']);
  $escaped_prac = $conn -> real_escape_string($_POST['practitioner']);
  $escaped_dob = $conn -> real_escape_string($_POST['dob']);
  $escaped_med = $conn -> real_escape_string($_POST['medcard']);
  
  //safely encrypts data after ensuring data is secure.
  $encrypted_sym = openssl_encrypt($escaped_sym, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_con = openssl_encrypt($escaped_con, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_name = openssl_encrypt($escaped_name, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_prac = openssl_encrypt($escaped_prac, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_dob = openssl_encrypt($escaped_dob, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_med = openssl_encrypt($escaped_med, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  
  //hexadecimal form of strings
  $iv_hex = bin2hex($iv);
  $sym_hex = bin2hex($encrypted_sym);
  $con_hex = bin2hex($encrypted_con);
  $name_hex = bin2hex($encrypted_name);
  $prac_hex = bin2hex($encrypted_prac);
  $dob_hex = bin2hex($encrypted_dob);
  $med_hex = bin2hex($encrypted_med);
  
  //places encrypted data into the database
  $sql = "INSERT INTO tracker (iv, symptoms, contact, name, practitioner, dob, medcard) VALUES ('$iv_hex', '$sym_hex', '$sym_hex', '$name_hex', '$prac_hex', '$dob_hex', '$med_hex')";
  if ($conn->query($sql) === TRUE) {
    echo '<p><i>New entry added!</i></p>';
  } else {
    die('Error creating entry: ' . $conn->error);
  }
}
?>

<h2>Create a New Entry</h2>

<form method="post">
  
  <label for= "symptoms">Do you have symptoms? Y/N :</label>
  <input type="text" id="symptoms" name="symptoms" maxlength="1" size="1" pattern="Y|N|y|n" required><br><br>
  
  <label for= "contact">Did you have contact with someone who has tested COVID-19 Positive? Y/N :</label>
  <input type="text" id="contact" name="contact" maxlength="1" size="1" pattern="Y|N|y|n" required><br><br>
  
  <label for= "name">Enter Your Name:</label><br>
  <input type="text" id="name" name="name" size="64" required><br><br>
  
  <label for= "practitioner">Enter Your Practitioner's Name:</label><br>
  <input type="text" id="practitioner" name="practitioner" size="64" required><br><br>
  
  <label for= "dob">Date of Birth:</label><br>
  <input type="date" id="dob" name="dob" size="64"><br><br>
  
  <label for= "medcard">Do you have a medical card? Y/N :</label>
  <input type="text" id="medcard" name="medcard" maxlength="1" size="1" pattern="Y|N|y|n" required><br><br>
  
  <button type="submit" name="new-note">Create Entry</button>
</form>

</body>
</html>
