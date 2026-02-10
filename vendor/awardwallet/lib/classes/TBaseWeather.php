<?

// get weather info
class TBaseWeather{
	
	public $XMLURL;
	public $Weather;
	public $DataTag;
	
	function TBaseWeather(){
		$this->XMLURL = GetDBParam("WeatherXMLURL");
	}
	
	function GetWeather(){
		global $Connection;
		$this->Weather = null;
		$this->ReadCache();
		if( isset( $this->Weather ) )
			return true;
		$dLastUpdate = GetDBParam( "WeatherCheckDate" );
		if( $dLastUpdate != "" ){
			$dLastUpdate = $Connection->SQLToDateTime( $dLastUpdate );
			if( ( time() - $dLastUpdate ) < 60*10 )
				return false;
		}
		SetDBParam( "WeatherCheckDate", PARAM_TYPE_STRING, $Connection->DateTimeToSQL( time() ), false );
		if( trim( $this->XMLURL ) == "" )
			DieTrace("Please set XMLURL");
		$rQuery = curl_init( $this->XMLURL );
		if( !$rQuery )
			return false;
		curl_setopt( $rQuery, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $rQuery, CURLOPT_TIMEOUT, 5 );
		curl_setopt( $rQuery, CURLOPT_HEADER, false );
		curl_setopt( $rQuery, CURLOPT_FAILONERROR, true );
		curl_setopt( $rQuery, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		curl_setopt( $rQuery, CURLOPT_RETURNTRANSFER, true );
		$sHTML = curl_exec( $rQuery );
		if( !$sHTML )
			return false;
		$this->ParseXML( $sHTML );
		if( !isset( $this->Weather ) )
			return false;
		$this->WriteCache();
		return true;
	}
	
	function ReadCache(){
		global $Connection;
		$dLastUpdate = GetDBParam( "WeatherUpdateDate" );
		if( $dLastUpdate == "" )
			return false;
		$dLastUpdate = $Connection->SQLToDateTime( $dLastUpdate );
		if( ( time() - $dLastUpdate ) > 1800 )
			return false;
		$q = new TQuery("select Name, StringVal from Param where Name like 'WeatherParam%'");
		if( $q->EOF )
			return false;
		$this->Weather = array();
		while( !$q->EOF ){
			$this->Weather[substr( $q->Fields["Name"], 12 )] = $q->Fields["StringVal"];
			$q->Next();
		}
		return true;
	}
	
	function WriteCache(){
		global $Connection;
		foreach ( $this->Weather as $sKey => $sValue )
			SetDBParam( "WeatherParam".$sKey, PARAM_TYPE_STRING, $sValue );	
		SetDBParam( "WeatherUpdateDate", PARAM_TYPE_STRING, $Connection->DateTimeToSQL( time() ), false );
	}
	
	function ParseXML( $sXML ){
		global $objWeather;
		if( !isset( $objWeather ) )
			DieTrace("\$objWeather variable should be set");
		$rParser = xml_parser_create();
		$this->Weather = array();
		xml_set_element_handler( $rParser, "WeatherStartElement", "WeatherEndElement" ); 
		xml_set_character_data_handler( $rParser, "WeatherCharacterData" ); 
		if( !xml_parse( $rParser, $sXML, true ) || ( count( $this->Weather ) == 0 ) ){
			$this->Weather = null;
			return false; 
		}
		xml_parser_free( $rParser );
		return true;
	}
	
	function GetHTML(){
		if( !isset( $this->Weather ) )
			$this->GetWeather();
	}
	
}

$sWeatherDataTag = null;

// ---------------------------------------------------------------
// called on <tag>
function WeatherStartElement( $rParser, $sName, $arAttrs ) 
{
	global $objWeather;
	$objWeather->DataTag = $sName;
}

// ---------------------------------------------------------------
// called on </tag>
function WeatherEndElement( $rParser, $sName ) 
{
	global $objWeather;
	$objWeather->DataTag = "";
}

// ---------------------------------------------------------------
// called on <tag>data</tag>
function WeatherCharacterData( $rParser, $sData ) 
{
	global $objWeather;
	switch( $objWeather->DataTag )
	{
		case "WEATHER":
		case "TEMP_F":
		case "RELATIVE_HUMIDITY":
		case "WIND_DIR":
		case "WIND_MPH":
		case "PRESSURE_STRING":
		case "ICON_URL_BASE":
		case "ICON_URL_NAME":
			if( !isset( $objWeather->Weather[$objWeather->DataTag] ) )
				$objWeather->Weather[$objWeather->DataTag] = $sData;
			else 
				$objWeather->Weather[$objWeather->DataTag] .= $sData;
			break;
	}
}


?>