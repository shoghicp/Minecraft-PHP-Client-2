<?php


/*
		for($i=36;$i<=44;++$i){
			$slot = $ginfo["inventory"][$i];
			if(isset($food[$slot[0]]) == true and ($ginfo["food"] + $food[$slot[0]]) <= 20){
				write_packet("10",array("slot" => $i-36));
				write_packet("0f", array("x" => -1, "y" => -1, "z" => -1, "direction" => -1, "slot" => array(-1)));
				$ginfo["food"] = 20;
				$eat = true;
				break;
			}elseif(!isset($food[$slot[0]]) and arg("only-food", false) == true){
				for($a=0;$a<min(3,$slot[1]);++$a){
					write_packet("10",array("slot" => $i-36));				
					write_packet("0e", array("status" => 4, "x" => 0, "y" => 0, "z" => 0, "face" => 0));
				}
			}
		}
		if($ginfo["timer"]["sayfood"]<=$time and $eat == false and $ginfo["food"] <= 12){
			$ginfo["timer"]["sayfood"] = $time+60;
			$messages = array(
				"Necesito comida!",
				"Comida!!!",
				"Me muero de hambre!",
				"No tengo comida!",			
			);
			Message($messages[count($messages)-1]);
		}

*/

class NoHunger{
	protected $client, $player, $only_food;
	function __construct($client, $only_food = true){
		$this->client = $client;
		$this->player = $this->client->getPlayer();
		$this->client->event("onHealthChange", "handler", $this);
		$this->client->event("onInventoryChanged", "handler", $this);
		$this->only_food = $only_food;
		console("[INFO] [NoHunger] Loaded");
	}
	
	public function setOnlyFood($only_food){
		$this->only_food = $only_food;
	}	
	public function handler($health, $event){
		include(dirname(__FILE__)."/../materials.php");
		for($i=36;$i<=44;++$i){
			$slot = $this->client->getInventorySlot($i);
			if($event == "onHealthChange" and isset($food[$slot[0]]) == true and ($health["food"] + $food[$slot[0]]) <= 20){
				$this->client->changeSlot($i-36);
				$this->client->eatSlot();
				$eat = true;
				console("[DEBUG] [NoHunger] Eated ".$slot[0], true, true, 2);
				break;
			}elseif(!isset($food[$slot[0]]) and $this->only_food == true){
				for($a=0;$a<min(3,$slot[1]);++$a){
					$this->client->changeSlot($i-36);
					$this->client->dropSlot();
				}
			}
		}
	}

}
