<?php

$errors = 0;

if(version_compare("5.3.3", PHP_VERSION) > 0){
	console("[ERROR] Use PHP >= 5.3.3", true, true, 0);
	++$errors;
}

if(!function_exists("openssl_encrypt")){
	console("[ERROR] Unable to find OpenSSL functions", true, true, 0);
	++$errors;
}

if(!function_exists("curl_init")){
	console("[ERROR] Unable to find cURL functions", true, true, 0);
	++$errors;
}

if(!function_exists("gzinflate")){
	console("[ERROR] Unable to find Zlib", true, true, 0);
	++$errors;
}

if(!function_exists("socket_create")){
	console("[ERROR] Unable to find Socket functions", true, true, 0);
	++$errors;
}

if($errors > 0){
	die();
}


?>