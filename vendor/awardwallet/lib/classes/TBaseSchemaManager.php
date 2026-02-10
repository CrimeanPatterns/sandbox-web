<?

class TBaseSchemaManager
{
	/*
	array(
		"Table1" => array(
			"PrimaryKey" = > "Table1ID",
			"CopyAllowed" => true,
			"Fields" => array( "Field1" => array( "Type" => "int(11)" ), .. ) ),
			"References" => array( array( "Table" => "Table1", "Field" => "Field1" ), ..
			"ReferencedBy" => array( array( "Table" => "Table1", "Field" => "Field1", "OnDelete" => "cascade" ),
		.. )
	);
	*/
	public $Tables;
	public $ReturnFields = false;
	/**
	 * @var TAbstractConnection
	 */
	protected $connection;

	function __construct(TAbstractConnection $connection = null)
	{
		global $Connection;
		if(empty($connection))
			$connection = $Connection;
		$this->connection = $connection;
		$this->ExploreSchema();
		//echo "<pre>";
		//var_dump( $this->Tables );
		//echo "</pre>";
	}

	// explore database schema, tables, and links between them
	protected function ExploreSchema()
	{
		$this->Tables = array();
		$q = new TQuery("show tables", $this->connection);
		while( !$q->EOF )
		{
			$sTable = array_pop( $q->Fields );
			if( strtolower( $sTable ) != $sTable ) {
				$arFields = $this->ExploreTableFields($sTable);
				$arTable = array(
				"PrimaryKey" => $this->connection->PrimaryKeyField( $sTable ),
				"Fields" => $arFields,
				"References" => array(),
				"ReferencedBy" => array(),
				"Files" => $this->ExploreTableFiles( $sTable, $arFields ),
				"CopyAllowed" => true);
				$this->Tables[$sTable] = $arTable;
			}
			$q->Next();
		}
		$this->BuildReferences();
	}

	// return all file specs stored in table
	private function ExploreTableFiles( $sTable, $arFields ) {
		$arFiles = array();
		foreach ( $arFields as $sField => $arField ) {
			if( preg_match( "/^(\w+)Ver$/", $sField, $arMatches ) && isset( $arFields[$arMatches[1]."Ext"] ) ) {
				$sFolder = strtolower( $sTable );
				$sPrefix = strtolower( $arMatches[1] );
				if( $sTable == "Picture" ) {
					$sFolder = "album";
					$sPrefix = "pic";
				}
				if( isset( $arFields[$arMatches[1]."OriginalWidth"] ) || ( $arMatches[1] == "Image" ) ) {
					// picture
					$arFiles[] = array(
						"Type" => "FileManager",
						"Field" => $arMatches[1],
						"Dir" => "/images/uploaded/$sFolder/small",
						"Prefix" => $sPrefix,
						"Suffix" => null,
						"ForceExtension" => "gif",
					);
					$arFiles[] = array(
						"Type" => "FileManager",
						"Field" => $arMatches[1],
						"Dir" => "/images/uploaded/$sFolder/medium",
						"Prefix" => $sPrefix,
						"Suffix" => null,
					);
					$arFiles[] = array(
						"Type" => "FileManager",
						"Field" => $arMatches[1],
						"Dir" => "/images/uploaded/$sFolder/large",
						"Prefix" => $sPrefix,
						"Suffix" => null,
					);
					$arFiles[] = array(
						"Type" => "FileManager",
						"Field" => $arMatches[1],
						"Dir" => "/images/uploaded/$sFolder/original",
						"Prefix" => $sPrefix,
						"Suffix" => null,
					);
				}
				else
					// file
					$arFiles[] = array(
						"Type" => "FileManager",
						"Field" => $arMatches[1],
						"Dir" => "/images/uploaded/" . $sFolder,
						"Prefix" => $sPrefix,
						"Suffix" => null,
					);
			}
		}
		return $arFiles;
	}

	// build references between tables
	private function BuildReferences()
	{
		foreach ( $this->Tables as $sFromTable => $arTable )
		{
			foreach ( $arTable["Fields"] as $sFieldName => $arField )
			{
				if( $sFieldName == $arTable["PrimaryKey"] )
					continue;
				$sToTable = $this->ReferencedTable( $sFromTable, $sFieldName );
				if( isset( $sToTable ) )
					$this->AddReference( $sFromTable, $sFieldName, $sToTable, "cascade" );
			}
		}
	}

	// register reference
	private function AddReference( $sFromTable, $sFromField, $sToTable, $sOnDelete )
	{
		$this->Tables[$sFromTable]["References"][] = array(
		"Field" => $sFromField,
		"Table" => $sToTable,
		);
		$this->Tables[$sToTable]["ReferencedBy"][] = array(
		"Field" => $sFromField,
		"Table" => $sFromTable,
		"OnDelete" => $sOnDelete,
		);
	}

	// return referenced table basing on source fieldname and tablename
	// return null if can't find reference
	function ReferencedTable( $sTable, $sFieldName )
	{
		if( $sFieldName == "UserID" )
			return "Usr";
		if( preg_match( "/^(\w+)ID$/", $sFieldName, $arMatches ) && isset( $this->Tables[$arMatches[1]] ) )
			return $arMatches[1];
		return null;
	}

	// return: array( "Field1" => array( "Type" => "varchar(80)", "Null" => False ), ... )
	private function ExploreTableFields( $sTable )
	{
		$arFields = array();
		$q = new TQuery("describe $sTable", $this->connection);
		while( !$q->EOF )
		{
			$arFields[$q->Fields["Field"]] = array(
			"Type" => $q->Fields["Type"],
			"Null" => $q->Fields["Null"] == "YES",
			);
			$q->Next();
		}
		return $arFields;
	}

	// get rows files
	public function RowFiles( $sTable, $arFields ) {
		$arTable = $this->Tables[$sTable];
		$sPrimaryKey = $this->Tables[$sTable]["PrimaryKey"];
		$arFiles = $arTable["Files"];
		$arResults = array();
		foreach( $arFiles as $arFile )
			switch( $arFile["Type"] ) {
				case "FileManager":
					if( !in_array( $arFile["Field"]."Ver", array_keys( $arFields ) ) ) {
						var_dump( $sTable );
						var_dump( $arTable );
						var_dump( $arFields );
						DieTrace("x");
					}
					if( $arFields[$arFile["Field"]."Ver"] != "" ) {
						$sExt = $arFields[$arFile["Field"]."Ext"];
						if( isset( $arFile["ForceExtension"] ) )
							$sExt = $arFile["ForceExtension"];
						$sFile = FilePath( $arFile["Dir"], $arFields[$sPrimaryKey], $arFields[$arFile["Field"]."Ver"], $sExt, $arFile["Prefix"], $arFile["Suffix"] );
						$arResults[] = array(
							"Field" => $arFile["Field"],
							"File" => $sFile,
							"Exist" => file_exists( __DIR__ . '/../..' . $sFile ),
						);
					}
					break;
				default:
					DieTrace("Unsupported file type: ".$arFile["Type"]);
			}
		return $arResults;
	}

	// delete row from table, including all dependend rows and files
	// return array of deleted records: array(see ChildRows())
	// bDelete - do actual delete, or only discover what will be deleted
	// (can be more than one, in case of deleting childs)
	// to see
	public function DeleteRow( $sTable, $nID, $bDelete )
	{
		$nID = intval( $nID );
		$q = new TQuery("select * from $sTable where {$this->Tables[$sTable]["PrimaryKey"]} = $nID", $this->connection );
		if( $q->EOF )
			return 0;
		$arRows = $this->ChildRows( $sTable, $q->Fields );
		$row = $this->SingleRow($sTable, $q->Fields);
		if($this->ReturnFields)
			$row['Fields'] = $q->Fields;
		$arRows[] = $row;
		if( $bDelete )
			foreach( $arRows as $arRow )
				$this->DeleteSingleRow($arRow);
		return $arRows;
	}

	public function SingleRow($sTable, array $fields){
		$sPrimaryKey = $this->Tables[$sTable]["PrimaryKey"];
		return array("Table" => $sTable,
			"ID" => $fields[$sPrimaryKey],
			"Files" => $this->RowFiles($sTable, $fields));
	}

	// actually delete a row
	protected function DeleteSingleRow($arRow){
		foreach ( $arRow["Files"] as $arFile )
			if( $arFile["Exist"] && file_exists(__DIR__ . '/../..' . $arFile["File"]) )
				@unlink( __DIR__ . '/../..' . $arFile["File"] );

        $this->doDeleteSingleRow($arRow);
	}

    protected function doDeleteSingleRow($arRow)
    {
        $this->connection->Delete($arRow['Table'], $arRow['ID']);
    }

	// returns sql for child row
	protected function ChildRowsQuery($sSubTable, $sSubKey, $nID){
		return "select * from $sSubTable where $sSubKey = $nID";
	}

	// get other tables dependent rows for this row
	// return: array( array( "Table" => "Table1", "ID" => 16, "Files" => array(see RowFiles) ), array..
	// recursive
	public function ChildRows( $sTable, $arFields, &$arExcludeRows = null, $loadChild = null, $loadDependencies = false )
	{
		$arRows = array();
		if( !isset( $arExcludeRows ) )
			$arExcludeRows = array();
		$sPrimaryKey = $this->Tables[$sTable]["PrimaryKey"];
		if(!isset($arFields[$sPrimaryKey]))
			return $arRows;
		$nID = $arFields[$sPrimaryKey];
		$sKey = $sTable . "_" . $nID;
		if( !in_array( $sKey, $arExcludeRows ) && (empty($loadChild) || call_user_func($loadChild, $sTable, $nID)) ) {
			$arExcludeRows[] = $sKey;
			foreach ( $this->Tables[$sTable]["ReferencedBy"] as $arReference ) {
				$sSubTable = $arReference["Table"];
				$sSubKey = $arReference["Field"];
				$subPrimaryKey = $this->Tables[$sSubTable]["PrimaryKey"];
				if(isset($this->Tables[$sSubTable]["Fields"][$subPrimaryKey])){
					$q = new TQuery($this->ChildRowsQuery($sSubTable, $sSubKey, $nID), $this->connection);
					while( !$q->EOF )
					{
						$sKey = $sSubTable . "_" . $q->Fields[$subPrimaryKey];
						if( !in_array( $sKey, $arExcludeRows ) && (empty($loadChild) || call_user_func($loadChild, $sSubTable, $q->Fields[$subPrimaryKey])) ) {
							//$arExcludeRows[] = $sKey;
							$arSubRows = $this->ChildRows( $sSubTable, $q->Fields, $arExcludeRows, $loadChild, $loadDependencies );
							$arRows = array_merge($arRows, $arSubRows);
							$arRow = array(
								"Table" => $sSubTable,
								"ID" => $q->Fields[$this->Tables[$sSubTable]["PrimaryKey"]],
								"Files" => $this->RowFiles( $sSubTable, $q->Fields ),
							);
							if($this->ReturnFields)
								$arRow['Fields'] = $q->Fields;
							$arRows[] = $arRow;
						}
						$q->Next();
					}
				}
			}

            if ($loadDependencies) {
                foreach ( $this->Tables[$sTable]["References"] as $arReference ) {
                    if (!empty($arFields[$arReference['Field']])) {
                        $depKey = $arReference['Table'] . "_" . $arFields[$arReference['Field']];
                        if (!in_array($depKey, $arExcludeRows)) {
                            $arExcludeRows[] = $depKey;
                            $depQ = new TQuery("select * from {$arReference['Table']} where {$this->Tables[$arReference['Table']]["PrimaryKey"]} = {$arFields[$arReference['Field']]}");
                            if (!$depQ->EOF) {
                                $dependentRow = $this->SingleRow($arReference['Table'], $depQ->Fields);
                                $arRows[] = $dependentRow;
                                $arSubRows = $this->ChildRows($arReference['Table'], $depQ->Fields, $arExcludeRows,
                                    $loadChild, $loadDependencies);
                                $arRows = array_merge($arRows, $arSubRows);
                            }
                        }
                    }
                }
             }

			switch ( $sTable ) {
				case "Picture":
					if( isset( $this->Tables["Cart"] ) && isset( $this->Tables["CartItem"] ) ) {
						$q = new TQuery("select * from CartItem ci, Cart c where ci.TypeID = " . CART_PICTURE . " and ci.ID = {$arFields["PictureID"]} and ci.CartID = c.CartID and c.PayDate is null", $this->connection);
						while( !$q->EOF ) {
							$arRow = array(
								"Table" => "CartItem",
								"ID" => $q->Fields["CartItemID"],
								"Files" => array(),
							);
							if($this->ReturnFields)
								$arRow['Fields'] = $q->Fields;
							$arRows[] = $arRow;
							$q->Next();
						}
					}
					break;
			}
		}
		return $arRows;
	}

	// get rows on which this row references to
	// return: array( array( "Field" => "Field1", "Table" => "Table1", "Exist" => True ), array..
	// not recursive
	public function ParentRows( $sTable, $arFields )
	{
		$arTable = $this->Tables[$sTable];
		$arRows = array();
		foreach ( $arTable["References"] as $arReference )
		if( isset( $arFields[$arReference["Field"]] ) && ( $arFields[$arReference["Field"]] != "" ) )
		{
			$sKey = $this->Tables[$arReference["Table"]]["PrimaryKey"];
			$q = new TQuery("select 1 from {$arReference["Table"]} where {$sKey} = {$arFields[$arReference["Field"]]}", $this->connection );
			$arRows[] = array(
			"Field" => $arReference["Field"],
			"Table" => $arReference["Table"],
			"Exist" => !$q->EOF,
			);
		}
		return $arRows;
	}

	public function loadRows(&$rows){
		foreach($rows as &$row){
			$q = new TQuery("select * from {$row['Table']} where ".$this->connection->PrimaryKeyField($row['Table'])." = '".$row['ID'] . "'", $this->connection);
			if($q->EOF)
				DieTrace("Row not found: {$row['Table']}, {$row['ID']}");
			unset($q->Fields[$this->connection->PrimaryKeyField($row['Table'])]);
			$row['Values'] = $q->Fields;
			$row['CopyAllowed'] = $this->Tables[$row['Table']]['CopyAllowed'];
		}
		foreach($rows as &$row){
			if(!$row['CopyAllowed'])
				$this->skipChildRows($rows, $row);
		}
	}

	// create copy of rows, correct links between rows
	public function CopyRows(&$rows){
		foreach($rows as &$row){
			if($row['CopyAllowed']){
				$this->copyRow($row);
				$this->correctLinks($rows, $row);
			}
		}
	}

	protected function copyRow(&$row){
		foreach($row['Values'] as $key => &$value){
			if($value == '' && $this->Tables[$row['Table']]['Fields'][$key]['Null'])
				$value = "null";
			else
				$value = "'".addslashes($value)."'";
		}
		$sql = InsertSQL($row['Table'], $row['Values']);
		echo $sql."<br/>";
		$this->connection->Execute($sql);
		$row['NewID'] = $this->connection->InsertID();
		//$row['NewID'] = $row['ID'] + 1000;
	}

	protected function skipChildRows(&$rows, $masterRow){
		foreach($this->Tables[$masterRow['Table']]['ReferencedBy'] as $reference){
			foreach($rows as &$row){
				if(($row['Table'] == $reference['Table']) && ($row['Values'][$reference['Field']] == $masterRow['ID']) && $row['CopyAllowed']){
					$row['CopyAllowed'] = false;
					$this->skipChildRows($rows, $row);
				}
			}
		}
	}

	protected function correctLinks(&$rows, $changedRow){
		foreach($this->Tables[$changedRow['Table']]['ReferencedBy'] as $reference){
			foreach($rows as &$row){
				if(($row['Table'] == $reference['Table']) && ($row['Values'][$reference['Field']] == $changedRow['ID'])){
					$row['Values'][$reference['Field']] = $changedRow['NewID'];
				}
			}
		}
	}

}
