<?

class TBaseAffiliateTranList extends TBaseList
{
	function __construct()
	{
		parent::__construct( "AffTransaction", array(
			"CreationDate" => array(
				"Caption" => "Date",
				"Type" => "datetime",
			),
			"Product" => array(
				"Type" => "string",
				"Size" => 80,
			),
			"Cost" => array(
				"Caption" => "Product Cost",
				"Type" => "money",
			),
			"Commission" => array(
				"Type" => "money",
			),
			"Comments" => array(
				"Type" => "string",
				"Size" => 80,
			),
			"Balance" => array(
				"Type" => "money",
			),
		), "CreationDate" );
		$this->ReadOnly = True;
		$this->ShowFilters = True;
		//$this->FilterForm = $this->CreateFilterForm();
	}
	
	function CreateFilterForm()
	{
		global $QS;
		$this->FilterForm = new TBaseAffiliateFilterForm();
		if( ArrayVal( $QS, "submitButton" ) != "" )
			$this->FilterForm->Check( $QS );
	}
	
	function GetFilters($filterType = "where")
	{
		if($filterType == "where")
			return $this->FilterForm->SQLDateFilter( "CreationDate" ) . " and UserID = {$_SESSION['UserID']}";
		else
			return parent::GetFilters($filterType);
	}

	// draw filter form. override to show custom filters
	// unset shown values from $arHiddens
	function DrawFiltersForm( &$arHiddens )
	{
		echo $this->FilterForm->HTML();
		unset( $arHiddens['StartDate'] );
		unset( $arHiddens['EndDate'] );
		unset( $arHiddens['LastRange'] );
		unset( $arHiddens['RangeMode'] );
		unset( $arHiddens['submitButton'] );
		unset( $arHiddens['submitButtonTrigger'] );
		unset( $arHiddens['DisableFormScriptChecks'] );
	}

	// show active filters
	function DrawFilters()
	{
		global $QS;
		echo "<form method=get action={$_SERVER['SCRIPT_NAME']} name=editor_form>\n";
		$arHiddens = $QS;
		$this->DrawListLinks( $arHiddens );
		$this->DrawFiltersForm( $arHiddens );
        $arHiddens['FormToken'] = GetFormToken();
		DrawHiddens( $arHiddens );
		echo "</form>\n";
	}
	
	// draw list header, called if list is not empty
	function DrawHeader()
	{
		$this->DrawFilters();
		if($this->showTopNav)
			$this->drawPageDetails("bottom", True);
?>
		<table cellspacing="0" cellpadding="3" border="0"<?=$this->tableParams?>">
<tr bgcolor="<?=$this->headerColor?>">
<?
		$this->DrawFieldHeaders();
		echo "</tr>\n";
		echo "<form method=post name=list_{$this->Table}>";
		echo "<input type=hidden name=action>\n";
	}
	
}

?>
