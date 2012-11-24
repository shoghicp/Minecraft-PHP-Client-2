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

class QuestionFAQ{
	protected $client, $event, $question, $response, $state;
	
	
	function __construct($client){
		$this->client = $client;
		$this->event = $this->client->event("onChatHandler", "handler", $this);
		$this->question = array();
		$this->response = array();
		$this->state = array();
		console("[INFO] [QuestionFAQ] Loaded");
	}
	
	public function addQuestionTrigger($txt){
		$this->question[] = strtolower($txt);
	}
	
	public function addResponse($fixedWords, $optionalWords, $noWords, $question, $response, $call = false){
		$this->response[] = array(explode(",",strtolower($fixedWords)), explode(",",strtolower($optionalWords)), explode(",",strtolower($noWords)), $question, $response, $call);
	}
	
	public function toggleState($user){
		$this->state[$user] = (isset($this->state[$user]) and $this->state[$user] == true) ? false:true;
	}

	public function handler($message){
		if((isset($this->state[$message["owner"]]) and $this->state[$message["owner"]] === false) or !isset($this->state[$message["owner"]])){
			$owner = $message["owner"];
			$type = $message["type"];
			$message = " ".trim(strtolower($message["message"]))." ";
			if($type == "private"){
				$question = true;
			}else{
				$question = false;
				foreach($this->question as $q){
					if($q != "" and strpos($message, $q) !== false){
						$question = true;
						console("[DEBUG] [QuestionFAQ] Detected question", true, true, 2);
						break;
					}
				}
			}

			
			$best = array();
			foreach($this->response as $i => $data){
				if(($data[3] == true and $question == true) or $data[3] == false){
					$points = 0;
					if(count($data[0]) > 1 or $data[0][0] != ""){
						$continue = false;
						foreach($data[0] as $word){
							if($word != "" and strpos($message, $word) !== false){
								$continue = true;
								++$points;
							}
						}
						if($continue == false){
							continue;
						}
					}
					if(count($data[1]) > 1 or $data[1][0] != ""){
						$continue = false;
						foreach($data[1] as $word){
							if($word != "" and strpos($message, $word) !== false){
								$points += 2;
								$continue = true;
							}
						}
						if($continue == false){
							continue;
						}
					}
					if(count($data[2]) > 1 or $data[2][0] != ""){
						$continue = true;
						foreach($data[2] as $word){
							if($word != "" and strpos($message, $word) !== false){
								$continue = false;
								break;
							}
						}
						if($continue == false){
							continue;
						}
					}
					if($points == 0){
						continue;
					}
					$best[$i] = $points;
				}
			}
			if(count($best) > 0){
				arsort($best);
				$p = reset($best);
				$best = key($best);
				console("[DEBUG] [QuestionFAQ] Chosen response with punctuation ".$p." over ".count($best)." responses", true, true, 2);
				if($this->response[$best][5] !== false){
					call_user_func($this->response[$best][4], $owner, $this->response[$best][5], $this->client);
				}else{
					$this->client->say($this->response[$best][4], $owner);
				}
			}
		}	
	}
	
	public function stop(){
		$this->client->deleteEvent("onChatHandler", $this->event);	
	}

}