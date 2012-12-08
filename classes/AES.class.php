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



			DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
	TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

	0. You just DO WHAT THE FUCK YOU WANT TO.


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
			$this->bytes = $blockSize >> 3;
			$this->key = $this->encIV = $this->decIV = str_repeat("\x00", openssl_cipher_iv_length($this->mode));
			$this->IVLenght = openssl_cipher_iv_length($this->mode);
		}
		
		public function setKey($key = ""){
			$this->key = str_pad($key, $this->IVLenght, "\x00", STR_PAD_RIGHT);
		}

		public function setIV($IV = ""){
			$this->encIV = $this->decIV = str_pad($IV, $this->IVLenght, "\x00", STR_PAD_RIGHT);
		}
		
		protected function _shiftIV(&$IV, $str){ //Only for CFB
			if(!isset($str{$this->IVLenght - 1})){
				$len = min($this->IVLenght, strlen($str));
				$IV = substr($IV, $len) . substr($str, -$len);
			}else{
				$IV = substr($str, -$this->IVLenght);
			}
		}
		
		public function encrypt($plaintext){
			$ciphertext = openssl_encrypt($plaintext, $this->mode, $this->key, true, $this->encIV);
			$this->_shiftIV($this->encIV, $ciphertext);
			return $ciphertext;
		}

		public function decrypt($ciphertext){
			$plaintext = openssl_decrypt($ciphertext, $this->mode, $this->key, true, $this->decIV);
			$this->_shiftIV($this->decIV, $ciphertext);
			return $plaintext;
		}
		
		public function init(){
		
		}

	}
	
}else{ //mcrypt


	class AES{
		private $key, $keyLenght, $IV, $IVLenght, $enc, $dec, $mode, $algorithm;

		function __construct($bits, $mode, $blockSize){
			console("[DEBUG] [AES] Using Mcrypt extension", true, true, 2);
			$this->algorithm = "rijndael-".intval($bits);
			$this->mode = strtolower($mode);
			$mcrypt = mcrypt_module_open($this->algorithm, "", $this->mode, "");
			$this->IVLenght = mcrypt_enc_get_iv_size($mcrypt);
			mcrypt_module_close($mcrypt);
			$this->keyLenght = $bits >> 3;
			$this->setKey();			
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