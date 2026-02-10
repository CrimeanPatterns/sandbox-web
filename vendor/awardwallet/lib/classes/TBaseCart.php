<?

// -----------------------------------------------------------------------
// shopping cart class
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

define( 'CART_ITEM_TYPE_SINGLE', 201 );
define( 'CART_CATEGORY_SHIPPING', 1 );
define( 'CART_CATEGORY_STATE_TAX', 2 );

class TBaseCart
{
	// unique CartID in Cart table
	var $ID;
	// payment type
	var $PaymentType;
	// to use following fields, you should call CalcTotals()
		// how many items in cart
		var $ItemCount = 0;
		// how much
		var $ItemsCost = 0;
		// how much
		var $Price = 0;
		// how much with discount
		var $Total = 0;
		// names. linked with <br>
		var $Names;
		// cart items. array( CartItemID => array( "TypeID" => 1, "ID" => 2 ), ..
		var $Items;
		var $ItemRows;
		// how much is discount, in currency
		var $DiscountAmount = 0;
	// entered coupon description and discount
	var $Coupon;
	var $CouponID;
	var $Discount = 0;
	// warnings. array of strings
	var $Warnings = array();
	// can unregistered user use cart?
	public $Anonymous = false;
	// cart fields
	public $Fields;
	// calculate shipping
	public $ShowShippingCalculator = False;
	public $ShippingZip;
	public $ShippingCost;
	public $ShippingDetails;
	public $Error;

	protected $baseSQL = "select
		c.*, co.Name as CouponName, co.Discount,
		co.CouponID as ActiveCouponID,
		bc.Name as BillCountryName,
		bs.Name as BillStateName
	from
		Cart c
		left outer join Coupon co on c.CouponID = co.CouponID
			and ( co.StartDate is null or co.StartDate <= now() )
			and ( co.EndDate is null or co.EndDate > now() )
		left outer join Country bc on c.BillCountryID = bc.CountryID
		left outer join State bs on c.BillStateID = bs.StateID";
	
	// open or create cart for user. by cookie or user id
	function Open()
	{
		global $Connection;
		if( $this->Anonymous && !isset( $_SESSION['UserID'] ) ) {
			$sCode = ArrayVal( $_COOKIE, "CartCode" );
			if( $sCode == "" )
				$sCode = RandomStr( ord( "A" ), ord( "Z" ), 20 );
			$q = new TQuery( $this->baseSQL . " where c.Code = '".addslashes($sCode)."' and c.UserID is null and c.PayDate is null" );
			if( $q->EOF ) {
				$arFields = array(
					"LastUsedDate" => "now()",
					"Code" => "'".addslashes($sCode)."'",
				);
				$Connection->Execute( InsertSQL( "Cart", $arFields ) );
				$q->Close();
				$q->Open();
				$Connection->Execute( "delete from Cart where UserID is null and LastUsedDate <= ". $Connection->DateTimeToSQL( time() - SECONDS_PER_DAY * 14 ) );
			}
			if( isset( $_SESSION['UserID'] ) && ( $q->Fields["UserID"] != $_SESSION['UserID'] ) ) {
				$Connection->Execute("update Cart set UserID = {$_SESSION['UserID']} where CartID = {$q->Fields['CartID']}");
				$q->Fields['UserID'] = $_SESSION['UserID'];
			}
			setcookie( 'CartCode', $sCode, time() + SECONDS_PER_DAY * 90, "/" );
		}
		else {
			AuthorizeUser();
			$nUserID = $_SESSION["UserID"];
			$q = new TQuery( $this->baseSQL . " where c.UserID = $nUserID and c.PayDate is null" );
			$arFields = array(
				"LastUsedDate" => "now()",
				"UserID" => $nUserID,
			);
			if( $this->ShowShippingCalculator ) {
				$qUser = new TQuery("select Zip from Usr where UserID = {$_SESSION['UserID']}");
				if( ( $qUser->Fields['Zip'] != '' ) && preg_match( REGEXP_USA_ZIP, $qUser->Fields['Zip'] ) )
					$arFields['ShippingZip'] = "'" . addslashes( $qUser->Fields['Zip'] ) . "'";
			}
			if( $q->EOF )
			{
				$Connection->Execute( InsertSQL( "Cart", $arFields ) );
				if(  $this->ShowShippingCalculator && isset( $_SESSION['UserID'] ) ){
					$qUsr = new TQuery("select * from Usr where UserID = {$_SESSION['UserID']}");
					if( !$qUsr->EOF && ( $qUsr->Fields["Zip"] != "" ) && preg_match( REGEXP_USA_ZIP, $qUsr->Fields["Zip"] ) ) {
						$this->ShippingZip = $qUsr->Fields["Zip"];
						$Connection->Execute("update Cart set ShippingZip = '{$qUsr->Fields['Zip']}' where UserID = $nUserID and PayDate is null");
					}
				}
				$q->Close();
				$q->Open();
			}
		}
		$this->Load( $q->Fields );
		if( isset( $_SESSION["CartWarnings"] ) )
			$this->Warnings = $_SESSION["CartWarnings"];
		else
			$this->Warnings = array();
		$Connection->Execute( "update Cart set LastUsedDate = now(), 
		CouponID = " . ArrayVal( $q->Fields, "ActiveCouponID", "null" ) . "
		where CartID = {$this->ID}" );
	}
	
	// open existing cart by id
	function OpenByID( $nID )
	{
		$q = new TQuery( $this->baseSQL . " where c.CartID = $nID" );
		if( $q->EOF )
			DieTrace( "Cart $nID not found" );
		$this->Load( $q->Fields );
	}
	
	// load cart from fields
	function Load( $arFields )
	{
		$this->ID = $arFields["CartID"];
		$this->PaymentType = ArrayVal( $arFields, "PaymentType", NULL );
		$this->Coupon = ArrayVal( $arFields, "CouponName", NULL );
		$this->CouponID = ArrayVal( $arFields, "CouponID", NULL );
		$this->Discount = ArrayVal( $arFields, "Discount", 0 );
		if( isset( $arFields['ShippingDetails'] ) )
			$this->ShippingDetails = $arFields['ShippingDetails'];
		$this->Fields = $arFields;
	}
	
	// clear cart
	function Clear()
	{
		global $Connection;
		$Connection->Execute( "delete from CartItem where CartID = {$this->ID}" );
		$this->Warnings = array();
		$_SESSION["CartWarnings"] = $this->Warnings;
		unset( $this->Coupon );
		unset( $this->CouponID );
		$this->Discount = 0;
		$this->Warnings = array();
		$Connection->Execute( "update Cart set CouponID = null where CartID = {$this->ID}" );
	}
	
	// add item to cart
	function Add( $nTypeID, $nCategoryID, $nID, $sName, $nCount, $nPrice, $nOperation = null, $nUserData, $sDescription, $arSqlParams = array(), $bCallUpdated = True )
	{
		global $Connection;
		$Connection->Execute( InsertSQL( "CartItem", array_merge( array(
			"CartID" => $this->ID,
			"TypeID" => ( isset( $nTypeID ) ? $nTypeID : "null" ),
			"CategoryID" => ( isset( $nCategoryID ) ? $nCategoryID : "null" ),
			"ID" => ( isset( $nID ) ? $nID : "null" ),
			"Name" => "'" . addslashes( $sName ) . "'",
			"Cnt" => $nCount,
			"Price" => $nPrice,
			"Operation" => ( isset( $nOperation ) ? $nOperation : "null" ),
			"UserData" => ( isset( $nUserData ) ? $nUserData : "null" ),
			"Description" => "'" . addslashes( $sDescription ) . "'",
		), $arSqlParams ) ) );
		if( $bCallUpdated )
			$this->Updated();
	}
	
	// override this method to do some actions, when cart is updated
	function Updated(){
		global $Connection;
		$this->OpenByID( $this->ID );
		$this->CalcTotals();
		if( $this->ItemCount > 0 ) {
			if( $this->ShowShippingCalculator )
				$this->SaveShipping( 0, "You should calculate shipping cost" );
		}
		else {
			// remove shipping, if cart is empty
			$Connection->Execute("delete from CartItem where CartID = {$this->ID} and TypeID = " . CART_ITEM_TYPE_SINGLE);
			$Connection->Execute("update Cart set Error = null where CartID = {$this->ID}");
		}
	}
	
	// remove item from cart
	function Remove( $nCartItemID )
	{
		global $Connection;
		$Connection->Execute("delete from CartItem where CartItemID = $nCartItemID");
		$this->Updated();
	}
	
	// add warning
	function AddWarning( $sText )
	{
		$this->Warnings[] = $sText;
		$_SESSION["CartWarnings"] = $this->Warnings;
	}
	
	// set fixed payment type
	function SetPaymentType( $nPaymentType )
	{
		global $Connection;
		$Connection->Execute( "update Cart set PaymentType = ".(isset($nPaymentType) ? $nPaymentType : "null")." where CartID = {$this->ID}");
		$this->PaymentType = $nPaymentType;
	}
	
	// calc totals
	function CalcTotals()
	{
		global $Connection;
		$this->ItemCount = 0;
		$this->Price = 0;
		$this->Names = null;
		$arNames = array();
		$arItems = array();
		$this->ItemRows = array();
		$this->ItemsCost = 0;
		if(!empty($this->ID)) {
			// delete missing pictures
			$q = new TQuery("show tables like 'Picture'");
			if (!$q->EOF)
				$Connection->Execute("delete from CartItem where CartID = {$this->ID} and TypeID = " . CART_PICTURE . " and ID not in(select PictureID from Picture)");
			$q = new TQuery("select * from CartItem where CartID = {$this->ID} order by TypeID, CartItemID");
			while (!$q->EOF) {
				$this->Price += round($q->Fields["Price"], 2) * $q->Fields["Cnt"];
				if ($q->Fields['TypeID'] != CART_ITEM_TYPE_SINGLE) {
					$this->ItemCount += $q->Fields["Cnt"];
					$this->ItemsCost += round($q->Fields["Price"], 2) * $q->Fields["Cnt"];
				} else
					if ($q->Fields['CategoryID'] == CART_CATEGORY_SHIPPING)
						$this->ShippingCost = $q->Fields["Price"];
				$arNames[] = $q->Fields["Name"];
				$arItems[$q->Fields["CartItemID"]] = array("TypeID" => $q->Fields["TypeID"], "ID" => $q->Fields["ID"]);
				$this->ItemRows[$q->Fields["CartItemID"]] = $q->Fields;
				$q->Next();
			}
		}
		if( count( $arNames ) > 0 )
			$this->Names = implode( "<br>", $arNames );
		else 
			$this->Names = null;
		$this->Items = $arItems;
		if( isset( $this->Coupon ) )
			$this->DiscountAmount = $this->CalcDiscount( $this->CouponID );
		else 
			$this->DiscountAmount = 0;
		$this->Total = $this->Price - $this->DiscountAmount;
		$this->ItemsCost -= $this->DiscountAmount;
	}
	
	// is item exists in Cart? call CalcTotals before this method
	function Exists( $nTypeID, $nID )
	{
		if( !isset( $this->Items ) )
			DieTrace("call CalcTotals before using Exists");
		return in_array( array( "TypeID" => $nTypeID, "ID" => $nID ), $this->Items );
	}
	
	// is item of this type exists in Cart ? call CalcTotals before this method
	function TypeExists( $nTypeID )
	{
		if( !isset( $this->Items ) )
			DieTrace("call CalcTotals before using Exists");
		foreach ( $this->Items as $arItem )			
			if( $arItem['TypeID'] == $nTypeID )
				return true;
		return false;
	}
	
	// calc how much discount does coupon to current selection
	function CalcDiscount( $nCouponID )
	{
		$q = new TQuery( "select sum( round( ci.Price * ci.Cnt * co.Discount / 100, 2 ) ) as DiscountAmount 
		from CartItem ci, Coupon co where co.CouponID = $nCouponID 
		and ci.CartID = {$this->ID}" );
		return $q->Fields["DiscountAmount"];
	}

	// check that coupon is applicable. return null on success or error message
	function CheckCoupon($arCoupon, $wantFree){
		// check uses count
		if( $arCoupon['MaxUses'] > 0 ){
			$q = new TQuery("select count(*) as Cnt from Cart where PayDate is not null and CouponID = {$arCoupon['CouponID']}");
			if( $q->Fields['Cnt'] >= $arCoupon['MaxUses'] )
				return "The number of times this coupon could be used has been exceeded by other AwardWallet members.";
		}
		// check that there are items in cart, matched coupon targets
		$discount = $this->CalcDiscount( $arCoupon["CouponID"] );
		if( $discount == 0 )
			return "Coupon does not match selected items";
		if($wantFree){
			$this->CalcTotals();
			if($discount != $this->Total)
				return "This coupon does not offer a 100% discount";
		}
		return null;
	}
	
	// apply coupon. return error message or null
	function ApplyCoupon( $sCode, $wantFree = false )
	{
		global $Connection;
		// find coupon
		$q = new TQuery( "select * from Coupon where Code = '". addslashes( $sCode ) . "'
		and ( StartDate is null or StartDate <= now() )
		and ( EndDate is null or EndDate > now() )" );
		if( $q->EOF )
			return "Invalid or expired coupon code";
		$arCoupon = $q->Fields;
		$error = $this->CheckCoupon($arCoupon, $wantFree);
		if(isset($error))
			return $error;
		$Connection->Execute( "update Cart set CouponID = {$arCoupon["CouponID"]}
		where CartID = {$this->ID}" );
		$this->Coupon = $arCoupon["Name"];
		$this->CouponID = $arCoupon["CouponID"];
		$this->Discount = $arCoupon['Discount'];
		$this->CalcTotals();
		return NULL;
	}

	function CanAddToCart( $nItemType, $nItemID ){
		return  !$this->Exists( $nItemType, $nItemID );
	}

	// save shipping
	function SaveShipping( $nShippingPrice, $sError = null ){
		global $Connection;
		$nShippingItemID = null;
		foreach ( $this->ItemRows as $nItemID => $arItem )
			if( ( $arItem['TypeID'] == CART_ITEM_TYPE_SINGLE ) && ( $arItem['CategoryID'] == CART_CATEGORY_SHIPPING ) )
				$nShippingItemID = $nItemID;
		// add shipping if do not have it
		if( !isset( $nShippingItemID ) )
			$this->Add( CART_ITEM_TYPE_SINGLE, CART_CATEGORY_SHIPPING, null, 'Shipping and Handling', 1, $nShippingPrice, null, null, null, array(), False );
		else
			$Connection->Execute("update CartItem set Price = $nShippingPrice where CartItemID = $nShippingItemID");
		$this->Error = $sError;
		if( isset( $sError ) )
			$sError = "'" . addslashes( $sError ) . "'";
		else
			$sError = "null";
		if( isset( $this->ShippingZip ) )
			$sShippingZip = "'" . addslashes( $this->ShippingZip ) . "'";
		else
			$sShippingZip = "null";
		if( isset( $this->ShippingDetails ) )
			$sShippingDetails = "'" . addslashes( $this->ShippingDetails ) . "'";
		else
			$sShippingDetails = "null";
		if( $sError != "null" )
			$sShippingDetails = "null";
		$Connection->Execute("update Cart set Error = $sError, ShippingZip = $sShippingZip, ShippingDetails = $sShippingDetails where CartID = {$this->ID}");
	}
	
	// calc shipping through freighquote.com
	function CalcShipping( $bSaveShipping = true ){
		$this->ShippingDetails = null;
		if( count( $this->Items ) > 0 ) {
			if( isset( $this->ShippingZip ) ){
				$objCalculator = new TBaseShippingCalculator();
				$arItems = array();
				foreach ( $this->ItemRows as $arRow )
					if( $arRow['TypeID'] != CART_ITEM_TYPE_SINGLE ) {
						$q = new TQuery( TProduct::LoadSQL( $arRow['ID'] ) );
						if( $q->EOF )
							DieTrace("Product not found: {$arRow['ID']}");
						$objProduct = new TProduct( $q->Fields, $q->Fields["ProductID"] );
						$arItems[] = array( 
							'ID' =>  $q->Fields["ProductID"],
							"Description" => $arRow['Name'], 
							'Volume' =>  $objProduct->OriginalFields['ShippingVolume'],
							'Weight' =>  $objProduct->WeightInPounds(),
							'Height' =>  $objProduct->DimensionInInches('ShippingHeight'),
							'Width' =>  $objProduct->DimensionInInches('ShippingWidth'),
							'Depth' =>  $objProduct->DimensionInInches('ShippingDepth'),
							'Count' =>  $arRow['Cnt'],
						);
					}
				$nCost = $objCalculator->Calculate( $arItems, STORAGE_ZIP, $this->ShippingZip );
				if( isset( $nCost ) ) {
					if( $objCalculator->UsePalletes ) {
						DeleteFiles( "$sPath/images/uploaded/palletes/cart-{$this->ID}*" );
						$objCalculator->SavePalletes( "/images/uploaded/palletes", "cart-" . $this->ID );
						file_put_contents( "$sPath/images/uploaded/palletes/cart-{$this->ID}.dmp", serialize( $objCalculator->Palletes ) );
						if( isset( $objCalculator->ShippingDetails ) )
							$this->ShippingDetails = "Will be shipped on " . $objCalculator->ShippingDetails;
						else 
							$this->ShippingDetails = null;
					}
					if( $bSaveShipping )
						$this->SaveShipping( $nCost, null );
				}
				else
					if( $bSaveShipping )
						$this->SaveShipping( 0, $objCalculator->Error );
			}
			else
				if( $bSaveShipping )
					$this->SaveShipping( 0, 'Please fill in shipping zip to calculate shipping cost' );
		}
	}
	
	/**
	 * is paid item of this type exists in user order history
	 * @param $userId int
	 * @param $typeIds array
	 * @return bool
	 */
	function UserPaidFor($userId, $typeIds){
		$q = new TQuery("select 1 from Cart c
		join CartItem ci on c.CartID = ci.CartID
		where c.UserID = {$userId} and c.PayDate is not null
		and ci.TypeID in (".implode(", ", $typeIds).")
		limit 1");
		return !$q->EOF;
	}

	/**
	 * is user ever used this coupon
	 */
	function UserUsedCoupon($userId, $couponCode){
		$q = new TQuery("select 1 from Cart
		where PayDate is not null and UserID = $userId
		and CouponCode = '".addslashes($couponCode)."'");
		return !$q->EOF;
	}

	function NameForPayPal(){
		return preg_replace("/<br[^>]*>/ims", ", ",
			preg_replace("/<br[^>]*>?\([^\)]+\)/ims", "", $this->Names)).", Order #".$this->ID;
	}

}

?>
