<?
class OTAConverter{

	/**
	 * get converter from itinerary to OTA xml
	 * @static
	 * @param $kind
	 * @param int $category
	 * @return OTABase
	 */
	public static function getConverter($kind, $category = 0, $apiVersion){
		global $arDetailTable;

		if ($category == -1)
			$className = "OTACancel";
		elseif ($kind == 'T' && $category == TRIP_CATEGORY_CRUISE)
			$className = "OTACruiseBook";
		else
			$className = "OTA".$arDetailTable[$kind];

		require_once __DIR__."/OTABase.php";
		require_once __DIR__."/{$className}.php";
		$converter = new $className($kind, $apiVersion);
		return $converter;
	}

}