<?
class OTACancel extends OTABase{

	var $id;

	public function __construct($kind, $apiVersion){
		parent::__construct($kind, $apiVersion);
	}

	public function addReservation($conf){

		$query = "SELECT r.AccountID, r.Kind, p.Code as ProviderCode, p.Name as ProviderName
			FROM CancelledItinerary r
			JOIN Account a ON r.AccountID = a.AccountID
			JOIN Provider p ON a.ProviderID = p.ProviderID
			WHERE ConfirmationNumber = '$conf'";
		$q = new TQuery($query);
		if($q->EOF){
			///DieTrace("Itinerary not found", false);
			return false;
		}
		$this->row = $q->Fields;

		// root
		$root = $this->doc->createElementNS('http://www.opentravel.org/OTA/2003/05', 'OTA_CancelRS');
		$this->doc->appendChild($root);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://www.opentravel.org/OTA/2003/05 OTA_CancelRS.xsd');
		$root->setAttribute('Status', 'Cancelled');
		$root->setAttribute('Target', 'Production');
		$root->setAttribute('Version', '1.000');
		$root->setAttribute('SequenceNmbr', '1');
		$root->setAttribute('PrimaryLangID', 'en-us');
		$root->setAttribute('AltLangID', 'en-us');

		$root->appendChild($this->doc->createElement("Success"));

		$uniqueID = $this->doc->createElement("UniqueID");
		$uniqueID->setAttribute("Type", "14"); // reservation
		$uniqueID->setAttribute("ID", $conf);

		$company = $this->doc->createElement("CompanyName");
		$company->setAttribute("CompanyShortName", $this->row['ProviderName']);
		$uniqueID->appendChild($company);

		$root->appendChild($uniqueID);

		$tpa = $this->doc->createElement("TPA_Extensions");
		$tpa->setAttribute("ProviderCode", $this->row['ProviderCode']);
		$tpa->setAttribute("ItineraryKind", $this->row['Kind']);
		$root->appendChild($tpa);

		return $root;
	}

}