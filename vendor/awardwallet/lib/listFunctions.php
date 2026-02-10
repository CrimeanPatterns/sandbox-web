<?

// -----------------------------------------------------------------------
// list function library
//		contains functions, used to display lists
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

// determine query match count and draw page navigation in style 1-15 of 434 displayed [Next page]
function TotalPageNavigator( &$q )
{
	global $PHP_SELF, $QS;
	// get total records
#	return "Page 1 of 5. Total results: 45.";
	$sSQL = preg_replace( "/\/\*fields\(\*\/.*\/\*\)fields\*\//is", "count(*) as Matches", $q->SQL );
	if( $sSQL == $q->SQL )
		DieTrace( "sql statement lacks '/*fields(*/', '/*)fields*/' marks" );
	$qTotal = new TQuery( $sSQL );
	$nTotal = $qTotal->Fields["Matches"];
	$nTotalPages = floor( $nTotal / $q->PageSize );
	if( ( $nTotal % $q->PageSize ) > 0 )
		$nTotalPages++;
	// link all together
	$nPage = $q->Page;
	if( !isset( $nPage ) )
		$nPage = ArrayVal( $QS, "Page", "1" );
	if( $nPage < 1 )
		$nPage = 1;
	if( $nPage > $nTotalPages )
		$nPage = $nTotalPages;
	$s = "Page {$nPage} of $nTotalPages. Total results: $nTotal.";
	$q->TotalPages = $nTotalPages;
	return $s;
}

// draws from with page size selector, submitting on change
function PageSizeSelector( &$q )
{
	global $objForm, $QS;
	echo "<form style='margin-bottom: 0px;' method='get'>Results per page: \r\n";
	$ar = $QS;
	unset( $ar["PageSize"] );
	$ar["Page"] = $q->Page;
		if( !isset( $objForm ) )
	{
		$objForm = new TForm( array(
			"PageSize" => array(
				"Type" => "integer",
				"Required" => True,
				"InputAttributes" => "style=\"width: 100px;\"",
				"Caption" => "Page size",
				"InputType" => "select",
				"Value" => "20",
				"Options" => array( "10" => "10", "20" => "20", "30" => "30", "40" => "40", "50" => "50" ) ),
		) );
		if( isset( $QS["Posted"] ) )
			$objForm->LoadPostData( $QS );
	}
	$objForm->Fields["PageSize"]["InputAttributes"] = "style=\"width: 100px;\" onchange=\"this.form.submit();\"";
	echo $objForm->InputHTML("PageSize");
	foreach( $ar as $sKey => $sValue )
	{
		if( is_array( $sValue ) )
		{
			$sKey = urlencode( $sKey . "[]" );
			$sValue = implode( ",", $sValue );
		}
		echo "<input type=hidden name=$sKey value=\"" . urlencode( $sValue ) . "\">\n";
	}
	echo "<input type=hidden name=Posted value=1>\n";
	echo "</form>\r\n";
}

?>