<?php

// -----------------------------------------------------------------------
// Field manager class.
//		Contains abstract class, to handle form field operation
//		You should override class to build custom field managers
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class TAbstractFieldManager
{
	// managed field. all these properties will be set by owner class: TForm
	var $FieldName = NULL;
	var $Field = NULL;
	/* @var $Form TBaseForm */
	var $Form = NULL;

	// initialize field
	function CompleteField()
	{
	}

	// set field values, from database
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		$this->Field["Value"] = $arValues[strtolower($this->FieldName)] ?? null;
	}

	// get field html
	function InputHTML($sFieldName = null, $arField = null)
	{
		if(!isset($sFieldName))
			$sFieldName = $this->FieldName;
		return $this->Form->InputHTML($sFieldName, $arField);
	}

	// hidden fields html
	function HiddenHTML()
	{
		return "<input type=hidden name={$this->FieldName} value=\"" . htmlspecialchars( $this->Field["Value"] ) . "\">\n";
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		$this->Form->LoadFieldPostData( $this->FieldName, $this->Field, $arData );
	}

	// check field. return NULL or error message. called only when field is checked.
	function Check( &$arData )
	{
		return NULL;
	}

	// get addional sql parameters, for update or insert call.
	function GetSQLParams( &$arFields, $bInsert )
	{
	}

	// save field. on this stage all record data saved database. you can do additional saving.
	function Save()
	{
	}

	// return check scripts for one field
	function FieldRequiredScripts($sFieldName, $arField, $sCheckScriptCondition){
		return $this->Form->FieldRequiredScripts($sFieldName, $arField, $sCheckScriptCondition);
	}

	// return required group scripts
	function RequiredGroupScripts($sFieldName, $arField){
		return $this->Form->RequiredGroupScripts($sFieldName, $arField);
	}

	// return check scripts, runs after required scripts
	function FieldCheckScripts($sFieldName, $arField){
		return "";
	}

}

?>
