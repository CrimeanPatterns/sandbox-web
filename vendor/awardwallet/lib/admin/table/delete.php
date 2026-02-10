<?
require( "../../../kernel/public.php" );
require( "$sPath/kernel/TSchemaManager.php" );

if(($_SERVER['REQUEST_METHOD'] != 'POST') || !isset($_POST['Table']))
	die("Invalid request");

$objSchemaManager = new TSchemaManager();
$arIDs = explode( ",", $_POST["ID"] );
$arRows = array();
$bDelete = ( ArrayVal( $_POST, "Action" ) == "Delete" && isValidFormToken() );

$returnFields = ArrayVal($_POST, "ReturnFields");

if (isset($arIDs[0]) && strpos($arIDs[0], '.') > 0) {
    $arIDs = array_walk(
        $arIDs,
        function($elem, $elemKey) use (& $arRows, $objSchemaManager, $bDelete) {
            if (strpos($elem, '.') > 0 && count($elemParts = explode('.' ,$elem)) == 2) {
                /**
                 * [0 => 'Usr', '1' => '100500']
                 */
                $table = trim($elemParts[0]);
                $nID = intval($elemParts[1]);
                if (!empty($nID) && '' !== $table) {
                    $arRows = array_merge($arRows, $objSchemaManager->DeleteRow($table, $nID, $bDelete));
                }
            } else {
                die("Invalid element {$elem}");
            }
        });
} else {
    foreach ( $arIDs as $nID ) {
        if (!empty($nID)) {
            $arRows = array_merge( $arRows, $objSchemaManager->DeleteRow( $_POST["Table"], $nID, $bDelete ) );
        }
    }
}
if( $bDelete || !sizeof($arRows) ){
	ScriptRedirect($_POST["BackTo"]);
}
else{
	require( "$sPath/lib/admin/design/header.php" );
	echo "<form method=post>";
	echo "<input type=hidden name=Action value=Delete>";
	echo "<input type=hidden name=Table value=\"". htmlspecialchars( $_POST["Table"] ) . "\">\n";
	echo "<input type=hidden name=BackTo value=\"". htmlspecialchars( $_POST["BackTo"] ) . "\">\n";
    echo "<input type='hidden' name='FormToken' value='" . GetFormToken() . "'>\n";
	echo "<input type=hidden name=ID value=\"". htmlspecialchars( $_POST["ID"] ) . "\">\n";
	echo "<input type=hidden name=ReturnFields value=\"". htmlspecialchars( $returnFields ) . "\">\n";
	echo "<h2>You are about to delete following:</h2><br>";
	foreach ( $arRows as $arRow ) {
		echo "<b>{$arRow["Table"]} #{$arRow["ID"]}</b><br>";
		foreach ( $arRow["Files"] as $arFile )
			if( $arFile["Exist"] )
				echo "&nbsp;&nbsp;&nbsp;file {$arFile["File"]}<br>";
	}
	echo "<br><input class=button type=submit name=s1 value=Delete> ";
	echo "<input type=button class=button  name=c1 value=Cancel onclick=\"document.location.href='{$_POST["BackTo"]}'; return false;\">";
	echo "</form>";
	require( "$sPath/lib/admin/design/footer.php" );
}

?>