<?php

class ftpUtils
{
	public $error = FALSE;

	function __construct()
	{
		$this->server = '63.240.71.180';
		$this->port = '5516';
		$this->user = 'PlatMD@2495';
		$this->pw = 'Lpi$357#2503!';
/*
IP:       63.240.71.180
Port:     5516
User:     PlatMD@2495
Pass:     <separate email>
Dir.:     /LIVE/Results/
          /LIVE/Orders/
          /TEST/Results/
          /TEST/Orders/    */
	}

	function connect()
	{
		$ftpstream = ssh2_connect($this->server, $this->port) or die("Couldn't connect to $this->server");

		if(!$ftpstream)
		{
			$this->error = "can't connect to ftp, check host address";
			return FALSE;
		}
		else 
		{
			$login = ssh2_auth_password($ftpstream, $this->user, $this->pw);
			// check connection
			if (!($ftpstream && $login))
			{
				$this->error = "wrong user/pass";
				return FALSE;
			}
			else 
			{
				$this->sftp = ssh2_sftp($ftpstream);
				return true;
			}
		}
	}
	
	function testconnect()
	{
		$ftpstream = ftp_connect($this->server, $this->port);
		if(!$ftpstream)
		{
			die("can't connect to ftp, check host address");
		}
		else 
		{
			$login = ftp_login($ftpstream, $this->user, $this->pw);
			if(empty($login))
			{
				die("wrong user/pass");
			}
			else 
			{
				$this->ftpstream = $ftpstream;
				ftp_pasv($this->ftpstream, TRUE);
				return $ftpstream;
			}
		}
	}
	
	function close()
	{
		ftp_close($this->ftpstream);
	}
	
	function isFileExist($file)
	{
		$res = ftp_size($this->ftpstream, $file);
		if ($res != -1)
		{
			return $res;
		}
		else
		{
			return FALSE;
		}
	}
	
	function isDirExist($dir)
	{
		return ftp_chdir($this->ftpstream, $dir);
	}
	
	function mkDir($dir)
	{
		return ftp_mkdir($this->ftpstream, $dir);
	}
	
	function delete($file)
	{
		return ftp_delete($this->ftpstream, $file);
	}
	
	function rename($oldFile, $newFile)
	{
		return ftp_rename($this->ftpstream, $oldFile, $newFile);
	}
	
	function upload($targetFile, $sourceFile)
	{
		$stream = fopen("ssh2.sftp://". $this->sftp . $targetFile, 'w');

		$data_to_send = file_get_contents($sourceFile);

		fwrite($stream, $data_to_send);
		fclose($stream);
		return true;
	}
	
	function testupload($targetFile, $sourceFile)
	{
		//ftp_pasv($this->ftpstream, TRUE);
		return ftp_put($this->ftpstream, $sourceFile, $targetFile, FTP_BINARY);
	}
	
	function getsize($targetFile)
	{
		// get the size of $file
		$res = ftp_size($this->ftpstream, $targetFile);
		
		if ($res != -1) {
		    return $res;
		} else {
		    return FALSE;
		}
	}
	
	function chmod($targetFile, $mode)
	{
		return ftp_site($this->ftpstream,"CHMOD $mode $targetFile");
	}
	
	function get($targetFile, $sourceFile)
	{
		if ($this->sftp) {
			$data = file_get_contents("ssh2.sftp://$this->sftp". $targetFile);
			return file_put_contents($sourceFile, $data);
		}

		return ftp_get($this->ftpstream, $targetFile, $sourceFile, FTP_BINARY);
	}
	
	function rmDir($dir)
	{
		return ftp_rmdir($this->ftpstream, $dir);
	}
	
	function cl_isDirExist($dir)
	{
		$this->connect();
		return $this->isDirExist($dir);
		$this->close();
	}
	
	function listDir($dir)
	{
		if ($this->sftp) {
			$handle = opendir("ssh2.sftp://$this->sftp/$dir");
			while (false !== ($file = readdir($handle)))
			{
			    if ($file != '.' && $file != '..') {
			        $files[] = $file;
			    }
			}

			return $files;
		}

		return ftp_nlist($this->ftpstream, $dir);
	}
	
	function rawListDir($dir)
	{
		return ftp_rawlist($this->ftpstream, $dir);
	}
	
	function listDirExt($dir, $ext)
	{
		$files = ftp_nlist($this->ftpstream, $dir);
		if(empty($files))
			return FALSE;
			
		foreach($files as $key=>$val)
		{
			$fileName = str_replace($dir, '', $val);
			$fileName = str_replace('/', '', $fileName);
			if($fileName != "." && $fileName != ".." && preg_match("/\.$ext/i", $fileName))
			{
				$retval[] = $fileName;
			}
		}
		
		if(empty($retval))
			return FALSE;
			
		sort($retval);
		return $retval;
	}
	
	function listSubs($dir, $reset=FALSE)
	{
		static $allDir = array();
		static $allFiles = array();
		if(!empty($reset))
		{
			$allDir = array();
			$allFiles = array();
		}
		$list = $this->listDir($dir);
		$count = count($list);
		if($count > 0)
		{
			for($i=0;$i<$count;$i++)
			{
				if($this->ftp_is_dir($list[$i]))
				{
					$allDir[] = $list[$i];
					$this->listSubs($list[$i]);
				}
				else 
				{
					//echo 'deleting file: '.$list[$i]."\n";
					$allFiles[] = $list[$i];
				}
			}
			
			$retval['dir'] = $allDir;
			$retval['files'] = $allFiles;
			unset($allDir, $allFiles);
			return $retval;
		}
		// FALSE
		return FALSE;
	}
	
	function ftp_is_dir($dir) 
	{
		if(ftp_chdir($this->ftpstream, $dir))
		{
			ftp_chdir($this->ftpstream, '..');
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function listSubs2($dir)
	{
		static $allDir = array();
		static $allFiles = array();

		$list = $this->listDir($dir);
		$count = count($list);
		if($count > 0)
		{
			for($i=0;$i<$count;$i++)
			{
				if($this->ftp_is_dir($list[$i]))
				{
					$allDir[] = $list[$i];
					$this->listSubs($list[$i]);
				}
				else 
				{
					$allFiles[] = $list[$i];
				}
			}
			$retval['dir'] = $allDir;
			$retval['files'] = $allFiles;
			return $retval;
		}
		// FALSE
		return FALSE;
	}
	
	function ftpRecursiveFileListing($path)
	{
		static $allFiles = array();
	    $contents = ftp_nlist($this->ftpstream, $path);
		foreach($contents as $currentFile)
		{
			// assuming its a folder if there's no dot in the name
			if (strpos($currentFile, '.') === FALSE)
			{
				$this->ftpRecursiveFileListing($currentFile);
			$allFiles[$path][] = substr($currentFile, strlen($path) + 1);
		}	}
		
		return $allFiles;
	}
    
	function testlistDirExt($dir, $ext)
	{
		$files = ftp_nlist($this->ftpstream, $dir);
		//echo "<pre>";print_r($files);exit;
		if(empty($files))
			return FALSE;
			
		foreach($files as $key=>$val)
		{
			$fileName = str_replace($dir, '', $val);
			$fileName = str_replace('/', '', $fileName);
			if($fileName != "." && $fileName != ".." && preg_match("/\.$ext/i", $fileName))
			{
				$retval[] = $fileName;
			}
			echo $fileName;
		}
		//echo "<pre>";print_r($files);exit;
		if(empty($retval))
			return FALSE;
			
		sort($retval);
		return $retval;
	}
	
	function multipleUpload($targetDir, $sourceDir, $fileArr)
	{
		// do not use this function 
		// Initiate the Upload
		$stream = $this->connect();
		$ret = ftp_nb_put($stream, "test.remote", "test.local", FTP_BINARY);
		while ($ret == FTP_MOREDATA)
		{
		   // Do whatever you want
			echo ".";
			// Continue uploading...
			$ret = ftp_nb_continue($my_connection);
		}
		if ($ret != FTP_FINISHED)
		{
			echo "There was an error uploading the file...";
			$this->close();
			exit(1);
		}
		$this->close();
	}
	
	
	
}
