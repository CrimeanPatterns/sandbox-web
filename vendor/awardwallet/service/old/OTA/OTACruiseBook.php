<?
/**
 * Cruise trips to OTA_CruiseBookRS converter
 */
class OTACruiseBook extends OTABase{

	var $segments;
	var $id;

	public function __construct($kind, $apiVersion){
		parent::__construct($kind, $apiVersion);
		$this->root = $this->doc->createElementNS('http://www.opentravel.org/OTA/2003/05', 'OTA_CruiseBookRS');
	}

	public function addReservation($id){
		global $Connection;
		if(parent::addReservation($id) === false)
			return false;

		// root
		$this->doc->appendChild($this->root);
		$this->root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$this->root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://www.opentravel.org/OTA/2003/05 OTA_CruiseBookRS.xsd');
		$this->root->setAttribute('Cancel', 'false');
		if(!empty($this->row['UpdateDate']))
			$this->root->setAttribute('TimeStamp', date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['UpdateDate'])));
		$this->root->setAttribute('Target', 'Production');
		$this->root->setAttribute('Version', '1.000');
		$this->root->setAttribute('SequenceNmbr', '1');
		$this->root->setAttribute('TransactionStatusCode', 'Start');
		$this->root->setAttribute('PrimaryLangID', 'en-us');
		$this->root->setAttribute('AltLangID', 'en-us');

		$this->root->appendChild($this->doc->createElement("Success"));
		
		if (isset($this->extProperties['Cancelled']) && $this->extProperties['Cancelled'] == true) {
			$this->CancelledIt($this->root, $this->row['RecordLocator']);
			return $this->root;
		}

		$BookingReferenceID  = $this->doc->createElement("ReservationID");
		$BookingReferenceID->setAttribute("Type", "14"); // reservation
		$BookingReferenceID->setAttribute("ID", $this->row['RecordLocator']);
		$this->root->appendChild($BookingReferenceID);

		$this->segments = array();
		$q = new TQuery("select * from TripSegment where TripID = $id order by DepDate");
		while(!$q->EOF){
			$this->segments[] = $q->Fields;
			$q->Next();
		}
		$this->id = $id;
		$this->root->appendChild($this->sailingInfo($this->doc));

		return $this->root;
	}

	private function sailingInfo(DOMDocument $doc){
		global $Connection;
		$sailingInfo = $doc->createElement("SailingInfo");
		$selectedSailing = $doc->createElement("SelectedSailing");

		$selectedSailing->setAttribute('PortsOfCallQuantity', count($this->segments));
		//$selectedSailing->setAttribute('Start', date('Y-m-d', $Connection->SQLToDateTime($this->segments[0]['DepDate'])));
		//$selectedSailing->setAttribute('End', date('Y-m-d', $Connection->SQLToDateTime($this->segments[count($this->segments)-1]['ArrDate'])));

		$cruiseLine = $doc->createElement("CruiseLine");
		if (isset($this->extProperties['ShipCode']))
			$cruiseLine->setAttribute('ShipCode', $this->extProperties['ShipCode']);
		if (isset($this->extProperties['ShipName']))
			$cruiseLine->setAttribute('ShipName', $this->extProperties['ShipName']);
		if (isset($this->segments[0]['AirlineName']))
			$cruiseLine->setAttribute('VendorName', $this->segments[0]['AirlineName']);
		$selectedSailing->appendChild($cruiseLine);

		$departurePort = $doc->createElement("DeparturePort");
		$departurePort->setAttribute('EmbarkationTime', date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->segments[0]['DepDate'])));
		$selectedSailing->appendChild($departurePort);

		$arrivalPort = $doc->createElement("ArrivalPort");
		$arrivalPort->setAttribute('DebarkationDateTime', date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->segments[count($this->segments)-1]['ArrDate'])));
		$selectedSailing->appendChild($arrivalPort);

		$sailingInfo->appendChild($selectedSailing);

		$selectedCategory = $doc->createElement("SelectedCategory");
		if (isset($this->extProperties['Deck'])) //@todo: rename to DeckName?
			$selectedCategory->setAttribute('DeckName', $this->extProperties['Deck']);

		$selectedCabin = $doc->createElement("SelectedCabin");
		$selectedCabin->setAttribute('Status', '1');
		if (isset($this->extProperties['RoomNumber'])) //@todo: rename to Cabin or Stateroom
			$selectedCabin->setAttribute('CabinNumber', $this->extProperties['RoomNumber']);
		$selectedCategory->appendChild($selectedCabin);

		$sailingInfo->appendChild($selectedCategory);

		$this->addTPAExtensions($this->doc, $sailingInfo);

		return $sailingInfo;
	}

	private function addTPAExtensions(DOMDocument $doc, DOMNode $node){
		global $Connection;
		$extensions = $doc->createElement("TPA_Extensions");
		$node->appendChild($extensions);

		$this->addExtProperties($extensions, $this->extProperties, array(
			"ShipCode", "ShipName", "Deck", "RoomNumber",
		));

		$segments = $doc->createElement("Segments");
		$extensions->appendChild($segments);
		$q = new TQuery("select * from TripSegment where TripID = {$this->id} order by DepDate");
		while(!$q->EOF){
			$seg = $doc->createElement("Segment");

			$seg->setAttribute("DepDate", date(OTABase::DATE_OTA, $Connection->SQLToDateTime($q->Fields["DepDate"])));
			$seg->setAttribute("ArrDate", date(OTABase::DATE_OTA, $Connection->SQLToDateTime($q->Fields["ArrDate"])));
			$seg->setAttribute("DepName", $q->Fields["DepName"]);
			$seg->setAttribute("ArrName", $q->Fields["ArrName"]);
			if (!empty($q->Fields["FlightNumber"]))
				$seg->setAttribute("Type", $q->Fields["FlightNumber"]);
			$segments->appendChild($seg);
			$q->Next();
		}

	}
}