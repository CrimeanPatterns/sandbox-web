<?php

// -----------------------------------------------------------------------
// image related functions
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

// convert text to html. something like <pre>
// -----------------------------------------------------------------------
function TextToHTML( $s )
{
	$s = preg_replace( "/\n/", "<br>\n", $s );
	$s = preg_replace_callback( "/^ /m", "TextToHTMLCallbackIndent", $s );
	return $s;
}

// convert indent spaces to &nbsp;
function TextToHTMLCallbackIndent( $arMatches )
{
	return str_repeat( "&nbsp;", strlen( $arMatches[0] ) );
}

?>