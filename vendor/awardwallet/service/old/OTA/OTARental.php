<?
/**
 * car rental to OTA_VehResRS converter
 */
class OTARental extends OTABase{

	protected $core;

	public function __construct($kind, $apiVersion){
		parent::__construct($kind, $apiVersion);
		$this->root = $this->doc->createElementNS('http://www.opentravel.org/OTA/2003/05', 'OTA_VehResRS');
		$this->doc->appendChild($this->root);
		$this->root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$this->root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://www.opentravel.org/OTA/2003/05 OTA_VehResRS.xsd');
		$this->root->setAttribute('Version', '2.001');
		//$this->root->setAttribute('TransactionIdentifier', $this->row['ID']);
		$this->root->appendChild($this->doc->createElement("Success"));
		$this->core = $this->doc->createElement("VehResRSCore");
		$this->root->appendChild($this->core);
	}

	public function addReservation($id){
		global $Connection;
		if(parent::addReservation($id) === false)
			return false;

		if (isset($this->extProperties['Cancelled']) && $this->extProperties['Cancelled'] == true) {
			$this->CancelledIt($this->root, $this->row['Number']);
			return $this->root;
		}
		
		$this->row['PickupDatetime'] = date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['PickupDatetime']));
		$this->row['DropoffDatetime'] = date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['DropoffDatetime']));
		$res = $this->doc->createElement("VehReservation");
		$this->core->appendChild($res);
		$res->appendChild($this->createSegCore($this->doc));
		$res->appendChild($this->createSegInfo($this->doc));
        if (isset($this->extProperties['RenterName'])) {
		    $res->appendChild($this->createCustomer($this->doc));
        }
		return $this->root;
	}

	private function createSegCore(DOMDocument $doc){
		$segCore = $doc->createElement("VehSegmentCore");

		// conf id
		$node = $doc->createElement("ConfID");
		$node->setAttribute("Type", "40");
		$node->setAttribute("ID", $this->row['Number']);
		$segCore->appendChild($node);
		
		$node = $doc->createElement("ConfID");
		$node->setAttribute("Type", "34");
		$node->setAttribute("ID", $this->row['RentalID']);
		$segCore->appendChild($node);

		// vendor
		$node = $doc->createElement("Vendor");
		if (isset($this->extProperties['RentalCompany']))
			$node->setAttribute("CompanyShortName", $this->extProperties['RentalCompany']);
		else
			$node->setAttribute("CompanyShortName", $this->row['ProviderCode']);
		$segCore->appendChild($node);

		// VehRentalCore
		$node = $doc->createElement("VehRentalCore");
		$node->setAttribute("PickUpDateTime", $this->row['PickupDatetime']);
		$node->setAttribute("ReturnDateTime", $this->row['DropoffDatetime']);
		$segCore->appendChild($node);
		$loc = $doc->createElement("PickUpLocation");
		$loc->setAttribute("LocationCode", "Loc1");
		$node->appendChild($loc);
		$loc = $doc->createElement("ReturnLocation");
		$loc->setAttribute("LocationCode", "Loc2");
		$node->appendChild($loc);

		// Vehicle
		if(isset($this->extProperties['CarType']) || isset($this->extProperties['CarModel']) || isset($this->extProperties['CarImageUrl'])){
			$node = $doc->createElement("Vehicle");
			if(isset($this->extProperties['CarType']))
				$node->setAttribute("Description", $this->extProperties['CarType']);
			$segCore->appendChild($node);
			if(isset($this->extProperties['CarModel'])){
				$model = $doc->createElement("VehMakeModel");
				$model->setAttribute("Name", $this->extProperties['CarModel']);
				$node->appendChild($model);
			}
			if(isset($this->extProperties['CarImageUrl'])){
				$carImage = $doc->createElement("PictureURL");
				$carImage->nodeValue = $this->extProperties['CarImageUrl'];
				$node->appendChild($carImage);
			}
		}

		/* rental rate
		$rate = $doc->createElement("RentalRate");
		$segCore->appendChild($rate);
		$charges = $doc->createElement("VehicleCharges");
		$rate->appendChild($charges);
		$charge = $doc->createElement("VehicleCharge");
		if(isset($this->row['TotalCharge']))
			$charge->setAttribute("Amount", $this->row['TotalCharge']);
		if(isset($this->row['Currency']))
			$charge->setAttribute("CurrencyCode", $this->row['Currency']);
		$charge->setAttribute("TaxInclusive", "false");
		$charge->setAttribute("Purpose", "1");
		$charges->appendChild($charge);
		$taxAmounts = $doc->createElement("TaxAmounts");
		$charge->appendChild($taxAmounts);		 

		$taxAmount = $doc->createElement("TaxAmount");
		$taxAmounts->appendChild($taxAmount);
		if(isset($this->row['TotalTaxAmount']))
			$taxAmount->setAttribute("Total", $this->row['TotalTaxAmount']);
		if(isset($this->row['Currency']))
			$taxAmount->setAttribute("CurrencyCode", $this->row['Currency']);
		$taxAmount->setAttribute("Description", "Tax with extras");
		 * 
		 */

		// priced equips
		$this->addPricedEquips($doc, $segCore);

		// fees
		$this->addFees($doc, $segCore);

		// total
		if(isset($this->extProperties['TotalCharge'])){
			$node = $doc->createElement("TotalCharge");
			$node->setAttribute("EstimatedTotalAmount", $this->extProperties['TotalCharge']);
			if(isset($this->extProperties['Currency']))
				$node->setAttribute("CurrencyCode", $this->extProperties['Currency']);
			$segCore->appendChild($node);
		}

		// tpa extensions
		$this->addTPAExtensions($doc, $segCore);

		return $segCore;
	}

	private function addPricedEquips(DOMDocument $doc, DOMNode $segCore){
		if(isset($this->extProperties['PricedEquips'])){
			$pricedEquips = $doc->createElement("PricedEquips");
			$segCore->appendChild($pricedEquips);
			foreach(unserialize($this->extProperties['PricedEquips']) as $row){
				if(isset($row['Key']) and $row['Key'] == 'More')
					continue;
				$pricedEquip = $doc->createElement("PricedEquip");
				$pricedEquips->appendChild($pricedEquip);
				$equipment = $doc->createElement("Equipment");
				$equipment->setAttribute("EquipType", "13"); // always GPS?
				
				$pricedEquip->appendChild($equipment);
				$description = $doc->createElement("Description");
				$description->nodeValue = htmlspecialchars(urldecode($row["Name"]));
				$equipment->appendChild($description);
				//$pricedEquip->appendChild($description);
				$charge = $doc->createElement("Charge");
				// Calcullation
				if(preg_match('/daily/', $row['Charge'])){
					$row['Charge'] = preg_replace(Array('/daily/', '/Included/'), Array('', '0'), $row['Charge']);
					$calcullation = $doc->createElement("Calculation");
					$calcullation->setAttribute('UnitName', 'Day');
					$charge->appendChild($calcullation);
				}
				$charge->setAttribute("Amount", floatval( str_replace(',', '', trim($row['Charge'])) ));
				if(isset($this->extProperties['Currency']) && preg_match('/([A-Z]{3})/', $this->extProperties['Currency']))
					$charge->setAttribute("CurrencyCode", $this->extProperties['Currency']);
				$charge->setAttribute("TaxInclusive", "false");
				$charge->setAttribute("IncludedInRate", "false");

				$pricedEquip->appendChild($charge);
			}
		}
	}


	private function addFees(DOMDocument $doc, DOMNode $segCore){
		if( isset($this->extProperties['Fees']) || ( isset($this->extProperties['TotalTaxAmount']) && isset($this->extProperties['Currency']) ) ){
			$fees = $doc->createElement("Fees");
			$segCore->appendChild($fees);

			if(isset($this->extProperties['Fees'])){
				foreach(unserialize($this->extProperties['Fees']) as $row){
					$fee = $doc->createElement("Fee");
					$row['Charge'] = preg_replace('/Included/', '0', $row['Charge']);

					$fee->setAttribute("Amount", $row['Charge']);
					if(isset($this->extProperties['Currency']))
						$fee->setAttribute("CurrencyCode", $this->extProperties['Currency']);
					$fee->setAttribute("TaxInclusive", "false");
					$fee->setAttribute("Description", $row['Name']);
					$fee->setAttribute("Purpose", "0");
					$fees->appendChild($fee);
				}
			}

			if(isset($this->extProperties['TotalTaxAmount']) && isset($this->extProperties['Currency'])){
				$fee = $doc->createElement("Fee");
				$fee->setAttribute("Amount", $this->extProperties['TotalTaxAmount']);
				$fee->setAttribute("CurrencyCode", $this->extProperties['Currency']);
				//$fee->setAttribute("Description", "Tax with extras");
				$fee->setAttribute("TaxInclusive", "true");
				$fee->setAttribute("Purpose", "7");
				$fees->appendChild($fee);
			}
		}
	}

	private function addTPAExtensions(DOMDocument $doc, DOMNode $segCore){
		$extensions = $doc->createElement("TPA_Extensions");
		$segCore->appendChild($extensions);
		$this->addExtProperties($extensions, $this->extProperties, array("Discounts", "CarType", "CarModel", "Currency", "TotalCharge", "Tax", "PricedEquips", "RenterName", "SpentAwards", "EarnedAwards"));
	}

	private function createSegInfo(DOMDocument $doc){
		$segInfo = $doc->createElement("VehSegmentInfo");
		$locations = array(
			'Loc1' => array(
  							'Location' => $this->row['PickupLocation'],
  							'Phone'    => $this->row['PickupPhone'],
  							'Fax'	   => (isset($this->row['ExtProperties']['PickupFax'])) ? $this->row['ExtProperties']['PickupFax'] : null,
  							'Hours'	   => $this->row['PickupHours'],
					  ),
	  		'Loc2' => array(
  							'Location' => $this->row['DropoffLocation'],
  							'Phone'    => $this->row['DropoffPhone'],
  							'Fax'	   => (isset($this->row['ExtProperties']['DropoffFax'])) ? $this->row['ExtProperties']['DropoffFax'] : null,
  							'Hours'	   => $this->row['DropoffHours'],
					  ),
		);
		foreach($locations as $key => $data){
			$details = $doc->createElement("LocationDetails");
			if (stripos($data['Location'], "airport") !== false || stripos($data['Location'], "airfield") !== false || preg_match('/\([A-Z]{3}\)/', $data['Location']))
                $details->setAttribute("AtAirport", "true");
            else
                $details->setAttribute("AtAirport", "false");
			$details->setAttribute("Code", $key);
			$details->setAttribute("Name", $key);
			$segInfo->appendChild($details);
			$addInfo = $doc->createElement("AdditionalInfo");
			$details->appendChild($addInfo);

			// Address
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
			if (isset($data['Location']) && $value < 2000) {
				// try to retrieve detailed address from geocoding
				$detailedAddress = FindGeoTag($data['Location'], $this->row['ShortName']);
				$fullAddress = true;
				foreach (array_keys($translate) as $key) {
					if (empty($detailedAddress[$key])) {
						$fullAddress = false;
					}
				}
				$parsedAddress = ($fullAddress);
			}

			// create Address node
			$Address = $doc->createElement("Address");
			if ($parsedAddress) {
				foreach (array_keys($translate) as $key) {
					$node = $doc->createElement($translate[$key]);
					$node->appendChild($doc->createTextNode(ArrayVal($detailedAddress, $key)));
					$Address->appendChild($node);
					unset($node);
				}
			} else {
				$AddressLine = $doc->createElement("AddressLine");
				///unless we have address we add an empty address line
				$AddressLine->appendChild($doc->createTextNode(isset($data['Location']) ? $data['Location'] : ''));
				$Address->appendChild($AddressLine);
			}
			$addInfo->appendChild($Address);

			$ext = $doc->createElement("TPA_Extensions");
			$addInfo->appendChild($ext);
			$ext->appendChild($doc->createElement("LocationData", $data['Location']));
			if (preg_match('/\((?<code>[A-Z]{3})\)/', $data['Location'], $matches))
				$ext->appendChild($doc->createElement("AirportCode", $matches['code']));
			# Phone
			if (isset($data['Phone']) && $data['Phone'] != '') {
				$telephone = $doc->createElement("Telephone");
				$telephone->setAttribute("PhoneTechType", 1);
				$telephone->setAttribute("PhoneNumber", $data['Phone']);
				$details->appendChild($telephone);
			}
			# Fax
			if (!is_null($data['Fax'])) {
				$fax = $doc->createElement("Telephone");
				$fax->setAttribute("PhoneTechType", 3);
				$fax->setAttribute("PhoneNumber", $data['Fax']);
				$details->appendChild($fax);
			}
			# Hours
			if (!is_null($data['Hours']) && $data['Hours'] != '') {
				$ext->appendChild($doc->createElement("Hours", $data['Hours']));
			}
		}
		return $segInfo;
	}

    private function createCustomer(DOMDocument $doc){
        $customer = $doc->createElement("Customer");

        if (isset($this->extProperties['RenterName'])) {
            $Primary = $doc->createElement("Primary");
            $Primary->appendChild($this->createPersonName(trim($this->extProperties['RenterName'])));
            $customer->appendChild($Primary);
        }

        return $customer;
    }

    private function createPersonName($name) {
        $PersonName = $this->doc->createElement("PersonName");
        $name = preg_replace("/\s+/ims", " ", $name);
        $name = preg_replace("/\./ims", "", $name);
        $parts = explode(" ", $name);
        if (count($parts) > 1)
            if (in_array(strtolower($parts[0]), array("mr", "ms", "mrs", "dr", "jr")))
                $PersonName->appendChild($this->doc->createElement("NameTitle", array_shift($parts)));
        if (count($parts) > 0)
            $PersonName->appendChild($this->doc->createElement("GivenName", array_shift($parts)));
        if (count($parts) > 1)
            $PersonName->appendChild($this->doc->createElement("MiddleName", array_shift($parts)));
        if (count($parts) > 0)
            $PersonName->appendChild($this->doc->createElement("Surname", implode(" ", $parts)));
        return $PersonName;
    }
}