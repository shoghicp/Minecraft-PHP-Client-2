<?php



if(version_compare("5.3.3", PHP_VERSION) > 0){
	die("[ERROR] Use PHP >= 5.3.3");
}

class AES{
	private $key, $encIV, $decIV, $IVLenght;

	function __construct($bits, $mode){
		$this->mode = "AES-".$bits."-".$mode;
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
		$len = strlen($str);
		$str = substr($str, $len - $this->IVLenght);
		$len = strlen($str);
		$IV = str_split($IV,1);
		for($i = 0; $i < $len; ++$i){
			array_shift($IV);
			array_push($IV, $str{$i});
		}		
		return implode($IV);
	}
	
	public function encrypt($plaintext){
		$ciphertext = openssl_encrypt($plaintext, $this->mode, $this->key, true, $this->encIV);
		//$this->encIV = $this->_shiftIV($this->encIV, $ciphertext);
		return $ciphertext;
	}

	public function decrypt($ciphertext){
		$plaintext = openssl_decrypt($ciphertext, $this->mode, $this->key, true, $this->decIV);
		//var_dump(Utils::strToHex($this->decIV));
		//$this->decIV = $this->_shiftIV($this->decIV, $plaintext);
		//var_dump(Utils::strToHex($this->decIV));
		return $plaintext;
	}	

}