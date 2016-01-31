<?php

class salesforce
{
	public $instance_url = FALSE;

	public function __construct()
	{
	}

	public function login()
	{
		$token_url = LOGIN_URI . "/services/oauth2/token";

		$params = "&grant_type=password"
				. "&client_id=" . CLIENT_ID
				. "&client_secret=" . CLIENT_SECRET
				. "&username=" . USERNAME
				. "&password=" . PASSWORD . SECURITY_TOKEN;

		$curl = curl_init($token_url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

		$json_response = curl_exec($curl);

		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ( $status != 200 )
		{
			die("Error: call to token URL $token_url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}

		curl_close($curl);

		$response = json_decode($json_response, true);

		if (!isset($response['access_token']) || $response['access_token'] == "")
		{
			die("Error - access token missing from response!");
		}

		if (!isset($response['instance_url']) || $response['instance_url'] == "")
		{
			die("Error - instance URL missing from response!");
		}

		$this->access_token = $response['access_token'];
		$this->instance_url = $response['instance_url'];
	}
	
	public function get($url)
	{
		$curl = curl_init($this->instance_url . '/services/data/v29.0' . $url);

		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth " . $this->access_token));

		$json_response = curl_exec($curl);

		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ( $status != 200 )
		{
			die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}

//		echo "HTTP status: $status\n\r";

		curl_close($curl);

		return json_decode($json_response, true);
	}

	public function put($url, $content)
	{
		$curl = curl_init($this->instance_url . '/services/data/v29.0' . $url);

		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth " . $this->access_token, "Content-type: application/json"));
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

		curl_exec($curl);

		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ( $status != 204 )
		{
			die("Error: call to URL $url failed with status $status, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}

//		echo "HTTP status: $status";

		curl_close($curl);
	}

}

?>