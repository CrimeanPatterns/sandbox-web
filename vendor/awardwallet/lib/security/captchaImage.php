<?

$bDisableCookieAuthorization = True;
require("../../kernel/public.php");

define( 'WIDTH', 120 );
define( 'HEIGHT', 40 );
define( 'CHARS', 4 );

$arUsedColors = array();
$sCode = RandomStr( ord( '0' ), ord( '9' ), CHARS );
$rImage = imagecreatetruecolor( WIDTH, HEIGHT );
imagefilledrectangle( $rImage, 0, 0, WIDTH, HEIGHT, imagecolorallocate( $rImage, 255, 255, 255 ) );
$rTextColor = imagecolorallocate( $rImage, 0, 0, 0 );
$sFont = 'arial.ttf';
if( IsUnix() )
	$sFont = getSymfonyContainer()->getParameter("kernel.project_dir") . '/data/fonts/' . $sFont;
var_dump( $sFont ); 
for( $n = 0; $n <= strlen( $sCode ); $n++ )
{
	$nFontSize = rand( HEIGHT / 2, HEIGHT - 10 );
	imagettftext($rImage, $nFontSize, rand( 0, 30 ) - 15, $n * WIDTH / CHARS + rand( 0, WIDTH / CHARS / 4 ), HEIGHT - rand( 0, HEIGHT - $nFontSize - 10 ) - 5, $rTextColor, $sFont, substr( $sCode, $n , 1 ) ); 
}
/*for( $n = 0; $n < 4; $n++ )
	imageellipse( $rImage, rand( 0, WIDTH ), rand( 0, HEIGHT ), rand( WIDTH / 4, WIDTH * 2 ), rand( HEIGHT /4, HEIGHT * 2 ), RandomColor( 10 ) );*/

ob_end_clean();
header( 'Content-Type: image/png' );
header( "pragma: no-cache" );
header( "cache-control: private" );
imagepng($rImage); 

imagedestroy($rImage); 
$_SESSION['CaptchaCode'] = $sCode;

function RandomColor( $nOffset = 10 )
{
	global $arUsedColors, $rImage;
	
	do 
	{
		$bUsed = False;
		$arColor = array( rand( 0, 255 ), rand( 0, 255 ), rand( 0, 255 ) );
		$nGrayColor =  $arColor[0] + $arColor[1] + $arColor[2];
		for( $n = 0; $n < count( $arUsedColors ); $n++ )
			if( abs( $arUsedColors[$n] - $nGrayColor ) < $nOffset )
				$bUsed = True;
	}
	while( $bUsed );
	$arUsedColors[] = $nGrayColor;
	return imagecolorallocate( $rImage, $arColor[0], $arColor[1], $arColor[2] );
}

?>
