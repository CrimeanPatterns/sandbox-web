<?

class TBaseImporter{

	var $File;
	var $Fields;
	var $TableName;
	var $ColMap;
	var $KeyField;
	var $KeyFieldCols;
	var $Query;
	var $ChangeCount;
	var $Form;
	var $MatchMode;
	var $Processed;
	var $Extension;
	var $HeadersParsed = false;
	var $InsertKeyField = false;
	var $KeyFieldNames;

	function Import(\TBaseSchema $schema, \TBaseList $objList, $nMatchMode, $arFile, $sExtension){
        $this->Fields = array();
        foreach ( $objList->Fields as $sField => $arField ){
            if( isset( $this->Fields[$arField["Caption"]] ) )
                DieTrace("Duplicate field caption in list: $sField ({$arField["Caption"]})");
            $this->Fields[$sField] = $arField;
        }
        $this->TableName = $schema->TableName;
        $this->KeyField = $schema->KeyField;
  //		if( $nMatchMode == MATCH_KEYFIELD ){
            if( !isset( $this->Fields[$this->KeyField] ) )
                $this->Fields = array( $this->KeyField => array( "Caption" => $this->KeyField, "Type" => "integer" ) ) + $this->Fields;
            if( !in_array( $this->KeyField, array_keys( $this->Fields ) ) )
                DieTrace("List should include key field: {$this->KeyField}");
  //		}
        $this->Query = new TQuery();
        $this->ChangeCount = 0;
        $this->Form = $schema->CreateForm();
        $this->MatchMode = $nMatchMode;
        $this->Extension = $sExtension;
        $this->KeyFieldNames = $schema->GetImportKeyFields();

        $this->File = $arFile;
		$sInput = "";
		$bSuccess = true;
		$arFields = array();
		$sField = "";
		$this->ColMap = array();
		$bWaitQuote = false;
		$this->Processed = 0;
		if(count($this->File) == 0){
			echo "<div class=formError>Empty file</div>";
			$bSuccess = false;
		}
		else{
			if($this->Extension == "txt")
				$sDelimiter = "\t";
			else{
				// detect ' or ;
				$freq = count_chars($this->File[0]);
				if($freq[ord(";")] > $freq[ord(",")])
					$sDelimiter = ";";
				else
					$sDelimiter = ",";
				echo "<div>Detected delimiter: $sDelimiter</div>";
			}
			foreach ( $this->File as $nLine => $sLine ){
				for( $n = 0; $n < strlen( $sLine ); $n++ ){
					$s = substr( $sLine, $n, 1 );
					if( ( $sField == "" ) && ( $s == "\"" ) && !$bWaitQuote ){
						$bWaitQuote = true;
						continue;
					}
					if( ( $s == $sDelimiter )  && !$bWaitQuote ){
						$arFields[] = $sField;
						$sField = "";
						continue;
					}
					if( ( $s == "\"" ) && $bWaitQuote ){
						if( ( $n < ( strlen( $sLine ) - 1 ) )
						&& ( substr( $sLine, $n + 1, 1 ) == "\"" )
						/*&& !(($sField == "") && ($n < ( strlen( $sLine ) - 2)) && (substr( $sLine, $n + 2, 1 ) != $sDelimiter))*/
						){
							$n++;
							$sField .= "\"";
						}
						else
							$bWaitQuote = false;
						continue;
					}
					$sField .= $s;
				}
				if( $bWaitQuote ){
					$sField .= "\n";
					continue;
				}
				$arFields[] = $sField;
				$sField = "";
				$bEmpty = true;
				foreach ( $arFields as $nField => $sValue ){
					$sValue = str_replace("\\r", "\r",$sValue);
					$sValue = str_replace("\\n", "\n",$sValue);
					$arFields[$nField] = trim( $sValue );
					if( $arFields[$nField] != "" )
						$bEmpty = false;
				}
				if( !$bEmpty )
					if( !$this->ProcessRow( $nLine, $arFields, $sLine ) ){
						$bSuccess = false;
						$arFields = array();
						break;
					}
				$this->Processed++;
				$arFields = array();
			}
		}
		if( $bSuccess ){
			$this->Finish();
		}
		echo "<p>Processed lines: {$this->Processed}</p>";
	}

	function CheckFieldCount( &$arFields, $nFieldCount ){
		while( count( $arFields ) > $nFieldCount )
			array_pop( $arFields );
		if( count( $arFields ) <> $nFieldCount ) {
			echo "<div class=formError>Error: Should be {$nFieldCount} fields, but only ".count( $arFields )." found</div>";
			var_dump( $arFields );
			$arFields = array();
			return false;
		}
		return true;
	}

	function FieldCaptionToName( $sCaption ){
		foreach ( $this->Fields as $sField => $arField ){
			if( strtolower( $arField["Caption"] ) == strtolower( $sCaption ) )
				return $sField;
		}
		return false;
	}

	function FieldCaptions(){
		$result = array();
		foreach ( $this->Fields as $sField => $arField )
			$result[] = $arField["Caption"];
		return $result;
	}

	function ProcessRow( $nLine, $arFields, $sLine ){
		global $Connection;

        // ignore custom fields in PromotionCard schema
        if($this->TableName === 'PromotionCard'){
            unset($arFields[1]);
            unset($arFields[2]);
        }

		if(!$this->HeadersParsed){
			$this->HeadersParsed = true;
			// headers
			$this->KeyFieldCols = array();
			foreach( $arFields as $nKey => $sCaption ) {
				$sField = $this->FieldCaptionToName( $sCaption );
				if( $sField === false ){
					echo "<div class=formError>Error on line ".($nLine+1).": Unknown field: $sCaption</div>";
					echo "<div class=formError>Known captions: ".implode(", ", $this->FieldCaptions())."</div>";
					echo "<div class=formError>Complete line: ".StrToHex($sLine)."</div>";
					return false;
				}
				$arField = $this->Fields[$sField];
				if( isset( $arField["Options"] ) && ( count( $arField["Options"] ) != count( array_flip( $arField["Options"] ) ) ) ){
					echo "<div class=formError>Error on line ".($nLine+1).": Can't import. Duplicate options for field {$arField["Caption"]}. Duplicates: " . json_encode(array_flip(array_filter(array_count_values($arField['Options']), function(int $count) { return $count > 1; }))) . "</div>";
					return false;
				}
				{
					if( in_array( $sField, $this->ColMap ) ){
						echo "<div class=formError>Error on line ".($nLine+1).": Duplicate field: $sCaption</div>";
						return false;
					}
					echo "Header: {$sField} at column #{$nKey}<br>\n";
					switch ( $this->MatchMode ){
						case MATCH_KEYFIELD:
							foreach($this->KeyFieldNames as $keyField)
								if( $sField == $keyField ){
									echo "Key Field is Column #{$nKey} - {$sField}<br>\n";
									$this->KeyFieldCols[$nKey] = $sField;
								}
								break;
						case MATCH_1FIELD:
							if( $nKey == 0 ){
								echo "Key Field is Column #{$nKey} - {$sField}<br>\n";
								$this->KeyFieldCols[$nKey] = $sField;
							}
							break;
						case MATCH_2FIELDS:
							if( $nKey <= 1 ){
								echo "Key Field is Column #{$nKey} - {$sField}<br>\n";
								$this->KeyFieldCols[$nKey] = $sField;
							}
							break;
						default:
							DieTrace("Unknown match mode: {$this->MatchMode}");
					}
					$this->ColMap[$nKey] = $sField;
				}
			}
			echo "Detected fields: " . implode( ", ", $this->ColMap ) . "<br>\n";
			if( count( $this->ColMap ) < 2 ){
				echo "<div class=formError>Error on line ".($nLine+1).": There should be at least two columns</div>";
				return false;
			}
			if( count( $this->KeyFieldCols ) == 0 ){
				switch ( $this->MatchMode ){
					case MATCH_KEYFIELD:
						echo "<div class=formError>Error on line ".($nLine+1).": There should be column with Key Field: {$this->Fields[$this->KeyField]["Caption"]}</div>";
						break;
					case MATCH_1FIELD:
						echo "<div class=formError>Error on line ".($nLine+1).": There should be at leaset one column for Key Field</div>";
						break;
					case MATCH_2FIELDS:
						echo "<div class=formError>Error on line ".($nLine+1).": There should be at leaset two columns for Key Field</div>";
						break;
				}
				return false;
			}
			echo "key fields: " . implode( ", ", $this->KeyFieldCols ) . "<br>\n";
			echo "<br><table border=1 cellpadding=4 cellspacing=0>\n";
			echo "<tr><td>Line</td><td>Process</td><td>".implode( "</td><td>", $this->ColMap )."</td></tr>\n";
		}
		else{
			// data
			if( !$this->CheckFieldCount( $arFields, count( $this->ColMap ) ) )
				return true;
			// get row from database
			$bUpdate =  false;
			$arKeyValues = array();
			foreach ( $this->KeyFieldCols as $nCol => $sField ){
				$arField = $this->Fields[$sField];
				$value = $arFields[$nCol];
				if( isset( $arField["Options"] ) ){
					$arOptions = array_flip( $arField["Options"] );
					if( !isset( $arOptions[$value] ) ){
						echo "<div class=formError>Error on line ".($nLine+1).": Unknown option '{$value}' for field '{$arField["Caption"]}'. Allowed options are: ".implode( ", ", array_keys( $arOptions ) )."</div>";
						return true;
					}
					$value = $arOptions[$value];
				}
				$arKeyValues[$sField] = "'" . addslashes( $value ) . "'";
			}
			$this->Query->Open("select * from {$this->TableName} where " .ImplodeAssoc( " = ", " and ", $arKeyValues ) );
			if( !$this->Query->EOF ){
				$bUpdate = true;
			}
			$arOldValues = array();
			$arNewValues = array();
			$arOldSQLValues = array();
			$arNewSQLValues = array();
			$bProcess = false;
			foreach( $arFields as $nKey => $sValue ) {
				if( isset( $this->ColMap[$nKey] ) && ( $sValue != "" ) ){
					$sField = $this->ColMap[$nKey];
					$arField = $this->Fields[$sField];
					$sNewValue = $sValue;
					$sCurrentValue = "";
					$sCurrentFormattedValue = "";
					if (!array_key_exists($sField, $this->Query->Fields)) {
					    continue;
                    }
					if( $bUpdate ){
						$sCurrentValue = trim( $this->Query->Fields[$sField] );
						$sCurrentFormattedValue = $sCurrentValue;
					}
					// decode option
					if( isset( $arField["Options"] ) ){
						$arOptions = array_flip( $arField["Options"] );
						if( !isset( $arOptions[$sNewValue] ) ){
							echo "<div class=formError>Error on line ".($nLine+1).": Unknown option '{$sValue}' for field '{$arField["Caption"]}'. Allowed options are: ".implode( ", ", array_keys( $arOptions ) )."</div>";
							return true;
						}
						$sNewValue = $arOptions[$sNewValue];
						if( isset( $arField["Options"][$sCurrentValue] ) )
							$sCurrentFormattedValue = $arField["Options"][$sCurrentValue];
					}
					// deformat by type
					$bIncludeTime = false;
					$sFormat = DATE_FORMAT;
					switch ( $arField["Type"] ){
						case "datetime":
							$bIncludeTime = true;
							$sFormat = DATE_TIME_FORMAT;
						case "date":
							$sDate = StrToDate( $sNewValue, $bIncludeTime );
							if( $sDate === false ){
								echo "<div class=formError>Error on line ".($nLine+1).": Invalid date".($bIncludeTime?"time":"")." '{$sNewValue}' for field '{$arField['Caption']}'</div>";
								return true;
							}
							$sNewValue = date( $sFormat, $sDate );
							if( $bUpdate && ( $sCurrentValue != "" ) ){
								$sCurrentValue = date( $sFormat, $Connection->SQLToDateTime( $sCurrentValue ) );
								$sCurrentFormattedValue = $sCurrentValue;
							}
							break;
						case "money":
							$sNewValue = str_replace( ",", "", $sNewValue );
							if( substr( $sNewValue, 0, 1 ) != "\$" ){
								echo "<div class=formError>Error on line ".($nLine+1).": Invalid money '{$sNewValue}' for field '{$arField["Caption"]}', should start with \$</div>";
								return true;
							}
							$sNewValue = substr( $sNewValue, 1 );
						case "float":
							if( (string)floatval( $sNewValue ) != (string)$sNewValue ){
								echo "<div class=formError>Error on line ".($nLine+1).": Invalid float '{$sNewValue}' for field '{$arField["Caption"]}'</div>";
								return true;
							}
							$sNewValue = floatval( $sNewValue );
							if( $bUpdate && ( $sCurrentValue != "" ) ){
								$sCurrentValue = floatval( $sCurrentValue );
								if( $arField["Type"] == "money" )
									$sCurrentFormattedValue = "\$" . number_format( $sCurrentValue, 2, ".", "," );
							}
							break;
						case "boolean":
							switch ( $sNewValue ){
								case "Yes":
									$sNewValue = "1";
									break;
								case "No":
									$sNewValue = "0";
									break;
								default:
									echo "<div class=formError>Error on line ".($nLine+1).": Invalid boolean '{$sNewValue}' for field '{$arField["Caption"]}', should be Yes or No</div>";
									return true;
							}
							if( $sCurrentValue != "" ){
								if( $sCurrentFormattedValue == "1" )
									$sCurrentFormattedValue = "Yes";
								else
									$sCurrentFormattedValue = "No";
							}
							break;
					}
					// compare with original value in database
					if( !$bUpdate ){
						// insert
						$arNewValues[$sField] = $sValue;
						$arNewSQLValues[$sField] = $sNewValue;
						$bProcess = true;
					}
					else{
						// update
						if((strval($sCurrentValue) != strval($sNewValue)) && !(($sField == $this->KeyField) && !$this->InsertKeyField)){
							$arNewValues[$sField] = $sValue;
							$arNewSQLValues[$sField] = $sNewValue;
							$bProcess = true;
						}
						$arOldValues[$sField] = $sCurrentFormattedValue;
						$arOldSQLValues[$sField] = $sCurrentValue;
					}
				}
			}
			if( $bProcess ){
				if(($this->MatchMode == MATCH_KEYFIELD) && !$this->InsertKeyField && !isset($arOldSQLValues[$this->KeyField])){
					unset($arNewValues[$this->KeyField]);
				}
				echo "<input type=hidden name=ID{$this->ChangeCount} value=1>\n";
				echo "<tr><td>".($nLine+1)."</td><td><input type=checkbox name=Change{$this->ChangeCount} value=1 checked></td>\n";
				foreach ( $this->ColMap as $sField ){
					echo "<td>";
					$arField = $this->Fields[$sField];
					if( isset( $arNewValues[$sField] ) ){
						$arField["Value"] = $arNewSQLValues[$sField];
						$sNewSQLValue = $this->Form->SQLValue( $sField, $arField );
						echo "<input type=hidden name=Change{$this->ChangeCount}{$sField}New value=\"".htmlspecialchars($sNewSQLValue)."\">\n";
						echo "<div style='font-weight: bold;'>{$arNewValues[$sField]}</div>\n";
					}
					if( isset( $arOldSQLValues[$sField] ) ){
						$arField["Value"] = $arOldSQLValues[$sField];
						$sOldSQLValue = $this->Form->SQLValue( $sField, $arField );
						echo "<input type=hidden name=Change{$this->ChangeCount}{$sField}Old value=\"".htmlspecialchars($sOldSQLValue)."\">\n";
						echo "<div style='color: gray; font-size: 80%;'>(old: {$arOldValues[$sField]})</div>";
					}
					if( !isset( $arOldSQLValues[$sField] ) && !isset( $arNewValues[$sField] ) )
						echo "&nbsp;";
					echo "</td>\n";
				}
				echo "</tr>\n";
				$this->ChangeCount++;
			}
		}
		return true;
	}

	function Finish(){
		echo "<input type=hidden name=ChangeCount value={$this->ChangeCount}>\n";
		echo "<input type=hidden name=KeyFields value=\"".implode(",",$this->KeyFieldCols)."\">\n";
		$arFields = $this->ColMap;
		//unset( $arFields[$this->KeyFieldCol] );
		echo "<input type=hidden name=Fields value=\"".htmlentities( implode( ",", $arFields ) )."\">\n";
		echo "</table>\n";
		if( $this->ChangeCount > 0 )
			echo "<br><input type=submit class=button name=s1 value=Import>\n";
	}

}

// autodetect unicode file
function LoadFile( $sFile ){
	global $Config;
	$s = file_get_contents( $sFile );
	if( ini_get( "mbstring.func_overload" ) != "0" ){
		// decode UCS-2
		if( substr( bin2hex( $s ), 0, 4 ) == "fffe" )
			$s = iconv( "ucs-2", "utf-8", $s );
		else
			if( isset( $Config["RussianSite"] ) && $Config["RussianSite"] )
				$s = iconv( "cp1251", "utf-8", $s );
		// remove BOM
		if( bin2hex( substr( $s, 0, 1 ) ) == "efbbbf" )
			$s = substr( $s, 1 );
	}
	else{
		// remove BOM
		if( bin2hex( substr( $s, 0, 3 ) ) == "efbbbf" )
			$s = substr( $s, 3 );
		else
		// recode cp1251
			if( !isUTF8($s) )
				$s = iconv( "cp1251", "utf-8", $s );
	}
	return preg_split("/\n/", $s);
}

function isUTF8($str) {
        if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", "UTF-8"), "UTF-8", "UTF-32")) {
            return true;
        } else {
            return false;
        }
}

?>
