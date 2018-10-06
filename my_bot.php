<?php
	header("Content-Type: text/html;charset=UTF-8");
	date_default_timezone_set('Europe/London');
	include 'b0tacc3s_guard.php';
	include 'f0nct1ons.php';

	$content = file_get_contents("php://input"); //To get the JSON data from Telegram
	//file_put_contents("./debug/contentTG-".date('d-m-Y-His').".json",$content); //To store the JSON for debug
	$update = json_decode($content, true); //To get an array of the previous JSON

	if(!isset($update) or $update === null or $update === '') {
		exit;
	}

	$tg_id = array(1234,5678); //List of Telegram ID you want to blacklist
	if (isset($update['message']['from']['id']) and in_array($update['message']['from']['id'],$tg_id)) {
		exit; //Blacklist them directly
	}

	if(isset($update["inline_query"]["from"]["id"]) and in_array($update["inline_query"]["from"]["id"],$tg_id)) {
		exit; //and now for inline search as well
	}

	if(isset($update["message"]["location"])) { //This is a location sharing message from Telegram
		apiRequest("sendChatAction",array('chat_id' => $chatId, 'action' => 'find_location')); //sending a "picking location..." to user on top of the client. You known, the "typing..." thing
		$message = $update["message"];
		$chatId = $message["chat"]["id"];
		$usernameTG = $update["message"]["chat"]["username"];
		$message_id = $message['message_id'];
		$db = init_acces();
		$latitudeTG = $message["location"]["latitude"];
		$longitudeTG = $message["location"]["longitude"];

		//Do some things with the location shared by your user
		//You can, for instance, lookup for real time informations nearby
		$db->close();
		exit;
	}

	if(isset($update["message"]["entities"]) and (strcmp($update["message"]["entities"][0]["type"],"bot_command") !== 0) and (strcmp($update["message"]["entities"][0]["type"],"hashtag") !== 0)) {
		//Here, you can lookup for URL in message.
		$message = $update["message"];
		$chatId = $message["chat"]["id"];
		$FromUserTG = $update["message"]["from"]["username"];
		$message_id = $message['message_id'];
		$index_entities = 0;
		$nb_entities = count($update["message"]["entities"]);
		foreach($update["message"]["entities"] as $entities) {
			if(strcmp($update["message"]["entities"][$index_entities]["type"],"url") === 0) break;
			if($index_entities == $nb_entities) {
				exit;
			}
			++$index_entities;
		}
		// repérer le nom de l'hôte dans l'URL '/(https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/'
		preg_match('$\b(https?|ftp|file)://[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]$i',$message["text"], $matches);
		if(isset($matches[0])) {
			$host = $matches[0];
		} else {
			apiRequest("sendMessage", array('chat_id' => $chatId, "text" => "Not a link..."));
			exit;
		}
		if (strpos("https://my.link",$host) !== FALSE or strpos("http://my.link",$host) !== FALSE){
			apiRequest("sendMessage", array('chat_id' => $chatId,
			"text" => "Not my link..."));
		} else {
			//Do things with your link here
		}
		exit;
	}

	if(isset($update["message"]["photo"])) { //If you would like to catch pic and do something on
			$chatId = $update["message"]["chat"]["id"];
			$FromUserTG = $update["message"]["from"]["username"];
		if (isset($update["message"]["caption"])) { //If the pic has a caption
			//Do stuff here on caption (recognition of words for instance)
		} else {
			apiRequest("sendMessage", array('chat_id' => $chatId, "text" => "No caption on this pic..."));
			exit;
		}
	}

	//Big part of code : treat message of your user
	if(isset($update["message"]["chat"]) or isset($update["edited_message"]["chat"])) {
		if(isset($update["edited_message"])) {
			$message = $update["edited_message"]; //Your user can edit the previous message. Here, you can handle this as a new message with no pain
		} else {
			$message = $update["message"];
		}
		$chatId = $message["chat"]["id"];
		$FromUserTG = ($message["from"]["username"]) ?? null;
		$usernameTG = ($message["chat"]["username"]) ?? null;
		$message_id = $message['message_id'];
		$identite_TG = (isset($message["from"]["last_name"])) ? $message["from"]["first_name"]." ".$message["from"]["last_name"] : $message["from"]["first_name"];

		apiRequest("sendChatAction",array('chat_id' => $chatId,'action' => 'typing')); //Let send a "typing.." now to indicate that we are decoding the message

		if(isset($message["reply_to_message"])) { //a reply message

			if(!isset($message["reply_to_message"]["venue"]["title"])) {
				//this is just to show some kind of reply messages we can get
			}
		}

		if(!isset($message["text"]) and (isset($message["voice"]) or isset($message["sticker"]) or isset($message["document"]) or isset($message["contact"]))) {
			apiRequest("sendSticker",array('chat_id' => $chatId,
			'sticker' => "BQADBAADtQIAAsm2ogb39gn8uLmdwQI", //replace with your Sticker ID. To get it, remove comment on top of this code and read in the JSON.
			'reply_markup' => array('remove_keyboard' => true)));
			exit;
		}
		if(!isset($message["text"])) {
			//In case Telegram added a new type of message and we are not treating it already
			file_put_contents("./debug/messageNOK-".date('d-m-Y-His').".json",$content);
			exit;
		}

		$command_user_to_bot = $db->real_escape_string(str_replace("’","'",$message["text"])); //The str_replace is to change the ' of iOS...
		$commandSentByUser = explode(" ",$command_user_to_bot);
		apiRequest("sendChatAction",array('chat_id' => $chatId,'action' => 'typing'));
		switch($commandSentByUser[0]) {
			case "/start":
				apiRequest("sendMessage",array('chat_id' => $chatId,
					'parse_mode' => 'HTML',
					'text' => "Hello ".$FromUserTG."! How can I help you today?",
					'reply_markup' => array('inline_keyboard' => array(
						array(['text' => " Test 1 ", 'callback_data' => "1"],['text' => " Test 2 ", 'callback_data' => "2"]),
						array(['text' => " Test 3 ", 'callback_data' => "3"],['text' => " Test 4 ", 'callback_data' => "4"])))));
						//You will get this kind of inline_keyboard under your message above [ Test 1 ] [ Test 2 ]
						//    an array add a new line and a couple of [] add a button        [ Test 3 ] [ Test 4 ]
				break;
			default:
				//In case this is not a / command but just a message
				//str_cmp words for instance
		}
		$db->close();
		exit;
	}

	if(isset($update["callback_query"])) { //when user click on inline_keyboard
		$chatId = $update["callback_query"]["message"]["chat"]["id"];
		$MessageId = $update["callback_query"]["message"]["message_id"];
		$callback_query_Id = $update["callback_query"]["id"];
		$username_callback = $update["callback_query"]["from"]["username"];
		$username = $update["callback_query"]["message"]["chat"]["username"];
		$reponse_user_explode = explode("@",$update["callback_query"]["data"]); //to separate things in your callback data for instance

		switch($update["callback_query"]["data"]) {
			case '1':
				apiRequest("editMessageText",array('chat_id' => CHAT_ROOT,
					'message_id' => $MessageId,
					'parse_mode' => 'HTML',
					'text' => "<b>New text message</b>"));
				break;
			case '2':
				break;
			case '3':
				break;
			case '4':
				break;
			default: //You can add more data in your callback to identify some specific use cases

		}
		exit;
	}

	if(isset($update["inline_query"])) {
		mb_internal_encoding("UTF-8");
		$QueryId = $update["inline_query"]["id"];
		$usernameTG = $update["inline_query"]["from"]["username"];
		$chatId = $update["inline_query"]["from"]["id"];
		$OffsetQuery = $update["inline_query"]["offset"];

		if(isset($TexteQuery) and $TexteQuery != "") { //Treat this only if user types some letters. Avoid empty request...
			$db = init_acces();
			$TexteQuery = $db->real_escape_string(str_replace("’","'",$update["inline_query"]["query"]));
			$next_offset = intval($OffsetQuery)+30;
			$request_some_stuff = "SELECT some, stuff FROM your_database WHERE this LIKE ? ORDER BY that ASC LIMIT 30 OFFSET ".intval($OffsetQuery);

			$result_some_stuff = $db->prepare($request_some_stuff);
			$param = $TexteQuery . "%";
			$result_some_stuff->bind_param("s", $param);
			$result_some_stuff->execute();
			$result = $result_some_stuff->get_result();

			$num_rows = $result->num_rows;
			switch($num_rows) {
				case 0:
					if(intval($OffsetQuery) > 0) {
						apiRequest("answerInlineQuery",array('inline_query_id' => $QueryId,
							'results' => array(array('type' => 'article', 'id' => bin2hex(openssl_random_pseudo_bytes(8)),
								'title' => "No more entry in databse...",
								'input_message_content' => array('message_text' => "End of list"),
								'description' => "End of list",
								'thumb_url' => "https://my.link/awesome_image.png",
								)),
							'cache_time' => 5
							));
					} else {
						apiRequest("answerInlineQuery",array('inline_query_id' => $QueryId,
							'results' => array(array('type' => 'article', 'id' => bin2hex(openssl_random_pseudo_bytes(8)),
								'title' => "No entry...",
								'input_message_content' => array('message_text' => "Try with another word"),
								'description' => "Try with another word",
								'thumb_url' => "https://my.link/awesome_image.png",
								)),
							'cache_time' => 5
							));
					}
					break;
				case 1:
					$my_result = $result->fetch_row();
					$img = ($my_result[3] == 'null') ? "https://my.link/default-image.png" : $my_result[3];
					apiRequest("answerInlineQuery",array('inline_query_id' => $QueryId, 'results' => array(array('type' => 'venue', 'id' => strval($my_result[5]),
					'latitude' => floatval($my_result[1]),
					'longitude' => floatval($my_result[2]),
					'title' => $my_result[0],
					'address' => 'Some address text here',
					'thumb_url' => $img)), 'cache_time' => 45, 'is_personal' => true));
					break;
				default:
					$nb_iterations = 0;
					$ListeToDisplay = array();
					$ListeResuls = array();
					while($my_result = $result->fetch_row()) {
						$imgPortail = ($my_result[3] == 'null') ? "https://my.link/my-image.png" : $my_result[3];
						//$IndexListePortails += 1;
						$ListeToDisplay = array('type' => 'venue', 'id' => strval($my_result[5]),
							'latitude' => floatval($my_result[1]),
							'longitude' => floatval($my_result[2]),
							'title' => $my_result[0],
							'address' => 'Some address text here',
							'thumb_url' => $imgPortail);
						$ListeResuls[$nb_iterations] = $ListeToDisplay;
						++$nb_iterations;
					}
					apiRequest("answerInlineQuery",array('inline_query_id' => $QueryId,
					 	'results' => json_encode($ListeResuls),
						'cache_time' => 45,
						'is_personal' => true,
						'next_offset' => strval($next_offset)));
					break;
			}
			$db->close();
		}
	}
?>
