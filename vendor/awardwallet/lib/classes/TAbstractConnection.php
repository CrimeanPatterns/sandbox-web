<?

// -----------------------------------------------------------------------
// Connection class.
//		Contains abstract class, to handle database connection
//		You should override class to build custom database connection
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class TAbstractConnection
{
	// is connection open, boolean
	var $Active = false;
	// how sql-server return/handle boolean/logical type
	// some servers returns boolean as 1/0, some as "True"/"False"
	var $BooleanSelectTrue = 1;
	var $BooleanInsertTrue = 1;
	var $BooleanSelectFalse = 0;
	var $BooleanInsertFalse = 0;

	// tracing
	public $Tracing = false;
	public $TraceData = array();
	public $TraceTable;

	// add query to trace
	// startTime should be microtime(true)
	function TraceQuery($sql, $startTime, $queryId = null, $rows = 0){
		if(!$this->Tracing)
			return;
		if(isset($queryId))
			$key = "q".$queryId;
		else
			$key = "u".count($this->TraceData);
		if(isset($this->TraceTable)){
			$this->Tracing = false;
			$this->Execute(InsertSQL($this->TraceTable, array(
				"QueryID" => "'$key'",
				"StartTime" => $startTime,
				"EndTime" => microtime(true),
				"SQLText" => "'".addslashes($sql)."'",
				"Rows" => $rows,
			)));
			$this->Tracing = true;
			return;
		}
		$this->TraceData[$key] = array(
			"StartTime" => $startTime,
			"EndTime" => microtime(true),
			"SQL" => $sql,
			"Rows" => $rows,
		);
	}

	function TraceRow($queryId){
		if(!$this->Tracing)
			return;
		if(isset($this->TraceTable)){
			$this->Tracing = false;
			$this->Execute(UpdateSQL($this->TraceTable,
			array(
				"QueryID" => "'q$queryId'"
			),
			array(
				"Rows" => "Rows + 1",
				//"EndTime" => microtime(true)
			)));
			$this->Tracing = true;
			return;
		}
		$this->TraceData['q'.$queryId]['Rows']++;
		//$this->TraceData['q'.$queryId]['EndTime'] = microtime(true);
	}

	function ShowTraceData(){
		echo "SQL Trace data:<br><table border=1 cellpadding=2 cellspacing=0 class=detailsTable>
		<tr><td>Duration</td><td>SQL</td><td>Rows</td></tr>";
		$count = 0;
		$rows = 0;
		$time = 0;
		foreach($this->TraceData as &$query){
			$query["Duration"] = $query["EndTime"] - $query["StartTime"];
		}
		usort($this->TraceData, function($a, $b){
			if($a["Duration"] == $b["Duration"])
				return 0;
			else
				if($a["Duration"] > $b["Duration"])
					return 1;
				else
					return 0;
		});
		foreach($this->TraceData as $query){
			$duration = $query["Duration"];
			$time += $duration;
			$rows += $query['Rows'];
			$count++;
			$duration = number_format($duration, 6);
			if($duration >= 0.1)
				$duration = "<span style='color: red; font-weight: bold;'>$duration</span>";
			echo "<tr><td>{$duration}</td><td><div style='max-height: 80px; overflow: auto;' onclick='selectSQL(this);'>{$query["SQL"]}</div></td><td>{$query["Rows"]}</td></tr>";
		}
		echo "<tr style='font-weight: bold;'><td>".number_format($time, 6)." secs</td><td>".count($this->TraceData)." queries</td><td>{$rows} rows</td></tr>";
		echo "</table>";
		echo "<script>function selectSQL(cell){
			cell.parentNode.innerHTML = '<textarea style=\"width: 100%; height: 160px;\">' + cell.innerHTML + '</textarea>';
		}</script>";
	}

	// open connection, $arParams - configuration parameters,
	// typically Login, Password, Host, Database
	// returns boolean - is database opened successfully. dies on error.
	function Open( $arParams )
	{
		DieTrace( "call to abstract method" );
	}

	// close connection. returns true on success . dies on error
	function Close()
	{
		DieTrace( "call to abstract method" );
	}

	// execute sql, not returning query (insert,update,delete..)
	// returns boolean - executed successfully. dies on error.
	function Execute( $sSQL )
	{
		DieTrace( "call to abstract method" );
	}

	// open SQL query returning rows (select)
	// returns QueryID or 0
	function OpenQuery( $sSQL )
	{
		DieTrace( "call to abstract method" );
	}

	// close query with specified QueryID
	function CloseQuery( $QueryID )
	{
		DieTrace( "call to abstract method" );
	}

	// fetch row from specified query QueryID.
	// returns array( Field => Value.. ) or 0 on EOF
	function Fetch( $QueryID )
	{
		DieTrace( "call to abstract method" );
	}

	// converts unix date/time to sql-string
	function DateTimeToSQL( $dDateTime, $bIncludeTIme = false )
	{
		DieTrace( "call to abstract method" );
	}

	// converts date/time string returned from server to unix date/time
	function SQLToDateTime( $dDateTime )
	{
		DieTrace( "call to abstract method" );
	}

	// determine table primary key field
	function PrimaryKeyField( $sTable )
	{
		if( $sTable == "Usr" )
			return "UserID";
		if( $sTable == "adminLeftNav" )
			return "id";
		else
			return $sTable . "ID";
	}
}

?>
