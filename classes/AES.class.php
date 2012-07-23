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


/*

Incomplete AES class with buffering
Only CFB8

*/



if(CRYPTO_LIB === "openssl"){
	class AES{
		private $key, $encIV, $decIV, $IVLenght, $bytes, $mode;

		function __construct($bits, $mode, $blockSize){
			console("[DEBUG] [AES] Using OpenSSL extension", true, true, 2);
			$this->mode = "AES-".$bits."-".strtoupper($mode).$blockSize;
			$this->bytes = $blockSize / 8;
			$this->key = $this->encIV = $this->decIV = str_repeat("\x00", openssl_cipher_iv_length($this->mode));
			$this->IVLenght = openssl_cipher_iv_length($this->mode);
		}
		
		public function setKey($key = ""){
			$this->key = str_pad($key, $this->IVLenght, "\x00", STR_PAD_RIGHT);
		}

		public function setIV($IV = ""){
			$this->encIV = $this->decIV = str_pad($IV, $this->IVLenght, "\x00", STR_PAD_RIGHT);
		}
		
		protected function _shiftIV($IV, $str){ //Only for CFB
			if(!isset($str{$this->IVLenght - 1})){
				$len = min($this->IVLenght, strlen($str));
				return substr($IV, $len).substr($str, -$len);
			}
			return substr($str, -$this->IVLenght);
		}
		
		public function encrypt($plaintext){
			$ciphertext = openssl_encrypt($plaintext, $this->mode, $this->key, true, $this->encIV);
			$this->encIV = $this->_shiftIV($this->encIV, $ciphertext);
			return $ciphertext;
		}

		public function decrypt($ciphertext){
			$plaintext = openssl_decrypt($ciphertext, $this->mode, $this->key, true, $this->decIV);
			$this->decIV = $this->_shiftIV($this->decIV, $ciphertext);
			return $plaintext;
		}
		public function init(){
		}

	}
	
}else{ //mcrypt


	class AES{
		private $key, $keyLenght, $IV, $IVLenght, $bytes, $mcrypt, $enc, $dec, $mode, $algorithm;

		function __construct($bits, $mode, $blockSize){
			console("[DEBUG] [AES] Using Mcrypt extension", true, true, 2);
			$this->algorithm = "rijndael-".intval($bits);
			$this->mode = strtolower($mode);
			$this->mcrypt = mcrypt_module_open($this->algorithm, "", $this->mode, "");
			$this->bytes = mcrypt_enc_get_block_size($this->mcrypt);
			$this->keyLenght = $bits / 8;
			$this->setKey();
			$this->IVLenght = mcrypt_enc_get_iv_size($this->mcrypt);
			$this->setIV();
			$this->init();
		}
		
		public function init(){
			if(is_resource($this->enc)){
				mcrypt_generic_deinit($this->enc);
				mcrypt_module_close($this->enc);
			}
			$this->enc = mcrypt_module_open($this->algorithm, "", $this->mode, "");
			mcrypt_generic_init($this->enc, $this->key, $this->IV);
			
			if(is_resource($this->dec)){
				mcrypt_generic_deinit($this->dec);
				mcrypt_module_close($this->dec);
			}
			$this->dec = mcrypt_module_open($this->algorithm, "", $this->mode, "");
			mcrypt_generic_init($this->dec, $this->key, $this->IV);	
		}
		
		public function setKey($key = ""){
			$this->key = str_pad($key, $this->keyLenght, "\x00", STR_PAD_RIGHT);
		}

		public function setIV($IV = ""){
			$this->IV = str_pad($IV, $this->IVLenght, "\x00", STR_PAD_RIGHT);
		}
		
		public function encrypt($plaintext){
			return mcrypt_generic($this->enc, $plaintext);
		}

		public function decrypt($ciphertext){
			return mdecrypt_generic($this->dec, $ciphertext);
		}	

	}
}