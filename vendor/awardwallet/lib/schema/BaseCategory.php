<?

class TBaseCategorySchema extends TBaseSchema
{
	function TBaseCategorySchema()
	{
		parent::TBaseSchema();
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
				"Required" => True
			),
			"Name" => array( 
				"Caption" => "Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True,
				"Note" => "Give it a descriptive user-friendly name. It will be used on the<br>site for easy-browsing."
			),
			"Description" => array( 
				"Caption" => "Description",
				"Type" => "string",
				"InputType" => "htmleditor",
				"Width" => 600,
				"Height" => 400,
				"HTML" => True,
				"Required" => False,
				"Size" => 10000,
				"Note" => "Allows to optionally add some description to the whole category"
			),
			"Visible" => array( 
				"Caption" => "Visible",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Options" => array( "1" => "Visible", 0 => "Hidden" ),
				"Value" => "1",
				"Note" => "If set to hidden, it won't show up on the website"
			),
			"Rank" => array( 
				"Caption" => "Rank",
				"Type" => "integer",
				"Required" => True,
				"Value" => 5,
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Note" => "Results may be sorted by this in Ascending order"
			)
		);
		$this->ListClass = "TCategoryList";
	}
	
	function GetFormFields()
	{
		$arFields = parent::GetFormFields();
		// get categories
		$objCategoryExplorer = new TCategoryExplorer();
		$objCategoryExplorer->Table = $this->TableName;
		$objCategoryExplorer->ParentField = "ParentCategoryID";
		$objCategoryExplorer->Init();
#		$objCategoryExplorer->showTree();

		$objCategoryExplorer->GetOptions( $arOptions, $arOptionAttributes );
		// create category field
		$objParentManager = new TTableLinksFieldManager();
		$objParentManager->TableName = "{$this->TableName}Relation";
		$objParentManager->Fields = array(
			"ParentCategoryID" => array(
				"Type" => "integer",
				"Caption" => "Category",
				"Required" => True,
				"InputType" => "select",
				"Caption" => "Category",
				"Options" => $arOptions,
				"OptionAttributes" => $arOptionAttributes,
				"Required" => True,
			)
		);
		$arFields = array(
			"Parents" => array(
				"Caption" => "Parent categories",
				"Manager" => $objParentManager,
				"Type" => "string",
				"Note" => "Please select *ONLY* the lowest level parent categories for this category."
		) )
		+ $arFields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->DeleteQueries[] = "delete from {$this->TableName}Relation where {$this->KeyField} = [ID] or ParentCategoryID = [ID]";
		$list->DeleteQueries[] = "delete from {$this->TableName}Link where {$this->KeyField} = [ID]";
	}
	
	function GetListFields()
	{
		$arFields = $this->Fields;
		unset($arFields['Description']);
		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form){
		$form->Uniques = array(
			array(
				"Fields" => array( "Name" ),
				"ErrorMessage" => "A category with this name already exists. Please choose another category name."
 			)
		);
	}
}

?>
