<?

class TCaptchaFieldManager extends TAbstractFieldManager
{

	// initialize field
	function CompleteField()
	{
		parent::CompleteField();
		$this->Field['Database'] = False;
	}

	// get field html
	function InputHTML($sFieldName = null, $arField = null)
	{
		return "<table border='0' cellpadding='0' cellspacing='0' style='border: none'><tr><td style='border: none;'>\n" . $this->Form->InputHTML( $this->FieldName ) . "</td><td style='border: none'>&nbsp;<img src='/lib/security/captchaImage.php' alt='' border='0'/></td></tr></table>";
	}

	// check field. return NULL or error message. called only when field is checked.
	function Check( &$arData )
	{
		$sCode = trim( ArrayVal( $arData, $this->FieldName ) );
		if( ( $sCode == "" ) || ( $sCode != ArrayVal($_SESSION, 'CaptchaCode') ) )
			return 'Invalid security code';
		return NULL;
	}

}

?>
