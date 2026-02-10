<?
// -----------------------------------------------------------------------
// forum class for displaying News - It should be "smarter" than the TBaseForumList. 
// Each news / forum format needs to be overwritten in a sparate class now.
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------

class TBaseNewsList extends TBaseList{
	var $achiveLink = "/newsArchive.php";
	var $iColumns = 2;
	var $bShowArchiveLink = true;
	var $showBottomNav = false;
	var $showTopNav = false;
	var $archiveText = "archive";
	
	function Draw(){
		global $Connection;
		$this->OpenQuery();
		$objRS = &$this->Query;
		if( !$objRS->EOF )
		{
			if($this->showTopNav)
				$this->drawPageDetails("bottom", True);
			print "<table cellspacing=0 cellpadding=0 border=0 width='100%'>";
			while( ( $this->UsePages && !$objRS->EndOfPage() ) || ( !$this->UsePages && !$objRS->EOF ) )
			{
				$this->FormatFields();
				$this->DrawRow();
				$objRS->Next();
			}
			if($this->bShowArchiveLink)
				print "<tr><td height='20' colspan='".$this->iColumns."' align='right'><a style='font-size: 10px;' href='".$this->achiveLink."'>".$this->archiveText." &gt;&gt;</a></td></tr>";
			print "</table>";
			if($this->showBottomNav)
			$this->drawPageDetails("top", False);
		}
		else
			$this->DrawEmptyList();
	}
}
?>
