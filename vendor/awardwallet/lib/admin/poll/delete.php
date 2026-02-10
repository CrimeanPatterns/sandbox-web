<?

require( "../../kernel/public.php" );

$nID = intval( $QS["ID"] );
$sName = Lookup( "Poll", "PollID", "Name", $nID, True );
$sTitle = "Delete poll/trivia '$sName'";

require( "../design/header.php" );

if( isset( $QS["Delete"] ) )
{
  $Connection->Execute( "update Poll set CorrectAnswerID = null where CorrectAnswerID in ( select PollOptionID from PollOption where PollID = $nID )" );
  $Connection->Execute( "delete from PollOption where PollID = $nID" );
  $Connection->Execute( "delete from Poll where PollID = $nID" );
  Redirect( "list.php" );
}
else
{
?>
<p align=center><img src=../images/question.gif width=32 height=32 border=0>
Delete poll/trivia '<?=$sName?>'?
</p> 
<p align=center><a style="{background-color:#d0ffd0; color:#000000; text-decoration: none; }" href="<?=$_SERVER["SCRIPT_NAME"]?>?<?=$_SERVER["QUERY_STRING"]?>&Delete=1&r=<?=rand( 0, 999999 )?>">&nbsp;&nbsp;<b>OK</b>&nbsp;&nbsp;</a><img src=../images/e.gif width=100 height=1><a  style="{background-color:#ffd0d0; color:#000000; text-decoration: none;}" href="#" onclick="javascript:history.go(-1); return false;">&nbsp;&nbsp;<b>Cancel</b>&nbsp;&nbsp;</a></p>
<?
}

require( "../design/footer.php" );

?>