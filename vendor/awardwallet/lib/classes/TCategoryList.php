<?

class TCategoryList extends TBaseList{

	// constructor
	function __construct( $table, $fields, $defaultSort ){
		parent::__construct( $table, $fields, $defaultSort );
		global $QS;
		if( isset( $QS["PageSize"] ) )
		{
			$this->PageSize = intval( $QS["PageSize"] );
			if( ( $this->PageSize > 600 ) || ( $this->PageSize < 1 ) )
				$this->PageSize = 20;
		}
		else
			$this->PageSize = 20;
	}

	function DrawPageSizeSelect()
	{
		global $QS;
		echo "<form method=get style='margin-bottom: 0px; margin-top: 0px;' name=pagesize_form>Results per page: ";
		$ar = $QS;
		unset( $ar["PageSize"] );
	 	unset( $ar["Page"] );
		$objForm = new TBaseForm( array(
			"PageSize" => array(
				"Type" => "integer",
				"Required" => True,
				"InputAttributes" => " onchange=\"this.form.submit();\"",
				"Caption" => "Page size",
				"InputType" => "select",
				"Value" => $this->PageSize,
				"Options" => array( "10" => "10", "20" => "20", "30" => "30", "40" => "40", "50" => "50",  "100" => "100",  "150" => "150",  "300" => "300",  "600" => "600" ) ),
		) );
		echo $objForm->InputHTML("PageSize");
		DrawHiddens($ar);
		echo "<input type=hidden name=Posted value=1>\n";
		echo "</form>\r\n";
	}
}
?>
