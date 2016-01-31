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
	 * @param string $bucket bucket name
	 */
	public function existBucket($bucket) {
		$result = $this->client->doesBucketExist($bucket);
		return $result;
	}
	
	/*
	 * existObject
	 * 
	 * does an object exist in specific bucket
	 */
	public function existObject($bucket,$filename) {
		$result = $this->client->doesObjectExist($bucket,$filename);
		return $result;
	}
	
	/*
	 * createBucket
	 * 
	 * @param string $bucket bucket name
	 */
	public function createBucket($bucket,$region='us-west-2') {
		return $this->client->createBucket(array('Bucket' => $bucket,'LocationConstraint'=>$region));
	}
	
	/*
	 * listObjects
	 * 
	 * list object in S3 bucket
	 */
	public function listObjects($bucket, $upload)
	{
		if ($this->existBucket($bucket)) {
			try {
				$iterator = $this->client->getIterator('ListObjects', array( 'Bucket' => $bucket,'Delimiter' => $upload), array('sort_results'=> true));
				return $iterator;
			} catch (\Aws\S3\Exception\S3Exception $e) {
				// The bucket couldn't be created
				echo $e->getMessage();
			}
		} else {
			return false;
		}
	}
		
	/*
	 * createFile
	 * 
	 * create a file and write data to it
	 * 
	 * result parts: Expiration, ServerSideEncryption, ETag, VersionId, RequestId, ObjectURL
	 */
	public function createFile($bucket,$filename,$data='') {
		$result = $this->client->putObject(array('Bucket'=>$bucket,'Key'=>$filename,'Body'=>$data));
		return $result;
	}
	
	/*
	 * _deleteObject
	 * 
	 * delete an object from bucket
	 */
	private function _deleteObject($bucket,$filename) {
		$result = $this->client->deleteObject(array('Bucket'=>$bucket,'Key'=>$filename));
		return $result;
	}
	
	/*
	 * deleteObjects
	 * 
	 * delete several objects from bucket
	 */
	public function deleteObjects($bucket,$filenames) {
		if (is_array($filenames)) {
			$result = $this->client->deleteObjects(array(
				'Bucket'=>$bucket,
				'Objects'=>array_map(function($filename) {
					return array(
						'Key' => $filename
					);
				}, $filenames)
			));
				return $result;
		} else {
			return $this->_deleteObject($bucket,$filenames);
		}
	}
	
	/*
	 * uploadFile
	 */
	public function uploadFile($bucket,$filename,$pathtofile) {
		$result = $this->client->putObject(array('Bucket'=>$bucket,'Key'=>$filename,'SourceFile' => $pathtofile));
		return $result;
	}
	
	/*
	 * downloadObject
	 * 
	 * download an object from S3
	 * 
	 * result parts: Body
	 */
	public function downloadObject($bucket,$filename,$saveas=null) {
		if ($saveas) {
			$result = $this->client->getObject(array('Bucket'=>$bucket,'Key'=>$filename,'SaveAs'=>$saveas));
		} else {
			$result = $this->client->getObject(array('Bucket'=>$bucket,'Key'=>$filename));
		}
		return $result;
	}
}