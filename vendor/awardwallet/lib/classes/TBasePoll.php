<?
// object to work with poll
class TBasePoll{
  var $ExistOnPage = False;
  var $Fields;
  var $Type;

	function TBasePoll( $bAutoLoad = True ){
		if( $bAutoLoad){
			$sLocation = addslashes($_SERVER["SCRIPT_NAME"]);
			$sSQL = "select * from Poll where ( Location = '{$sLocation}'
			or Location like '%,{$sLocation}'
			or Location like '{$sLocation},%'
			or Location like '%,{$sLocation},%') and IsOpen = 1";
			if( !isset( $_SESSION["UserID"] ) )
				$sSQL .= " and OnlyUsersView = 0";
			$sSQL .= " order by CreationDate desc";
			$q = new TQuery( $sSQL );
			if( $q->EOF )
				return;
			$this->ExistOnPage = True;
			$this->Load( $q->Fields );
		}
	}

  function Load( $arFields )
  {
    $this->Fields = $arFields;
    if( $arFields["IsTrivia"] == 1 )
      $this->Type = "Trivia";
    else
      $this->Type = "Poll";
  }

function DrawForm( $bWithTitle = True ){
	global $Interface;
	echo "<form method='post' action='/lib/poll/vote.php' style='margin-bottom: 5px; margin-top: 5px;'>";
	echo "<table cellspacing=0 cellpadding=0 border=0>";
	if( $bWithTitle )
		echo "<tr><td colspan=2><span style='font-weight: bold; font-size: 11px; font-family: Arial;'>{$this->Fields["Question"]}</span></td></tr>";
	$q = new TQuery( "select * from PollOption where PollID = {$this->Fields["PollID"]} order by SortIndex" );
	while( !$q->EOF ){
		echo "<tr><td><input type=radio name=PollOptionID value={$q->Fields["PollOptionID"]}></td><td width='100%' style='padding-left: 12px;'><span style='color: " . COLOR_RED . "; font-size: 11px; font-family: Arial;'>{$q->Fields["Name"]}</span></td></tr>";
		$q->Next();
	}
	echo "</table>";
	echo "<br>";
	print $Interface->DrawButton2("Vote", " style='width: 90px;'", 19);
	echo "</form>";
}

	function DrawResults($fillImage = "whitePixel.gif"){
		$qTotal = new TQuery( "select sum( Votes ) as Votes from PollOption where PollID = {$this->Fields["PollID"]}" );
		$nTotalVotes = $qTotal->Fields["Votes"];
		if( $nTotalVotes == 0 )
			$nTotalVotes = 1;
		$qOption = new TQuery( "select * from PollOption where PollID = {$this->Fields["PollID"]} order by SortIndex" );
		print "<table cellspacing=0 cellpadding=5 border=0>";
		while( !$qOption->EOF ){
			$nPercent = round( $qOption->Fields["Votes"] / $nTotalVotes * 100 );
			$selectedWidth = $nPercent * 3;
			$remainingWidth = 300 - $selectedWidth;
			if(($this->Fields["IsTrivia"] == 1) && ($qOption->Fields["PollOptionID"] == $this->Fields["CorrectAnswerID"]))
				echo "<tr><td height='22' nowrap style='font-size: 10px;'><b>{$qOption->Fields["Name"]}</b></td><td  style='font-size: 10px;'><img src=/lib/images/yellowPixel.gif height=10 width=" . $selectedWidth . "><img src=/lib/images/{$fillImage} height=10 width=" . $remainingWidth . "> <b>$nPercent% ({$qOption->Fields["Votes"]}) - Correct Answer</b></td></tr>";
			else
				echo "<tr><td height='22' nowrap  style='font-size: 10px;'>{$qOption->Fields["Name"]}</td><td nowrap  style='font-size: 10px;'><img src=/lib/images/yellowPixel.gif height=10 width=" . $selectedWidth . "><img src=/lib/images/{$fillImage} height=10 width=" . $remainingWidth . "> $nPercent% ({$qOption->Fields["Votes"]})</td></tr>";
			$qOption->Next();
		}
		print "</table>";
/*		if( $this->Fields["IsTrivia"] == 1 ){
			// trivia
			$qOption = new TQuery( "select po.* from PollOption po, Poll p where p.PollID = {$this->Fields["PollID"]} and p.CorrectAnswerID = po.PollOptionID" );
			$nPercent = round( $qOption->Fields["Votes"] / $nTotalVotes * 100 );
			#      echo "<tr><td colspan=2>Correct Answer: <span style='font-weight: bold;'>\"{$qOption->Fields["Name"]}\"</span>; Correct Guesses: <span style='font-weight: bold;'>$nPercent%</span></td></tr>";
		}
*/
	}

}

?>