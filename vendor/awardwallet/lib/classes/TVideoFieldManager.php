<?php

// -----------------------------------------------------------------------
// Video Field manager class.
//		Contains class, to handle youtube video
//		saves only video cpde
// Author: Vladimir Silantyev, ITlogy LLC, vsilantyev@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------

require_once( __DIR__ . "/../imageFunctions.php" );

class TVideoFieldManager extends TAbstractFieldManager {
	
	function TVideoFieldManager()
	{
	}
	
	// get field html
	function InputHTML($sFieldName = null, $arField = null){
		$s = "<input type=\"hidden\" name=\"{$this->FieldName}\" value=\"{$this->Field["Value"]}\">";
		$s .= "<div id=YouTubeEmbed></div>";
		$s .= "To add a video you need to upload this video to YouTube.com first. If you already have an account on YouTube and your video has already been uploaded please provide your YouTube login name here, if you do not have a YouTube account or do not have the video for this ad under your YouTube account you first need to create a new YouTube account and upload your video there. To go to YouTube follow this link:<br
<br>
<a href=\"http://www.youtube.com/\" target=\"_blank\">http://www.youtube.com</a> (will open in a new window)<br>
<br>
<div id=\"YouTubeVideoList\"></div>
Please provide your YouTube account name:<br>
<input type=text name=\"YouTubeAccount\" id=\"YouTubeAccount\"> <input type=\"button\" class=\"button\" onclick=\"findVideo(); return false;\" value=\"Submit\">";
		$s .= "

<script>

function findVideo(){
	var account = trim( document.getElementById('YouTubeAccount').value );
	if( account == '' ){
		alert('YouTube account required');
		document.getElementById('YouTubeAccount').focus();
		return false;
	}
	document.getElementById('YouTubeVideoList').innerHTML = '<span style=\"font-weight: bold;\"><img src=\"/lib/images/circle-ball-dark-antialiased.gif\"> Contacting youtube.com, please wait..</span><br>';
	cp.call('/lib/youtube/ajax.php', 'GetVideoList', YouTubeGetVideoListResults, account );
	return true;
}

function YouTubeGetVideoListResults( result ){
	if( result.data.Error != '' ){
		document.getElementById('YouTubeVideoList').innerHTML = '<span class=formError>'+result.data.Error+'</span><br>';
		return false;
	}
	document.getElementById('YouTubeVideoList').innerHTML = result.data.HTML + '<br>';
	return true;
}

function YouTubeSetVideo( EmbedURL ){
	document.getElementById('YouTubeEmbed').innerHTML = '<object width=\"425\" height=\"355\"><param name=\"movie\" value=\"'+EmbedURL+'\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"'+EmbedURL+'\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"425\" height=\"355\"></embed></object><br><a href=# onclick=\"YouTubeRemoveVideo()\">Remove video</a><br>';
	document.forms['editor_form']['{$this->FieldName}'].value = EmbedURL;
}

function YouTubeRemoveVideo(){
	document.getElementById('YouTubeEmbed').innerHTML = '';
	var form = document.forms['editor_form'];
	form['{$this->FieldName}'].value = '';
	for( i=0; i < form.length; i++ ) 
	{
		var element=form.elements[i];
  		if( ( element.type.toLowerCase() == \"radio\" ) && ( element.name.indexOf('video') == 0 ) )
  			if( element.checked )
    			element.checked = false;
	}
}

</script>		
		";
		if( $this->Field["Value"] != "" ){
			$s .= "<script>YouTubeSetVideo( '{$this->Field["Value"]}' )</script>";
		}
		return $s;
	}
	
	
}

?>
