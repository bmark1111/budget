<?php

require_once('aws.php');

/**
 * extend base AWS class
 * 
 * @author PROOVEBIO\bmarkham
 * 
 * for credential configuration see: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/credentials.html#credential-profiles
 */
class SQS extends prooveAWS
{
	var $client=null;
	
	/*
	 * __construct
	 * 
	 * initialize defaults for class
	 */
	public function __construct() {	
		try
		{
			$this->client = $this->SQSClient();
		}
		catch (\Aws\Sqs\Exception\SqsException $e)
		{
			// AWS SQS could not be reached
			return $e->getMessage();
		}
	}
	/*
	 * Get Queue URL
	 * 
	 */
	public function receiveMessage()
	{
		try {
			$result = $this->client->receiveMessage(array(
				// QueueUrl is required
				'QueueUrl' => 'https://sqs.us-west-2.amazonaws.com/895759819391/reporterresults-dev'
			));
			return $result;
		}
		catch (\Aws\Sqs\Exception\SqsException $e)
		{
			echo $e->getMessage();
		}
	}
	/*
	 * Get Queue URL
	 * 
	 */
	public function getQueueUrl()
	{
		try {
			$result = $this->client->getQueueUrl(array(
				'QueueName' => 'reportresults-dev',
				'QueueOwnerAWSAccountId' => '',
			));
			return $result;
		}
		catch (\Aws\Sqs\Exception\SqsException $e)
		{
			echo $e->getMessage();
		}
	}

}