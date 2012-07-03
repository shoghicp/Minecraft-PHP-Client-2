<?php

require_once("classes/MinecraftClient.class.php");



file_put_contents("console.log", "");
file_put_contents("packets.log", "");

$M = new MinecraftClient("127.0.0.1");
$M->connect("BotAyuda", "");


$M->event("03", "chatHandler");
function chatHandler($data, $eid, $ob){
	$m = explode(" ",$data["data"][0]);
	if(array_pop($m) == "1234"){
		$ob->say("Adios, mundo cruel!");
		$ob->logout("Magic password");
	}
}


$M->process(); //This is after auth and default handlers