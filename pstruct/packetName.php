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

$packetName = array(
	0x00 => "Keep Alive",
	0x01 => "Login",
	0x02 => "Handshake",
	0x03 => "Chat Message",
	0x04 => "Time Update",
	0x05 => "Entity Equipment",
	0x06 => "Spawn Position",
	0x07 => "Use Entity",
	0x08 => "Update Health",
	0x09 => "Respawn",
	0x0a => "Player",
	0x0b => "Player Position",
	0x0c => "Player Look",
	0x0d => "Player Position & Look",
	0x0e => "Player Digging",
	0x0f => "Player Block Placement",
	0x10 => "Slot Change",
	0x11 => "Use Bed",
	0x12 => "Animation",
	0x13 => "Entity Action",
	0x14 => "Spawn Named Entity",
	0x15 => "Spawn Dropped Item",
	0x16 => "Collect Item",
	0x17 => "Spawn Object/Vehicle",
	0x18 => "Spawn Mob",
	0x19 => "Spawn Painting",
	0x1a => "Spawn Experience Orb",
	0x1c => "Entity Velocity",
	0x1d => "Destroy Entity",
	0x1e => "Entity",
	0x1f => "Entity Relative Move",
	0x20 => "Entity Look",
	0x21 => "Entity Look & Relative Move",
	0x22 => "Entity Teleport",
	0x23 => "Entity Head Look",
	
	0x26 => "Entity Status",
	0x27 => "Attach Entity",
	0x28 => "Entity Metadata",
	0x29 => "Entity Effect",
	0x2a => "Remove Entity Effect",
	0x2b => "Set Experience",
	
	0x32 => "Chunk Allocation",
	0x33 => "Chunk Data",
	0x34 => "Multi Block Change",
	0x35 => "Block Change",
	0x36 => "Block Action",
	0x37 => "Block Break Animation",
	0x38 => "Map Chunk Bulk",	
	
	0x3c => "Explosion",
	0x3d => "Sound/Particle Effect",
	0x3e => "Named Sound Effect",
	
	0x46 => "Change Game State",
	0x47 => "Global Entity",
	
	0x64 => "Open Window",
	0x65 => "Close Window",
	0x66 => "Click Window",
	0x67 => "Set Slot",
	0x68 => "Set Window Items",
	0x69 => "Update Window Property",
	0x6a => "Confirm Transaction",
	0x6b => "Creative Inventory Action",
	0x6c => "Enchant Item",
	
	0x82 => "Update Sign",
	0x83 => "Item Data",
	0x84 => "Update Tile Entity",
	
	0xc8 => "Increment Statistic",
	0xc9 => "Player List Item",	
	0xca => "Player Abilities",
	0xcb => "Tab-complete",
	0xcc => "Locale and View Distance",
	0xcd => "Client Statuses",
	
	0xfa => "Plugin Message",
	
	0xfc => "Encryption Key Response",
	0xfd => "Encryption Key Request",
	0xfe => "Server List Ping",
	0xff => "Kick",
	
);