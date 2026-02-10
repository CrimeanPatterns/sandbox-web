<?

class OTABase{

	const DATE_OTA = 'Y-m-d\TH:i:s';

	protected $row;
	protected $extProperties;
	protected $kind;
	protected $table;
	protected $apiVersion;
	/**
	 * @var \DOMDocument
	 */
	public $doc;
	protected $root;

	/**
	 * loads itinerary details
	 * @param  $itId
	 */
	public function __construct($kind, $apiVersion){
		global $arDetailTable;
		$this->kind = $kind;
		$this->table = $arDetailTable[$this->kind];
		$this->doc = new XDOMDocument('1.0', 'utf-8');
		$this->doc->formatOutput = true;
		$this->apiVersion = $apiVersion;
	}

	public function setReservationData(array $data){
		$this->row = $data;
		$this->extProperties = $this->row['ExtProperties'];
		unset($this->extProperties['Hash']);
		return false;
	}

	/**
	 * @return DOMDocument or false on failure
	 */
	public function addReservation($itId){
        $query = "select
			t.*,
			p.Code as ProviderCode,
			p.IATACode,
			p.Name as ProviderName,
			p.ShortName as ShortName";
        if (ConfigValue(CONFIG_TRAVEL_PLANS)){
            $query .= ",\nu.LastLogonIP\n";
        }
		$query .= " from {$this->table} t
			join Account a on t.AccountID = a.AccountID";
        if (ConfigValue(CONFIG_TRAVEL_PLANS)){
			$query .= "\n join Usr u on u.UserID = a.UserID\n";
        }
		$query .= " join Provider p on a.ProviderID = p.ProviderID
		where {$this->table}ID = $itId";
		$q = new TQuery($query);
		if($q->EOF)
            return false;

		$this->loadReservationDetails($q->Fields);
		$this->setReservationData($q->Fields);
		return $this->root;
	}

	protected function loadReservationDetails(array &$row){
		$row['ExtProperties'] = LoadExtProperties($this->table, $row["{$this->table}ID"]);
	}

	public function dumpInfo(){
		echo "<h2>Row:</h2>";
		echo "<pre>";
		echo htmlspecialchars(var_export($this->row, true));
		echo "</pre>";
	}

	protected function addExtProperties(DOMNode $Root, $properties, $ignoreKeys){
		foreach($ignoreKeys as $key)
			unset($properties[$key]);
		if(count($properties) > 0){
			$ExtProperties = $this->doc->createElement("ExtProperties");
			$Root->appendChild($ExtProperties);
			foreach($properties as $key => $value)
				$ExtProperties->setAttribute($this->cleanXMLAttribute($key), $value);
		}
	}
	
	protected function CancelledIt(DOMNode $Root, $Number) {
		$warnings = $this->doc->createElement("Warnings");
		$Root->appendChild($warnings);
		$warning = $this->doc->createElement("Warning");
		$warnings->appendChild($warning);
		$warning->setAttribute("Type", "Cancel");
		$warning->nodeValue = $Number;
	}
	
	protected function cleanXMLAttribute($str) {
		return preg_replace("/[^a-z0-9]/ims", "", $str);
	}
}