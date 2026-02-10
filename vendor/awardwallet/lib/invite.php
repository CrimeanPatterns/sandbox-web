<?
global $Interface;
if( isset( $_SESSION["UserID"] ) ) {
	$invitedCount = 0;
	$acceptedCount = 0;
	$qInvite = new TQuery("SELECT count(InvitesID) AS invited FROM Invites WHERE InviterID = ".$_SESSION["UserID"]." AND InviteeID <> InviterID");
	if(!$qInvite->EOF)
		$invitedCount = $qInvite->Fields["invited"];
	$qInvite->Close();
	$qInvite = new TQuery("SELECT count(InvitesID) AS accepted FROM Invites WHERE InviterID = ".$_SESSION["UserID"] . " AND InviteeID IS NOT NULL AND InviteeID <> InviterID");
	if(!$qInvite->EOF)
		$acceptedCount = $qInvite->Fields["accepted"];
	$qInvite->Close();
	$Interface->DrawInviteBox($invitedCount, $acceptedCount);
} #the user is logged in
?>