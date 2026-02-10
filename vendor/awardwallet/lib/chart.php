<?
header('Content-Type: image/png');
header("Cache-control: public");
header("Cache-control: max-age=7200");

readfile("http://chart.apis.google.com/chart?".$_SERVER['QUERY_STRING']);
?>
