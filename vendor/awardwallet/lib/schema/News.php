<?

class TNewsSchema extends TBaseSchema
{
	var $newsNumber = 1;
	function TNewsSchema(){
		global $Config;
		parent::TBaseSchema();
		$this->TableName = "News";
		$this->KeyField = "NewsID";
		$this->DefaultSort = "NewsTime";
		$this->Description = array("Homepage News");
		$objPictureManager = new TPictureFieldManager();
		$objPictureManager->Dir = "/images/uploaded/news";
		$objPictureManager->Prefix = "news";
		$objPictureManager->thumbWidth = 150;
		$objPictureManager->thumbHeight = 150;

		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
				"Required" => True
			),
			"FullName" => array( 
				"Caption" => "Full Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250
			),
			"Email" => array( 
				"Caption" => "Email",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250
			),
			"NewsTime" => array( 
				"Caption" => "Date",
				"Type" => "date",
				"Note" => $Config["dateNote"],
				"InputType" => "date",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Sort" => "NewsTime DESC",
				"Required" => True,
			),
			"Title" => array( 
				"Caption" => "Title",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250
			),
			"BodyText" => array( 
				"Caption" => "Text Body",
				"Type" => "string",
				"InputType" => "htmleditor",
				"Width" => 600,
				"Height" => 400,
				"HTML" => True,
				"Required" => True,
				"Size" => 30000
			),
			"Visible" => array( 
				"Caption" => "Visible",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Options" => array( "1" => "Visible", 0 => "Hidden" )
			),
			"Rank" => array( 
				"Caption" => "Rank",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250
			),
			"NewsPhoto" => array( 
				"Caption" => "News Picture",
				"Note" => "Optionaly upload a picture for this news article",
				"Type" => "custom",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Manager" => $objPictureManager,
			)
		);
	}
	
	function GetListFields()
	{
		$arFields = $this->Fields;
		unset($arFields['NewsPhoto']);
#		unset($arFields['BodyText']);
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->SQL = "SELECT * FROM News WHERE NewsNumber = " . $this->newsNumber;
		$list->MultiEdit = False;
		$list->KeyField = $this->KeyField;
		$list->DefaultSort2 = "ID";
		$list->Sorts["ID"] = array(
			"Caption" => "ID DESC", 
			"OrderBy" => "NewsID DESC" );
#		$objList->DeleteQueries[] = "delete from PictureCategoryLink where PictureID = [ID]";
	}

	function TuneForm(\TBaseForm $form){
		$form->KeyField = $this->KeyField;
		$form->SQLParams = array( "NewsNumber" => $this->newsNumber);
	}
	
	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}
}
?>
