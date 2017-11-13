<?php
defined('ABSPATH') or die("Cannot access pages directly.");
class cart2cartftpUpload {
	var $conn;
	var $messages;
	var $messageType = 'success';
	var $dir;
	var $token;
	var $localpath;

	public function init($host,$user,$pass,$dir,$token){
		$this->dir = $dir;
		$this->token = $token;
		$this->localpath = realpath(dirname(__FILE__)) . '/bridge2cart';
		$ftpRes = ftp_connect($host);	
		if (($ftpRes !== false) && @ftp_login($ftpRes, $user, $pass)){
			ftp_pasv($ftpRes, true);
			$this->conn = $ftpRes; 
			if (!ftp_chdir ($this->conn,$dir)) {
				$this->messages = 'Can\'t open target directory.';
				$this->messageType = 'error';
				return false;
			}

			return true;
		} else{
			$this->messages = 'Can\'t login to FTP account';
			$this->messageType = 'error';
			return false;
		}
	}

	private function checkBridge(){
		$contents = ftp_nlist($this->conn, '.');
		if (!is_array($contents)) {
			return false;
		}
		return in_array('bridge2cart', $contents);
	}

	public function uploadBridge(){
		if ($this->conn == false){
			$this->messages = 'Can\'t connect to FTP host';
			$this->messageType = 'error';
			return false;
		}
		

		if ($this->checkBridge()){
			$this->messages = 'Bridge already installed';
			$this->messageType = 'error';
			return false;
		}
		$configFileName = $this->localpath . '/config.php';
		if(file_exists($configFileName)){
			unlink($configFileName);
		}
		file_put_contents($configFileName, "<?php define('M1_TOKEN', '".$this->token."');");
	
		if (!ftp_mkdir($this->conn,'bridge2cart')){
			$this->messages = 'Can\'t create bridge directory.';
			$this->messageType = 'error';
			return false;
		}

		if (!ftp_chmod($this->conn,0755,'bridge2cart')){
			$this->messages = 'Can\'t  change permissions to bridge directory.';
			$this->messageType = 'error';
			return false;
		}

		if (!ftp_chdir ($this->conn,'bridge2cart')) {
			$this->messages = 'Can\'t open bridge directory.';
			$this->messageType = 'error';
			return false;
		}

		if (!ftp_put($this->conn,'bridge.php',$this->localpath . '/bridge.php',FTP_BINARY)) {
			$this->messages = 'Can\'t copy bridge files.';
			$this->messageType = 'error';
			return false;
		}

		if (!ftp_put($this->conn,'config.php',$this->localpath . '/config.php',FTP_BINARY)) {
			$this->messages = 'Can\'t copy bridge files.';
			$this->messageType = 'error';
			return false;
		}
		if (!ftp_chmod($this->conn,0644,'bridge.php')){
			$this->messages = 'Can\'t  change permissions to bridge files.';
			$this->messageType = 'error';
			return false;
		}
		if (!ftp_chmod($this->conn,0644,'config.php')){
			$this->messages = 'Can\'t  change permissions to bridge files.';
			$this->messageType = 'error';
			return false;
		}
		$this->messages = "Connection bridge uploaded.";
		return true;
	}

	private function removeBridge(){
		$messageType = 'success';
		if (!$this->checkBridge()){
			return true;
		}
		
		ftp_chdir ($this->conn,'bridge2cart');
		$contents = ftp_nlist($this->conn, '.');
		$failToDelete = false;
		foreach($contents as $file){
			if ($file !== '.' || $file !== '..'){
				if (!ftp_delete($this->_connectId, $target)){
					$failToDelete = true;
				}
			}
		}
		
		if(ftp_rmdir($this->conn,'bridge2cart')) {
			$failToDelete = true;
		}

		if ($failToDelete){
			$this->messages = 'Couldn\'t delete bridge directory.';
			$this->messageType = 'error';
			return false;
		}else{
			$this->messages = 'Bridge removed.';
			return true;
		}
	}

	public function __destruct(){
		if ($this->conn !== NULL){
			return ftp_close($this->conn);
		}
	}
}