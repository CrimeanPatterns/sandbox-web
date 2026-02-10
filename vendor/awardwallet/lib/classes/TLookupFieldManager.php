<?php
require_once __DIR__."/TAbstractFieldManager.php";

$bAutoSuggestLinked = false;

class TLookupFieldManager extends TAbstractFieldManager {
	public $ScriptName;
	public $MaxResults = 35;
	public $Timeout = 5000;
	public $OffsetY = -1;
	public $Width = "";
	public $Callback = "''";
	
	function __construct($sScriptName){
		if(!preg_match("/[\?\&]/", $sScriptName))
			$sScriptName .= "?";
		$this->ScriptName = $sScriptName;		
	}
	
	// get field html
	function InputHTML($sFieldName = null, $arField = null)
	{
		global $bAutoSuggestLinked;
		if(!isset($sFieldName))
			$sFieldName = $this->FieldName;
		$s = "";
		if(!$bAutoSuggestLinked){
			$s .= "<script src=\"/lib/scripts/bsn.AutoSuggest.js\"></script>";
			$bAutoSuggestLinked = true;
		}
		$s .= parent::InputHTML($sFieldName, $arField)."<script>
		var options = {
			script: '".$this->ScriptName."',
			varname: 'Text',
			json: true,
			cache: false,
			timeout: ".$this->Timeout.",
			offsety: ".$this->OffsetY.",
			width: '".$this->Width."',
			callback: ".$this->Callback.",
			maxresults: ".$this->MaxResults."
		};
		var as = new bsn.AutoSuggest('fld".$sFieldName."', options);
		</script>";
		return $s;
	}
	
}
?>