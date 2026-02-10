<?

// -----------------------------------------------------------------------
// Forms class.
//		Contains base form class, to handle input forms
//		You should override class to build custom interface, or use custom language.
//		TForm = class( TBaseForm ) ..
//		interface methods: FormatHTML, FormatRowHTML
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once __DIR__ . '/TBaseFormEngConstants.php';

class TBaseForm
{
	// form fields
	var $Fields;
	/*
	$Fields = array(
		"FieldName1" => array(
			"Caption"  => human-readable name of field, displayed in errors
			"Note"     => note, for example "4-16 symbols"
			"Type"     => "string", "integer", "float", "date", "boolean", "html"
			"MinSize"  => min length for strings
			"Size"     => max length for strings
			"Cols"     => width for text input
			"Required" => not null
			"Value"    => any string,
			"Min"      => min value for integer fields
			"Max"      => max value for integer fields
			"RegExp"   => regexp for checking
			"RegExpErrorMessage" => Error message, that will be displayed
				if field value do not match regexp
			"CheckScriptCondition" => js-condition, that will be added before each field js-check
			"HTML"     => html tags allowed, boolean
			"Error"    => string, describing field error,
			"SQLValue" => sql-value of field, for insert, update, etc. )
			"InputType"=> "text", "textarea", "checkbox", "select", "htmleditor",
				"password", "radio", "date", "html"
				if InputType is "password", then data saved to table as md5 hash
			"Options"  => dropdown list of options for <select>, array( key => value, .. )
			"OptionAttributes"  => additional attributes  for <option>, array( key => value, .. )
			"InputAttributes" => additional attributes <input>
			"Database" => True(default)/False - store field to database
			"OnGetHTML" => event handler function( $sFieldName, $arField ), should return field html
			"OnGetHidden" => event handler function( $sFieldName, $arField ), should return field html
			"RequiredGroup" => required fields group name. if set, one of group fields is required
			"IncludeTime" => if set - display time in date/time fields
			"Encoding"	=> "md5", "rsa" - default is null, how to encode text data, when storing to database
			"Page"		=> page name, on which field is displayed, if form is multi-paged
						see also ->Pages property
			"UnformattedHTML" => if set, OnGetHTML result will not go through FormatRowHTML
			"Manager" => instance of TFieldManager class, handle all field operations
			"IncludeCaption" => boolean, include/or not caption to fields,
				usefull when field type is html, default True
			"NoWrap" => do not wrap caption
	        "Nullable" => bool, whether field can be saved as null
		),
		"FieldName2" => array ...
	)
	*/
	// error message
	var $Error;
	// data table name
	var $TableName;
	// key field name, primary key of table
	var $KeyField;
	// unique constraint on table in form:
	// array( array( "Fields" => array( "Field1", "Field2" ), "ErrorMessage" => "Field1, Field should be unique" ),..
	var $Uniques = array();
	// filters, will be passed to URL, after edit. array( "Param1" => "Value1", ..
	var $Filters;
	// event handler function, will be called, when form data checked, after standard checks.
	// prototype: function CheckForm();
	// should return error message or NULL
	var $OnCheck;
	// event handler function, will be called, when form data is saved, after standard save
	// prototype: function SaveForm( $nID );
	// return nothing
	var $OnSave;
	// target URL, after form successfully saved
	// will be appended with PageBy<KeyField>=<ID> parameters . ID - id of edited row
	// example (default value): "list.php". will be redirected to list.php?PageByUserID=45
	// unset to disable redirect
	var $SuccessURL = "list.php";
	// title of submit button
	var $SubmitButtonCaption = S_SUBMIT_BUTTON_CAPTION;
	// is posting data? boolean. read-only
	var $IsPost;
	// is inserting record? boolean. read-only
	var $IsInsert;
	// parameters, will be append to insert/update sql. associative array
	var $SQLParams = array();
	// id of active table record. filled automatically from QS "ID" parameter
	var $ID;
	// form title
	var $Title;
	// form pages. array "PageName" => "Page caption". pages displayed in same order
	var $Pages;
	// active form page. set automatically from QS["Page"]. Default is first page
	var $ActivePage;
	// last page. set automatically to last index if $this->Pages
	var $LastPage;
	// previous page. set automatically to previous index if $this->Pages
	var $PrevPage;
	// next page. set automatically to next index if $this->Pages
	var $NextPage;
	// calendar script is linked to page. set automatically
	var $CalendarLinked = False;
	// submit form only once
	var $SubmitOnce = True;
	// where to send form
	var $SubmitURL;
	// autocomplete form in browser
	var $AutoComplete = true;
	// show 'Continue to Confirmation' on button
	var $ShowPageOnButtons = true;
	var $RequiredGroupError = S_FIELD_GROUP_REQUIRED;
	var $FormName = 'editor_form';
	// do redirect after form save. used internally
	var $WantRedirect = false;
	// do save after form post. used internally
	var $WantSave = false;
	// do not save changes
	var $ReadOnly = false;
	var $Method = "POST";
	var $Action; // form tag action
	// called after default validate
	var $UserCheckScripts = '';
	// check anti-CSRF token on form submission
	var $CsrfEnabled = true;
	/** @var Callable - called when form is loaded with data */
	public $OnLoaded;

	// constructor
	public function __construct( $arFields, $bCompleteFields = True )
	{
		$this->IsPost = ArrayVal($_SERVER, "REQUEST_METHOD") == "POST";
		$this->Fields = $arFields;
		$this->ID = isset($_GET["ID"]) ? intval($_GET["ID"]) : 0;
		if( $bCompleteFields )
			$this->CompleteFields();
	}

	// complete one field
	function CompleteField( $sFieldName, &$arField )
	{
		if( isset( $this->Fields[$sFieldName] ) && !isset( $arField ) )
			$arField = &$this->Fields[$sFieldName];
		if( !isset( $arField["Type"] ) && isset( $arField["Manager"] ) )
			$arField["Type"] = "string";
		if( $arField["Type"] == "html" )
		{
			$arField["InputType"] = "html";
			$arField["Database"] = False;
		}
		if( isset( $arField["OnGetHTML"] ) && !isset( $arField["InputType"] ) )
			$arField["InputType"] = "none";
		if( !isset( $arField["Type"] ) )
			DieTrace( "Field $sFieldName has no type" );
		if( !isset( $arField["Value"] ) )
			$arField["Value"] = NULL;
		if( !isset( $arField["Caption"] ) )
			$arField["Caption"] = NameToText( $sFieldName );
		if( !isset( $arField["Required"] ) )
			$arField["Required"] = False;
		if( !isset( $arField["InputAttributes"] ) )
			$arField["InputAttributes"] = "";
        if( !isset( $arField["TimeInputAttributes"] ) )
			$arField["TimeInputAttributes"] = "";
		if( !isset( $arField["Database"] ) )
			$arField["Database"] = True;
		if( !isset( $arField["HTML"] ) )
			$arField["HTML"] = false;
		if( !isset( $arField["DecimalPlaces"] ) )
			$arField["DecimalPlaces"] = 2;
		if( !isset( $arField["NoWrap"] ) )
			$arField["NoWrap"] = true;
		if( !isset( $arField["InputType"] ) )
			if( isset( $arField["Options"] ) )
				$arField["InputType"] = "select";
			else
				switch( $arField["Type"] )
				{
					case "boolean":
						$arField["InputType"] = "checkbox";
						break;
					case "date":
						$arField["InputType"] = "date";
						break;
					default:
						$arField["InputType"] = "text";
				}
		if( !isset( $arField["Cols"] ) && isset( $arField["Size"] )
		&& ( $arField["InputType"] != "textarea" ) ) {
            $arField["Cols"] = floor($arField["Size"] / 2 + 0.5);
            if ($arField['Cols'] > 100) {
                $arField['Cols'] = 100;
            }
        }
		switch( $arField["InputType"] )
		{
			case "select":
				if( !isset( $arField["Rows"] ) )
					$arField["Rows"] = 1;
				if( !isset( $arField["MultiSelect"] ) )
					$arField["MultiSelect"] = False;
				break;
			case "radio":
				if( !isset( $arField["RadioGlue"] ) )
					$arField["RadioGlue"] = " ";
				break;
			case "textarea":
				if( !isset( $arField["Cols"] ) )
					$arField["Cols"] = 80;
				if( !isset( $arField["Rows"] ) )
					$arField["Rows"] = 4;
				break;
			case "htmleditor":
				if(!isset($arField["Manager"])){
					if(!isset($arField["HtmlEditorClass"]))
						$arField["HtmlEditorClass"] = ConfigValue(CONFIG_HTML_EDITOR_CLASS);
					$sClass = $arField["HtmlEditorClass"];
					$objManager = new $sClass();
					$arField['Manager'] = $objManager;
				}
				if( !isset( $arField["CheckScripts"] ) )
					$arField["CheckScripts"] = True;
				break;
		}
		if( isset( $arField["Manager"] ) )
		{
			$arField["Manager"]->FieldName = $sFieldName;
			$arField["Manager"]->Field = &$arField;
			$arField["Manager"]->Form = &$this;
			$arField["Manager"]->CompleteField();
			if( !isset( $arField["CheckScripts"] ) )
				$arField["CheckScripts"] = False;
		}
		if( !isset( $arField["IncludeCaption"] ) )
			$arField["IncludeCaption"] = True;
		if( !isset( $arField["CheckScripts"] ) )
			$arField["CheckScripts"] = True;
		if(($arField["InputType"] == "date") && isset($arField["IncludeTime"]) && !isset($arField["TimeValue"]))
			$arField["TimeValue"] = "";

        if (!isset($arField['Nullable'])) {
            $arField['Nullable'] = true;
        }
        if (!isset($arField['Widgets'])) {
            $arField['Widgets'] = [];
        }
        if (!isset($arField['LookupTable'])) {
            $arField['LookupTable'] = null;
        }
	}

	// fill in missing field properties with defaults
	// should not be called directly.
	function CompleteFields()
	{
		global $Connection;
		if( !isset( $this->KeyField ) && isset($this->TableName) )
			$this->KeyField = $Connection->PrimaryKeyField( $this->TableName );
		foreach( $this->Fields as $sFieldName => &$arField )
		{
			$arField = &$this->Fields[$sFieldName];
			$this->CompleteField( $sFieldName, $arField );
			if( isset( $arField["Page"] ) && ( !isset( $this->Pages ) || !isset( $this->Pages[$arField["Page"]] ) ) )
				DieTrace( "Unknown page {$arField["Page"]} for field $sFieldName" );
			if( isset( $this->Pages ) && ( count( $this->Pages ) > 0 ) && !isset( $arField["Page"] ) )
				DieTrace( "Field $sFieldName - Page not set" );
		}
		// pages. check.
		if( isset( $this->Pages ) )
		{
			if( $this->IsPost )
				$this->ActivePage = ArrayVal( $_POST, "FormPage" );
			else
				$this->ActivePage = ArrayVal( $_GET, "Page" );
			$arPageNames = array_keys( $this->Pages );
			if( !isset( $this->Pages[$this->ActivePage] ) )
				$this->ActivePage = $arPageNames[0];
			$this->LastPage = $arPageNames[count( $this->Pages ) - 1];
			if( !isset( $this->Pages[$this->ActivePage] ) )
				DieTrace( "Unknown page {$this->ActivePage}" );
		}
	}

	// clear field values
	function Clear()
	{
		foreach( $this->Fields as $sFieldName => &$arField )
			$arField["Value"] = NULL;
	}

	// check one form field
	function CheckField( $sFieldName, &$arField )
	{
		// Check value by type
		if ( isset( $arField["Value"] ) )
		{
			if( is_array( $arField["Value"] ) )
				$arValues = $arField["Value"];
			else
				$arValues = array( $arField["Value"] );
			foreach( $arValues as $sValue )
			{
				if( isset( $arField["Options"] ) && !in_array( $sValue, array_keys( $arField["Options"] ) ) && !isset( $arField['AllowOtherOptions'] ) )
					$arField["Error"] = S_INVALID_VALUE . ": ".htmlspecialchars($sValue);
				if( isset( $arField["RegExp"] ) && !isset( $arField["Error"] ) )
					if( !preg_match( $arField["RegExp"], $sValue ) )
					{
						if( isset( $arField["RegExpErrorMessage"] ) )
							$arField["Error"] = $arField["RegExpErrorMessage"];
						else
							$arField["Error"] = S_INVALID_VALUE;
					}
				if( isset( $arField["Error"] ) )
					break;
				switch( $arField[ "Type" ] )
				{
					case "integer":
						if( (string)intval( $sValue ) != trim($sValue) )
							$arField["Error"] = S_EXPECTED_INTEGER;
						else
						if( isset( $arField["Min"] ) && ( $sValue < $arField["Min"] ) )
							$arField["Error"] = sprintf( S_NOT_LOWER_THAN, $arField["Min"] );
						else
							if( isset( $arField["Max"] ) && ( $sValue > $arField["Max"] ) )
							$arField["Error"] = sprintf( S_NOT_GREATER_THAN, $arField["Max"] );
						break;
					case "float":
						if( (string)floatval( $sValue ) != (string)$sValue )
							$arField["Error"] = S_EXPECTED_FLOAT;
						else
						if( isset( $arField["Min"] ) && ( $sValue < $arField["Min"] ) )
							$arField["Error"] = sprintf( S_NOT_LOWER_THAN, $arField["Min"] );
						else
							if( isset( $arField["Max"] ) && ( $sValue > $arField["Max"] ) )
							$arField["Error"] = sprintf( S_NOT_GREATER_THAN, $arField["Max"] );
						break;
					case "boolean":
						if( $sValue == "1" )
							$sValue = 1;
						else
							$sValue = 0;
						break;
					case "string":
						if( isset( $arField["Size"] ) )
						if( mb_strlen( $sValue ) > $arField["Size"] )
							$arField["Error"] = sprintf( S_LINE_TOO_LONG, $arField["Size"] );
						if( isset( $arField["MinSize"] ) )
						if( mb_strlen( $sValue ) < $arField["MinSize"] )
							$arField["Error"] = sprintf( S_LINE_TOO_SHORT, $arField["MinSize"] );
						break;
					case "datetime":
						break;
					case "date":
						if( !StrToDate( $sValue, isset( $arField["IncludeTime"] ) ) )
							$arField["Error"] = S_INVALID_DATE_FORMAT;
						if(isset( $arField["IncludeTime"] ) && !isset($arField["Error"])
						&& isset($arField["TimeValue"]) && ($arField["TimeValue"] != ""))
							if(!ParseTime( $arField["TimeValue"], $arTime ))
								$arField["Error"] = S_INVALID_TIME_FORMAT;
						break;
					case "html":
						break;
					default:
						DieTrace( "TForm->Check: Unknown form field type: {$arField[ "Type" ]}" );
				}
				if( isset( $arField["Error"] ) )
					break;
			}
		}
		else
			if( $arField["Type"] == "boolean" && $arField['Required'] )
				$arField["Value"] = 0;
		if( isset( $arField["Error"] ) )
			return;
		// Check not null
		if( isset( $arField["OnGetRequired"] ) )
		{
			if( is_array( $arField['OnGetRequired'] ) )
				$bRequired = CallUserFunc( array_merge( $arField['OnGetRequired'], array( $sFieldName, &$arField ) ) );
			else
				$bRequired = CallUserFunc( array( $arField['OnGetRequired'], $sFieldName, &$arField ) );
		}
		else
			$bRequired = $arField["Required"];
		if ( !isset( $arField["Value"] ) && $bRequired )
		{
			$arField["Error"] = S_FIELD_REQUIRED;
			return;
		}
		// check password matching
		if( ( $arField["InputType"] == "password" ) && isset( $this->Fields[$sFieldName . "Confirm"] ) )
		{
			if( $this->Fields[$sFieldName . "Confirm"]["Value"] != $arField["Value"] )
			{
				$arField["Error"] = S_PASSWORD_NOT_MATCH;
				return;
			}
		}
	}

	// preload post data to fields
	function LoadPostData( $arData )
	{
		foreach( array_keys( $this->Fields ) as $sFieldName )
		{
			// Find fields and set values
			$arField = &$this->Fields[ $sFieldName ];
			unset( $arField["Error"] );
			if(isset($arField['Value']))
				$arField['OldValue'] = $arField['Value'];
			else
				$arField['OldValue'] = null;
			$arField["Value"] = NULL;
			$Value = "";
			if( isset( $arField["Manager"] ) )
				$arField["Manager"]->LoadPostData( $arData );
			else
				$this->LoadFieldPostData( $sFieldName, $arField, $arData );
			if( !isset( $this->Error ) && isset( $arField["Error"] ) )
				$this->Error = $arField["Caption"] . ": " . $arField["Error"];
		}
	}

	// load post data for one field
	function LoadFieldPostData( $sFieldName, &$arField, $arData )
	{
		$Value = "";
		if(isset($arData[ $sFieldName ]) && is_array($arData[ $sFieldName ]))
			return;
		switch( $arField["InputType"] )
		{
			case "select":
				if( $arField["MultiSelect"] )
					$Value = ArrayVal( $arData, $sFieldName );
				else{
					if(isset($arField['Options']) && isset($arData[$sFieldName])
					&& isset($arField['Options'][$arData[$sFieldName]]))
						$Value = $arData[$sFieldName];
					else
						$Value = trim( stripslashes( ArrayVal( $arData, $sFieldName ) ) );
				}
				break;
			case "checkbox":
				if( ArrayVal( $arData, $sFieldName ) == "1" )
					$Value = "1";
				else
					$Value = "0";
				break;
			case "date":
				if(isset($arField["IncludeTime"]) && !is_array(ArrayVal( $arData, $sFieldName."Time" )))
					$arField["TimeValue"] = trim(stripslashes(ArrayVal( $arData, $sFieldName."Time" )));
			default:
				if ( isset( $arData[ $sFieldName ] ) )
					$Value = trim( $arData[ $sFieldName ] );
		}
		if( !$arField["HTML"] )
			$Value = htmlspecialchars( $Value );
		if( $Value != "" )
			$arField["Value"] = $Value;
	}


	// check specified data array (if data is not specified, assumed $_POST)
	// set $Error for entire form and each wrong field
	function Check( $arData = NULL )
	{
		if( !isset( $arData ) )
			$arData = &$_POST;
		$this->Error = NULL;
		$bSkipCheck = False;
		if( $this->IsPost && ( count( $_POST ) == 0 ) && ( count( $_FILES ) == 0 ) && ( intval( $_SERVER['CONTENT_LENGTH'] ) > 0 ) ) {
			$nMaxPostSize = StrToBytes( ini_get( 'post_max_size' ) );
			if( ( $nMaxPostSize > 0 ) && ( intval( $_SERVER['CONTENT_LENGTH'] ) > $nMaxPostSize ) )
				$this->Error = "You trying to upload to large data, no more than " . ( $nMaxPostSize / 1024 / 1024 ) . "Mb is allowed";
			else
				$this->Error = "Can't handle your request. Possible you are trying to upload too large file";
			return false;
		}
		$this->LoadPostData( $arData );
        $this->doOnLoaded();
        // csrf check
        if($this->CsrfEnabled && $this->IsPost && !isValidFormToken()){
            $this->Error = SESSION_HAS_EXPIRED;
            return false;
        }
		if( isset(  $this->Pages ) )
		{
			if( !isset( $arData["FormPage"] ) || !isset( $arData["NewFormPage"] ) ) {
				$this->Error = "Invalid POST data. Multi-paged form POST should contain page variables";
				return false;
			}
			$arPageNames = array_keys( $this->Pages );
			if( ( array_search( $arData["NewFormPage"], $arPageNames ) < array_search( $arData["FormPage"], $arPageNames ) )
			&& ( $this->ID == "0" ) )
				$bSkipCheck = True;
		}
		if( ( ArrayVal( $arData, "submitButton" ) == "" )
		&& isset( $arData["DisableFormScriptChecks"] )
		&& ( $arData["DisableFormScriptChecks"] == "1" )
		&& ( ArrayVal( $arData, "NewFormPage" ) == ArrayVal( $arData, "FormPage" ) ) )
			$bSkipCheck = True;
		if( !$bSkipCheck )
		{
			$arGroups = array();
			foreach( array_keys( $this->Fields ) as $sFieldName )
			{
				if( isset( $this->Error ) )
					break;
				$arField = &$this->Fields[ $sFieldName ];
				// skip checking if data from other page, or it's back operation
				if( isset( $this->Pages )
				&& ( ArrayVal( $arData, "submitButton" ) == "" )
				&& ( $arField["Page"] != $arData["FormPage"] ) )
					continue;
				if( isset( $arField["Manager"] ) )
					$arField["Error"] = $arField["Manager"]->Check( $arData );
				else
					$this->CheckField( $sFieldName, $arField );
				if( isset( $arField["RequiredGroup"] ) )
				{
					$sGroup = $arField["RequiredGroup"];
					if( !isset( $arGroups[$sGroup] ) )
						$arGroups[$sGroup] = array( "Fields" => array(), "Filled" => False );
					$arGroups[$sGroup]["Fields"][] = $sFieldName;
					switch( $arField["InputType"] )
					{
						case "checkbox":
							if( isset( $arField["Value"] ) && ( $arField["Value"] == "1" ) )
								$arGroups[$sGroup]["Filled"] = True;
							break;
						default:
							if( isset( $arField["Value"] ) )
								$arGroups[$sGroup]["Filled"] = True;
					}
				}
			}
			// set error?
			foreach( array_keys( $this->Fields ) as $sFieldName )
				if( isset( $this->Fields[$sFieldName]["Error"] ) )
				{
                    $dash = '-';
                    if($this->Fields[$sFieldName]["Caption"] == false)
                        $dash = '';
					if(!isset($this->Error))
						$this->Error = "<b class='errorPrefix'>" . S_ERROR . ": </b>{$this->Fields[$sFieldName]["Caption"]} {$dash} {$this->Fields[$sFieldName]["Error"]}";
					return false;
				}
			// required field groups
			if(!isset($this->Error))
				foreach( $arGroups as $arGroup )
					if( !$arGroup["Filled"] )
					{
						$arCaptions = array();
						foreach ( $arGroup["Fields"] as $sReqField )
						{
							$this->Fields[$sReqField]["Error"] = S_FIELD_REQUIRED;
							$arCaptions[] = $this->Fields[$sReqField]["Caption"];
						}
						$sGroupNames = implode( ", ", $arCaptions );
						$this->Error = sprintf( $this->RequiredGroupError, $sGroupNames );
						return false;
					}
			// ext check
			$this->Error = $this->DoOnCheck();
			if( isset( $this->Error ) )
				return false;
			// uniques
			$sWhere = NULL;
			if( $this->ID != 0 )
				$sWhere = "{$this->KeyField} <> " . $this->ID;
			if( isset( $this->Uniques ) )
				foreach( $this->Uniques as $Unique ){
					if(isset($Unique['AllowNulls'])){
						$haveNull = false;
						foreach( $Unique["Fields"] as $field){
							if(isset($this->Fields[$field]) && !isset($this->Fields[$field]["Value"])){
								$haveNull = true;
								break;
							}
						}
						if($haveNull)
							continue;
					}

                    foreach ($Unique["Fields"] as $field) {
                        if (!isset($this->Fields[$field])) {
                            continue 2;
                        }
                    }

					if( $this->Exists( $Unique["Fields"], $sWhere, ArrayVal( $Unique, "Table", null ) ) )
					{
						foreach( $Unique["Fields"] as $sValue){
							if( isset( $this->Fields[$sValue] ) ){
								if(isset($Unique['AllowNulls']) && !isset($this->Fields[$sValue]["Value"]))
									break;
								$this->Fields[$sValue]["Error"] = "&nbsp;";
							}
						}
						$this->Error = $this->FormatUniqueError($Unique);
						return false;
					}
				}
		}
		// all ok
		if( isset( $this->Pages ) && isset( $arData["NewFormPage"] ) && isset( $this->Pages[$arData["NewFormPage"]] ) )
			$this->ActivePage = $arData["NewFormPage"];
		if( isset( $this->Error ) )
			return false;
		return true;
	}

	function FormatUniqueError($Unique){
	    if (isset($Unique["ErrorMessage"])) {
            return $Unique["ErrorMessage"];
        }
	    return "Row with this " . implode(" and ", array_map(function(string $field) {
	        return $this->Fields[$field]["Caption"];
        }, $Unique["Fields"])) . " already exists";
	}

	// return field value as sql-compatible string
	// ( add ' for strings, converts dates etc.
	function SQLValue( $sFieldName, $arField = NULL )
	{
		global $Connection;
		if( !isset( $arField ) )
			$arField = &$this->Fields[$sFieldName];
		if( isset( $arField["Value"] ) )
		{
			$sValue = $arField["Value"];
			if( is_array( $sValue ) )
				$sValue = implode( ", ", $sValue );
			switch( $arField["Type"] )
			{
				case "string":
					if( isset( $arField["Encoding"] ) )
						switch( $arField["Encoding"] )
						{
							case "md5":
								return "'" . addslashes( md5( $sValue ) ) . "'";
								break;
							case "rsa":
								return "'" . addslashes( SSLEncrypt( $sValue ) ) . "'";
								break;
							default:
								DieTrace( "TBaseForm->SQLValue: Unknown encoding ({$arField["Encoding"]}) for field $sFieldName" );
						}
					else
						return "'" . addslashes( $sValue ) . "'";
					break;
				case "integer":
				case "float":
					return $arField["Value"];
					break;
				case "boolean":
					if( $arField["Value"] == "1" )
						return $Connection->BooleanInsertTrue;
					else
						return $Connection->BooleanInsertFalse;
					break;
                case "datetime":
					$s = $arField["Value"];
					return $Connection->DateTimeToSQL( strtotime( $s ), true );
					break;
				case "date":
					$s = $arField["Value"];
					if(isset($arField["IncludeTime"])
					&& isset($arField["TimeValue"]) && ($arField["TimeValue"] != ""))
						$s .= " ".$arField["TimeValue"];
					return $Connection->DateTimeToSQL( StrToDate( $s, isset( $arField["IncludeTime"] ) ), isset( $arField["IncludeTime"] ) );
					break;
				default:
					DieTrace( "SQLValue: $sFieldName: Unknown field type: {$arField["Type"]}" );
			}
		}
		else
            return $arField['Nullable'] ? 'null' : "''";
	}

	// external checking. return null or error message
	function DoOnCheck()
	{
		if( isset( $this->OnCheck ) )
			return CallUserFunc( $this->OnCheck );
		else
			return NULL;
	}

	// select form data from database
	function Select()
	{
		global $Connection, $Interface;
		$SQL = "select * from {$this->TableName} where {$this->KeyField} = {$this->ID}";
		$q = new TQuery( $SQL, $Connection );
		if( !$q->EOF )
			$this->SetFieldValues( $q->Fields );
		else
			if( $this->ID != 0 )
				throw new \AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException("Specified record does not exist.");
		$q->Close();
	}

	// return field values as associative array
	function GetFieldValues()
	{
		$Result = array();
		foreach( $this->Fields as $sFieldName => $arField )
			if( isset( $arField["Value"] ) )
				$Result[$sFieldName] = $arField["Value"];
			else
				$Result[$sFieldName] = NULL;
		return $Result;
	}

	// return forms text, as: ParamName1: ParamValue1\r\nParamName2: ParamValue2..
	// usually used to send form to email
	function GetText()
	{
		$Result = "";
		foreach( $this->Fields as $sFieldName => $arField )
			if( isset( $arField["Value"] ) ){
				$sValue = $arField["Value"];
				if(isset($arField["Options"][$sValue]))
					$sValue = $arField["Options"][$sValue];
				if($sFieldName == "Questions")
					$Result .=  "\n\n" . $sValue . "\n";
				else
					$Result .= $arField["Caption"] . ": " . $sValue . "\n";
			}
		return $Result;
	}

	// return prev, next, update buttons html
	function ButtonsHTML()
	{
		$sResult = "";
		if( ( $this->ID == 0 ) || ( !isset( $this->TableName ) ) )
		{
			if( isset( $this->PrevPage ) )
				$sResult .= "<input class='button' type='submit' name='prevButton' value=\"Back\" onclick=\"this.form.DisableFormScriptChecks.value = '1'; this.form.NewFormPage.value = '{$this->PrevPage}'; return CheckForm( document.forms['{$this->FormName}'] );\"/> ";
			if( isset( $this->NextPage ) )
				$sResult .= "<input class='button' type='submit' name='nextButton' value=\"Continue".($this->ShowPageOnButtons?" to {$this->Pages[$this->NextPage]} Page":"")."\" onclick=\"this.form.NewFormPage.value = '{$this->NextPage}'; return CheckForm( document.forms['{$this->FormName}'] )\"/>";
			if( !isset( $this->Pages ) || ( $this->ActivePage == $this->LastPage ) )
				$sResult .= "<input class='button' type='submit' name='submitButtonTrigger' value=\"".htmlspecialchars($this->SubmitButtonCaption)."\" onclick=\"if( CheckForm( document.forms['{$this->FormName}'] ) ) { this.form.submitButton.value='submit'; return true; } else return false;\"/>";
		}
		else
		{
			if( isset( $this->PrevPage ) )
				$sResult .= "<input class='button' type='submit' name='prevButton' value=\"Save and Back\" onclick=\"this.form.NewFormPage.value = '{$this->PrevPage}'; return CheckForm( document.forms['{$this->FormName}'] )\"/> ";
			if( isset( $this->NextPage ) )
				$sResult .= "<input class='button' type='submit' name='nextButton' value=\"Save and Continue\" onclick=\"this.form.NewFormPage.value = '{$this->NextPage}'; return CheckForm( document.forms['{$this->FormName}'] )\"/>";
			if( !isset( $this->Pages ) || ( $this->ActivePage == $this->LastPage ) )
				$sResult .= "<input class='button' type='submit' name='submitButtonTrigger' value=\"".htmlspecialchars($this->SubmitButtonCaption)."\" onclick=\"if( CheckForm( document.forms['{$this->FormName}'] ) ) { this.form.submitButton.value='submit'; return true; } else return false;\"/>";
		}
		return $sResult;
	}

	// formats form html
	// override to implement your own formatting
	function FormatHTML( $sHTML, $bExistsRequired ){

		$sRequiredWarning = "<tr><td>&nbsp;</td><td align='center'><span style='font-style: italic;'>" . S_REQUIRED_COMMENT . "</span></td></tr>\r\n";
		$nFieldCount = count( $this->Fields );
		if($bExistsRequired && ($nFieldCount>10))
			$sHTML = $sRequiredWarning . $sHTML . $sRequiredWarning;
		elseif($bExistsRequired && $nFieldCount<=10)
			$sHTML = $sRequiredWarning . $sHTML;
		if( isset( $this->Error ) )
			$sHTML = "<tr><td colspan='2' align='center'><span class='formerror'>" . $this->Error . "</span></td></tr>\n$sHTML";
		if($this->Title != "")
			$sHTML = "<tr><td colspan='2' align='center' style='border-bottom: solid #E0E0E0 1px; color: White; font-weight: bold;' bgcolor='".FORM_TITLE_COLOR."'>" . $this->Title . "</td></tr>\n" . $sHTML;
		$Result = "<form method='post' ".(isset($this->Action)?" action=\"".htmlspecialchars($this->Action)."\"":"")." enctype=\"multipart/form-data\" name=\"".htmlspecialchars($this->FormName)."\" style='margin-bottom: 0px; margin-top: 0px;'";
		if( $this->SubmitOnce )
			$Result .= " onsubmit='submitonce(this)'";
		if( isset( $this->SubmitURL ) )
			$Result .= " action='".htmlspecialchars($this->SubmitURL)."'";
		$Result .= ">
        <input type='hidden' name='FormToken' value='" . GetFormToken() . "'>
		<table cellspacing='0' cellpadding='5' border='0' class='formTable detailsTableDark'>
		$sHTML
		<tr><td colspan='2' align='center' height='35' style='border-top: solid #E0E0E0 1px;'>" . $this->ButtonsHTML() . "</td></tr></table></form>\n\n";
		return $Result;
	}

	// update pages data
	function UpdatePages()
	{
		if( isset( $this->Pages ) )
		{
			$arPageNames = array_keys( $this->Pages );
			$nActivePageIndex = array_search( $this->ActivePage, $arPageNames );
			if( $nActivePageIndex > 0 )
				$this->PrevPage = $arPageNames[$nActivePageIndex - 1];
			if( $nActivePageIndex < ( count( $arPageNames ) - 1 ) )
				$this->NextPage = $arPageNames[$nActivePageIndex + 1];
		}
	}

	function HiddenInputs(){
        $formToken = GetFormToken();
		$s = "<input type='hidden' name='FormToken' value='".htmlspecialchars($formToken)."'>
        <input type='hidden' name='DisableFormScriptChecks' value='0'/>
		<input type='hidden' name='submitButton'/>\n";
		if( isset( $this->Pages ) )
			$s .= "<input type='hidden' name='FormPage' value='".htmlspecialchars($this->ActivePage)."'/>\r\n"
			. "<input type='hidden' name='NewFormPage' value='".htmlspecialchars($this->ActivePage)."'/>\r\n";
		return $s;
	}

	// returns form html
	function HTML( $bFormatHTML = True )
	{
		$this->UpdatePages();
		$sResult = $this->HiddenInputs();
		$bExistsRequired = false;

		foreach( $this->Fields as $sFieldName => &$arField )
		{
			if( !isset( $arField["InputType"] ) )
				DieTrace( "Unknown InputType for field: $sFieldName" );
			if( !isset( $this->Pages ) || ( $this->ActivePage == $arField["Page"] ) )
			{
				if( isset( $arField["OnGetHTML"] ) )
				{
					$sFunc = $arField["OnGetHTML"];
					if( !isset( $arField["UnformattedHTML"] ) )
						$sResult .= $this->FormatRowHTML( $sFieldName, $arField, $sFunc( $sFieldName, $arField ) );
					else
						$sResult .= $sFunc( $sFieldName, $arField );
				}
				else
					if( isset( $arField["Manager"] ) )
					{
						if( !isset( $arField["UnformattedHTML"] ) ){
							$sResult .= $this->FormatRowHTML( $sFieldName, $arField, $arField["Manager"]->InputHTML() );
						}
						else
							$sResult .= $arField["Manager"]->InputHTML();
					}
					else
	 					$sResult .= $this->InputHTML( $sFieldName, $arField, $arField["IncludeCaption"] );
				if( $arField["Required"] )
					$bExistsRequired = True;
			}
			else
				if( isset( $arField["OnGetHidden"] ) )
				{
					$sFunc = $arField["OnGetHidden"];
					$sResult .= $sFunc( $sFieldName, $arField );
				}
				else
					if( isset( $arField["Manager"] ) )
						$sResult .= $arField["Manager"]->HiddenHTML();
					else
					{
						$sValue = ArrayVal( $arField, "Value" );
						if( $arField["HTML"] )
							$sValue = htmlspecialchars( $sValue );
						$sResult .= "<input type='hidden' name='".htmlspecialchars($sFieldName)."' value=\"$sValue\"/>\r\n";
					}
		}
		$sScripts = $this->CheckScripts();
		if( $bFormatHTML )
			$sResult = $this->FormatHTML( $sResult, $bExistsRequired );
		$sResult = $sResult . $sScripts;
		return $sResult;
	}
		
	// return check scripts body
	// override to do custom checks
	function CheckScriptsBody()
	{
		$sResult = "";
		$sResult .= "  if( Form.DisableFormScriptChecks.value != '1' )\n  {\n";
		$arGroups = array();
		foreach( $this->Fields as $sFieldName => $arField )
			if( ( !isset( $this->Pages ) || ( $this->ActivePage == $arField["Page"] ) )
			&& $arField["CheckScripts"] ){
				$sCheckScriptCondition = ArrayVal( $arField, "CheckScriptCondition" );
				if( $sCheckScriptCondition != "" )
					$sCheckScriptCondition = " $sCheckScriptCondition &&";
				if( $arField["InputType"] == "html" )
					continue;
				if( $arField["Required"] )
					if(isset($arField['Manager']))
						$sResult .= $arField['Manager']->FieldRequiredScripts($sFieldName, $arField, $sCheckScriptCondition);
					else
						$sResult .= $this->FieldRequiredScripts($sFieldName, $arField, $sCheckScriptCondition);
				if( isset( $arField["RequiredGroup"] ) )
					if(isset($arField['Manager']))
						$arGroups[$arField["RequiredGroup"]][$arField["Caption"]] = $arField['Manager']->RequiredGroupScripts($sFieldName, $arField);
					else
						$arGroups[$arField["RequiredGroup"]][$arField["Caption"]] = $this->RequiredGroupScripts($sFieldName, $arField);
			}
		foreach( $arGroups as $arGroup )
		{
			$sGroups = implode( " && ", $arGroup );
			if( $sGroups != "" )
			{
				$sGroupNames = implode( ", ", array_keys( $arGroup ) );
				$sResult .= "    if( $sGroups )\n    {\n       alert( \"" . sprintf( $this->RequiredGroupError, $sGroupNames ) . "\" );\n      return false;\n    }\n";
			}
		}
		foreach( $this->Fields as $sFieldName => $arField )
			if(isset($arField['Manager']))
				$sResult .= $arField['Manager']->FieldCheckScripts($sFieldName, $arField);
				
		$sResult .= " ".$this->UserCheckScripts." ";
		
		return $sResult;
	}

	// return required group scripts for one field
	function RequiredGroupScripts($sFieldName, $arField){
		switch( $arField["InputType"] )
		{
			case "checkbox":
				return "!Form.$sFieldName.checked";
			default:
				return "( trim( Form.$sFieldName.value ) == '' )";
		}
	}

	// return required field check scripts for one field
	function FieldRequiredScripts($sFieldName, $arField, $sCheckScriptCondition){
		switch( $arField["InputType"] )
		{
			case "radio":
				return "    if({$sCheckScriptCondition} !radioChecked( Form, '$sFieldName' ) )\n    {\n    alert( " . json_encode( sprintf( S_THIS_FIELD_REQUIRED, StripTags( $arField["Caption"] ) ) ) . " );\n      return( false );\n    }\n";
			case "select":
				return "    if({$sCheckScriptCondition} ( Form.$sFieldName !== undefined && Form.$sFieldName.value == '' ) )\n    {\n      Form.$sFieldName.focus();
alert( " . json_encode(sprintf( S_THIS_FIELD_REQUIRED, StripTags( $arField["Caption"] ) ) ) . " );\n      return( false );\n    }\n";
			default:
				return "    if({$sCheckScriptCondition} ( trim( Form.$sFieldName.value ) == '' ) )\n    {\n      Form.$sFieldName.focus();
alert( " . json_encode( sprintf( S_THIS_FIELD_REQUIRED, StripTags( $arField["Caption"] ) ) ) . " );\n      return( false );\n    }\n";
		}
	}

	// return client-side checking javascript
	function CheckScripts()
	{
		$sResult = "<script language='javascript' type='text/javascript'>\n\nfunction CheckForm( Form )\n{\n";
		$sResult .= $this->CheckScriptsBody();
		$sResult .= "  }\n";
		$sResult .= "  EnableFormControls( Form );\n";
		$sResult .= "  return true;\n}\n\n";
		$sResult .= "\nfunction EnableFormControls( Form )\n{\n";

		foreach( $this->Fields as $sFieldName => $arField ) {
            $sResult .= "  if (Form.$sFieldName !== undefined ) {\n";
            if ((!isset($this->Pages) || ($this->ActivePage == $arField["Page"]))
                && $arField["CheckScripts"] && !isset($arField["OnGetHTML"])
                && ($arField["InputType"] != "html"))
            {
                if ($arField["InputType"] == "radio") {
                    $sResult .= "  enableRadio( Form, '$sFieldName', true )\n";
                } else {
                    $sResult .= "  if( Form.$sFieldName.disabled )\n    Form.$sFieldName.disabled = false;\n";
                }
            }
            $sResult .= "}\n";
        }

		$sResult .= "}\n";
		foreach ( $this->Fields as $sField => $arField )
			if( ( $arField["InputType"] == "textarea" ) && isset( $arField["Size"] ) )
				$sResult .= "
{$sField}_alerted = false;
function {$sField}_keyup(what) {
    if (what.value.length > {$arField["Size"]}) {
        if (!{$sField}_alerted) alert('You\'ve reached the maximum allowed amount of characters you can type in this field.');
        what.value = what.value.substring(0,{$arField["Size"]}-1); // chop the last typed char
        {$sField}_alerted = true;
    }
}\n";
		$sResult .= "</script>\n\n";
		return $sResult;
	}

	// returns input html with title
	// override to implement your own formatting
	// $sInput - input html, example: "<input type=text name=Field1>"
	// will be formatted with title
	function FormatRowHTML( $sFieldName, $arField, $sInput )
	{
		$bgColor = "";
		if(isset($this->Fields[$sFieldName]["Error"]))
			$bgColor = " bgcolor = '#FCC9C9'";
		$sCaption = $arField["Caption"];
		if( $arField["Required"] && ( $arField["InputType"] != "checkbox" ) )
			$sCaption = "$sCaption(*)";

		$sNote = "";

		if (isset($arField['Links'])) {
		    $sNote .= $arField['Links'];
        }

		if( isset( $arField["Note"] ) ) {
            $sNote .= "<br><span class='fieldhint' id='fld{$sFieldName}Hint'>" . $arField["Note"] . "</span>";
        }

		switch( $arField["InputType"] )
		{
			case "checkbox":
				$s = "<tr valign='top{$bgColor}'>\n  <td>&nbsp;</td>\n  <td>$sInput <label for=\"fld{$sFieldName}\">$sCaption</label>$sNote</td>\n</tr>\n";
				break;
			default:
				if( $sCaption != "" )
					$sCaption .= ": ";
				else
					$sCaption = "&nbsp;";
				$s = "<tr valign='top{$bgColor}'>\n  <td><label for=\"fld{$sFieldName}\">$sCaption</label> </td>\n  <td>$sInput$sNote</td>\n  </tr>\n";
				break;
		}
		return $s;
	}

	// returns input html, f.x: "<input type=text name=field1>"
	function InputHTML( $sFieldName, $arField = NULL, $bIncludeCaption = False )
	{
		global $Config;
		if( !isset( $arField ) )
			$arField = $this->Fields[$sFieldName];
		if( isset( $arField["Value"] ) )
			$sValue = $arField["Value"];
		else
			$sValue = "";
		$sAttributes =  $arField["InputAttributes"];
        $sTimeAttributes = $arField["TimeInputAttributes"];
		if( stripos( $sAttributes, "id=" ) === false )
			$sAttributes .= " id=\"fld{$sFieldName}\"";
		switch( $arField["InputType"] )
		{
			case "date":
				if( isset( $arField["Size"] ) )
					$sAttributes .= " maxlength=" . $arField["Size"];
				if( isset( $arField["Cols"] ) )
					$sAttributes .= " size=" . $arField["Cols"];
				if( !isset( $arField['ReadOnly'] ) ){
					if(isset($arField['scwNextAction']))
						$scwNextAction = "scwNextAction={$arField['scwNextAction']}.runsAfterSCW(this); ";
					else
						$scwNextAction = "";
					$sAttributes .= " onclick='{$scwNextAction}scwShow(this, event)'";
					if( !$this->CalendarLinked ){
						$s = "<script language='javascript' type='text/javascript' src=\"/lib/scripts/scw.js?v=1\"></script>\n";
						if(isset($Config['RussianSite']))
							$s .= "<script>scwLanguage = 'ru'; scwDateOutputFormat = 'DD.MM.YYYY'</script>";
						if(DATE_FORMAT == 'd/m/Y')
							$s .= "<script>scwDateOutputFormat  = 'DD/MM/YYYY';</script>";
						$this->CalendarLinked = True;
					}
					else
						$s = "";
				}
				else
					$s = "";
				$s .= "<table border='0' cellpadding='0' cellspacing='0' class='noBorder' id='scw{$sFieldName}Table'>
				<tr>
				<td class='scwDate1'><input class='inputTxt inputDate' type='text'
				name='$sFieldName' $sAttributes value=\"" . htmlspecialchars( $sValue ) . "\"/></td>";
				if( !isset( $arField['ReadOnly'] ) )
					$s .= "<td class='scwDate2'><img src=\"/lib/images/calendar3.gif\" alt=\"Pick the date\" class=\"calicon\" width=\"20\" border=\"0\" height=\"22\" onclick=\"{$scwNextAction}scwShow(document.forms['{$this->FormName}']['{$sFieldName}'],event);\"></td>";
				if(isset($arField["IncludeTime"]))
					$s .= "<td class='scwDate3'>Time:&nbsp;</td><td class='scwDate4'><input class='inputTxt inputTime' type='text' size='7' id='fld{$sFieldName}Time' name='{$sFieldName}Time' {$sTimeAttributes} value=\"{$arField["TimeValue"]}\"/><div class='helpTime'>hh:mm or hh:mm pm</div></td>";
				$s .= "</tr></table>";
				break;
			case "text":
			case "password":
				if( isset( $arField["Size"] ) )
					$sAttributes .= " maxlength='{$arField['Size']}'";
				if( isset( $arField["Cols"] ) )
					$sAttributes .= " size='{$arField['Cols']}'";
				if( $arField["HTML"] )
					$s = htmlspecialchars( $sValue );
				else
					$s = $sValue;
				$s = "<input class='inputTxt' type='" . $arField["InputType"] . "' name='$sFieldName' $sAttributes value=\"" . $s . "\"/>";
				break;
			case "textarea":
				if( isset( $arField["Size"] ) )
					$sAttributes .= " maxlength='{$arField["Size"]}' onkeyup=\"{$sFieldName}_keyup(this)\"";
				if( isset( $arField["Cols"] ) )
					$sAttributes .= " cols='{$arField['Cols']}'";
				if( isset( $arField["Rows"] ) )
					$sAttributes .= " rows='{$arField['Rows']}'";
				if( $arField["HTML"] )
					$s = htmlspecialchars( $sValue );
				else
					$s = $sValue;
				$s = "<textarea class='inputTxt' id='fld$sFieldName' name='$sFieldName' $sAttributes>" . $s . "</textarea>";
				break;
			case "select":
				if( $arField["MultiSelect"] )
					$sInputName = $sFieldName . "[]";
				else
					$sInputName = $sFieldName;
				$s = "<select class='selectTxt' name='$sInputName' $sAttributes size='{$arField["Rows"]}'" . ( $arField["MultiSelect"] ? " multiple='multiple'" : "" ) . ">\n";
				$bSelected = False;
				foreach( $arField["Options"] as $sKey => $sValue )
				{
					if( isset( $arField["OptionAttributes"] ) && isset( $arField["OptionAttributes"][$sKey] ) )
						$sAttributes = " " . $arField["OptionAttributes"][$sKey];
					else
						$sAttributes = "";
					if( strpos( $sKey, " _" ) === 0 )
						$sKey = "";
					$s .= "<option value=\"" . htmlspecialchars($sKey) . "\"";
					if( ( $arField["MultiSelect"] && isset( $arField["Value"] ) && in_array( $sKey, $arField["Value"] ) )
					|| ( !$arField["MultiSelect"] && !$bSelected && array_key_exists( "Value", $arField ) && ( strval($arField["Value"]) == strval($sKey) ) ) )
					{
						$s .= " selected";
						$bSelected = True;
					}

                    if ($arField["HTML"]) {
                        $sValue = htmlspecialchars($sValue);
                    }

					$s .= "$sAttributes>"  . $sValue . "</option>\n";
				}
				$s .= "</select>";
				break;
			case "radio":
				$s = "";
				foreach( $arField["Options"] as $sKey => $sValue )
				{
					if( isset( $arField["OptionAttributes"] ) && isset( $arField["OptionAttributes"][$sKey] ) )
						$sAttributes = " " . $arField["OptionAttributes"][$sKey];
					else
						$sAttributes = "";
					$s .= "<table cellspacing='0' cellpadding='2' border='0' id='noBorder'><tr valign='top'><td><input $sAttributes id='fld{$sFieldName}{$sKey}' type='radio' name='$sFieldName' $sAttributes value=\"".htmlspecialchars($sKey)."\"";
					if( ( isset( $arField["Value"] ) && ( $arField["Value"] == $sKey ) )
					|| ( !isset( $arField["Value"] ) && ( $sKey == "" ) ) )
						$s .= " checked='checked'";
					$s .= "/></td><td> <label for='fld{$sFieldName}{$sKey}'>$sValue</label>{$arField["RadioGlue"]}</td></tr></table>\n";
				}
				break;
			case "checkbox":
				//$sAttributes .= " value=1";
				if( $arField["Value"] == "1" )
					$sAttributes .= " checked='checked'";
				$s = "<input type='checkbox' name='$sFieldName' value=\"1\" $sAttributes/>";
				break;
			case "html":
				$s = $arField["HTML"];
				break;
            case "hidden":
                $s = "<input type='hidden' name='$sFieldName' value=\"" . htmlspecialchars( $arField["Value"] ) . "\"/>";
				break;
			default:
				DieTrace( "TBaseForm->InputHTML: unknown InputType({$arField["InputType"]}) for field $sFieldName" );
		}
		if( $bIncludeCaption )
			$s = $this->FormatRowHTML( $sFieldName, $arField, $s );
		return $s;
	}

	function DrawButton($title, $attributes){
		return "<input class='button' type=submit {$attributes} value='{$title}'>\n";
	}

	// set form values from associative array
	function SetFieldValues( $arValues )
	{
		global $Connection;
		$arValues = array_change_key_case( $arValues, CASE_LOWER );
		foreach( $this->Fields as $sFieldName => &$arField )
		{
			$sLowerFieldName = strtolower( $sFieldName );
			//if(!$arField['Database'])
			//	continue;
			if( isset( $arField["Manager"] ) )
				$arField["Manager"]->SetFieldValue( $arValues );
			else
				if( array_key_exists( $sLowerFieldName, $arValues ) )
				{
					switch( $arField["Type"] )
					{
						case "boolean":
							if( ( $arValues[$sLowerFieldName] == $Connection->BooleanSelectTrue ) || ( $arValues[$sLowerFieldName] == "1" ) )
								$arField["Value"] = "1";
							if( ( $arValues[$sLowerFieldName] == $Connection->BooleanSelectFalse ) || ( $arValues[$sLowerFieldName] == "0" ) )
								$arField["Value"] = "0";
							break;
						case "float":
							$arField["Value"] = $arValues[$sLowerFieldName];
							if( $arField["Value"] != "" )
								$arField["Value"] = preg_replace( "/\.0+$/i", "", number_format( $arField["Value"], $arField["DecimalPlaces"], ".", "" ) );
							break;
						case "date":
							if( $arValues[$sLowerFieldName] != "" )
							{
								if( isset( $arField["IncludeTime"] ) )
									$arField["TimeValue"] = date( TIME_FORMAT, $Connection->SQLToDateTime( $arValues[$sLowerFieldName] ) );
								$arField["Value"] = date( DATE_FORMAT, $Connection->SQLToDateTime( $arValues[$sLowerFieldName] ) );
							}
							else
								$arField["Value"] = "";
							break;
						default:
							$arField["Value"] = $arValues[$sLowerFieldName];
					}
					if( isset( $arField["Encoding"] ) && ( $arField["Encoding"] == "md5" || $arField['Encoding'] == "symfonyPasswordEncoding" ) )
						$arField["Value"] = "";
					if( isset( $arField["Encoding"] ) && ( $arField["Encoding"] == "rsa" ))
						$arField["Value"] = SSLDecrypt($arField["Value"]);
					if( !isset( $arField["InputType"] ) ) {
						DieTrace("InputType not set for field $sFieldName");
					}
					if( ( $arField["InputType"] == "password" ) && isset( $this->Fields[$sFieldName . "Confirm"] ) )
						$this->Fields[$sFieldName . "Confirm"]["Value"] = $arField["Value"];
				}
		}
		$this->CompleteFields();
	}

	// fill "SQLValue" property of fields. see SQLValue method for details
	function CalcSQLValues()
	{
		foreach( $this->Fields as $sFieldName => &$arField )
			if( $arField["Database"] )
				$this->Fields[$sFieldName]["SQLValue"] = $this->SQLValue( $sFieldName );
	}

	// insert form to table. $arValues - additional parameters
	function Insert( $arValues = array() )
	{
		global $Connection;
		$this->CalcSQLValues();
		if( isset( $this->Filters ) )
			$arValues = array_merge( $arValues, $this->Filters );
		foreach( $this->Fields as $sFieldName => &$arField )
			if( $arField["Database"] )
				$arValues[$sFieldName] = $arField["SQLValue"];
		foreach( $this->Fields as $sFieldName => &$arField )
			if( isset( $arField["Manager"] ))
				$arField["Manager"]->GetSQLParams( $arValues, True );
//		$this->ID = NextKey( $this->TableName );
//		$arValues[$this->KeyField] = $this->ID;
		$Connection->Execute( InsertSQL( $this->TableName, $arValues ) );
		$this->ID = $Connection->InsertID();
	}

	// update form to table row. $arValues - additional parameters
	function Update( $arValues = array() )
	{
		global $Connection;
		$_GET['ID'] = intval($_GET['ID']);
		$this->ID = $_GET["ID"];
		$this->CalcSQLValues();
		if( isset( $this->Filters ) )
			$arValues = array_merge( $arValues, $this->Filters );
		foreach( $this->Fields as $sFieldName => &$arField )
			if( $arField["Database"] )
				$arValues[$sFieldName] = $arField["SQLValue"];
		foreach( $this->Fields as $sFieldName => &$arField )
			if( isset( $arField["Manager"] ))
				$arField["Manager"]->GetSQLParams( $arValues, False );
        if (!empty($arValues))
		    $Connection->Execute( UpdateSQL( $this->TableName, array( $this->KeyField => $this->ID ), $arValues ) );
	}

	// check whether edited row exists in target table
	function Exists( $KeyFields, $Where, $sTableName = null )
	{
		global $Connection;
		$this->CalcSQLValues();
		$arKeyValues = array();
		if( !isset( $sTableName ) )
			$sTableName = $this->TableName;
		foreach( $KeyFields as $sFieldName )
			if(isset($this->Fields[$sFieldName]) && $this->Fields[$sFieldName]["Database"])
				$arKeyValues[$sFieldName] = $this->Fields[$sFieldName]["SQLValue"];
			else
				if( isset( $this->Filters[$sFieldName] ) )
					$arKeyValues[$sFieldName] = $this->Filters[$sFieldName];
				else
					if( isset( $this->SQLParams[$sFieldName] ) )
						$arKeyValues[$sFieldName] = $this->SQLParams[$sFieldName];
					else
						DieTrace( "TBaseForm->Exists: Field $sFieldName not found in form" );
		$sSQL = "select 1 from {$sTableName} where " . ImplodeAssoc( " = ", " and ", $arKeyValues );
		$sSQL = str_replace(" = null", " is null", $sSQL);
		if( isset( $Where ) )
			$sSQL .= " and $Where";
		$objRS = New TQuery( $sSQL, $Connection );
		return !$objRS->EOF;
	}

	// edit form. shows add/edit form. then save form to database and redirect to SuccessURL
	// mode depends on QS "ID" parameter, in case of ID=0, or ID is missing - it's add
	// else edit
	// $bInteractive - if true - form is shown. else only check/save/redirect code is running
	function Edit( $bInteractive = True )
	{
		$bResult = false;
		$this->Select();
		if(isset($_GET['Copy'])){
			$this->ID = 0;
			$_GET['ID'] = 0;
			$_GET['ID'] = 0;
			$this->Action = preg_replace('/\?ID=\d+/ims', '?ID=0', $_SERVER['REQUEST_URI']);
			$this->Action = str_replace('&Copy=1', '', $this->Action);
		}
		if(!isset($_GET["ID"]))
			$_GET["ID"] = "0";
		$_GET['ID'] = intval($_GET['ID']);
		if( $this->IsPost )
		{
			$this->WantRedirect = ( ArrayVal( $_POST, "submitButton" ) != "" );
			$this->WantSave = false;
			$this->IsInsert = ( $_GET["ID"] == "0" );
			$this->Check();
			if( !isset( $this->Error ) && (
			$this->WantRedirect
			|| $this->WantSave
			|| ( ( $this->ID != "0" )
			&& ( !isset( $_POST["DisableFormScriptChecks"] )
			|| ( $_POST["DisableFormScriptChecks"] == "0" ) ) ) ) )
			{
				$this->IsInsert = ( $_GET["ID"] == "0" );
				if(!$this->ReadOnly)
					$this->Save();
				$bResult = true;
				if(ArrayVal($_GET, 'OnSave') == 'CloseAndNotify'){
					print "<script language='JavaScript' type='text/javascript'>execOnSaveForm('{$_GET['OnSave']}', {$this->ID});</script>";
				}
				else
					if( $this->WantRedirect ){
						if( isset( $this->SuccessURL ) )
						{
							$s = $this->SuccessURL;
							if( strpos( $s, "?" ) === false )
							{
								$arParams = $_GET;
								$arParams["PageBy" . $this->KeyField] = $this->ID;
								unset( $arParams["ID"] );
								$s .= "?" . ImplodeAssoc( "=", "&", $arParams, true );
								if( isset( $this->Filters ) )
									$s .= "&" . ImplodeAssoc( "=", "&", $this->Filters, true );
							}
							ScriptRedirect($s);
						}
					}
			}
		}
		else {
		    if ($this->ID === 0) {
                $this->SetFieldValues(array_filter($_GET, function ($x) { return $x !== ''; }));
            }

            $this->doOnLoaded();
        }
		if( $bInteractive )
			echo $this->HTML();
		return $bResult;
	}

	// saves form
	function Save(){
		if( $this->IsInsert )
			$this->Insert( $this->SQLParams );
		else
			$this->Update( $this->SQLParams );
		foreach( $this->Fields as $sFieldName => &$arField )
			if( isset( $arField["Manager"] ) )
				$arField["Manager"]->Save();
		$this->DoOnSave();
	}

	// on save edit handler
	function DoOnSave()
	{
		if( isset( $this->OnSave ) )
			if( is_string( $this->OnSave ) )
				CallUserFunc( array( $this->OnSave, $this->ID ) );
			else
				CallUserFunc( $this->OnSave );
	}

	// draws page navigation;
	// navigation allows form to navigate between form pages. see Fields["Page"] property
	// override to build custom html
	function DrawPageNavigation($items = NULL){
		global $pageColor;
		if( !isset( $items ) )
		{
			$items = array();
			if( $this->ID == "0" )
				$sDisableChecks = "form.DisableFormScriptChecks.value = '1'; ";
			else
				$sDisableChecks = "";
			foreach( $this->Pages as $sCode => $sCaption )
			{
				if( ( $this->ID == "0" ) && ( $this->ActivePage == $sCode ) )
					$sDisableChecks = "";
				$items[$sCode] = array(
					"caption"	=> $sCaption,
					"path"		=> "#",
					"selected"	=> ( $this->ActivePage == $sCode ),
					"onclick"	=> "form = document.forms['{$this->FormName}']; $sDisableChecks; if ( CheckForm( document.forms['{$this->FormName}'] ) ) { form.NewFormPage.value = '$sCode'; form.submit(); }; return false;" );
			}
		}
		$counter = 0;
		$unread = false;
		if($pageColor == "blue"){
			$darkColor = COLOR_BLUE;
			$darkStyle = "a12pxBldBlue";
			$cFull = "circleFullBlue2.jpg";
			$cBold = "circleBoldBlue2.jpg";
			$cEmpty = "circleBlue2.jpg";
		}
		else{
			$darkColor = COLOR_RED;
			$darkStyle = "a12pxBldRed";
			$cFull = "circleFull2.jpg";
			$cBold = "circleBold2.jpg";
			$cEmpty = "circle2.jpg";
		}
?>
	<table cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff">
<tr>
	<td colspan="2" height="20" style="color: <?=$darkColor?>; font-weight: bold; border-bottom: 2px solid <?=$darkColor?>;">Steps:</td>
</tr>
<tr>
	<td colspan="2" height="5"><img src="/lib/images/pixel.gif"></td>
</tr>
<?
		foreach($items as $key => $value ){
			$counter++;
			$textWeight = "normal";
			$stepBg = $cFull;
			$stepText = "White";
			if($unread){
				$stepBg = $cEmpty;
				$stepText = $darkColor;
			}
			if($value["selected"]){
	    		$stepBg = $cBold;
				$stepText = $darkColor;
				$textWeight = "bold";
				$unread = true;
			}
	    	if(isset($value["onclick"]))
	    		$sOnClick = " onclick=\"".htmlspecialchars($value["onclick"])."\"";
	    	else
	    		$sOnClick = "";
	?>
<tr>
	<td height="29" width="29" align="center" background="/lib/images/<?=$stepBg?>" style="background-repeat: no-repeat; color: <?=$stepText?>; font-size: 12px; font-weight: bold;" nowrap><?=$counter?></td>
	<td style="padding-left: 5px; padding-right: 5px;" nowrap><a style="font-weight: <?=$textWeight?>;" href="<?=$value["path"]?>" class="<?=$darkStyle?>"<?=$sOnClick?>><?=$value["caption"]?></a></td>
</tr>
<?if(count($items) != $counter){?>
<tr>
	<td colspan="2" height="5">
<div style="width: 15px; height: 5px; border-right: 1px <?=$darkColor?> solid;"><img src="/lib/images/pixel.gif"></div>
</td>
<?}?>
</tr>
	<?
		}
?>
</table>
<?
	}

	// draws page navigation;
	// navigation allows form to navigate between form pages. see Fields["Page"] property
	// override to build custom html
	function DrawPageNavigation2($items = NULL){
		global $pageColor;
		if( !isset( $items ) )
		{
			$items = array();
			if( $this->ID == "0" )
				$sDisableChecks = "form.DisableFormScriptChecks.value = '1'; ";
			else
				$sDisableChecks = "";
			foreach( $this->Pages as $sCode => $sCaption )
			{
				if( ( $this->ID == "0" ) && ( $this->ActivePage == $sCode ) )
					$sDisableChecks = "";
				$items[$sCode] = array(
					"caption"	=> $sCaption,
					"path"		=> "#",
					"selected"	=> ( $this->ActivePage == $sCode ),
					"onclick"	=> "form = document.forms['{$this->FormName}']; $sDisableChecks; if ( CheckForm( document.forms['{$this->FormName}'] ) ) { form.NewFormPage.value = '$sCode'; form.submit(); }; return false;" );
			}
		}
?>
<table cellspacing="0" cellpadding="5" border="0" bgcolor='#F5F2EB'>
<tr>
	<td>
	<table cellspacing="0" cellpadding="0" border="0">
<tr>
	<td colspan="2" height="5"><img src="/lib/images/pixel.gif"></td>
</tr>
<?
		$counter = 0;
		$unread = false;
		if($pageColor == "blue"){
			$darkColor = COLOR_BLUE;
			$darkStyle = "a12pxBldBlue";
		}
		else{
			$darkColor = COLOR_RED;
			$darkStyle = "a12pxBldRed";
		}
		foreach($items as $key => $value ){
			$counter++;
			$stepBg = $darkColor;
			$stepText = "White";
			$numberStyle = " border: 1px solid $darkColor;";
			$captionBg = "White";
			$captionText = $darkStyle;
			$captionStyle = " border: 1px solid $darkColor; border-left: 0px none;";
			if($value["selected"]){
	    		$stepBg = "White";
				$stepText = $darkColor;
				$captionBg = $darkColor;
				$captionText = "a12pxBldWht";
				$unread = true;
			}
			if($unread){
				$stepBg = "White";
				$stepText = $darkColor;
			}
	    	if(isset($value["onclick"]))
	    		$sOnClick = " onclick=\"".htmlspecialchars($value["onclick"])."\"";
	    	else
	    		$sOnClick = "";
	?>
<tr>
	<td height="23" width="30" align="center" bgcolor="<?=$stepBg?>" style="color: <?=$stepText?>; font-size: 12px; font-weight: bold;<?=$numberStyle?>" nowrap><?=$counter?></td>
	<td bgcolor="<?=$captionBg?>" style="padding-left: 5px; padding-right: 5px;<?=$captionStyle?>" nowrap><a href="<?=$value["path"]?>" class="<?=$captionText?>"<?=$sOnClick?>><?=$value["caption"]?></a></td>
</tr>
<tr>
	<td colspan="2" height="5"><img src="/lib/images/pixel.gif"></td>
</tr>
	<?
		}
?>
</table>
	</td>
</tr>
</table>
<?
	}

	// return form values as assoc array
	function Values()
	{
		$arParams = array();
		foreach( $this->Fields as $sFieldName => $arField )
			$arParams[$sFieldName] = $arField["Value"];
		return $arParams;
	}

    function isModified(array $fields = array(), bool $exclude = true) : bool
    {
        foreach ($this->Fields as $name => $field) {
            if (in_array($name, $fields) === $exclude) {
                continue;
            }

            if ($field['Value'] != $field['OldValue']) {
                return true;
            }
        }

        return false;
    }

    private function doOnLoaded()
    {
        if ($this->OnLoaded) {
            call_user_func($this->OnLoaded);
        }
    }

}
