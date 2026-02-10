<?
require_once(__DIR__ . "/Group.php");

class TBaseAgeGroupSchema extends TGroupSchema
{
	function TBaseAgeGroupSchema()
	{
		parent::TGroupSchema();
		$this->TableName = "AgeGroup";
		$this->KeyField = $this->TableName . "ID";
		$this->Description = array("User Admin", "Groups");
		unset($this->Fields["SiteGroupID"]);
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
			)
		) + $this->Fields;
	}
}
?>
