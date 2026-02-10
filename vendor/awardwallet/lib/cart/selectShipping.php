<?

// -----------------------------------------------------------------------
// checkout shopping cart, using credit card
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require( "../../kernel/public.php" );

AuthorizeUser();

$Interface->requireHTTPS = true;
require_once( "$sPath/lib/cart/public.php" );
require( "$sPath/kernel/TForm.php" );
require( "$sPath/lib/cart/common.php" );
if( file_exists( "$sPath/kernel/transactionFunctions.php" ) )
	require_once( "$sPath/kernel/transactionFunctions.php" );
require_once("$sPath/kernel/TCartManager.php");

$Interface->SecuredPage = True;

NDSkin::setLayout("@AwardWalletMain/Layout/hidden_left_menu.html.twig");

//if (!isset($_SESSION['UserID']) && !isset( $_SESSION['AuthorizeSuccessURL'] ) ) {
//	$_SESSION['AuthorizeCause'] = 'You need to authorize to proceed with Payment by Credit Card';
//	$_SESSION['AuthorizeSuccessURL'] = $_SERVER['SCRIPT_NAME'];
//	ScriptRedirect("/cart/authorize.php");
//}
//
$objCart->autoAddOneCards = false;
$objCart->CalcTotals();
$objCartManager = new TCartManager();
$objCartManager->CheckShippingPage();
if( $objCart->Total != 0 )
	DieTrace("This page is used only for free shipping", true, 0, $objCart);

// construct form
$arFields = array();
$arFields +=  CreateAddressFields( "( radioValue( Form, 'ShippingAddressID' ) == '0' )", "BillingInfo", "NewShippingAddressRequired", "Shipping", "Shipping" );
$objForm = new TForm( $arFields, False );

if( isset( $_SESSION['UserID'] ) )
	$objForm->Filters["UserID"] = $_SESSION["UserID"];
$objForm->Pages = array(
	"BillingInfo" => "Billing Info",
	"Confirmation" => "Confirmation",
);
$objForm->CompleteFields();
$objForm->SubmitButtonCaption = "Place order";
$objForm->OnCheck = "CheckForm";
$objForm->ShowPageOnButtons = false;
$objCartManager->TuneForm( $objForm );
$objCartManager->ShowCoupons = false;

// form returned from progress form
if( isset( $QS["ReturnStatus"] ) )
{
	unset( $_SESSION['FormValues']['SecurityCode'] );
	$objForm->LoadPostData( $_SESSION["FormValues"] );
	$objForm->Fields["BillingStateID"]["Value"] = $_SESSION["FormValues"]["BillingStateID"];
	$objForm->CompleteField( "BillingStateID", $objForm->Fields["BillingStateID"] );
	if( $objCartManager->ShowShippingAddress )
	{
		$objForm->Fields["ShippingStateID"]["Value"] = $_SESSION["FormValues"]["ShippingStateID"];
		$objForm->CompleteField( "ShippingStateID", $objForm->Fields["ShippingStateID"] );
	}
	$objForm->Error = $QS["Error"];
	$arFields = explode( ",", $QS["Fields"] );
	foreach( $arFields as $sField )
		if( isset( $objForm->Fields[$sField] ) )
			$objForm->Fields[$sField]["Error"] = True;
}

if( $objForm->IsPost && $objForm->Check() ) {
	$objCartManager->ProcessCheckedForm( $objForm );
	if( !isset( $_POST["prevButton"] ) && ( $objForm->ActivePage == "Confirmation" ) )
		if( ArrayVal( $_POST, "submitButton" ) != "" ){
            $arShippingAddress = GetAddressInfo( "Shipping" );
            $_SESSION["ShippingAddress"] = $arShippingAddress;
			$_SESSION["FormValues"] = $objForm->GetFieldValues();
            SaveAddress( "Shipping" );
            $objCartManager->MarkAsPayed();
            $objCartManager->ShippingComplete();
		}
		else
		{
			SaveAddress( "Shipping" );
		}
}
$objForm->UpdatePages();
if( $objForm->IsPost && !isset( $objForm->Error ) ) {
	$arShippingAddress = GetAddressInfo( "Shipping" );
}

$sTitle = "Specify your shipping address"; /*checked*/

$objCartManager->DrawHeader();

echo '<div align="center" class="cartCenter">';

if( isset( $objForm->Error ) )
	$Interface->DrawMessage( $objForm->Error, "error" );
	//echo "<p align=center class=formError><br>{$objForm->Error}</p>";
?>
	<form method=post name=editor_form onsubmit="submitonce(this)" style="margin-top: 0px; margin-bottom: 0px;">
	<input type=hidden name=DisableFormScriptChecks value=0>
	<input type=hidden name=submitButton>
    <input type='hidden' name='FormToken' value='<?=GetFormToken()?>'>
	<input type=hidden name=FormPage value=<?=$objForm->ActivePage?>>
	<input type=hidden name=NewFormPage value=<?=$objForm->ActivePage?>>
	<input type=hidden name=CalcNewShipping value=''>

<? if( $objForm->ActivePage == "BillingInfo" ) {
	$objCartManager->DrawShippingInfo( $objForm );
 } else { // preview
	$objCartManager->DrawShippingPreview( $objForm, $arShippingAddress );
	 } ?>
<div><?
$s = $objForm->ButtonsHTML();
echo $s;
?></div>
	</form>
<?=$objForm->CheckScripts()?>
<?

// check form
function CheckForm()
{
	global $objForm, $Connection, $arBillingAddress, $objCart, $objCartManager;
	$sError =  $objCartManager->CheckForm( $objForm );
	if( isset( $sError ) )
		return $sError;
	return null;
}

echo '</div>';

$objCartManager->DrawFooter();
?>
