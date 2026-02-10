<?php
class TBasePageSchema extends TBaseSchema{

	function __construct(){
		parent::TBaseSchema();
		$this->TableName = "Page";
		$this->Fields = array(
			"PageID" => array(
				"Type" => "integer",
				"Caption" => "ID",
			),
			"ParentID" => array(
				"Type" => "integer",
				"Caption" => "Folder",
				"Options" => $this->GetParentOptions(),
			),
			"MenuTitle" => array(
				"Type" => "string",
				"Size" => 80,
				"Required" => true,
				"InputAttributes" => "style='width: 800px;'",
			),
			"PageTitle" => array(
				"Type" => "string",
				"Size" => 200,
				"Required" => true,
				"InputAttributes" => "style='width: 800px;'",
			),
			"Path" => array(
				"Type" => "string",
				"Size" => 200,
				"Required" => true,
				"Note" => "page path, in web browser",
				"InputAttributes" => "style='width: 400px;'",
			),
			"Description" => array(
				"Type" => "string",
				"Required" => false,
				"InputType" => "textarea",
				"InputAttributes" => "style='width: 800px;'",
			),
			"Keywords" => array(
				"Type" => "string",
				"Required" => false,
				"InputType" => "textarea",
				"InputAttributes" => "style='width: 800px;'",
			),
			"Content" => array(
				"Type" => "string",
				"Required" => false,
				"InputType" => "htmleditor",
				"HTML" => true,
			),
			"SortIndex" => array(
				"Type" => "integer",
				"Required" => true,
				"Note" => "Page index in menu",
			),
			"Visible" => array(
				"Type" => "boolean",
				"Required" => true,
				"Value" => "1",
			),
		);
		$this->DefaultSort = "SortIndex";
	}

	function GetParentOptions(){
		return array("" => "/") + SQLToArray("select PageID, MenuTitle from Page order by MenuTitle", "PageID", "MenuTitle");
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();
		unset($fields['PageID']);
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		if($form->ID > 0){
			unset($form->Fields["ParentID"]["Options"][$form->ID]);
		}
		else{
			$form->Fields["SortIndex"]["Value"] = TableMax("Page", "SortIndex") + 10;
		}
		$form->Uniques[] = array(
			"Fields" => array("Path"),
			"ErrorMessage" => "Path should be unique",
		);
	}

	function GetListFields(){
		$fields = parent::GetListFields();
		unset($fields["PageTitle"]);
		unset($fields["Description"]);
		unset($fields["Keywords"]);
		unset($fields["Content"]);
		unset($fields["Visible"]);
		return $fields;
	}

	function TuneList(&$list){
		parent::TuneList($list);
	}

}

?>
