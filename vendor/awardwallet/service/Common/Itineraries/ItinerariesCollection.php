<?php
namespace AwardWallet\Common\Itineraries;

use AwardWallet\Common\Geo\GoogleGeo;
use Psr\Log\LoggerInterface;
use JMS\Serializer\Annotation\Expose;

class ItinerariesCollection extends AbstractCollection {

	/**
	 * @var GoogleGeo
	 * @Expose
	 */
	private $googleGeo;

	public function __construct(GoogleGeo $googleGeo, LoggerInterface $logger){
		parent::__construct($logger);
		$this->googleGeo = $googleGeo;
	}

	/** @return GoogleGeo */
	public function getGoogleGeo()
	{
		return $this->googleGeo;
	}

	/**
	 * @return HotelReservation
	 */
	public function addReservation(){
		$result = new HotelReservation($this->logger);
		$this->collection[] = $result;
		return $result;
	}

	/**
	 * @return CarRental
	 */
	public function addRental(){
		$result = new CarRental($this->logger);
		$this->collection[] = $result;
		return $result;
	}

	/**
	 * @return Flight
	 */
	public function addFlight(){
		$result = new Flight($this->logger);
		$this->collection[] = $result;
		return $result;
	}

	/** @return Flight */
	public function addTransportation(){
		$result = new Transportation($this->logger);
		$this->collection[] = $result;
		return $result;
	}

	/**
	 * @return Cruise
	 */
	public function addCruise(){
		$result = new Cruise($this->logger);
		$this->collection[] = $result;
		return $result;
	}

	/**
	 * @return Cancelled
	 */
	public function addCancelled(){
		$result = new Cancelled($this->logger);
		$this->collection[] = $result;
		return $result;
	}

	static public function SQLToDateTime( $sDateTime )
	{
		if( preg_match( "/([0-9]{4})\-([0-9]{2})\-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/ims", $sDateTime, $Args ) )
			return mktime( $Args[4], $Args[5], $Args[6], $Args[2], $Args[3], $Args[1] );
		else
			if( preg_match( "/([0-9]{4})\-([0-9]{2})\-([0-9]{2})/ims", $sDateTime, $Args ) )
				return mktime( 0, 0, 0, $Args[2], $Args[3], $Args[1] );
			else
				throw new \Exception( "Invalid date format:$sDateTime" );
	}
}