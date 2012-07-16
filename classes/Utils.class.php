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


define("BIG_ENDIAN", 0x00);
define("LITTLE_ENDIAN", 0x01);
define("ENDIANNESS", (pack('d', 1) == "\77\360\0\0\0\0\0\0" ? BIG_ENDIAN:LITTLE_ENDIAN));

class Utils{


	public static function sha1($input){
		$hash = Utils::hexToStr(sha1($input));
		$binary = "";
		$len = strlen($hash);
		for($i = 0; $i < $len; ++$i){
			$binary .= str_pad(decbin(ord($hash{$i})),8,"0",STR_PAD_LEFT);
		}
		$negative = false;
		$len = strlen($binary);
		if($binary{0} == "1"){
			$negative = true;
			for($i = 0; $i < $len; ++$i){
				$binary{$i} = $binary{$i} == "1" ? "0":"1";
			}
			for($i = strlen($binary) - 1; $i >= 0; --$i){
				if($binary{$i} == "1"){
					$binary{$i} = "0";
				}else{
					$binary{$i} = "1";
					break;
				}
			}
		}
		
		$hash = "";
		for($i = 0; $i < $len; $i += 8){
			$hash .= chr(bindec(substr($binary,$i,8)));
		}
		$hash = Utils::strToHex($hash);
		$len = strlen($hash);
		for($i = 0; $i < $len; ++$i){
			if($hash{$i} == "0"){
				$hash{$i} = "x";
			}else{
				break;
			}
		}
		
		return ($negative == true ? "-":"").str_replace("x", "", $hash);
	}
	
	public static function microtime(){
		//Since PHP >= 5.3.3 is needed to run the client, we don't need anymore this function
		/*$time = explode(" ",microtime());
		$time = $time[1] + floatval($time[0]);
		return $time;*/
		return microtime(true);
	}
	
	public static function curl_get($page){
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Minecraft PHP Client 2'));
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		return curl_exec($ch);
	}
	
	public static function curl_post($page, $args){
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Minecraft PHP Client 2'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		return curl_exec($ch);
	}

	public static function padHex($hex){
		return str_pad($hex, 2, "0", STR_PAD_LEFT);
	}
	
	public static function strToHex($str){
		return bin2hex($str);
	}
	
	public static function hexToChr($hex){
		return chr(hexdec($hex));
	}
	
	public static function hexToStr($hex){
		if(HEX2BIN === true){
			return hex2bin($hex);
		}
		return implode(array_map("Utils::hexToChr",str_split($hex,2)));
	}

	public static function utf16_to_utf8($str) {
		$c0 = ord($str{0});
		$c1 = ord($str{1});

		if ($c0 == 0xfe && $c1 == 0xff) {
			$be = true;
		} else if ($c0 == 0xff && $c1 == 0xfe) {
			$be = false;
		} else {
			return $str;
		}
		$len = strlen($str);
		$dec = "";
		for ($i = 0; $i < $len; $i += 2) {
			$c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) : 
					ord($str[$i + 1]) << 8 | ord($str[$i]);
			if ($c >= 0x0001 && $c <= 0x007f) {
				$dec .= chr($c);
			} else if ($c > 0x07ff) {
				$dec .= chr(0xe0 | (($c >> 12) & 0x0f));
				$dec .= chr(0x80 | (($c >>  6) & 0x3f));
				$dec .= chr(0x80 | (($c >>  0) & 0x3f));
			} else {
				$dec .= chr(0xc0 | (($c >>  6) & 0x1f));
				$dec .= chr(0x80 | (($c >>  0) & 0x3f));
			}
		}
		return $dec;
	}

	public static function readString($str){
		$len = strlen($str);		
		if($len % 2 != 0){
			return false;
		}
		$ret = "";
		/*for($i = 0; $i < $len; $i += 2){
			$ret .= Utils::utf16_to_utf8(substr($str,$i,2));
		}*/
		for($i = 0; $i < $len; $i += 2){
			$ret .= $str{$i+1};
		}
		return $ret;
	}
	
	public static function writeString($str){
		$len = strlen($str);
		$ret = "";
		for($i = 0; $i < $len; ++$i){
			$ret .= "\x00".$str{$i};
		}
		return $ret;
	}
	
	public static function readByte($c, $signed = true){
		$b = ord($c{0});
		if($signed === true and ($b & 0x80) == 0x80){ //calculate Two's complement
			$b = -0x80 + ($b & 0x7f);
		}
		return $b;
	}
	
	public static function writeByte($c){
		if($c > 255){
			return false;
		}
		if($c < 0 and $c >= -0x80){
			$c = 0xff + $c + 1;
		}
		return chr($c);
	}

	public static function readShort($str){
		list(,$unpacked) = unpack("n", $str);
		if($unpacked >= pow(2, 15)) $unpacked -= pow(2, 16); // Convert unsigned short to signed short.
		return $unpacked;
	}
	
	public static function writeShort($value){
		if($value < 0){
			$value += pow(2, 16); 
		}
		return pack("n", $value);
	}

	public static function readInt($str){
		list(,$unpacked) = unpack("N", $str);
		if($unpacked >= pow(2, 31)) $unpacked -= pow(2, 32); // Convert unsigned int to signed int
		return $unpacked;
	}
	
	public static function writeInt($value){
		if($value < 0){
			$value += pow(2, 32); 
		}
		return pack("N", $value);
	}
	
	public static function readFloat($str){
		list(,$value) = ENDIANNESS === BIG_ENDIAN?unpack('f', $str):unpack('f', strrev($str));
		return $value;
	}
	
	public static function writeFloat($value){
		return ENDIANNESS === BIG_ENDIAN?pack('f', $value):strrev(pack('f', $value));
	}

	public static function readDouble($str){
		list(,$value) = ENDIANNESS === BIG_ENDIAN?unpack('d', $str):unpack('d', strrev($str));
		return $value;
	}
	
	public static function writeDouble($value){
		return ENDIANNESS === BIG_ENDIAN?pack('d', $value):strrev(pack('d', $value));
	}

	public static function readLong($str){		
		list(,$firstHalf) = unpack("N", substr($str, 0, 4));
		list(,$secondHalf) = unpack("N", substr($str, 4, 4));
		if(GMPEXT === true){
			$value = gmp_add($secondHalf, gmp_mul($firstHalf, "4294967296"));
			if(gmp_cmp($value, gmp_pow(2, 63)) >= 0){
				$value = gmp_sub($value, gmp_pow(2, 64));
			}
			return gmp_strval($value);
		}else{
			$value = bcadd($secondHalf, bcmul($firstHalf, "4294967296"));
			if(bccomp($value, bcpow(2, 63)) >= 0){
				$value = bcsub($value, bcpow(2, 64));
			}
			return $value;
		}
	}
	
	public static function writeLong($value){
		return ENDIANNESS === BIG_ENDIAN?pack('d', $value):strrev(pack('d', $value));
	}	
	
}