<?php

require_once("classes/MinecraftClient.class.php");
define("DEBUG", 1); //0 none, 1 messages, 2 all
define("CURRENT_PROTOCOL", 29);
define("ACTION_MODE", 1); //1 => ticks, other by packets. 

file_put_contents("console.log", "");
file_put_contents("packets.log", "");
$M = new MinecraftClient("127.0.0.1");
$M->activateSpout();
$M->event("onChat", "chatHandler");
$M->event("onPluginMessage", "pluginViewer");
$M->connect("BotAyuda", "");

function pluginViewer($data, $event, $ob){
	console("[*] Plugin Message: Channel => ".$data["channel"].", Data: ".$data["data"]);
 }

function chatHandler($message, $event, $ob){
	$m = explode(" ",$message);
	if(array_pop($m) == "1234"){
		$ob->say("Adios, mundo cruel!");
		$ob->logout();
	}
}

