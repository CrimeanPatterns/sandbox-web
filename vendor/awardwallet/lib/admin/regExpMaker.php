<?

require("../../kernel/public.php");
if(isset($Config["RussianSite"]))
	require_once("$sPath/lib/classes/TBaseFormRusConstants.php");
elseif(isset($Config["SpanishSite"]))
	require_once("$sPath/lib/classes/TBaseFormSpaConstants.php");
else
	require_once("$sPath/lib/classes/TBaseFormEngConstants.php");
require("$sPath/lib/admin/design/header.php");

echo "<h2>This page will convert html to regexp</h2>";

$objForm = new TBaseForm( array(
	"RegExp" => array(
		"Type" => "string",
		"Required" => True,
		"InputType" => "textarea",
		"HTML" => true,
	),
) );

if( $objForm->IsPost && $objForm->Check() ){
	$s = $objForm->Fields["RegExp"]["Value"];
	$s = preg_replace( "/\//ims", "\/", $s );
	$s = preg_replace( "/\)/ims", "\)", $s );
	$s = preg_replace( "/\(/ims", "\(", $s );
	$s = preg_replace( "/\"/ims", "\\\"", $s );
	$s = preg_replace( "/\*/ims", "\\*", $s );
	$s = preg_replace( "/\-/ims", "\\-", $s );
	$s = preg_replace( "/\,/ims", "\\,", $s );
	$s = preg_replace( "/\./ims", "\\.", $s );
	$s = preg_replace( "/\?/ims", "\\?", $s );
	$s = preg_replace( "/(\s*[\n\r]+\s*)+/ims", "\s*", $s );
	echo "Encoded:<br><textarea style='width: 600px; height: 300px; font-size: 12px;'>".htmlspecialchars( $s )."</textarea>";
}

echo $objForm->HTML();

require("$sPath/lib/admin/design/footer.php");

?>