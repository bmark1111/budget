<?php

require_once('aws.php');

/**
 * extend base AWS class
 * 
 * @author PROOVEBIO\dcarroll
 * 
 * for credential configuration see: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/credentials.html#credential-profiles
 */
class S3 extends prooveAWS
{
	var $client=null;
	
	/*
	 * __construct
	 * 
	 * initialize defaults for class
	 */
	public function __construct() {	
		try {
			$this->client = $this->S3Client();
		} catch (\Aws\S3\Exception\S3Exception $e) {
			// The bucket couldn't be created
			return $e->getMessage();
		}
	}
	
	/*
	 * listBuckets
	 * 
	 * list buckets in S3
	 */
	public function listBuckets()
	{
		$result = null;
		try {
			$result = $this->client->listBuckets();
			return $result;
		}
		catch (\Aws\S3\Exception\S3Exception $e)
		{
			echo $e->getMessage();
		}
	}
	
	/**
	 * existBucket
	 * 
	 * @param string $bucket
	 */
	private function existBucket($bucket) {
		$result = $this->client->doesBucketExist($bucket);
		return $result;
	}
	
	/*
	 * listObjects
	 * 
	 * list object in S3 bucket
	 */
	public function listObjects($bucket, $delimiter = false)
	{
		if ($this->existBucket($bucket)) {
			try {
				if ($subdir)
				{
					$iterator = $this->client->getIterator('ListObjects', array( 'Bucket' => $bucket,'Delimiter' => $delimiter), array('sort_results'=> true));
				} else {
					$iterator = $this->client->getIterator('ListObjects', array( 'Bucket' => $bucket));
				}
				return $iterator;
			} catch (\Aws\S3\Exception\S3Exception $e) {
				// The bucket couldn't be created
				echo $e->getMessage();
			}
		} else {
			return false;
		}
	}
	
	public function downloadObject($sourceBucket, $sourceFile, $destination) {
		$fh = fopen($destination.'/'.$sourceFile, 'w+');
		$this->client->get_object($sourceBucket, $sourceFile, array('fileDownload' => $fh));
	}
	
	public function uploadObject($bucket, $key, $body)
	{
		$result = $this->client->putObject(array('Bucket' => $bucket,
												'Key' => $key,
												'Body' => $body,
												'ACL' => 'public-read'));
		return $result;
	}

	//$url = getSignedURL("http://abcdefg.cloudfront.net/test.jpg", 60);
	public function getSignedURL($resource, $timeout)
	{
		//This comes from key pair you generated for cloudfront
		$keyPairId = "APKAINNUHCWCSQGQ5XHQ"; // YOUR_CLOUDFRONT_KEY_PAIR_ID

		$expires = time() + $timeout; //Time out in seconds
		$json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';		

		//Read Cloudfront Private Key Pair
		$fp=fopen("/var/www/cloudfront-dev-key.pem","r"); 
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
		$url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$keyPairId;

		return $url;
	}

}