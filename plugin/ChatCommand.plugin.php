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

class ChatCommand{
	protected $owners, $client, $commands, $chat, $alias;
	
	function __construct($client){
		$this->client = $client;
		$this->owners = array();
		$this->commands = array();
		$this->alias = array();
		$this->client->event("onChatHandler", "handler", $this);
		$this->addAlias($this->client->getPlayer()->getName());
		console("[INFO] [ChatCommand] Loaded");
	}
	
	public function addOwner($owner){
		$this->owners[$owner] = $owner;
	}
	
	public function addAlias($alias){
		$this->alias[] = strtolower($alias);
	}
	
	public function addCommand($command, $callback = false, $onlyMe = false, $ownerOnly = false){
		$command = strtolower($command);
		if(!isset($this->commands[$command])){
			$this->commands[$command] = array();
		}
		$this->commands[$command][] = array($onlyMe, $ownerOnly);
		if($callback !== false){
			$this->client->event("onChatCommand_".$command, $callback);
		}
	}
	
	public function handler($info){
		$owner = $info["owner"];
		$message = explode(" ",$info["message"]);
		$command = strtolower(array_shift($message));
		
		foreach($this->alias as $alias){
			if($command === $alias){
				$command = strtolower(array_shift($message));
				$info["type"] = "private";
				break;
			}
		}
		if(isset($this->commands[$command])){
			console("[DEBUG] [ChatCommand] Command by ".$owner.": ".$command, true, true, 2);
			foreach($this->commands[$command] as $c){
				if(($c[1] == false or ($c[1] == true and isset($this->owners[$owner]))) and (($c[0] == true and $info["type"] == "private") or $c[0] == false)){		
					$this->client->trigger("onChatCommand_".$command, array("text" => implode(" ", $message), "owner" => $owner, "channel" => $info["world"]));
				}elseif((($c[0] == true and $info["type"] == "private") or $c[0] == false) and $c[1] == true and !isset($this->owners[$owner])){
					$this->client->trigger("onChatCommand_NO_PERMISSIONS", array("command" => $command, "owner" => $owner, "channel" => $info["world"]));
				}
			}
		}
		return false;	
	}
}