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


//22
// 1.0.0, 1.0.1

$pstruct = array(
	"00" => array(
		"int",
	),
	
	"01" => array(
		"int",
		"string",
		"long",
		"int",
		"byte",
		"byte",
		"ubyte",
		"ubyte",
	),
	
	"02" => array(
		"string",	
	),
	
	"03" => array(
		"string",
	),
	
	"04" => array(
		"long",
	),	
	
	"05" => array(
		"int",
		"short",
		"short",
		"short",
	),
	
	"06" => array(
		"int",
		"int",
		"int",
	),
	
	"08" => array(
		"short",
		"short",
		"float",
	),
	
	"09" => array(
		"byte",
		"byte",
		"byte",
		"short",
		"long",
	),
	
	"0d" => array(
		"double",
		"double",
		"double",
		"double",
		"float",
		"float",
		"bool",
	),
	
	"10" => array(
		"short",
	),
	
	"11" => array(
		"int",
		"byte",
		"int",
		"byte",
		"int",
	),
	
	"12" => array(
		"int",
		"byte",
	),
	
	"14" => array(
		"int",
		"string",
		"int",
		"int",
		"int",
		"byte",
		"byte",
		"short",
	),
	
	"15" => array(
		"int",
		"short",
		"byte",
		"short",
		"int",
		"int",
		"int",
		"byte",
		"byte",
		"byte",
	),
	
	"16" => array(
		"int",
		"int",
	),
	
	"17" => array(
		"int",
		"byte",
		"int",
		"int",
		"int",
		"int", //if >0, fireball
		"short",
		"short",
		"short",
	),
	
	"18" => array(
		"int",
		"byte",
		"int",
		"int",
		"int",
		"byte",
		"byte",
		"entityMetadata",
	),
	
	"19" => array(
		"int",
		"string",
		"int",
		"int",
		"int",
		"int",
	),
	
	"1a" => array(
		"int",
		"int",
		"int",
		"int",
		"short",
	),
	
	"1b" => array(
		"float",
		"float",
		"float",
		"float",
		"bool",
		"bool",	
	),
	
	"1c" => array(
		"int",
		"short",
		"short",
		"short",
	),
	
	"1d" => array(
		"int",
	),
	
	"1e" => array(
		"int",
	),
	
	"1f" => array(
		"int",
		"byte",
		"byte",
		"byte",
	),
	
	"20" => array(
		"int",
		"byte",
		"byte",
	),
	
	"21" => array(
		"int",
		"byte",
		"byte",
		"byte",
		"byte",
		"byte",
	),
	
	"22" => array(
		"int",
		"int",
		"int",
		"int",
		"byte",
		"byte",
	),
	
	"26" => array(
		"int",
		"byte",
	),
	
	"27" => array(
		"int",
		"int",
	),
	
	"28" => array(
		"int",
		"entityMetadata",
	),
	
	"29" => array(
		"int",
		"byte",
		"byte",
		"short",
	),
	
	"2a" => array(
		"int",
		"byte",
	),
	
	"2b" => array(
		"float",
		"short",
		"short",
	),
	
	"32" => array(
		"int",
		"int",
		"bool",
	),
	
	"33" => array(
		"int",
		"short",
		"int",
		"byte",
		"byte",
		"byte",
		"int",
		"chunkArray",
	),
	
	"34" => array(
		"int",
		"int",
		"short",
		"multiblockArray",
	),
	
	"35" => array(
		"int",
		"byte",
		"int",
		"byte",
		"byte",
	),
	
	"36" => array(
		"int",
		"short",
		"int",
		"byte",
		"byte",
	),
	
	"3c" => array(
		"double",
		"double",
		"double",
		"float",
		"int",
		"explosionRecord"
	),
	
	"3d" => array(
		"int",
		"int",
		"byte",
		"int",
		"int",
	),
	
	"46" => array(
		"byte",
		"byte",
	),
	
	"47" => array(
		"int",
		"bool",
		"int",
		"int",
		"int",
	),
	
	"64" => array(
		"byte",
		"byte",
		"string",
		"byte",
	),
	
	"65" => array(
		"byte",
	),
	
	"67" => array(
		"byte",
		"short",
		"slotData",
	),
	
	"68" => array(
		"byte",
		"short",
		"slotArray",
	),
	
	"69" => array(
		"byte",
		"short",
		"short",
	),
	
	"6a" => array(
		"byte",
		"short",
		"bool",
	),
	
	"6b" => array(
		"short",
		"slotData",
	),
	
	"6c" => array(
		"byte",
		
	),
	
	"82" => array(
		"int",
		"short",
		"int",
		"string",
		"string",
		"string",
		"string",
	),
	
	"83" => array(
		"short",
		"short",
		"ubyte",
		"byteArray",
	),
	
	"c8" => array(
		"int",
		"byte",
	),
	
	"c9" => array(
		"string",
		"byte",
		"short",
	),
	
	"cb" => array(
		"string",
	),
	
	"fa" => array(
		"string",
		"short",
		"byteArray",
	),
	
	"fe" => array(
		"string",
	),
	
	"ff" => array(
		"string",	
	),
);


?>