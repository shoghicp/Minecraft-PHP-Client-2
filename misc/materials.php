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

	$material = array(
		"nosolid" => array(
			0 => true,
			6 => true,
			/*8 => true,
			9 => true,
			10 => true,
			11 => true,*/
			20 => true,
			//27 => true,
			28 => true,
			29 => true,
			30 => true,
			31 => true,
			32 => true,
			37 => true,
			38 => true,
			39 => true,
			40 => true,
			50 => true,
			55 => true,
			63 => true,
			65 => true,
			//66 => true,
			68 => true,
			85 => true,
			101 => true,
			102 => true,
			106 => true,
			107 => true,			
		),
		"color" => array(
			0 => array(255, 255, 255, 127),
			1 => array(127, 127, 127, 0),
			2 => array(102, 166, 60, 0),
			3 => array(150, 108, 74, 0),
			4 => array(97, 97, 97, 0),
			5 => array(185, 149, 95, 0),
			
			7 => array(7, 7, 7, 0),
			8 => array(38, 92, 255, 0),
			9 => array(38, 92, 255, 0),
			10 => array(252, 87, 0, 0),
			11 => array(252, 87, 0, 0),
			12 => array(251, 244, 189, 0),
			13 => array(144, 136, 132, 0),
			14 => array(255, 241, 68, 0),
			15 => array(226, 192, 170, 0),
			16 => array(63, 63, 63, 0),
			17 => array(108, 87, 54, 0),
			18 => array(57, 61, 13, 0),
			21 => array(25, 70, 181),
			22 => array(25, 70, 181), 
			23 => array(132, 132, 132, 0),
			24 => array(251, 244, 189, 0),
			27 => array(245, 204, 45, 0),
			28 => array(164, 164, 164, 0),
			29 => array(132, 132, 132, 0),
			33 => array(132, 132, 132, 0),
			35 => array(
				0 => array(221, 221, 221, 0),
				1 => array(219, 125, 62, 0),
				2 => array(179, 80, 188, 0),
				3 => array(107, 138, 201, 0),
				4 => array(177, 166, 39, 0),
				5 => array(65, 174, 56, 0),
				6 => array(208, 132, 153, 0),
				7 => array(64, 64, 64, 0),
				8 => array(154, 154, 154, 0),
				9 => array(46, 110, 137, 0),
				10 => array(126, 61, 181, 0),
				11 => array(46, 56, 141, 0),
				12 => array(79, 50, 31, 0),
				13 => array(53, 70, 27, 0),
				14 => array(150, 52, 48, 0),
				15 => array(25, 22, 22, 0),
			),
			41 => array(255, 241, 68, 0),
			42 => array(230, 230, 230, 0),
			43 => array(168, 168, 168, 0),
			44 => array(168, 168, 168, 0),
			45 => array(151, 83, 61, 0),
			46 => array(219, 68, 26, 0),
			47 => array(185, 149, 95, 0),
			48 => array(97, 97, 97, 0),
			49 => array(19, 19, 28, 0),
			50 => array(255, 188, 94, 0),
			51 => array(255, 188, 94, 0),
			53 => array(159, 132, 77, 0),
			55 => array(213, 24, 24, 0),
			56 => array(160, 235, 232, 0),
			57 => array(160, 235, 232, 0),
			61 => array(132, 132, 132, 0),
			62 => array(132, 132, 132, 0),
			66 => array(164, 164, 164, 0),
			67 => array(97, 97, 97, 0),
			73 => array(213, 24, 24, 0),
			74 => array(213, 24, 24, 0),
			75 => array(213, 24, 24, 0),
			76 => array(213, 24, 24, 0),
			78 => array(255, 255, 255, 0),
			79 => array(136, 136, 217, 0),
			80 => array(255, 255, 255, 0),
			86 => array(227, 170, 0, 0),
			87 => array(96, 6, 6, 0),
			89 => array(255, 188, 94, 0),
			91 => array(227, 170, 0, 0),
			93 => array(213, 24, 24, 0),
			94 => array(213, 24, 24, 0),
			98 => array(168, 168, 168, 0),
			108 => array(151, 83, 61, 0),
			109 => array(168, 168, 168, 0),			
			110 => array(102, 166, 60, 0),
			111 => array(127, 127, 127, 0),
			112 => array(74, 42, 48, 0),
			125 => array(185, 149, 95, 0),
			126 => array(185, 149, 95, 0),
			128 => array(251, 244, 189, 0),
			134 => array(185, 149, 95, 0),
			135 => array(185, 149, 95, 0),
			136 => array(185, 149, 95, 0),
		),
		0 => "Air",
		1 => "Stone",
		2 => "Grass",
		3 => "Dirt",
		4 => "Cobblestone",
		5 => "Plank",
		6 => "Sapling",
		7 => "Bedrock",
		8 => "Water",
		9 => "Water",
		10 => "Lava",
		11 => "Lava",
		12 => "Sand",
		13 => "Gravel",
		14 => "Gold Ore",
		15 => "Iron Ore",
		16 => "Coal Ore",
		17 => "Wood",
		18 => "Leave",
		19 => "Sponge",
		20 => "Glass",
		21 => "Lapis Lazuli Ore",
		22 => "Lapis Lazuli Block",
		23 => "Dispenser",
		24 => "Sandstone",
		25 => "Note Block",
		26 => "Bed",
		27 => "Powered Rail",
		28 => "Detector Rail",
		29 => "Sticky Piston",
		30 => "Cobweb",
		31 => "Tall Grass",
		32 => "Dead Bush",
		33 => "Piston",
		34 => "Piston Extension",
		35 => "Wool",
		36 => "Block Entity",
		37 => "Dandelion",
		38 => "Rose",
		39 => "Brown Mushroom",
		40 => "Red Mushroom",
		41 => "Gold Block",
		42 => "Iron Block",
		43 => "Double Slab",
		44 => "Slab",
		45 => "Brick",
		46 => "TNT",
		47 => "Bookshelf",
		48 => "Moss Stone",
		49 => "Obsidian",
		50 => "Torch",
		51 => "Fire",
		52 => "Monster Spawner",
		53 => "Wood Stairs",
		54 => "Chest",
		55 => "Redstone Wire",
		56 => "Diamond Ore",
		57 => "Diamond Block",
		58 => "Crafting Table",
		59 => "Wheat Seeds",
		60 => "Farmland",		
		61 => "Furnace",
		62 => "Burning Furnace",
		63 => "Sign",
		64 => "Wood Door",
		65 => "Ladder",
		66 => "Rail",
		67 => "Cobblestone Stairs",
		68 => "Sign",
		69 => "Lever",
		70 => "Stone Pressure Plate",
		71 => "Iron Door",
		72 => "Wood Presure Plate",
		73 => "Redstone Ore",
		74 => "Redstone Ore",
		75 => "Redstone Torch",
		76 => "Redstone Torch",
		77 => "Stone Button",
		78 => "Snow",
		79 => "Ice",
		80 => "Snow Block",
		81 => "Cactus",
		82 => "Clay Block",
		83 => "Sugar Cane",
		84 => "Jukebox",
		85 => "Fence",
		86 => "Pumpkin",
		87 => "Netherrack",
		88 => "Soul Sand",
		89 => "Glowstone",
		90 => "Portal",
		91 => "Jack-O-Lantern",
		92 => "Cake",
		93 => "Repeater",
		94 => "Repeater",
		95 => "Locked Chest",
		96 => "Trapdoor",
		97 => "Monster Egg",
		98 => "Stone Brick",
		99 => "Huge Brown Mushroom",
		100 => "Huge Red Mushroom",
		101 => "Iron Bars",
		102 => "Glass Panel",
		103 => "Melon",
		104 => "Pumpkin Stem",
		105 => "Melon Stem",
		106 => "Vines",
		107 => "Fence Gate",
		108 => "Brick Stairs",
		109 => "Stone Stairs",
		
		112 => "Nether Brick",
		/*
		Continue this
		*/
	
	);
	
	$food = array(
		282 => 8, //Stew
		364 => 8, //Steak
		320 => 8, //Porkchop
		366 => 6, //Chicken
		297 => 5, //Bread
		350 => 5, //Fish
		260 => 4, //R Apple
		322 => 4, //G Apple
		363 => 3, //Raw Beef
		319 => 3, //Raw Porkchop
		360 => 2, //Melon
		349 => 2, //Raw fish
		265 => 2, //Raw Chicken
		357 => 1, //Cookie
		367 => 4, //Flesh
		375 => 2, //Spider eye,	
	);
	

?>