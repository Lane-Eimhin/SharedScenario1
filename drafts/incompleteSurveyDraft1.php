<?php
$host = 'localhost';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password);

$cipher = 'AES-128-CBC';
$key = 'thebestsecretkey';

if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}

if (isset($_POST['delete-everything'])) {
  $sql = 'DROP DATABASE covidchecklist;';
  if (!$conn->query($sql) === TRUE) {
    die('Error dropping database: ' . $conn->error);
  }
}

$sql = 'CREATE DATABASE IF NOT EXISTS covidchecklist;';
if (!$conn->query($sql) === TRUE) {
  die('Error creating database: ' . $conn->error);
}

$sql = 'USE covidchecklist;';
if (!$conn->query($sql) === TRUE) {
  die('Error using database: ' . $conn->error);
}

$sql = 'CREATE TABLE IF NOT EXISTS tracker (
id int NOT NULL AUTO_INCREMENT,
iv varchar(32) NOT NULL,
symptoms varchar(256) NOT NULL,
contact BOOLEAN NOT NULL,
content varchar(256) NOT NULL,
name varchar(256) NOT NULL,
dob varchar(256) NOT NULL,
practitioner varchar(256) NOT NULL,
medcard BOOLEAN NOT NULL,
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
  
  $escaped_content = $conn -> real_escape_string($_POST['content']);
  $escaped_name = $conn -> real_escape_string($_POST['name']);
  $escaped_dob = $conn -> real_escape_string($_POST['dob']);
  $escaped_sym = $conn -> real_escape_string($_POST['symptoms']);
  
  $encrypted_content = openssl_encrypt($escaped_content, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_name = openssl_encrypt($escaped_name, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_dob = openssl_encrypt($escaped_dob, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  $encrypted_sym = openssl_encrypt($escaped_sym, $cipher, $key, OPENSSL_RAW_DATA, $iv);
  
  $iv_hex = bin2hex($iv);
  $content_hex = bin2hex($encrypted_content);
  $name_hex = bin2hex($encrypted_name);
  $dob_hex = bin2hex($encrypted_dob);
  $sym_hex = bin2hex($encrypted_sym);
  
  $sql = "INSERT INTO tracker (iv, content, name, dob, symptoms) VALUES ('$iv_hex', '$content_hex', '$name_hex', '$dob_hex', '$sym_hex')";
  if ($conn->query($sql) === TRUE) {
    echo '<p><i>New entry added!</i></p>';
  } else {
    die('Error creating entry: ' . $conn->error);
  }
}
?>
<h2>Create a New Entry</h2>

<form method="post">
  <input type="text" id="content" name="content" size="64"><br><br>
  
  <label for= "name">Enter Your Name:</label><br>
  <input type="text" id="name" name="name" size="64" required><br><br>
  
  <label for= "dob">Date of Birth:</label><br>
  <input type="date" id="dob" name="dob" size="64"><br><br>
  
  <label for= "symptoms">Tick if you have symptoms Y/N :</label>
  <input type="text" id="symptoms" name="symptoms" maxlength="1" size="1" pattern="Y|N|y|n"><br><br>
  
  <button type="submit" name="new-note">Create Entry</button>
</form>

<h2>List Existing Entry</h2>

<?php
$sql = "SELECT * FROM tracker";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  echo '<table><tr><th>ID</th><th>Content</th><th>Name</th><th>dob</th><th>Symptoms</th></tr>';
  while($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $iv = hex2bin($row['iv']);
    $content = hex2bin($row['content']);
	$name = hex2bin($row['name']);
	$dob = hex2bin($row['dob']);
	$sym = hex2bin($row['symptoms']);
    $unencrypted_content = openssl_decrypt($content, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	$unencrypted_name = openssl_decrypt($name, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	$unencrypted_dob = openssl_decrypt($dob, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	$unencrypted_sym = openssl_decrypt($sym, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    echo "<tr><td>$id</td><td>$unencrypted_content</td><td>$unencrypted_name</td><td>$unencrypted_dob</td><td>$unencrypted_sym</td></tr>";
  }
  echo '</table>';
} else {
  echo '<p>There are no entries!</p>';
}
?>

<h3>Delete Contents</h3>

<form method="post">
  <button type="submit" name="delete-everything">Delete All Information!</button>
</form>
</body>
</html>
