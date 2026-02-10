<?php

class AjaxRequestHandler {
	
	public $defaultError = array('error' => 'Invalid request');
	protected $actionParameter = 'action';
	protected $before = array();
	protected $actions = array();
	protected $errorHandler = null;
	protected $errorParameter = 'error';
	
	public function __construct($checkAjax = true) {
		if ($checkAjax)
			$this->before(function($ajax){
				if (!$ajax->isAjax())
					return false;
				return true;
			});
	}
	
	public function setActionParameter($parameter) {
		$this->actionParameter = $parameter;
		return $this;
	}
	
	public function before($callback) {
		$this->before[] = $callback;
		return $this;
	}
	
	public function addAction($name, $callback) {
		$this->actions[$name] = $callback;
		return $this;
	}

	public function addErrorHandler($callback, $parameter = 'error') {
		$this->errorHandler = $callback;
		$this->errorParameter = $parameter;
		return $this;
	}
	
	public function handle() {
		# Before
		foreach ($this->before as $callback) {
			if (!is_callable($callback))
				continue;
			$result = call_user_func_array($callback, array($this));
			if ($result === false)
				$this->outputContent($this->defaultError);
			elseif ($result !== true)
				$this->outputContent($result);
		}
		# Actions
		$action = null;
		if (isset($_GET[$this->actionParameter]) && isset($this->actions[$_GET[$this->actionParameter]]) && is_callable($this->actions[$_GET[$this->actionParameter]])) {
			$action = $this->actions[$_GET[$this->actionParameter]];
		} elseif (isset($_POST[$this->actionParameter]) && isset($this->actions[$_POST[$this->actionParameter]]) && is_callable($this->actions[$_POST[$this->actionParameter]])) {
			$action = $this->actions[$_POST[$this->actionParameter]];
		}
		if (isset($action)) {
			$result = call_user_func_array($action, array($this));
			# error handler
			if (isset($this->errorHandler)) {
				if ($result === false) {
					call_user_func_array($this->errorHandler, array('Unknown error'));
				} elseif (is_array($result) && isset($result[$this->errorParameter]) && $result[$this->errorParameter] != '') {
					call_user_func_array($this->errorHandler, array($result));
				}
			}

			if ($result === false)
				$this->outputContent($this->defaultError);
			elseif ($result !== true)
				$this->outputContent($result);
		} else
			$this->outputContent($this->defaultError);
	}
	
	public function isAjax() {
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}
	
	public function isPOST() {
		return $_SERVER["REQUEST_METHOD"] == "POST";
	}
	
	public function outputContent($data) {
		if (!is_string($data))
			return $this->outputJson($data);
		
		return $this->outputHtml($data);
	}
	
	public function outputHtml($data) {
		echo $data;
		exit();
	}
	
	public function outputJson($data) {
		echo json_encode($data);
		exit();
	}
}

?>