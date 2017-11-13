<?php
defined('ABSPATH') or die("Cannot access pages directly.");
class Cart2CartWorker{

	var $root ='';
	var $c2cBridgePath ='';
	var $errorMessage = '';

	public function __construct(){
		$this->root = ABSPATH;
		$this->c2cBridgePath = $this->root . '/bridge2cart';
	}
	
	public function isBridgeExist(){
		if (is_dir($this->c2cBridgePath) && file_exists($this->c2cBridgePath.'/bridge.php') && file_exists($this->c2cBridgePath.'/config.php')){
			return true;
		}
		return false;
	}

	public function installBridge(){
		if($this->isBridgeExist()){
			return true;
		}
		return $this->xcopy(dirname(__FILE__).'/bridge2cart/',$this->root  . '/bridge2cart/');
	}

	public function unInstallBridge(){
		if(!$this->isBridgeExist()){
			return true;
		}
		return $this->deleteDir($this->c2cBridgePath);
	}

	public function updateToken($token){
		$config = @fopen($this->c2cBridgePath . '/config.php', 'w');
		$writed = fwrite($config, "<?php define('M1_TOKEN', '".$token."');");
		if (($config === false) || ($writed === false) || (fclose($config) === false)){
			$this->errorMessage.=  ' Could not update token';
			return false;
		}
		return true;
	}

	private function deleteDir($dirPath) {
		if (is_dir($dirPath)) {
			$objects = scandir($dirPath);
			foreach ($objects as $object) {
				if ($object != "." && $object !="..") {
					if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
						$this->deleteDir($dirPath . DIRECTORY_SEPARATOR . $object);
					} else {
						if(!unlink($dirPath . DIRECTORY_SEPARATOR . $object)){
							return false;
						}
					}
				}
			}
			reset($objects);
			if(!rmdir($dirPath)){
				return false;
			}
		}else{
			return false;
		}
		return true;
	}

	private function xcopy($src,$dst) {
		$dir = opendir($src);
		
		if(!$dir){
			return false;
		}

		if(!mkdir($dst)){
			return false;
		}
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					$this->xcopy($src . '/' . $file,$dst . '/' . $file);
				}
				else {
					if(!copy($src . '/' . $file,$dst . '/' . $file)){
						$this->deleteDir($dst);
						return false;
					}
				}
                chmod($dst . $file, 0755);
                chmod($dst, 0755);
			}
		}
		closedir($dir);
		return true;
	}
}