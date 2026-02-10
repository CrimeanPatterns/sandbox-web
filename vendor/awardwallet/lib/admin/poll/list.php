<?

require( "../../../kernel/public.php" );
require( "$sPath/lib/classes/TBaseList.php" );
$sTitle = "Poll and trivia";

require( "../design/header.php" );
require( "fields.php" );

class TPollList extends TBaseList
{
	function Delete( $nID )
	{
		global $Connection;
		$Connection->Execute( "delete from PollOption where PollID = $nID" );
		parent::Delete( $nID );
	}
}

$objList = New TPollList( "Poll", $arFields, "Name"  );
$objList->CanAdd = True;
$objList->ReadOnly = False;
$objList->AllowDeletes = true;
$objList->ShowEditors = true;
$objList->Update();
$objList->Draw();

require( "../design/footer.php" );

?>