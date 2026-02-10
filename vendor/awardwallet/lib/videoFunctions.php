<?

function DrawVideoPreview( $sFile, $sExt, $nWidth, $nHeight )
{
	if($sExt == "mov" || $sExt == "avi" || $sExt == "mp4")
		$nHeight = $nHeight - 53;
				
	switch( strtolower( $sExt ) )
	{
		case "swf":
?>			
<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0"
 WIDTH=<?=$nWidth?> HEIGHT=<?=$nHeight?>>
 <PARAM NAME=movie VALUE="<?=$sFile?>"> <PARAM NAME=quality VALUE=high><EMBED src=" <?=$sFile?>" quality=high WIDTH=<?=$nWidth?> HEIGHT=<?=$nHeight?> TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"></EMBED>
</OBJECT>
<?
			break;
		default:
?>
<p id='video' style='width: <?=$nWidth?>px; height: <?=$nHeight?>px;'>Video</p>
<script type='text/javascript' src='/3dParty/jwplayer/swfobject.js'></script>
<script type='text/javascript'>
	var s1 = new SWFObject('/3dParty/jwplayer/player.swf','player','<?=$nWidth?>','<?=$nHeight?>','9');
	s1.addParam('allowfullscreen','true');
	s1.addParam('allowscriptaccess','always');
	s1.addParam('flashvars','file=<?=$sFile?>');
	s1.write('video');
</script>

<a class="form" href="<?=$sFile?>">download this video</a>
<?
	}
}
?>