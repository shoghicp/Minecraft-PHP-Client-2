<?php

require_once("classes/MinecraftDissector.class.php");

file_put_contents("console.log", "");
file_put_contents("packets.log", "");

define("DEBUG", 1); //0 none, 1 messages, 2 all
define("CURRENT_PROTOCOL", 29);


$M = new MinecraftDissector("spout_recieved.dump");
while(1){
$M->readPacket();
}
?>