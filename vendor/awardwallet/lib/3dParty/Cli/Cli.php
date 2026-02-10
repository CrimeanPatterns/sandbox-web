<?php

require_once __DIR__."/CliHelp.php";
require_once __DIR__."/ProgressBar.php";
require_once __DIR__."/Table.php";

class Cli {
	
	/**
	 * @var CliHelp
	 */
	protected $helper;
	
	/**
	 * @var array
	 */
	protected $input;
	
	/**
	 * @var bool
	 */
	protected $colorMode = true;
	
	/**
	 * @var bool
	 */
	protected $useProgress = false;
	
	/**
	 * @var int
	 */
	protected $pointProgress = 0;
	
	/**
	 * @var int
	 */
	protected $maxPointProgress;
	
	/**
	 * @var Console_ProgressBar
	 */
	protected $progressBar;
	
	
	/**
	 * Constructor
	 * 
	 * @param CliHelp $cli_help
	 * @return void
	 */
	public function __construct(CliHelp $cli_help, $colorMode = true) {
		$this->helper = $cli_help;
		$this->setColorMode($colorMode);
	}
	
	/**
	 * Set color mode
	 * 
	 * @param bool $mode
	 * @return Cli
	 */
	public function setColorMode($mode = true) {
		$this->colorMode = (bool)$mode;
		
		return $this;
	}
	
	/**
	 * Create Progress Bar
	 * 
     * @param float  The target number for the bar
     * @param array  Options for the progress bar
	 * @param string The format string
     * @param string The string filling the progress bar
     * @param string The string filling empty space in the bar
     * @param int    The width of the display
	 * @return Cli
	 */
	public function createProgressBar($target_num, $options = array(), $formatstring = "%fraction% [%bar%] %percent% [%estimate%] %memory%", 
									  $bar = "=>", $prefill = "-", $width = 60)
 	{
		$this->progressBar = new Console_ProgressBar($formatstring, $bar, $prefill, $width, $target_num, $options);
		$this->useProgress = true;
		$this->maxPointProgress = (int)$target_num;
		echo "\n";
		
		return $this;
	}
	
	/**
	 * Update Progress Bar
	 * 
	 * @param int current position of the progress counter
	 * @return bool
	 */
	public function updateProgressBar($current) {
		if (!$this->useProgress)
			return false;
		
		$this->pointProgress = $current;
		$result = $this->progressBar->update($current);
		
		return $result;
	}
	
	/**
	 * Get Helper
	 * 
	 * @return CliHelp
	 */
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Add error(s)
	 * 
	 * @param string|array $error
	 * @return void
	 */
	public function addError($error, $timestamp = false) {
		if (!is_array($error))
			$error = array($error);
		
		if (is_array($error)) {
			foreach ($error as $e) {
				$e = "Error: $e";
				if ($this->colorMode)
					$e = "\033[37;41m$e\033[m";
				
				$this->Log("$e\n", $timestamp);
			}
		}
	}
	
	/**
	 * Add good event
	 * 
	 * @param string $event
	 * @return void
	 */
	public function addGoodEvent($event, $timestamp = false) {
		if ($this->colorMode)
			$event = "\033[30;42m$event\033[m";
		$this->Log("$event\n", $timestamp);
	}
	
	/**
	 * Send text to console
	 * 
	 * @param string $text
	 * @return void
	 */
	public function Log($text, $timestamp = false) {
		$time = '';
		if ($timestamp)
			$time = date("[H:i:s] ");
		
		if ($this->useProgress) {
			$this->progressBar->erase(true);
		}
		echo $time.$text;
		if ($this->useProgress &&  $this->pointProgress != $this->maxPointProgress) {
			$this->progressBar->display($this->pointProgress);
		}
	}
	
	/**
	 * Validate options
	 * 
	 * @return true|array errors
	 */
	public function validate() {
		$params = $this->getHelper()->getCLIParams();
		$longParams = array();
		$shortParams = array();
		foreach ($params as $long => $v) {
			$longParams[] = $long;
			if (isset($v['short']))
				$shortParams[] = $v['short'];
		}
		$options = getopt(implode('', $shortParams), $longParams);
		$errors = array();
		foreach ($params as $long => $v) {
			$havingVal = (strpos($long, ':') === strlen($long)-1);
			$_long = $this->getHelper()->clearParam($long);
			$_short = (isset($v['short'])) ? $this->getHelper()->clearParam($v['short']) : null;
			$this->input[$_long] = null;
			$input = & $this->input[$_long];
			if (isset($options[$_long]) || isset($options[$_short])) {
				if ($havingVal) {
					$input = (isset($options[$_long])) ? $options[$_long] : $options[$_short];
					if (isset($v['regExp'])) {
						if (!preg_match($v['regExp'], $input)) {
							$errors[] = (isset($v['error'])) ? $v['error'] : 'Incorrect '.$_long;
						}
					}
					if (!sizeof($errors)) {
						if (isset($v['callback']) && is_callable($v['callback'])) {
							$result = call_user_func($v['callback'], $input);
							if (is_string($result)) {
								$errors[] = $result;
							}
						}
					}
				} else
					$input = true;
			} else {
				if (isset($v['default']))
					$input = $v['default'];
				else {
					$errors[] = $_long. ' is required';
				}
			}
			
		}
		
		return (!sizeof($errors)) ? true : $errors;
	}
	
	/**
	 * Get user input
	 * 
	 * @return array
	 */
	public function getInput() {
		return $this->input;
	}
}

?>