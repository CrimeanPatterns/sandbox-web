<?
// -----------------------------------------------------------------------
// product class for displaying product details in many different formats
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com
// -----------------------------------------------------------------------

class TBaseDraw{
	var $drawID;
	var $drawSize;
	var $rounds;
	var $doubles = false;
#	var $players = array();
	var $buttonCaption = "Save Draw Reults";
	var $winnersPlace;
	var $drawScope;
	var $visible = true;
	var $mainDrawID;
	var $modificationTime;
	var $maxModificationTime;
	var $subGroupCount = 0;
	var $subGroupSize = false;
	var $totalPlayers = 0;
	var $subGroupCaption = "Sub Group";
	var $playerCaption = "Player";
	var $pointsCaption = "Points";
	var $placeCaption = "Place";
	var $numberCaption = "#";
	var $drawNotReadyMsg = "You did not set the draw size. Please go back and set the draw size before setting up draw results";
	var $sMakeABet = "Make a bet!";
	var $drawName = null;

	function TBaseDraw($createDrawID=false){
		global $QS, $Interface;
		if($createDrawID){
			$this->drawID = $createDrawID;
		}
		elseif(isset($QS["DrawID"]))
			$this->drawID = intval($QS["DrawID"]);
		else
			$Interface->DiePage("Invalid URL. DrawID is not set");
		$check = New TQuery( "select Doubles from Draw WHERE DrawID = " . $this->drawID );
			if(!$check->EOF){
				if($check->Fields["Doubles"] == true){
					$this->doubles = true;
				}
			}

		$objRS = New TQuery("select Size, WinnersPlace, DrawScope, Visible, MainDrawID, ModificationTime, SubGroupCount, SubGroupSize, SubName from Draw WHERE DrawID = ".$this->drawID);
		if(!$objRS->EOF){
			if($objRS->Fields["Size"] != "")
				$this->drawSize = $objRS->Fields["Size"];
			else
				$Interface->DiePage($this->drawNotReadyMsg);
			if($objRS->Fields["WinnersPlace"] != "")
				$this->winnersPlace = $objRS->Fields["WinnersPlace"];
			else
				$Interface->DiePage("The draw is misconfigured. Please notify " . SITE_NAME . " administrator." );
			if($objRS->Fields["DrawScope"] != "")
				$this->drawScope = $objRS->Fields["DrawScope"];
			if($objRS->Fields["Visible"] != "")
				$this->visible = $objRS->Fields["Visible"];
			if($objRS->Fields["MainDrawID"] != "")
				$this->mainDrawID = $objRS->Fields["MainDrawID"];
			else
				$this->mainDrawID = $this->drawID;
			if($objRS->Fields["ModificationTime"] != "")
				$this->modificationTime = $objRS->Fields["ModificationTime"];
			if($objRS->Fields["SubGroupCount"] != "")
				$this->subGroupCount = $objRS->Fields["SubGroupCount"];
			if($objRS->Fields["SubGroupSize"] != "")
				$this->subGroupSize = $objRS->Fields["SubGroupSize"];
			if ($objRS->Fields["SubName"] != "")
				$this->drawName = $objRS->Fields["SubName"];
			$modTimeRS = New TQuery("select MAX(ModificationTime) as ModificationTime from Draw WHERE DrawID = ".$this->drawID." OR MainDrawID = ".$this->drawID);
			if(!$modTimeRS->EOF)
				if($modTimeRS->Fields["ModificationTime"] != "")
					$this->maxModificationTime = $modTimeRS->Fields["ModificationTime"];
		}
		else
			$Interface->DiePage("Error occured. You proably have invalid URL");
		$numberOfPlayersRS = New TQuery("select COUNT(TournamentApplicationID) as NumberOfPlayers FROM TournamentApplication where Approved = TRUE AND (DrawID = ".$this->drawID." OR DrawID = ".$this->mainDrawID.") AND UserID != " . BYE_ID);
		if(!$numberOfPlayersRS->EOF)
			$this->totalPlayers = $numberOfPlayersRS->Fields["NumberOfPlayers"];
		switch ($this->drawSize){
		case 2:
		   $this->rounds = 2;
		   break;
		case 4:
		   $this->rounds = 3;
		   break;
		case 8:
		   $this->rounds = 4;
		   break;
		case 16:
		   $this->rounds = 5;
		   break;
		case 32:
		   $this->rounds = 6;
		   break;
		case 64:
		   $this->rounds = 7;
		   break;
		case 128:
		   $this->rounds = 8;
		   break;
		}
#		print $_POST["drawID"] . " - " . $this->drawID . "<br>";
		if(count($_POST)>0 && $_POST["drawID"] == $this->drawID){
			$this->processForm();
		}
	}

	function ShowForm($editable=true){
		global $Interface;
		$rowSpanMarks = array();
		$lastPosition = array();
		$position = 0;
		for($i=1; $i<=$this->drawSize*2-1; $i++)
			for($round=1; $round<=$this->rounds+1; $round++){
				$rowSpanMarks[$i][$round] = false;
				$lastPosition[$round] = 0;
			}
		if($editable){
			print "<form method='post' style='margin-top: 0px; margin-bottom: 0px;' name='frmDrawResults_".$this->drawID."'>";
			print "<input type='Hidden' value='".$this->drawID."' name='drawID'>";
		}
		$this->printLastUpdate();
		if (isset($this->drawName))
			echo "<h4>".$this->drawName."</h4>";
#Begin showing subgroups
		if($this->subGroupCount > 0){
			$subgroupSize = ceil($this->totalPlayers / $this->subGroupCount);
			if($this->subGroupSize)
				$subgroupSize = $this->subGroupSize;
			print "<br>";
			for($groupNum=1;$groupNum<=$this->subGroupCount;$groupNum++){
#Begin determining custom header and footer
				$thisGroupFooter = "";
				$thisGroupHeader = "";
				if ($this->subGroupCount > 1)
					$thisGroupHeader = "<div style=\"font-weight: bold;\">".$this->subGroupCaption." " . $groupNum ."</div>";
				$subGroupDetailsRS = New TQuery("select * from SubGroup WHERE DrawID = (SELECT DrawID FROM Draw WHERE WinnersPlace = 1 AND TournamentCategoryLinkID = (
SELECT TournamentCategoryLinkID FROM Draw WHERE DrawID = ".$this->drawID . ")) AND SubGroupNumber = ".$groupNum);
					if(!$subGroupDetailsRS->EOF && trim($subGroupDetailsRS->Fields["SubGroupHeader"]) != "" && trim($subGroupDetailsRS->Fields["SubGroupHeader"]) != "<br />")
						$thisGroupHeader = "<div>".$subGroupDetailsRS->Fields["SubGroupHeader"]."</div>";
					if(!$subGroupDetailsRS->EOF && trim($subGroupDetailsRS->Fields["SubGroupFooter"]) != "" && trim($subGroupDetailsRS->Fields["SubGroupFooter"]) != "<br />")
						$thisGroupFooter = "<div>".$subGroupDetailsRS->Fields["SubGroupFooter"]."</div>";
#End determining custom header and footer
				print "<div align=\"left\" style=\"padding-left: 10px;\">$thisGroupHeader<table cellspacing=\"0\" cellpadding=\"5\" border=\"0\" class=\"detailsTable\">";
				if(!$editable){
					$subgroupSizeRS = New TQuery("SELECT MAX(FirstGroupPosition) AS subGroupSize FROM TournamentMatch WHERE DrawID = {$this->drawID} AND GroupNumber = $groupNum
UNION
SELECT MAX(SecondGroupPosition) FROM TournamentMatch WHERE DrawID = {$this->drawID} AND GroupNumber = $groupNum
Order BY subGroupSize DESC");
					if(!$subgroupSizeRS->EOF)
						$subgroupSize = $subgroupSizeRS->Fields["subGroupSize"];
				}
				for($firstGroupPosition=0;$firstGroupPosition<=$subgroupSize;$firstGroupPosition++){
					if($this->getGroupName($groupNum, $firstGroupPosition) || $editable || $firstGroupPosition==0){
						if($firstGroupPosition==0)
							print "<tr bgcolor=\"#e7e7e7\">";
						else
							print "<tr>";
						for($secondGroupPosition=-1;$secondGroupPosition<=$subgroupSize+2;$secondGroupPosition++){
							if($secondGroupPosition == $firstGroupPosition && $firstGroupPosition != 0)
								print "<td bgcolor='#000000'>&nbsp;";
							else
#								print "<td>";
							if($firstGroupPosition==0 && $secondGroupPosition==-1)
								print "<td align='center'>" . $this->numberCaption;
							elseif($firstGroupPosition==0 && $secondGroupPosition==0)
								print "<td align='center'>" . $this->playerCaption;
							elseif($firstGroupPosition==0 && $secondGroupPosition==$subgroupSize+1)
								print "<td align='center'>" . $this->pointsCaption;
							elseif($firstGroupPosition==0 && $secondGroupPosition==$subgroupSize+2)
								print "<td align='center'>" . $this->placeCaption;
							elseif($firstGroupPosition==0)
								print "<td align='center' width='70' style='font-weight: bold;'>" . $secondGroupPosition;
							elseif($secondGroupPosition==-1)
								print "<td align='center' style='font-weight: bold;'>" . $firstGroupPosition;
							elseif($secondGroupPosition==0){
#begin showing names
								if($editable){
									$nameToShow = $this->getGroupNameSelect($groupNum, $firstGroupPosition);
									$playerToShow = $this->getGroupName($groupNum, $firstGroupPosition);
								}
								else{
									$nameToShow = "&nbsp;";
									$playerToShow = $this->getGroupName($groupNum, $firstGroupPosition);
									if($playerToShow)
										$nameToShow = $this->getDisplayName($playerToShow, 1, true, false);
								}
								print "<td style='min-width: 280px;'><span class='drawNames'>".$nameToShow."</span>";
#end showing names
							}
#begin showing points
							elseif($secondGroupPosition==$subgroupSize+1){
								if(isset($playerToShow) && $playerToShow != ""){
									$pointsRS = New TQuery("select COUNT(WinnerID) AS Points from TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND WinnerID = $playerToShow;");
									if(!$pointsRS->EOF)
										$points = $pointsRS->Fields["Points"];
									else
										$points = "0";
								}
								else
									$points = "n/a";
								print "<td align='center' style='min-width: 30px;'>$points";
							}
#end showing points
#begin showing place
							elseif($secondGroupPosition==$subgroupSize+2){
/*
								$pointsRS2 = New TQuery("select count(TournamentMatchID) AS Points from TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND (FirstGroupPosition = $firstGroupPosition OR SecondGroupPosition = $firstGroupPosition)");
# Commented this out because /tournaments/drawResults.php?DrawID=1649 - sub-group 2, Makarova's place was not showing
#								if($firstGroupPosition == $subgroupSize)
#									$pointsRS2->Fields["Points"] = $pointsRS2->Fields["Points"] - 1;
								$placeRS = New TQuery("SELECT ta.SubGroupPlaceAcheived FROM TournamentMatch tm
INNER JOIN TournamentApplication ta ON ta.TournamentApplicationID = tm.FirstPlayerID
WHERE tm.DrawID = ".$this->drawID." AND tm.GroupNumber = ".$groupNum." AND tm.FirstGroupPosition = ".$firstGroupPosition."
LIMIT 1;");
								$position = "&nbsp;";
# the second part of this OR condition is intended to show places inside admin interface only, for the subgroups that will have fewer players than the size of the subgroup.
								if((!$placeRS->EOF && $pointsRS2->Fields["Points"] >= ($subgroupSize-1)) || (!$placeRS->EOF && $Interface->IsAdminInteface())){
									$position = $placeRS->Fields["SubGroupPlaceAcheived"];
								}
*/
								$position = "&nbsp;";
								if(!$subGroupDetailsRS->EOF && trim($subGroupDetailsRS->Fields["Player".$firstGroupPosition."Place"]) != "")
									$position = $subGroupDetailsRS->Fields["Player".$firstGroupPosition."Place"];
								if(isset($playerToShow) && $playerToShow != "" && $position == "&nbsp;"){
									$placeRS = New TQuery("SELECT SubGroupPlaceAcheived FROM TournamentApplication WHERE TournamentApplicationID = $playerToShow LIMIT 1;");
	# If the place was overriden via tournamnetApplication manually - use that value, otherwize calculate it on the fly...
									if((!$placeRS->EOF && $placeRS->Fields["SubGroupPlaceAcheived"] != "")){
										$position = $placeRS->Fields["SubGroupPlaceAcheived"];
									}
									else{
										$checkCompletnesRS = new TQuery("SELECT COUNT(*) UnfinishedMatches FROM TournamentMatch WHERE WinnerID IS NULL AND DrawID = {$this->drawID} AND GroupNumber = $groupNum;");
										if($checkCompletnesRS->Fields["UnfinishedMatches"] > 0){
											$position = "&nbsp;";
										}
										else{
											$placesAr = SQLToSimpleArray("select COUNT(WinnerID) AS Points, WinnerID from TournamentMatch
		WHERE DrawID = {$this->drawID} AND GroupNumber = $groupNum GROUP BY WinnerID ORDER BY Points DESC;", "WinnerID");
											$position = array_keys($placesAr, $playerToShow);
											if(count($position) > 0)
												$position = $position[0] + 1;
											else
												$position = count($placesAr) + 1;

										}
									}
								}
								print "<td align='center' style='font-size: 14px; font-weight: bold; min-width: 30px;'>";
								if ($editable) {
									print "<select name=\"groupplace_{$groupNum}_{$firstGroupPosition}\">";
									print "<option value=\"auto\"".(!is_numeric($position) ? " selected" : "").">Auto</option>";
									for ($idx = 1; $idx <= $subgroupSize; $idx++)
										print "<option value=\"{$idx}\"". ($position == $idx ? " selected" : "") . ">{$idx}</option>";
									print "</select>";
								}
								else
									print "$position";

							}
#end showing place
							elseif($secondGroupPosition != $firstGroupPosition){
#begin showing score
								if($editable) {
									$groupScoreToShow = "<span style='font-size: 10px; color: #666666;'>Score: <input type='Text' value='".$this->getGroupValue($groupNum, $firstGroupPosition, $secondGroupPosition, "score")."' name='groupscore_{$groupNum}_{$firstGroupPosition}_{$secondGroupPosition}' style='width: 90px;' class='drawInputTxt'>";
									$groupScoreToShow .= "<br>
									Time: <input type='Text' value='".$this->getGroupValue($groupNum, $firstGroupPosition, $secondGroupPosition, "time")."' name='grouptime_{$groupNum}_{$firstGroupPosition}_{$secondGroupPosition}' style='width: 90px; margin-top: 1px; margin-left: 2px;' class='drawInputTxt'>";
									$groupScoreToShow .= "<br>Win: <input type='checkbox' name='groupwin_{$groupNum}_{$firstGroupPosition}_{$secondGroupPosition}' value='1'" . ($this->getGroupValue($groupNum, $firstGroupPosition, $secondGroupPosition, "win") ? " checked='checked'" : "") . "></span>";
								}
								else{
#begin determinig if bet is set
									$objBet = New TQuery("SELECT TournamentMatchID FROM TournamentMatch WHERE
(BetWin1 IS NOT NULL OR BetWin2 IS NOT NULL OR Bet20 IS NOT NULL OR
Bet21 IS NOT NULL OR Bet12 IS NOT NULL OR Bet02 IS NOT NULL OR
BetHandicap1 IS NOT NULL OR BetHandicapOdds1 IS NOT NULL OR BetHandicap2 IS NOT NULL OR
BetHandicapOdds2 IS NOT NULL OR BetTotal IS NOT NULL OR BetTotalLessOdds IS NOT NULL OR
BetTotalMoreOdds IS NOT NULL) AND BetsOn = 1 AND DrawID = ".$this->drawID." AND FirstGroupPosition = $firstGroupPosition AND SecondGroupPosition = $secondGroupPosition AND GroupNumber = $groupNum AND MatchDate > ADDTIME(NOW(), '".TIME_DIFF_WITH_PERM.":00:00')");
									$betToShow = "";
									if(!$objBet->EOF){
										$betToShowAr[$groupNum][$firstGroupPosition][$secondGroupPosition] = "<a href='/tournaments/makeBet.php?MatchID=".$objBet->Fields["TournamentMatchID"]."&ID=0' style='font-style: italic; font-size: 10px;'>".$this->sMakeABet."</a>";
										$betToShow = $betToShowAr[$groupNum][$firstGroupPosition][$secondGroupPosition];
									}
									elseif(isset($betToShowAr[$groupNum][$secondGroupPosition][$firstGroupPosition]))
										$betToShow = $betToShowAr[$groupNum][$secondGroupPosition][$firstGroupPosition];
#end determinig if bet is set
									$groupScoreToShow = "<div class='drawDetails' style='color: #3b3a3a; " . ($this->isWin($groupNum, $firstGroupPosition, $secondGroupPosition) ? "font-weight: bold;" : "") . "'>$betToShow" . $this->getGroupValue($groupNum, $firstGroupPosition, $secondGroupPosition, "score") .  PIXEL . "</div><div style='color: #979696; font-size: 10px;'>".$this->getGroupValue($groupNum, $firstGroupPosition, $secondGroupPosition, "time")."</div>";
								}
								print "<td align='center' style='min-width: 100px;'>" . $groupScoreToShow;
#end showing score
							}
							print "</td>";
						}
						print "</tr>";
					}//showing a row only if there is a player
				}
				print "</table>$thisGroupFooter</div><br>";
			}
		}
#End showing subgroups
#begin if the draw is visible and not editable do not show it
		if($this->visible){
			print "<table cellspacing=0 cellpadding=2 border=0 bordercolor='#c0c0c0'>";
			for($i=1; $i<=$this->drawSize*2-1; $i++){
	#$position = ($i+1)/2;
				print "<tr>";
				for($round=1; $round<=$this->rounds+1; $round++){
					$rowSpan = pow(2, $round) - 2;
					$beginRowSpan = pow(2, $round-1) + 2;
	#begin working with first round only...
					if($round==1){
						if($i%2==1){
							$borderClass = "bBottom";
							if($i%4==3)
								$borderClass = "bBottomRight";
		#begin adding names of the first round
							$position = $this->calcPosition($i, $round, $lastPosition);
							if($editable)
								$nameToShow = "<span style='color: #9b9b9b; font-size: 9px;'>{$position}.</span>" . $this->getFirstRowNameSelect($position);
							else{
								$nameToShow = "&nbsp;";
								$playerToShow = $this->getName($round, $position);
								if($playerToShow!="")
									$nameToShow = $this->getDisplayName($playerToShow, $position);
								else
									$nameToShow = "<div align='center'>".spacer(90)."&nbsp;</div>";
							}
							print "<td nowrap class='$borderClass'><span class='drawNames'>$nameToShow</span></td>";
		#end adding names of the first round
						}
						elseif($i%4==2){
		#begin adding match times of the first round
							if($editable)
								$timeToShow = "<input type='Text' value='".$this->getValue($round, $position, "time")."' name='time_1_$position' style='width: 90px;' class='drawInputTxt'>";
							else
								$timeToShow = "<span class='drawDetails'>" . $this->getValue($round, $position, "time") . PIXEL . "</span>";
							print "<td nowrap class='bRight' align='right'>".$timeToShow."</td>";
		#end adding match times of the first round
						}
						else{
							print "<td>".spacer(90)."</td>";
						}
					}
	#end working with first round only...
	#begin creating draw for every round other than the first one
					elseif($i%pow(2, $round)==pow(2, ($round-1))){
						$position = $this->calcPosition($i, $round, $lastPosition);
						$borderClass = " class='bBottom'";
						if($i%pow(2, $round+1)==pow(2, $round+1)-pow(2, $round-1) && $round!=$this->rounds)
							$borderClass = " class='bBottomRight'";
		#begin adding names
						if($editable)
							$nameToShow = $this->getNameSelect($round, $position);
						else{
							$nameToShow = "&nbsp;";
							$playerToShow = $this->getName($round, $position);
							if($playerToShow!="")
								$nameToShow = $this->getDisplayName($playerToShow, $position, false);
		#begin determinig if bet is set
							else{
								$objBet = New TQuery("SELECT TournamentMatchID FROM TournamentMatch WHERE
(BetWin1 IS NOT NULL OR BetWin2 IS NOT NULL OR Bet20 IS NOT NULL OR
Bet21 IS NOT NULL OR Bet12 IS NOT NULL OR Bet02 IS NOT NULL OR
BetHandicap1 IS NOT NULL OR BetHandicapOdds1 IS NOT NULL OR BetHandicap2 IS NOT NULL OR
BetHandicapOdds2 IS NOT NULL OR BetTotal IS NOT NULL OR BetTotalLessOdds IS NOT NULL OR
BetTotalMoreOdds IS NOT NULL) AND BetsOn = 1 AND DrawID = ".$this->drawID." AND Round = ".($round-1)." AND (FirstPosition = ".($position*2)." OR FirstPosition = " . ($position*2-1).") AND MatchDate > ADDTIME(NOW(), '".TIME_DIFF_WITH_PERM.":00:00')");
								if(!$objBet->EOF)
									$nameToShow = "<a href='/tournaments/makeBet.php?MatchID=".$objBet->Fields["TournamentMatchID"]."&ID=0' style='font-style: italic; font-size: 10px;'>".$this->sMakeABet."</a>";
							}
		#end determinig if bet is set
						}
						print "<td nowrap{$borderClass}><span class='drawNames'>".$nameToShow."</span></td>";
		#end adding names
					}
					elseif($i%pow(2, $round)==pow(2, ($round-1))+1){
						$borderClass = "";
						if($i%pow(2, $round+1)==pow(2, $round-1)+1 && $round!=$this->rounds)
							$borderClass = " class='bRight'";
		#begin adding score
						print "<td nowrap align='center'{$borderClass}>";
						if($editable)
							$scoreToShow = "<input type='Text' value='".$this->getValue($round, $lastPosition[$round], "score")."' name='score_{$round}_{$lastPosition[$round]}' style='width: 90px;' class='drawInputTxt'>";
						else
							$scoreToShow = "<span class='drawDetails'>" . $this->getValue($round, $lastPosition[$round], "score") .  PIXEL . "</span>";
						print $scoreToShow;
						print "</td>";
		#end adding score
					}
					elseif(($i+(pow(2, ($round+1))-$beginRowSpan))%pow(2, ($round+1))==0){
						$rowSpanMarks[$i][$round] = true;
						$borderClass = "";
						if($round!=$this->rounds){
							$borderClass = " class='bRight'";
						}
		#begin adding match times
						print "<td nowrap align='right' rowspan='$rowSpan'$borderClass>";
						if($round!=$this->rounds){
							if($editable)
								$timeToShow = "<input type='Text' value='".$this->getValue($round, $lastPosition[$round], "time")."' name='time_{$round}_{$lastPosition[$round]}' style='width: 90px;' class='drawInputTxt'>";
							else
								$timeToShow = "<span class='drawDetails'>" . $this->getValue($round, $lastPosition[$round], "time") . PIXEL . "</span>";
							print $timeToShow;
						}
						print"</td>";
#end adding match times
					}
#begin the very last row containing the winners place number
					elseif($round==$this->rounds+1){
						if($i==1){
							print "<td rowspan='".($this->drawSize*2-1)."' style='padding-top: 18px;'><b>".$this->winnersPlace."</b></td>";
						}
					}
#end the very last row containing the winners place number
					else{
						$bRemoveCell = false;
						for($k=$i; ($k>$i-$rowSpan && $k>0); $k--)
							if($rowSpanMarks[$k][$round])
								$bRemoveCell = true;
						if(!$bRemoveCell)
							print "<td>".spacer(90)."</td>";
					}
#end creating draw for every round other than the first one
				}
				print "</tr>";
			}
			print "</table><br>";
		}
#end if the draw is visible and not editable do not show it
		if($editable){
			print "<input type='Submit' value='".htmlspecialchars($this->buttonCaption)."' name='saveResults' class='button' onclick='this.disabled=true; document.frmDrawResults_".$this->drawID.".submit();'>";
			print "</form>";
		}
	}

	function printLastUpdate(){
		global $Connection, $QS;
		if(isset($this->maxModificationTime) && !isset($QS["print"]) && $this->winnersPlace == 1){
			$sFormat = "M d, Y (H:i:s)";
			$d = $Connection->SQLToDateTime( $this->maxModificationTime );
			if( $d > 0 )
				$this->maxModificationTime = date( $sFormat, $d );
			else
				$this->maxModificationTime = "";
			print "<span style='font-size: 10px;'>Last update: " . $this->maxModificationTime . " GMT -08:00</span><br>";
		}
	}

	function calcPosition($i, $round, &$lastPosition){
		$pos = $i-((pow(2, $round-1)-1)+((pow(2, $round)-1)*$lastPosition[$round]));
		$lastPosition[$round] = $pos;
		return $pos;
	}

	function getFirstRowNameSelect($position){
		$result = "<select name='name_1_$position' class='drawInputSelect'>";
		$toSelect = $this->getName(1, $position);
		$result.="<option value=''>N/A";
		$objRS = New TQuery("select TournamentApplicationID, Rating, LastName, FirstName, MidName, City, Display1Round from TournamentApplication where Approved = TRUE AND (DrawID = ".$this->drawID." OR DrawID = ".$this->mainDrawID.") ORDER BY LastName");
		while(!$objRS->EOF){
			$sel = "";
			if(isset($toSelect) && $toSelect == $objRS->Fields["TournamentApplicationID"])
				$sel = " selected";
			$displyName = $objRS->Fields["Rating"] . ". " . $objRS->Fields["Display1Round"];
			$result.="<option value='".$objRS->Fields["TournamentApplicationID"]."'{$sel}>" . $displyName;
			$objRS->Next();
		}
		$result.="</select>";
			return $result;
	}

	function getNameSelect($round, $position){
		$result = "<select name='name_{$round}_{$position}' class='drawInputSelect'>";
		$toSelect = $this->getName($round, $position);
		$result.="<option value=''>N/A";
		$roundPosition = $position * 2 - 1;
		$round = $round - 1;
		$objRS = New TQuery("select ta.TournamentApplicationID, ta.LastName, ta.FirstName, ta.MidName, ta.City, ta.Display1Round, ta.Display from TournamentApplication ta INNER JOIN TournamentMatch tm ON tm.FirstPlayerID = ta.TournamentApplicationID OR tm.SecondPlayerID = ta.TournamentApplicationID where tm.DrawID = ".$this->drawID." AND tm.Round = ".$round." AND tm.RoundPosition = ".$roundPosition . " ORDER BY LastName" );
		while(!$objRS->EOF){
			$sel = "";
			if(isset($toSelect) && $toSelect == $objRS->Fields["TournamentApplicationID"])
				$sel = " selected";
			$displyName = $objRS->Fields["Display1Round"];
			$result.="<option value='".$objRS->Fields["TournamentApplicationID"]."'{$sel}>" . $displyName;
			$objRS->Next();
		}
		$result.="</select>";
			return $result;
	}

	function getGroupNameSelect($groupNum, $firstGroupPosition){
		$result = "<select name='groupname_{$groupNum}_{$firstGroupPosition}' class='drawInputSelect'>\n";
		$toSelect = $this->getGroupName($groupNum, $firstGroupPosition);
		$result.="<option value=''>N/A\n";
		$objRS = New TQuery("select ta.TournamentApplicationID, ta.Display1Round, ta.Display from TournamentApplication ta where Approved = TRUE AND ta.DrawID = (SELECT DrawID FROM Draw WHERE WinnersPlace = 1 AND TournamentCategoryLinkID = (
SELECT TournamentCategoryLinkID FROM Draw WHERE DrawID = ".$this->drawID . ")) ORDER BY Display1Round");
		while(!$objRS->EOF){
			$sel = "";
			if(isset($toSelect) && $toSelect == $objRS->Fields["TournamentApplicationID"])
				$sel = " selected";
			$displyName = $objRS->Fields["Display1Round"];
			$result.="<option value='".$objRS->Fields["TournamentApplicationID"]."'{$sel}>" . $displyName . "\n";
			$objRS->Next();
		}
		$result.="</select>\n";
			return $result;
	}

	function getName($round, $position){
#		if(isset($_POST["name_{$round}_{$position}"]))
#			return $_POST["name_{$round}_{$position}"];
#		else{
			$objRS1 = New TQuery("SELECT FirstPlayerID FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND Round = ".$round." AND FirstPosition = ".$position);
			if(!$objRS1->EOF)
				return $objRS1->Fields["FirstPlayerID"];
			else{
				$objRS2 = New TQuery("SELECT SecondPlayerID FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND Round = ".$round." AND SecondPosition = ".$position);
				if(!$objRS2->EOF)
					return $objRS2->Fields["SecondPlayerID"];
			}
#		}
	}

	function getGroupName($groupNum, $firstGroupPosition){
		$objRS1 = New TQuery("SELECT FirstPlayerID, SecondPlayerID, FlipMatch FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND FirstGroupPosition = ".$firstGroupPosition);
		if(!$objRS1->EOF)
			if($objRS1->Fields["FlipMatch"] == true)
				$result = $objRS1->Fields["SecondPlayerID"];
			else
				$result = $objRS1->Fields["FirstPlayerID"];
		else{
			$objRS2 = New TQuery("SELECT FirstPlayerID, SecondPlayerID, FlipMatch FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND SecondGroupPosition = ".$firstGroupPosition);
			if(!$objRS2->EOF)
				if($objRS2->Fields["FlipMatch"] == true)
					$result = $objRS2->Fields["FirstPlayerID"];
				else
					$result = $objRS2->Fields["SecondPlayerID"];
			else
				return false;
		}
		return $result;
	}

	function getDisplayName($applicationID, $position, $full=true, $showPosition=true){
		global $QS;
		$bXUser = false;
		$result = $lineStyle = "";
		if($this->subGroupCount>0)
			$showPosition=false;
		$objRS = New TQuery("select UserID, PartnerID, FirstName, MidName, LastName, City, Display1Round, Display, Seeded FROM TournamentApplication WHERE TournamentApplicationID = " . $applicationID);
		if(!$objRS->EOF){
			if($full){
				$result = $objRS->Fields["Display1Round"];
			}
			else
				$result = $objRS->Fields["Display"];
		}
		if($result == BYE_DISPLAY or $result == BYE_DISPLAY){
			if($full && $this->winnersPlace == 1){
				$result = "<table cellspacing='0' cellpadding='0' border='0'><tr><td class='drawNames'>$position.</td><td align='center' width='100%' class='drawNames'>".BYE_DISPLAY."</td></tr></table>";
				$bXUser = true;
			}
			else
				$result = "<div align='center' class='drawNames'>".BYE_DISPLAY."</div>";
		}
		if($objRS->Fields["Seeded"] && $this->winnersPlace == 1)
			$lineStyle = "font-weight: bold;";
		if(!$bXUser && !isset($QS["print"]) && $objRS->Fields["UserID"] != "" && $objRS->Fields["UserID"] != BYE_ID) {
			$names = explode('-', $result);
			if ($this->doubles && !empty($objRS->Fields["PartnerID"]) && count($names) == 2) {
				$ids = array();
				$f = $objRS->Fields;
				$compareFName = stripos(trim($names[0]), ' ') !== false;
				$compareFName2 = stripos(trim($names[1]), ' ') !== false;
				if ((empty($f["FirstName"]) || !$compareFName || strpos(mb_strtolower($names[0], 'utf-8'), mb_strtolower($f["FirstName"], 'utf-8')) !== false) && strpos(mb_strtolower($names[0], 'utf-8'), mb_strtolower($f["LastName"], 'utf-8')) !== false)
					$ids = array($f["UserID"], $f["PartnerID"]);
				elseif ((empty($f["FirstName"]) || !$compareFName2|| strpos(mb_strtolower($names[1], 'utf-8'), mb_strtolower($f["FirstName"], 'utf-8')) !== false) && !empty($f["LastName"]) && strpos(mb_strtolower($names[1], 'utf-8'), mb_strtolower($f["LastName"], 'utf-8')) !== false)
					$ids = array($f["PartnerID"], $f["UserID"]);

				if (count($ids) == 2) {
					$result = "<a style='$lineStyle' href='/user/personal.php?id=".$ids[0]."'>".trim($names[0])."</a>";
					$result .= " - ";
					$result .= "<a style='$lineStyle' href='/user/personal.php?id=".$ids[1]."'>".trim($names[1])."</a>";
				}
			}
			elseif (!$this->doubles)
				$result = "<a class='drawNames' style='$lineStyle'  href='/user/personal.php?id=".$objRS->Fields["UserID"]."&list=1'>{$result}</a>";
		}
		if($this->winnersPlace == 1 && $full && !$bXUser && $showPosition)
			$result = $position . ". " . $result;
		if($objRS->Fields["Seeded"] && $this->winnersPlace == 1)
			$result = "<b>" . $result .  "</b>";
		return $result;
	}

	function getValue($round, $position, $type){
		$roundPosition = $position;
		if($position%2==0)
			$roundPosition = $position - 1;
		if($type == "score"){
			$roundPosition = $position * 2 - 1;
			$round = $round - 1;
		}
		$objRS1 = New TQuery("SELECT FirstPosition, FirstPlayerID, SecondPosition, SecondPlayerID, Score, MatchTime FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND Round = ".$round." AND RoundPosition = " . $roundPosition);
		if(!$objRS1->EOF){
			if($type == "time")
				return  $objRS1->Fields["MatchTime"];
			elseif($type == "score"){
				return $objRS1->Fields["Score"];
			}
		}
		else{
			return "";
		}
	}

	function getGroupValue($groupNum, $firstGroupPosition, $secondGroupPosition, $type){
		$objRS1 = New TQuery("SELECT Score, MatchTime, FirstPlayerID, SecondPlayerID, WinnerID FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND FirstGroupPosition = " . $firstGroupPosition . " AND SecondGroupPosition = " . $secondGroupPosition);
		$objRS2 = New TQuery("SELECT Score, MatchTime, FirstPlayerID, SecondPlayerID, WinnerID FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND FirstGroupPosition = " . $secondGroupPosition . " AND SecondGroupPosition = " . $firstGroupPosition);
		if(!$objRS1->EOF){
			if($type == "time" && $objRS1->Fields["MatchTime"] != "")
				return  $objRS1->Fields["MatchTime"];
			elseif($type == "score" && $objRS1->Fields["Score"] != ""){
				return $objRS1->Fields["Score"];
			}
			elseif($type="win") {
				return !empty($objRS1->Fields["WinnerID"]);
			}
		}
		if(!$objRS2->EOF){
			if($type == "time" && $objRS2->Fields["MatchTime"] != "")
				return  $objRS2->Fields["MatchTime"];
			elseif($type == "score" && $objRS2->Fields["Score"] != ""){
				return $this->reverseScore($objRS2->Fields["Score"]);
			}
			elseif($type="win") {
				return false;
			}

		}
		else{
			return "";
		}
	}

	function processForm(){
#print "processing form!!!!!!!!!!<br>";
		global $Connection;
		$drawAr = array();
		foreach($_POST as $key => $value){
			if($key != "drawID"){
				$keyAr = explode("_", $key);
				$dataType = $keyAr[0];
				$round = intval($keyAr[1]);
				$position = intval($keyAr[2]);
				$playerID1 = $playerID2 = $playerPosition1 = $playerPosition2 = "";
				$drawArIndex = $position;
				if($position%2==0)
					$drawArIndex = $position - 1;
				$roundPosition = $drawArIndex;
				$drawArIndex = $round . "_" . $drawArIndex;
				if(!is_array($drawAr))
					$drawAr[$drawArIndex] = array();
				$drawAr[$drawArIndex]["round"] = $round;
				$drawAr[$drawArIndex]["roundPosition"] = $roundPosition;
				if($dataType == "name"){
					if($position%2==1){
						$playerID1 = $value;
						$playerPosition1 = $position;
					}
					else{
						$playerID2 = $value;
						$playerPosition2 = $position;
					}
					if($playerID1 != ""){
						$drawAr[$drawArIndex]["playerID1"] = $playerID1;
						$drawAr[$drawArIndex]["playerPosition1"] = $playerPosition1;
					}
					if($playerID2 != ""){
						$drawAr[$drawArIndex]["playerID2"] = $playerID2;
						$drawAr[$drawArIndex]["playerPosition2"] = $playerPosition2;
					}
					if($round>1){
						if($value != ""){
							$drawArIndex = ($round-1) . "_" . ($position*2-1);
							$drawAr[$drawArIndex]["winnerID"] = $value;
						}
					}
				}
				elseif($dataType == "time"){
					if($value != ""){
						$drawAr[$drawArIndex]["time"] = $value;
					}
				}
				elseif($dataType == "score"){
					if($value != ""){
						$drawArIndex = ($round-1) . "_" . ($position*2-1);
						$drawAr[$drawArIndex]["score"] = $value;
					}
				}
			}
		}
#print "<textarea rows='20' cols=80>";
#print_r($drawAr);
#print "</textarea>";
#die();
		foreach($drawAr as $key => $value){
			$objRS = New TQuery("SELECT TournamentMatchID FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND Round = ".$drawAr[$key]["round"]." AND RoundPosition = ".$drawAr[$key]["roundPosition"]."");
			if(!$objRS->EOF){
				$Connection->Execute("UPDATE TournamentMatch SET
FirstPosition = ".(isset($drawAr[$key]["playerPosition1"])?$drawAr[$key]["playerPosition1"]:"NULL").",
FirstPlayerID = ".(isset($drawAr[$key]["playerID1"])?$drawAr[$key]["playerID1"]:"NULL").",
SecondPosition = ".(isset($drawAr[$key]["playerPosition2"])?$drawAr[$key]["playerPosition2"]:"NULL").",
SecondPlayerID = ".(isset($drawAr[$key]["playerID2"])?$drawAr[$key]["playerID2"]:"NULL").",
Score = ".(isset($drawAr[$key]["score"])?"'".$drawAr[$key]["score"]."'":"NULL").",
WinnerID = ".(isset($drawAr[$key]["winnerID"])?$drawAr[$key]["winnerID"]:"NULL").",
MatchTime = ".(isset($drawAr[$key]["time"])?"'".$drawAr[$key]["time"]."'":"NULL")." WHERE TournamentMatchID = " . $objRS->Fields["TournamentMatchID"]);
			}
#do an additional check to see if this is actually a match and not just empty draw (where only N/As are selected)
			elseif(isset($drawAr[$key]["playerID1"]) || isset($drawAr[$key]["playerID2"]) || isset($drawAr[$key]["time"])){
				$Connection->Execute("INSERT INTO TournamentMatch (DrawID, Round, RoundPosition, FirstPosition, FirstPlayerID, SecondPosition, SecondPlayerID, Score, WinnerID, MatchTime) VALUES (".$this->drawID.",
".(isset($drawAr[$key]["round"])?$drawAr[$key]["round"]:"NULL").",
".(isset($drawAr[$key]["roundPosition"])?$drawAr[$key]["roundPosition"]:"NULL").",
".(isset($drawAr[$key]["playerPosition1"])?$drawAr[$key]["playerPosition1"]:"NULL").",
".(isset($drawAr[$key]["playerID1"])?$drawAr[$key]["playerID1"]:"NULL").",
".(isset($drawAr[$key]["playerPosition2"])?$drawAr[$key]["playerPosition2"]:"NULL").",
".(isset($drawAr[$key]["playerID2"])?$drawAr[$key]["playerID2"]:"NULL").",
".(isset($drawAr[$key]["score"])?"'".$drawAr[$key]["score"]."'":"NULL").",
".(isset($drawAr[$key]["winnerID"])?$drawAr[$key]["winnerID"]:"NULL").",
".(isset($drawAr[$key]["time"])?"'".$drawAr[$key]["time"]."'":"NULL").")");
			}
		}
		$Connection->Execute("UPDATE Draw SET ModificationTime = now() WHERE DrawID = ".$this->drawID);
		$this->processGroupForm();
#print "<textarea rows='20' cols=80>";
#print_r($drawAr);
#print "</textarea>";
	}

	function processGroupForm(){
		global $Connection, $Interface;
		$MatchesAr = array();
		$groupNameAr = array();
		$placesArr = array();
		$MatchesArIndex = 0;
#begin getting all the names that have been selected in the goups so that i can calculate second player id
		foreach($_POST as $key => $value){
			if(strpos($key, "groupname") !== false){
				$keyAr = explode("_", $key);
				$postedGroupNum = $keyAr[1];
				$postedFirstGroupPosition = $keyAr[2];
				$groupNameAr[$postedGroupNum][$postedFirstGroupPosition] = $value;
				$placeKey = "groupplace_{$postedGroupNum}_{$postedFirstGroupPosition}";
				if (isset($_POST[$placeKey]))
					$placesArr[$value] = $_POST[$placeKey];
			}
		}
#end getting all the names that have been selected in the goups so that i can calculate second player id
#*new* begin adding all matches to the $MatchesAr regardless if they have been played or not
		foreach($groupNameAr as $groupNum => $firstGroupPlayerIDsAr){
			$secondGroupPlayerIDsAr = $firstGroupPlayerIDsAr;
			foreach($firstGroupPlayerIDsAr as $firstGroupPos => $firstPlayerID){
				if($firstPlayerID != ""){
					foreach($secondGroupPlayerIDsAr as $secondGroupPos => $secondPlayerID){
						if($firstGroupPos < $secondGroupPos && $secondPlayerID != "" && !$this->matchExists($groupNum, $firstGroupPos, $secondGroupPos, $MatchesAr)){
							$filedID = "{$groupNum}_{$firstGroupPos}_{$secondGroupPos}";
							$filedIDrev = "{$groupNum}_{$secondGroupPos}_{$firstGroupPos}";
#							if(isset($_POST["groupscore_{$filedIDrev}"]) && rusStrLen($_POST["groupscore_{$filedIDrev}"]) > 2)
#								break;
							$MatchesAr[$MatchesArIndex]["GroupNumber"] = $groupNum;
							$MatchesAr[$MatchesArIndex]["FirstGroupPosition"] = $firstGroupPos;
							$MatchesAr[$MatchesArIndex]["FirstPlayerID"] = $firstPlayerID;
							$MatchesAr[$MatchesArIndex]["SecondGroupPosition"] = $secondGroupPos;
							$MatchesAr[$MatchesArIndex]["SecondPlayerID"] = $secondPlayerID;
							$MatchesAr[$MatchesArIndex]["FlipMatch"] = "FALSE";
#begin getting the match score and time. Need to check both fileds: player 1 against player 3 and player 3 against player 1
							if(isset($_POST["groupscore_{$filedID}"]) && (isset($_POST["groupwin_{$filedID}"]) || (mb_strlen($_POST["groupscore_{$filedID}"]) > 2 && mb_strlen($_POST["groupscore_{$filedIDrev}"]) <= 2))){
								$MatchesAr[$MatchesArIndex]["Score"] = $_POST["groupscore_{$filedID}"];
								$MatchesAr[$MatchesArIndex]["WinnerID"] = $MatchesAr[$MatchesArIndex]["FirstPlayerID"];
							}
							elseif(isset($_POST["groupscore_{$filedIDrev}"]) && (isset($_POST["groupwin_{$filedIDrev}"]) || (mb_strlen($_POST["groupscore_{$filedIDrev}"]) > 2 && mb_strlen($_POST["groupscore_{$filedID}"]) <= 2))){
								$MatchesAr[$MatchesArIndex]["Score"] = $_POST["groupscore_{$filedIDrev}"];
								$MatchesAr[$MatchesArIndex]["WinnerID"] = $MatchesAr[$MatchesArIndex]["SecondPlayerID"];
								$MatchesAr[$MatchesArIndex]["FirstGroupPosition"] = $secondGroupPos;
								$MatchesAr[$MatchesArIndex]["SecondGroupPosition"] = $firstGroupPos;
#								$MatchesAr[$MatchesArIndex]["FirstPlayerID"] = $secondPlayerID;
#								$MatchesAr[$MatchesArIndex]["SecondPlayerID"] = $firstPlayerID;
								$MatchesAr[$MatchesArIndex]["FlipMatch"] = "TRUE";
#								$this->matchExists($groupNum, $secondGroupPos, $firstGroupPos, $MatchesAr);
							}
							if(isset($_POST["grouptime_{$filedID}"]) && $_POST["grouptime_{$filedID}"] != "")
								$MatchesAr[$MatchesArIndex]["Time"] = $_POST["grouptime_{$filedID}"];
							elseif(isset($_POST["grouptime_{$filedIDrev}"]))
								$MatchesAr[$MatchesArIndex]["Time"] = $_POST["grouptime_{$filedIDrev}"];
#end getting the match score and time. Need to check both fileds: player 1 against player 3 and player 3 against player 1
							$MatchesArIndex++;
						}
					}
				}
				else{
					$Connection->Execute("DELETE FROM TournamentMatch WHERE GroupNumber = $groupNum AND DrawID = " . $this->drawID . " AND (FirstGroupPosition = ".intval($firstGroupPos)." OR SecondGroupPosition = ".intval($firstGroupPos) .")");
				}
			}
		}
#*new* end adding all matches to the $MatchesAr regardless if they have been played or not
		foreach($MatchesAr as $key => $value){
# begin checking to see if this match has already been recorded, if so do the update
#begin checking if the match for the opposite cell is already in the database, if so lets update that one instead of creating new
			$matchIDToUpdate = $updateQuery = "";
			$oppositeMatch = New TQuery("select TournamentMatchID from TournamentMatch WHERE GroupNumber = ".intval($value["GroupNumber"])." AND FirstGroupPosition = ".intval($value["SecondGroupPosition"])." AND SecondGroupPosition = " . intval($value["FirstGroupPosition"]) . " AND DrawID = " . $this->drawID);
#If this is a match for the bottom half of the group then set FlipMatch = TRUE
			if(!$oppositeMatch->EOF){
#				$tmp = $value["FirstPlayerID"];
#				$value["FirstPlayerID"] = $value["SecondPlayerID"];
#				$value["SecondPlayerID"] = $tmp;
				$matchIDToUpdate = $oppositeMatch->Fields["TournamentMatchID"];
			}
			$updateQuery = ", FlipMatch = FALSE";
# && !$oppositeMatch->EOF
			if($value["FirstGroupPosition"] > $value["SecondGroupPosition"])
				$updateQuery = ", FlipMatch = TRUE";
#end checking if the match for the opposite cell is already in the database, if so lets update that one instead of creating new
			$checkMatch = New TQuery("select TournamentMatchID from TournamentMatch WHERE GroupNumber = ".intval($value["GroupNumber"])." AND FirstGroupPosition = ".intval($value["FirstGroupPosition"])." AND SecondGroupPosition = " . intval($value["SecondGroupPosition"]) . " AND DrawID = " . $this->drawID);
			if(!$checkMatch->EOF || !$oppositeMatch->EOF){
				if($oppositeMatch->EOF)
					$matchIDToUpdate = $checkMatch->Fields["TournamentMatchID"];
				$Connection->Execute("UPDATE TournamentMatch SET
Score = ".((isset($value["Score"]) && $value["Score"] != "")?"'".$value["Score"]."'":"NULL").",
WinnerID = ".((isset($value["WinnerID"]) && $value["WinnerID"] != "")?"'".$value["WinnerID"]."'":"NULL").",
GroupNumber = ".$value["GroupNumber"].",
FirstGroupPosition = ".$value["FirstGroupPosition"].",
SecondGroupPosition = ".$value["SecondGroupPosition"].",
MatchTime = ".((isset($value["Time"]) && $value["Time"] != "")?"'".$value["Time"]."'":"NULL")."{$updateQuery}
WHERE TournamentMatchID = $matchIDToUpdate");
/*				$Connection->Execute("UPDATE TournamentMatch SET
FirstPlayerID = ".$value["FirstPlayerID"].",
SecondPlayerID = ".$value["SecondPlayerID"].",
Score = ".((isset($value["Score"]) && $value["Score"] != "")?"'".$value["Score"]."'":"NULL").",
WinnerID = ".((isset($value["WinnerID"]) && $value["WinnerID"] != "")?"'".$value["WinnerID"]."'":"NULL").",
GroupNumber = ".$value["GroupNumber"].",
FirstGroupPosition = ".$value["FirstGroupPosition"].",
SecondGroupPosition = ".$value["SecondGroupPosition"].",
MatchTime = ".((isset($value["Time"]) && $value["Time"] != "")?"'".$value["Time"]."'":"NULL")."{$updateQuery}
WHERE TournamentMatchID = $matchIDToUpdate");
*/
			if(!$oppositeMatch->EOF)
				print $checkMatch->Fields["TournamentMatchID"] . "<br>";
			}
# end checking to see if this match has already been recorded, if so do the update
# begin inserting a new record in to the database since this match doesn't exist yet
			else{
				$Connection->Execute("INSERT INTO TournamentMatch (DrawID, FirstPlayerID, SecondPlayerID, Score, WinnerID, GroupNumber, FirstGroupPosition, SecondGroupPosition, MatchTime, FlipMatch) VALUES (".$this->drawID.",
".$value["FirstPlayerID"].",
".$value["SecondPlayerID"].",
".(isset($value["Score"])?"'".$value["Score"]."'":"NULL").",
".(isset($value["WinnerID"])?"'".$value["WinnerID"]."'":"NULL").",
".$value["GroupNumber"].",
".$value["FirstGroupPosition"].",
".$value["SecondGroupPosition"].",
".((isset($value["Time"]) && $value["Time"] != "")?"'".$value["Time"]."'":"NULL").",
".$value["FlipMatch"].")");
			}
# end inserting a new record in to the database since this match doesn't exist yet
		}
# begin recording who acheived what place in the group
		foreach ($placesArr as $appId => $place)
			if (is_numeric($place))
				$Connection->Execute("UPDATE TournamentApplication set SubGroupPlaceAcheived = '" . intval($place) . "' where TournamentApplicationID = " . intval($appId));
/*
		for($groupNum=1;$groupNum<=$this->subGroupCount;$groupNum++){
			$placeRS = New TQuery("select count(WinnerID) AS Points, FirstPlayerID, FirstGroupPosition from TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." GROUP BY FirstPlayerID, FirstGroupPosition ORDER BY Points DESC");
			$position = 0;
			while(!$placeRS->EOF){
				$position++;
				$Connection->Execute("UPDATE TournamentApplication SET SubGroupPlaceAcheived = '$position' WHERE TournamentApplicationID = " . $placeRS->Fields["FirstPlayerID"]);
				$placeRS->Next();
			}
		}
*/
# end recording who acheived what place in the group
		$Connection->Execute("UPDATE Draw SET ModificationTime = now() WHERE DrawID = ".$this->drawID);
/*
print "<textarea rows='20' cols=80>";
print_r($_POST);
print "-------------------------------\n";
print_r($groupNameAr);
print "-------------------------------\n";
print_r($MatchesAr);
print "</textarea>";
*/
	}

	function matchExists($groupNum, $firstGroupPos, $secondGroupPos, $MatchesAr){
		global $Connection;
		foreach($MatchesAr as $key => $match){
			if($MatchesAr[$key]["FirstGroupPosition"] == $firstGroupPos &&
			   $MatchesAr[$key]["SecondGroupPosition"] == $secondGroupPos &&
			   $MatchesAr[$key]["GroupNumber"] == $groupNum)
				return true;
			if($MatchesAr[$key]["FirstGroupPosition"] == $secondGroupPos &&
			   $MatchesAr[$key]["SecondGroupPosition"] == $firstGroupPos &&
			   $MatchesAr[$key]["GroupNumber"] == $groupNum)
				return true;
		}
		return false;
	}

#Begin adding this method as a template for drawing olympic draw - do not modify this code, instead copy it out into a separate method and modify it there
	function DoNotUseOrChangeThisMethodHere(){
		$rowSpanMarks = array();
		$lastPosition = array();
		$position = 0;
		for($i=1; $i<=$this->drawSize*2-1; $i++)
			for($round=1; $round<=$this->rounds+1; $round++){
				$rowSpanMarks[$i][$round] = false;
				$lastPosition[$round] = 0;
			}
		print "<table cellspacing=0 cellpadding=2 border=0 bordercolor='#c0c0c0'>";
		for($i=1; $i<=$this->drawSize*2-1; $i++){
#$position = ($i+1)/2;
			print "<tr>";
			for($round=1; $round<=$this->rounds+1; $round++){
				$rowSpan = pow(2, $round) - 2;
				$beginRowSpan = pow(2, $round-1) + 2;
#begin working with first round only...
				if($round==1){
					if($i%2==1){
						$borderClass = "bBottom";
						if($i%4==3)
							$borderClass = "bBottomRight";
	#begin adding names of the first round
						$position = $this->calcPosition($i, $round, $lastPosition);
						print "<td nowrap class='$borderClass'><span style='color: #9b9b9b; font-size: 9px;'>{$position}.</span> Name</td>";
	#end adding names of the first round
					}
					elseif($i%4==2){
	#begin adding match times of the first round
						print "<td nowrap class='bRight' align='right'>time</td>";
	#end adding match times of the first round
					}
					else{
						print "<td>".spacer(90)."</td>";
					}
				}
#end working with first round only...
#begin creating draw for every round other than the first one
				elseif($i%pow(2, $round)==pow(2, ($round-1))){
					$position = $this->calcPosition($i, $round, $lastPosition);
					$borderClass = " class='bBottom'";
					if($i%pow(2, $round+1)==pow(2, $round+1)-pow(2, $round-1) && $round!=$this->rounds)
						$borderClass = " class='bBottomRight'";
	#begin adding names
					print "<td nowrap{$borderClass}>Name</td>";
	#end adding names
				}
				elseif($i%pow(2, $round)==pow(2, ($round-1))+1){
					$borderClass = "";
					if($i%pow(2, $round+1)==pow(2, $round-1)+1 && $round!=$this->rounds)
						$borderClass = " class='bRight'";
	#begin adding score
					print "<td nowrap{$borderClass}>{$lastPosition[$round]}. score</td>";
	#end adding score
				}
				elseif(($i+(pow(2, ($round+1))-$beginRowSpan))%pow(2, ($round+1))==0){
					$rowSpanMarks[$i][$round] = true;
					$time = $borderClass = "";
					if($round!=$this->rounds){
						$borderClass = " class='bRight'";
						$time="{$lastPosition[$round]}. time";
					}
	#begin adding match times
					print "<td nowrap align='right' rowspan='$rowSpan'$borderClass>$time</td>";
	#end adding match times
				}
	#begin the very last row containing the winners place number
				elseif($round==$this->rounds+1){
					if($i==1){
						print "<td rowspan='".($this->drawSize*2-1)."' style='padding-top: 22px;'><b>".$this->winnersPlace."</b></td>";
					}
				}
	#end the very last row containing the winners place number
				else{
					$bRemoveCell = false;
					for($k=$i; ($k>$i-$rowSpan && $k>0); $k--)
						if($rowSpanMarks[$k][$round])
							$bRemoveCell = true;
					if(!$bRemoveCell)
						print "<td>".spacer(90)."</td>";
				}
#end creating draw for every round other than the first one
			}
			print "</tr>";
		}
		print "</table>";
	}

	private function reverseScore($score) {
		if (empty($score) || strpos($score, "/") === false)
			return "";
		$score = str_replace(",", " ", $score);
		$score = preg_replace("/\s+/", " ", $score);
		$score = preg_replace("/\s+\(/", "(", $score);
		$score = trim($score);
		$scores = explode(" ", $score);
		$reverse = "";
		foreach ($scores as $game) {
			$points = explode("/", $game);
			if (count($points) !== 2)
				continue;
			$pos = strpos($points[1], "(");
			if ($pos !== false) {
				$points[0] .= substr($points[1], $pos);
				$points[1] = substr($points[1], 0, $pos);
			}
			$reverse .= $points[1] . "/" . $points[0] . ", ";
		}
		$reverse = trim($reverse, ", ");
		return $reverse;
	}

	private function isWin($groupNum, $firstGroupPosition, $secondGroupPosition){
		$objRS1 = New TQuery("SELECT Score, MatchTime, FirstPlayerID, SecondPlayerID, WinnerID FROM TournamentMatch WHERE DrawID = ".$this->drawID." AND GroupNumber = ".$groupNum." AND FirstGroupPosition = " . $firstGroupPosition . " AND SecondGroupPosition = " . $secondGroupPosition);
		return !$objRS1->EOF;
	}

#End adding this method as a template for drawing olympic draw - do not modify this code, instead copy it out into a separate method and modify it there
}
?>
