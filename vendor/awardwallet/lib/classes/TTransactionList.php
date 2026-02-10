<?

class TTransactionList extends TBaseList
{
	function FormatFields($output = "html"){
		global $arAdKindTable, $arAdKindAlias;
		$arFields = &$this->Query->Fields;
		$arFields["AdID"] = "<a href=/" . strtolower( $arAdKindTable[$arFields["AdKind"]] ). "/{$arFields["AdID"]}/>{$arAdKindAlias[$arFields["AdKind"]]}{$arFields["AdID"]}</a>";
		parent::FormatFields($output);
	}
}
