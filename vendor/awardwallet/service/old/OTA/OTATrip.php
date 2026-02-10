<?
/**
 * Air trips to OTA_TripResRS converter
 */
class OTATrip extends OTABase{

	public function __construct($kind, $apiVersion){
		parent::__construct($kind, $apiVersion);
		$this->root = $this->doc->createElementNS('http://www.opentravel.org/OTA/2003/05', 'OTA_AirBookRS');
	}

	protected function loadReservationDetails(array &$row){
		parent::loadReservationDetails($row);
		$row['TripSegments'] = array();
		foreach(new TQuery("select * from TripSegment where TripID = {$row['TripID']} order by DepDate") as $segment){
			$segment['ExtProperties'] = LoadExtProperties('TripSegment', $segment['TripSegmentID']);
			$row['TripSegments'][] = $segment;
		}
	}

	public function setReservationData(array $data){
		global $Connection;
		parent::setReservationData($data);
//		$this->addBackTrip($id);
		// root
		$this->doc->appendChild($this->root);
		$this->root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$this->root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://www.opentravel.org/OTA/2003/05 OTA_AirBookRS.xsd');
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
		
		$AirReservation  = $this->doc->createElement("AirReservation");
		$AirReservation->setAttribute("LastModified", date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['UpdateDate'])));
		$this->root->appendChild($AirReservation);
		
		if (isset($this->extProperties['Status'])) {
			$Comment  = $this->doc->createElement("Comment");
			$AirReservation->appendChild($Comment);
			$Comment->nodeValue = $this->extProperties['Status'];
		}
		if(isset($this->extProperties['ReservationDate']) && !empty($this->extProperties['ReservationDate']))
			$AirReservation->setAttribute("CreateDateTime", date(OTABase::DATE_OTA, $this->extProperties['ReservationDate']));
		
		$AirItinerary = $this->doc->createElement("AirItinerary");
		$AirItinerary->setAttribute("DirectionInd", 'OneWay');
		$AirReservation->appendChild($AirItinerary);

		$OriginDestinationOptions = $this->doc->createElement("OriginDestinationOptions");
		$AirItinerary->appendChild($OriginDestinationOptions);
		
		$OriginDestinationOption = $this->doc->createElement("OriginDestinationOption");
		$OriginDestinationOptions->appendChild($OriginDestinationOption);

        $i = 1;
		$segmentFields = array();
		foreach($this->row['TripSegments'] as $segment){
			$OriginDestinationOption->appendChild($this->createSegment($segment, $i));
			$segmentFields[$i] = $segment;
            $i += 1;
		}

		$this->createPriceInfo($AirReservation);
		$this->createPassengers($AirReservation, $segmentFields);

		$BookingReferenceID  = $this->doc->createElement("BookingReferenceID");
		$BookingReferenceID->setAttribute("Type", "14"); // reservation
		$BookingReferenceID->setAttribute("ID", $this->row['RecordLocator']);
		if(!empty($this->extProperties["TripNumber"]) && $this->apiVersion >= 4){
			$extensions = $this->doc->createElement("TPA_Extensions");
			$this->addExtProperties($extensions, array_intersect_key($this->extProperties, ["TripNumber" => 0]), []);
			$this->root->appendChild($extensions);
		}
		$AirReservation->appendChild($BookingReferenceID);

		return $this->root;
	}

	private function createPriceInfo(DOMNode $AirReservation)
	{
		if(isset($this->extProperties['TotalCharge'])){
			$PriceInfo = $this->doc->createElement("PriceInfo");
			$AirReservation->appendChild($PriceInfo);
			$ItinTotalFare = $this->doc->createElement("ItinTotalFare");
			$PriceInfo->appendChild($ItinTotalFare);
			$TotalFare = $this->doc->createElement("TotalFare");
			$ItinTotalFare->appendChild($TotalFare);
			$TotalFare->setAttribute("Amount", $this->extProperties['TotalCharge']);
		}
		if (isset($TotalFare) && isset($this->extProperties['Currency'])) {
			$TotalFare->setAttribute("CurrencyCode", $this->extProperties['Currency']);
		}
		if(isset($this->extProperties['BaseFare']) && isset($PriceInfo) && isset($ItinTotalFare)){
			$BaseFare = $this->doc->createElement("BaseFare");
			$ItinTotalFare->appendChild($BaseFare);
			$BaseFare->setAttribute("Amount", $this->extProperties['BaseFare']);
		}
		if (isset($BaseFare) && isset($this->extProperties['Currency'])) {
			$BaseFare->setAttribute("CurrencyCode", $this->extProperties['Currency']);
		}
		if (isset($ItinTotalFare) && isset($this->extProperties['Tax']) && isset($this->extProperties['Currency'])) {
			$fees = $this->doc->createElement("Fees");
			$ItinTotalFare->appendChild($fees);
			$fee = $this->doc->createElement("Fee");
			$fee->setAttribute("Amount", $this->extProperties['Tax']);
			$fee->setAttribute("CurrencyCode", $this->extProperties['Currency']);
			$fee->setAttribute("TaxInclusive", "true");
			$fee->setAttribute("Purpose", "7");
			$fees->appendChild($fee);
		}

		foreach(['SpentAwards', 'EarnedAwards'] as $key)
			if (isset($this->extProperties[$key])) {
				if (!isset($PriceInfo)) {
					$PriceInfo = $this->doc->createElement("PriceInfo");
					$AirReservation->appendChild($PriceInfo);
				}
				if(!isset($tpaExtensionNode)){
					$tpaExtensionNode = $this->doc->createElement('TPA_Extensions');
					$PriceInfo->appendChild($tpaExtensionNode);
				}
				$awardsNode = $this->doc->createElement($key, $this->extProperties[$key]);
				$tpaExtensionNode->appendChild($awardsNode);
			}
	}

	private function createPassengers(DOMNode $AirReservation, $segmentFields){
		if(isset($this->extProperties['Passengers']))
			$passengers = explode(", ", $this->extProperties['Passengers']);
		else
			$passengers = array();
		if(isset($this->extProperties['AccountNumbers']))
			$numbers = explode(", ", $this->extProperties['AccountNumbers']);
		else
			$numbers = array();
		
		$n = 0;
		$TravelerInfo = $this->doc->createElement("TravelerInfo");
		while(isset($passengers[$n]) || isset($numbers[$n])){
			$AirTraveler = $this->doc->createElement("AirTraveler");
			$TravelerRefNumber = $this->doc->createElement("TravelerRefNumber");
			$TravelerRefNumber->setAttribute("RPH", $n+1);
			$AirTraveler->appendChild($TravelerRefNumber);
			$TravelerInfo->appendChild($AirTraveler);
			if(isset($passengers[$n]))
				$AirTraveler->appendChild($this->createPersonName($passengers[$n]));
			if(isset($numbers[$n])){
				$CustLoyalty = $this->doc->createElement("CustLoyalty");
				$CustLoyalty->setAttribute("ProgramID", $this->row['ProviderCode']);
				$CustLoyalty->setAttribute("MembershipID", $numbers[$n]);
				$AirTraveler->appendChild($CustLoyalty);
			}
			$n++;
		}
		
		# Seats
		$SpecialReqDetails = $this->doc->createElement("SpecialReqDetails");
		$seatRequests = array();
		foreach ($segmentFields as $sid => $segment) {
			$properties = $segment['ExtProperties'];
			if (!isset($properties['Seats'])) 
				continue;
			$seats = explode('|', str_replace(',', '|', $properties['Seats']));
			foreach ($seats as $n => $seat) {
				$seat = trim($seat);
				$SeatRequest = $this->doc->createElement("SeatRequest");
				if (isset($properties['Smoking'])) {
					if (preg_match("/non smoking|0|no/i", $properties['Smoking'])) {
						$SmokingAllowed = 'false';
					} elseif (preg_match("/smoking|1|yes/i", $properties['Smoking'])) {
						$SmokingAllowed = 'true';
					} else {
						$SmokingAllowed = 'false';
					}
					$SeatRequest->setAttribute("SmokingAllowed", $SmokingAllowed);
				}
				$SeatRequest->setAttribute("SeatNumber", $seat);
				$SeatRequest->setAttribute("FlightRefNumberRPHList", $sid);
				if (isset($passengers[$n]) || isset($numbers[$n])) {
					$SeatRequest->setAttribute("TravelerRefNumberRPHList", $n+1);
				}
				$seatRequests[] = $SeatRequest;
			}
		}
		if (sizeof($seatRequests)) {
			$SeatRequests = $this->doc->createElement("SeatRequests");
			foreach ($seatRequests as $seatRequest) {
				$SeatRequests->appendChild($seatRequest);
			}
			$SpecialReqDetails->appendChild($SeatRequests);
		}

		# Confirmation Numbers
		if(!empty($this->extProperties['ConfirmationNumbers'])){
			$remarks = $this->doc->createElement("SpecialRemarks");
			$remark = $this->doc->createElement("SpecialRemark", $this->extProperties['ConfirmationNumbers']);
			$remark->setAttribute('RemarkType', 'ConfirmationNumbers');
			$remarks->appendChild($remark);
			$SpecialReqDetails->appendChild($remarks);
		}

		if($SpecialReqDetails->childNodes->length > 0)
			$TravelerInfo->appendChild($SpecialReqDetails);
		if ($TravelerInfo->childNodes->length > 0) {
			$AirReservation->appendChild($TravelerInfo);
		}
		
	}

	private function createPersonName($name){
		$PersonName = $this->doc->createElement("PersonName");
		$name = preg_replace("/\s+/ims", " ", $name);
		$name = preg_replace("/\./ims", "", $name);
		$parts = explode(" ", $name);
		if(count($parts) > 1)
			if(in_array(strtolower($parts[0]), array("mr", "ms", "mrs", "dr", "jr")))
				$PersonName->appendChild($this->doc->createElement("NameTitle", array_shift($parts)));
		if(count($parts) > 0)
			$PersonName->appendChild($this->doc->createElement("GivenName", array_shift($parts)));
		if(count($parts) > 1)
			$PersonName->appendChild($this->doc->createElement("MiddleName", array_shift($parts)));
		if(count($parts) > 0)
			$PersonName->appendChild($this->doc->createElement("Surname", implode(" ", $parts)));
		return $PersonName;
	}

	private function createSegment($segment, $n = 1){
		global $Connection;
		$properties = $segment['ExtProperties'];
		$FlightSegment = $this->doc->createElement("FlightSegment");
		$FlightSegment->setAttribute("DepartureDateTime", date(OTABase::DATE_OTA, $Connection->SQLToDateTime($segment['DepDate'])));
		$FlightSegment->setAttribute("ArrivalDateTime", date(OTABase::DATE_OTA, $Connection->SQLToDateTime($segment['ArrDate'])));
		$FlightSegment->setAttribute("RPH", $n);
		$FlightSegment->setAttribute("FlightNumber", $segment['FlightNumber']);
		$FlightSegment->setAttribute("Status", '30');
        if(isset($this->extProperties['Passengers'])) {
            $passengers = count(explode(", ", $this->extProperties['Passengers']));
            $FlightSegment->setAttribute("NumberInParty", $passengers);
        }

		if(isset($properties['BookingClass']))
			$FlightSegment->setAttribute("ResBookDesigCode", $properties['BookingClass']);
		if(isset($properties['Meal']))
			$FlightSegment->setAttribute("MealCode", $properties['Meal']);
		if(isset($properties['TraveledMiles']))
			$FlightSegment->setAttribute("Distance", $properties['TraveledMiles']);
		if(isset($properties['Stops']))
			$FlightSegment->setAttribute("StopQuantity", $properties['Stops']);

		$DepartureAirport = $this->doc->createElement("DepartureAirport");
		$DepartureAirport->setAttribute("LocationCode", $segment['DepCode']);
		$FlightSegment->appendChild($DepartureAirport);

		$ArrivalAirport = $this->doc->createElement("ArrivalAirport");
		$ArrivalAirport->setAttribute("LocationCode", $segment['ArrCode']);
		$FlightSegment->appendChild($ArrivalAirport);

//		$MarketingAirline = $this->doc->createElement("MarketingAirline");
//		$MarketingAirline->setAttribute("CompanyShortName", $this->row['ProviderName']);
//		$MarketingAirline->setAttribute("Code", $this->row['ProviderCode']);
//		$FlightSegment->appendChild($MarketingAirline);
//
		if(!empty($segment['AirlineName'])) {
			$OperatingAirline = $this->doc->createElement("OperatingAirline");
			$OperatingAirline->setAttribute("CompanyShortName", $segment['AirlineName']);
			$IATACode = Lookup("Airline", "Name", "Code", "'" . addslashes($segment['AirlineName']) . "'");
			if (!empty($IATACode)) {
				$OperatingAirline->setAttribute('Code', $IATACode);
				$OperatingAirline->setAttribute('CodeContext', 'IATA');
			}
			$FlightSegment->appendChild($OperatingAirline);
		}

		$MarriageGrp = $this->doc->createElement("MarriageGrp");
		$MarriageGrp->nodeValue = $this->row['TripID'];
		$FlightSegment->appendChild($MarriageGrp);

		$Extensions = $this->doc->createElement("TPA_Extensions");
		$FlightSegment->appendChild($Extensions);
		$Extensions->appendChild($this->doc->createElement("DepartureName", $segment['DepName']));
		$Extensions->appendChild($this->doc->createElement("ArrivalName", $segment['ArrName']));
		$Extensions->appendChild($this->doc->createElement("Category", $this->row['Category']));

		$this->addExtProperties($Extensions, $properties, array("Stops", "Meal", "TraveledMiles", "Seats", "Smoking", "BookingClass"));

		$segment['ExtProperties'] = $properties;
		$this->row['Segments'][] = $segment;

		return $FlightSegment;
	}

}