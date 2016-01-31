<?php

require_once(SHAREPATH . 'libraries/aws/aws-autoloader.php'); // AWS php library

/**
 * base class with member properties and methods
 * 
 * @author PROOVEBIO\bmarkham
 */
//AWS credentials library
use Aws\Common\Credentials\Credentials;
//AWS S3 libraies
use Aws\S3\S3Client;
use Aws\S3\Enum\CannedAcl;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
//AWS SES libraries
use Aws\Ses\SesClient;
//AWS SQS libraries
use Aws\Sqs\SqsClient;

/**
 * prooveAws
 * 
 * base class for AWS
 * @author PROOVEBIO\bmarkham
 * @updated bmarkham 08/28/2014
 *
 */
class prooveAWS
{
	private $credentials = null;
	
	/*
	 * initialize base class
	 */
	public function __construct()
	{
		$this->credentials();
	}
	
	/*
	 * credentials
	 * 
	 * create credentials for loging into aws
	 */
	private function credentials()
	{
		if ($string = file_get_contents('../environment.json'))
		{
			$json = json_decode($string,TRUE);
		}

		$this->credentials = new Credentials($json[0]['AWS_KEY'], $json[1]['AWS_SECRET_KEY']);
	}
	
	/*
	 * Instantiate the S3 client with your AWS credentials
	 */
	public function S3Client()
	{
		if (!$this->credentials)
		{
			$this->credentials();
		}
		$s3Client = S3Client::factory(array('credentials' => $this->credentials));
		return  $s3Client;
	}
	
	/*
	 * Instantiate the Ses client with AWS credentials
	 */
	public function SESClient() {
		if (!$this->credentials) {
			$this->credentials();
		}
		$sesClient = SesClient::factory(array('credentials' => $this->credentials, 'region'=>'us-west-2'));
		return  $sesClient;
	}
	
	/*
	 * Instantiate the Sqs client with AWS credentials
	 */
	public function SQSClient()
	{
		if (!$this->credentials)
		{
			$this->credentials();
		}
		$sqsClient = SqsClient::factory(array('credentials' => $this->credentials, 'region'=>'us-west-2'));
		return  $sqsClient;
	}
}

/**
 * extend base AWS class
 *
 * @author PROOVEBIO\bmarkham
 *
 * for credential configuration see: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/credentials.html#credential-profiles
 */
class Ses extends prooveAWS {
	var $client=null;
	
	/*
	 * __construct
	*
	* initialize defaults for class
	*/
	public function __construct() {
		try {
			$this->client = $this->SesClient();
		} catch (\Aws\Ses\Exception\SesException $e) {
			return $e->getMessage();
		}
	}
	
	public function getVerifiedEmails() {
		$result = $this->client->listIdentities(array('MaxItems' => 100));
		return $result;
	}
	/**
	 * sendEmail
	*
	* send email using Ses
	* http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.Ses.SesClient.html#_sendEmail
	*/
	public function sendEmail($msg='Error',$toemail=null,$msgheader='Error') {
		$result = null;
		try {
			$result = $this->client->sendEmail(array(
		    // Source is required
		    'Source' => 'scanner@proovebio.com',
		    // Destination is required
		    'Destination' => array(
		        'ToAddresses' => array($toemail)
		    ),
		    // Message is required
		    'Message' => array(
		        // Subject is required
		        'Subject' => array(
		            // Data is required
		            'Data' => $msgheader,
		            'Charset' => 'UTF8',
		        ),
		        // Body is required
		        'Body' => array(
		            'Text' => array(
		                // Data is required
		                'Data' => $msg,
		                'Charset' => 'UTF8',
		            ),
		            'Html' => array(
		                // Data is required
		                'Data' => $msg,
		                'Charset' => 'UTF8',
		            ),
		        ),
		    )
		));
			return $result;
		} catch (\Aws\S3\Exception\S3Exception $e) {
			echo $e->getMessage();
		}
	}
}
