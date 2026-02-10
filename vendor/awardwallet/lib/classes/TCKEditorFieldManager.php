<?php
require_once 'TAbstractFieldManager.php';

class TCKEditorFieldManager extends TAbstractFieldManager{

    var $configFile = "/lib/scripts/ckeditorConfig.js";
	var $customConfig = array();

	function InputHTML($sFieldName = null, $arField = null){
		$sFieldName = $this->FieldName;
		$arField = $this->Form->Fields[$sFieldName];
        if(!$arField['HTML'])
            $arField['Value'] = html_entity_decode($arField['Value']);

        $basePath = '/assets/common/vendors/ckeditor/';
        $config = array();
        if( isset( $arField["Height"] ) )
            $config['height'] = $arField["Height"];
        else
            $config['height'] = "300";
        if( isset( $arField["Width"] ) )
            $config['width'] = $arField["Width"];
        else
            $config['width'] = "100%";
        if(isset($arField["ToolbarSet"]))
            $config['toolbar'] = $arField["ToolbarSet"];
        $config['customConfig'] = $this->configFile;
        $config['baseHref'] = "http://{$_SERVER['HTTP_HOST']}/";
        $config = array_merge($config, $this->customConfig);

        $s = '';
        $s .= "<textarea name=\"" . $sFieldName . "\" rows=\"8\" cols=\"60\">" . htmlspecialchars($arField['Value']) . "</textarea>\n";
        $s .= $this->script("window.CKEDITOR_BASEPATH='".$basePath."';");
        $s .= "<script type=\"text/javascript\" src=\"" . $basePath . 'ckeditor.js' . "\"></script>\n";

        $js = "CKEDITOR.replace('".$sFieldName."', ".$this->jsEncode($config).");";
        $s .= $this->script($js);

		return $s;
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData ){
		$this->Form->LoadFieldPostData( $this->FieldName, $this->Field, $arData );
		if(isset($this->Field['Value'])){
			if(!$this->Field['HTML'])
				$this->Field['Value'] = htmlspecialchars_decode($this->Field['Value']);
			if(!preg_match('/<br[^>]*>\s*<br[^>]*>\s*\z/ims', $this->Field['Value']))
				$this->Field['Value'] = preg_replace("/<br[^>]*>\s*\z/ims", '', $this->Field['Value']);
			$clean = preg_replace('/<br[^>]*>/ims', '', $this->Field['Value']);
			$clean = str_ireplace('&nbsp;', '', $clean);
			if(trim($clean) == '')
				$this->Field['Value'] = '';
			if(!$this->Field['HTML'])
				$this->Field['Value'] = htmlspecialchars($this->Field['Value']);
		}
	}

	// check field. return NULL or error message
	function Check( &$arData )
	{
		if( isset( $this->Field["OnGetRequired"] ) )
		{
			if( is_array( $this->Field['OnGetRequired'] ) )
				$bRequired = CallUserFunc( array_merge( $this->Field['OnGetRequired'], array( $this->FieldName, &$this->Field ) ) );
			else
				$bRequired = CallUserFunc( array( $this->Field['OnGetRequired'], $this->FieldName, &$this->Field ) );
		}
		else
			$bRequired = $this->Field["Required"];
		if($bRequired && (trim($this->Field['Value']) == ''))
			return S_FIELD_REQUIRED;
		if(trim($this->Field['Value']) != '' && isset( $this->Field["RegExp"] ) && !isset( $this->Field["Error"] ) )
			if( !preg_match( $this->Field["RegExp"], $this->Field['Value'] ) )
			{
				if( isset( $this->Field["RegExpErrorMessage"] ) )
					return $this->Field["RegExpErrorMessage"];
				else
					return S_INVALID_VALUE;
			}
		return null;
	}

	// get addional sql parameters, for update or insert call.
	function GetSQLParams(&$arFields, $bInsert){
		$arFields[$this->FieldName] = $this->Form->SQLValue($this->FieldName);
	}

	function FieldRequiredScripts($sFieldName, $arField, $sCheckScriptCondition){
		return "    if({$sCheckScriptCondition} ( trim( CKEDITOR.instances.$sFieldName.getData() ) == '' ) )\n    {\n      alert( '" . sprintf( S_THIS_FIELD_REQUIRED, StripTags( $arField["Caption"] ) ) . "' );\n      return( false );\n    }\n";
	}

	// return required group scripts
	function RequiredGroupScripts($sFieldName, $arField){
		return "( trim( CKEDITOR.instances.$sFieldName.getData() ) == '' )";
	}

    private function script($js)
    {
        $out = "<script type=\"text/javascript\">";
        $out .= "//<![CDATA[\n";
        $out .= $js;
        $out .= "\n//]]>";
        $out .= "</script>\n";

        return $out;
    }

    private function jsEncode($val)
    {
        if (is_null($val)) {
            return 'null';
        }
        if ($val === false) {
            return 'false';
        }
        if ($val === true) {
            return 'true';
        }
        if (is_scalar($val))
        {
            if (is_float($val))
            {
                // Always use "." for floats.
                $val = str_replace(",", ".", strval($val));
            }

            // Use @@ to not use quotes when outputting string value
            if (strpos($val, '@@') === 0) {
                return substr($val, 2);
            }
            else {
                // All scalars are converted to strings to avoid indeterminism.
                // PHP's "1" and 1 are equal for all PHP operators, but
                // JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
                // we should get the same result in the JS frontend (string).
                // Character replacements for JSON.
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
                                             array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));

                $val = str_replace($jsonReplaces[0], $jsonReplaces[1], $val);

                return '"' . $val . '"';
            }
        }
        $isList = true;
        for ($i = 0, reset($val); $i < count($val); $i++, next($val))
        {
            if (key($val) !== $i)
            {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList)
        {
            foreach ($val as $v) $result[] = $this->jsEncode($v);
            return '[ ' . join(', ', $result) . ' ]';
        }
        else
        {
            foreach ($val as $k => $v) $result[] = $this->jsEncode($k).': '.$this->jsEncode($v);
            return '{ ' . join(', ', $result) . ' }';
        }
    }

}

?>
