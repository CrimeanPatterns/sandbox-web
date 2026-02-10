<?

class TBaseAffiliateStatViewer
{
	private $FilterForm;
	private $StatDateFilter;
	private $TranDateFilter;
	
	function Draw()
	{
		global $QS;
		$this->FilterForm = $this->CreateFilterForm();
		if( ArrayVal( $QS, "submitButton" ) != "" )
			$this->FilterForm->Check( $QS );
		echo $this->FilterForm->HTML();
		if( !isset( $this->FilterForm->Error ) )
		{
			$this->StatDateFilter = $this->FilterForm->SQLDateFilter( "StatDate" );
			$this->TranDateFilter = $this->FilterForm->SQLDateFilter( "CreationDate" );
			$this->DrawStats();
		}
	}
	
	function DrawStats()
	{
		echo "Override TBaseAffiliateStatViewer->DrawStats() to show statistics";
	}
	
	function CreateFilterForm()
	{
		return new TBaseAffiliateFilterForm();
	}
	
	function CalcStats( $nHitKind, $nItemKind, &$nCount, &$sLastDate )
	{
		global $Connection;
		$sSQL = "select count(*) as Cnt, max( StatDate ) as LastDate from AffStat where UserID = {$_SESSION['UserID']} and {$this->StatDateFilter} and HitKind = $nHitKind" ;
		if( isset( $nItemKind ) )
			$sSQL .= " and ItemKind = $nItemKind";
		//echo $sSQL . "<hr>";
		$q = new TQuery( $sSQL );
		$nCount = $q->Fields["Cnt"];
		if( $q->Fields["LastDate"] != "" )
			$sLastDate = date( DATE_TIME_FORMAT, $Connection->SQLToDateTime( $q->Fields["LastDate"] ) );
		else
			$sLastDate = "&nbsp;";
	}

	function CalcTransactions( $arTranKind, $nItemKind, &$nCount, &$nCommission, &$sLastDate )
	{
		global $Connection;
		$sSQL = "select count(*) as Cnt, max( CreationDate ) as LastDate, sum( Commission ) as Commission from AffTransaction where UserID = {$_SESSION['UserID']} and {$this->TranDateFilter} and Kind in ( " . implode( ", ", $arTranKind ) . " )";
		if( isset( $nItemKind ) )
			$sSQL .= " and ItemKind = $nItemKind";
		//echo $sSQL . "<hr>";
		$q = new TQuery( $sSQL );
		$nCount = $q->Fields["Cnt"];
		$nCommission = $q->Fields["Commission"];
		if( !isset( $nCommission ) )
			$nCommission = 0;
		if( $q->Fields["LastDate"] != "" )
			$sLastDate = date( DATE_TIME_FORMAT, $Connection->SQLToDateTime( $q->Fields["LastDate"] ) );
		else
			$sLastDate = "&nbsp;";
	}
}

?>
