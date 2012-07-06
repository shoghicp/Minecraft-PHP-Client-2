<?php

/*
00000001 0x01
00000010 0x02
00000100 0x04
00001000 0x08
00010000 0x10
00100000 0x20
01000000 0x40
10000000 0x80
01111111 0x7f

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
		$time = explode(" ",microtime());
		$time = $time[1] + floatval($time[0]);
		return $time;
	}
	
	public static function curl_get($page){
		$ch = curl_init ($page);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array('User-Agent: Minecraft PHP Client'));
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);
	}
	
	public static function curl_post($page, $args){
		$ch = curl_init($page);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array('User-Agent: Minecraft PHP Client'));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
	}

	public static function padHex($hex){
		return str_pad($hex, 2, "0", STR_PAD_LEFT);
	}
	
	public static function strToHex($str){
		return implode(array_map("Utils::padHex",array_map("dechex",array_map("ord",str_split($str,1)))));
	}
	
	public static function hexToStr($hex){
		return implode(array_map("chr",array_map("hexdec",str_split($hex,2))));
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
		if($signed == true and ($b & 0x80) == 0x80){ //calculate Two's complement
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
		list(,$unpacked) = unpack("n", substr($str, 0, 2));
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
		list(,$unpacked) = unpack("N", substr($str, 0, 4));
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
		list(,$value) = ENDIANNESS === BIG_ENDIAN?unpack('f', substr($str,0, 4)):unpack('f', strrev(substr($str,0, 4)));
		return $value;
	}
	
	public static function writeFloat($value){
		return ENDIANNESS === BIG_ENDIAN?pack('f', $value):strrev(pack('f', $value));
	}

	public static function readDouble($str, $signed = true){
		list(,$value) = ENDIANNESS === BIG_ENDIAN?unpack('d', substr($str,0, 8)):unpack('d', strrev(substr($str,0, 8)));
		return $value;
	}
	
	public static function writeDouble($value){
		return ENDIANNESS === BIG_ENDIAN?pack('d', $value):strrev(pack('d', $value));
	}

	public static function readLong($str, $signed = true){
		$n = "";
		for($i=0;$i<8;++$i){
			$n .= bin2hex($str{$i});
		}
		$n = hexdec($n);
		if($signed == true){
			$n = $n>9223372036854775807 ? -(18446744073709551614-$n+1):$n;
		}
		return sprintf("%.0F", $n);
	}
	
	public static function writeLong($value){
		return ENDIANNESS === BIG_ENDIAN?pack('d', $value):strrev(pack('d', $value));
	}	
	
}