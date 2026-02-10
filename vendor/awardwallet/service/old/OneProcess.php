<?php

class OneProcess {
	
	public $pidPath = '/var/log/www/awardwallet';
	public $stopCallback;
	protected $fp;
	
	public function __construct($stopCallback = null) {
		if(isset($stopCallback))
			$this->stopCallback = $stopCallback;
		else
			$this->stopCallback = function() {
				exit();
			};
	}
	
	public function stopIfRunning($file) {
		$lockfile = $this->pidPath .'/'. $file;
		$this->fp = fopen($lockfile, "w+");
		$isRunning = !flock($this->fp, LOCK_EX | LOCK_NB);
		if (!$isRunning) {
			register_shutdown_function(array($this, 'removePID'), $file);
		} else { 
			call_user_func($this->stopCallback);
		}
	}
	
	public function removePID($file) {
		$lockfile = $this->pidPath .'/'. $file;
		if (flock($this->fp, LOCK_UN)) {
			fclose($this->fp);
			if (file_exists($lockfile))
				return unlink($lockfile);
			else
				return true;
		} else
			return false;
	}
	
}
?>
