<?
class TFaqSchema extends TBaseSchema
{
	function TFaqSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "Faq";
		$this->KeyField = "FaqID";
		$this->Description = array("Resources", "FAQs");
		$this->Fields = array(
			"FaqID" => array(
				"Caption" => "id",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True
			),
			"FaqCategoryID" => array( 
				"Caption" => "Category",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Options" => SQLToArray( "SELECT FaqCategoryID, CategoryTitle FROM FaqCategory",  "FaqCategoryID", "CategoryTitle")
			),
			"Question" => array(
				"Caption" => "Question",
				"Type" => "string",
				"InputType" => "textarea",
				"Width" => 600,
				"Height" => 200,
				"InputAttributes" => "style=\"width: 600px; height:100;\"",
				"HTML" => True,
				"Required" => True,
				"Size" => 4000
			),
			"Answer" => array(
				"Caption" => "Answer",
				"Type" => "string",
				"InputType" => "htmleditor",
				"Width" => 600,
				"Height" => 400,
				"HTML" => True,
				"Required" => True,
				"Size" => 10000
			),
			"Rank" => array(
				"Caption" => "Rank",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Value" => 50,
				"Required" => True
			),
			"Visible" => array( 
				"Caption" => "Visible",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Options" => array( "1" => "Visible", 0 => "Hidden" )
			),
            'Mobile' => [
                'Caption'   => 'Mobile',
                'Type'      => 'boolean',
                'InputType' => 'checkbox',
                'Value' => 1,
            ],
            'EnglishOnly' => [
                'Caption'   => 'English only',
                'Type'      => 'boolean',
                'InputType' => 'checkbox',
            ],
		);
	}
	
	function GetListFields()
	{
		$arFields = $this->Fields;
		unset($arFields["Answer"]);
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->SQL = "SELECT * FROM " . $this->TableName;
		$list->MultiEdit = False;
		$list->KeyField = $this->KeyField;
	}

	function TuneForm(\TBaseForm $form){
		$form->KeyField = $this->KeyField;
        $form->OnCheck = [$this, 'formCheck', $form];
	}

	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}

	function formCheck($objForm)
    {
        if (isset($objForm->Fields['Answer']['Value'])) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core', 'CollectErrors', true);
            $config->set('CSS.Trusted', true);
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%%');
            $config->set('Cache.DefinitionImpl', null);
            $purifier = new HTMLPurifier($config);
            $purifier->purify($objForm->Fields['Answer']['Value']);
            /** @var HTMLPurifier_ErrorCollector $error_collector */
            $error_collector = $purifier->context->get('ErrorCollector');
            $errors = array_filter($error_collector->getRaw(), function($error) {
                [$line, $severity, $message] = $error;
                $allow = true;
                if (preg_match('/attribute on \<\w+\> removed/ims', $message)) {
                    $allow = false;
                }

                return $allow;
            });
            $errors = array_map(function($error) {
                [$line, $severity, $message] = $error;

                return sprintf('<li>%s</li>', htmlspecialchars($message));
            }, $errors);
            if (count($errors) > 0) {
                return sprintf('Answer html errors: <ul>%s</ul>', implode("", $errors));
            }
        }
    }
}
?>
