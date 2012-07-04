<?php

require_once("classes/MinecraftClient.class.php");


define("DEBUG", 1); //0 none, 1 messages, 2 all


file_put_contents("console.log", "");
file_put_contents("packets.log", "");
$M = new MinecraftClient("127.0.0.1");
$M->event("onChat", "chatHandler");
$M->connect("BotAyuda", "");
function chatHandler($message, $event, $ob){
	$m = explode(" ",$message);
	if(array_pop($m) == "1234"){
		$ob->say("Adios, mundo cruel!");
		$ob->logout();
	}
}

