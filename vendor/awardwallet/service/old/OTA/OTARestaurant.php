<?
/**
 * Restaurant rental to OTA_HotelEventRS converter
 * Author: Sergey Reutov
 */
class OTARestaurant extends OTABase{

	protected $core;

	public function __construct($kind, $apiVersion){
		parent::__construct($kind, $apiVersion);
		$this->root = $this->doc->createElementNS('http://www.opentravel.org/OTA/2003/05', 'OTA_HotelEventRS');
		$this->doc->appendChild($this->root);
		$this->root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$this->root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://www.opentravel.org/OTA/2003/05 OTA_HotelEventRS.xsd');
		$this->root->setAttribute('Version', '3.1415926535897932384626433832795');
		//$this->root->setAttribute('TransactionIdentifier', $this->row['ID']);
		$this->root->appendChild($this->doc->createElement("Success"));
		$this->core = $this->doc->createElement("Events");
		$this->root->appendChild($this->core);
	}

	public function addReservation($id){
		global $Connection;
		if(parent::addReservation($id) === false)
			return false;

		if (isset($this->extProperties['Cancelled']) && $this->extProperties['Cancelled'] == true) {
			$this->CancelledIt($this->root, $this->row['ConfNo']);
			return $this->root;
		}
		
		$this->row['StartDate'] = date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['StartDate']));
		$event = $this->doc->createElement("Event");
		$event->setAttribute("Type", $this->row["EventType"]);
		$event->setAttribute("Name", $this->row["Name"]);
		$this->core->appendChild($event);
		$event->appendChild($this->createContacts($this->doc));
	//	$event->appendChild($this->createAttendeeInfo($this->doc));
		$event->appendChild($this->createEventInfos($this->doc));
	//	$event->appendChild($this->createSites($this->doc));
		return $this->root;
	}
	
	private function createContacts(DOMDocument $doc){
		$contacts = $doc->createElement("Contacts");
		
		$host = $doc->createElement("Contact");
		$host->setAttribute("ContactType", "EventHost");
		
		$phone = $doc->createElement("Telephone");
		$phone->setAttribute("PhoneNumber", $this->row['Phone']);
		$phone->setAttribute("PhoneTechType", 1);
		$host->appendChild($phone);
		
		$address = $doc->createElement("Address");
		$addressLine = $doc->createElement("AddressLine");
		$addressLine->nodeValue = $this->row['Address'];
		$address->appendChild($addressLine);
		$host->appendChild($address);
		
		
		$companyName = $doc->createElement("CompanyName");
		$companyName->nodeValue = $this->row['ProviderCode'];
		$host->appendChild($companyName);
		
		$contacts->appendChild($host);
		
		return $contacts;
	}
	
	private function createEventInfos(DOMDocument $doc){
		global $Connection;
		$evInfos = $doc->createElement("EventInfos");
		
		$ev = $doc->createElement("EventInfo");
		$ev->setAttribute("Start", $this->row['StartDate']);
		if (isset($this->row['EndDate']))
		{
			$ev->setAttribute("End", date(OTABase::DATE_OTA, $Connection->SQLToDateTime($this->row['EndDate'])));
		}
		
		$comments = $doc->createElement("Comments");
		
		$confCode = $doc->createElement("Comment");
		$confCode->setAttribute("Name", "ConfNo");
		$text = $doc->createElement("Text");
		$text->nodeValue = $this->row['ConfNo'];
		$confCode->appendChild($text);
		$comments->appendChild($confCode);
		
		if (isset($this->extProperties['DinerName'])) {
			$contacts = $doc->createElement("Contacts");
			$contact = $doc->createElement("Contact");
			$contacts->appendChild($contact);
			$contact->setAttribute("ContactType", "DinerName");
			$personName = $this->createPersonName($this->extProperties['DinerName']);
			$contact->appendChild($personName);
			$ev->appendChild($contacts);
		}
		
		if (isset($this->row['Notes']))
		{
			$singleComment = $doc->createElement("Comment");
			$singleComment->setAttribute("Name", "Notes");
			$text = $doc->createElement("Text");
			$text->nodeValue = $this->row['Notes'];
			$singleComment->appendChild($text);
			$comments->appendChild($singleComment);
		}
		
		if (isset($this->extProperties))
		{
			foreach($this->extProperties as $key => $value)
			{
				$comm = $doc->createElement("Comment");
				$comm->setAttribute("Name", $key);
				$text = $doc->createElement("Text");
				$text->nodeValue = $value;
				$comm->appendChild($text);
				$comments->appendChild($comm);
			}
		}
		
		$ev->appendChild($comments);
		
		$evInfos->appendChild($ev);
		
		return $evInfos;
	}
	
	private function createPersonName($name){
		$PersonName = $this->doc->createElement("PersonName");
		$name = preg_replace("/\s+/ims", " ", $name);
		$name = preg_replace("/\./ims", "", $name);
		$parts = explode(" ", $name);
		if(count($parts) > 1)
			if(in_array(strtolower($parts[0]), array("mr", "ms", "mrs", "dr", "jr")))
				$PersonName->appendChild($this->doc->createElement("NamePrefix", array_shift($parts)));
		if(count($parts) > 0)
			$PersonName->appendChild($this->doc->createElement("GivenName", array_shift($parts)));
		if(count($parts) > 1)
			$PersonName->appendChild($this->doc->createElement("MiddleName", array_shift($parts)));
		if(count($parts) > 0)
			$PersonName->appendChild($this->doc->createElement("Surname", implode(" ", $parts)));
		return $PersonName;
	}
}