<?php

// -----------------------------------------------------------------------
// Pictures Field manager class.
//		Contains class, to handle pictures containing in linked table
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once( __DIR__ . "/../imageFunctions.php" );

class TPicturesFieldManager extends TAbstractFieldManager
{
	// allowed extensions
	var $Extensions = array( "jpg", "jpeg", "gif", "png", "jpe" );
	// virtual directory to save files
	var $Dir;
	// physical path to save files
	var $Path;
	// file name prefix
	var $Prefix;
	// keep original file in $Dir . "/original"
	var $KeepOriginal = False;
	// min file size
	var $MinSize = 1;
	// max file size
	var $MaxSize = 1572864;
	// table info
	var $TableName;
	var $KeyField;
	// primary PictureID field in main form table, may be null
	var $PrimaryPictureField;
	// pictures. integer-indexed(auto) array of arrays: array( [0] => array(
	//	"ID" => integer ID from database, negative if picture is not saved to database yet
	//	"Caption" => picture caption
	//	"URL" => big image URL, like /images/uploaded/temp/4395890485.65.jpg
	//	"ThumbURL" => preview url, like /images/uploaded/temp/4395890485.65.gif ), ..
	var $Pictures = array();
	// current primary picture id
	var $PrimaryPictureID;
	// max picture count
	var $MaxPictureCount;
	// checks done
	var $Checked = False;
	// root of album. do not set directly
	var $sPath;
	// save picture properties: width/height/filename to database
	var $StoreExtendedProperties = False;
	var $pictureCaption = "Picture:";
	var $captionCaption = "Caption:";
	var $makePrimaryCaption = "Make primary";
	var $primaryCaption = "Primary";
	var $removeCaption = "Remove";
	// initialize field
	function CompleteField()
	{
		global $Connection;
		parent::CompleteField();
		$this->Field["Database"] = False;
		$this->KeyField = $Connection->PrimaryKeyField( $this->TableName );
		if( !isset( $this->Dir ) && !isset( $this->Path ) )
			DieTrace("Set Dir or Path");
		if( isset( $this->Dir ) && isset( $this->Path ) )
			DieTrace("Set Dir or Path, not both together");
		if( !isset( $this->Form->Fields[$this->FieldName]["Fields"] ) )
			$this->Form->Fields[$this->FieldName]["Fields"] = array(
				"Caption" => array(
					"Type" => "string",
					"InputAttributes" => "style=\"width: 150px;\"",
					"Size" => 32,
				) );
		$this->Form->CompleteField( "AddPictureCaption", $this->Form->Fields[$this->FieldName]["Fields"]["Caption"] );
		if( !isset( $this->ItemCaption ) )
			$this->ItemCaption = "Picture";
		if( isset( $this->Dir ) )
			$this->Root = realpath(__DIR__ . '/../..') . $this->Dir;
		else
			$this->Root = $this->Path;
	}

	// set field values
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		global $Connection;
		$q = new TQuery( "select {$this->KeyField} as ID, Caption,
		ImageVer, ImageExt, " . ( $this->StoreExtendedProperties ? "OriginalFilename" : "''" ) . " as OriginalFilename
		from {$this->TableName} where {$this->Form->KeyField} = {$this->Form->ID}" );
		while( !$q->EOF )
		{
			$this->Pictures[] = $q->Fields;
			$q->Next();
		}
		if( isset( $this->PrimaryPictureField ) )
			$this->PrimaryPictureID = $arValues[strtolower( $this->PrimaryPictureField )];
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		$nCount = intval(ArrayVal($_POST, "PictureCount", 0));
		$this->Pictures = array();
		for( $n = 0; $n < $nCount; $n++ )
			if(isset($_POST["Picture{$n}ID"]) && isset($_POST["Picture{$n}Caption"])
			&& isset($_POST["Picture{$n}ImageVer"]) && isset($_POST["Picture{$n}ImageVer"]))
				$this->Pictures[] = array(
					"ID" => floatval( $_POST["Picture{$n}ID"] ),
					"Caption" => $_POST["Picture{$n}Caption"],
					"ImageVer" => $_POST["Picture{$n}ImageVer"],
					"ImageExt" => $_POST["Picture{$n}ImageExt"],
					"OriginalFilename" => ArrayVal( $_POST, "Picture{$n}OriginalFilename" ),
					);
		if( isset( $this->PrimaryPictureField ) )
			$this->PrimaryPictureID = ArrayVal($_POST, "PrimaryPictureID", null);
		if( !isset( $this->Form->Error ) && ( ( ArrayVal( $arData, "RemovePicture" ) != "" )
		|| isset( $arData["AddPictureButton"] ) ) )
		{
			$this->Form->Error = $this->Check( $arData );
			if( isset( $this->Form->Error ) )
			{
				$this->Form->Error = $this->Form->Fields[$this->FieldName]["Caption"] . ": " . $this->Form->Error;
				$this->Form->Fields[$this->FieldName]["Error"] = $this->Form->Error;
			}
		}
		if( ArrayVal( $arData, "RotatePictureID" ) != "" )
			foreach ( $this->Pictures as &$arPicture )
				if( $arPicture["ID"] == $arData["RotatePictureID"] )
					$this->RotatePicture( $arPicture, $arData['RotateAngle'] );
	}

	// rotate picture 90, -90 or 180
	function RotatePicture( &$arPicture, $nAngle ) {
	    $rootPath = realpath(__DIR__ . '/../..');
		if( $this->KeepOriginal && file_exists( $rootPath . $this->PictureURL( $arPicture, "original" ) ) )
			$sSource = $rootPath . $this->PictureURL( $arPicture, "original" );
		else
			$sSource = $rootPath . $this->PictureURL( $arPicture, "large" );
		if( !RotateImage( $sSource, $nAngle, false ) )
			return false;
		if( $arPicture["ID"] > 0 )
			$arPicture["ImageVer"] = time();
		else
			$arPicture["ImageVer"] = microtime(true);
		DeleteFiles( PicturePath( $this->Root, "small", $arPicture["ID"], "*", "*", $this->Prefix ) );
		ScaleImage( $sSource, $rootPath . $this->PictureURL( $arPicture, "small" ), THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT );
		DeleteFiles( PicturePath( $this->Root, "medium", $arPicture["ID"], "*", "*", $this->Prefix ) );
		ScaleImage( $sSource, $rootPath . $this->PictureURL( $arPicture, "medium" ), MEDIUM_WIDTH, MEDIUM_HEIGHT );
		if( $this->KeepOriginal ) {
			DeleteFiles( PicturePath( $this->Root, "large", $arPicture["ID"], "*", "*", $this->Prefix ) );
			ScaleImage( $sSource, $rootPath . $this->PictureURL( $arPicture, "large" ), PICTURE_WIDTH, PICTURE_HEIGHT );
			copy( $sSource, $rootPath . $this->PictureURL( $arPicture, "original" ) );
		} else
			copy( $sSource, $rootPath . $this->PictureURL( $arPicture, "large" ) );
		unlink( $sSource );
	}

	// get primary picture url or null
	function PrimaryPicture()
	{
		if( !isset( $this->PrimaryPictureID ) )
			return null;
		foreach( $this->Pictures as $arPicture )
			if( $arPicture["ID"] == $this->PrimaryPictureID )
				return $arPicture;
		return null;
	}

	// get field html
	function 	InputHTML($sFieldName = null, $arField = null)
	{
#		$this->Form->Fields["AddPictureCaption"]["InputAttributes"] = "style=\"width: 200px;\"";
#		$this->Form->Fields["AddPictureCaption"]["Size"] = 30;
		// show table
		$bulletHTML = "<tr><td width='10'><img style='margin-top: 4px;' src='/lib/images/bulletRed1.gif'></td>";
		$s = "<input type=hidden name=PictureCount value=\"" . count( $this->Pictures ) . "\">\n";
		if( isset( $this->PrimaryPictureField ) )
			$s .= "<input type=hidden name=PrimaryPictureID value=\"" . ( !isset( $this->PrimaryPictureID ) ? "" : $this->PrimaryPictureID ) . "\">\n";
		if( count( $this->Pictures ) > 0 )
		{
			$s .= "<script>
			function rotatePicture( id, angle ) {
				f = document.forms['editor_form'];
				f.RotatePictureID.value=id;
				f.RotateAngle.value=angle;
				f.DisableFormScriptChecks.value='1';
				CheckForm( f );
				f.submit();
				return false;
			}
			</script>";
			$s .= "<table class='noBorder' style='padding: 2px;' border='1' cellpadding='0' cellspacing='2'>\n";
			$s .= "<input type=hidden name=RemovePicture value=\"\">\n";
			$s .= "<input type=hidden name=RotatePictureID value=\"\">\n";
			$s .= "<input type=hidden name=RotateAngle value=\"\">\n";
			$n = 0;
			foreach( $this->Pictures as $arPicture )
			{
				if( ( $n % 3 ) == 0 )
					$s .= "  <tr>\n";
				$s .= "    <td align=left valign='top'><a href='#' onclick=\"openAWindow( '" . $this->PictureURL( $arPicture, "large" ) . "', 'Preview', " . ( PICTURE_WIDTH + 20 ). ", " . ( PICTURE_HEIGHT + 20 ). ", 1, 0 ); return false;\"><img src=\"" . $this->PictureURL( $arPicture, "small" ) ."\" border='0'></a>";
				$s .= "<div style='padding: 4 0 0 0'>
				<a href='#' onclick=\"return rotatePicture( {$arPicture["ID"]}, 3 )\"><img src=/lib/images/90ccw.gif width=21 height=21 border=0></a>
				<a href='#' onclick=\"return rotatePicture( {$arPicture["ID"]}, 2 )\"><img src=/lib/images/180.gif width=21 height=21 border=0></a>
				<a href='#' onclick=\"return rotatePicture( {$arPicture["ID"]}, 1 )\"><img src=/lib/images/90cw.gif width=21 height=21 border=0></a>
				</div>";
				$s .= "<div style='padding: 4 0'><input class=pictureCaptionEdit type=text name=Picture{$n}Caption value=\"" . ArrayVal( $arPicture, "Caption" ) . "\" maxlength=32></div>";
				$s .= "<table style='padding: 0px;' border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
				$s .= $bulletHTML;
				if( $arPicture["ID"] != $this->PrimaryPictureID )
					$s .= "<td><a href='#' onclick=\"f = document.forms['editor_form']; f.PrimaryPictureID.value='{$arPicture["ID"]}'; f.DisableFormScriptChecks.value='1'; CheckForm( f ); f.submit(); return false;\" style='font-size: 9px;'>{$this->makePrimaryCaption}</a></td></tr>";
				else
					$s .= "<td><span style='font-size: 9px;'>{$this->primaryCaption}</span></td></tr>";
				$s .= $bulletHTML . "<td><a href='#' onclick=\"if(confirm('Are you sure you want to delete this picture?')){f = document.forms['editor_form']; f.RemovePicture.value='{$arPicture["ID"]}'; f.DisableFormScriptChecks.value='1'; CheckForm( f ); f.submit(); return false;} else{return false;}\"  style='font-size: 9px;'>{$this->removeCaption}</a></td></tr></table></td>\n";
				$s .= $this->CommonHiddens( $n, $arPicture );
				$n++;
				if( ( $n % 3 ) == 0 )
					$s .= "  </tr>\n";
			}
			while( ( $n	% 3 ) != 0 )
			{
				$s .= "    <td>&nbsp;</td>\n";
				$n++;
			}
			$s .= "</table>\n";
		}
		$sCaption = "Add";
		if( isset( $this->ItemCaption ) )
			$sCaption .= " " . $this->ItemCaption;
		if(isset($this->buttonCaption))
			$sCaption = $this->buttonCaption;
		$s .= "<table cellspacing='0' cellpadding='3' border='1' class='noBorder'><tr><td>{$this->pictureCaption}</td><td><input class='inputTxt' style='width: 300px;' type=file name=AddPictureImage></td></tr>"
		. "<tr><td>{$this->captionCaption}</td><td>" . $this->Form->InputHTML( "AddPictureCaption", $this->Field["Fields"]["Caption"], false )
		. "</td></tr><tr><td colspan='2' align='center'><input type=hidden name=AddPictureButton><input class='button' type=submit name=AddPictureButtonTrigger value=\"$sCaption\" onclick=\"this.form.DisableFormScriptChecks.value='1';CheckForm( this.form );this.form.AddPictureButton.value='submit';\"></td></table>\n";
		return $s;
	}

	function PictureURL( $arPicture, $sSize )
	{
		if( isset( $this->Dir ) )
			return PicturePath( $this->Dir, $sSize, $arPicture["ID"], $arPicture["ImageVer"], $arPicture["ImageExt"], $this->Prefix );
		else
			return "/album/getImage.php?ID={$arPicture["ID"]}&Type=$sSize&Ver={$arPicture["ImageVer"]}";
	}

	function CommonHiddens( $n, $arPicture )
	{
		$s = "";
		$s .= "<input type=hidden name=Picture{$n}ImageVer value=\"{$arPicture["ImageVer"]}\">";
		$s .= "<input type=hidden name=Picture{$n}ImageExt value=\"{$arPicture["ImageExt"]}\">";
		$s .= "<input type=hidden name=Picture{$n}OriginalFilename value=\"{$arPicture["OriginalFilename"]}\">";
		$s .= "<input type=hidden name=Picture{$n}ID value=\"{$arPicture["ID"]}\">\n";
		return $s;
	}

	// hidden fields html
	function HiddenHTML()
	{
		$s = "<input type=hidden name=PictureCount value=\"" . count( $this->Pictures ) . "\">\n";
		if( isset( $this->PrimaryPictureField ) )
			$s .= "<input type=hidden name=PrimaryPictureID value=\"" . $this->PrimaryPictureID . "\">\n";
		$n = 0;
		foreach( $this->Pictures as $arPicture )
		{
			$s .= $this->CommonHiddens( $n, $arPicture );
			$s .= "<input type=hidden name=Picture{$n}Caption value=\"" . ArrayVal( $arPicture, "Caption" ) . "\">\n";
			$n++;
		}
		return $s;
	}

	// check field. return NULL or error message
	function Check( &$arData )
	{
		$sHelp = "";
		if(strcasecmp(SITE_NAME, "EverythingEquus.com") == 0)
			$sHelp = " For help on this task please refer to <a style='font-weight: bold; color: Red' target='_blank' href='/resources/Photo-Editing-Basics.php'>Help</a>.";
		if( $this->Checked )
			return null;
		$this->Checked = True;
		if( isset( $_FILES["AddPictureImage"] ) &&
		( ( ArrayVal( $arData, "AddPictureButton" ) != "" )
		|| (  ArrayVal( $arData, "submitButton" ) != "" )
		|| (  ArrayVal( $arData, "nextButton" ) != "" )
		|| ( $this->Form->ID != "0" ) ) )
		{
			$arFile = $_FILES["AddPictureImage"];
			if( $arFile["error"] != UPLOAD_ERR_NO_FILE )
			{
				// check picture count
				if( isset( $this->MaxPictureCount )
				&& ( count( $this->Pictures ) >= $this->MaxPictureCount ) )
					return "You can upload no more than {$this->MaxPictureCount} pictures";
				// check caption
				$arCaptionField = $this->Field["Fields"]["Caption"];
				if( isset( $_POST["AddPictureCaption"] ) )
					$arCaptionField["Value"] = $_POST["AddPictureCaption"];
				$this->Form->CheckField( "AddPictureCaption", $arCaptionField );
				if( isset( $arCaptionField["Error"] ) )
					return "Caption: " . $arCaptionField["Error"];
				// picture check.
				if( $arFile['error'] == 0 )
				{
					if( $arFile["size"] < $this->MinSize )
						return sprintf( S_FILE_TOO_SMALL, $arFile["size"], $this->MinSize );
					if( $arFile["size"] > $this->MaxSize )
						return sprintf( "The picture you are trying to upload is too large.
						The current size of your picture is %s MB.  Please reduce the size
						of your picture to %s MB or less." . $sHelp,
						number_format( $arFile["size"] / 1024 / 1024, 1 ), number_format( $this->MaxSize / 1024 / 1024, 1 ) );
					if( !preg_match( "/\.(" . implode( "|", $this->Extensions ) . ")$/i", $arFile["name"] ) )
						return sprintf( S_INVALID_FILE_EXTENSION, implode( ", ", $this->Extensions ) );
				}
				else
					switch( $arFile['error'] )
					{
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							return "File is too big";
						case UPLOAD_ERR_PARTIAL:
							return "File is uploaded partial. Please try again.";
					}
				$rootPath = realpath(__DIR__ . '/../..');
				DeleteFiles( "$rootPath/images/uploaded/temp/*", SECONDS_PER_DAY * 7 );
				// move uploaded file to temp dir
				if( !preg_match( "/\.(\w+)$/i", $_FILES["AddPictureImage"]['name'], $arMatches ) )
					DieTrace( "Can't detect file extension" );
				$sExt = strtolower( $arMatches[ 1 ] );
				$nVer = microtime( true );
				$sTempFileName = $rootPath . "/images/uploaded/temp/{$this->Prefix}-temp-{$nVer}.{$sExt}";
				if( !move_uploaded_file( $_FILES["AddPictureImage"]['tmp_name'], $sTempFileName ) )
					DieTrace( "Can't move uploaded file from {$_FILES["AddPictureImage"]['tmp_name']} to $sTempFileName" );
				// keep original
				$sOriginalURL = "/images/uploaded/temp/{$this->Prefix}-original-{$nVer}." . $sExt;
				$sOriginalFileName = $rootPath . $sOriginalURL;
				if( !copy( $sTempFileName, $sOriginalFileName ) )
					DieTrace( "Can't copy $sTempFileName to $sOriginalFileName" );
				// resize base
				$sURL = "/images/uploaded/temp/{$this->Prefix}-large-{$nVer}.{$sExt}";
				$sFileName = $rootPath . $sURL;
				$sError = ScaleImage( $sTempFileName, $sFileName, PICTURE_WIDTH, PICTURE_HEIGHT );
				if( isset( $sError ) )
					return $sError;
				// create thumbnail
				$sThumbURL = "/images/uploaded/temp/{$this->Prefix}-small-{$nVer}.gif";
				$sThumbFileName = $rootPath . $sThumbURL;
				$sError = ScaleImage( $sTempFileName, $sThumbFileName, THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT );
				if( isset( $sError ) )
					return $sError;
				// create medium
				$sMediumURL = "/images/uploaded/temp/{$this->Prefix}-medium-{$nVer}.{$sExt}";
				$sMediumFileName = $rootPath . $sMediumURL;
				$sError = ScaleImage( $sTempFileName, $sMediumFileName, MEDIUM_WIDTH, MEDIUM_HEIGHT );
				if( isset( $sError ) )
					return $sError;
				// save to internal array
				$nID = -1 * $nVer;
				// check picture count
				if( isset( $this->MaxPictureCount ) && ( count( $this->Pictures ) >= $this->MaxPictureCount ) )
				{
					// remove last picture
					$arPicture = array_pop( $this->Pictures );
					// unset primary picture, if this removed
					if( isset( $this->PrimaryPictureField ) && ( $this->PrimaryPictureID == $arPicture["ID"] ) )
						$this->PrimaryPictureID = "";
				}
				$arPicture = array( "ID" => $nID, "ImageVer" => $nVer, "Caption" => $arCaptionField["Value"], "ImageExt" => $sExt, "OriginalFilename" => $arFile["name"] );
				$this->Pictures[] = $arPicture;
				// set as primary, if there is no primary picture yet
				if( isset( $this->PrimaryPictureField ) && ( $this->PrimaryPictureID == "" ) )
					$this->PrimaryPictureID = $nID;
			}
			else
				if( isset( $_POST["AddPictureButton"] ) && ( $_POST["AddPictureButton"] != "" ) )
					return "Select file";
		}
		// remove picture, check
		$nRemovedID = null;
		if( ArrayVal( $arData, "RemovePicture" ) != "" )
		{
			$n = 0;
			foreach( $this->Pictures as $arPicture )
			{
				if( $arPicture["ID"] == $_POST["RemovePicture"] )
				{
					$nRemovedID = $arPicture["ID"];
					array_splice( $this->Pictures, $n, 1 );
					if( isset( $this->PrimaryPictureField ) && ( $nRemovedID == $this->PrimaryPictureID ) )
						if( count( $this->Pictures ) > 0 )
							$this->PrimaryPictureID = $this->Pictures[min( $n, count( $this->Pictures ) - 1 )]["ID"];
						else
							$this->PrimaryPictureID = "";
					break;
				}
				$n++;
			}
		}
		// if edit, then save picture to db
		if( ( ( ArrayVal( $_POST, "AddPictureButton" ) != "" ) || isset( $nRemovedID ) ) && ( $this->Form->ID != "0" ) )
			$this->Save();
	}

	// saving
	function Save()
	{
		global $Connection;
		$n = 0;
		$arPictureID = array();
		// unset primary picture
		if( isset( $this->PrimaryPictureField ) )
			$Connection->Execute( UpdateSQL( $this->Form->TableName, array( $this->Form->KeyField => $this->Form->ID ), array( $this->PrimaryPictureField => "null" ) ) );
		foreach( $this->Pictures as &$arPicture )
		{
			if( $arPicture["ID"] <= 0 )
			{
				// this is temporary picture. move to database
				$nPictureID = TableMax( $this->TableName, $this->KeyField ) + 1;
				$sExt = strtolower( $arPicture["ImageExt"] );
				$nVersion = time();
				$arFields = array(
					$this->KeyField => $nPictureID,
					$this->Form->KeyField => $this->Form->ID,
					"ImageVer" => $nVersion,
					"ImageExt" => "'$sExt'",
					"Caption" => ( isset( $arPicture["Caption"] ) ? "'" . addslashes( $arPicture["Caption"] ) . "'" : 'null' ),
				);
				if( $this->StoreExtendedProperties )
					$arFields["OriginalFilename"] = "'" . addslashes( $arPicture["OriginalFilename"] ) . "'";
				$Connection->Execute( InsertSQL( $this->TableName, $arFields ) );
				// move files
                $rootPath = realpath(__DIR__ . '/../..');
				$this->MoveFile( $rootPath . "/images/uploaded/temp/{$this->Prefix}-large-{$arPicture["ImageVer"]}.$sExt", PicturePath( $this->Root, "large", $nPictureID, $nVersion, $sExt, $this->Prefix ) );
				$this->MoveFile( $rootPath . "/images/uploaded/temp/{$this->Prefix}-small-{$arPicture["ImageVer"]}.gif", PicturePath( $this->Root, "small", $nPictureID, $nVersion, "gif", $this->Prefix ) );
				$this->MoveFile( $rootPath . "/images/uploaded/temp/{$this->Prefix}-medium-{$arPicture["ImageVer"]}.$sExt", PicturePath( $this->Root, "medium", $nPictureID, $nVersion, $sExt, $this->Prefix ) );
				if( $this->KeepOriginal )
					$this->MoveFile( $rootPath . "/images/uploaded/temp/{$this->Prefix}-original-{$arPicture["ImageVer"]}.$sExt", PicturePath( $this->Root, "original", $nPictureID, $nVersion, $sExt, $this->Prefix ) );
				// update primary picture
				if( isset( $this->PrimaryPictureField ) && ( $this->PrimaryPictureID == $arPicture["ID"] ) )
					$this->PrimaryPictureID = $nPictureID;
				// mark picture as saved
				$arPicture["ID"] = $nPictureID;
				$arPicture["ImageVer"] = $nVersion;
			}
			else
				// update caption
				$Connection->Execute( UpdateSQL( $this->TableName, array( $this->KeyField => $arPicture["ID"] ), array( "Caption" => ( isset( $arPicture["Caption"] ) ? "'" . addslashes( substr( $arPicture["Caption"], 0, 32 ) ) . "'" : 'null' ), "ImageVer" => $arPicture["ImageVer"], "ImageExt" => "'{$arPicture["ImageExt"]}'" ) ) );
			$arPictureID[] = $arPicture["ID"];
		}
		// delete pictures
		$this->DeletePictures( $this->Form->KeyField, $this->Form->ID, $arPictureID );
		// save primary picture
		if( isset( $this->PrimaryPictureField ) )
		{
			if( !in_array( $this->PrimaryPictureID, $arPictureID ) )
				$this->PrimaryPictureID = "";
			if( $this->PrimaryPictureID == "" )
				$sPrimaryPictureID = "null";
			else
				$sPrimaryPictureID = $this->PrimaryPictureID;
			$Connection->Execute( UpdateSQL( $this->Form->TableName, array( $this->Form->KeyField => $this->Form->ID ), array( $this->PrimaryPictureField => $sPrimaryPictureID ) ) );
		}
	}

	function MoveFile( $sFrom, $sTo )
	{
		MkDirs( dirname( $sTo ) );
		rename( $sFrom, $sTo );
	}

	// delete pictures
	function DeletePictures( $sKeyField, $nID, $arPictureID )
	{
		global $Connection;
		$q = new TQuery( "select {$this->KeyField} from {$this->TableName} where {$sKeyField} = {$nID}" );
		while( !$q->EOF )
		{
			if( !in_array( $q->Fields[$this->KeyField], $arPictureID ) )
			{
				$arOldFiles = array();
				foreach ( array( "large", "small", "medium", "original" ) as $sSize )
					DeleteFiles( PicturePath( $this->Root, $sSize, $q->Fields[$this->KeyField], "*", "*", $this->Prefix ) );
				$Connection->Execute( "delete from {$this->TableName} where {$this->KeyField} = {$q->Fields[$this->KeyField]}" );
			}
			$q->Next();
		}

	}

}

?>
