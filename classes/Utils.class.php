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


if(!defined("GMPEXT")){
	@define("GMPEXT", false);
}
if(!defined("HEX2BIN")){
	@define("HEX2BIN", false);
}

define("BIG_ENDIAN", 0x00);
define("LITTLE_ENDIAN", 0x01);
define("ENDIANNESS", (pack('d', 1) == "\77\360\0\0\0\0\0\0" ? BIG_ENDIAN:LITTLE_ENDIAN));

class Utils{
	public static $hexToBin = array(
		"0" => "0000",
		"1" => "0001",
		"2" => "0010",
		"3" => "0011",
		"4" => "0100",
		"5" => "0101",
		"6" => "0110",
		"7" => "0111",
		"8" => "1000",
		"9" => "1001",
		"a" => "1010",
		"b" => "1011",
		"c" => "1100",
		"d" => "1101",
		"e" => "1110",
		"f" => "1111",		
	);

	public static function sha1($input){
		$binary = Utils::hexToBin(sha1($input));
		$negative = false;
		$len = strlen($binary);
		if($binary{0} == "1"){
			$negative = true;
			for($i = 0; $i < $len; ++$i){
				$binary{$i} = $binary{$i} === "1" ? "0":"1";
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
		
		$hash = Utils::binToHex($binary);
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
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
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
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
	
	public static function strToBin($str){
		return Utils::hexToBin(Utils::strToHex($str));
	}
	
	public static function hexToBin($hex){
		$bin = "";
		$len = strlen($hex);		
		for($i = 0; $i < $len; ++$i){
			$bin .= Utils::$hexToBin[$hex{$i}];
		}
		return $bin;
	}
	
	public static function binToStr($bin){
		$len = strlen($bin);
		if(($len % 8) != 0){
			$bin = substr($bin, 0, -($len % 8));
		}
		$str = "";
		for($i = 0; $i < $len; $i += 8){
			$str .= chr(bindec(substr($bin, $i, 8)));
		}
		return $str;
	}
	public static function binToHex($bin){
		$len = strlen($bin);
		if(($len % 8) != 0){
			$bin = substr($bin, 0, -($len % 8));
		}
		$hex = "";
		for($i = 0; $i < $len; $i += 4){
			$hex .= dechex(bindec(substr($bin, $i, 4)));
		}
		return $hex;
	}
	
	public static function strToHex($str){
		return bin2hex($str);
	}
	
	public static function hexToStr($hex){
		if(HEX2BIN === true){
			return hex2bin($hex);
		}		
		return pack("H*" , $hex);
	}

	public static function readString($str){
		return preg_replace('/\x00(.)/s', '$1', $str);
	}
	
	public static function writeString($str){
		return preg_replace('/(.)/s', "\x00$1", $str);
	}
	
	public static function readByte($c, $signed = true){
		$b = ord($c{0});
		if($signed === true and ($b & 128) == 128){ //calculate Two's complement
			$b = -128 + ($b & 127);
		}
		return $b;
	}
	
	public static function writeByte($c){
		if($c > 255){
			return false;
		}
		if($c < 0 and $c >= -128){
			$c = 0xff + $c + 1;
		}
		return chr($c);
	}

	public static function readShort($str){
		list(,$unpacked) = unpack("n", $str);
		if($unpacked > 32767){
			$unpacked -= pow(2, 16); // Convert unsigned short to signed short
		}
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
		return (int) $unpacked;
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
		list(,$firstHalf,$secondHalf) = unpack("N*", $str);
		if(GMPEXT === true){
			$value = gmp_add($secondHalf, gmp_mul($firstHalf, "4294967296"));
			if(gmp_cmp($value, "9223372036854775808") >= 0){
				$value = gmp_sub($value, "18446744073709551616");
			}
			return gmp_strval($value);
		}else{
			$value = bcadd($secondHalf, bcmul($firstHalf, "4294967296"));
			if(bccomp($value, "9223372036854775808") >= 0){
				$value = bcsub($value, "18446744073709551616");
			}
			return $value;
		}
	}
	
	public static function writeLong($value){
		return ENDIANNESS === BIG_ENDIAN?pack('d', $value):strrev(pack('d', $value));
	}	
	
}