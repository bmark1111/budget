<?php

class S33
{
	//$url = getSignedURL("http://abcdefg.cloudfront.net/test.jpg", 60);
	public function getSignedURL($resource, $timeout)
	{
		//This comes from key pair you generated for cloudfront
//		$keyPairId = "APKAINNUHCWCSQGQ5XHQ"; // YOUR_CLOUDFRONT_KEY_PAIR_ID
//		$keyPairId = "AKIAIAZJEUXCEUAFLXVA";
		$keyPairId = "AKIAIDPIEBVDF4BDDR3Q";

		$expires = time() + $timeout; //Time out in seconds
		$json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';		

		//Read Cloudfront Private Key Pair
//		$fp=fopen("/var/www/cloudfront-dev-key.pem","r"); 
		$fp=fopen("/var/www/dev.pem","r"); 
		$priv_key=fread($fp,8192);
		fclose($fp); 

		//Create the private key
		$key = openssl_get_privatekey($priv_key);
		if(!$key)
		{
			echo "<p>Failed to load private key!</p>";
			return;
		}

		//Sign the policy with the private key
		if(!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1))
		{
			echo '<p>Failed to sign policy: '.openssl_error_string().'</p>';
			return;
		}

		//Create url safe signed policy
		$base64_signed_policy = base64_encode($signed_policy);
		$signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

		//Construct the URL
//		$url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$keyPairId;
		$url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&AWSAccessKeyId'.$keyPairId;
		return $url;
	}
}