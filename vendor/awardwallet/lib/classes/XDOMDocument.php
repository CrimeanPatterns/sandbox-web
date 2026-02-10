<?

// this class enhances DOMDocument - automatically adds htmlentities to createElement

class XDOMElement extends DOMElement
{

	function __construct($name, $value = null, $namespaceURI = null)
	{
		parent::__construct($name, null, $namespaceURI);
	}
}

class XDOMDocument extends DOMDocument
{
	function __construct($version = null, $encoding = null)
	{
		parent::__construct($version, $encoding);
		$this->registerNodeClass('DOMElement', 'XDOMElement');
	}

	function createElement($name, $value = null, $namespaceURI = null)
	{
		$element = new XDOMElement($name, $value, $namespaceURI);
		$element = $this->importNode($element);
		if (!empty($value)) {
			$element->appendChild(new DOMText($value));
		}
		return $element;
	}
}
