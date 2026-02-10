<?php

// -----------------------------------------------------------------------
// Checkbox Links Field manager class.
//		Contains class, to handle sub-tables
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

require_once(__DIR__."/TAbstractFieldManager.php" );

class TCheckBoxLinksFieldManager extends TAbstractFieldManager
{
	var $TableName;					// sub-table, where to save selection
	var $KeyField;					// key field of sub-table, set automatically
	var $ValueField;				// value field of sub-table
	var $Checkboxes;				// array of key => value
	var $SQLParams = NULL;			// assoc array of sql insert/update/delete statements
	var $SelectedOptions = array();	// array of arrays: selected rows
	var $ColumnWidth = 300;			// sets up the width of the table of results
	var $KeepUnknownOptions = false;  // remove unknown options on save
	
	// initialize field
	function CompleteField()
	{
		global $Connection;
		parent::CompleteField();
		$this->Field["Database"] = False;
		if( !isset( $this->TableName ) )
			DieTrace( "TableName not set for field $this->FieldName" );
		if( !isset( $this->KeyField ) )
			$this->KeyField = $Connection->PrimaryKeyField($this->TableName);
		if( !isset( $this->ValueField ) )
			DieTrace( "ValueField not set for field $this->FieldName" );
		if( !isset( $this->Checkboxes ) )
			DieTrace( "Checkboxes not set for field $this->FieldName" );
	}
	
	function LoadSelected()
	{
		$sSQL = "select {$this->ValueField} 
		from {$this->TableName} where {$this->Form->KeyField} = {$this->Form->ID}";
		if( isset( $this->SQLParams ) )
			$sSQL .= " and " . ImplodeAssoc( " = ", " and ", $this->SQLParams );
		$arResult = array();
		$q = new TQuery($sSQL);
		while( !$q->EOF ){
			$nValue = intval( $q->Fields[$this->ValueField] );
			if( !in_array( $nValue, $arResult ) )
				$arResult[] = $nValue;
			$q->Next();
		}
		return $arResult;
	}
	
	// set field values
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		$this->SelectedOptions = $this->LoadSelected();
		$this->Field["Value"] = implode( ",", $this->SelectedOptions );
	}
	
	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		$this->SelectedOptions = array();
		foreach ( $this->Checkboxes as $nValue => $sCaption )
			if( ArrayVal( $arData, $this->FieldName.$nValue ) == "1" )
				$this->SelectedOptions[] = $nValue;
	}
	
	// get field html
	function InputHTML($sFieldName = null, $arField = null)
	{
		$s = "<table cellspacing='0' cellpadding='5' border='0' class='detailsTable' width='{$this->ColumnWidth}'>\n";
		foreach ( $this->Checkboxes as $nValue => $sCaption ){
			$s .= "<tr><td style='border: 1px solid #C7C4BF; width: 20px; text-align: center;'><input type=checkbox name={$this->FieldName}{$nValue} value=1" .(in_array( $nValue, $this->SelectedOptions )?" checked":"")."></td><td style='border: 1px solid #C7C4BF;'>{$sCaption}</td></tr>\n";
		}
		if( count( $this->Checkboxes ) > 1 )
			$s .= "<tr><td style='border: 1px solid #C7C4BF; width: 20px; text-align: center;'><input type=checkbox value=''" .(count( $this->SelectedOptions ) == count( $this->Checkboxes )?" checked":"")." onclick=\"selectCheckBoxes( this.form, '{$this->FieldName}', this.checked )\"></td><td style='border: 1px solid #C7C4BF;'>Select All</td></tr>\n";
		$s .= "</table><br>\n";
		return $s;	
	}
	
	// hidden fields html
	function HiddenHTML()
	{
		$s = "";
		foreach ( $this->Checkboxes as $nValue => $sCaption )
			if(in_array( $nValue, $this->SelectedOptions ))
				$s .= "<input type=hidden name={$this->FieldName}{$nValue} value=1>\n";
		return $s;
	}	
	
	// format row to sql
	function FormatRowToSQL( $nValue )
	{
		$arRow = array(
			$this->ValueField => $nValue,
			$this->Form->KeyField => $this->Form->ID,
		);
		if( isset( $this->SQLParams ) )
			$arRow = array_merge( $arRow, $this->SQLParams );
		return $arRow;
	}
	
	// saving
	function Save()
	{
		global $Connection;
		$arExistingOptions = $this->LoadSelected();
		// add new options
		foreach( $this->SelectedOptions as $nValue )
			if( !in_array( $nValue, $arExistingOptions ) ) 
				$Connection->Execute( InsertSQL( $this->TableName, $this->FormatRowToSQL( $nValue ) ) );
			else
				array_splice( $arExistingOptions, array_search( $nValue, $arExistingOptions, 1 ), 1 );
		// remove deleted options
		foreach( $arExistingOptions as $nValue )
			if(!$this->KeepUnknownOptions || isset($this->Checkboxes[$nValue]))
				$Connection->Execute( DeleteSQL( $this->TableName, $this->FormatRowToSQL( $nValue ), True ) );
	}	
}

?>