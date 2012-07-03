<?php

require_once(dirname(__FILE__)."/Utils.class.php");


class Anvil{
	protected $chunk;
	
	function __construct($data, $compressed = true){
		$chunk = $compressed === true ? gzinflate(substr($data,2)):$data;
	}
	
	function getBlock($x, $y, $z){
	
	
	}


}