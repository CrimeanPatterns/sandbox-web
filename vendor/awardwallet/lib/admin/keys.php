<?
require __DIR__ . '/../../kernel/public.php';

require("$sPath/lib/admin/design/header.php");

ob_end_flush();

$result = array();

foreach(new TQuery("show tables") as $row){
	$table = array_pop($row);
	$q = new TQuery("show create table $table");
	if(preg_match("/AUTO_INCREMENT=(\d+)/", ArrayVal($q->Fields, 'Create Table'), $matches))
		$result[$table] = floatval($matches[1]);
}

arsort($result);
echo "<pre>";
var_export(array_map(function($value){ return number_format($value, 0, ".", ","); }, $result));
echo "</pre>";

require( "$sPath/lib/admin/design/footer.php" );
