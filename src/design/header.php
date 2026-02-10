<?
if(!isset($bodyParams))
	$bodyParams = "";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>ITlogy Admin Interface</title>
<link rel="stylesheet" href="/lib/3dParty/jquery/themes/itlogy/jquery-ui.css" type="text/css" media="all" />
<link href="/lib/admin/design/mainStyle.css" rel="stylesheet" type="text/css"/>
<?
if (isset($Interface))
	foreach ($Interface->CssFiles as $css)
		echo '<link href="' . $css . '?v=2" rel="stylesheet" type="text/css"></link>';
?>
<script language="JavaScript" src="/lib/scripts.js"></script>
<script type="text/javascript" src="/lib/3dParty/jquery/jq.js"></script>
<script type="text/javascript" language="JavaScript" src="/lib/3dParty/jquery/plugins/ui/jquery-ui.js"></script>
<script language="JavaScript" src="/lib/admin/scripts.js"></script>
<?
if (isset($Interface))
	foreach ($Interface->ScriptFiles as $js)
		echo '<script language="JavaScript" src="' . $js . '?v=2"></script>'
?>
</head>

<body style="padding: 10px;">

<?#Begin main part of the page?>
