<?

// -----------------------------------------------------------------------
// category explorer class
// contains utilities to handle multi-multi catalog tree
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class TBaseCategoryExplorer
{
	var $Table;
	var $LinkTable;
	var $RelationTable;
	var $KeyField;
	var $ParentField;
	var $NameField = "Name";
	public $Joins = "";
 	public $NameExpression;
	var $ItemField;

	//depth of the tree
	var $depth = 0;
	// category tree, private
	var $Tree;
	//Left menu tree
	var $leftMenu = array();
	// selected tree options: Level => Category
	var $Selected = array();
	//target url
	var $targetURL;
	//category path
	var $sCategoryPath;
	//Full or part of page title
	var $pageTitle;
	//private variables here:
	//private array - all children of a category identified by QS["ID"]
	var $privChildren = array();
	var $privCurCatId;
	var $privCurCatName;
	var $categoriesText = "All categories";
	//Optionally you can specify a filter. For example on Studio-Elements.com ii need to show a tree of services that belong to a signle user.
	var $where = "";
	//Put all ids into a lat array (used on studio-elements.com/meet-our-professionals?id=7 to then optimize a sql query)
	var $allIds = array();
	// explore tree level. private
	function Explore($nID, &$subMenu, $linkPath, $parentSelect, $nLevel)
	{
	global $QS;
#		if(isset($this->Tree[$nLevel][$nID]))
#			return false;
//begin calculating the depth of the tree
		if($this->depth<$nLevel)
			$this->depth = $nLevel;
//end calculating the depth of the tree
		$nLevel++;
		$query = $this->ExploreSQL($nID);
		$objRS = New TQuery($query);
		while( !$objRS->EOF )
		{
			$nCategoryID = $objRS->Fields[$this->KeyField];
			$sName = $objRS->Fields[$this->NameField];
//begin populating the left menu array
			if(!is_array($subMenu))
				$subMenu = array();
#if(isset($categoriesTAr[$level][$nCategoryID])){
			$subMenu = $subMenu + array(
				$sName => array(
					"caption"	=> $sName,
					"path"		=> $linkPath . "&Cat".$nLevel."=$nCategoryID",
					"selected"	=> $nCategoryID == intval( ArrayVal($QS, "Cat{$nLevel}" ) ) && $parentSelect,
					"id"		=> $nCategoryID,
				)
			);
//end populating the left menu array
#begin populating allIds array
			if(!in_array($nCategoryID, $this->allIds))
				$this->allIds[] = $nCategoryID;
#begin populating allIds array
			if($subMenu[$sName]["selected"])
				$this->buildCategoryPath($nLevel, $subMenu[$sName]["path"], $subMenu[$sName]["caption"]);
			if(!isset($this->Tree[$nLevel][$nCategoryID]))
			{
				$this->Tree[$nLevel][$nCategoryID] = $sName;
			}
			$this->Explore($nCategoryID, $subMenu[$sName]["subMenu"], $subMenu[$sName]["path"], $subMenu[$sName]["selected"], $nLevel);
			$objRS->Next();
		}
	}

	function ExploreSQL($nID) {
		return "SELECT {$this->KeyField}, " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . "{$this->NameField} FROM {$this->Table} {$this->Joins} WHERE Visible = TRUE AND {$this->KeyField} IN (SELECT {$this->KeyField} FROM {$this->RelationTable} WHERE {$this->ParentField} = ".$nID.") {$this->where} ORDER BY `Rank`,  {$this->NameField}";
	}

	// call this function after setting all clss properties
	function Init()
	{
		global $Connection, $QS;
// prepare
		if( !isset( $this->Table ) )
			DieTrace("Please Set Table");
		if( !isset( $this->ParentField ) )
			DieTrace("Please Set ParentField");
		if( !isset( $this->KeyField ) )
			$this->KeyField = $Connection->PrimaryKeyField($this->Table);
		if (!isset($this->LinkTable))
			$this->LinkTable = $this->Table."Link";
		if (!isset($this->RelationTable))
			$this->RelationTable = $this->Table."Relation";
//initiate category array with top-most parent nodes
		$this->Tree = array(1 => SQLToArray("SELECT {$this->KeyField}, " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . "{$this->NameField} FROM {$this->Table} {$this->Joins} WHERE Visible = TRUE AND {$this->KeyField} NOT IN (SELECT DISTINCT {$this->KeyField} FROM {$this->RelationTable}) {$this->where} ORDER BY `Rank`,  {$this->NameField}", $this->KeyField, $this->NameField));
//Loop through the top-most parent nodes and start exploring their sub-nodes
		$objRS = New TQuery( "SELECT {$this->KeyField}, " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . "{$this->NameField} FROM {$this->Table} {$this->Joins} WHERE Visible = TRUE AND {$this->KeyField} NOT IN (SELECT DISTINCT {$this->KeyField} FROM {$this->RelationTable}) {$this->where} ORDER BY `Rank`,  {$this->NameField}" );
		while(!$objRS->EOF)
		{
			$nCategoryID = $objRS->Fields[$this->KeyField];
			$sName = $objRS->Fields[$this->NameField];
//begin populating the left menu array
			$this->leftMenu = $this->leftMenu + array(
				$sName => array(
					"caption"	=> $sName,
					"path"		=> $this->targetURL."?Cat1=$nCategoryID",
					"selected"	=> $nCategoryID == intval( ArrayVal($QS, "Cat1" ) ),
					"id"		=> $nCategoryID,
				)
			);
//end populating the left menu array
#begin populating allIds array
			if(!in_array($nCategoryID, $this->allIds))
				$this->allIds[] = $nCategoryID;
#begin populatin
			if($this->leftMenu[$sName]["selected"])
				$this->buildCategoryPath(1, $this->leftMenu[$sName]["path"], $this->leftMenu[$sName]["caption"]);
			$this->Explore($nCategoryID, $this->leftMenu[$sName]["subMenu"], $this->leftMenu[$sName]["path"], $this->leftMenu[$sName]["selected"], 1);
			$objRS->Next();
		}

// begin getting selected categories
		$bCatSelected = True;
		foreach( $this->Tree as $nLevel => &$arCategories )
		{
			// sort categories alphab
			$this->SortCategories($arCategories, $nLevel);
			$nSelected = intval( ArrayVal($QS, "Cat$nLevel" ) );
			// add category to first level for one-category filtering
			if( ( $nLevel == 1 ) && !isset( $arCategories[$nSelected] ) )
			{
				$q = new TQuery("select " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . "{$this->NameField} from {$this->Table} {$this->Joins} where {$this->KeyField} = $nSelected");
				if( !$q->EOF )
					$arCategories[$nSelected] = $q->Fields["Name"];
			}
			if( isset( $arCategories[$nSelected] ) && $bCatSelected )
			{
				if( $nLevel > 1 )
				{
					// check that selected category is child of parent
					$q = new TQuery( "select 1 from {$this->RelationTable} where {$this->KeyField} = $nSelected and {$this->ParentField} = " . $this->Selected[$nLevel - 1] );
					if( $q->EOF )
						$bCatSelected = False;
				}
				if( $bCatSelected )
					$this->Selected[$nLevel] = $nSelected;
			}
			else
				$bCatSelected = False;
		}
		if(isset($QS["ID"])){
			$this->privCurCatId = intval($QS["ID"]);
			$this->privCurCatName = Lookup( $this->Table, $this->KeyField, "Name", $this->privCurCatId );
		}
// end getting selected categories
		$this->completeCategories();
	}

	function SortCategories(&$arCategories, $level) {
		asort( $arCategories );
	}

	// get tree options for <select>
	function GetOptions( &$categorySelectAr, &$arCategoryOptionAttributes, $bShowLevel=true, $bgColor="#f6a436", $bUseCurCat = True )
	{
		$categorySelectAr = array();
		if(isset($this->privCurCatId) && $bUseCurCat )
			$this->getAllChildren($this->leftMenu);
		$arCategoryOptionAttributes = array();
		foreach( $this->Tree as $key => $value ){
			$categorySelectAr = $categorySelectAr + array(" __".$key => "");
			if($bShowLevel)
				$categorySelectAr = $categorySelectAr + array(" _".$key => "---- Level ".$key." ----");
			foreach( $value as $id => $cat ){
				if( !$bUseCurCat || ( !array_key_exists($cat, $this->privChildren) && $cat!=$this->privCurCatName) )
					$categorySelectAr = $categorySelectAr + array($id => $cat);
			}
#			$categorySelectAr = $categorySelectAr + $value;
			$arCategoryOptionAttributes[" _".$key] = "style=\"background-color: {$bgColor};\"";
			if(!$bShowLevel)
				$arCategoryOptionAttributes[" __".$key] = "style=\"background-color: {$bgColor};\"";
		}
	}
// returns true if this category is a child of the current category in some shape or form (grand or grand-grand child)
	function getAllChildren($arr, $recordChildren = false){
		foreach( $arr as $key => $value ){
			if($recordChildren)
				if(!array_key_exists($key, $this->privChildren))
					$this->privChildren[$key] = $key;
			if(is_array($value["subMenu"])){
				if($key == $this->privCurCatName)
					$this->getAllChildren($value["subMenu"], true);
				elseif($recordChildren)
					$this->getAllChildren($value["subMenu"], true);
				elseif(!$recordChildren)
					$this->getAllChildren($value["subMenu"], false);
			}
		}
	}

	// debug
	function showTree(){
		print "showTree:<br>";
		print "<textarea cols=100 rows=40>";
		print_r($this->Tree);
		print "</textarea><br><br>";
	}
	// debug
	function showLeftMenuArray(){
		print "showLeftMenuArray:<br>";
		print "<textarea cols=100 rows=40>";
		print_r($this->leftMenu);
		print "</textarea><br><br>";
	}

	// debug
	function showSelectedArray(){
		print "showSelectedArray:<br>";
		print "<textarea cols=100 rows=40>";
		print_r($this->Selected);
		print "</textarea><br><br>";
	}

	// show tree filter, as dropdown
	function DrawDropdownFilters( &$arHiddens, $selParams="", $stack=false )
	{
		foreach ( $this->Tree as $nLevel => $arCategories )
		{
			if( isset( $this->Selected[$nLevel-1] ) || ( $nLevel == 1 ) )
			{
				if( $nLevel > 1 )
				{
					// keep only children of selected parent
					$arChilds = SQLToArray( "select distinct pcr.{$this->KeyField}, pc.Name from {$this->RelationTable} pcr, {$this->Table} pc where pc.{$this->KeyField} = pcr.{$this->KeyField} and pcr.{$this->ParentField} = " . $this->Selected[$nLevel-1], "{$this->KeyField}", "Name" );
					$arCategories = array_intersect_assoc( $arCategories, $arChilds );
				}
				if( count( $arCategories ) > 0 )
				{
					echo "<select name=Cat{$nLevel} onchange=\"this.form.submit();\"{$selParams}>\n";
					echo "<option value=\"\">{$this->categoriesText}</option>\n";
					DrawArrayOptions( $arCategories, ArrayVal( $this->Selected, $nLevel ) );
					echo "</select>";
					if($stack)
						echo "<br>";
				}
			}
			unset( $arHiddens["Cat{$nLevel}"] );
		}
	}

	// get sql filters for TBaseList, for selected categories
	function GetListFilters()
	{
		global $QS;
		$sFilters = "";
		foreach ( $this->Selected as $nLevel => $nCategoryID )
		{
//this will make the filter to show all the pictures in the last selected category...
			if( ArrayVal( $QS, "Cat{$nLevel}Mode" ) == "Only" )
				$sFilters = "{$this->ItemField} in ( select {$this->ItemField} from( select {$this->ItemField}, count( {$this->KeyField} ) as CatCount, max( {$this->KeyField} ) as Cat from {$this->LinkTable} group by {$this->ItemField} having count( {$this->KeyField} ) = 1 ) as Pics where Cat = $nCategoryID )";
			else{
				if( $sFilters != "" )
					$sFilters .= " and ";
				$sFilters .= " {$this->ItemField} in ( select {$this->ItemField} from {$this->LinkTable} where {$this->KeyField} = $nCategoryID )";
			}
#Putting the bottom statement into the else condition above will make the category to show all the items that fall under the last category....
#				$sFilters = "{$this->ItemField} in ( select {$this->ItemField} from {$this->LinkTable} where {$this->KeyField} = $nCategoryID )";
		}
		return $sFilters;
	}

	function makeDefaultSelection(){
		global $QS;
		$categoryURL = $this->targetURL."?";
		if(!isset($QS["Cat1"]) && isset($QS["ID"])){
			foreach($this->Tree as $level => $categories){
				foreach($categories as $key => $val){
					$objRS = New TQuery("SELECT {$this->KeyField} FROM {$this->LinkTable} WHERE {$this->ItemField} = " . intval( ArrayVal( $QS, "ID" )));
					while(!$objRS->EOF){
						if($objRS->Fields[$this->KeyField] == $key)
							if(!isset($QS["Cat".$level])){
								$QS["Cat".$level] = $key;
								if($level != 1)
									$categoryURL .="&";
								$categoryURL .="Cat{$level}=$key";
								$this->buildCategoryPath($level, $categoryURL, $val);
							}
						$objRS->Next();
					}
				}
			}
		}
		$this->completeCategories();
	}

	function buildCategoryPath($level, $path, $caption){
		if($level != 1){
			$this->sCategoryPath .= "<td height=20 valign=top width=20 align=center><img src='/lib/images/arrowRight3.gif' style='margin-top: 4px;'></td>";
			$this->pageTitle .= ", ";
		}
		$this->sCategoryPath .= "<td height=20 valign=top><a href='".$path."'>" . $caption . "</a></td>";
		$this->pageTitle .= $caption;
	}

	function completeCategories(){
		if($this->sCategoryPath != "" && substr($this->sCategoryPath, 0, 6) != "<table")
			$this->sCategoryPath = "<table cellspacing=0 cellpadding=0 border=0><tr>".$this->sCategoryPath."</tr></table>";
	}

}
?>
