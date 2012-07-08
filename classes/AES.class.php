<?php

/*

Incomplete AES class with buffering

*/

if(version_compare("5.3.3", PHP_VERSION) > 0){
	die("[ERROR] Use PHP >= 5.3.3");
}

class AES{
	private $key, $encIV, $decIV, $IVLenght, $bytes;

	function __construct($bits, $mode, $blockSize){
		$this->mode = "AES-".$bits."-".$mode;
		$this->bytes = $blockSize / 8;
		$this->key = $this->encIV = $this->decIV = str_repeat("\x00", openssl_cipher_iv_length($this->mode));
		$this->IVLenght = openssl_cipher_iv_length($this->mode);
	}
	
	public function setKey($key){
		$this->key = str_pad($key, $this->IVLenght, "\x00", STR_PAD_RIGHT);
	}

	public function setIV($IV){
		$this->encIV = $this->decIV = str_pad($IV, $this->IVLenght, "\x00", STR_PAD_RIGHT);
	}
	
	protected function _shiftIV($IV, $str){
		$len = min($this->IVLenght, strlen($str));
		return substr($IV, $len).substr($str, -$len);
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

}