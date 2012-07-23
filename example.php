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


define("ACTION_MODE", 1);
define("DEBUG", 1);
define("LOG", false);
include("config.php");
require_once("classes/MinecraftClient.class.php");
require("misc/dependencies.php");

$client = new MinecraftClient("127.0.0.1");
$info = $client->ping();
console("[INFO] Name: ".$info[0]);
console("[INFO] Online players: ".$info[1]);
console("[INFO] Max players: ".$info[2]);
echo PHP_EOL;
$client = new MinecraftClient("127.0.0.1");
$client->connect("Player", "password"); //NO CODE IS EXECUTED AFTER THIS LINE. BE SURE TO CREATE EVENTS BEFORE THIS LINE