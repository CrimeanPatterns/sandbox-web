<?

require_once("$sPath/lib/classes/TBaseList.php");

class TInviteUserList extends TBaseList {

	var $UserID;
	var $Mode;
	// shown emails
	var $Emails = array();
	var $Title;
	var $MoreLink;

	function __construct( $sSQL, $nUserID, $sTitle ){
		parent::__construct( "Usr", array(
			"FirstName" => array(
				"Caption" => "First Name",
				"Type" => "string",
				"Sort" => "FirstName, LastName",
			),
			"LastName" => array(
				"Caption" => "Last Name",
				"Type" => "string",
				"Sort" => "LastName, FirstName",
			),
			"City" => array(
				"Caption" => "Location",
				"Type" => "string",
				"Sort" => "CountryName, StateName, City",
			),
		), "LastName" );
		$this->SQL = $sSQL;
		$this->UserID = $nUserID;
		$this->Title = $sTitle;
		$this->showTopNav = false;
		$this->EmptyListMessage = null;
		$this->PageSize = 20;
//		$this->Fields['LastName']['Sort'] = false;
//		$this->Fields['FirstName']['Sort'] = false;
//		$this->Fields['City']['Sort'] = 'UserID';
		$this->FormAction = "/ranch/list.php";
		$_SERVER['SCRIPT_NAME'] = "/ranch/list.php";
	}

	function DrawHeader(){
		global $Interface;
		echo $Interface->drawSectionDivider($this->Title);
		echo '<br>';
		echo '<table cellspacing="0" cellpadding="5" border="0" width="100%" class="detailsTableLight">';
	}

	function DrawFooter()
	{
		echo "</table><br>\n";
		if( $this->UsePages && ( $this->PageNavigator != "" ) )
			echo "<div align=\"right\"><a href=\"{$this->MoreLink}\">More matches »</a></div>";
	}

	// draw one row
	function DrawRow()
	{
		global $Interface;
		$q = &$this->Query;
		if( isset( $q->Fields["Email"] ) )
			$this->Emails[] = strtolower( $q->Fields["Email"] );
		if( ( $q->Position % 2 ) == 1 )
			$sBgColor = "#FAF9F5";
		else
			$sBgColor = "#FFFFFF";
	?>
	<tr bgcolor="<?=$sBgColor?>">
		
		<td><?=$q->Fields['FirstName']?> <?=$q->Fields['LastName']?> (<?=$q->Fields['Email']?>)</td>
	</tr>
	<?
	}

	function drawPageDetails($vAlign = "top", $bTop = True){
		global $QS;
		$ar = $QS;
		parent::drawPageDetails( $vAlign, $bTop );
		$QS = $ar;
	}

}

?>
