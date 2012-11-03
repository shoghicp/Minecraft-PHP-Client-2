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
				Version 2, December 2004

Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>

Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

			DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
	TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

	0. You just DO WHAT THE FUCK YOU WANT TO.


*/


if(!defined("GMPEXT")){
	@define("GMPEXT", false);
}
if(!defined("HEX2BIN")){
	@define("HEX2BIN", false);
}

define("BIG_ENDIAN", 0x00);
define("LITTLE_ENDIAN", 0x01);
define("ENDIANNESS", (pack('d', 1) === "\77\360\0\0\0\0\0\0" ? BIG_ENDIAN:LITTLE_ENDIAN));
console("[DEBUG] Endianness: ".(ENDIANNESS === LITTLE_ENDIAN ? "Little Endian":"Big Endian"), true, true, 2);

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
	
	public function getOS(){
		$uname = strtoupper(php_uname("s"));
		if(strpos($uname, "WIN") !== false){
			return "win";
		}else{
			return "linux";
		}
	}
	
	public static function hexdump($bin){
		$output = "";
		$bin = str_split($bin, 16);
		foreach($bin as $counter => $line){
			$hex = chunk_split(chunk_split(str_pad(bin2hex($line), 32, " ", STR_PAD_RIGHT), 2, " "), 24, " ");
			$ascii = preg_replace('#([^\x20-\x7E])#', '.', $line);
			$output .= str_pad(dechex($counter << 4), 4, "0", STR_PAD_LEFT). "  " . $hex . " " . $ascii . PHP_EOL;		
		}
		return $output;
	}
	
	

	public static function generateKey($startEntropy = ""){
		//not much entropy, but works ^^
		$entropy = array(
			implode(stat(__FILE__)),
			lcg_value(),
			print_r($_SERVER, true),
			implode(mt_rand(0,394),get_defined_constants()),
			get_current_user(),
			print_r(ini_get_all(),true),
			(string) memory_get_usage(),
			php_uname(),
			phpversion(),
			zend_version(),
			getmypid(),
			mt_rand(),
			rand(),
			implode(get_loaded_extensions()),
			sys_get_temp_dir(),
			disk_free_space("."),
			disk_total_space("."),
			(function_exists("openssl_random_pseudo_bytes") and version_compare(PHP_VERSION, "5.3.4", ">=")) ? openssl_random_pseudo_bytes(16):microtime(true),
			function_exists("mcrypt_create_iv") ? mcrypt_create_iv(16, MCRYPT_DEV_URANDOM):microtime(true),
			uniqid(microtime(true),true),
			file_exists("/dev/urandom") ? fread(fopen("/dev/urandom", "rb"),16):microtime(true),
		);
		
		shuffle($entropy);
		$value = Utils::hexToStr(md5((string) $startEntropy));
		unset($startEntropy);
		foreach($entropy as $c){
			$c = (string) $c;
			for($i = 0; $i < 4; ++$i){
				$value ^= md5($i . $c . microtime(true), true);
				$value ^= substr(sha1($i . $c . microtime(true), true), $i, 16);
			}			
		}
		unset($entropy);
		return $value;
	}
	
	public static function round($number){
		return round($number, 0, PHP_ROUND_HALF_DOWN);
	}
	
	public static function distance($pos1, $pos2){
		return sqrt(pow($pos1["x"] - $pos2["x"], 2) + pow($pos1["y"] - $pos2["y"], 2) + pow($pos1["z"] - $pos2["z"], 2));
	}
	
	public static function angle3D($pos1, $pos2){
		$X = $pos1["x"] - $pos2["x"];
		$Z = $pos1["z"] - $pos2["z"];
		$dXZ = sqrt(pow($X, 2) + pow($Z, 2));
		$Y = $pos1["y"] - $pos2["y"];
		$hAngle = rad2deg(atan2($Z, $X) - M_PI_2);
		$vAngle = rad2deg(-atan2($Y, $dXZ));
		return array("yaw" => $hAngle, "pitch" => $vAngle);
	}
	
	public static function sha1($input){
		$number = new Math_BigInteger(sha1($input, true), -256);
		$zero = new Math_BigInteger(0);
		return ($zero->compare($number) <= 0 ? "":"-") . ltrim($number->toHex(), "0");
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
	
	public static function curl_post($page, $args, $timeout = 10){
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Minecraft PHP Client 2'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
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
	
	public static function readBool($b){
		return Utils::readByte($b, false) === 0 ? false:true;
	}
	
	public static function writeBool($b){
		return Utils::writeByte($b === true ? 1:0);
	}
	
	public static function readByte($c, $signed = true){
		$b = ord($c{0});
		if($signed === true and ($b & 0x80) === 0x80){ //calculate Two's complement
			$b = -0x80 + ($b & 0x7f);
		}
		return $b;
	}
	
	public static function writeByte($c){
		if($c > 0xff){
			return false;
		}
		if($c < 0 and $c >= -0x80){
			$c = 0xff + $c + 1;
		}
		return chr($c);
	}

	public static function readShort($str, $signed = true){
		list(,$unpacked) = unpack("n", $str);
		if($unpacked > 0x7fff and $signed === true){
			$unpacked -= 0x10000; // Convert unsigned short to signed short
		}
		return $unpacked;
	}
	
	public static function writeShort($value){
		if($value < 0){
			$value += 0x10000; 
		}
		return pack("n", $value);
	}

	public static function readInt($str){
		list(,$unpacked) = unpack("N", $str);
		if($unpacked >= 2147483648){
			$unpacked -= 4294967296;
		}
		return (int) $unpacked;
	}
	
	public static function writeInt($value){
		if($value < 0){
			$value += 0x100000000; 
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
	
	public static function printFloat($value){
		return preg_replace("/(\.\d+?)0+$/", "$1", sprintf("%F", $value));
	}
	
	public static function writeDouble($value){
		return ENDIANNESS === BIG_ENDIAN?pack('d', $value):strrev(pack('d', $value));
	}

	public static function readLong($str){
		$long = new Math_BigInteger($str, -256);
		return $long->toString();
	}
	
	public static function writeLong($value){
		$long = new Math_BigInteger($value, -10);
		return $long->toBytes(true);
	}	
	
}