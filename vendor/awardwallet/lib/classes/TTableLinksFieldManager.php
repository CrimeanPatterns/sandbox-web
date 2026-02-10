<?php

// -----------------------------------------------------------------------
// Table Links Field manager class.
//		Contains class, to handle sub-tables
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once( __DIR__."/TAbstractFieldManager.php" );

class TTableLinksFieldManager extends TAbstractFieldManager
{
	var $TableName = NULL;			// sub-table
	var $KeyField = NULL;			// key field of sub-table, set automatically
	var $Fields = NULL;				// array of table fields, same format as TBaseForm
	var $SQLParams = NULL;			// assoc array of sql insert/update/delete statements
	var $SelectedOptions = array();	// array of arrays: selected rows
	var $SingleColumn = false;		// if set to true the output comes out in a single column
	var $ColumnWidth = 300;			// sets up the width of the table of results
	var $ItemCaption;				// caption of one item. f.x. "Registration"
	var $RelatedLink = false;		// if set to true, there should be only 1 integer field,
									// use to manage related(one-level) products
	var $UniqueFields;				// array of field names, used to determine row uniqueness. if null - all fields
	var $CanEdit = false;			// edit selected rows
	var $AutoSave = false;			// save not-empty edited option when user submits whole form
	var $MinCount;					// minimum required rows
	var $SaveOnAdd = false;
	/**
	 * @var Callable - function (array $insertedRow) : array - should return modified row
	 */
	public $onInsertRow;

	// initialize field
	function CompleteField()
	{
		parent::CompleteField();
		$this->Field["Database"] = False;
		if( !isset( $this->Fields ) )
			DieTrace( "TTableLinksFieldManager->CompleteField: Fields not set for field $this->FieldName" );
		if( !isset( $this->TableName ) )
			DieTrace( "TTableLinksFieldManager->CompleteField: TableName not set for field $this->FieldName" );
		$this->Field["InputType"] = "text";
		foreach( $this->Fields as $sOptionField => &$arOptionField )
			$this->Form->CompleteField( $sOptionField, $arOptionField );
	    if(!isset($this->KeyField))
		    $this->KeyField = $this->Form->KeyField;
		if( !isset( $this->UniqueFields ) )
			$this->UniqueFields = array_keys( $this->Fields );
	}

	function GetSQL()
	{
		if( $this->RelatedLink )
		{
			if( count( $this->Fields ) > 1 )
				DieTrace( "When RelatedLink is set to true, there should be only one field" );
			$sChildField = array_pop( array_keys( $this->Fields ) );
			$sSQL = "select $sChildField
			from {$this->TableName} where {$this->KeyField} = {$this->Form->ID}";
			if( isset( $this->SQLParams ) )
				$sSQL .= " and " . ImplodeAssoc( " = ", " and ", $this->SQLParams );
			$sSQL .= " union select {$this->KeyField} as $sChildField
			from {$this->TableName} where {$sChildField} = {$this->Form->ID}";
			if( isset( $this->SQLParams ) )
				$sSQL .= " and " . ImplodeAssoc( " = ", " and ", $this->SQLParams );
		}
		else
		{
			$fields = array();
			foreach($this->Fields as $fieldName => $field)
				if($field["Database"])
					$fields[] = "`" . $fieldName . "`";
			$sSQL = "select " . implode( ", ", $fields ) . "
			from {$this->TableName} where {$this->KeyField} = {$this->Form->ID}";
			if( isset( $this->SQLParams ) )
				$sSQL .= " and " . ImplodeAssoc( " = ", " and ", $this->SQLParams );
		}
		return $sSQL;
	}

	// set field values
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues ){

        if ($this->Form->ID != 0) {
            $arSelectedOptions = array();
            $q = new TQuery($this->GetSQL());
            while (!$q->EOF) {
                foreach ($this->Fields as $sOptionField => $arOptionField)
                    if (isset($arOptionField["Options"])
                        && !isset($arOptionField["Options"][$q->Fields[$sOptionField]]))
                        continue;
                $arSelectedOptions[] = $this->FormatRowToForm($q->Fields);
                $q->Next();
            }
            $this->SelectedOptions = $arSelectedOptions;
        }

		// remove??
        $arSelectedOptions = $this->SelectedOptions;
		foreach($arSelectedOptions as &$arRow )
		{
			foreach( $arRow as &$sValue )
				$sValue = urlencode( $sValue );
			$arRow = urlencode( implode( ",", $arRow ) );
		}

		$this->Field["Value"] = implode( ",", $arSelectedOptions );
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		foreach( $this->Fields as $sOptionField => $arOptionField )
			if(isset($arOptionField["Manager"]))
				$this->Fields[$sOptionField]["Manager"]->LoadPostData($arData);
		$nCount = min(100, intval(ArrayVal( $arData, $this->FieldName."Count", 0 )));
		$arSelectedOptions = array();
		for( $n = 0; $n < $nCount; $n++ )
		{
			$arRow = array();
			$bRecordValid = True;
			foreach( $this->Fields as $sOptionField => $arOptionField )
			{
				$arRow[$sOptionField] = ArrayVal($arData, $this->FieldName.$n.$sOptionField);
				if( isset( $arOptionField["Options"] ) && !isset( $arOptionField["Manager"] )
				&& !isset( $arOptionField["Options"][$arRow[$sOptionField]] ) )
				{
					$bRecordValid = False;
					break;
				}
			}
			foreach ($arRow as $fieldName => $val) {
				if ((!isset($this->Fields[$fieldName]['HTML']) || $this->Fields[$fieldName]['HTML'] == false) && !is_array($val)) {
					$arRow[$fieldName] = $this->smartEscape($val);
				}
			}
			if( $bRecordValid && !$this->RowExists( $arRow, $arSelectedOptions ) )
				$arSelectedOptions[] = $arRow;
		}
		$this->SelectedOptions = $arSelectedOptions;
		if( !isset( $this->Form->Error ) &&
		( ( ArrayVal( $arData, "{$this->FieldName}AddButton" ) != "" )
		|| ( ( ArrayVal( $arData, "{$this->FieldName}RemoveOption" ) != "" ) && in_array( $arData["{$this->FieldName}RemoveOption"], array_keys( $arSelectedOptions ) ) ) )
		){
			$this->Form->Error = $this->Check( $arData );
			if($this->SaveOnAdd && !$this->Form->IsInsert){
				$this->Form->WantSave = true;
			}
		}
		if( ( ArrayVal( $arData, "{$this->FieldName}RemoveOption" ) != "" ) && in_array( $arData["{$this->FieldName}RemoveOption"], array_keys( $arSelectedOptions ) ) )
			array_splice( $this->SelectedOptions, $arData["{$this->FieldName}RemoveOption"], 1 );
	}

	function smartEscape($value) {
		return htmlspecialchars(htmlspecialchars_decode($value));
	}

	// get selected options as array of text
	function SelectedOptionsText()
	{
		$arSelectedOptions = $this->SelectedOptions;
		$arResult = array();
		foreach( $arSelectedOptions as $arRow )
		{
			$arText = array();
			foreach( $this->Fields as $sOptionField => $arOptionField )
				if( isset( $arOptionField["Options"] ) )
					$arText[$sOptionField] = $arOptionField["Options"][$arRow[$sOptionField]];
				else
					if( $arRow[$sOptionField] != "" )
						$arText[$sOptionField] = $arRow[$sOptionField];
			$arResult[] = $arText;
		}
		return $arResult;
	}

	// return selected option text, $arText - array of fields
	function FormatSelectedText( $arText ){
		$result = "<table id='noBorder' cellspacing='0' cellpadding='5' border='0' class='valuesTable'>";
		foreach( $arText as $key => $value ){
			if (in_array($key, ['DepDate', 'ArrDate'])) {
                $ts = convertDateByFormatToUnix($value, true);
				false === $ts ?: $value = date(getDateTimeFormat(true)['full'], $ts); 
			}
			$result .= "<tr><td class='keyClass'>" . $this->Fields[$key]["Caption"] . ":</td><td class='valueClass'>" . $value . "</td></tr>";
		}
		$result .= "</table>";
		return $result;
#		return implode( ", ", $arText );
	}

	// get field html
	function InputHTML($sFieldName = null, $arField = null)
	{
		$arSelectedOptions = $this->SelectedOptions;
		// hidden fields
		$s = "<input type=hidden name={$this->FieldName}Count value=\"" . count( $arSelectedOptions ) . "\">\n";
		$s .= "<input type=hidden name={$this->FieldName}RemoveOption value=\"\">\n";
		// draw selected options
		if( count( $arSelectedOptions ) > 0 )
		{
			$s .= "<table cellspacing='0' cellpadding='5' border='0' class='detailsTable' width='{$this->ColumnWidth}'>\n";
			$n = 0;
			foreach( $arSelectedOptions as $arRow )
			{
				$arText = array();
				$sInputs = "";
				foreach( $this->Fields as $sOptionField => $arOptionField )
				{
					if( isset( $arOptionField["Options"] ) ){
#print "<script>alert('{$arRow[$sOptionField]}')</script>";
						if(isset($arOptionField["Options"][$arRow[$sOptionField]]))
							$arText[$sOptionField] = $arOptionField["Options"][$arRow[$sOptionField]];
						else
							$arText[$sOptionField] = $arRow[$sOptionField];
					}
					else{
						if( ArrayVal($arRow, $sOptionField) != "" )
							$arText[$sOptionField] = $arRow[$sOptionField];
					}

                    $value = ArrayVal($arRow, $sOptionField);
					if (isset($arOptionField['Type']) && 'date' == $arOptionField['Type']) {
                        $ts = convertDateByFormatToUnix($value, !empty($arOptionField['IncludeTime']));
                        $valueFormated = false === $ts ? $value : date(getDateTimeFormat(!empty($arOptionField['IncludeTime']))['full'], $ts);
                        $sInputs .= "<input type=hidden name=_{$this->FieldName}{$n}{$sOptionField} value=\"".htmlspecialchars($valueFormated)."\">\n";
					}
                    $sInputs .= "<input type=hidden name={$this->FieldName}{$n}{$sOptionField} value=\"" . htmlspecialchars($value) . "\">\n";
				}
				$s .= "<tr><td id={$this->FieldName}Row{$n}Cell0 style='border: 1px solid #C7C4BF;'>
				" . $this->FormatSelectedText( $arText ) . "</td>
				<td id={$this->FieldName}Row{$n}Cell1 width='75' align='center' style='border: 1px solid #C7C4BF;'>{$sInputs}";
				if( $this->CanEdit )
					$s .= "<a href='#' onclick=\"editTableLink{$this->FieldName}( $n ); return false;\">(Edit)</a>&nbsp;";
				$s .= "<a href='#' onclick=\"form = document.forms['{$this->Form->FormName}']; form.{$this->FieldName}RemoveOption.value=$n; form.DisableFormScriptChecks.value=1; form.submit(); return false;\">(Remove)</a></td></tr>\n";
				$n++;
			}
			$s .= "</table><br>\n";
		}
		// draw add option
		$s .= "<div id={$this->FieldName}Editor><table cellspacing='0' cellpadding='0' border='0' id='noBorder' style='border: 0px none; padding: 1px;' class='tableLinkedFields'>\n";
		foreach( $this->Fields as $sOptionField => &$arOptionField ){
			$s .= "<tr id='trAddOption$sOptionField'>";
			if(($arOptionField['Type'] == 'html') && isset($arOptionField['IncludeCaption']) && ($arOptionField['IncludeCaption'] === false)){
				$s .= $this->Form->InputHTML( $this->FieldName . "AddOption" . $sOptionField, $arOptionField, False );
			}
			else{
				if( $arOptionField["Caption"] != "" ){
					$s .= "<td class='tlCaption'>" . $arOptionField["Caption"] . ": &nbsp;&nbsp;</td>";
					if($this->SingleColumn)
						$s .= "</tr><tr>";
				}
				$s .= "<td class='tlValue'>";
				if(isset($arOptionField["Manager"]))
					$s .= $arOptionField["Manager"]->InputHTML($this->FieldName."AddOption".$sOptionField, $arOptionField);
				else
					$s .= $this->Form->InputHTML( $this->FieldName . "AddOption" . $sOptionField, $arOptionField, False );
				if(isset($arOptionField["Note"]))
					$s .= "<br><span class='fieldhint'>" . $arOptionField["Note"] . "</span>";
				 $s .= "</td></tr>\n";
			}
		}
		$sCaption = "Add";
		if( isset( $this->ItemCaption ) )
			$sCaption .= " " . $this->ItemCaption;
		$sScript = "";
		$s .= "<tr><td align='center' colspan='2' height='24'>
			<input type=hidden name={$this->FieldName}AddButton>
			<input class='button' type=button name={$this->FieldName}AddButtonTrigger
			id={$this->FieldName}AddButtonTrigger
			value=\"$sCaption\" onclick=\"saveTableLinks{$this->FieldName}(this.form)\">
		</td></tr>\n";
		$s .= "</table></div>\n";
		$s .= "<script>

		var {$this->FieldName}activeRow = -1;

		function editTableLink{$this->FieldName}( n ){
		    if(typeof(allDatepickers) != 'undefined') activateDatepickers('destroy');
			var editor;
			var cell0;
			var cell1;
			var form = document.forms['{$this->Form->FormName}'];
			if({$this->FieldName}activeRow >= 0){
				cell0 = document.getElementById('{$this->FieldName}Row'+{$this->FieldName}activeRow+'Cell0');
				editor = cell0;
			}
			else{
				editor = document.getElementById('{$this->FieldName}Editor');
			}
			var html = editor.innerHTML;
			if({$this->FieldName}activeRow >= 0)
				saveActiveRow{$this->FieldName}(form, false);
			else
				editor.innerHTML = '';
			cell0 = document.getElementById('{$this->FieldName}Row'+n+'Cell0');
			cell1 = document.getElementById('{$this->FieldName}Row'+n+'Cell1');
			cell1.style.display = 'none';
			cell0.colSpan = 2;
			cell0.innerHTML = html;			
			document.getElementById('{$this->FieldName}AddButtonTrigger').value = 'Save';
			{$this->FieldName}activeRow = n;
		";
		foreach( $this->Fields as $sOptionField => &$arOptionField ){
			if($arOptionField["InputType"] == "checkbox"){
				$s .= "if(form['{$this->FieldName}'+n+'{$sOptionField}'].value == '1')
					form['".$this->FieldName . "AddOption" . $sOptionField."'].checked = true;
				else
					form['".$this->FieldName . "AddOption" . $sOptionField."'].checked = false;";
			}
			else{
				$s .= "form['".$this->FieldName . "AddOption" . $sOptionField."'].value = unescapeHTML(form['{$this->FieldName}'+n+'{$sOptionField}'].value);\n";
				if(($arOptionField["Type"] == "date") && isset($arOptionField["IncludeTime"]) && $arOptionField["IncludeTime"])
					$s .= "var re = new RegExp('^(.+) +([0-9]+:[0-9]+ *([am|pm]*)) *$');
					re.ignoreCase = true;
					//var match = re.exec(form['".$this->FieldName . "AddOption" . $sOptionField."'].value);
					var match = re.exec(form['_{$this->FieldName}'+n+'{$sOptionField}'].value);
					if(match){
						form['".$this->FieldName . "AddOption" . $sOptionField."Time'].value = trim(match[2]);
						/*form['".$this->FieldName . "AddOption" . $sOptionField."'].value = trim(match[1]);*/
						form['_".$this->FieldName . "AddOption" . $sOptionField."'].value = trim(match[1]);
					}
					";
			}
		}
		$s .= "
		if(typeof(allDatepickers) != 'undefined') activateDatepickers('active');
		}

		function saveTableLinks{$this->FieldName}(form){
			if(typeof(allDatepickers) != 'undefined')  activateDatepickers('destroy');
			if({$this->FieldName}activeRow >= 0)
				saveActiveRow{$this->FieldName}(form, true);
			else{
				form.DisableFormScriptChecks.value=1;
				form.{$this->FieldName}AddButton.value='submit';
				form.submit();
			}
			if(typeof(allDatepickers) != 'undefined')  activateDatepickers('active');
		}

		function saveActiveRow{$this->FieldName}(form, restoreForm){
			var s  = '<table id=noBorder cellspacing=0 cellpadding=5 border=0 class=valuesTable>';
			var value;
			var editor = document.getElementById('{$this->FieldName}Editor');
			var input\n";
			foreach( $this->Fields as $sOptionField => &$arOptionField ){
				$s .= "input = document.getElementById('fld{$this->FieldName}AddOption{$sOptionField}');
				if(input){\n";
				if(isset($arOptionField['Options']))
					$s .= "value = escapeHTML(selectText(input));\n";
				else
					$s .= "value = escapeHTML(input.value);\n";
				if($arOptionField["InputType"] == "checkbox"){
					$s .= "if(input.checked)
						value = '1';
					else
						value = '0';
					var v = value;\n";
				}
				else
					$s .= "var v = escapeHTML(input.value);\n";
				if(($arOptionField['Type'] == 'date') && isset($arOptionField['IncludeTime']) && $arOptionField['IncludeTime']){
					$s .= "v += ' '    +document.getElementById('fld{$this->FieldName}AddOption{$sOptionField}Time').value;\n";
					$s .= "value += ' '+document.getElementById('fld{$this->FieldName}AddOption{$sOptionField}Time').value;\n";
				}
				$s .= "s += '<tr><td class=keyClass>" . $arOptionField["Caption"] . ":</td><td class=valueClass>'+value+'</td></tr>'\n";
				if ($arOptionField['Type'] == 'date') {
                    $s .= "form['_{$this->FieldName}'+{$this->FieldName}activeRow+'{$sOptionField}'].value = v;
                    var re = new RegExp('^(.+) +([0-9]+:[0-9]+ *([am|pm]*)) *$');
					re.ignoreCase = true;
                    var dt = $('input[name=\"{$this->FieldName}AddOption{$sOptionField}\"]').val(), match = re.exec(dt);
					if (match && match[1]) {
                        dt = match[1] + " . (empty($arOptionField['IncludeTime']) ? "''" : "' ' + $('input[name=\"{$this->FieldName}AddOption{$sOptionField}Time\"]').val()") . ";
                    } else {
                        dt += " . (empty($arOptionField['IncludeTime']) ? "''" : "' ' + $('input[name=\"{$this->FieldName}AddOption{$sOptionField}Time\"]').val()") . ";
                    }
                    $('input[name=\"{$this->FieldName}'+{$this->FieldName}activeRow+'{$sOptionField}\"]').val(dt); }";
				} else 
				$s .= "form['{$this->FieldName}'+{$this->FieldName}activeRow+'{$sOptionField}'].value = v;
				}\n";
			}
			$s .= "s += '</table>';
			var cell0 = document.getElementById('{$this->FieldName}Row'+{$this->FieldName}activeRow+'Cell0');
			var cell1 = document.getElementById('{$this->FieldName}Row'+{$this->FieldName}activeRow+'Cell1');
			var html = cell0.innerHTML;
			cell0.innerHTML = s;
			cell0.colSpan = '';
			cell1.style.display = '';
			{$this->FieldName}activeRow = -1;
			$('input[name=\"_SegmentsAddOptionDepDate\"],input[name=\"_SegmentsAddOptionArrDate\"]').val('');
			if(restoreForm){
				editor.innerHTML = html;
				document.getElementById('{$this->FieldName}AddButtonTrigger').value = 'Add';
			}
		}

		</script>\n";
		return $s;
	}

	// hidden fields html
	function HiddenHTML()
	{
		$arSelectedOptions = $this->SelectedOptions;
		// hidden fields
		$sResult = "<input type=hidden name={$this->FieldName}Count value=\"" . count( $arSelectedOptions ) . "\">\n";
		// draw selected options
		$n = 0;
		foreach( $arSelectedOptions as $arRow )
		{
			foreach( $this->Fields as $sOptionField => $arOptionField )
				$sResult .= "<input type=hidden name={$this->FieldName}{$n}{$sOptionField} value=\"{$arRow[$sOptionField]}\">\n";
			$n++;
		}
		return $sResult;
	}

	// check new row before add. should return null or error message
	function CheckNewRow( $arRow )
	{
		return null;
	}

	// find row in already selected rows. return row number, or false
	function FindRowNumber( $arNewRow, $arExistingOptions = null ){
		if(!isset($arExistingOptions))
			$arExistingOptions = $this->SelectedOptions;
		foreach ( $arExistingOptions as $nRow => $arRow ){
			$bMatch = true;
			foreach ( $this->UniqueFields as $sField ){
				if( strcasecmp( $arRow[$sField], $arNewRow[$sField] ) != 0 ){
					$bMatch = false;
					break;
				}
			}
			if( $bMatch )
				return $nRow;
		}
		return false;
	}

	// return if all option-add fields is empty
	function EmptyFields($arData){
		$bResult = true;
		foreach( $this->Fields as $sOptionField => $arOptionField )
		{
			$sValue = ArrayVal( $arData, "{$this->FieldName}AddOption{$sOptionField}" );
			if(($sValue != "") && (!isset($arOptionField['DefaultValue']) || ($arOptionField['DefaultValue'] != $sValue))){
				$bResult = false;
				break;
			}
		}
		return $bResult;
	}

	// empty all option-add fields
	function CleanFields(&$arData){
		foreach( $this->Fields as $sOptionField => $arOptionField ){
			$this->Fields[$sOptionField]["Value"] = null;
			if(isset($this->Fields[$sOptionField]["TimeValue"]))
				$this->Fields[$sOptionField]["TimeValue"] = null;
			unset($arData["{$this->FieldName}AddOption{$sOptionField}"]);
			unset($arData["{$this->FieldName}AddOption{$sOptionField}Time"]);
		}
	}

	// check field. return NULL or error message
	function Check( &$arData )
	{
		$arSelectedOptions = $this->SelectedOptions;
		if((ArrayVal( $arData, "{$this->FieldName}AddButton" ) != "") || ($this->AutoSave && !$this->EmptyFields($arData)))
		{
			$arRow = array();
			foreach( $this->Fields as $sOptionField => $arOptionField )
			{
				if(isset($arData[ "{$this->FieldName}AddOption{$sOptionField}" ]) && is_array($arData[ "{$this->FieldName}AddOption{$sOptionField}" ]))
					continue;
				//$arOptionField = &$this->Fields[$sOptionField];
				$arOptionField["Value"] = ArrayVal( $arData, "{$this->FieldName}AddOption{$sOptionField}" );
				if(is_array($arOptionField["Value"]))
					$arOptionField["Value"] = NULL;
				if(($arOptionField["InputType"] == "date") && isset($arOptionField["IncludeTime"])) {
					$arOptionField["TimeValue"] = ArrayVal($arData, "{$this->FieldName}AddOption{$sOptionField}Time");
					if(is_array($arOptionField["TimeValue"]))
						$arOptionField["TimeValue"] = null;
				}
				if( $arOptionField["Value"] == "" )
					$arOptionField["Value"] = NULL;
				if( !empty($arOptionField['Value']) && empty($arOptionField["HTML"]) && !is_array( $arOptionField['Value'] ) )
					$arOptionField['Value'] = htmlspecialchars( $arOptionField['Value'] );
				$this->Form->CheckField( $this->FieldName . "AddOption" . $sOptionField, $arOptionField );
				$this->Fields[$sOptionField] = $arOptionField;
				if( isset( $arOptionField["Error"] ) )
					return $arOptionField["Caption"] . ": " . $arOptionField["Error"];
				$s = trim(ArrayVal($arData, "{$this->FieldName}AddOption{$sOptionField}"));
				if(($arOptionField["InputType"] == "date") && isset($arOptionField["IncludeTime"]))
                    if ($arOptionField["TimeValue"] != "")
                        $s .= " " . $arOptionField["TimeValue"];
                    else
                        $s .= " " . date(TIME_FORMAT, strtotime("12:00am"));
				if($arOptionField["InputType"] == "checkbox")
					$s = $arOptionField['Value'];
				$arRow[$sOptionField] = $s;
			}
			$sError = $this->CheckNewRow( $arRow );
			if( isset( $sError ) )
				return $sError;
			$this->CleanFields($arData);
			$nRowNumber = $this->FindRowNumber( $arRow );
			foreach ($arRow as $fieldName => $val) {
				if ((!isset($this->Fields[$fieldName]['HTML']) || $this->Fields[$fieldName]['HTML'] == false) && !is_array($val)) {
					$arRow[$fieldName] = $this->smartEscape($val);
				}
			}
			if( $nRowNumber === false )
				$arSelectedOptions[] = $arRow;
			else
				$arSelectedOptions[$nRowNumber] = $arRow;
		}
		$this->SelectedOptions = $arSelectedOptions;
		foreach( $this->SelectedOptions as $arRow ){
			foreach( $this->Fields as $sOptionField => $arOptionField ){
				$arOptionField["Value"] = $arRow[$sOptionField];
				if( $arOptionField["Value"] == "" )
					$arOptionField["Value"] = NULL;
				$this->Form->CheckField( $this->FieldName . "AddOption" . $sOptionField, $arOptionField );
				if( isset( $arOptionField["Error"] ) )
                    return $arOptionField["Caption"] . ": " . $arOptionField["Error"];
			}
		}
		if(isset($this->MinCount) && (count($this->SelectedOptions) < $this->MinCount))
			return "You should add at least {$this->MinCount} row";
	}

	// format row to sql
	function FormatRowToSQL( &$arRow )
	{
		foreach($this->Fields as $fieldName => $field)
			if(!$field['Database'])
				unset($arRow[$fieldName]);
		foreach( $arRow as $sOptionField => $sValue )
		{
			$arField = $this->Fields[$sOptionField];

			if($sValue != "") {
                $arField["Value"] = $sValue;
            } elseif ($arField['Nullable']) {
                $arField["Value"] = NULL;
            }

			if(($arField["Type"] == "boolean") && $arField["Required"] && !isset($arField['Value']))
				$arField['Value'] = '0';
			$arRow[$sOptionField] = $this->Form->SQLValue( $sOptionField, $arField );
//			else
//				$arRow[$sOptionField] = "'".addslashes($sValue)."'";
		}
		$arRow[$this->KeyField] = $this->Form->ID;
		if( isset( $this->SQLParams ) )
			$arRow = array_merge( $arRow, $this->SQLParams );
	}

	// format row to sql
	function FormatRowToForm($arRow)
	{
		global $Connection;
		$arResult = array();
		foreach( $arRow as $sOptionField => $sValue )
			if(isset($this->Fields[$sOptionField]))
			{
				$arResult[$sOptionField] = $sValue;
				$arField = $this->Fields[$sOptionField];
				if($sValue != ""){
					if($arField["Type"] == "date"){
						if(isset($arField["IncludeTime"]) && $arField["IncludeTime"])
							$sFormat = DATE_FORMAT." ".TIME_FORMAT;
						else
							$sFormat = DATE_FORMAT;
						$arResult[$sOptionField] = date($sFormat, $Connection->SQLToDateTime($sValue, isset($arField['IncludeTime']) && $arField["IncludeTime"]));
					}
				}
			}
		return $arResult;
	}

	function RowExists( $arRow, $arExistingOptions ){
		foreach ( $arExistingOptions as $arSelectedRow ){
			$bMatch = true;
			foreach ( $this->Fields as $sFieldName => $arField ){
				if( $arField['Database'] && (strcasecmp( $arSelectedRow[$sFieldName], $arRow[$sFieldName] ) != 0) ){
					$bMatch = false;
					break;
				}
			}
			if( $bMatch )
				return true;
		}
		return false;
	}
	
	/**
	get key fields for update sql
	@return array
	**/
	function getUpdateKeys($row){
		$keys = array();
		if(isset($this->UniqueFields))
			foreach($this->UniqueFields as $field)
				if(isset($row[$field]))
					$keys[$field] = $row[$field];
		if(isset($this->SQLParams))
			foreach($this->SQLParams as $field => $value)
				$keys[$field] = $value;
		$keys[$this->KeyField] = $row[$this->KeyField];
		return $keys;
	}
	
	// saving
	function Save()
	{
		global $Connection;
		$arExistingOptions = array();
		$q = new TQuery( $this->GetSQL() );
		while( !$q->EOF ){
			$arExistingOptions[] = $this->FormatRowToForm($q->Fields);
			$q->Next();
		}
		// add new options
		foreach( $this->SelectedOptions as $arRow )
		{
			$nRowNumber = $this->FindRowNumber($arRow, $arExistingOptions);
			$plainRow = $arRow;
			$this->FormatRowToSQL( $arRow );
			if($nRowNumber === false)
			{
                if ($this->onInsertRow !== null) {
                    $arRow = call_user_func($this->onInsertRow, $arRow);
                }
				$Connection->Execute( InsertSQL( $this->TableName, $arRow ) );
			}
			else{
				$Connection->Execute( UpdateSQL( $this->TableName, $this->getUpdateKeys($arRow), $arRow ) );
				foreach($plainRow as $key => $value)
					if($value == "")
						$plainRow[$key] = null;
				array_splice( $arExistingOptions, array_search( $plainRow, $arExistingOptions, 1 ), 1 );
			}
		}
		// remove deleted options
		foreach( $arExistingOptions as $arRow )
		{
			$this->FormatRowToSQL( $arRow );
			$Connection->Execute( DeleteSQL( $this->TableName, $arRow, True ) );
			if( $this->RelatedLink )
			{
				$sChildField = array_pop( array_keys( $this->Fields ) );
				$Connection->Execute( DeleteSQL( $this->TableName, array(
					$this->KeyField => $arRow[$sChildField],
					$sChildField => $this->Form->ID
				), True ) );
			}
		}
	}

	// return check scripts, runs after required scripts
	function FieldCheckScripts($sFieldName, $arField){
		if(!isset($arField['Page']) || ($arField['Page'] == $this->Form->ActivePage))
			return "if({$this->FieldName}activeRow >= 0)
				saveActiveRow{$this->FieldName}(Form, true);\n";
		else
			return "";
	}
}

?>
