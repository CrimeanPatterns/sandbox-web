<?

require_once(__DIR__ . "/../geoFunctions.php");

class TBaseAffiliateAdGenerator
{
	// supported ad formats. array( "FormatCode" => array( Format properties ), .. 
	public $Formats = array();
	// location of user, requesting ad
	public $CountryID;
	public $StateID;
	public $City;
	
	function TBaseAffiliateAdGenerator()
	{
		// set Formats in ancestor
	}
	
	// should return boolean
	function Authorize( $nUserID, $sFormat )
	{
		$q = new TQuery("select 1 from Usr where UserID = {$nUserID} and AffApproved = 1");
		return !$q->EOF && ( !isset( $sFormat ) || isset( $this->Formats[$sFormat] ) );
	}
	
	// should return ad html or empty string
	function GetAdHTML( $nUserID, $sFormat, $bShowFooter )
	{
		return "Override GetAdHTML in ancestor";
	}

}

?>
