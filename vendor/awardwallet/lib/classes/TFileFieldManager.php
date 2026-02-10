<?php

// -----------------------------------------------------------------------
// Picture Field manager class.
//		Contains class, to handle picture form field
//		if field name is "Image",
//		then underlying table should contain "ImageVer", "ImageExt" fields
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once( __DIR__."/TAbstractFieldManager.php" );
require_once( __DIR__."/../imageFunctions.php" );

class TFileFieldManager extends TAbstractFieldManager
{
	// allowed extensions
	var $Extensions = array( "doc", "xls", "avi", "rtf", "txt" );
	// virtual directory to save files
	var $Dir = NULL;
	// physical directory to save files
	var $Path = NULL;
	// file name prefix
	var $Prefix = "file";
	// file name prefix
	var $Suffix = "";
	// min file size
	var $MinSize = 1;
	// max file size
	var $MaxSize = 5242880;
	// store extended info in db: OriginalFilename, Width/Height, Size..
	var $StoreExtendedInfo = False;
	var $UpdateButtonCaption = "Update file";
	var $UploadButtonCaption = "Upload file";
	var $ShowUploadButton = true;
	// -------------------------------- private ------------------------------
	// ---------------------- do not set from outside class ------------------
	// current file info
	var $FileID;
	// preview photo url, without hostname, like /images/uploaded/temp/4543553.45.jpg
	var $FileVer;
	// big photo extensionm without ".", like jpg
	var $FileExt;
	// original uploaded file
	var $OriginalFilename;
	// physical path to save files. do not set
	var $sPath;
	// whether file was changed
	protected $FileChanged = False;

	// initialize field
	function CompleteField()
	{
		parent::CompleteField();
		$this->Field["Database"] = False;
		if( !isset( $this->Dir ) && !isset( $this->Path ) )
			DieTrace("Set Dir or Path");
		if( isset( $this->Dir ) && isset( $this->Path ) )
			DieTrace("Set Dir or Path, not both together");
		if( isset( $this->Dir ) )
			$this->Root = realpath(__DIR__ . '/../..') . $this->Dir;
		else
			$this->Root = $this->Path;
	}

	// set field values
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		$sVer = ArrayVal( $arValues, strtolower( $this->FieldName . "Ver" ) );
		$sExt = ArrayVal( $arValues, strtolower( $this->FieldName . "Ext" ) );
		if( $sVer != "" )
		{
			$this->FileID = $arValues[strtolower($this->Form->KeyField)];
			$this->FileExt = $sExt;
			$this->FileVer = $sVer;
			$this->OriginalFilename = ArrayVal( $arValues, strtolower( $this->FieldName ) . "originalfilename" );
		}
		else
		{
			$this->FileExt = NULL;
			$this->FileVer = NULL;
			$this->OriginalFilename = NULL;
		}
	}

	function FileURL()
	{
		if( isset( $this->Dir ) || ( $this->FileID < 0 ) )
			return FilePath( $this->Dir, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix );
		else
			return "/lib/file/getFile.php?ID={$this->FileID}&Ver={$this->FileVer}";
	}

	// get field html
	function InputHTML($sFieldName = null, $arField = null){
		$sCaption = $this->UploadButtonCaption;
		$sAttributes =  $this->Field["InputAttributes"];
		if( isset( $this->Field["Cols"] ) )
			$sAttributes .= " size=" . $this->Field["Cols"];
		$s = "";
		if( isset( $this->FileVer ) )
		{
			$sPreview = "<a href=\"" . $this->FileURL() . "\">Download file";
			if( $this->StoreExtendedInfo )
				$sPreview .= " {$this->OriginalFilename} (" . round( filesize( FilePath( $this->Root, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix, $this->Suffix, True ) ) / 1024/ 1024, 3 ) . "M)<br>";
			$sPreview .= "</a><br>\n";
		}
		else
			$sPreview = "";
		if( $sPreview != "" )
		{
			$s .= $sPreview;
			if( !$this->Field["Required"] )
				$s .= "<input type=checkbox name=" . $this->FieldName . "Delete value=1> " . S_DELETE . "<br>\n";
			$sCaption = $this->UpdateButtonCaption;
		}
		$s .= "<input class='inputTxt fileField' type=file name=$this->FieldName $sAttributes><br>"
		. "<input type=hidden name={$this->FieldName}Changed value=\"" . intval( $this->FileChanged ) ."\"><input type=hidden name={$this->FieldName}AddButton value=\"\">";
		if( $this->ShowUploadButton )
			$s .= "<input class='button' type=submit name={$this->FieldName}AddButtonTrigger onclick=\"this.form.{$this->FieldName}AddButton.value='submit'; this.form.DisableFormScriptChecks.value='1';\" value='".htmlspecialchars($sCaption)."'>\n";
		$s .= $this->HiddenHTML();
		return $s;
	}

	// hidden fields html
	function HiddenHTML()
	{
		$s = "<input type=hidden name={$this->FieldName}Changed value=\"" . intval( $this->FileChanged ) ."\">";
		if( isset( $this->FileVer ) )
			$s .= "<input type=hidden name={$this->FieldName}Ver value=\"".htmlspecialchars($this->FileVer)."\">\n";
		if( isset( $this->FileExt ) )
			$s .= "<input type=hidden name={$this->FieldName}Ext value=\"".htmlspecialchars($this->FileExt)."\">\n";
		if( isset( $this->OriginalFilename ) )
			$s .= "<input type=hidden name={$this->FieldName}OriginalFilename value=\"".htmlspecialchars($this->OriginalFilename)."\">\n";
		if( isset( $this->FileID ) )
			$s .= "<input type=hidden name={$this->FieldName}FileID value=\"".htmlspecialchars($this->FileID)."\">\n";
		return $s;
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		if( isset( $arData["{$this->FieldName}Ver"] ) )
			$this->FileVer = floatval($arData["{$this->FieldName}Ver"]);
		if( isset( $arData["{$this->FieldName}Changed"] ) )
			$this->FileChanged = intval( $arData["{$this->FieldName}Changed"] );
		if( isset( $arData["{$this->FieldName}Ext"] ) && in_array(strtolower($arData["{$this->FieldName}Ext"]), $this->Extensions) )
			$this->FileExt = $arData["{$this->FieldName}Ext"];
		if( isset( $arData["{$this->FieldName}OriginalFilename"] ) )
			$this->OriginalFilename = $arData["{$this->FieldName}OriginalFilename"];
		if( isset( $arData["{$this->FieldName}FileID"] ) )
			$this->FileID = $arData["{$this->FieldName}FileID"];
		if( ( ArrayVal( $arData, "{$this->FieldName}AddButton" ) != "" )
		&& ( ArrayVal( $arData, "DisableFormScriptChecks" ) == "1" ) )
			$this->Field["Error"] = $this->Check( $arData );
	}

	// check field. return NULL or error message
	function Check( &$arData )
	{
		$sHelp = "";
		if(strcasecmp(SITE_NAME, "EverythingEquus.com") == 0)
			$sHelp = " For help on this task please refer to <a style='font-weight: bold; color: Red' target='_blank' href='/resources/Photo-Editing-Basics.php'>Help</a>.";
		// check uploaded picture
		if( ( ( ArrayVal( $arData, "{$this->FieldName}AddButton" ) != "" )
		|| ( ArrayVal( $arData, "submitButton" ) != "" )
		|| ( $this->Form->ID != "0" ) ) && isset($_FILES[ $this->FieldName ]) )
		{
			if( $_FILES[ $this->FieldName ]['error'] == 0 )
			{
				DeleteFiles( realpath(__DIR__ . '/../..') . "/images/uploaded/temp/*", SECONDS_PER_DAY * 7 );
				$arFile = $_FILES[ $this->FieldName ];
				if( $arFile["size"] < $this->MinSize )
					return sprintf( "The file you are trying to upload is too small.
					The current size of your file is %s MB.  Please increase the size
					of your file to %s MB or more." . $sHelp, number_format( $arFile["size"] / 1024 / 1024, 1 ), number_format( $this->MinSize / 1024 / 1024, 1 ) );
				if( $arFile["size"] > $this->MaxSize )
					return sprintf( "The file you are trying to upload is too large.
					The current size of your file is %s MB.  Please reduce the size
					of your file to %s MB or less." . $sHelp,
					number_format( $arFile["size"] / 1024 / 1024, 1 ), number_format( $this->MaxSize / 1024 / 1024, 1 ) );
				if( ( count( $this->Extensions ) > 0 ) && !preg_match( "/\.(" . implode( "|", $this->Extensions ) . ")$/i", $arFile["name"] ) )
					return sprintf( S_INVALID_FILE_EXTENSION, implode( ", ", $this->Extensions ) );
				$nFileVer = microtime( true );
				$nFileID = -1 * $nFileVer;
				if( !preg_match( "/\.(\w+)$/i", $_FILES[$this->FieldName]['name'], $arMatches ) )
					DieTrace( "Can't detect file extension" );
				$sFileExt = strtolower( $arMatches[ 1 ] );
				// move uploaded file to temp dir
				$sError = $this->MoveUploadedFile( $nFileID, $nFileVer, $sFileExt );
				if( isset( $sError ) )
					return $sError;
				// all ok. file uploaded. set result
				$this->OriginalFilename = $_FILES[$this->FieldName]["name"];
				$this->FileExt = $sFileExt;
				$this->FileVer = $nFileVer;
				$this->FileID = $nFileID;
			}
			else
				switch( $_FILES[ $this->FieldName ]['error'] )
				{
					case UPLOAD_ERR_NO_FILE:
						if( !isset( $_POST["{$this->FieldName}Delete"] )
						&& ( ( $this->Field["Required"] && !isset( $this->FileVer ) ) ) )
							return "File is not selected";
						break;
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						return "File is too big";
					case UPLOAD_ERR_PARTIAL:
						return "File was uploaded partialy. Please try again.";
				}
		}
		else
			if( $this->Field["Required"] )
				return S_FIELD_REQUIRED;
		// remove picture, check
		if( ( ArrayVal( $_POST, "{$this->FieldName}Delete") != "" ) && isset( $this->FileID ) )
		{
			unset( $this->FileID );
			unset( $this->FileVer );
			unset( $this->FileExt );
			unset( $this->OriginalFilename );
		}
		// if edit, then save picture to db
		/*if( ( ArrayVal( $_POST, "{$this->FieldName}AddButton" ) != "" ) && ( $this->Form->ID != "0" ) )
		{
			$arFields = array();
			$this->GetSQLParams( $arFields, False );
			$Connection->Execute( UpdateSQL( $this->Form->TableName, array( $this->Form->KeyField => $this->Form->ID ), $arFields ) );
			$this->Save();
		}*/
		return NULL;
	}

	// move uploaded file to destination dir
	// return null or error
	function MoveUploadedFile( $nFileID, $nFileVer, $sFileExt )
	{
		$sOriginalFile = realpath(__DIR__ . '/../..') . FilePath( $this->Root, $nFileID, $nFileVer, $sFileExt, $this->Prefix );
		MkDirs( dirname( $sOriginalFile ) );
		if( !move_uploaded_file( $_FILES[$this->FieldName]['tmp_name'], $sOriginalFile ) )
			DieTrace( "Can't move uploaded file" );
		return null;
	}

	// get addional sql parameters, for update or insert call.
	function GetSQLParams( &$arFields, $bInsert )
	{
		if( !isset( $this->FileID ) )
		{
			$arFields[$this->FieldName . "Ver"] = 'null';
			$arFields[$this->FieldName . "Ext"] = 'null';
		}
		else
		{
			$nDBVersion = intval( $this->FileVer );
			$arFields[$this->FieldName . "Ver"] = $nDBVersion;
			$arFields[$this->FieldName . "Ext"] = "'" . addslashes( $this->FileExt ) . "'";
			if( ( $this->FileID < 0 ) && $this->StoreExtendedInfo )
			{
				$this->GetFileInfo( FilePath( $this->Root, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), $arFields );
				$arFields[$this->FieldName."OriginalFilename"] = "'" . addslashes( $this->OriginalFilename ) . "'";
			}
		}
	}

	function GetFileInfo( $sFile, &$arFields )
	{
		if( strpos( $sFile, "/images/uploaded/temp" ) === 0 )
			$sFile = realpath(__DIR__ . '/../..') . $sFile;
		if( !file_exists( $sFile ) )
			DieTrace("File is missing");
		$arFields[$this->FieldName."OriginalSize"] = filesize( $sFile );
	}

	function MoveFile( $sFrom, $sTo )
	{
		MkDirs( dirname( $sTo ) );
		rename( $sFrom, $sTo );
	}

	// saving
	function Save()
	{
		if( isset( $this->FileID ) && ( $this->FileID < 0 ) )
		{
			DeleteFiles( FilePath( $this->Root, $this->Form->ID, "*", "*" ) );
			$this->MoveFile( realpath(__DIR__ . '/../..') . FilePath( $this->Root, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), FilePath( $this->Root, $this->Form->ID, intval( $this->FileVer ), $this->FileExt, $this->Prefix ) );
			$this->FileVer = intval( $this->FileVer );
			$this->FileID = $this->Form->ID;
		}
	}

}

?>
