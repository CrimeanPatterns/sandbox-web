<?
include_once "AccountChecker/tools.php";
include_once "AccountChecker/fields.php";

class TAccountCheckerExtended extends TAccountChecker {
	var $cache = array();
	var $history = array();
	var $extraValues = array();

	var $reFrom = null;
	var $reProvider = null;
	var $reSubject = null;
	var $reHtml = null;
	var $rePlain = null;
	var $xPath = null;
	var $processors = [];

	var $onProcessHandler = null;
	var $onFlyStop = true;
	var $onFlyError = false;
	var $isServerSide = true;

	/**
	 * @var PlancakeEmailParser
	 */
	protected $parser;

	function processors() {
		return array();
	}

	function inContext($string) // dirty method, only for detectField methods (looking for better solution)
	{
		$hash = null;
		eval("\$hash = $string;");
		return $hash;
	}

	public function detectEmailFromProvider($from) {
		//if (isset($this->rePDF) && $this->rePDF)
		//	return false;

		return (isset($this->reProvider) && $this->reProvider) ? (quick_match($this->reProvider, $from) ? true : false) : false;
	}

	public function detectEmailByHeaders(array $headers) {
		//if (isset($this->rePDF) && $this->rePDF)
		//	return false;

		return ((isset($this->reFrom) && $this->reFrom && isset($headers['from'])) ? quick_match($this->reFrom, $headers["from"]) : false) ||
		((isset($this->reSubject) && $this->reSubject && isset($headers['subject'])) ? quick_match($this->reSubject, $headers["subject"]) : false);
	}

	public function detectEmailByBody(PlancakeEmailParser $parser) {

		if (isset($this->pdfRequired) && $this->pdfRequired) {
			if (!quick_match("#[/.]+pdf#i", $parser->emailRawContent)) {
				return false;
			}
		}

		if (isset($this->rePDF) && $this->rePDF) {
			$found = false;

			// PDF (more correct way)
			$altCount = $parser->countAlternatives();
			$pdfRange = (isset($this->rePDFRange) && $this->rePDFRange) ? $this->rePDFRange : 1000;
			for ($i = 0; $i < $parser->countAttachments() + $altCount; $i++) {
				$type = $parser->getAttachmentHeader($i, 'content-type');
				$name = re('#name="([^"]+)"#i', $type);
				$filename = re('#name="([^"]+)"#i', $parser->getAttachmentHeader($i, 'Content-Disposition'));
				if (preg_match("#application/pdf#i", $type)
						or preg_match('#\.pdf#i', $name)
						or preg_match('#\.pdf#i', $filename)) {
					$body = $parser->getAttachmentBody($i);
					if (($html = \PDF::convertToText($body)) !== null) {
						if (quick_match($this->rePDF, is_array($this->rePDF)?$html:cutByRange($html, $pdfRange))) {
							return true;
						}
					}
				}
			}

			// PDF (old styles, TODO: should be removed)
			/*
			$pdfs = $parser->searchAttachmentByName('.*pdf');
			$pdfRange = (isset($this->rePDFRange) && $this->rePDFRange) ? $this->rePDFRange : 1000;
			if (count($pdfs) > 0) {
				foreach ($pdfs as $pdfo) {
					if (($html = \PDF::convertToHtml($parser->getAttachmentBody($pdfo), \PDF::MODE_SIMPLE)) !== null) {
						if (quick_match($this->rePDF, is_array($this->rePDF)?$html:cutByRange($html, $pdfRange))) {
							return true;
						}
					}
				}
			}
			*/

			if (!$found)
				return false;
		}

		$plainRange = (isset($this->rePlainRange) && $this->rePlainRange) ? $this->rePlainRange : 500;
		$htmlRange = (isset($this->reHtmlRange) && $this->reHtmlRange) ? $this->reHtmlRange : 1000;

		$htmlContent = $this->http->Response['body'];
		if (isset($this->reHtml) and $this->reHtml and !$htmlContent) {
			$htmlContent = $parser->getHTMLBody();
		}

		return ((isset($this->rePlain) && $this->rePlain) ? quick_match($this->rePlain, is_array($this->rePlain)?$parser->getPlainBody():cutByRange($parser->getPlainBody(), $plainRange)) : false) ||
					((isset($this->reHtml) && $this->reHtml) ? quick_match($this->reHtml, is_array($this->rePlain)?$htmlContent:cutByRange($htmlContent, $htmlRange)) : false);
	}

	function ParsePlanEmail(PlancakeEmailParser $parser) {
#$timer=microtime(true);
		$its = array();
		$this->extraValues = array();

		$this->parser = $parser;
		$this->resultModified = false; // if PostProcess updated array
		$this->onFlyError = false; // used with onFlyStop
		$result = false;

		targetInstance($this);
		if (method_exists($this, 'processors')) {
			$this->structure = $this->processors();
		}

		if (isset($this->onStartProcessing)) {
			$call = $this->onStartProcessing;
			$call();
		}

		if (isset($this->structure) && $this->structure) {

			$split = isset($this->structure['ItinerariesSplitter']) ? $this->structure['ItinerariesSplitter'] : null;

			if ($split && is_callable($split)) {
				$itsList = $split($its, $its);
				if (!$itsList) {
					$itsList = null;
					die("Not implemented. Take first itinerary.");
				}
			}

			foreach ($this->structure as $re => $processor) {
				if ($re == 'PostProcessing' || $re == 'ItinerariesSplitter') continue;

				if (preg_match("#^\#.*?\#[imsxeu]*$#i", $re) && // we have html regexp
					preg_match($re, $this->html())
				) {
					$this->idLetter = $re;
					$this->history[$this->idLetter] = [];
					$result = $this->processLetter($processor, $its);
					break;
				} elseif (preg_match("#^\.*//#", $re) && // we have xpath
					($result = $this->http->FindNodes($re, $its)) !== null
					&& count($result) > 0
				) {
					$this->idLetter = $re;
					$this->history[$this->idLetter] = [];
					$result = $this->processLetter($processor, $its);
					break;
				} elseif (strpos($re, $this->html()) !== false) { // string in html
					$this->idLetter = $re;
					$this->history[$this->idLetter] = [];
					$result = $this->processLetter($processor, $its);
					break;
				}
			}
		}

		if ($this->isServerSide){
			$its = $this->flightLocatorProcessing($its);
		} else {
			// enable FlightLocator field to avoid error output
			TAccountChecker::$itinerarySchema['T']['TripSegments']['FlightLocator'] = false;
		}

		$this->extraValues['Itineraries'] = isset($its[0]) ? $its : array($its);

		return array(
			'emailType' => 'reservations',
			'parsedData' => ($result && !$this->onFlyError) ? $this->extraValues : [
				'InvalidItineraries' => $this->extraValues,
				'Error' => $this->onFlyError ? 'On-fly error occurred: invalid field "' . $this->onFlyError . '"' : 'Stopped by parser'
			]
		);
	}

	public function parsedValue($name, $value = null) {
		if (func_num_args() == 1) {
			return isset($this->extraValues[$name]) ? $this->extraValues[$name] : null;
		} else {
			$this->extraValues[$name] = $value;
		}
		return true;
	}

	private function processHandler($name, &$handler, &$it, &$seg = null, &$segmentSlice = null) {
		if ($this->onProcessHandler) { // for autodetection
			$call = $this->onProcessHandler;
			if (is_callable($call))
				$call($name, $handler, $it, $seg, $segmentSlice);
		}

		if ($seg === null) $dst = & $it; else $dst = & $seg;

		if (is_callable($handler)) {
			try {
				$r = $handler($segmentSlice, $segmentSlice, $dst);
			} catch (Exception $e) {
				$r = null;
			}

			$isNumeric = false;
			$newbies = [];

			if (is_array($r)) {
				foreach ($r as $key => $value) {
					if (is_numeric($key)) { // numeric array (not multikey array)
						$newbies[$name] = $r;
						$isNumeric = true;
						break;
					}
				}
				if (!$isNumeric) {
					foreach ($r as $item => $value) {
						$newbies[$item] = $value;
					}
				}
			} else {
				$newbies[$name] = $r;
			}

			// store as final data
			foreach ($newbies as $key => $value) {
				$dst[$key] = $value;
			}

			// check final data, only new fields checking
			if (isset($it['Kind'])) {
				global $accountCheckerFields;
				$byKind = $accountCheckerFields[$it['Kind']];

				foreach ($newbies as $key => $value) {
					if (isset($byKind[$key])) {
						$handlers = $byKind[$key];
						foreach ($handlers as $call) {
							if (is_callable($call)) {
								$retValue = $call($value, $it, $seg, $key);
								if ($retValue) {
									$this->http->Log($retValue, LOG_LEVEL_NORMAL);
									$this->onFlyError = $key;
									if ($this->onFlyStop)
										return false;
									/*else {
										if (!$this->onFlyError) // store only first error field
											$this->onFlyError = $key;
									}*/
								}
							}
						}
					}
				}
			}
		}

		return true;
	}

	private function processItinerary(&$handlers, &$it, &$itinerarySlice) {
		$this->parseTarget($itinerarySlice);

		foreach ($handlers as $name => $handler) {
			if ($name == 'SegmentsSplitter') continue;
			$tmp = null;
			if (!$this->processHandler($name, $handler, $it, $tmp, $itinerarySlice))
				return false;
		}

		if (isset($handlers['SegmentsSplitter'])) {
			$segSplit = $handlers['SegmentsSplitter'];

			$segments = $segSplit($itinerarySlice, $itinerarySlice, $it);
			$this->parentTarget($itinerarySlice);

			if ($segments) {
				$it['TripSegments'] = array();

				if (!isset($handlers['TripSegments'])) $handlers['TripSegments'] = [];

				foreach ($segments as $segmentSlice) {
					$seg = array();
					foreach ($handlers['TripSegments'] as $name => $handler) {
						$this->parseTarget($segmentSlice);
						if (!$this->processHandler($name, $handler, $it, $seg, $segmentSlice)) {
							$it['TripSegments'][] = $seg; // store values and return
							return false;
						}
					}

					if ($it['Kind'] === 'C') {
						$seg['Port'] = $seg['DepName'];
						#unset($seg['DepName']);
					}

					$it['TripSegments'][] = $seg;
				}
			} else {
				// Splitter function exists
				// but it doesn't return segments
			}
		}

		if ($it['Kind'] === 'C') { // Cruise to Trip
			$this->converter = new CruiseSegmentsConverter();
			if (isset($it['TripSegments']))
				$it['TripSegments'] = $this->converter->Convert($it['TripSegments']);

			$it["Kind"] = "T";
			$it["TripCategory"] = TRIP_CATEGORY_CRUISE;
		}

		if ($it['Kind'] === 'B') { // Bus to Trip
			$it["Kind"] = "T";

			if (!isset($it["TripCategory"]))
				$it["TripCategory"] = TRIP_CATEGORY_BUS;
		}

		return true;
	}

	private function processLetter(&$reArray, &$its) {
		$this->parentSource = null;

		if (isset($reArray['ItinerariesSplitter'])) {
			$itSplit = $reArray['ItinerariesSplitter'];

			$tmp = $this->text();
			$this->parseTarget($tmp);
			$itineraries = $itSplit($tmp);

			$this->parentTarget($this->text());

			if (!is_array($itineraries) && !(is_object($itineraries) and get_class($itineraries) == 'DOMNodeList')) {
				return false;
			}

			foreach ($itineraries as $itinerarySlice) {
				$itinerary = array();

				foreach ($reArray as $re => $handler) {
					if ($re == 'ItinerariesSplitter' || $re == 'PostProcessing') continue;

					if (preg_match("#^\#.*?\#[imsxeu]*$#i", $re) && // we have html regexp
						#preg_match($re, $itinerarySlice)){
						re($re, $itinerarySlice) !== null
					) { // gives flexibility between text/nodes
						$this->idItinerary = $re;
						$this->history[$this->idLetter][] = $this->idItinerary;
						if (!$this->processItinerary($handler, $itinerary, $itinerarySlice)) {
							$its[] = $itinerary;
							return false;
						}
						break;
					} elseif (preg_match("#^[\(.]*//#", $re) && is_object($itinerarySlice) && in_array(get_class($itinerarySlice), ["DOMNode", "DOMElement"]) && // we have xpath
						($result = $this->http->FindNodes($re, $itinerarySlice)) !== null
						&& count($result) > 0
					) {

						$this->idItinerary = $re;
						$this->history[$this->idLetter][] = $this->idItinerary;
						if (!$this->processItinerary($handler, $itinerary, $itinerarySlice)) {
							$its[] = $itinerary;
							return false;
						}
						break;
					} elseif (!empty($itinerarySlice)
						&& is_string($itinerarySlice)
						&& strpos($re, $itinerarySlice) !== false
					) { // string in html
						$this->idItinerary = $re;
						$this->history[$this->idLetter][] = $this->idItinerary;
						if (!$this->processItinerary($handler, $itinerary, $itinerarySlice)) {
							$its[] = $itinerary;
							return false;
						}
						break;
					}
				}

				$its[] = $itinerary;
			}

			$postProcessing = isset($reArray['PostProcessing']) ? $reArray['PostProcessing'] : null;
			if ($postProcessing && is_callable($postProcessing) && $res = $postProcessing($this->text(), null, $its)) {
				if ($res != $its) {
					$its = $res;
					$this->resultModified = true;
				}
			}
		}
		return true;
	}

	function html() {
		return (isset($this->parser) && $this->parser) ? $this->parser->getHtmlBody() : $this->http->Response['body'];
	}

	function text() {
        if (!isset($this->parser)) {
            return '';
        }
		if (!isset($this->cache['text'])) {
			if(!empty($this->parser->cacheGeneral['text']))
				$html = $this->parser->cacheGeneral['text'];
			else {
				$html = text($this->html()); // full html
				$this->parser->cacheGeneral['text'] = $html;
			}
			return $this->cache['text'] = $html;
		} else {
			return $this->cache['text'];
		}
	}

	function parent() {
		return $this->parentSource;
	}

	public function cache($name, $value = false) {
		if ($value === false) {
			return isset($this->cache[$name]) ? $this->cache[$name] : null;
		} else {
			$this->cache[$name] = $value;
		}
	}

	public function setDocument($findBy = "view", $format = null, $index = -1) {
		if ($findBy == 'source') {
			if (is_object($format) and in_array(get_class($format), ['DOMElement', 'DOMNodeList']))
				$format = html(xpath($format));

			$this->http->SetBody($format);
			$text = text($format);
		} elseif ($findBy == 'xpath') {
			if (!is_string($format) && in_array(get_class($format), ['DOMElement', 'DOMNodeList']))
				$html = html($format);
			else
				$html = html(xpath($format)); // html source

			$this->http->SetBody($html);
			$text = text($html);
		} else {
			$text = $this->getDocument($findBy, $format, $index);
		}

		if ($format == 'complex') {
			$this->http->SetBody($text);
			$text = text($text);
		} else if ($format == 'html') {
			$this->http->SetBody($text);
			$text = text($text);
		} else if ($format == 'simpletable') {
			$this->http->SetBody($text);
			$text = text($text);
		}
		$this->cache('text', $text);
		$this->parseTarget($text);
		$this->parentTarget($text);
		return $text;
	}

	public function getDocument($findBy = "view", $format = null, $index = -1) {
		$parser = $this->parser;

		if ($findBy == 'view') {
			$body = isset($parser->cacheGeneral->body) ? $parser->cacheGeneral->body : $parser->getHTMLBody();

			if ($format == 'text')
				return isset($parser->cacheGeneral->text) ? $parser->cacheGeneral->text : text($body);
			else
				return $body;
		}

		if ($findBy == 'plain') {
			$plain = isset($parser->cacheGeneral->plain) ? $parser->cacheGeneral->plain : $parser->getPlainBody();

			if ($format == 'text')
				return isset($parser->cacheGeneral->textFromPlain) ? $parser->cacheGeneral->textFromPlain : text($plain);
			else
				return $plain;
		}

		$altCount = $parser->countAlternatives();

		for ($i = 0; $i < $parser->countAttachments() + $altCount; $i++) {
			if ($index !== -1) {
				if ($index != $i) {
					continue;
				}
			}

			$p = isset($parser->cachedContext[$i]) ? $parser->cachedContext[$i] : (object)array();

			$info = isset($p->info) ? $p->info : $parser->getAttachmentHeader($i, 'content-type');
			if (preg_match('#name="?(.*)"?#', $info, $m))
				$name = $m[1];
			else
				$name = '';

			if (isRegex($findBy)) {
				// $findBy is regex
				$res = preg_match($findBy, $name);
				if ($res === 0)
					// $findBy is regex, but it doesn't match attachment name
					continue;
				elseif ($res === false)
					// $findBy isn't regex (despite result of isRegex()), or there is regex syntax error
					$pregMatched = false;
				else
					// $findBy is regex and it matched attachment name
					$pregMatched = true;
			} else {
				// $findBy isn't regex, or there is regex syntax error
				$pregMatched = false;
			}

			if (isset($p->curtype)) {
				$body = $p->body;
				$curtype = $p->curtype;
			} else {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$body = $parser->getAttachmentBody($i);
				$curtype = finfo_buffer_safe($finfo, $body);
				finfo_close($finfo);
			}

			if (!$pregMatched and $curtype == $findBy) {
				if (preg_match("#^application/pdf#i", $findBy, $m)) {
					if ($format == 'html') {
						return isset($p->pdf) ? $p->pdf->html : \PDF::convertToHtml($body, \PDF::MODE_SIMPLE);
					} elseif ($format == 'simpletable') {
						return (isset($p->pdf) && isset($p->pdf->simpletable)) ? $p->pdf->simpletable : pdfHtmlHtmlTable(\PDF::convertToHtml($body, \PDF::MODE_COMPLEX));
					} elseif ($format == 'complex') {
						return isset($p->pdf) ? $p->pdf->complex : \PDF::convertToHtml($body, \PDF::MODE_COMPLEX);
					} elseif ($format == 'text') {
						return isset($p->pdf) ? $p->pdf->text : text(\PDF::convertToHtml($body, \PDF::MODE_SIMPLE));
					}
				} elseif (preg_match("#^text/rtf#i", $findBy)) {
					if ($format == 'html') {
						return isset($p->rtf) ? $p->rtf->html : $parser->rtf2html($body);
					} elseif ($format == 'text') {
						return isset($p->rtf) ? $p->rtf->text : text($parser->rtf2text($body));
					}
				} elseif (preg_match("#^text/#i", $findBy)) {
					if ($format == 'text') {
						return isset($p->text) ? $p->text : text($body);
					} else {
						return $body;
					}
				}
			} elseif ($pregMatched) {
				if (preg_match("#^application/pdf#i", $info, $m) || ($pregMatched && preg_match("#^application/octet#i", $info, $m))) {
					if ($format == 'html') {
						return isset($p->pdf) ? $p->pdf->html : \PDF::convertToHtml($body, \PDF::MODE_SIMPLE);
					} elseif ($format == 'simpletable') {
						return (isset($p->pdf) && isset($p->pdf->simpletable)) ? $p->pdf->simpletable : pdfHtmlHtmlTable(\PDF::convertToHtml($body, \PDF::MODE_COMPLEX));
					} elseif ($format == 'complex') {
						return isset($p->pdf) ? $p->pdf->complex : \PDF::convertToHtml($body, \PDF::MODE_COMPLEX);
					} elseif ($format == 'text') {
						return isset($p->pdf) ? $p->pdf->text : text(\PDF::convertToHtml($body, \PDF::MODE_SIMPLE));
					}
				} elseif (preg_match("#^text/#i", $info)) {
					if ($format == 'text') {
						return isset($p->text) ? $p->text : text($body);
					} else {
						return $body;
					}
				}
			}
		}
	}

	function parseTarget($target) {
		$this->targetSource = $target;
	}

	function parentTarget($target) {
		$this->parentSource = $target;
	}

	function flightLocatorProcessing($it)
	{
		// index by locators
		$rebuild = [];
		$other = [];

		foreach($it as $i => &$cur)
		{
			// only trip with locators
			if (!isset($cur['Kind']) || $cur['Kind'] != 'T' || (isset($cur['TripCategory']) && $cur['TripCategory'])){ // if not air trip
				$other[] = $cur;
				continue;
			}
			
			$defaultLocator = isset($cur['RecordLocator'])?$cur['RecordLocator']:null;

			if (isset($cur['TripSegments']) && is_array($cur['TripSegments']) && $cur['TripSegments']){
				foreach($cur['TripSegments'] as $seg){
					$locator = (isset($seg['FlightLocator']) && $seg['FlightLocator'])?$seg['FlightLocator']:$defaultLocator;
					if (!isset($rebuild[$locator])){
						$rebuild[$locator] = [
							'segments' => [],
							'it' => $cur
						];
					}

					$rebuild[$locator]['segments'][] = $seg;
				}
			} else { // Air without segments (if cancelled for example)
				$other[] = $cur;
			}
		}

		// recount
		$res = $other;
		foreach($rebuild as $locator => $info){
			$it = $info['it'];

			$it['RecordLocator'] = $locator;
			$it['TripSegments'] = [];

			foreach($info['segments'] as &$seg){
				if (isset($seg['FlightLocator']) || array_key_exists('FlightLocator', $seg)) // bug: sometimes isset($seg['FlightLocator']) return false when it's true, so we have to use array_key_exists
					unset($seg['FlightLocator']);
				$it['TripSegments'][] = $seg;
			}
			$res[] = $it;
		}

		return $res;
	}

	function getEmailDate() {
		return strtotime($this->parser->getHeader('date'));
	}

	function getEmailYear() {
		return date('Y', $this->getEmailDate());
	}

	static public function requireTools(){

	}

}
