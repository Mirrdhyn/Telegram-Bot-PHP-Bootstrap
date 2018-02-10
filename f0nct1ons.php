<?php
function exec_curl_request($handle,$url) {
	$response = curl_exec($handle);

	if ($response === false) {
	$errno = curl_errno($handle);
	$error = curl_error($handle);
	file_get_contents(API_URL."sendmessage?chat_id=".CHAT_ROOT."&parse_mode=HTML&text=<i>Erreur Curl</i> ".$errno." ".$error."");
	error_log("Curl returned error $errno: $error\n");
	curl_close($handle);
	return false;
	}

	$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
	curl_close($handle);

	if ($http_code >= 500) {
	// do not wat to DDOS server if something goes wrong
	file_get_contents(API_URL."sendmessage?chat_id=".CHAT_ROOT."&parse_mode=HTML&text=<i>HTTP Erreur Code </i>".$http_code);
	sleep(10);
	return false;
	} else if ($http_code != 200) {
	file_put_contents("./debug/PHP-Error-".date('d-m-Y-His').".json",$url);
	$response = json_decode($response, true);
	error_log("My Bot : Request has failed with error {$response['error_code']}: {$response['description']}\n");
	if ($http_code == 401) {
		file_get_contents(API_URL."sendmessage?chat_id=".CHAT_ROOT."&parse_mode=HTML&text=<i>Code 401.</i>");
		throw new Exception('Invalid access token provided');
	}
	return false;
	} else {
	$response = json_decode($response, true);
	if (isset($response['description'])) {
		file_get_contents(API_URL."sendmessage?chat_id=".CHAT_ROOT."&parse_mode=HTML&text=<i>Requete OK.</i>");
		error_log("Request was successfull: {$response['description']}\n");
	}
	$response = $response['result'];
	}

	return $response;
}

function apiRequest($method, $parameters) {
	if (!is_string($method)) {
	error_log("Method name must be a string\n");
	return false;
	}

	if (!$parameters) {
	$parameters = array();
	} else if (!is_array($parameters)) {
	error_log("Parameters must be an array\n");
	return false;
	}

	foreach ($parameters as $key => &$val) {
	// encoding to JSON array parameters, for example reply_markup
	if (!is_numeric($val) && !is_string($val)) {
		$val = json_encode($val);
	}
	}
	$url = API_URL.$method.'?'.http_build_query($parameters);

	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($handle, CURLOPT_TIMEOUT, 60);

	return exec_curl_request($handle,$url);
}

function cURL($url) {
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL,$url);
	// Execute
	$result=curl_exec($ch);
	// Closing
	curl_close($ch);
	return $result;
}

?>
