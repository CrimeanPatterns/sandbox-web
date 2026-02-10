<?php

// -----------------------------------------------------------------------
// Table Links Field manager class.
//		Contains class, to handle sub-tables
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

class TStateFieldManager extends TAbstractFieldManager
{
	var $CountryField;
	var $HaveStates;
	var $DBFieldName;
	var $Required = true;

	// initialize field
	function CompleteField()
	{
		if( !isset( $this->Form->Fields[$this->CountryField] ) )
			DieTrace( "CountryField not set or does not exist in form" );
		$this->Field["CheckScripts"] = True;
		$this->Field["MultiSelect"] = False;
		$this->Field["Rows"] = 1;
		$this->CheckCountry();
		if( !isset( $this->DBFieldName ) )
			$this->DBFieldName = $this->FieldName;
	}
	
	// check field. return NULL or error message. called only when field is checked.
	function Check( &$arData )
	{
		if( isset( $this->Field["OnGetRequired"] ) )
		{
			if( is_array( $this->Field['OnGetRequired'] ) )
				$bRequired = CallUserFunc( array_merge( $this->Field['OnGetRequired'], array( $this->FieldName, &$this->Field ) ) );
			else 
				$bRequired = CallUserFunc( array( $this->Field['OnGetRequired'], $this->FieldName, &$this->Field ) );
		}
		else
			$bRequired = $this->Field["Required"];
		if(!$this->HaveStates)
			$bRequired = false;
		if( $bRequired && !isset( $this->Field["Value"] ) )
			return "Value required";
		if( isset( $this->Field["Value"] ) && ( $this->Field["InputType"] == "select" ) && !isset( $this->Field["Options"][$this->Field["Value"]] ) )
			return "Invalid option";
		return NULL;
	}

	// load state options, set input attributes depending on country
	function CheckCountry()
	{
		if( !isset( $this->Form->Fields[$this->CountryField]["Value"] ) )
			$nCountryID = 1;
		else 
		{
			$nCountryID = $this->Form->Fields[$this->CountryField]["Value"];
			if( !isset( $this->Form->Fields[$this->CountryField]["Options"][$nCountryID] ) )
				$nCountryID = 1;
		}
		if( $nCountryID > 0 )
			$this->HaveStates = Lookup( "Country", "CountryID", "HaveStates", $nCountryID, True );
		else 
			$this->HaveStates = False;
		if( $this->HaveStates )
		{
			// country have states, select state from dropdown list
			$arStateOptionAttributes = array();
			$arStateOptions = $this->LoadStateOptions( $nCountryID, $arStateOptionAttributes );
			$this->Field["Type"] = "integer";
			$this->Field["InputType"] = "select";
			$this->Field["Options"] = $arStateOptions;
			$this->Field["OptionAttributes"] = $arStateOptionAttributes;
			$this->Field["Required"] = $this->Required;
		}
		else
		{
			// country do not have states, enter state as free text
			$this->Field["Type"] = "string";
			$this->Field["InputType"] = "text";
			$this->Field["Size"] = 80;
			$this->Field["Required"] = false;
		}
	}
	
	// load state options and attributes. returns state array
	static function LoadStateOptions( $nCountryID, &$arOptionAttributes = NULL )
	{
		$q = new TQuery( "select s.*, a.Name as AreaName from State s 
		left outer join StateArea a on s.AreaID = a.AreaID 
		where s.CountryID = $nCountryID
		order by IsNull( s.AreaID ), a.Name, s.Name" );
		$sArea = "";
		$arResult = array();
		$bHaveAreas = False;
		while( !$q->EOF )
		{
			if( $sArea != $q->Fields["AreaName"] )
			{
				if( $q->Fields["AreaName"] != "" )
					$sArea = $q->Fields["AreaName"];
				else 
					$sArea = "Other";
				$arResult[" __" . $q->Fields["AreaID"]] = " ";
				$arResult[" _" . $q->Fields["AreaID"]] = "--- $sArea ---";
				if( isset( $arOptionAttributes ) )
					$arOptionAttributes[" _" . $q->Fields["AreaID"]] = "class=\"stateDividerOption\"";
				$bHaveAreas = True;
			}
			$arResult[$q->Fields["StateID"]] = $q->Fields["Name"];
			$q->Next();
		}
		if( !$bHaveAreas && ( count( $arResult ) > 0 ) )
			$arResult = array( " __0" => " " ) + $arResult;
#print "<textarea cols=80 rows=30>";
#print_r($arResult);
#print "</textarea>";
		return $arResult;
	}

	// set field values, from database
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		$sValue = $arValues[strtolower( $this->DBFieldName )];
		$this->CheckCountry();
		if( isset( $sValue ) )
		{
			if( !$this->HaveStates )
				$sValue = Lookup( "State", "StateID", "Name", $sValue );
			$this->Field["Value"] = $sValue;
		}
	}
	
	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		$this->CheckCountry();
		parent::LoadPostData( $arData );
	}
	
	// get addional sql parameters, for update or insert call. 
	function GetSQLParams( &$arFields, $bInsert )
	{
		global $Connection;
		if( !$this->HaveStates )
		{
			// save free-text entered state
			$sState = "'" . addslashes( $this->Field["Value"] ) . "'";
			$nCountryID = "'" . addslashes($this->Form->Fields[$this->CountryField]["Value"]) . "'";
			$q = new TQuery( "select * from State 
			where CountryID = $nCountryID and Name = $sState" );
			if( $q->EOF )
			{
				// save new state
				$nStateID = TableMax( "State", "StateID" ) + 1;
				$Connection->Execute( InsertSQL( "State", array(
					"StateID" => $nStateID,
					"CountryID" => $nCountryID,
					"Name" => $sState,
					"Code" => $nStateID,
				) ) );
			}
			else 
				$nStateID = $q->Fields["StateID"];
		}
		else 
			$nStateID = $this->Field["Value"];
		if( !isset( $nStateID ) )
			$nStateID = "null";
		$arFields[$this->FieldName] = $nStateID;
	}

}
