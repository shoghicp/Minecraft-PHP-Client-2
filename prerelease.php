<?php


require("config.php");
require_once("classes/MinecraftClient.class.php");
include_once("plugin/Record.plugin.php");
include_once("plugin/Follow.plugin.php");
include_once("plugin/ChatCommand.plugin.php");
include_once("plugin/NoHunger.plugin.php");
include_once("plugin/LagOMeter.plugin.php");


file_put_contents("console.log", "");
file_put_contents("packets.log", "");
$M = new MinecraftClient("127.0.0.1", 38);
//$M->activateSpout();
$M->event("onDeath", "testHandler");
$M->event("onConnect", "testHandler");
$M->event("onPluginMessage", "testHandler");

$M->connect("BotAyuda", "");

function testHandler($message, $event, $ob){
	global $record, $play, $follow, $chat, $lag;
	switch($event){
		case "onChatHandler":
			logg(ChatHandler::format($message), "chat", true, 0);
			break;
		case "onLagEnd":
			$ob->say("[LagOMeter] Lag de ".round($message,2)." segundos acabo");
			break;
		case "onConnect":
			$lag = new LagOMeter($ob, 4);
			$ob->event("onLagEnd", "testHandler");
			$food = new NoHunger($ob);
			$chat = new ChatCommand($ob);
			$ob->event("onChatHandler", "testHandler");
			$chat->addOwner("shoghicp");
			$chat->addAlias("bot");
			$chat->addAlias("bota");
			$chat->addAlias("robot");
			$chat->addCommand("die", "testHandler", true, true);
			$chat->addCommand("say", "testHandler", true, true);
			$chat->addCommand("record", "testHandler", false, true);
			$chat->addCommand("follow", "testHandler", false, true);
			$chat->addCommand("play", "testHandler", false, true);
			$chat->addCommand("stop", "testHandler", false, true);
			$chat->addCommand("tonto", "testHandler");
			$chat->addCommand("chiste", "testHandler");
			$chat->addCommand("dado", "testHandler");
			$chat->addCommand("jump", "testHandler");
			$chat->addCommand("coord", "testHandler");
			break;
		case "onChatCommand_jump":
			$ob->jump();
			break;
		case "onChatCommand_dado":
			$ob->say('El '.mt_rand(1,((intval($message["text"])>0) ? intval($message["text"]):6)));
			break;
		case 'onChatCommand_tonto':
			$ob->say($message["owner"].', tu mas', $message["owner"]);
			break;
		case 'onChatCommand_coord':
			$p = $ob->getPlayer($message["owner"]);
			if(is_object($p)){
				$coords = $p->getPosition();
				$ob->say("Tus ultimas coordenadas conocidas: x = ".$coords["x"].", y = ".$coords["y"].", z = ".$coords["z"], $message["owner"]);
			}
			break;	
		case 'onChatCommand_chiste':
			$chiste = array(
				"El dinero no hace la felicidad pero es mejor llorar en un Ferrari.",
				"Era un cocinero tan feo, pero tan feo, que hacía llorar a las cebollas.",
				"Voy volando. Firmado, Palomo.",
				"Cuantos son 99 en chino? caxi xien.",
				"Era un señor tan sordo, tan sordo, que contestaba al teléfono aunque no sonara.",
				"Como se llama el padre de ete? Donete.",
				"¿Cuánto es 4x4? Empate!!! ¿Y 2x1? Oferta!!",
				"Que le dice un árbol a otro? Ponte el chubasquero que viene un perro",
				"Como se dice en aleman autobus!   ...subenpagenestrugenbagen",
				"¿Que le dice una ficha de puzzle a otra? Solo hay un sitio para mi",
				"Trololololololololololo... Trololololo... Hahaha!!",
				"Llega corriendo Pepita a su casa: ¿Mama, mama, es verdad que soy huerfana...?",
				"- Y tu, ¿como te llamas? - Yo, Bienvenido. - ¡Anda! ¡como mi felpudo!",
				"El coronel dijo: ¡Sigan avanzando! Y todos se perdieron, porque Vanzando no se sabía el camino.",
				"Están en un barco y dice el capitán: -Subid las velas!! Y los de abajo se quedaron a oscuras",
				"Entra uno a un bar de pinchos y... hay ! huy! ahy ! huy!",
				"Había una vez un señor tan gordo, que cada vez que daba una vuelta era su cumpleaños.",
				"Había una vez un hombre tan feo, tan feo, que fue a un concurso de feos y lo perdió por feo.",
				"Había una vez una persona tan pobre, tan pobre, tan pobre que no tenia ni hambre.",
				"Había una vez una vaca que se comió un vidrio, y la leche le salió cortada.",
				"- Ay, cariño... No sé que sería el tiempo sin ti... - Pues que va a ser: empo!!!",
			);
			$ob->say($chiste[mt_rand(0,count($chiste)-1)],$message["owner"]);
			break;
		case "onChatCommand_die":
			$ob->say("Adios, mundo cruel!");
			$ob->logout();
			break;
		case "onChatCommand_say":
			$ob->say($message["text"]);
			break;
		case "onChatCommand_record":
			console("[INFO] Start recording");
			$eid = $ob->getPlayer($message["text"])->getEID();
			$record = new RecordPath($ob, $eid);
			break;
		case "onChatCommand_follow":
			console("[INFO] Start following");
			$eid = $ob->getPlayer($message["text"])->getEID();
			$follow = new FollowPath($ob, $eid);
			break;
		case "onChatCommand_play":
			if(isset($record)){
				$record->stop();
				$path = $record->getPath();
				console("[INFO] Start path");
				$play = new PlayPath($ob, $path);
			}
			break;
		case "onChatCommand_stop":
			if(isset($play)){
				$play->stop();
			}
			if(isset($record)){
				$record->stop();
			}
			if(isset($follow)){
				$follow->stop();
			}
			break;
		case "onPluginMessage":
			console("[INFO] Plugin Message: Channel => ".$message["channel"].", Data: ".$message["data"]);
			break;
		case "onDeath":
			$messages = array(
				"Nooo!!!",
				"Por que??",
				"Solo hice lo que me pedian!",
				"Noooouuu!",			
			);
			$ob->say($messages[mt_rand(0,count($messages)-1)]);
			break;
	}
}

