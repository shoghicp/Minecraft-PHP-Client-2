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


define("ACTION_MODE", 1);
define("DEBUG", 1);
define("LOG", false);
include("config.php");
require("misc/dependencies.php");
require_once("classes/MinecraftClient.class.php");

$client = new MinecraftClient("127.0.0.1");
$info = $client->ping();
console("[INFO] Name: ".$info[0]);
console("[INFO] Online players: ".$info[1]);
console("[INFO] Max players: ".$info[2]);
echo PHP_EOL;
$client = new MinecraftClient("127.0.0.1");
$client->connect("Player", "password"); //NO CODE IS EXECUTED AFTER THIS LINE. BE SURE TO CREATE EVENTS BEFORE THIS LINE