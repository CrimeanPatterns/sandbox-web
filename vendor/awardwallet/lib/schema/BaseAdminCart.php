<?

class TBaseAdminCartSchema extends TBaseSchema
{
	function TBaseAdminCartSchema(){
		global $arPaymentTypeName;
		parent::TBaseSchema();
		$this->TableName = "Cart";
		$this->ListClass = "TBaseAdminCartList";
		$this->Fields = array(
			"CartID" => array(
				"Caption" => "ID",
				"Type" => "integer",
				"filterWidth" => 30
			),
			"UserID" => array(
				"Caption" => "UserID",
				"Type" => "integer",
				"filterWidth" => 30,
                'FilterField' => 'c.UserID',
			),
			"PaymentType" => array(
				"Type" => "integer",
				"filterWidth" => 50,
				"Options" => $arPaymentTypeName,
			),
			"FirstName" => array(
				"Type" => "string",
				"Size" => 80,
				"filterWidth" => 50,
                'FilterField' => 'c.FirstName',
			),
			"LastName" => array(
				"Type" => "string",
				"Size" => 80,
				"filterWidth" => 80,
                'FilterField' => 'c.LastName',
			),
			"Email" => array(
				"Type" => "string",
				"Size" => 40,
				"filterWidth" => 120,
                'FilterField' => 'c.Email',
			),
			"PayDate" => array(
				"Type" => "datetime",
				"Sort" => "PayDate DESC",
			),
            'UserRegistrationDate' => [
                'Type' => 'string',
                'Database' => false,
                'Required' => false,
            ],
            'PayingUser' => [
                'Type' => 'string',
                'Database' => false,
                'Required' => false,
            ],
/*
			"CouponName" => array(
				"Type" => "string",
				"Size" => 40,
				"filterWidth" => 60,
			),
*/
			"ShippingAddress" => array(
				"Type" => "string",
				"Size" => 40,
				"filterWidth" => 60,
			),
			"CouponCode" => array(
				"Type" => "string",
				"Size" => 20,
				"filterWidth" => 60,
			),
/*
			"Comments" => array(
				"Type" => "string",
				"Size" => 250,
			),
*/
			"Processed" => array(
				"Type" => "boolean",
				"filterWidth" => 40,
			),
			"Total" => array(
				"Caption" => "Total",
				"Type" => "float",
				"Database" => false,
			)
		);
		$objCartManager = new TCartManager();
		if( !$objCartManager->ShowShippingAddress ){
			unset( $this->Fields["ShippingAddress"] );
		}
	}

	function GetListFields()
	{
		$arFields = $this->Fields;
		return $arFields;
	}

	function TuneList( &$list )
	{
		parent::TuneList( $list );
        $extendJoin = empty($_GET['Order']) ? ''
            : 'JOIN CartItem ci ON (ci.CartID = c.CartID AND ci.TypeID = ' . ((int) $_GET['Order']) . ')';
		$list->SQL = "select
			c.CartID,
			c.UserID,
			c.PaymentType,
			c.FirstName,
			c.LastName,
			c.Email,
			c.PayDate,
			/* c.CouponName, */
			c.CouponCode,
			c.Comments,
			c.BillingTransactionID,
		    concat( c.ShipFirstName, ' ', c.ShipLastName, '<br>',
		    c.ShipAddress1, '<br>',
		    c.ShipAddress2, '<br>',
		    c.ShipCity, '<br>',
		    c.ShipZip ) as ShippingAddress,
            NULL AS `Order`,
            u.CreationDateTime
		from
			Cart c
        left join Usr u on u.UserID = c.UserID
        {$extendJoin}
		where
			c.PayDate is not null
			[Filters]
		";
		$list->AllowDeletes = False;
		$list->CanAdd = False;
		$list->DefaultSort = "PayDate";
	}

	function GetFormFields()
	{
		global $QS;
		$arFields = array(
			"Processed" => array(
				"Type" => "boolean",
				"Required" => True,
			),
			"Comments" => array(
				"Type" => "string",
				"InputType" => "htmleditor",
				"Width" => 600,
				"Height" => 400,
				"HTML" => True,
				"Required" => False,
				"Size" => 10000,
			),
			"Actions" => array(
			"Database" => False,
			"Type" => "html",
			"HTML" => "<input class=button onclick=\"document.location.href = '/lib/admin/cart/processAgain.php?ID=".urlencode($QS["ID"])."'\" type=button name=ProcessAgain value='Process this order again'>",
			),
		);
		return $arFields;
	}
}
?>
