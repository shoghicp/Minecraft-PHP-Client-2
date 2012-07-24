<?php

/*


           -
         /   \
      /         \
   /   MINECRAFT   \
/         PHP         \
|\       CLIENT      /|
|.   \     2     /   .|
| ..     \   /     .. |
|    ..    |    ..    |
|       .. | ..       |
\          |          /
   \       |       /
      \    |    /
         \ | /
         
         
	by @shoghicp

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


*/

$errors = 0;

if(version_compare("5.3.3", PHP_VERSION) > 0){
	console("[ERROR] Use PHP >= 5.3.3", true, true, 0);
	++$errors;
}

if(version_compare("5.4.0", PHP_VERSION) > 0){
	console("[NOTICE] Use PHP >= 5.4.0 to increase performance", true, true, 0);
	define("HEX2BIN", false);
}else{
	define("HEX2BIN", true);
}

if(!extension_loaded("gmp")){
	console("[NOTICE] Enable GMP extension to increase performance", true, true, 0);
	define("GMPEXT", false);
}else{
	define("GMPEXT", true);
}


if(extension_loaded("mcrypt") and mcrypt_module_self_test(MCRYPT_RIJNDAEL_128)){
	define("CRYPTO_LIB", "mcrypt");	
}elseif(!extension_loaded("openssl")){
	console("[NOTICE] Unable to find Mcrypt extension", true, true, 0);
	console("[ERROR] Unable to find OpenSSL extension (fallback)", true, true, 0);
	++$errors;
}else{
	console("[NOTICE] Unable to find Mcrypt extension", true, true, 0);
	define("CRYPTO_LIB", "openssl");
}



if(!function_exists("curl_init")){
	console("[ERROR] Unable to find cURL functions", true, true, 0);
	++$errors;
}

if(!function_exists("gzinflate")){
	console("[ERROR] Unable to find Zlib extension", true, true, 0);
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