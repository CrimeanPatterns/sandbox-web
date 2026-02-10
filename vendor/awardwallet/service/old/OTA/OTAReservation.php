<?php
class OTAReservation extends OTABase{

	protected $reservations, $pos;

	public function __construct($kind, $apiVersion){
		parent::__construct($kind, $apiVersion);
		// root
		$this->root = $this->doc->createElementNS('http://www.opentravel.org/OTA/2003/05', 'OTA_HotelResRS');
		$this->doc->appendChild($this->root);
		$this->root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$this->root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://www.opentravel.org/OTA/2003/05 OTA_HotelResRS.xsd');
		$this->root->setAttribute('Version', '1.003');
		//$root->setAttribute('TransactionIdentifier', $this->row['ID']);

		// AW LP code
		$this->pos = $this->doc->createElement("POS");
		$this->root->appendChild($this->pos);
		$this->root->appendChild($this->doc->createElement("Success"));
		$this->reservations = $this->doc->createElement("HotelReservations");
		$this->root->appendChild($this->reservations);
	}

	public function addReservation($id){
		if(parent::addReservation($id) === false)
            return false;

		global $Connection;
		
		if (isset($this->extProperties['Cancelled']) && $this->extProperties['Cancelled'] == true) {
			$this->CancelledIt($this->root, $this->row['ConfirmationNumber']);
			return $this->root;
		}

		$this->row['CheckInDate'] = date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['CheckInDate']));
		$this->row['CheckOutDate'] = date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['CheckOutDate']));
//		if($this->row['CreateDate'] == '0000-00-00 00:00:00')
//			$this->row['CreateDate'] = date(OTABase::DATE_OTA, $this->row['UpdateDate']);
//		else
		$this->row['CreateDate'] = date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['CreateDate']));

		$pos = $this->doc->createElement("Source");
		$res = $this->doc->createElement("HotelReservation");

		if(isset($this->extProperties['ReservationDate']))
			$res->setAttribute("CreateDateTime", date(OTABase::DATE_OTA, $this->extProperties['ReservationDate']));

		$this->pos->appendChild($pos);
		$this->reservations->appendChild($res);

		$pos->appendChild($this->createAWLPcode($this->doc));
		$res->appendChild($this->createRoomStays($this->doc));
		$res->appendChild($this->createResGuests($this->doc));
		$res->appendChild($this->createResGlobalInfo($this->doc));
		$res->appendChild($this->createTPA_Extensions($this->doc));

		return $this->root;
	}

	private function createAWLPcode(DOMDocument $doc){

		$bookingChannel = $doc->createElement('BookingChannel');
		$bookingChannel->setAttribute('Type', '4');
		if(isset($this->row['ProviderCode'])){
			$companyName = $doc->createElement('CompanyName');
			$companyName->nodeValue = $this->row['ProviderCode'];
			$bookingChannel->appendChild( $companyName );
		}
		return $bookingChannel;
	}

	private function createRoomStays(DOMDocument $doc){

		$RoomStays = $doc->createElement("RoomStays");

		# preparing incoming data
		if(isset($this->extProperties['RoomType']))
			$roomTypes = explode('|', $this->extProperties['RoomType']);
		if(isset($this->extProperties['RoomTypeDescription']))
			$roomDescr = explode('|', $this->extProperties['RoomTypeDescription']);
		if(isset($this->extProperties['CancellationPolicy']))
			$cancellationPolicy = explode('|', $this->extProperties['CancellationPolicy']);
		if(isset($this->extProperties['RateType']))
			$rateTypeDescr = explode('|', $this->extProperties['RateType']);
		if(isset($this->extProperties['Rate']))
			$rateValue = explode('|', $this->extProperties['Rate']);
		if(isset($this->extProperties['Guests']))
			$roomAdults = explode('|', $this->extProperties['Guests']);
		if(isset($this->extProperties['Kids']))
			$roomKids = explode('|', $this->extProperties['Kids']);
		if(isset($this->extProperties['Taxes']))
			$roomTaxes = str_replace(',', '', (explode('|', $this->extProperties['Taxes']) ));

        if(isset($this->extProperties['GuestNames']))
            $roomNames = explode('|', str_replace(',', '|', $this->extProperties['GuestNames']));
        if (isset($roomNames) && !empty($roomNames) && !is_array($roomNames))
            $roomNames = array($roomNames);

		if(isset($this->row['Currency']) && $this->row['Currency'] == 'Points')
			$replaceT = Array(',','-','.');
		else
			$replaceT = Array(',','-');

		if(isset($this->extProperties['Cost'])){
			$roomCost = explode('|', $this->extProperties['Cost']);
			foreach($roomCost as &$cost)
				$cost = filterBalance($cost, true);
		}
		if(isset($this->extProperties['Total']))
			$totalSum = filterBalance($this->extProperties['Total'], true);

        $numRooms = null;
		// count unique rooms
		if(isset($roomTypes))
			$numRooms = count($roomTypes);
		else if(isset($rateValue))
			$numRooms = count($rateValue);
		else if(isset($roomAdults))
			$numRooms = count($roomAdults);
		else if(isset($roomTaxes))
			$numRooms = count($roomTaxes);

        $RoomStay = $doc->createElement("RoomStay");
        $RoomStays->appendChild($RoomStay);
        if($numRooms > 0){
            $RoomTypes = $doc->createElement("RoomTypes");
            $RoomStay->appendChild($RoomTypes);
            $RatePlans = $doc->createElement("RatePlans");
            $RoomStay->appendChild($RatePlans);
            $RoomRates = $doc->createElement("RoomRates");
            $RoomStay->appendChild($RoomRates);
        }
		$GuestCounts= $doc->createElement("GuestCounts");

		(isset($this->extProperties['Rooms'])) ? $numSameRooms = $this->extProperties['Rooms'] : $numSameRooms = 1;
		($numRooms==1) ? $numberOfUnits = $numSameRooms : $numberOfUnits = 1;

		for($i=0; $i<$numRooms; $i++){
			$n = $i + 1;

			# Room Types
			$RoomType = $doc->createElement("RoomType");
			if(isset($roomTypes[$i]))
				$RoomType->setAttribute("Configuration", $roomTypes[$i]);
			else
				$RoomType->setAttribute("Configuration", 'None');

			$RoomType->setAttribute("NumberOfUnits", $numberOfUnits);
			$RoomType->setAttribute("RoomID", $n); // New
			$RoomTypes->appendChild($RoomType);


			if(isset($roomDescr[$i]) && (!isset($roomTypes[$i]) || $roomTypes[$i] != $roomDescr[$i])){
				$RoomDescription = $doc->createElement("RoomDescription");
				$RoomType->appendChild($RoomDescription);
				$Text = $doc->createElement("Text");
				$Text->nodeValue  = isset($roomDescr[$i]) ? $roomDescr[$i] : '';
				$RoomDescription->appendChild($Text);
			}

			# Rate Plans
			$RatePlan = $doc->createElement("RatePlan");
			$RatePlan->setAttribute("RatePlanID", $n); // New
			$RatePlans->appendChild($RatePlan);

			$CancelPenalties = $doc->createElement("CancelPenalties");
			$RatePlan->appendChild($CancelPenalties);
			$CancelPenalty = $doc->createElement("CancelPenalty");
			$CancelPenalties->appendChild($CancelPenalty);
			$PenaltyDescription = $doc->createElement("PenaltyDescription");
			$CancelPenalty->appendChild($PenaltyDescription);
			$Text = $doc->createElement("Text");
			if(isset($cancellationPolicy[$i]))
				$Text->nodeValue  = htmlspecialchars(stripslashes($cancellationPolicy[$i]));
			$PenaltyDescription->appendChild($Text);

			$RatePlanDescription = $doc->createElement("RatePlanDescription");
			$RatePlan->appendChild($RatePlanDescription);
			$Text = $doc->createElement("Text");
			$Text->nodeValue  = isset($rateTypeDescr[$i]) ? htmlspecialchars($rateTypeDescr[$i]) : '';
			$RatePlanDescription->appendChild($Text);

			# Room Rates
			$RoomRate = $doc->createElement("RoomRate");
			$RoomRate->setAttribute("RoomID", $n); // New
			$RoomRates->appendChild($RoomRate);
			$Rates = $doc->createElement("Rates");
			$RoomRate->appendChild($Rates);

			if(isset($rateValue[$i])){
				if(preg_match('/\A\s*(\d+.\d+|\d+)\s*\Z/ims', $rateValue[$i], $matches)){
					$amountBeforeTax = floatval(str_replace(',','',$matches[1]));

					$Rate = $doc->createElement("Rate");
					$Rate->setAttribute("ChargeType", '19');
					$Rates->appendChild($Rate);

					$Base = $doc->createElement("Base");
					$Base->setAttribute("AmountBeforeTax", $amountBeforeTax);
					$Base->setAttribute("CurrencyCode", isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');
					$Rate->appendChild($Base);
				} else {
					$Rate = $doc->createElement("Rate");
					$Rate->setAttribute("ChargeType", '14');
					$Rates->appendChild($Rate);

					$RateDescription = $doc->createElement("RateDescription");
					$Rate->appendChild($RateDescription);

					$Text = $doc->createElement("Text");
					$Text->nodeValue  = $rateValue[$i];
					$RateDescription->appendChild($Text);
				}
			}
			else{
				$Rate = $doc->createElement("Rate");
				$Rate->setAttribute("ChargeType", '19');
				$Rates->appendChild($Rate);

				$Base = $doc->createElement("Base");
				if(isset($roomCost[0]))
					$Base->setAttribute("AmountBeforeTax", array_sum($roomCost));
				else
					$Base->setAttribute("AmountBeforeTax", 0);
				$Base->setAttribute("CurrencyCode", isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');
				$Rate->appendChild($Base);
			}

			$totalEl = $doc->createElement("Total");

			if(isset($totalSum)){
				if(isset($roomCost[$i])){
					if(preg_match('/(\d+.\d+|\d+)/ims', $roomCost[$i], $matches))
						$amountBeforeTax = floatval($matches[1]);
					else
						$amountBeforeTax = 0;

					$totalEl->setAttribute("AmountBeforeTax", $amountBeforeTax);
					($numRooms == 1) ? $amountAfterTax = $totalSum : $amountAfterTax = $amountBeforeTax;
					$totalEl->setAttribute("AmountAfterTax", $amountAfterTax);
					$totalEl->setAttribute("CurrencyCode", isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');
				}
				else{
					$totalEl->setAttribute("AmountAfterTax", 0);
					$totalEl->setAttribute("CurrencyCode", (isset($this->extProperties['Currency'])) ? $this->extProperties['Currency'] : 'USD' );
				}
			}
			$RoomRate->appendChild($totalEl);

			$Taxes = $doc->createElement("Taxes");
			$totalEl->appendChild($Taxes);

			$repl = Array('%', ' ');
			if(isset($roomTaxes[$i])){
				$Tax = $doc->createElement("Tax");
				if(preg_match('/%/im', $roomTaxes[$i], $temp)){
					$Tax->setAttribute("Percent", str_replace($repl, '', $roomTaxes[$i]));
				}
				else{
					//if(isset($roomTaxes[$i]))
					$Tax->setAttribute("Amount", floatval(trim($roomTaxes[$i])));
				}

				$Tax->setAttribute("CurrencyCode", isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');

				$Taxes->appendChild($Tax);

                $TaxDescription = $doc->createElement("TaxDescription");
                $Tax->appendChild($TaxDescription);

                $Text = $doc->createElement("Text");
				$Text->nodeValue  = isset($this->extProperties['TaxDescription']) ? $this->extProperties['TaxDescription'] : '';
                $TaxDescription->appendChild($Text);
			}

			# Guest Counts
			if(!isset($roomAdults[$i]))
				$roomAdults[$i] = 0;
			if(!isset($roomKids[$i]))
				$roomKids[$i] = 0;

			if(intval($roomAdults[$i]) > 0){
				if (isset($roomNames) && intval($roomAdults[$i]) == intval(count($roomNames))) {
                    foreach ($roomNames as $id => $roomNameValue) {
                        $GuestCount = $doc->createElement("GuestCount");
                        $GuestCount->setAttribute("AgeQualifyingCode", 10); // 10 - adult
                        $GuestCount->setAttribute("Count", 1);
                        $GuestCount->setAttribute("ResGuestRPH", $id+1);
                        $GuestCounts->appendChild($GuestCount);
                    }
                } else {
                    $GuestCount = $doc->createElement("GuestCount");
                    $GuestCount->setAttribute("AgeQualifyingCode", 10); // 10 - adult
                    $GuestCount->setAttribute("Count", $roomAdults[$i]);
                    if (isset($roomNames))
                        $GuestCount->setAttribute("ResGuestRPH", $n); // New
                    $GuestCounts->appendChild($GuestCount);
                }
			}
			if(intval($roomKids[$i]) > 0){
				$Guests = $doc->createElement("GuestCount");
				$Guests->setAttribute("AgeQualifyingCode", 8); // 8 - child
				$Guests->setAttribute("Count", $roomKids[$i]);
				//$Guests->setAttribute("ResGuestRPH", $n); // New
				$GuestCounts->appendChild($Guests);
			}

            // GuestNames
            /*/ placeholders
            if (isset($roomNames)) {
                foreach ($roomNames as $id => $roomNameValue) {
                    $roomNamesRPHs = $doc->createElement("ResGuestRPHs");
                    $RoomStay->appendChild($roomNamesRPHs);
                    $roomNamesRPH = $doc->createElement("ResGuestRPH");
                    $roomNamesRPH->setAttribute("RPH", $id+1); // New
                    $roomNamesRPHs->appendChild($roomNamesRPH);
                }
            }
            /*/
		}

		if($GuestCounts->childNodes->length > 0)
			$RoomStay->appendChild($GuestCounts);


		//////////////////////////////////
		///////// TimeSpan ///////////////
		//////////////////////////////////
		$TimeSpan = $doc->createElement("TimeSpan");
		$TimeSpan->setAttribute("Start", $this->row['CheckInDate']);
		$TimeSpan->setAttribute("End", $this->row['CheckOutDate']);
		$RoomStay->appendChild($TimeSpan);

		# TotalCharge
		$totalCharge = $doc->createElement('Total');
		$taxes = $doc->createElement('Taxes');
		/**
		 * we've hardcoded this node for now because we have just one tax so we just duplicate the tax above
		 *
		if(isset($this->extProperties['Taxes']) && !empty($this->extProperties['Taxes'])){
			$tax = $doc->createElement('Tax');
			if($numRooms==1){
				if(preg_match('/%/im', $this->extProperties['Taxes'], $temp)){
					$tax->setAttribute('Percent', str_replace($repl, '', $roomTaxes[0]));
				}
				else{
					$tax->setAttribute('Amount', isset($this->row['Taxes']) ? floatval(trim($this->row['Taxes'])) : 0);
				}
			}
			else{
				if(preg_match('/%/im', $roomTaxes[0], $temp)){
					$tax->setAttribute('Percent', str_replace($repl, '', $roomTaxes[0]));
				}
				else{
					$tax->setAttribute('Amount', isset($roomTaxes[0]) ? array_sum($roomTaxes) : 0);
				}
			}
			$tax->setAttribute('CurrencyCode', isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');
			$taxDescr = $doc->createElement('TaxDescription');
			$taxDescrText = $doc->createElement('Text');
			$taxDescrText->nodeValue = '';
			$taxDescr->appendChild($taxDescrText);
			$tax->appendChild($taxDescr);
			$taxes->appendChild($tax);
		}
		 *
		 */
        if(isset($roomTaxes[0])){
			$Tax = $doc->createElement("Tax");
			if(preg_match('/%/im', $roomTaxes[0], $temp)){
				$Tax->setAttribute("Percent", str_replace($repl, '', $roomTaxes[0]));
			}
			else{
				//if(isset($roomTaxes[0]))
				$Tax->setAttribute("Amount", floatval(trim($roomTaxes[0])));
			}

			$Tax->setAttribute("CurrencyCode", isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');

			$taxes->appendChild($Tax);

            $TaxDescription = $doc->createElement("TaxDescription");
            $Tax->appendChild($TaxDescription);

            $Text = $doc->createElement("Text");
			$Text->nodeValue  = isset($this->extProperties['TaxDescription']) ? $this->extProperties['TaxDescription'] : '';
            $TaxDescription->appendChild($Text);
		}

		$totalCharge->appendChild($taxes);

		if(isset($roomCost[0]))
			$totalCharge->setAttribute('AmountBeforeTax', array_sum($roomCost));
		if(isset($totalSum))
			$totalCharge->setAttribute('AmountAfterTax', $totalSum);

		$totalCharge->setAttribute('CurrencyCode', isset($this->extProperties['Currency'])?$this->extProperties['Currency']:'USD');

		$RoomStay->appendChild($totalCharge);

		$keys = ['SpentAwards', 'EarnedAwards'];
		if(!empty(array_intersect($keys, array_keys($this->extProperties)))){
			$tpaExtensionNode = $this->doc->createElement('TPA_Extensions');
			$RoomStay->appendChild($tpaExtensionNode);
			foreach($keys as $key){
				$node = $this->doc->createElement($key, ArrayVal($this->extProperties, $key));
		  		$tpaExtensionNode->appendChild($node);
			}
		}

		///////////////////////////////////////////
		///////// BasicPropertyInfo ///////////////
		///////////////////////////////////////////
		$BasicPropertyInfo = $doc->createElement("BasicPropertyInfo");
		if(isset($this->row['ProviderCode'])){
			$brandName = $this->row['ProviderCode'];
			$BasicPropertyInfo->setAttribute("BrandName", $brandName);
		}
		if(isset($this->extProperties['2ChainName'])){
			$chainName = htmlspecialchars_decode($this->extProperties['2ChainName']);
			$BasicPropertyInfo->setAttribute("ChainName", $chainName);
		}
		if(isset($this->row['HotelName'])){
			$hotelName = htmlspecialchars_decode($this->row['HotelName']);
			$BasicPropertyInfo->setAttribute("HotelName", $hotelName);
		}
		$RoomStay->appendChild($BasicPropertyInfo);
		
		# Phone && Fax
		if (isset($this->row['Phone']) || isset($this->extProperties['Fax'])) {
			$ContactNumbers = $doc->createElement("ContactNumbers");
			if (isset($this->row['Phone'])) {
				$Phone = $doc->createElement("ContactNumber");
				$Phone->setAttribute("PhoneTechType", 1);
				$Phone->setAttribute("PhoneNumber", $this->row['Phone']);
				$ContactNumbers->appendChild($Phone);
			}
			if (isset($this->extProperties['Fax'])) {
				$Fax = $doc->createElement("ContactNumber");
				$Fax->setAttribute("PhoneTechType", 3);
				$Fax->setAttribute("PhoneNumber", $this->extProperties['Fax']);
				$ContactNumbers->appendChild($Fax);
			}
			
			$BasicPropertyInfo->appendChild($ContactNumbers);
		}

		// try to parse address
		$parsedAddress = false;
		$value = 0;
		GoogleGeoTagLimitOk($value);
		$translate = array(
			'AddressLine' => 'AddressLine',
			'City' => 'CityName',
			'State' => 'StateProv',
			'Country' => 'Country',
			'PostalCode' => 'PostalCode',
		);
		if (isset($this->row['Address']) /*&& preg_match('/\b(united states|us|usa|Russian Federation|russia|canada)\b/i', $this->row['Address'])*/ && $value < 2000) {
			// try to retrieve detailed address from geocoding
			$detailedAddress = FindGeoTag($this->row['Address']);
			$parsedAddress = !empty($detailedAddress['AddressLine']) && !empty($detailedAddress['City']) && !empty($detailedAddress['State']) && !empty($detailedAddress['Country']) /* && in_array($detailedAddress['Country'], ['United States', 'Russia', 'Canada'])*/;
		}

		// create Address node
		$Address = $doc->createElement("Address");
        $Address->setAttribute("UseType", 12); // 12 - Hotel Address
        if (isset($this->extProperties['DetailedAddress'])) {
            $detailedAddress = unserialize($this->extProperties['DetailedAddress']);
            $detailedAddress = $detailedAddress[0];

            foreach (array("AddressLine", "CityName", "PostalCode", "StateProv", "Country") as $key) {
                $node = $doc->createElement($key);
                $node->appendChild($doc->createTextNode(isset($detailedAddress[$key]) ? $detailedAddress[$key] : ''));
                $Address->appendChild($node);
                unset($node);
            }
		} elseif ($parsedAddress) {
			foreach (array_keys($translate) as $key) {
				$node = $doc->createElement($translate[$key]);
				$node->appendChild($doc->createTextNode(ArrayVal($detailedAddress, $key)));
				$Address->appendChild($node);
				unset($node);
			}
		} else {
			$AddressLine = $doc->createElement("AddressLine");
            ///unless we have address we add an empty address line
            $AddressLine->appendChild($doc->createTextNode(isset($this->row['Address']) ? $this->row['Address'] : ''));
            $Address->appendChild($AddressLine);
        }
        $BasicPropertyInfo->appendChild($Address);

		return $RoomStays;
	}

    private function createResGuests(DOMDocument $doc) {

        $ResGuests = $doc->createElement("ResGuests");

        if (isset($this->extProperties['GuestNames'])) {
            $roomNames = explode('|', str_replace(',', '|', $this->extProperties['GuestNames']));
            if (!empty($roomNames) && !is_array($roomNames))
                $roomNames = array($roomNames);

            foreach ($roomNames as $id => $roomNameValue) {
                $ResGuest = $doc->createElement("ResGuest");
                $ResGuest->setAttribute("ResGuestRPH", $id + 1);

                $Profiles = $doc->createElement("Profiles");
                $ProfileInfo = $doc->createElement("ProfileInfo");
                $Profile = $doc->createElement("Profile");
                $Profile->setAttribute("ProfileType", 1); //code 1 = Customer
                $Customer = $doc->createElement("Customer");

                $Customer->appendChild($this->createPersonName(trim($roomNameValue)));

                $Profile->appendChild($Customer);
                $ProfileInfo->appendChild($Profile);
                $Profiles->appendChild($ProfileInfo);
                $ResGuest->appendChild($Profiles);


                $ResGuests->appendChild($ResGuest);
            }
        }
        return $ResGuests;
    }

    private function createPersonName($name) {
        $PersonName = $this->doc->createElement("PersonName");
        $name = preg_replace("/\s+/ims", " ", $name);
        $name = preg_replace("/\./ims", "", $name);
        $parts = explode(" ", $name);
		$titles = array("mr", "ms", "mrs", "dr");
        if (count($parts) > 1)
            if (in_array(strtolower($parts[0]), $titles)){
				$title = array_shift($parts);
                $PersonName->appendChild($this->doc->createElement("NameTitle", $title));
				$parts = array_filter($parts, function($value) use ($title) { return strtolower($value) != strtolower($title); });
			}
        if (count($parts) > 0)
            $PersonName->appendChild($this->doc->createElement("GivenName", array_shift($parts)));
        if (count($parts) > 1)
            $PersonName->appendChild($this->doc->createElement("MiddleName", array_shift($parts)));
        if (count($parts) > 0)
			$PersonName->appendChild($this->doc->createElement("Surname", implode(" ", $parts)));
        return $PersonName;
    }

    private function createResGlobalInfo(DOMDocument $doc){
		$ResGlobalInfo = $doc->createElement("ResGlobalInfo");

		$HotelReservationIDs = $doc->createElement("HotelReservationIDs");
		$ResGlobalInfo->appendChild($HotelReservationIDs) ;

		$HotelReservationID  = $doc->createElement("HotelReservationID");
		$HotelReservationID->setAttribute("ResID_Type", '40');
		$HotelReservationID->setAttribute("ResID_Value", isset($this->row['ConfirmationNumber'])?$this->row['ConfirmationNumber']:'');
		$HotelReservationIDs->appendChild($HotelReservationID) ;

		$HotelReservationID  = $doc->createElement("HotelReservationID");
		$HotelReservationID->setAttribute("ResID_Type", '34');
		$HotelReservationID->setAttribute("ResID_Value", isset($this->row['ReservationID'])?$this->row['ReservationID']:'');
		$HotelReservationIDs->appendChild($HotelReservationID) ;


		return $ResGlobalInfo;
	}

	private function createTPA_Extensions(DOMDocument $doc){
		$TPA_Extensions = $doc->createElement("TPA_Extensions");

		// @TODO: remove TC extensions
		$TravelConfirm = $doc->createElement("TravelConfirm");
		$TPA_Extensions->appendChild($TravelConfirm) ;

		$this->addExtProperties($TPA_Extensions, $this->extProperties, array(
			'RoomType', 'RoomTypeDescription', 'CancellationPolicy', 'RateType', 'Rate', 'Guests', 'Kids', 'Taxes', 'Cost', 'Total', 'Rooms', 'Currency',
			'Fax', '2ChainName', 'TaxDescription', 'DetailedAddress'
		));

		$PartnerCode  = $doc->createElement("PartnerCode");
		$PartnerCode->nodeValue  = 'AwardWallet';
		$TravelConfirm->appendChild($PartnerCode) ;

		$Reservation  = $doc->createElement("Reservation");
		$Reservation->setAttribute("DateTime", isset($this->row['CreateDate'])?$this->row['CreateDate']:'');
		$TravelConfirm->appendChild($Reservation) ;

		$CustomerInfo  = $doc->createElement("CustomerInfo");
		$CustomerInfo->setAttribute("Type", 'A');

		if(isset($this->row['LastLogonIP'])){
			$IpArray = explode('.',$this->row['LastLogonIP']);
			if(count($IpArray) >= 4){
				$IpArray[count($IpArray) - 1] = '0';
				$SecureIp = implode('.', $IpArray);
				$CustomerInfo->setAttribute("LastUsedIPAddress", $SecureIp);
	        }
		}
		$TravelConfirm->appendChild($CustomerInfo) ;

		return $TPA_Extensions;
	}
}
