<?

require_once(__DIR__."/../../lib/cart/public.php");

class TBaseCartManager
{
	public $CouponError = False;
	public $ShowShippingAddress = False;
	public $ShowCoupons = True;
	public $CompleteScript = '/lib/cart/complete.php';
	public $allowTotalZero = false;
	var $formParams = "";

	function __construct()
	{

	}
	
	function initCreditCardPayment()
	{
	
	}

	// mark one cart item as paid. input: CartItem table row
	// do not suggest that that order belongs to $_SESSION['UserID'],
	// use $objCart to read information
	function MarkItemPaid( $arFields )
	{

	}

	function CreateCommonFields( $sPage ){
		global $objCart;
		$arFields = array();
		if( array_key_exists( "Notes", $objCart->Fields ) ) {
			$arFields += array(
				"Notes" => array(
					"Type" => "string",
					"InputType" => "textarea",
					"Size" => 8000,
					"Page" => $sPage,
					"InputAttributes" => "style='width: 100%;'",
					"Value" => $objCart->Fields["Notes"],
				),
			);
		}
		return $arFields;
	}

	// mark cart as payed. return CartID
	function MarkAsPayed()
	{
		global $objCart, $Connection, $nPayedCartID;
		// mark items as payed
		$q = new TQuery( "select * from CartItem where CartID = {$objCart->ID}" );
		while( !$q->EOF )
		{
			$this->MarkItemPaid( $q->Fields );
			$q->Next();
		}
		// move cart to archive
		$sSQL = "update Cart c, Usr u
		set
			c.PayDate = now(),
			c.FirstName = u.FirstName,
			c.LastName = u.LastName,
			c.Email = u.Email
		where
			u.UserID = c.UserID
			and c.CartID = {$objCart->ID}";
		$Connection->Execute( $sSQL );
		$sSQL = "update Cart c, Coupon co
		set
			c.CouponName = co.Name,
			c.CouponCode = co.Code
		where
			c.CouponID = co.CouponID
			and c.CartID = {$objCart->ID}";
		$Connection->Execute( $sSQL );
		// save applied discounts and coupons
		if( isset( $objCart->Coupon ) )
		{
			$sql = "select CartItemID from CartItem where CartID= {$objCart->ID}";
			$q = new TQuery($sql);
			while( !$q->EOF )
			{
				$Connection->Execute( "update CartItem set Discount = {$objCart->Discount}
				where CartItemID = {$q->Fields["CartItemID"]}" );
				$q->Next();
			}
		}
		// save shipping address
		if( $this->ShowShippingAddress )
		{
			$arAddress = $_SESSION['ShippingAddress'];
			$Connection->Execute("update Cart c
			set
				c.ShipFirstName = '" . addslashes( $arAddress['FirstName'] ) . "',
				c.ShipLastName = '" . addslashes( $arAddress['LastName'] ) . "',
				c.ShipAddress1 = '" . addslashes( $arAddress['Address1'] ) . "',
				c.ShipAddress2 = '" . addslashes( $arAddress['Address2'] ) . "',
				c.ShipCity = '" . addslashes( $arAddress['City'] ) . "',
				c.ShipZip = '" . addslashes( $arAddress['Zip'] ) . "',
				c.ShipCountryID = '" . addslashes( $arAddress['CountryID'] ) . "',
				c.ShipStateID = '" . addslashes( $arAddress['StateID'] ) . "'
			where
				c.CartID = {$objCart->ID}
			");
		}
		// save billing address
		$q = new TQuery("select * from Cart where CartID = {$objCart->ID}");
		if( isset($_SESSION['Address']) && array_key_exists( "BillFirstName", $q->Fields ) && in_array( $objCart->PaymentType, array( PAYMENTTYPE_TEST_CREDITCARD, PAYMENTTYPE_CREDITCARD ) ) ) {
			$arAddress = $_SESSION['Address'];
			$Connection->Execute("update Cart c
			set
				c.BillFirstName = '" . addslashes( $arAddress['FirstName'] ) . "',
				c.BillLastName = '" . addslashes( $arAddress['LastName'] ) . "',
				c.BillAddress1 = '" . addslashes( $arAddress['Address1'] ) . "',
				c.BillAddress2 = '" . addslashes( $arAddress['Address2'] ) . "',
				c.BillCity = '" . addslashes( $arAddress['City'] ) . "',
				c.BillZip = '" . addslashes( $arAddress['Zip'] ) . "',
				c.BillCountryID = '" . addslashes( $arAddress['CountryID'] ) . "',
				c.BillStateID = '" . addslashes( $arAddress['StateID'] ) . "'
			where
				c.CartID = {$objCart->ID}
			");
		}
	}

	function TuneForm( &$objForm ) {
		if( isset( $objForm->Fields["Login"] ) ) {
			$objForm->Uniques += array(
				array(
					"Fields" => array( "Login" ),
					"ErrorMessage" => "User with this login already exists. Please choose another login. If you already registered with " . SITE_NAME .", please follow one of these links:<br><ul><li><a href=/cart/authorize.php>Login to " . SITE_NAME ."</a></li><li><a href=/security/forgotPassword.php>I forgot my " . SITE_NAME ." password</a>",
					"Table" => "Usr",
					"KeyField" => "UserID",
				 ),
				array(
					"Fields" => array( "Email" ),
					"ErrorMessage" => "User with this email already exists. Please choose another email. If you already registered with " . SITE_NAME .", please <a href=/cart/authorize.php>Login</a>. <a href=/security/forgotPassword.php>Forgot password?</a>",
					"Table" => "Usr",
					"KeyField" => "UserID",
				 )
			);
		}
		if( !$this->ShowCoupons )
			unset( $objForm->Fields['CouponCode'] );
	}

	static public function CreateShippingFields( $sPage, $sOnButtonClick ){
		global $objCart;
		$arFields = array();
		$arFields["CalcShippingZip"] = array(
			"Type" => "string",
			"Caption" => "Shipping Zip",
			"MinSize" => 5,
			"MaxSize" => 11,
			"Required" => True,
			"Value" => $objCart->ShippingZip,
			"Size" => 11,
			"RegExp" => REGEXP_USA_ZIP,
			"Page" => $sPage,
			"OnButtonClick" => $sOnButtonClick,
			"InputAttributes" => "style='width: 60px' id='calcShippingZipInput'",
			"CheckScriptCondition" => "!Form.CalcShippingZip.disabled",
		);
		if( ( $_SERVER['REQUEST_METHOD'] == 'POST' )
		&& ( ArrayVal( $_POST, "ShippingAddressID" ) == "-1" ) )
			$arFields["CalcShippingZip"]["Required"] = False;
		return $arFields;
	}

	function CalcPhotoOrder(){
		global $objCart, $Connection;
		$nCartID = $objCart->ID;
		$q = new TQuery("select ci.*, p.ImageVer, p.ImageExt, pp.SizeName, p.ImageOriginalWidth, p.ImageOriginalHeight, pp.PictureCost from CartItem ci, Picture p, PicturePrice pp where ci.CartID = $nCartID and ci.ID = p.PictureID and ci.CategoryID = pp.PictureSize and ci.TypeID = " . CART_PICTURE );
		$nTotalPrice = 0;
		$nTotalCount = 0;
		$nMailPrice = 0;
		$bHaveLarge = False;
		while( !$q->EOF )
		{
			$nTotalPrice += $q->Fields["PictureCost"] * $q->Fields["Cnt"];
			// mail price
			if( preg_match( "/^(\d+(\.\d+)?)x(\d+(\.\d+)?)/i", $q->Fields["SizeName"], $arMatches ) ) {
				$nWidth = floatval( $arMatches[1] );
				$nHeight = floatval( $arMatches[3] );
				if( ( $nWidth >= 11 ) || ( $nHeight >= 14 ) )
					$bHaveLarge = True;
			}
			$nTotalCount += $q->Fields["Cnt"];
			$q->Next();
		}
		$arMailPriceList = array(
			1 => 0.99,
			11 => 1.99,
			21 => 2.99,
			51 => 3.99,
			101 => 4.99,
			151 => 6.99,
			201 => 8.99,
			251 => 9.99,
			301 => 10.99,
		);
		foreach ( $arMailPriceList as $nCount => $nPrice )
			if( $nTotalCount >= $nCount )
				$nMailPrice = $nPrice;
		$nProcessingPrice = 1.5;
		if( $bHaveLarge )
			$nMailPrice += 3;
		$nTotalPrice += $nMailPrice + $nProcessingPrice;
		$qOrder = new TQuery("select Max( PhotoOrderNumber ) as Num from Cart where PhotoOrderNumber is not null");
		$nInternalOrder = intval($qOrder->Fields["Num"]);
		if( $nInternalOrder <= 0 )
			$nInternalOrder = 1;
		else
			$nInternalOrder = $nInternalOrder + 1;
		$Connection->Execute("update Cart set PhotoOrderNumber = {$nInternalOrder}, PhotoTotal = {$nTotalPrice}, PhotoMailPrice = {$nMailPrice}, PhotoProcessingPrice = {$nProcessingPrice}, PhotoTotalCount = $nTotalCount where CartID = $nCartID");
	}

	function DrawContents()
	{
		global $objCart;
?>
<table cellspacing='0' align=center cellpadding='5' border='0' class='detailsTableDark cartContent' width="600">
	<tr bgcolor="<?=FORM_TITLE_COLOR?>"><td class="white">Item</td><td class="white">Price</td></tr>
	<?
	$q = new TQuery( "select * from CartItem where CartID = {$objCart->ID} order by TypeID, CartItemID" );
	while( !$q->EOF )
	{
		echo "<tr><td align=left>{$q->Fields["Name"]}";
		if( $q->Fields["Description"] != "" )
			echo "<br>" . $q->Fields["Description"];
		echo "</td>
		<td align=left>\$" . number_format( $q->Fields["Price"], 2, ".", "," ) . "</td></tr>\n";
		$q->Next();
	}
	if( isset( $objCart->Coupon ) )
	{
		echo "<tr><td align=left>Subtotal</td>
		<td align=left>\$" . number_format( $objCart->Price, 2, ".", "," ) . "</td></tr>\n";
		echo "<tr><td align=left>Coupon {$objCart->Coupon}, Discount {$objCart->Discount}%</td>
		<td align=left>-\$" . number_format( $objCart->DiscountAmount, 2, ".", "," ) ."</td></tr>\n";
	}
	?>
	<tr bgcolor="#fcf6ea" style="font-weight: bold;"><td align=left>Total:</td><td align=left style="font-weight: bold;">$<?=number_format( $objCart->Total, 2, ".", "," )?></td></tr>
</table>
<?
	}

	function DrawPayFree(){
		$this->DrawContents();
	}

	function DrawNotes( $objForm )
	{
		global $Interface;
		print "<br>";
		$Interface->drawSectionDivider("Special notes");
		echo "<br>";
		echo $objForm->InputHTML("Notes");
		echo "<br>";
	}

	function DrawAddressInfo( $sPrefix, $arAddress ){
		global $Interface;
		print "<br><div style=\"margin:0 auto; width: 600px;\">";
		$Interface->drawSectionDivider("{$sPrefix} address")?>
		</div><br>
		<? if( is_array( $arAddress ) ) { ?>
		<table align=center cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' width="600"<?=$this->formParams?>>
			<tr><td width="150">Full Name</td><td><?=$arAddress["FirstName"]?> <?=$arAddress["LastName"]?></td></tr>
			<tr><td>Address 1</td><td><?=$arAddress["Address1"]?></td></tr>
			<tr><td>Address 2</td><td><?=$arAddress["Address2"]?></td></tr>
			<tr><td>City</td><td><?=$arAddress["City"]?></td></tr>
			<tr><td>State</td><td><?=$arAddress["StateName"]?></td></tr>
			<tr><td>Country</td><td><?=$arAddress["CountryName"]?></td></tr>
			<tr><td>Zip</td><td><?=$arAddress["Zip"]?></td></tr>
		</table>
		<? } else { ?>
		I will pickup my order from the <?=SITE_NAME?> store<br>
		<? } ?>
		<?
	}

	function DrawNotesPreview( $objForm )
	{
		global $Interface;
		if( $objForm->Fields["Notes"]["Value"] == "" )
			return false;
		print "<br>";
		$Interface->drawSectionDivider("Special Notes")?>
		<br>
		<?=$objForm->Fields["Notes"]["Value"]?><br>
		<?
	}

	function DrawCreditCardPreview( $objForm, $arBillingAddress, $arShippingAddress ){
		global $Interface;
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td>
<?
			print "<div style=\"margin: 0 auto; width: 600px;\">";
			$Interface->drawSectionDivider("Your order");
			print "</div>";
			foreach( $objForm->Fields as $sFieldName => $arField  )
				echo "<input type=hidden name={$sFieldName} value=\"" . htmlspecialchars( ArrayVal( $arField, "Value" ) ) . "\">\r\n";
			$this->DrawContents();
			if( isset( $objForm->Fields["Notes"] ) )
				$this->DrawNotesPreview( $objForm );
			$this->DrawAddressInfo( "Billing", $arBillingAddress );
			if( $this->ShowShippingAddress )
				$this->DrawAddressInfo( "Shipping", $arShippingAddress );
			?>
			<br><div style="margin:0 auto; width: 600px;">
			<?$Interface->drawSectionDivider("Credit Card Info");?>
			</div><br>
			<table cellspacing='0' align=center cellpadding='5' border='0' class='detailsTableDark' width="600"<?=$this->formParams?>>
				<tr><td width="150">Credit Card Type</td><td><?=$objForm->Fields["CreditCardType"]["Value"]?></td></tr>
				<tr><td>Credit Card Number</td><td><?
				echo str_repeat( "X", strlen( $objForm->Fields["CreditCardNumber"]["Value"] ) - 4 );
				echo substr( $objForm->Fields["CreditCardNumber"]["Value"], strlen( $objForm->Fields["CreditCardNumber"]["Value"] ) - 4 );
				?></td></tr>
				<tr><td>Expiration Date</td><td><?=$objForm->Fields["ExpirationMonth"]["Value"]?>/<?=$objForm->Fields["ExpirationYear"]["Value"]?></td></tr>
			</table>
	</td>
</tr>
</table>
<?
	}

	function DrawAddressForm( $sAddressType, $objForm )
	{
		global $Interface;
?>
<br>
<?
if( isset( $_SESSION['UserID'] ) )
	$qAddress = new TQuery( "select ba.*, s.Name as StateName, c.Name as CountryName
from {$sAddressType}Address ba, State s, Country c
where ba.StateID = s.StateID and ba.CountryID = c.CountryID
and ba.UserID = {$_SESSION["UserID"]}" );

$sAddressTypeLow = strtolower( $sAddressType );
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="48%"><?$Interface->drawSectionDivider( "Saved $sAddressType Addresses" );?></td>
	<td width="15" style="border-right: dashed 1px #ADADAD;">&nbsp;</td>
	<td width="15">&nbsp;</td>
	<td width="47%"><?$Interface->drawSectionDivider("New $sAddressType Address");?></td>
</tr>
<tr>
	<td valign="top">
<?#begin existing addresses?>
<br>
<? if( isset( $_SESSION['UserID'] ) && !$qAddress->EOF ) { ?>
<Table border=0 cellspacing="5" width="100%">
<?
while( !$qAddress->EOF )
{
	echo "<tr valign=top><td><input type=radio name={$sAddressType}AddressID value={$qAddress->Fields["{$sAddressType}AddressID"]} onclick=\"AddressChanged( this.form, '{$sAddressType}', radioValue( this.form, '{$sAddressType}AddressID' ) == '0' )\"";
	if( $objForm->Fields["{$sAddressType}AddressID"]["Value"] == $qAddress->Fields["{$sAddressType}AddressID"] )
		echo " checked";
	echo "></td><td>";
	echo "<table cellspacing='0' cellpadding='2' border='0' class='detailsTable' width='100%'>";
	echo "<tr><td colspan='2'><b>{$qAddress->Fields["AddressName"]}</b> ";
	echo "[<a href=editAddress.php?ID={$qAddress->Fields["{$sAddressType}AddressID"]}&Type={$sAddressType}>Edit</a>] [<a onclick=\"return window.confirm('Delete {$sAddressTypeLow} address?')\" href=deleteAddress.php?ID={$qAddress->Fields["{$sAddressType}AddressID"]}&Type={$sAddressType}>Delete</a>]";
	echo "</td></tr>";
	echo "<tr><td width='150'>Full Name:</td><td>{$qAddress->Fields["FirstName"]} {$qAddress->Fields["LastName"]}</td></tr>";
	echo "<tr><td>Address 1:</td><td>{$qAddress->Fields["Address1"]}</td></tr>";
	if( $qAddress->Fields["Address2"] != "" )
		echo "<tr><td>Address 2:</td><td>{$qAddress->Fields["Address2"]}</td></tr>";
	echo "<tr><td>City:</td><td>{$qAddress->Fields["City"]}</td></tr>";
	echo "<tr><td>Zip:</td><td>{$qAddress->Fields["Zip"]}</td></tr>";
	echo "<tr><td>State:</td><td>{$qAddress->Fields["StateName"]}</td></tr>";
	echo "<tr><td>Country:</td><td>{$qAddress->Fields["CountryName"]}</td></tr></table>";
	echo "</td></tr>";
	$qAddress->Next();
}
?>
</Table>
<? } else { ?>
There are no <?=strtolower( $sAddressType )?> addresses saved with your <?=SITE_NAME?> profile. Please enter a <?=strtolower( $sAddressType )?> address in the form on the right. We will store it with your profile for future use.
<? } ?>
<?#end existing addresses?>
	</td>
	<td style="border-right: dashed 1px #ADADAD;">&nbsp;</td>
	<td>&nbsp;</td>
	<td valign="top">
<?#begin new address?>
<br>
<?
$showRadioButton = true;
if( ( count( $objForm->Fields["{$sAddressType}AddressID"]["Options"] ) == 1 ) && ( $sAddressType != "Shipping" ) ){
	$showRadioButton = false;
	echo "<input type=hidden name={$sAddressType}AddressID value=0>";
}
# this shows radio button, only works if there is already at leas one address saved...
else {
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td valign="top"><input type=radio name=<?=$sAddressType?>AddressID value="0" onclick="AddressChanged( this.form, '<?=$sAddressType?>', radioValue( this.form, '<?=$sAddressType?>AddressID' ) == '0' )"<? if( $objForm->Fields["{$sAddressType}AddressID"]["Value"] == "0" ) echo " checked"?>>&nbsp;</td>
	<td>
<?
}
?>
<?#begin the actual new address form?>
<table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' width="100%">
	<tr><td colspan=2 align=center><i>Fields marked with (*) are required</i></td></tr>
	<?
	echo $objForm->InputHTML( "{$sAddressType}AddressName", null, True );
	echo $objForm->InputHTML( "{$sAddressType}FirstName", null, True );
	echo $objForm->InputHTML( "{$sAddressType}LastName", null, True );
	echo $objForm->InputHTML( "{$sAddressType}Address1", null, True );
	echo $objForm->InputHTML( "{$sAddressType}Address2", null, True );
	echo $objForm->InputHTML( "{$sAddressType}City", null, True );
	echo $objForm->InputHTML( "{$sAddressType}CountryID", null, True );
	echo $objForm->InputHTML( "{$sAddressType}StateID", null, True );
	echo $objForm->InputHTML( "{$sAddressType}Zip", null, True );
	?>
</table>
<?#end the actual new address form?>
<?
if($showRadioButton){
?>
	</td>
</tr>
<? if( $sAddressType == "Shipping" ) { ?>
<tr><td colspan="2">&nbsp;</td></tr>
<tr>
	<td valign="top"><input type=radio name=<?=$sAddressType?>AddressID value="-1" onclick="AddressChanged( this.form, '<?=$sAddressType?>', radioValue( this.form, '<?=$sAddressType?>AddressID' ) == '0' )"<? if( $objForm->Fields["{$sAddressType}AddressID"]["Value"] == "-1" ) echo " checked"?>>&nbsp;</td>
	<td>I will pickup my order from the <?=SITE_NAME?> store</td>
</tr>
<? } ?>
</table>
<?
}
?>
<?#end new address?>
	</td>
</tr>
</table>
<br><br>
<?
	}

	function DrawCreditCardForm( $objForm )
	{
?>
<table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' align="center"<?=$this->formParams?>>
	<tr><td colspan=2 align=center><i>Fields marked with (*) are required</i></td></tr>
	<?
	echo $objForm->InputHTML( "CreditCardType", null, True );
	echo $objForm->InputHTML( "CreditCardNumber", null, True );
	echo $objForm->InputHTML( "SecurityCode", null, True );
	$s = '<table border="0" cellpadding="0" cellspacing="0"><tr><td>'.$objForm->InputHTML( "ExpirationMonth" ).'</td><td>&nbsp;</td>
	<td>'.$objForm->InputHTML( "ExpirationYear" ).'</td></tr></table>';
	$objForm->Fields["ExpirationYear"]["Caption"] = "Expiration Date";
	echo $objForm->FormatRowHTML("ExpirationYear", $objForm->Fields["ExpirationYear"], $s);
	$objForm->Fields["ExpirationYear"]["Caption"] = "Expiration Year";
	?>
</table>
<br>
<div class="fieldhint">* <?=SITE_NAME?> does not store your credit card information, therefore you have to specify your credit card each time you submit a new order. We are doing it to make our site more secure and to prevent any credit card fraud. We apologize for the inconvenience it causes you.</div>

<?
	}

	function DrawRegistrationForm( $objForm )
	{
		global $Interface;
		print "<br>";
		$Interface->drawSectionDivider("Create your account");
		echo "<br>";
?>
<table cellspacing='0' cellpadding='5' border='0' class='detailsTableDark' align="center"<?=$this->formParams?>>
	<tr><td colspan=2 align=center><i>Fields marked with (*) are required</i></td></tr>
	<?
	echo $objForm->InputHTML( "Login", null, True );
	echo $objForm->InputHTML( "Pass", null, True );
	echo $objForm->InputHTML( "PassConfirm", null, True );
	echo $objForm->InputHTML( "Email", null, True );
	?>
</table>
<br>
<div class="fieldhint">* <?=SITE_NAME?> requires this information to track your order history.</div>
<?
	}

	function DrawCouponForm( $objForm )
	{
?>
<div align="center" style="padding-left: 309px;"><table cellspacing="0" cellpadding="0" border="0" width="50" style="margin-top: 10px;">
<tr<? if( $this->CouponError ) echo " bgcolor = '#FCC9C9'"?>><td nowrap style="padding: 5px 0px 5px 5px;">
Coupon code:&nbsp;&nbsp;</td><td style="padding: 5px 0px;"><?=$objForm->InputHTML( "CouponCode" )?></td><td style="padding: 5px 0px 5px 5px;">
<input type=hidden name=addCouponButton value=""><input type=submit name=addCouponButtonTrigger value=Enter onclick="this.form.DisableFormScriptChecks.value='1'; this.form.NewFormPage.value = 'BillingInfo'; if( CheckForm( this.form ) ) { this.form.addCouponButton.value='submit'; return true; } else return false;" class=button style="height: 26px;">
</td></tr></table></div>
<br>
<?
	}

	function DrawCreditCardBillingInfo( $objForm )
	{
		global $Interface, $objCart;
		$this->DrawWarnings();
		print "<br><div style=\"margin: 0 auto; width: 600px;\">";
		$Interface->drawSectionDivider("Order Details");
		echo "</div><br>";
		$this->DrawContents();
		if( isset( $objForm->Fields["Notes"] ) )
			$this->DrawNotes( $objForm );
		if( $this->ShowCoupons )
			$this->DrawCouponForm( $objForm );
		if( $objCart->Anonymous && !isset( $_SESSION['UserID'] ) )
			$this->DrawRegistrationForm( $objForm );
		if( $this->ShowShippingAddress )
			$this->DrawAddressForm( "Shipping", $objForm );
		$this->DrawAddressForm( "Billing", $objForm );
		print "<br><div style=\"margin: 0 auto; width: 358px;\">";
		$Interface->drawSectionDivider( "Credit Card Info" );
		print "<br></div>";
		$this->DrawCreditCardForm( $objForm );
		$this->DrawAddressScripts();
	}

	function CheckForm( &$objForm ) {
		global $objCart;
		if( isset( $objForm->Fields['CalcShippingZip'] ) ) {
			if( $this->ShowShippingAddress ) {
				if( $_POST['ShippingAddressID'] > 0 )
					$sShippingZip = Lookup( 'ShippingAddress', 'ShippingAddressID', 'Zip', intval( $_POST['ShippingAddressID'] ), True );
				else
					$sShippingZip = $objForm->Fields['ShippingZip']['Value'];
				if( ( intval( $_POST['ShippingAddressID'] ) != -1 )
				&& ( substr( $objForm->Fields['CalcShippingZip']['Value'], 0, 5 ) != substr( $sShippingZip, 0, 5 ) ) ) {
					$objForm->Fields['CalcShippingZip']['Error'] = "You opted to calculate shipping price using the following zip code '{$objForm->Fields['CalcShippingZip']['Value']}', however your shipping address has another zip code '{$sShippingZip}', please either calculate the shipping price again or correct your shipping address.";
					return $objForm->Fields['CalcShippingZip']['Error'];
				}
			}
			if( ( $objForm->Fields['CalcShippingZip']['Value'] != $objCart->ShippingZip )
			|| ( $objCart->ShippingCost == 0 )
			|| ( intval( $_POST['ShippingAddressID'] ) == -1 ) ) {
				$objCart->ShippingZip = $objForm->Fields['CalcShippingZip']['Value'];
				if( intval( $_POST['ShippingAddressID'] ) != -1 )
					$objCart->CalcShipping();
				else {
					$objCart->ShippingDetails = null;
					$objCart->ShippingZip = null;
					$objCart->SaveShipping( 0, null );
				}
				$objCart->CalcTotals();
			}
		}
		if( isset( $objCart->Error ) ){
			$objForm->Fields['CalcShippingZip']['Error'] = $objCart->Error;
			return $objCart->Error;
		}
		// check blocked payer
		$q = new TQuery("show tables like 'BlockPayer'");
		if( !$q->EOF ){
			$sFilter = "IP = '{$_SERVER['REMOTE_ADDR']}'";
			if( ArrayVal( $_SESSION, 'Email' ) != "" )
				$sFilter .= " or Email = '".addslashes( $_SESSION['Email'] )."'";
			if( ( ArrayVal( $_SESSION, 'FirstName' ) != "" ) && ( ArrayVal( $_SESSION, 'LastName' ) != "" ) )
				$sFilter .= " or FullName = '".addslashes( $_SESSION['FirstName'] . " " . $_SESSION['LastName'] )."'";
			if( ArrayVal( $_SESSION, 'UserID' ) != "" )
				$sFilter .= " or UserID = '".addslashes( $_SESSION['UserID'] )."'";
			if( isset( $objForm->Fields["CreditCardNumber"] ) )
				$sFilter .= " or CreditCardNumber = '".addslashes( $objForm->Fields["CreditCardNumber"]["Value"] )."'";
			$q->Open("select * from BlockPayer where $sFilter");
			if( !$q->EOF ){
				return "We are sorry; you have been flagged in our system and currently you are not allowed to purchase items from our store. If you believe this was done in an error please feel free to <a href=\"".ConfigValue(CONFIG_CONTACT_SCRIPT)."\">contact us</a>.";
			}
		}
		return null;
	}

	function DrawWarnings()
	{
		global $objCart, $Interface;
		if( count( $objCart->Warnings ) > 0 )
			foreach ( $objCart->Warnings as $sWarning )
				$Interface->DrawMessage( $sWarning, "warning" );
	}

	function DrawHeader()
	{
		global $bSecuredPage, $objCart;
		if( $objCart->Anonymous )
			$bSecuredPage = False;
		require(__DIR__ . "/../../design/header.php");
	}

	function DrawFooter()
	{
		require(__DIR__ . "/../../design/footer.php");
	}

	function GetMailItemName($fields){
		return strip_tags($fields["Name"]);
	}

	function OrderDetailsText(){
		global $objCart;
		$q = new TQuery("select * from CartItem where CartID = {$objCart->ID} order by TypeID");
		$nTotal = 0;
		$sText = "";
		while( !$q->EOF ){
			if( $q->Fields["TypeID"] == CART_ITEM_TYPE_SINGLE )
				$sText .= sprintf( "%-50s  %10s %5s \$%-10.2f\n", $this->GetMailItemName($q->Fields), "", "", $q->Fields["Price"] * $q->Fields["Cnt"] );
			else
				$sText .= sprintf( "%-50s \$%-10.2f %-5d \$%-10.2f\n", $this->GetMailItemName($q->Fields), $q->Fields["Price"], $q->Fields["Cnt"], $q->Fields["Price"] * $q->Fields["Cnt"] );
			$nTotal += $q->Fields["Price"] * $q->Fields["Cnt"];
			$q->Next();
		}
		if( $objCart->Coupon != "" ){
			$sText .= sprintf( "%-50s  %-10s %5s -\$%-10.2f\n", "Coupon '{$objCart->Coupon}'", "", "", $objCart->DiscountAmount );
			$nTotal -= $objCart->DiscountAmount;
		}
		$sText .= sprintf( "%-50s  %-10s %5s \$%-10.2f\n", "Total", "", "", $nTotal );
		return $sText;
	}

	function SendMailCCPaymentComplete()
	{
		global $objCart;
		$arAddress = $_SESSION["Address"];
		$arShippingAddress = $_SESSION["ShippingAddress"];
		$arFormValues = $_SESSION["FormValues"];
		$sPrice = number_format( $objCart->Total, 2 );
		$sName = str_replace( "<br>", ", ", $objCart->Names );
		$nOrderNumber = $objCart->ID;
		if( isset( $objCart->Fields["PhotoOrderNumber"] ) && ( $objCart->Fields["PhotoOrderNumber"] != "" ) )
			$nOrderNumber = sprintf( PEPHOTO_ORDER_FORMAT, $objCart->Fields["PhotoOrderNumber"] );
		$sText = " Thank You! You have been charged \$$sPrice for Order ID: $nOrderNumber:

Your order:
-------------------------\n";
$sText .= $this->OrderDetailsText();

if( ArrayVal( $objCart->Fields, "Notes" ) != "" )
	$sText .= "\nSpecial Notes:
-------------------------
{$objCart->Fields["Notes"]}
";

$sText .= "\nBilling Address:
-------------------------
{$arAddress["FirstName"]} {$arAddress["LastName"]}
Address 1: {$arAddress["Address1"]}
Address 2: {$arAddress["Address2"]}
City: {$arAddress["City"]}
State: {$arAddress["StateName"]}
Country: {$arAddress["CountryName"]}
Zip: {$arAddress["Zip"]}
";

		if( $this->ShowShippingAddress ) {
			$sText .= "
Shipping Address:
-------------------------
";
			if( is_array( $arShippingAddress ) )
				$sText .= "{$arShippingAddress["FirstName"]} {$arShippingAddress["LastName"]}
Address 1: {$arShippingAddress["Address1"]}
Address 2: {$arShippingAddress["Address2"]}
City: {$arShippingAddress["City"]}
State: {$arShippingAddress["StateName"]}
Country: {$arShippingAddress["CountryName"]}
Zip: {$arShippingAddress["Zip"]}
";
			else
				$sText .= "I will pickup my order from the " . SITE_NAME . " store
";
		}

		$sText .= "-------------------------
Credit Card Info:
-------------------------
Credit Card Type: {$arFormValues["CreditCardType"]}
Credit Card Number: " . str_repeat( "X", strlen( $arFormValues["CreditCardNumber"] ) - 4 ) . substr( $arFormValues["CreditCardNumber"], strlen( $arFormValues["CreditCardNumber"] ) - 4 ) . "
Expiration Date: {$arFormValues["ExpirationMonth"]}/{$arFormValues["ExpirationYear"]}
-------------------------
This charge will appear on your credit card statement as AWARDWALLET
";
		mailTo( $_SESSION["Email"], SITE_NAME . " Order ID: $nOrderNumber", html_entity_decode( $sText ), EMAIL_HEADERS );
		mail( ConfigValue(CONFIG_SALES_EMAIL), SITE_NAME . " Order ID: $nOrderNumber", html_entity_decode( $sText . "
User: {$_SESSION['FirstName']} {$_SESSION['LastName']} ({$_SESSION['UserID']})
"), EMAIL_HEADERS );
	}

	function SendMailPaypalPaymentComplete()
	{
		global $objCart;
		$arShippingAddress = $_SESSION["ShippingAddress"];
		$arFormValues = $_SESSION["FormValues"];
		$sPrice = number_format( $objCart->Total, 2 );
		$sName = str_replace( "<br>", ", ", $objCart->Names );
		$nOrderNumber = $objCart->ID;
		if( isset( $objCart->Fields["PhotoOrderNumber"] ) && ( $objCart->Fields["PhotoOrderNumber"] != "" ) )
			$nOrderNumber = sprintf( PEPHOTO_ORDER_FORMAT, $objCart->Fields["PhotoOrderNumber"] );
		$sText = " Thank You! You have been charged \$$sPrice for Order ID: $nOrderNumber:

Your order:
-------------------------\n";
$sText .= $this->OrderDetailsText();

if( ArrayVal( $objCart->Fields, "Notes" ) != "" )
	$sText .= "\nSpecial Notes:
-------------------------
{$objCart->Fields["Notes"]}
";

		if( $this->ShowShippingAddress ) {
			$sText .= "
Shipping Address:
-------------------------
";
			if( is_array( $arShippingAddress ) )
				$sText .= "{$arShippingAddress["FirstName"]} {$arShippingAddress["LastName"]}
Address 1: {$arShippingAddress["Address1"]}
Address 2: {$arShippingAddress["Address2"]}
City: {$arShippingAddress["City"]}
State: {$arShippingAddress["StateName"]}
Country: {$arShippingAddress["CountryName"]}
Zip: {$arShippingAddress["Zip"]}
";
			else
				$sText .= "I will pickup my order from the " . SITE_NAME . " store
";
		}

		$sText .= "-------------------------
Payment method: PayPal
-------------------------
This charge will appear on your paypal history as AWARDWALLET
";
		mail( $_SESSION["Email"], SITE_NAME . " Order ID: $nOrderNumber", html_entity_decode( $sText ), EMAIL_HEADERS );
		mail( ConfigValue(CONFIG_SALES_EMAIL), SITE_NAME . " Order ID: $nOrderNumber", html_entity_decode( $sText."
User: {$_SESSION['FirstName']} {$_SESSION['LastName']} ({$_SESSION['UserID']})
" ), EMAIL_HEADERS );
	}

	function CompletePayment()
	{
		global $objCart, $Connection;
		$this->MarkAsPayed();
		$objCart->OpenByID($objCart->ID);
		$objCart->CalcTotals();
		if( ( $objCart->PaymentType == PAYMENTTYPE_CREDITCARD ) || ( $objCart->PaymentType == PAYMENTTYPE_TEST_CREDITCARD ) || ( $objCart->PaymentType == PAYMENTTYPE_RECURLY ) )
			$this->SendMailCCPaymentComplete();
		if( ( $objCart->PaymentType == PAYMENTTYPE_PAYPAL ) || ( $objCart->PaymentType == PAYMENTTYPE_TEST_PAYPAL ) || ( $objCart->PaymentType == PAYMENTTYPE_BITCOIN ) )
			$this->SendMailPaypalPaymentComplete();
		if(isset($_SESSION["ref"])){
			// track ad
			$qSiteAd = new TQuery("select * from SiteAd limit 1");
			if( !$qSiteAd->EOF
			&& array_key_exists( "Purchases", $qSiteAd->Fields )
			&& array_key_exists( "LastPurchase", $qSiteAd->Fields )
			&& array_key_exists( "TotalAmount", $qSiteAd->Fields ) )
				$Connection->Execute("UPDATE SiteAd SET Purchases = coalesce( Purchases, 0 ) + 1, LastPurchase = now(), TotalAmount = coalesce( TotalAmount, 0 ) + {$objCart->Total} WHERE SiteAdID = " . $_SESSION["ref"]);
			if( array_key_exists( "CameFrom", $objCart->Fields ) )
				$Connection->Execute( "update Cart set CameFrom = {$_SESSION["ref"]} where CartID = {$objCart->ID}" );
		}
		echo "<html><body onload=\"parent.location.href = '" . $this->CompleteScript . "?ID=" . $objCart->ID . "&Complete=1'\">\n";
		echo "</body></html>";
	}

	function DrawAddressScripts()
	{
?>
<script defer="true">
initAddress();

function initAddress(){
	if( typeof( libScriptsLoaded ) == 'undefined' ) {
		setTimeout( 'initAddress()', 300 );
		return;
	}
	AddressChanged( document.forms['editor_form'], 'Billing', radioValue( document.forms['editor_form'], 'BillingAddressID' ) == '0' );
<? if( $this->ShowShippingAddress ) { ?>
	AddressChanged( document.forms['editor_form'], 'Shipping', radioValue( document.forms['editor_form'], 'ShippingAddressID' ) == '0' )
<? } ?>
}

// enable/disable location fields
function AddressChanged( form, type, enabled )
{
	if( !form[type + "AddressID"] )
		return;
	if( form[type + "AddressID"].type == "hidden" )
		return;
	form[type + 'City'].disabled = !enabled;
	form[type + 'CountryID'].disabled = !enabled;
	form[type + 'StateID'].disabled = !enabled;
	form[type + 'Zip'].disabled = !enabled;
	form[type + 'FirstName'].disabled = !enabled;
	form[type + 'LastName'].disabled = !enabled;
	form[type + 'AddressName'].disabled = !enabled;
	form[type + 'Address1'].disabled = !enabled;
	form[type + 'Address2'].disabled = !enabled;
	if( type == 'Shipping' ) {
		var AddressID = radioValue( form, type + 'AddressID' );
		var shippingZip = document.getElementById('calcShippingZipInput');
		if( shippingZip ) {
			var enableShipping = ( AddressID != "-1" );
			shippingZip.disabled = !enableShipping;
			var button = document.getElementById('calcShippingZipButton');
			button.disabled = !enableShipping;
		}
	}
	return;
}

</script><?
	}

	function DrawPayPalForm( $objForm )
	{
		global $objCart, $Interface;
?>
<form method=post name=editor_form onsubmit="submitonce(this)">
<input type=hidden name=submitButton>
<input type=hidden name=CalcNewShipping value=''>
<input type='hidden' name='FormToken' value='<?=GetFormToken()?>'>
<input type=hidden name=DisableFormScriptChecks value=0>
<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
<tr>
	<td align="center">
<?
if( isset( $objForm->Error ) )
	$Interface->DrawMessage($objForm->Error, "error")
?>
	</td>
</tr>
<tr>
	<td><?$this->DrawWarnings();?></td>
</tr>
<tr>
	<td height="25"><?$Interface->drawSectionDivider("Your order details:")?><br></td>
</tr>
<tr>
	<td><?$this->DrawContents();?></td>
</tr>
<? if( $this->ShowCoupons ) { ?>
<tr>
	<td height="30" align="center"><br>Enter a new coupon code: <?=$objForm->InputHTML( "CouponCode" )?> <input type=hidden name=addCouponButton><input style="height: 17px;" type=submit name=addCouponButtonTrigger value=Enter onclick="if( CheckForm( this.form ) ) { this.form.addCouponButton.value='submit'; return true; } else return false;" class=button></td>
</tr>
<? }
if( isset( $objForm->Fields["Notes"] ) ) {
	echo "<tr><td>";
	$this->DrawNotes( $objForm );
	echo "</td></tr>";
}
if( $objCart->Anonymous && !isset( $_SESSION['UserID'] ) ) {
	echo "<tr><td>";
	$this->DrawRegistrationForm( $objForm );
	echo "</td></tr>";
}
?>
<tr>
	<td>
<br>
<?
if( $this->ShowShippingAddress )
	$this->DrawAddressForm( "Shipping", $objForm );
?>
<? $this->DrawAddressScripts(); ?>
<div align="center">
<?#$Interface->DrawButton2("Proceed to PayPal.com for Authentication", "name=submitButtonTrigger onclick=\"if( CheckForm( document.forms['editor_form'] ) ) { this.form.submitButton.value='submit'; return true; } else return false;\"")?>
<?=$objForm->ButtonsHTML()?></div>
	</td>
</tr>
</table>
</form>
<?=$objForm->CheckScripts()?>
<?
	}

	// called on every post. after form was checked.
	function ProcessCheckedForm( &$objForm ){
		global $objCart, $Connection;
		if( isset( $objForm->Fields["Notes"] ) ) {
			$objForm->CalcSQLValues();
			$Connection->Execute("update Cart set Notes = " . $objForm->Fields["Notes"]["SQLValue"] . " where CartID = {$objCart->ID}" );
			$objCart->Fields["Notes"] = $objForm->Fields["Notes"]["Value"];
		}
		if( ArrayVal( $_POST, 'CalcNewShipping' ) == '1' ) {
			if( !preg_match( REGEXP_USA_ZIP, $objForm->Fields['CalcShippingZip']['Value'] ) ){
				$objForm->Fields['CalcShippingZip']['Error'] = 'Invalid shipping zip';
				$objForm->Error = $objForm->Fields['CalcShippingZip']['Error'];
				return;
			}
			$q = new TQuery("select * from Cart where CartID = {$objCart->ID}");
			$objForm->CalcSQLValues();
			$objCart->ShippingZip = $objForm->Fields['CalcShippingZip']['Value'];
			$objCart->CalcShipping();
			$objCart->Open();
			$objCart->CalcTotals();
			if( isset( $objCart->Error ) ) {
				$objForm->Error = $objCart->Error;
				$objForm->Fields['CalcShippingZip']['Error'] = $objForm->Error;
			}
		}
		if( ArrayVal( $_POST, 'DisableFormScriptChecks' ) != '1' ) {
			// update state tax
			foreach ( $objCart->ItemRows as $arItem ) {
				if( ( $arItem['TypeID'] == CART_ITEM_TYPE_SINGLE ) && ( $arItem['CategoryID'] == CART_CATEGORY_STATE_TAX ) ){
					// zero or not?
					$arShippingAddress = GetAddressInfo( "Shipping" );
					if( !$arShippingAddress || ( $arShippingAddress['StateName'] == 'Ohio' ) )
						$nPrice = round( $objCart->ItemsCost * OHIO_TAX / 100, 2 );
					else
						$nPrice = 0;
					$Connection->Execute("update CartItem set Price = $nPrice where CartItemID = {$arItem['CartItemID']}");
					$objCart->CalcTotals();
				}
			}
		}
	}

	function DrawSelectPaymentType(&$objForm){
		global $Interface;
		echo "<div align=\"center\"><br>";
		print "<br><div style=\"margin: 0 auto; width: 600px;\">";
		$Interface->drawSectionDivider("Select Payment Type");
		print "</div><br>";
		echo $objForm->HTML();
		echo "</div>";

	}

	function DrawPaypalConfirmation(&$details){
		global $objCart;
		?>
		<form method=post onsubmit="submitonce(this)">
        <input type='hidden' name='FormToken' value='<?=GetFormToken()?>'>
		<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
		<tr>
			<td valign="bottom" align="center"><br>
		<table cellspacing="0" cellpadding="5" border="5" width="330" class="detailsTableDark">
		<tr>
			<td bgcolor="#A02831" class="white" align="center" style="font-weight: bold;">Confirmation</td>
		</tr>
		<tr>
			<td align="center" height="70" style="line-height: 20px;">
		Dear <?=$details->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerName->FirstName?> <?=$details->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerName->LastName?>, do you want to pay <b>$<?=$objCart->Total?></b> for <b><?=$objCart->Names?></b>?
			</td>
		</tr>
		<tr>
			<td align="center">
			<input class="button" type=button name=cancel value=Cancel onclick="window.location.href='/'">
			<input type=submit name=s1 value=Pay class="button">
			</td>
		</tr>
		</table>
			</td>
		</tr>
		</table>
		</form>
		<?
	}

	function DrawFreePage(){
		global $Interface;
		?>
		<div align="center">
		<form method=post>
        <input type='hidden' name='FormToken' value='<?=GetFormToken()?>'>
		<?
		$this->DrawWarnings();
		print "<br><div style=\"margin: 0 auto; width: 600px;\">";
		$Interface->drawSectionDivider("Free upgrade confirmation");
		print "</div>";
		print "<br><div style=\"margin: 0 auto; width: 600px;\">\n";
		$this->DrawPayFree();
		?>
		</div><br>
		<input type=submit name=pay value=Continue class=button></div>
		<?
	}

	function SelectPaymentTypeForm(){
		global $arPaymentType;
		return new TForm( array(
			"PaymentType" => array(
				"Type" => "integer",
				"InputType" => "radio",
				"Options" => $arPaymentType,
				"Required" => True,
			),
		) );
	}

	function preparePayPalRequest(){
		global $objCart;

		$OrderTotal =& PayPal::getType('BasicAmountType');
		if (PayPal::isError($OrderTotal))
		    DieTrace( "Error getting paypal BasicAmountType", true, 0, $OrderTotal );
		$OrderTotal->setattr('currencyID', 'USD');
		$OrderTotal->setval($objCart->Total, 'iso-8859-1');

		$SetExpressCheckoutRequestDetails =& PayPal::getType('SetExpressCheckoutRequestDetailsType');
		if (PayPal::isError($SetExpressCheckoutRequestDetails))
		    DieTrace( "Error getting paypal SetExpressCheckoutRequestDetailsType", true, 0, $SetExpressCheckoutRequestDetails );
		$SetExpressCheckoutRequestDetails->setCancelURL("http://{$_SERVER["HTTP_HOST"]}/lib/cart/cancelPayPal.php", 'iso-8859-1');
		$SetExpressCheckoutRequestDetails->setReturnURL("http://{$_SERVER["HTTP_HOST"]}/lib/cart/acceptPayPal.php", 'iso-8859-1');
		$SetExpressCheckoutRequestDetails->setOrderTotal($OrderTotal);
		$SetExpressCheckoutRequestDetails->setBuyerEmail($_SESSION["Email"], 'iso-8859-1');
		$SetExpressCheckoutRequestDetails->setNoShipping('1', 'iso-8859-1');
		$SetExpressCheckoutRequestDetails->setOrderDescription($objCart->NameForPayPal(), 'iso-8859-1');
		return $SetExpressCheckoutRequestDetails;
	}

	function completePayPalRequest($caller, $token){

	}

}
