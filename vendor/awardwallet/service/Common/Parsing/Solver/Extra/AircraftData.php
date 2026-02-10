<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


class AircraftData {

	public $iataCode;
	public $name;
	public $turboProp;
	public $jet;
	public $wideBody;
	public $regional;

	public static function fromArray($arr) {
		$new = new self();
		$new->iataCode = $arr['IataCode'];
		$new->name = $arr['Name'];
		$new->turboProp = !!$arr['TurboProp'];
		$new->jet = !!$arr['Jet'];
		$new->wideBody = !!$arr['WideBody'];
		$new->regional = !!$arr['Regional'];
		return $new;
	}

}