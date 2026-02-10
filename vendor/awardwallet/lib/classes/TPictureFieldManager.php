<?php

// -----------------------------------------------------------------------
// Picture Field manager class.
//		Contains class, to handle picture form field
//		if field name is "Image",
//		then underlying table should contain "FileVer", "FileExt" fields
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once( __DIR__."/TFileFieldManager.php" );
require_once( __DIR__."/../imageFunctions.php" );

class TPictureFieldManager extends TFileFieldManager
{
	// save originals
	var $KeepOriginal = False;
	// create mediums
	var $CreateMedium = False;
	// picture width, by default set from a constant (/kernel/constants.php)
	var $MediumWidth = MEDIUM_WIDTH;
	// picture width, by default set from a constant (/kernel/constants.php)
	var $MediumHeight = MEDIUM_HEIGHT;
	// picture width, by default set from a constant (/kernel/constants.php)
	var $picWidth = PICTURE_WIDTH;
	// picture width, by default set from a constant (/kernel/constants.php)
	var $picHeight = PICTURE_HEIGHT;
	// picture width, by default set from a constant (/kernel/constants.php)
	var $thumbWidth = THUMBNAIL_WIDTH;
	// picture width, by default set from a constant (/kernel/constants.php)
	var $thumbHeight = THUMBNAIL_HEIGHT;
	// show upload button
	var $ShowUploadButton = true;
	public $previewSize = "small";

	function TPictureFieldManager()
	{
		$this->Extensions = array( "jpg", "jpeg", "gif", "png", "jpe" );
	}

	function PictureURL( $sSize )
	{
		if( isset( $this->Dir ) || ( $this->FileID < 0 ) ){
			$result = PicturePath( $this->Dir, $sSize, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix );
			return $result;
		}
		else
			return "/lib/album/getImage.php?ID={$this->FileID}&Type={$sSize}&Ver={$this->FileVer}";
	}

	function PicturePath( $sSize )
	{
		if( isset( $this->Dir ) || ( $this->FileID < 0 ) )
			return realpath(__DIR__ . '/../..') . PicturePath( $this->Dir, $sSize, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix );
		else
			return PicturePath( $this->Path, $sSize, $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix );
	}

	// get field html
	function InputHTML($sFieldName = null, $arField = null){
		$sCaption = "Upload Picture";
		$sAttributes =  $this->Field["InputAttributes"];
		if( isset( $this->Field["Cols"] ) )
			$sAttributes .= " size=" . $this->Field["Cols"];
		$s = "";
		if( isset( $this->FileVer ) )
			$sPreview = "<a href='#' onclick=\"openAWindow( '" . $this->PictureURL( "large" ) . "', 'Preview', " . ( $this->picWidth + 50 ). ", " . ( $this->picHeight + 50 ). ", 1, 0 ); return false;\"><img border='0' src=" . $this->PictureURL($this->previewSize) . "></a><br>\n";
		else
			$sPreview = "";
		if( $sPreview != "" )
		{
			$s .= $sPreview;
			$s .= "<script>
			function rotate{$this->FieldName}Picture( angle ) {
				f = document.forms['editor_form'];
				f.{$this->FieldName}RotateAngle.value=angle;
				f.DisableFormScriptChecks.value='1';
				CheckForm( f );
				f.submit();
				return false;
			}
			</script>";
			$s .= "<input type=hidden name={$this->FieldName}RotateAngle value=\"\">\n";
			$s .= "<input type=hidden name={$this->FieldName}Changed value=\"" . intval( $this->FileChanged ) ."\">\n";
			$s .= "<div style='padding: 4 0 0 0'>
			<a href='#' onclick=\"return rotate{$this->FieldName}Picture( 3 )\"><img src=/lib/images/90ccw.gif width=21 height=21 border=0></a>
			<a href='#' onclick=\"return rotate{$this->FieldName}Picture( 2 )\"><img src=/lib/images/180.gif width=21 height=21 border=0></a>
			<a href='#' onclick=\"return rotate{$this->FieldName}Picture( 1 )\"><img src=/lib/images/90cw.gif width=21 height=21 border=0></a>
			</div>";
			if( !$this->Field["Required"] )
				$s .= "<input type=checkbox name=" . $this->FieldName . "Delete value=1> " . S_DELETE . "<br>\n";
			$sCaption = "Update Picture";
		}
		$s .= "<input class='inputTxt fileField' type=file name=$this->FieldName $sAttributes>";
		if($this->ShowUploadButton)
			$s .= "<input type=hidden name={$this->FieldName}AddButton>".$this->Form->DrawButton("Upload picture", "name={$this->FieldName}AddButtonTrigger onclick=\"var form = document.forms['{$this->Form->FormName}']; form.{$this->FieldName}AddButton.value='submit'; form.DisableFormScriptChecks.value='1'; form.submit();\"");
		$s .= $this->HiddenHTML();
        return $s;
	}

	function MoveUploadedFile( $nFileID, $nFileVer, $sFileExt )
	{
		if( !preg_match( "/\.(\w+)$/i", $_FILES[$this->FieldName]['name'], $arMatches ) )
			DieTrace( "Can't detect file extension" );
		$sFileExt = strtolower( $arMatches[ 1 ] );
		$rootPath = realpath(__DIR__ . '/../..');
		$sOriginalFile = $rootPath . PicturePath( $this->Root, "original", $nFileID, $nFileVer, $sFileExt, $this->Prefix );
		MkDirs( dirname( $sOriginalFile ) );
		if( !move_uploaded_file( $_FILES[$this->FieldName]['tmp_name'], $sOriginalFile ) )
			DieTrace( "Can't move uploaded file {$_FILES[$this->FieldName]['tmp_name']} to {$sOriginalFile}" );
		// resize base
		$sLargeFile = $rootPath . PicturePath( $this->Root, "large", $nFileID, $nFileVer, $sFileExt, $this->Prefix );
		$sError = ScaleImage( $sOriginalFile, $sLargeFile, $this->picWidth, $this->picHeight );
		if( isset( $sError ) )
			return $sError;
		// create thumbnail
		$sThumbFile = $rootPath . PicturePath( $this->Root, "small", $nFileID, $nFileVer, $sFileExt, $this->Prefix );
		$sError = ScaleImage( $sOriginalFile, $sThumbFile, $this->thumbWidth, $this->thumbHeight );
		if( isset( $sError ) )
			return $sError;
		// create medium
		$sMediumFile = $rootPath . PicturePath( $this->Root, "medium", $nFileID, $nFileVer, $sFileExt, $this->Prefix );
		$sError = ScaleImage( $sOriginalFile, $sMediumFile, $this->MediumWidth, $this->MediumHeight );
		if( isset( $sError ) )
			return $sError;
	}

	// get addional sql parameters, for update or insert call.
	function GetSQLParams( &$arFields, $bInsert )
	{
		parent::GetSQLParams( $arFields, $bInsert );
		if( isset( $this->FileID ) && ( ( $this->FileID < 0 ) || $this->FileChanged ) && $this->StoreExtendedInfo )
		{
			$this->GetPictureInfo( PicturePath( $this->Root, "large", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), "Large", $arFields );
			$this->GetPictureInfo( PicturePath( $this->Root, "small", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), "Small", $arFields );
			if( $this->CreateMedium )
				$this->GetPictureInfo( PicturePath( $this->Root, "medium", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), "Medium", $arFields );
			if( $this->KeepOriginal )
				$this->GetPictureInfo( PicturePath( $this->Root, "original", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), "Original", $arFields );

		}
	}

	function GetFileInfo( $sFile, &$arFields )
	{

	}

	function GetPictureInfo( $sFile, $sSize, &$arFields )
	{
		if( strpos( $sFile, "/images/uploaded/temp" ) === 0 )
			$sFile = realpath(__DIR__ . '/../..') . $sFile;
 		$arImageSize = getimagesize( $sFile );
		$arFields[$this->FieldName.$sSize."Width"] = $arImageSize[0];
		$arFields[$this->FieldName.$sSize."Height"] = $arImageSize[1];
		$arFields[$this->FieldName.$sSize."Size"] = filesize( $sFile );
	}

	// saving
	function Save()
	{
		if( isset( $this->FileID ) && ( $this->FileID < 0 ) ){
		    $rootPath = realpath(__DIR__ . '/../..');
			$largeFile = $rootPath. PicturePath( $this->Root, "large", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix );
			if(file_exists($largeFile)){
				DeleteFiles( PicturePath( $this->Root, "large", $this->Form->ID, "*", "*" ) );
				DeleteFiles( PicturePath( $this->Root, "small", $this->Form->ID, "*", "*" ) );
				if( $this->KeepOriginal )
					DeleteFiles( PicturePath( $this->Root, "original", $this->Form->ID, "*", "*" ) );
				if( $this->CreateMedium )
					DeleteFiles( PicturePath( $this->Root, "medium", $this->Form->ID, "*", "*" ) );
				$this->MoveFile( $largeFile, PicturePath( $this->Root, "large", $this->Form->ID, intval( $this->FileVer ), $this->FileExt, $this->Prefix ) );
				$this->MoveFile( $rootPath. PicturePath( $this->Root, "small", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), PicturePath( $this->Root, "small", $this->Form->ID, intval( $this->FileVer ), "gif", $this->Prefix ) );
				if( $this->KeepOriginal )
					$this->MoveFile( $rootPath. PicturePath( $this->Root, "original", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), PicturePath( $this->Root, "original", $this->Form->ID, intval( $this->FileVer ), $this->FileExt, $this->Prefix ) );
				if( $this->CreateMedium )
					$this->MoveFile( $rootPath. PicturePath( $this->Root, "medium", $this->FileID, $this->FileVer, $this->FileExt, $this->Prefix ), PicturePath( $this->Root, "medium", $this->Form->ID, intval( $this->FileVer ), $this->FileExt, $this->Prefix ) );
				$this->FileVer = intval( $this->FileVer );
				$this->FileID = $this->Form->ID;
			}
		}
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		parent::LoadPostData( $arData );
		if( ArrayVal( $arData, "{$this->FieldName}RotateAngle" ) != "" )
			$this->RotatePicture( $arData["{$this->FieldName}RotateAngle"] );
	}

	// rotate picture 90, -90 or 180
	function RotatePicture( $nAngle ) {
		if( $this->KeepOriginal && file_exists( $this->PicturePath( "original" ) ) )
			$sSource = $this->PicturePath( "original" );
		else
			$sSource = $this->PicturePath( "large" );
		/*unlink( $this->PicturePath( "small" ) );
		if( $this->KeepOriginal )
			unlink( $this->PicturePath( "large" ) );
		if( $this->CreateMedium )
			unlink( $this->PicturePath( "medium" ) );*/
		/*if( $this->FileID > 0 )
			$this->FileVer = time();
		else
			$this->FileVer = microtime(true);*/
		$this->FileVer = microtime( true );
		$this->FileID = -1 * $this->FileVer;
		copy( $sSource, $this->PicturePath( "original" ) );
		$sSource = $this->PicturePath( "original" );
		RotateImage( $sSource, $nAngle );
		ScaleImage( $sSource, $this->PicturePath( "small" ), THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT );
		if( $this->CreateMedium )
			ScaleImage( $sSource, $this->PicturePath( "medium" ), MEDIUM_WIDTH, MEDIUM_HEIGHT );
		if( $this->KeepOriginal ) {
			ScaleImage( $sSource, $this->PicturePath( "large" ), PICTURE_WIDTH, PICTURE_HEIGHT );
		} else
			copy( $sSource, $this->PicturePath( "large" ) );
		//unlink( $sSource );
		$this->FileChanged = True;
	}

}

?>
