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


class RemoteConsole{
	protected $client, $socket, $event, $event2, $clients, $users, $login;
	
	function __construct($client, $port = 31337){
		$this->client = $client;
		$this->clients = array();
		$this->socket = new Socket("127.0.0.1", (int) $port, true);
		$this->event = $this->client->event("onTick", "recieve", $this);
		$this->event2 = $this->client->event("onChat", "send", $this);
		$this->users = array();
		$this->login = array();
		console("[INFO] [RemoteConsole] Loaded");
	}
	
	public function addUser($username, $password){
		$this->users[$username] = md5($password);
	}
	
	public function send($data){
		$data = preg_replace("/\xa7[a-z0-9]/", "", $data);
		foreach($this->clients as $i => $client){
			if(isset($this->logins[$i])){
				$client->write("\x03".Utils::writeInt(strlen($data)).$data);
			}
		}
	}
	
	public function recieve(){
		$connection = $this->socket->listenSocket();
		if($connection !== false and $connection !== null){
			$this->clients[] = $connection;
			socket_getpeername($connection->sock, $ip, $port);
			$this->client->trigger("onRemoteConsoleConnect", array("ip" => $ip, "port" => $port));
		}
		foreach($this->clients as $i => $client){
			if(isset($this->logins[$i])){
				continue;
			}
			if(($pid = $client->read(1, true)) !== false){
				$pid = ord($pid);
				if(!isset($this->logins[$i]) and $pid !== 1){
					$client->close();
					unset($this->clients[$i]);
					continue;
				}
				switch($pid){
					case 1:
						$username = $client->read(Utils::readShort($client->read(2)));
						$password = $client->read(Utils::readShort($client->read(2)));
						if(isset($this->users[$username]) and $this->users[$username] === md5($password)){
							socket_getpeername($connection->sock, $ip, $port);
							$this->client->trigger("onRemoteConsoleJoin", array("ip" => $ip, "port" => $port, "username" => $username));
							$this->logins[$i] = $username;
						}else{
							$client->close();
							unset($this->clients[$i]);
						}
						break;
					case 2:
						$this->client->trigger("onRemoteConsoleLeave", $this->logins[$i]);
						$client->write("\x02");
						$client->close();
						unset($this->clients[$i]);
						unset($this->logins[$i]);
						break;
					case 3:
						$text = $client->read(Utils::readInt($client->read(4)));
						$this->client->say("#".$username.": ".$text);
						break;
					case 4:
						$text = $client->read(Utils::readInt($client->read(4)));
						$this->client->say($text);
						break;
				}
			}
		}
	}
}
	