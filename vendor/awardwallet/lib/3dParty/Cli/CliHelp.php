<?php

class CliHelp {
	
	/**
	 * @var array
	 */
	protected $cli_params = array();
	
	/**
	 * @var string
	 */
	protected $usageScript;
	
	/**
	 * @var string
	 */
	protected $example;
	
	/**
	 * Set Command Line Interface params
	 * 
	 * @param array $params
	 * @return CliHelp
	 */
	public function setCLIParams($params) {
		$params = array_merge(array(
			'help' => array('short' => 'h', 'default' => false, 'desc' => 'Show this message')
		), $params);
		$this->cli_params = $params;
		return $this;
	}
	
	/**
	 * Get Command Line Interface params
	 * 
	 * @return array
	 */
	public function getCLIParams() {
		return $this->cli_params;
	}
	
	/**
	 * Set Usage Script
	 * 
	 * @param string $script file name
	 * @return CliHelp
	 */
	public function setUsageScript($script) {
		$this->usageScript = $script;
		return $this;
	}
	
	/**
	 * Get Usage Script
	 * 
	 * @return string
	 */
	public function getUsageScript() {
		return $this->usageScript;
	}
	
	/**
	 * Set Example
	 * 
	 * @param string $example
	 * @return CliHelp
	 */
	public function setExample($example) {
		$this->example = $example;
		return $this;
	}
	
	/**
	 * Get Example
	 * 
	 * @return string
	 */
	public function getExample() {
		return $this->example;
	}
	
	/**
	 *  Output help
	 */
	public function __toString() {
		$out = "usage: php ". $this->getUsageScript() ."";
		$params = $this->getCLIParams();
		$items = array();
		$options = array();
		foreach ($params as $long => $v) {
			$item = '';
			$option = '';
			$havingVal = (strpos($long, ':') === strlen($long)-1);
			if (isset($v['short']) && trim($v['short']) != '') {
				$item .= '-'.$this->clearParam($v['short']).'|';
				$option .= '        -'.$this->clearParam($v['short']).'  ';
			} else {
				$option .= '            ';
			}
				
			$item .= '--'.$this->clearParam($long);
			$option .= '--'.$this->clearParam($long);
			if ($havingVal && isset($v['default']) && !is_null($v['default'])) {
				if (is_bool($v['default']))
					$item .= '='. ($v['default'] === true ? 'true' : 'false');
				elseif (trim($v['default']) == '')
					$item .= '=""';
				else
					$item .= '='.strval($v['default']);
			}
			if (isset($v['default']) || is_null($v['default']))
				$item = '['.$item.']';
			
			$items[$long] = $item;
			$options[$long] = $option;
		}
		
		# Max length
		$max_length = 0;
		foreach ($options as $long => $v) {
			$len = strlen($v);
			if ($len > $max_length)
				$max_length = $len;
		}
		$max_length += 2;
		foreach ($options as $long => $v) {
			$o = & $options[$long];
			$o = str_pad($o, $max_length, " ", STR_PAD_RIGHT);
			if (isset($params[$long]['desc'])) {
				$o .= $params[$long]['desc'];
				$havingVal = (strpos($long, ':') === strlen($long)-1);
				if ($havingVal && isset($params[$long]['default']) && !is_null($params[$long]['default'])) {
					if (is_bool($params[$long]['default']))
						$o .= ' (default: '.($params[$long]['default'] === true ? 'true' : 'false').')';
					elseif (trim($params[$long]['default']) == '')
						$o .= ' (default: "")';
					else
						$o .= ' (default: '.strval($params[$long]['default']).')';
				}
			}
		}
		
		$out = "\033[1;32m".$out. " " .implode(" ", $items). "\033[m\n\n";
		$out .= "\033[1;36mOptions:\n". implode("\n", $options) ."\033[m\n";
		$out .= "\033[1mExample:\n        ". $this->getExample() ."\033[m\n";
		return $out;
	}
	
	public function clearParam($param) {
		if (strpos($param, ':') === strlen($param)-1)
			$param = substr_replace($param, "", -1);
		return trim($param);
	}
}
	
?>