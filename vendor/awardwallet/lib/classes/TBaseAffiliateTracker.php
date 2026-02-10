<?

define( 'AFF_HIT_KIND_CLICK', 101 );
define( 'AFF_HIT_KIND_REGISTER', 102 );
define( 'AFF_HIT_KIND_PAYMENT', 103 );

define( 'AFF_ITEM_KIND_USER', 101 );
define( 'AFF_ITEM_KIND_SITE', 102 );

define( 'AFF_TRAN_KIND_COMMISSION', 1 );
define( 'AFF_TRAN_KIND_CHARGE_BACK', 2 );
define( 'AFF_TRAN_KIND_REFUND', 3 );
define( 'AFF_TRAN_KIND_PAYMENT', 4 );

define( 'AFF_TRAN_STATE_PENDING', 1 );
define( 'AFF_TRAN_STATE_APPROVED', 2 );

class TBaseAffiliateTracker
{
	// record affiliate statistics, if any
	// add this call to /kernel/public.php
	function TrackURL()
	{
		global $QS, $Connection;
		if( isset( $QS["RefUserID"] ) )
		{
			$nUserID = intval( $QS["RefUserID"] );
			$q = new TQuery("select 1 from Usr where AffApproved = 1 and UserID = $nUserID" );
			if( !$q->EOF )
			{
				if( $this->GetURLInfo( $nItemKind, $nItemID ) )
					$this->Track( $nUserID, AFF_HIT_KIND_CLICK, $nItemKind, $nItemID );
				else
					$this->Track( $nUserID, AFF_HIT_KIND_CLICK, AFF_ITEM_KIND_SITE, null );
				$_SESSION['RefUserID'] = $nUserID;
			}
			unset( $QS['RefUserID'] );
		}
	}
	
	// discover advertised item from url
	// return true and info on success. else false
	function GetURLInfo( &$nItemKind, &$nItemID )
	{
		DieTrace( "You should override this method in ancestor" );
	}
	
	// track currently logged in or specified user, if he registered through affiliate
	// return affiliate UserID, or null
	function TrackUser( $nHitKind, $nItemKind, $nItemID, $nUserID = null )
	{
		if( !isset( $nUserID ) && !isset( $_SESSION['UserID'] ) )
			return null;
		if( !isset( $nUserID ) )
			$nUserID = $_SESSION['UserID'];
		$q = new TQuery("select a.UserID from Usr u, Usr a where u.AffCameFrom = a.UserID and a.AffApproved = 1 and u.UserID = {$nUserID}");
		if( $q->EOF )
			return null;
		$this->Track( $q->Fields["UserID"], $nHitKind, $nItemKind, $nItemID );
		return $q->Fields["UserID"];
	}
	
	// add track record for affiliate. UserID - it's ID of affiliate
	function Track( $nUserID, $nHitKind, $nItemKind, $nItemID )
	{
		global $Connection;
		$Connection->Execute("insert into AffStat( UserID, StatDate, HitKind, ItemKind, ItemID )
		values( $nUserID, now(), $nHitKind, $nItemKind, " . ( isset( $nItemID ) ? $nItemID : "null" ) . " )");
	}
	
	// add transaction record
	function TrackTransaction( $nItemKind, $nItemID, $sProduct, $nCost, $sComments, $nUserID = null )
	{
		global $Connection;
		if( !isset( $nUserID ) && !isset( $_SESSION['UserID'] ) )
			DieTrace("Can't trace unknown user");
		if( !isset( $nUserID ) )
			$nUserID = $_SESSION['UserID'];
		$nAffUserID = $this->TrackUser( AFF_HIT_KIND_PAYMENT, $nItemKind, $nItemID, $nUserID );
		if( !isset( $nAffUserID ) )
			return false;
		$arParams = array(
			"UserID" => $nAffUserID,
			"CreationDate" => "now()",
			"State" => AFF_TRAN_STATE_PENDING,
			"ItemKind" => ( isset( $nItemKind ) ? $nItemKind : "null" ),
			"ItemID" => ( isset( $nItemID ) ? $nItemID : "null" ),
			"Product" => $sProduct,
			"Cost" => $nCost,
			"Comments" => $sComments,
			"Commission" => round( $nCost * 0.1, 2 ),
			"Kind" => AFF_TRAN_KIND_COMMISSION,
		);
		if( !$this->ApproveTransaction( $arParams, $nItemKind, $nItemID, $nUserID, $Connection->SQLToDateTime( Lookup('Usr', 'UserID', 'CreationDateTime', $nUserID, True ) ) ) )
			return false;
		$arParams['Balance'] = $this->GetCurrentBalance( $nAffUserID ) + $arParams['Commission'];
		$arParams['Product'] = "'" . addslashes( $arParams['Product'] ) . "'";
		if( isset( $arParams['Comments'] ) )
			$arParams['Comments'] = "'" . addslashes( $arParams['Comments'] ) . "'";
		else
			$arParams['Comments'] = 'null';
		$Connection->Execute( InsertSQL( "AffTransaction", $arParams ) );
		return true;
	}
	
	// calc current balance for affiliate
	function GetCurrentBalance( $nUserID )
	{
		$q = new TQuery("select Balance from AffTransaction where UserID = $nUserID order by CreationDate desc limit 1");
		if( !$q->EOF )
			return $q->Fields["Balance"];
		else 
			return 0;
	}
	
	// calc additional transaction parameters, f.x. commission
	// should return true on success, false to cancel transaction recording
	function ApproveTransaction( &$arParams, $nItemKind, $nItemID, $nUserID, $dUserRegDate )
	{
		return true;
	}
	
}

?>