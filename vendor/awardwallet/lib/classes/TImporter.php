<?php

// -----------------------------------------------------------------------
// importer class.
//		imports CSV file into table
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

class TImporter{
	// target table
	var $Table;
	// source csv file
	var $SourceFile;
	// field delimiter
	var $Delimiter = ",";
	// column mappings. source file field => target table field
	var $Mappings = array();
	// packet size, page will be reloaded afer each packet, to prevent timeout
	var $PacketSize = 1000000000;
	// default values for each row in target table. fieldname => value
	var $Defaults = array();
	
	// import data to table
	function Import()
	{
		global $Connection;
		$nLine = 1;
		ob_end_flush();
		set_time_limit( 1000000000 );
		echo "Importing CSV file {$this->SourceFile}<br>\r\n";
		$rFile = fopen( $this->SourceFile, "r" );
		if( $rFile === false )
			DieTrace(" Error opening file" );
		// detect file columns
		$s = fgets( $rFile );
		$arCols = explode( $this->Delimiter, $s );
		$arCols = TrimArray( $arCols );
		$ar = array();
		foreach( $arCols as $sCol )
		{
			if( ( $sCol != "" ) && ( substr( $sCol, 0, 1 ) == "\"" ) )
				$sCol = substr( $sCol, 1, strlen( $sCol ) - 2 );
			$ar[] = $sCol;
		}
		$arCols = $ar;
		echo "File columns: " . implode( $arCols, ", " ) . "<br>\r\n";
		$arFieldTypes = array();
		// detect table fields
		$arTableFields = array();
		$q = new TQuery( "describe {$this->Table}" );
		while( !$q->EOF )
		{
			$arTableFields[] = $q->Fields["Field"];
			$arFieldTypes[$q->Fields["Field"]] = $q->Fields["Type"];
			$q->Next();
		}
		$q->Close();
		$arTableFields = TrimArray( $arTableFields );
		echo "Table columns: " . implode( ", ", $arTableFields ) . "<br>\r\n";
		// check column mapping
		foreach( $this->Mappings as $sSrc => $sDst )
		{
			if( !in_array( $sSrc, $arCols ) )
				DieTrace( "Mapping $sSrc => $sDst not found in source file" );
			if( !in_array( $sDst, $arTableFields ) )
				DieTrace( "Mapping $sSrc => $sDst not found in target table" );
		}
		foreach( $arCols as $sField )
			if( !in_array( $sField, $arTableFields ) && !isset( $this->Mappings[$sField] ) )
				DieTrace( "Field $sField not found in target table or mappings" );
		// skip lines
		if( $nLine == 1 )
		{
			echo "deleting table data<br>\r\n";
			$Connection->Execute( "delete from $this->Table" );
		}
		else
		{
			echo "skipping $nLine lines<br>\r\n";
			for( $n = 0; $n <= $nLine; $n++ )
				fgets( $rFile );
		}
		// import
		$nSessionLine = 0;
		while( !feof( $rFile ) )
		{
			$nLine++;
			$nSessionLine++;
			$s = trim( fgets( $rFile ) );
			if( ( $nLine % 100 ) == 0 )
				echo "$nLine\r\n";
			// load whole row. it may be divided to more than one string
			while( !feof( $rFile ) && ( substr_count( $s, $this->Delimiter ) < ( count( $arCols ) - 1 ) ) )
			{
				$t = trim( fgets( $rFile ) );
				$s .= "\r\n" . $t;
				$nSessionLine++;
				$nLine++;
			}
			if( ( $s != "" ) && ( substr_count( $s, $this->Delimiter ) != ( count( $arCols ) - 1 ) ) )
			{
				echo( "error at line $nLine(" . substr( $s, 0, 20 ) . "..): columns count(" . substr_count( $s, $this->Delimiter ) . ") not equal " . ( count( $arCols ) - 1 ) . "\r\n" );
				continue;
			}
			if( $s != "" )
			{
				$arValues = explode( $this->Delimiter, $s );
				while( count( $arValues ) < count( $arCols ) )
					$arValues[] = "";
				$arValues = TrimArray( $arValues );
				$arSourceValues = array_combine( $arCols, $arValues );
				$arValues = array();
				foreach( $arSourceValues as $sKey => $sValue )
				{
					if( isset( $this->Mappings[$sKey] ) )
						$sField = $this->Mappings[$sKey];
					else
						$sField = $sKey;
					if( $sValue == "" ) 
						$sValue = "null";
					else
						if( preg_match( "/char/i", $arFieldTypes[$sField] ) )
							$sValue = "'" . addslashes( $sValue ) . "'";
					$arValues[$sField] = $sValue;
				}
				$arValues += $this->Defaults;
				$Connection->Execute( InsertSQL( $this->Table, $arValues ) );
				if( $nSessionLine >= $this->PacketSize )
				{
					echo "<script>document.location.href='{$_SERVER["SCRIPT_NAME"]}?File=$this->SourceFile&Table=$this->Table&SkipLines=$nLine&LastId={$arValues[0]}'</script>\r\n";
					exit();
				}
			}
		}
		fclose( $rFile );
		echo "OK. Imported $nLine lines.<br>\r\n";
	}
	
}

// trim all values in array
function TrimArray( $ar )
{
  $arResult = array();
  foreach( $ar as $sIndex )
    $arResult[] = trim( $sIndex );
  return $arResult;
}

?>