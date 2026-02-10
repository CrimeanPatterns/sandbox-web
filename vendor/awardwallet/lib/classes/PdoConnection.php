<?
// -----------------------------------------------------------------------
// mysql connection class.
//		Contains class, to handle mysql database connection
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class PdoConnection extends TAbstractConnection
{
    /**
     * @var \PDO
     */
	var $Connection;
	var $Parameters;
	var $AffectedRows;

	protected $InsertID;

	// open connection, $arParams - configuration parameters,
	// Login, Password, Host, Database, Persistent, ErrorHandler
	// returns boolean - is database opened successfully. dies on error.
	// Persistent - establish persistent connection (default), set to False to change
	// ErrorHandler - this function will be called on connection error

	function Close()
	{
		$this->Connection = NULL;
		$this->Active = false;
	}

	function GetLastError(){
	    // Exceptions will be thrown
		return null;
	}

	function DateTimeToSQL( $dDateTime, $bIncludeTime = true )
	{
		if( $bIncludeTime )
			return date( "'Y-m-d H:i:s'", mktime( date( "H", $dDateTime ), date( "i", $dDateTime ), date( "s", $dDateTime ), date( "m", $dDateTime ), date( "d", $dDateTime ), date( "Y", $dDateTime ) ) );
		else
			return date( "'Y-m-d'", mktime( date( "H", $dDateTime ), date( "i", $dDateTime ), date( "s", $dDateTime ), date( "m", $dDateTime ), date( "d", $dDateTime ), date( "Y", $dDateTime ) ) );
	}

	// close connection. returns true on success . dies on error

	function Reconnect(){
		$this->ConnectionID = null;
		$this->Open($this->Parameters);
	}

	// execute sql, not returning query (insert,update,delete..)
	// returns boolean - executed successfully. dies on error.

	function Open( $arParameters = null, $newLink = false )
	{
		if( !isset( $arParameters ) && !isset( $this->Parameters ) )
			DieTrace("Parameters required");
		if( !isset( $arParameters ) )
			$arParameters = $this->Parameters;
		$this->Parameters = $arParameters;
		$this->OpenConnection($arParameters, $newLink);
		if(!$this->Connection)
			return false;
		$this->Active = true;
		return true;
	}

	function OpenConnection($arParameters, $newLink){
        $this->Connect($arParameters, $newLink);
	}
	
	private function Connect($arParameters, $newLink){
	    $connStr = "mysql:host={$arParameters["Host"]};dbname={$arParameters["Database"]}";
	    if (isset($arParameters['Port'])) {
	        $connStr .= ";port={$arParameters['Port']}";
        }
        $this->Connection = new \PDO($connStr, $arParameters[ "Login" ], $arParameters[ "Password" ], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
	}

	// open SQL query returning rows (select)
	// returns QueryID or 0

	function InsertID(){
		if($this->Tracing && isset($this->TraceTable))
			return $this->InsertID;
		else
			return $this->Connection->lastInsertId();
	}

	// close query with specified QueryID

    function Delete($sTable, $sValue)
    {
        $this->Execute("delete from {$sTable} where " . $this->PrimaryKeyField($sTable) . " = '".addslashes($sValue)."'");
    }

	// fetch row from specified query QueryID.
	// returns array( Field => Value.. ) or 0 on EOF

	function Execute( $sSQL, $bDieOnError = true )
	{
		$startTime = microtime(true);
        $this->AffectedRows = $this->Connection->exec($sSQL);
		$this->TraceQuery($sSQL, $startTime, null, $this->AffectedRows);
		return true;
	}

	// converts unix date/time to sql-string

	function PrimaryKeyField( $sTable )
	{
		$Result = $this->Fetch($res = $this->OpenQuery("select column_name from information_schema.columns where table_schema=DATABASE() and table_name = '$sTable' and column_key='PRI'"));
		$this->CloseQuery($res);
		if(is_array($Result))
			return $Result['column_name'];
		else
			return $sTable.'ID';
	}

	// converts date/time string returned from server to unix date/time

    /**
     * @param \PDOStatement $rQueryID
     * @return array|void
     */
	function Fetch( $rQueryID )
	{
		$row = $rQueryID->fetch(\PDO::FETCH_ASSOC);
		$this->TraceRow($rQueryID);
		return $row;
	}

	function OpenQuery( $sSQL )
	{
//		if($this->Tracing)
//			$sSQL = preg_replace("/^select\s+/ims", "select /*! SQL_NO_CACHE */ ", $sSQL);
		$startTime = microtime(true);
		$result = $this->SendQuery($sSQL);
		$this->TraceQuery($sSQL, $startTime, $result);
		return $result;
	}

	// determine table primary key field

	/**
	 * send query to server
	 * handle reconnects and errors
	 * @return query id, or bool
	 */
	function SendQuery($sql, $dieOnError = true){
		$result = $this->Connection->query($sql, \PDO::FETCH_ASSOC);
//		if(!$result && in_array(mysql_errno($this->ConnectionID), array(2006, 2013))){ // mysql gone away, reconnect
//			$this->Reconnect();
//			$result = mysql_query($sql, $this->ConnectionID);
//		}
//		$retry = 0;
//		while(!$result && in_array(mysql_errno($this->ConnectionID), array(1213, 1205)) && $retry < 10){ // deadlock, restart
//			sleep(rand(1, 7));
//			$result = mysql_query($sql, $this->ConnectionID);
//			$retry++;
//		}
//		if(!$result && $dieOnError)
//			DieTrace("mysql error: [".mysql_errno($this->ConnectionID)."] ".mysql_error($this->ConnectionID).": ".$sql);
		return $result;
	}

    /**
     * @param \PDOStatement $rQueryID
     */
	function CloseQuery( $rQueryID )
	{
		$rQueryID->closeCursor();
	}

	function GetTime(){
		$q = new TQuery("select now() as Time");
		return $this->SQLToDateTime($q->Fields['Time']);
	}

	function SQLToDateTime( $sDateTime )
	{
		if( preg_match( "/([0-9]{4})\-([0-9]{2})\-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/ims", $sDateTime, $Args ) )
			return mktime( $Args[4], $Args[5], $Args[6], $Args[2], $Args[3], $Args[1] );
		else
			if( preg_match( "/([0-9]{4})\-([0-9]{2})\-([0-9]{2})/ims", $sDateTime, $Args ) )
				return mktime( 0, 0, 0, $Args[2], $Args[3], $Args[1] );
			else
				DieTrace( "Invalid date format:$sDateTime" );
	}

	public function GetAffectedRows()
    {
        return $this->AffectedRows;
    }
}
