<?
// -----------------------------------------------------------------------
// mysql connection class.
//		Contains class, to handle mysql database connection
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once( __DIR__."/TAbstractConnection.php" );

class TMySQLConnection extends TAbstractConnection
{
	// mysql connection id
	var $ConnectionID = NULL;
	var $Parameters;
	var $AffectedRows;

	protected $InsertID;

	// open connection, $arParams - configuration parameters,
	// Login, Password, Host, Database, Persistent, ErrorHandler
	// returns boolean - is database opened successfully. dies on error.
	// Persistent - establish persistent connection (default), set to False to change
	// ErrorHandler - this function will be called on connection error
	function Open( $arParameters = null, $newLink = false )
	{
		if( !isset( $arParameters ) && !isset( $this->Parameters ) )
			DieTrace("Parameters required");
		if( !isset( $arParameters ) )
			$arParameters = $this->Parameters;
		$this->Parameters = $arParameters;
		$this->OpenConnection($arParameters, $newLink);
		if(!$this->ConnectionID)
			return false;
		$result = @mysql_select_db($arParameters["Database"], $this->ConnectionID);
		if(!$result && (mysql_errno($this->ConnectionID) == 2006)){ // mysql gone away, reconnect
			$this->OpenConnection($arParameters, $newLink);
			$result = @mysql_select_db($arParameters["Database"], $this->ConnectionID);
		}
		if(!$result)
			if(isset($arParameters['ErrorHandler']))
				call_user_func($arParameters['ErrorHandler'], mysql_error($this->ConnectionID));
			else
				DieTrace( "TMySQLConnection->Open: Error opening database <b>{$arParameters["Database"]}</b>: ".mysql_errno($this->ConnectionID).", ".mysql_error($this->ConnectionID));
		$this->Active = true;
		return true;
	}

	private function Connect($arParameters, $newLink){
		if((isset( $arParameters[ "Persistent" ] ) && !$arParameters[ "Persistent" ]) || $newLink)
			@$this->ConnectionID = mysql_connect( $arParameters[ "Host" ], $arParameters[ "Login" ], $arParameters[ "Password" ], $newLink );
		else
			@$this->ConnectionID = mysql_pconnect( $arParameters[ "Host" ], $arParameters[ "Login" ], $arParameters[ "Password" ] );
	}

	function OpenConnection($arParameters, $newLink){
		$this->Connect($arParameters, $newLink);
		for($retry = 0; !$this->ConnectionID && in_array(mysql_errno(), array(2006, 2013, 2003)) && $retry < 5; $retry++){ // mysql gone away, reconnect
			sleep(1);
			$this->Connect($arParameters, $newLink);
		}
		if( !$this->ConnectionID )
			if(isset($arParameters['ErrorHandler']))
				call_user_func($arParameters['ErrorHandler'], mysql_error());
			else
				DieTrace("Can't connect to server: [" . mysql_errno()."] ".mysql_error());
	}

	// close connection. returns true on success . dies on error
	function Close()
	{
        if ($this->ConnectionID) {
            mysql_close($this->ConnectionID);
        }

		$this->ConnectionID = NULL;
		$this->Active = false;
	}

	// execute sql, not returning query (insert,update,delete..)
	// returns boolean - executed successfully. dies on error.
	function Execute( $sSQL, $bDieOnError = true )
	{
		$startTime = microtime(true);
		$result = $this->SendQuery($sSQL, $bDieOnError);
		$this->AffectedRows = mysql_affected_rows($this->ConnectionID);
		if($this->Tracing && isset($this->TraceTable))
			$this->InsertID = mysql_insert_id($this->ConnectionID);
		$this->TraceQuery($sSQL, $startTime, null, $this->AffectedRows);
		return $result;
	}

	function GetLastError(){
		return mysql_error( $this->ConnectionID );
	}
	
	/**
	 * send query to server
	 * handle reconnects and errors
	 * @return query id, or bool
	 */
	function SendQuery($sql, $dieOnError = true){
		$result = mysql_query($sql, $this->ConnectionID);
		if(!$result && in_array(mysql_errno($this->ConnectionID), array(2006, 2013))){ // mysql gone away, reconnect
			$this->Reconnect();
			$result = mysql_query($sql, $this->ConnectionID);
		}
		$retry = 0;
		while(!$result && in_array(mysql_errno($this->ConnectionID), array(1213, 1205)) && $retry < 10){ // deadlock, restart
			sleep(rand(1, 7));
			$result = mysql_query($sql, $this->ConnectionID);
			$retry++;
		}
		if(!$result && $dieOnError)
			DieTrace("mysql error: [".mysql_errno($this->ConnectionID)."] ".mysql_error($this->ConnectionID).": ".$sql);
		return $result;
	}

	// open SQL query returning rows (select)
	// returns QueryID or 0
	function OpenQuery( $sSQL )
	{
//		if($this->Tracing)
//			$sSQL = preg_replace("/^select\s+/ims", "select /*! SQL_NO_CACHE */ ", $sSQL);
		$startTime = microtime(true);
		$result = $this->SendQuery($sSQL);
		$this->TraceQuery($sSQL, $startTime, $result);
		return $result;
	}

	// close query with specified QueryID
	function CloseQuery( $rQueryID )
	{
		mysql_free_result( $rQueryID );
	}

	// fetch row from specified query QueryID.
	// returns array( Field => Value.. ) or 0 on EOF
	function Fetch( $rQueryID )
	{
		$row = mysql_fetch_assoc( $rQueryID );
		$this->TraceRow($rQueryID);
		return $row;
	}

	// converts unix date/time to sql-string
	function DateTimeToSQL( $dDateTime, $bIncludeTime = true )
	{
		if( $bIncludeTime )
			return date( "'Y-m-d H:i:s'", mktime( date( "H", $dDateTime ), date( "i", $dDateTime ), date( "s", $dDateTime ), date( "m", $dDateTime ), date( "d", $dDateTime ), date( "Y", $dDateTime ) ) );
		else
			return date( "'Y-m-d'", mktime( date( "H", $dDateTime ), date( "i", $dDateTime ), date( "s", $dDateTime ), date( "m", $dDateTime ), date( "d", $dDateTime ), date( "Y", $dDateTime ) ) );
	}

	// converts date/time string returned from server to unix date/time
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

	function Reconnect(){
		$this->ConnectionID = null;
		$this->Open($this->Parameters);
	}

	// determine table primary key field
	function PrimaryKeyField( $sTable )
	{
		$Result = $this->Fetch($res = $this->OpenQuery("select COLUMN_NAME from information_schema.columns where table_schema=DATABASE() and table_name = '$sTable' and column_key='PRI'"));
		$this->CloseQuery($res);
		if(is_array($Result))
			return $Result['COLUMN_NAME'];
		else
			return $sTable.'ID';
	}

	function InsertID(){
		if($this->Tracing && isset($this->TraceTable))
			return $this->InsertID;
		else
			return mysql_insert_id($this->ConnectionID);
	}

    function Delete($sTable, $sValue)
    {
        $this->Execute("delete from {$sTable} where " . $this->PrimaryKeyField($sTable) . " = '".addslashes($sValue)."'");
    }

	function GetTime(){
		$q = new TQuery("select now() as Time");
		return $this->SQLToDateTime($q->Fields['Time']);
	}

	public function GetAffectedRows()
    {
        return $this->AffectedRows;
    }
}
