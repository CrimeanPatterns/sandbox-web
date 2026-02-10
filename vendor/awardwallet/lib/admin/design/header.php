<?
if(isset($_SERVER['HTTP_VIA_AW_PROXY']))
	return;
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
<?
if(file_exists("$sPath/admin/design/adminStyle.css")){
?>
<link href="/admin/design/adminStyle.css" rel="stylesheet" type="text/css"></link>
<?
}
?>
<script language="JavaScript" src="/lib/scripts.js"></script>
<script language="JavaScript" type="text/javascript">
var smIds = new Array(0<?
$objRS1 = New TQuery("SELECT distinct(ParentID) as ParentID
	FROM adminLeftNav WHERE ParentID IS NOT NULL AND visible = true
	AND ParentID IN (SELECT id from adminLeftNav WHERE visible = true)
	ORDER BY ParentID", $Connection);
while(!$objRS1->EOF){
	print ", " . $objRS1->Fields["ParentID"];
	$objRS1->Next();
}
?>);
</script>
<script type="text/javascript" src="/lib/3dParty/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="/assets/common/vendors/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" language="JavaScript" src="/lib/3dParty/jquery/plugins/ui/jquery-ui.js"></script>
<script language="JavaScript" src="/lib/scripts.js"></script>
<script language="JavaScript" src="/lib/admin/scripts.js"></script>
<script language="JavaScript" src="/lib/design/menu.js"></script>
<?
if (isset($Interface))
	foreach ($Interface->ScriptFiles as $js)
		echo '<script language="JavaScript" src="' . $js . '?v=2"></script>'
?>
</head>
<? if(ArrayVal($_GET, 'Headers') == 'off') { ?>
<body>
<? } else { ?>
<body leftmargin="0" topmargin="0" rightmargin="0" bottommargin="0" OnLoad="InitMenu()" Onclick="HideMenus()">

<table cellspacing="0" cellpadding="0" border="0" width="100%" height="100%" style="height: 100%;">
<tr id="firstRow">
	<td width="210" align="center" valign="top" bgcolor="#f6a436" class="notPrintable">
	<div style="width: 210px; height: 80px; background-color: #f63636;">
	<a href="/lib/admin/" tabindex="-1"><img src="/lib/admin/images/logo.gif" alt="ITlogy Administrative management interface" border="0" style="margin-top: 17px;" tabindex="-1"></a></div>
<? if (isset($Interface)) $Interface->DrawAdminNavigation(); ?>
<div style="position: absolute; bottom: 85px; left: 5px;">
<? if (isset($Interface)) $Interface->DrawAdminLinks(); ?>
</div>
	</td>
	<td valign="top" width="28"  class="notPrintable" height="76" class="notPrintable"><img src="/lib/admin/images/topLeft.gif" alt=""></td>
	<td style="background-image: url(/lib/admin/images/topBg.jpg); background-repeat: repeat-x; background-position: top; padding-top: 20px;" valign="top" onmouseover="HideMenus()">
<?#Begin main part of the page?>
<? } ?>
