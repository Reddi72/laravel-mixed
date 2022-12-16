<title>Laravel Requirement</title>
<?php

if( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) 
  echo $apache = "apache: <span style='color:green;'>Ok</span><br>";
else
  echo $apache = "apache: <span style='color:red;'>Not Ok</span><br>";

$mysql_ver = getMySQLVersion();

if ($mysql_ver > 5.1) {
   echo $mysql_ver = "mysql: <span style='color:green;'>Ok</span> $mysql_ver<br>";
} else {
    echo $mysql_ver = "mysql: <span style='color:red;'>Not Ok</span> $mysql_ver<br>";
}

$ver = (float)phpversion();

if ($ver > 7.3) {
   echo $php_ver = "php: <span style='color:green;'>Ok</span> $ver<br>";
} else {
    echo $php_ver = "php: <span style='color:red;'>Not Ok</span> $ver<br>";
}

if (extension_loaded("curl")) {
  echo $curl = "cURL: <span style='color:green;'>Ok</span><br>";
} else {
  echo $curl = "cURL: <span style='color:red;'>Not Ok</span><br>";
}

if(extension_loaded("bcmath")) {
  echo $bcmath = "bcmath: <span style='color:green;'>Ok</span><br>";
} else {
  echo $bcmath = "bcmath: <span style='color:red;'>Not Ok</span><br>";
}

if(extension_loaded("ctype")) {
  echo $ctype = "ctype: <span style='color:green;'>Ok</span><br>";
} else {
  echo $ctype = "ctype: <span style='color:red;'>Not Ok</span><br>";
}

if(extension_loaded("fileinfo")) {
  echo $Fileinfo = "Fileinfo: <span style='color:green;'>Ok</span><br>";
} else {
  echo $Fileinfo = "Fileinfo: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("json")) {
  echo $json = "json: <span style='color:green;'>Ok</span><br>";
} else {
  echo $json = "json: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("mbstring")) {
  echo $mbstring = "mbstring: <span style='color:green;'>Ok</span><br>";
} else {
  echo $mbstring = "mbstring: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("openssl")) {
  echo $openssl = "openssl: <span style='color:green;'>Ok</span><br>";
} else {
  echo $openssl = "openssl: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("pdo")) {
  echo $pdo = "PDO: <span style='color:green;'>Ok</span><br>";
} else {
  echo $pdo = "PDO: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("tokenizer")) {
  echo $tokenize = "tokenizer: <span style='color:green;'>Ok</span><br>";
} else {
  echo $tokenize = "tokenizer: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("xml")) {
  echo $xml = "xml: <span style='color:green;'>Ok</span><br>";
} else {
  echo $xml = "xml: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("mysqlnd")) {
  echo $mysqlnd = "mysqlnd: <span style='color:green;'>Ok</span><br>";
} else {
  echo $mysqlnd = "mysqlnd: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("zip")) {
  echo $zip = "zip: <span style='color:green;'>Ok</span><br>";
} else {
  echo $zip = "zip: <span style='color:red;'>Not Ok</span><br>";
}

if (extension_loaded("mysqli")) {
  echo $mysqli = "mysqli: <span style='color:green;'>Ok</span><br>";
} else {
  echo $mysqli = "mysqli: <span style='color:red;'>Not Ok</span><br>";
}


function getMySQLVersion() { 
  $output = shell_exec('mysql -V'); 
  preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
  return $version[0]; 
}


//print_r(get_loaded_extensions());

?>

