<?php

trait PointsDotComGwtOperations {

	protected $rpc_url;

	protected $purchase_placeholders;

	protected $purchase_values;

	protected function gwtCall($request) {
		$request = str_replace($this->purchase_placeholders, $this->purchase_values, $request);
		$this->http->PostURL($this->rpc_url, $request, [], true);
		if (strpos($this->http->Response["body"], "//OK") !== 0) {
			$this->http->Log("gwt did not response with OK status", LOG_LEVEL_ERROR);
			throw new CheckException("Unknown error", ACCOUNT_ENGINE_ERROR);
		}
		$response = substr($this->http->Response["body"], 4);
		$arr = json_decode($response);
		if (!$arr || !is_array($arr)) {
			$this->http->Log("invalid gwt response", LOG_LEVEL_ERROR);
			throw new CheckException("Unknown error", ACCOUNT_ENGINE_ERROR);
		}
		array_pop($arr);
		array_pop($arr);
		$table = array_pop($arr);
		if (!is_array($table)) {
			$this->http->Log("string table not found in gwt response", LOG_LEVEL_ERROR);
			throw new CheckException("Unknown error", ACCOUNT_ENGINE_ERROR);
		}
		return $table;
	}

	protected function getPointsStateId($code) {
		$knownCodes = [
			"NY" => 56,
			"PA" => 60,
		];
		if (!isset($knownCodes[$code])) {
			$this->http->Log("Unsupported card state: $code", LOG_LEVEL_ERROR);
			throw new CheckException("Unknown error", ACCOUNT_ENGINE_ERROR);
		}
		return $knownCodes[$code];
	}

}