<?php

// -----------------------------------------------------------------------
// Table Links Field manager class.
//		Contains class, to handle sub-tables
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

class TCategoryLinksFieldManager extends TAbstractFieldManager
{
	var $TableName = NULL;			// sub-table
	var $KeyField = NULL;			// key field of sub-table, set automatically
	var $SQLParams = NULL;			// assoc array of sql insert/update/delete statements
	var $SelectedOptions = array();	// array of arrays: selected rows
	var $ItemCaption;				// caption of one item. f.x. "Registration"
	var $CategoryExplorer;			// TCategoryExplorer
	var $plusImage = "/lib/images/plus.gif";
	var $minusImage = "/lib/images/minus.gif";
	
	// initialize field
	function CompleteField()
	{
		parent::CompleteField();
		$this->Field["Database"] = False;
		if( !isset( $this->TableName ) )
			DieTrace( "TableName not set for field $this->FieldName" );
		if( !isset( $this->CategoryExplorer ) )
			DieTrace( "CategoryExplorer not set for field $this->FieldName" );
		$this->Field["InputType"] = "text";
	}
	
	function GetSQL()
	{
		$sSQL = "select {$this->CategoryExplorer->KeyField} 
		from {$this->TableName} where {$this->Form->KeyField} = {$this->Form->ID}";
		if( isset( $this->SQLParams ) )
			$sSQL .= " and " . ImplodeAssoc( " = ", " and ", $this->SQLParams );
		return $sSQL;
	}
	
	// set field values
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		global $Connection;
		$arSelectedOptions = array();
		$q = new TQuery( $this->GetSQL() );
		while( !$q->EOF )
		{
			$arSelectedOptions[] = $q->Fields[$this->CategoryExplorer->KeyField];
			$q->Next();
		}
		$this->SelectedOptions = $arSelectedOptions;
		$this->Field["Value"] = implode( ",", $arSelectedOptions );
	}
	
	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		$arSelectedOptions = array();
		$q = new TQuery( "SELECT {$this->CategoryExplorer->KeyField}, {$this->CategoryExplorer->NameField} FROM {$this->CategoryExplorer->Table} ORDER BY `Rank`,  {$this->CategoryExplorer->NameField}" );
		while( !$q->EOF ) {
			$nValue = $q->Fields[$this->CategoryExplorer->KeyField];
			if( ( ArrayVal( $arData, $this->FieldName.$nValue ) == $nValue )
			&& !in_array( $nValue, $arSelectedOptions ) )
				$arSelectedOptions[] = $nValue;
			$q->Next();
		}
		$this->SelectedOptions = $arSelectedOptions;
	}
	
	function TreeHTML( $nPreParentID, $nParentID, $path = "" ) {
		if( isset( $nParentID ) )
			$sSQL = "SELECT {$this->CategoryExplorer->KeyField}, {$this->CategoryExplorer->NameField} FROM {$this->CategoryExplorer->Table} WHERE {$this->CategoryExplorer->KeyField} IN (SELECT {$this->CategoryExplorer->KeyField} FROM {$this->CategoryExplorer->Table}Relation WHERE {$this->CategoryExplorer->ParentField} = ".$nParentID.") ORDER BY `Rank`,  {$this->CategoryExplorer->NameField}";
		else
			$sSQL = "SELECT {$this->CategoryExplorer->KeyField}, {$this->CategoryExplorer->NameField} FROM {$this->CategoryExplorer->Table} WHERE {$this->CategoryExplorer->KeyField} NOT IN (SELECT {$this->CategoryExplorer->KeyField} FROM {$this->CategoryExplorer->Table}Relation) ORDER BY `Rank`,  {$this->CategoryExplorer->NameField}";
		$q = new TQuery($sSQL);
		$s = "";
		if( !$q->EOF ) {
			$s .= "<div id={$this->FieldName}{$nPreParentID}-{$nParentID}Div style='margin-left: " . ( isset( $nParentID ) ? "20" : "0" ) . "px; display: " . ( !isset( $nParentID ) ? "block" : "none" ) . "'>\n";
			while( !$q->EOF ) {
				$nValue = $q->Fields[$this->CategoryExplorer->KeyField];
				$id = $path."_".$nValue;
				$s .= "<table border=0 class=noBorder cellpadding=0 cellspacing=0><tr><td><span id={$this->FieldName}{$nParentID}-{$nValue}Button style='visibility: hidden'><img src='".$this->plusImage."' valign='top' id='{$this->FieldName}{$nParentID}-{$nValue}Image' onclick=\"var div = document.getElementById('{$this->FieldName}{$nParentID}-{$nValue}Div'); var image = document.getElementById('{$this->FieldName}{$nParentID}-{$nValue}Image'); if( div.style.display == 'none' ) { div.style.display = 'block'; image.src = '".$this->minusImage."'  } else { div.style.display = 'none'; image.src = '".$this->plusImage."' }\"> </span></td><td valign='middle'><input type=checkbox name={$this->FieldName}{$nValue} value={$nValue} id='check{$id}'";
				if( in_array( $nValue, $this->SelectedOptions ) )
					$s .= " checked";
				if( isset( $nParentID ) )
					$s .= " onclick=\"markCheckBoxes( this.form, '{$this->FieldName}{$nValue}', this.checked ); if( this.checked ) clickCheckBoxesId( this.form, 'check{$path}' )\"";
				$s .= "> {$q->Fields[$this->CategoryExplorer->NameField]}</td></tr></table>\n";
				$nOldLen = strlen( $s );
				$s .= $this->TreeHTML( $nParentID, $nValue, $id );
				if( $nOldLen != strlen( $s ) )
					$s .= "<script>document.getElementById('{$this->FieldName}{$nParentID}-{$nValue}Button').style.visibility  = 'visible';</script>";
				$q->Next();
			}
			$s .= "</div>\n";
		}
		return $s;
	}
	
	// get field html
	function InputHTML($sFieldName = null, $arField = null)
	{
		// hidden fields
		$s = $this->TreeHTML( null, null );
		return $s;	
	}
	
	// hidden fields html
	function HiddenHTML()
	{
		$arSelectedOptions = $this->SelectedOptions;
		// hidden fields
		$sResult = "";
		// draw selected options
		$n = 0;
		foreach( $arSelectedOptions as $nValue )
		{
			$sResult .= "<input type=hidden name={$this->FieldName}{$nValue} value=\"{$nValue}\">\n";
			$n++;
		}
		return $sResult;
	}	
	
	// saving
	function Save()
	{
		global $Connection;
		$arExistingOptions = array();
		$q = new TQuery( $this->GetSQL() );
		while( !$q->EOF ) {
			$arExistingOptions[] = $q->Fields[$this->CategoryExplorer->KeyField];
			$q->Next();
		}
		// add new options
		foreach( $this->SelectedOptions as $nValue ) {
			if( !in_array( $nValue, $arExistingOptions ) ) {
				$Connection->Execute( InsertSQL( $this->TableName, array( $this->Form->KeyField => $this->Form->ID, $this->CategoryExplorer->KeyField => $nValue ) ) );
			}
			else
				array_splice( $arExistingOptions, array_search( $nValue, $arExistingOptions, 1 ), 1 );
		}
		// remove deleted options
		foreach( $arExistingOptions as $nValue )
			$Connection->Execute( DeleteSQL( $this->TableName, array( $this->Form->KeyField => $this->Form->ID, $this->CategoryExplorer->KeyField => $nValue ), True ) );
	}	
}

?>
