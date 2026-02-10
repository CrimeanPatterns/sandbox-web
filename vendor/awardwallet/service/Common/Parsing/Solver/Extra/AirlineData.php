<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


class AirlineData {

	public $iata;

	public $icao;

	public $name;

	public static function fromArray($arr) {
		$new = new self();
		$new->iata = $arr['Code'];
		$new->icao = $arr['ICAO'];
		$new->name = $arr['Name'];
		return $new;
	}
	
}