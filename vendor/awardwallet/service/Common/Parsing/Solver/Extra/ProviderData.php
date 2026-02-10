<?php

namespace AwardWallet\Common\Parsing\Solver\Extra;


class ProviderData {

	public $code;

	public $id;

	public $iata;

	public $kind;

	public $name;

	public $properties = [];

	public $historyFields = [];

	public static function fromArray($arr) {
		$new = new self();
		$new->code = $arr['Code'];
		$new->id = $arr['ProviderID'];
		$new->iata = $arr['IATACode'];
		$new->kind = intval($arr['Kind']);
		$new->name = $arr['ShortName'];
		return $new;
	}

}