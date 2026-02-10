<?

class TBaseAffiliateFilterForm extends TForm
{
	function TBaseAffiliateFilterForm()
	{
		parent::TForm(
			array(
				"RangeMode" => array(
					"Type" => "string",
					"Options" => array( "last" => "", "range" => "" ),
					"Value" => "last",
					"InputType" => "radio",
					"Required" => True,
				),
				"LastRange" => array(
					"Type" => "string",
					"Options" => array( 
						"1" => "Last Day",
						"7" => "Last 7 Days",
						"30" => "Last 30 Days",
						"365" => "Last 365 Days",
					),
					"InputType" => "select",
					"CheckScriptCondition" => "( radioValue( Form, 'RangeMode' ) == 'last' )",
					"Required" => True,
					"Value" => "7",
				),
				"StartDate" => array(
					"Type" => "date",
					"Required" => True,
					"Value" => date( DATE_FORMAT, time() - SECONDS_PER_DAY * 7 ),
					"CheckScriptCondition" => "( radioValue( Form, 'RangeMode' ) == 'range' )",
				),
				"EndDate" => array(
					"Type" => "date",
					"Required" => True,
					"Value" => date( DATE_FORMAT, time() ),
					"CheckScriptCondition" => "( radioValue( Form, 'RangeMode' ) == 'range' )",
				),
			)
		);
		$this->SubmitButtonCaption = "Apply Filters";
	}
	
	function FormatHTML( $sHTML, $bExistsRequired )
	{
		$this->CalendarLinked = False;
		$sHTML = "<div style='padding-top: 10px; padding-bottom: 10px;'>\n";
		if( isset( $this->Error ) )
			$sHTML = "<div class=formerror>" . $this->Error . "</div>\n";
		$sHTML .= "<form method=get name=\"editor_form\" style='margin-bottom: 0px; margin-top: 0px;' onsubmit='submitonce(this)'>
<input type='hidden' name='FormToken' value='" . GetFormToken() . "'>
<input type=hidden name=DisableFormScriptChecks value=0>
<input type=hidden name=submitButton>
<table cellspacing=0 cellpadding=5 border=0 class=detailsTable width='100%'>
<tr>
	<td valign=top><div style='padding-top: 2px;'>Time Period:</div></td>
	<td nowrap>
<input type=radio name=RangeMode value=last" . ( $this->Fields["RangeMode"]["Value"] == 'last' ? " checked" : "" ) . " onclick='RangeModeChanged( this.form )'> " . $this->InputHTML( "LastRange" ) . "<br>
<input type=radio name=RangeMode value=range" . ( $this->Fields["RangeMode"]["Value"] == 'range' ? " checked" : "" ) . " onclick='RangeModeChanged( this.form )'> From: " .	$this->InputHTML( "StartDate" ) . " To: " .  $this->InputHTML( "EndDate" ) . "
	</td>
</tr>
<tr>
	<td colspan=2 align=center>" . $this->ButtonsHTML() . "</td>
</tr>
</table>
		</form>
		<script>
		function RangeModeChanged( form )
		{
			if( radioValue( form, 'RangeMode' ) == 'last' )
			{
				form.StartDate.disabled = true;
				form.EndDate.disabled = true;
				form.LastRange.disabled = false;
			}
			else
			{
				form.StartDate.disabled = false;
				form.EndDate.disabled = false;
				form.LastRange.disabled = true;
			}
		}
		RangeModeChanged( document.forms['editor_form'] );
		</script>
		</div>";
		return $sHTML;
	}
	
	function SQLDateFilter( $sField )
	{
		global $Connection;
		if( $this->Fields['RangeMode']['Value'] == 'last' )
		{
			$dEnd = time();
			$dStart = $dEnd - SECONDS_PER_DAY * intval( $this->Fields["LastRange"]["Value"] );
		}
		else 
		{
			$dStart = StrToDate( $this->Fields["StartDate"]["Value"] );
			$dEnd = StrToDate( $this->Fields["EndDate"]["Value"] );
		}
		return "$sField >= " . $Connection->DateTimeToSQL( $dStart ) . " and $sField < " . $Connection->DateTimeToSQL( $dEnd + SECONDS_PER_DAY );
	}
}

?>
