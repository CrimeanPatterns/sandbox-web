<!-- cache:<?=time()?> -->
<?
if(isset($_SERVER['HTTP_VIA_AW_PROXY']))
	return;
?>
<? if(ArrayVal($_GET, 'Headers') == 'off') { ?>
</body>
<? } else { ?>
<?#End main part of the page?>
	</td>
	<td  class="notPrintable" valign="top" width="36" style="background-image: url(/lib/admin/images/rightBg.jpg); background-repeat: repeat-y; background-position: right;"><img src="/lib/admin/images/topRight.jpg" alt=""></td>
</tr>

<tr style="height: 58px; max-height: 58px;">
	<td bgcolor="#f63636" height="58" width="210" class="notPrintable">&nbsp;</td>
	<td height="58" width="28" class="notPrintable" style="vertical-align: top; background-color: #f73431;"><img src="/lib/admin/images/bottomLeft.gif" alt=""></td>
	<td  height="58" style="background-image: url(/lib/admin/images/bottomBg.jpg); background-repeat: repeat-x; background-position: bottom;">&nbsp;</td>
	<td height="58" width="36" class="notPrintable" style="vertical-align: bottom;"><img src="/lib/admin/images/bottomRight.jpg" alt=""></td>
</tr>
</table>

<?
if(isset($Connection) && $Connection->Tracing && isset($_GET['SQLTrace']))
	$Connection->ShowTraceData();
?>
</body>
<script>

function adjustSize(){
	var bodyHeight;
	if (self.innerWidth)
		bodyHeight = self.innerHeight;
	else
		if (document.documentElement && document.documentElement.clientWidth)
			bodyHeight = document.documentElement.clientHeight;
		else
			if (document.body)
				bodyHeight = document.body.clientHeight;
	if(document.all)
		document.getElementById('firstRow').style.height = (bodyHeight - 78) + 'px';
	else
		document.getElementById('firstRow').style.height = (bodyHeight - 58) + 'px';
}
adjustSize();
window.onresize = adjustSize;

</script>
<?php
if (isset($Interface->onLoadScripts) && count($Interface->onLoadScripts) > 0)
	echo "<script>
	\$(window).load(function() {
		".implode("\n", $Interface->onLoadScripts)."\n
		activateDatepickers('active');
	});
	</script>";
?>
<? } ?>
</html>
