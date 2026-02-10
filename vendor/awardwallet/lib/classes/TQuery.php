<?php

// -----------------------------------------------------------------------
// query class. analog of ADODB.RecordSet
//		Contains class, to handle select queries
//		Can break results to pages, 
//		Can buffer results to create bi-directional recordset
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

class TQuery implements Iterator
{
	// used connection (TConnection)
	var $Connection = NULL;
	// is active, boolean
	var $Active = false;
	// no more records. boolean
	var $EOF = NULL;
	// field values. associative array field1=>value1, ..
	var $Fields = NULL;
	// active record no. starting with 1
	var $Position = NULL;
	// active page
	var $Page = NULL;
	// page size, records per page
	var $PageSize = 20;
	// total pages. set form external total calculators
	var $TotalPages = NULL;
	// query is empty
	var $IsEmpty = True;

	// private --------------------------------
	// ID of active query
	var $QueryID = NULL;
	// number of last page record
	var $PageEndPosition = NULL;
	// name of QS variable, containing page number
	var $PageVarName = NULL;
	// key fields for active query. array( "Field1", "Field2" ... )
	var $KeyFields = NULL;
	// active sql statement
	var $SQL = NULL;

	// constructor, specify sql-statement and connection. opens query
	function __construct( $SQL = NULL, $aConnection = NULL )
	{
		global $Connection;
		if( isset( $aConnection ) )
			$this->Connection = $aConnection;
		else
			$this->Connection = $Connection;
		if( isset( $SQL ) )
			$this->Open( $SQL );
	}

	// open query with specified sql, if not specified, will be used last sql
	function Open( $SQL = NULL )
	{
		if( $this->Active )
			$this->Close();
		if( !isset( $SQL ) )
			if ( isset( $this->SQL ) )
				$SQL = $this->SQL;
			else
				DieTrace( "TQuery->Open: SQL statement not specified" );
		if( !$this->QueryID = $this->Connection->OpenQuery( $SQL ) )
			return false;
		$this->Active = true;
		$this->SQL = $SQL;
		$this->EOF = false;
		$this->Position = 0;
		$this->Page = 1;
		$this->KeyFields = array();
		$this->Next();
		$this->IsEmpty = $this->EOF;
		return true;
	}

	// select next row $Count times. results fetched to $Rows and $Fields. 
	// return true on success ( not eof )
	function Next( $Count = 1 )
	{
		$Result = true;
		$this->EOF = false;
		for( $i = 0; $i < $Count; $i++ )
		{
			$arFields = $this->Connection->Fetch( $this->QueryID );
			if( !$arFields )
			{
				$this->EOF = true;
				$Result = false;
				break;
			}
			$this->Fields = $arFields;
			$this->Position++;
		}
		return $Result;
	}
	
	// select page. cursor set to first record of page
	// returns boolean - is page selected
	function SelectPage( $nPage )
	{
		$nNewPosition = ( $nPage - 1 ) * $this->PageSize + 1;
		if( $nNewPosition < $this->Position )
		{
			// scroll back 
			$this->Close();
			$this->Open();
		}
		$Result = $this->Next( $nNewPosition - $this->Position );
		$this->PageEndPosition = $this->Position + $this->PageSize - 1;
		$this->Page = floor( ( $this->Position - 1 ) / $this->PageSize ) + 1;
		return $Result;
	}

	// select page using parameters from URL. returns nothing.
	/*
		 Logic:
		 1. If $KeyFields is specified
				then QueryString scanned for parameters like PageByKeyField1, PageByKeyField2 
				and proc will select page containing record with this params
				Example: 
					Query->SelectPageByURL( "Page", array( "NewsID" ) );
					QueryString: ?PageByNewsID=26
					will select page, with record NewsID = 26
		 2. Otherwise: if QueryString contains $PageVarName("Page"), 
				then select page from this var
		 3. Otherwise: select first page
	*/
	function SelectPageByURL( $PageVarName = "Page", $KeyFields = array() )
	{
		$this->PageVarName = $PageVarName;
		$this->KeyFields = $KeyFields;
		$SearchMask = array();
		foreach( $KeyFields as $FieldName )
			if( isset( $_GET[$PageVarName . "By" . $FieldName] ) && ( trim( $_GET[$PageVarName . "By" . $FieldName] ) != "" ) )
			{
				$SearchMask[$FieldName] = intval( trim( $_GET[$PageVarName . "By" . $FieldName] ) );
				unset( $_GET[$PageVarName . "By" . $FieldName] );
			}
		if( ( count( $SearchMask ) == count( $KeyFields ) ) && ( count( $KeyFields ) != 0 ) )
			$this->LocatePage( $SearchMask );
		else
		{
			if( !isset( $_GET[$PageVarName] ) )
				$_GET[$PageVarName] = 1;
			$NewPage = $_GET[$PageVarName];
			$NewPage = intval( $NewPage );
			if ( $NewPage <= 0 )
				$NewPage = 1;
			$this->SelectPage( $NewPage );
		}
	}

	// select page containing record with specified values
	// key fields - asocciative array field1=>value1
	// if record is not found - selects last page
	// returns boolean - is record found
	function LocatePage( $KeyFields )
	{
		$Result = $this->Locate( $KeyFields );
		$this->SelectPage( $this->Page );
	}

	// find record with specified $KeyFields: array field1 => value1
	// returns boolean - found or not
	function Locate( $KeyFields )
	{
		$Match = false;
		while( !$this->EOF && !$Match )
		{
			$Match = true;
			foreach( $KeyFields as $FieldName => $Value )
				if( $this->Fields[$FieldName] != $Value )
				{
					$Match = false;
					break;
				}
			if( !$Match )
				$this->Next();
		}
		$this->Page = floor( max( $this->Position - 1, 0 ) / $this->PageSize ) + 1;
	}
	
	// is end of page. usually checked afer Next()
	// возвращает boolean
	function EndOfPage()
	{
		return $this->EOF || ( $this->Position > $this->PageEndPosition );
	}

	// build page navigator - returns html with links to pages 
	// returns number of links
	function PageNavigator( $ShowPrevPages = 4, $ShowNextPages = 4, $ShowNextPage = true )
	{
		$s = "";
		// build Link to other pages, ending "Page=", ex: /list.php?x=4&Page=
		if ( isset( $_GET[$this->PageVarName] ) )
			unset( $_GET[ $this->PageVarName ] );
		foreach( array_keys( $_GET ) as $FieldName )
			if( strpos( $FieldName, "PageBy" ) === 0 )
				unset( $_GET[$FieldName] );
		$_GET[ $this->PageVarName ] = "";
		$Link = $_SERVER['DOCUMENT_URI'] . "?";
		foreach( $_GET as $Key => $Value )
			if( !is_array( $Value ) )
				$Link .= $Key . "=" . urlencode( $Value ) . "&";
			else
				$Link .= urlencode( $Key . "[]" ) . "=" . urlencode( implode( ",", $Value ) ) . "&";
		$Link = substr( $Link, 0, strlen( $Link ) - 1 );
		// previous pages
		$PrevPages = "";
		for( $i = max( 1, $this->Page - $ShowPrevPages ); $i < $this->Page; $i++ )
		{
			if( ( $i == ( $this->Page - $ShowPrevPages ) ) && ( $i != 1 ) )
				$sText = "...";
			else 
				$sText = $i;
			$PrevPages .= "| <a href=\"".htmlspecialchars($Link.$i)."\">$sText</a> ";
		}
		// current
		$ActivePage = $this->Page;
		// next pages
		$NextPages = "";
		for( $i = $this->Page + 1; $i <= ( $ActivePage + $ShowNextPages ); $i++ )
		{
			if( isset( $this->TotalPages ) )
			{
				if( $i > $this->TotalPages )
					break;
			}
			else
				if( !$this->SelectPage( $i ) )
					break;
			if( $i == ( $ActivePage + $ShowNextPages ) )
				$sText = "...";
			else 
				$sText = $i;
			$NextPages .= "| <a href=\"".htmlspecialchars($Link.$i)."\">$sText</a> ";
		}
		if( !isset( $this->TotalPages ) )
			$this->SelectPage( $ActivePage );
		// next page
		if ( ( $this->Page > $ActivePage ) && $ShowNextPage )
			$NextPages .= "| <a href=\"" .htmlspecialchars( $Link. ( $ActivePage + 1 ) ). "\">&gt;</a> ";
		// link all together
		if( ( $NextPages != "" ) || ( $PrevPages != "" ) )
			$s = $PrevPages . "| <b>$ActivePage</b> " . $NextPages;
		unset( $_GET[ $this->PageVarName ] );
		return $s;
	}

	// close query. always returns true
	function Close()
	{
		if( $this->Active )
		{
			$this->Connection->CloseQuery( $this->QueryID );
			$this->Active = false;
		}
		return true;
	}
	
	function rewind() 
	{
        $this->Position = 0;
    }
    
    function current()
	{
        return $this->Fields;
    }
    
    public function key() 
	{ 
		return $this->Position; 
	}
	
	public function valid () 
	{ 
		return !$this->EOF; 
	}
}

?>
