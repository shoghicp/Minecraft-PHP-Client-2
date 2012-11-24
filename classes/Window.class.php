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


define("WINDOW_INVENTORY", 0);
define("WINDOW_CHEST", 1);
define("WINDOW_WORKBENCH", 2);
define("WINDOW_FURNACE", 3);
define("WINDOW_DISPENSER", 4);
define("WINDOW_ENCHANTMENT", 5);
define("WINDOW_BREWING", 6);
define("WINDOW_MERCHANT", 7);

class Window{
	protected $slots, $zones;
	var $id, $type, $title;
	function __construct($id, $type, $title){
		$this->id = (int) $id;
		$this->type = (int) $type;
		$this->title = (string) $title;
		$this->slots = array();
		$this->zones = array();
		switch($this->type){
			case WINDOW_INVENTORY:
				$this->zones["output"] = 0;
				$this->zones["craft"] = range(1, 4);
				$this->zones["armor"] = range(5, 8);
				$this->zones["inventory"] = range(9, 35);
				$this->zones["held"] = range(36, 44);
				break;
			case WINDOW_WORKBENCH:
				$this->zones["output"] = 0;
				$this->zones["craft"] = range(1, 9);
				$this->zones["inventory"] = range(10, 36);
				$this->zones["held"] = range(37, 45);
				break;
			case WINDOW_CHEST:
				break;
			case WINDOW_FURNACE:
				$this->zones["output"] = 2;
				$this->zones["fuel"] = 1;
				$this->zones["input"] = 0;
				$this->zones["inventory"] = range(3, 29);
				$this->zones["held"] = range(30, 38);
				break;
			case WINDOW_DISPENSER:
				$this->zones["input"] = range(0, 8);
				$this->zones["inventory"] = range(9, 35);
				$this->zones["held"] = range(36, 44);
				break;
			case WINDOW_ENCHANTMENT_TABLE:
				$this->zones["input"] = 0;
				$this->zones["output"] = 0;
				$this->zones["inventory"] = range(1, 27);
				$this->zones["held"] = range(28, 36);
				break;
			case WINDOW_BREWING:
			
				break;
		
		}
	
	}
}

?>