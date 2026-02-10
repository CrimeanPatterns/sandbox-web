<?php

/*************************************************************************************
 * ===================================================================================*
 * Software by: Danyuki Software Limited                                              *
 * This file is part of Plancake.                                                     *
 *                                                                                    *
 * Copyright 2009-2010-2011 by:     Danyuki Software Limited                          *
 * Support, News, Updates at:  http://www.plancake.com                                *
 * Licensed under the LGPL version 3 license.                                         *                                                       *
 * Danyuki Software Limited is registered in England and Wales (Company No. 07554549) *
 **************************************************************************************
 * Plancake is distributed in the hope that it will be useful,                        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of                     *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                      *
 * GNU Lesser General Public License v3.0 for more details.                           *
 *                                                                                    *
 * You should have received a copy of the GNU Lesser General Public License           *
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.              *
 *                                                                                    *
 **************************************************************************************
 *
 * Valuable contributions by:
 * - Chris
 *
 * **************************************************************************************/

/**
 * Extracts the headers and the body of an email
 * Obviously it can't extract the bcc header because it doesn't appear in the content
 * of the email.
 *
 * N.B.: if you deal with non-English languages, we recommend you install the IMAP PHP extension:
 * the Plancake PHP Email Parser will detect it and used it automatically for better results.
 *
 * For more info, check:
 * https://github.com/plancake/official-library-php-email-parser
 *
 * @author dan
 */
class PlancakeEmailParser {

	const PLAINTEXT = 1;
	const HTML = 2;

	/**
	 *
	 * @var boolean
	 */
	private $isImapExtensionAvailable = false;

	/**
	 *
	 * @var string
	 */
	public $emailRawContent;

	/**
	 *
	 * @var array
	 */
	protected $rawFields = array();

	/**
	 *
	 * @var array
	 */
	protected $rawFieldsArray = array();

	/**
	 *
	 * @var array of string (each element is a line)
	 */
	protected $rawHeaders = array();

	/**
	 *
	 * @var array
	 */
	protected $rawAttachments = [];

	/**
	 *
	 * @var array of string (each element is a line)
	 */
	protected $rawBodyLines = array();

	protected $alternativeBodies=array();
	/**
	 * @var string
	 */
	protected $htmlBody;
	/**
	 * @var string
	 */
	protected $textBody;

	public $cachedContext = array();
	public $cacheGeneral = array();

	public function  __construct($emailRawContent) {
		$this->emailRawContent = $emailRawContent;
		$this->extractHeadersAndRawBody();
        /*
		if (function_exists('imap_open')) {
			$this->isImapExtensionAvailable = true;
		}
        */
	}

	/**
	 * @param string $text if null take original Content-Type field
	 *
	 * @return array
	 *      get header "Content-Type" and parse it
	 */
	private function extractContentType($text=null)
	{
		if (!$text || is_array($text))
		    $text = $this->getHeader('Content-Type');
		$res = array(
			'type'=>'text',
			'subtype'=>'plain',
		);
		$lines = explode(';', $text);
		foreach($lines as $line) {
            if (preg_match("#^[^=]*\b([\w-]+)\/([\w-]+)#", $line, $m)) {
				$res['type'] = $m[1];
				$res['subtype'] = $m[2];
			}
			elseif (preg_match("#^\s*([\w-]+)=\s*(?:\"|')([^\"']+)(?:\"|')#", $line, $m)) {
			    if(!(strtolower($m[1]) == 'type' && $res['type'] == 'multipart')) {
				    $res[strtolower($m[1])] = $m[2];
			    }
			}
			elseif (preg_match("#^\s*([\w-]+)=([^\s]+)#", $line, $m)) {
                if(!(strtolower($m[1]) == 'type' && $res['type'] == 'multipart')) {
                    $res[strtolower($m[1])] = $m[2];
                }
			}
		}
		return $res;
	}

	/**
	 * @param array of string $lines
	 *
	 * @return array
	 *
	 *   Split message body on headers and body lines
	 */
	private function readHeaderLines(&$lines){
		$res=array(
			'headers'=>array(),
			'body'=>array(),
			'rawHeaders'=>array(),
		);
		$hlines=&$res['rawHeaders'];
		$headers=&$res['headers'];
		$body=&$res['body'];
		$isBody=false;
		$idx = 0;
		// clear empty lines in the beginning
		while (isset($lines[0]) && empty($lines[0]))
			array_shift($lines);
		$params = [];
		foreach($lines as &$line){
			if($isBody){
				$body[]=&$line;
			}else{
				if($this->isNewLine($line) and !$isBody){
					$isBody=true;
					continue;
				}
				if ($this->isLineStartingWithPrintableChar($line)) {
					$hlines[$idx]=&$line;
					$idx++;
				}
				elseif ($idx > 0) {
					$write = true;
					$tmp = substr($line, 1);
					if (preg_match('/^(\w+)\*(\d+)=(.+)$/', $tmp, $m)) {
						$key = sprintf('%s-%d', $m[1], $idx-1);
						if (!isset($params[$key])) {
							$params[$key] = ['line' => $idx - 1, 'name' => $m[1], 'parts' => []];
							$line = sprintf('%s%s="{%s}";', substr($line, 0, 1), $m[1], $m[1]);
						}
						else
							$write = false;
						$params[$key]['parts'][] = trim($m[3], '\'";');
					}
					if ($write)
						$hlines[$idx - 1] .= " " . substr($line, 1);
				}
			}
		}
		foreach($params as $param)
			$hlines[$param['line']] = str_replace(sprintf('{%s}', $param['name']), implode('', $param['parts']), $hlines[$param['line']]);
		if(!empty($hlines)){
			if ($this->isImapExtensionAvailable) { // actually always false, because isImapExtensionAvailable filled in later in constructor
                // tried to fix that, but this sibling is doing something wrong, leaving as is
				$h = imap_mime_header_decode(implode("\r\n",$hlines));
			} else {
                // encode invalid headers, like not encoded utf8,
                // for example: Subject: 答复: Reservation for Walia family from Dec 19 to 22
			    $hlines = array_map(function(string $line) : string {
			        $filtered = @iconv("UTF-8", "ASCII//IGNORE", $line);
			        if ($filtered !== $line) {
			            return '=?UTF-8?B?' . base64_encode(trim($line)) . '?=';
                    }
                    return $line;
                }, $hlines);
				$h = iconv_mime_decode_headers(implode("\r\n",$hlines),2,'UTF-8');
				if (empty($h)) {
				    // possible outdated branch, seems like iconv_mime_decode_headers does not return false on invalid characters anymore
                    // it strips them out
				    $filteredLines = array_filter($hlines, function($line){
				        foreach([
				            'Subject', 'From', 'To', 'Content-Type', 'Delivered-To', 'Received', 'Message-ID', 'Date'
                                ] as $header)
				            if (strpos($line, $header) === 0)
				                return true;
                        return false;
                    });
                    $h = @iconv_mime_decode_headers(implode("\r\n", $filteredLines), 2, 'UTF-8');
                    if ($h === false) {
                        // invalid headers, not encoded utf8,
                        // for example: Subject: 答复: Reservation for Walia family from Dec 19 to 22
                        // try to parse without decoding
                        $h = [];
                        foreach ($filteredLines as $line) {
                            $p = strpos($line, ":");
                            if ($p !== false) {
                                $h[substr($line, 0, $p)] = trim(substr($line, $p + 1));
                            }
                        }
                    }
                }
			}
			foreach($h as $title=>&$value){
				$headers[strtolower($title)]=&$value;
			}
		}
		return $res;
	}

	/**
	 * @param array of string $lines
	 * @param string $boundary
	 *
	 * @return array
	 *
	 * split message body on multiple bodies thru boundary
	 * and parse recursive messages and multiplicity
	 */
	private function splitMultipart(&$lines,$boundary){

		$res=array();
		$boundary='--'.trim($boundary,'"\' ');
		$firstBoundary=true;
        $bnd = false;

		foreach($lines as &$line){
            $bnd = false;
			if(strpos($line,$boundary)!==false){
                $bnd = true;
				if($firstBoundary){
					$firstBoundary=false;
				}else{
					if(isset($body)){
						$res[]=&$body;
						unset($body);
					}
				}
				$body=array();
				continue;
			}
			if(!$firstBoundary){
				$body[]=&$line;
			}
		}
        if (!$bnd && !empty($body)) {
            // they didn't include the ending boundary
            $res[] = &$body;
            unset($body);
        }

		if(!empty($res))
		{
			foreach($res as &$body)
			{
				$body=$this->readHeaderLines($body);
				$headers=&$body['headers'];

				if(isset($headers['content-disposition'])
//                   and preg_match('/(attachment|inline)/i',$headers['content-disposition'])
				){
					$isAttached=true;
				} else {
					$isAttached=false;
				}

				$body['attach']=$isAttached;
				$content = $this->extractContentType(isset($headers['content-type'])&&is_string($headers['content-type'])?$headers['content-type']:'');

				if (isset($content['boundary']) || in_array($content['type'],array('multipart','message')))
				{					
					$_body=$body['body'];
					if (isset($headers['content-type-encoding'])){
						$_body=implode("\n",$_body);
						if (strcasecmp($headers['content-type-encoding'], "base64") === 0)
							$_body = base64_decode($_body);
						elseif (strcasecmp($headers['content-type-encoding'], 'quoted-printable') === 0)
							$_body = quoted_printable_decode($_body);
						$_body=preg_split("/(\r?\n|\r)/", $_body);
					}

					if (isset($content['boundary']) && $content['type']=='multipart'){
						$return = $this->splitMultipart($_body, $content['boundary']);
						// "boundary" is not always reason to make splitMultipart,
						// in such cases it returns void array, we should check it
						// for emptiness before add, otherwise we can loose one of attachments
						if ($return)
							$body['multipart'] = $return;
					}
					elseif($content['type']=='message'){
						$body['parser'] = new PlancakeEmailParser(implode("\n",$_body));
						$body['attach'] = true;
					}

				} else {
					if($content['type'] == 'text'){
						$body['attach'] = false;
					} elseif (!$body['attach']){
						$body['attach'] = true;
					}
				}
			}
		}

		return $res;
	}

	/**
	 * @param array $parts
	 * @param array of string $list
	 *
	 *   line up parts from recursion
	 */
	private function unDepth(&$parts,&$list){
		foreach($parts as &$part){
			if(isset($part['multipart'])){
				$this->unDepth($part['multipart'],$list);
			}else{
				$list[]=&$part;
			}
		}
	}

	/**
	 *
	 * @return void
	 *
	 */
	private function extractHeadersAndRawBody()
	{
		$lines = preg_split("/(\r?\n|\r)/", ltrim($this->emailRawContent));

		if (count($lines) > 500000) {
            return;
        }

		$currentHeader = '';

		$i = 0;

		$mainbody=$this->readHeaderLines($lines);
		if( isset($mainbody['headers']['from']) && is_array($mainbody['headers']['from']) && (0 < count($mainbody['headers']['from'])) )
            $mainbody['headers']['from'] = array_values(array_filter(array_unique($mainbody['headers']['from'])))[0];

		$this->rawBodyLines=&$mainbody['body'];
		$this->rawHeaders=&$mainbody['rawHeaders'];
		$this->rawFields=&$mainbody['headers'];

		$matches=$this->extractContentType();

		if ($matches['type'] == 'multipart' and in_array($matches['subtype'], ['mixed', 'alternative', 'relative', 'signed', 'related'], true) and !empty($matches['boundary'])) {
			$multipart=$this->splitMultipart($this->rawBodyLines,$matches['boundary']);
			$this->rawAttachments=array();
			$this->unDepth($multipart,$this->rawAttachments);
			$multipartAlternative = ($matches['subtype'] == 'alternative');

			// all alternative part move to $alternativeBodies
			if(!empty($this->rawAttachments)){
				foreach($this->rawAttachments as $key => &$attachment){
					if(!$attachment['attach'] and !isset($attachment['parser'])){
						$this->alternativeBodies[]=&$attachment;
						unset($this->rawAttachments[$key]);
					}

					// explode sub parsers
					if (isset($attachment['parser'])){
						$parser = $attachment['parser'];

						// move to altbodies all subparser's bodies
						if(!empty($parser->rawAttachments)){
							foreach($parser->rawAttachments as $key => &$attachment){
								$this->alternativeBodies[] = &$attachment;
							}
						}

            // move to altbodies all subparser's altbodies
						if(!empty($parser->alternativeBodies)){
							foreach($parser->alternativeBodies as $key => &$attachment){
								$this->alternativeBodies[] = &$attachment;
							}
						}
					}
				}
				$this->rawAttachments=array_values($this->rawAttachments);
			}

		}
		if (($html = $this->getHTMLBody()) && stripos($html, '<embed') !== false) {
            ($http = new HttpBrowser('none', new NullDriver()))->SetEmailBody($html);
            $embeds = $http->XPath->query('//embed[starts-with(@src, "data:application/pdf;base64,")]/@src');
            for($i = 0; $i < $embeds->length; $i++) {
                $src = substr($embeds->item($i)->nodeValue, 28);
                $headers = [
                    'content-type' => 'application/pdf; name=file.pdf',
                    'content-disposition' => 'inline; filename=file.pdf',
                    'content-transfer-encoding' => 'base64',
                ];
                $this->rawAttachments[] = [
                    'headers' => $headers,
                    'body' => [$src],
                    'rawHeaders' => $headers,
                ];
            }
        }

	}

	public function getBodyStr() {
		$lines = preg_split("/(\r?\n|\r)/", ltrim($this->emailRawContent));
		do {
			$line = array_shift($lines);
		} while (false !== $line && !$this->isNewLine($line));
		return implode("\r\n", $lines);
	}

	/**
	 *
	 * @return string (in UTF-8 format)
	 * @throws Exception if a subject header is not found
	 */
	public function getSubject()
	{
	    return $this->getHeader('subject', false);

		/* headers are decoded in $this->readHeaderLines()

		if (!isset($this->rawFields['subject']))
		{
			return '';
		}

		if ($this->isImapExtensionAvailable) {
			$h = imap_mime_header_decode($this->rawFields['subject']);
			if(count($h) == 0)
				return utf8_encode(iconv_mime_decode($this->rawFields['subject'], ICONV_MIME_DECODE_CONTINUE_ON_ERROR));
			$subject = '';
			foreach ($h as $chunk) {
				$charset = ($chunk->charset == 'default') ? 'US-ASCII' : $chunk->charset;
				$subject .= @iconv($charset, "UTF-8//TRANSLIT", $chunk->text);
			}
			return $subject;
		} else {
			$ret = utf8_encode(iconv_mime_decode($this->rawFields['subject'], ICONV_MIME_DECODE_CONTINUE_ON_ERROR));
		}
		return $ret;
		*/
	}

	/**
	 *
	 * @return array
	 */
	public function getCc()
	{
		if (!isset($this->rawFields['cc']))
		{
			return array();
		}

		return explode(',', $this->rawFields['cc']);
	}

	/**
	 *
	 * @return array
	 */
	public function getTo()
	{
		if ( (!isset($this->rawFields['to'])) || (!is_string($this->rawFields['to'])) || (!strlen($this->rawFields['to'])) )
		{
			return [];
		}
		return explode(',', $this->rawFields['to']);
	}

	public function getCleanTo($pos = 0) {
		try {
			$tos = $this->getTo();
		} catch (Exception $e) {
			$tos = [];
		}
		if (isset($tos[$pos])) {
			$to = $tos[$pos];
			if (preg_match("/<([^>]+)>/ims", $to, $m))
				$to = $m[1];
			$to = preg_replace("/\([^\)]*\)/ims", "", $to);
			return trim($to);
		}
		return null;
	}
	public function getFrom()
	{
		if ( (!isset($this->rawFields['from'])) || (!is_string($this->rawFields['from'])) || (!strlen($this->rawFields['from'])))
		{
			return [];
		}
		if (preg_match_all('/"[^"]+"\s*<[^>]+@[^>]+>/', $this->rawFields['from'], $m)) {
		    return $m[0];
        }
		else {
		    return array_map('trim', explode(',', $this->rawFields['from']));
        }
	}

	public function getCleanFrom($pos = 0) {
		try {
			$tos = $this->getFrom();
		} catch (Exception $e) {
			$tos = [];
		}
		if (isset($tos[$pos])) {
			$to = $tos[$pos];
			if (preg_match("/<([^>]+)>/ims", $to, $m))
				$to = $m[1];
			$to = preg_replace("/\([^\)]*\)/ims", "", $to);
			return trim($to);
		}
		return null;
	}

	/**
	 * return string - UTF8 encoded
	 *
	 * Example of an email body
	 *
	--0016e65b5ec22721580487cb20fd
	Content-Type: text/plain; charset=ISO-8859-1

	Hi all. I am new to Android development.
	Please help me.

	--
	My signature

	email: myemail@gmail.com
	web: http://www.example.com

	--0016e65b5ec22721580487cb20fd
	Content-Type: text/html; charset=ISO-8859-1
	 */
	public function getBody($returnType=self::PLAINTEXT)
	{
		$body = '';
		$contentTransferEncoding = null;
		$charset = null;
		$content=$this->extractContentType();
		if($content['type'] == 'text'){
			$body=implode("\n", $this->rawBodyLines);
			if(!empty($content['charset'])){
				$charset=$content['charset'];
			}
			$contentTransferEncoding=$this->getHeader('Content-Transfer-Encoding');
		}elseif(!empty($this->alternativeBodies)){
			foreach($this->alternativeBodies as &$_body){
				$headers=&$_body['headers'];
				if (!array_key_exists('content-type', $headers))
					continue;
				$content=$this->extractContentType($headers['content-type']);
				if($content['type']=='text'){
					if((($returnType == self::PLAINTEXT) && (strcasecmp($content['subtype'], 'plain') === 0)) or
						(($returnType == self::HTML) && (strcasecmp($content['subtype'], 'html') === 0))){
						$part=implode("\n", $_body['body']);
						if(!empty($content['charset'])){
							$charset=$content['charset'];
						}
						if(isset($headers['content-transfer-encoding'])) {
							$contentTransferEncoding=$headers['content-transfer-encoding'];
                            if (is_array($contentTransferEncoding)) {
                                $contentTransferEncoding = reset($contentTransferEncoding);
                            }
							if (strcasecmp($contentTransferEncoding, 'base64') === 0)
								$part = base64_decode($part);
							elseif (strcasecmp($contentTransferEncoding, 'quoted-printable') === 0)
								$part = quoted_printable_decode($part);
						}
						$contentTransferEncoding = null;
						if (!isset($charset))
							$charset = 'UTF-8';
						if(strcasecmp($charset, 'UTF-8') !== 0) {
							$part = $this->convBody($part, $charset);
						}
						$charset = false;
						$body = $part;
						break;
					}
				}
			}
		}else{
			$previousLine = '';
			$detectedContentType = false;
			$waitingForContentStart = true;
			$boundaries = array();
			preg_match_all("/boundary=\"?([^\";\r\n]*)/i", $this->emailRawContent, $matches);
			if ($matches)
				foreach ($matches[1] as $boundary) {
					$tmp = trim($boundary, "\"'\n\r");
					$boundaries[] = "--".$tmp;
					$boundaries[] = "--".$tmp."--";
				}

			if ($returnType == self::HTML)
				$contentTypeRegex = '/^Content-Type: ?text\/html/i';
			else
				$contentTypeRegex = '/^Content-Type: ?text\/plain/i';

			foreach ($this->rawBodyLines as $line) {
				if (preg_match('/^Content-Transfer-Encoding: ?(.*)/i', $line, $matches)) {
					$contentTransferEncoding = $matches[1];
				}
				if (in_array($line, $boundaries) && $waitingForContentStart)
					$contentTransferEncoding = null;

				if (!$detectedContentType) {

					if (preg_match($contentTypeRegex, $line, $matches)) {
						$detectedContentType = true;

						$delimiter = $previousLine;
					}

					if(preg_match('/charset=(.*)/i', $line, $matches)) {
						$charset = strtoupper(trim($matches[1], '"'));
					}

				} else if ($detectedContentType && $waitingForContentStart) {


					if (self::isNewLine($line)) {
						$waitingForContentStart = false;
					}
				} else {  // ($detectedContentType && !$waitingForContentStart)
					// collecting the actual content until we find the delimiter
					if (in_array($line, $boundaries)) {  // found the delimiter
						break;
					}
					$body .= $line . "\n";
				}

				$previousLine = $line;
			}

			if (!isset($contentTransferEncoding) && isset($this->rawFields["content-transfer-encoding"]))
				$contentTransferEncoding = $this->rawFields["content-transfer-encoding"];
			if (!$detectedContentType && isset($this->rawFields["content-type"]) && preg_match("/charset=([^;]*)/", $this->getHeader("content-type"), $matches)) {
				$charset = strtoupper(trim($matches[1], "\"' "));
			}
		}

        if (is_array($contentTransferEncoding) && isset($contentTransferEncoding[0])) {
            $contentTransferEncoding = $contentTransferEncoding[0];
        }
		// removing trailing new lines
		$body = rtrim($body);
		if (strcasecmp($contentTransferEncoding, 'base64') === 0)
			$body = base64_decode($body);
		else if (strcasecmp($contentTransferEncoding, 'quoted-printable') === 0)
			$body = quoted_printable_decode($body);

		if (!isset($charset))
			$charset = 'UTF-8';
		if($charset !== false && strcasecmp($charset, 'UTF-8') !== 0) {
			$body = $this->convBody($body, $charset);
		}
		if ($returnType === self::HTML)
		    $body = html_entity_decode($body);

		return $body;
	}

	protected function convBody($body, $charset) {
		$charset = str_replace("FORMAT=FLOWED", "", $charset);

		$utfBody = @iconv($charset, 'UTF-8//IGNORE', $body);

		if ($utfBody === FALSE) { // iconv returns FALSE on failure
			$utfBody = utf8_encode($body);
		}
		if($utfBody !== false)
			$body = $utfBody;
		return $body;
	}

	/**
	 * @return string - UTF8 encoded
	 *
	 */
	public function getPlainBody()
	{
		if(!isset($this->textBody))
			$this->textBody = $this->getBody(self::PLAINTEXT);
		return $this->textBody;
	}

	/**
	 * return string - UTF8 encoded
	 */
	public function getHTMLBody()
	{
		if(!isset($this->htmlBody))
			$this->htmlBody = $this->getBody(self::HTML);
		return $this->htmlBody;
	}

	public function getDate() {
		$date = $this->getHeader('date');
		if (!empty($date) && strtotime($date) === false && preg_match('/(?<date>\d{1,2} [a-zA-Z]{3} \d{4} [\d:]+ ([+-]\d{4}|[A-Z]+))/', $date, $m))
			$date = $m['date'];
		return $date;
	}

	public function getHeaderArray($headerName) {
		$headerName = strtolower($headerName);
		if (isset($this->rawFields[$headerName])) {
			$header = $this->rawFields[$headerName];
			if (!is_array($header))
				$header = [$header];
			return $header;
		}
		return [];
	}

	/**
	 * N.B.: if the header doesn't exist an empty string is returned
	 *
	 * @param string $headerName - the header we want to retrieve
	 * @return string|array - the value of the header
	 */
	public function getHeader($headerName, $returnArray = false)
	{
		$headerName = strtolower($headerName);
		if($returnArray){
			if(isset($this->rawFieldsArray[$headerName]))
				return $this->rawFieldsArray[$headerName];
			else
				return [];
		}
		else{
			if (isset($this->rawFields[$headerName]))
			{
				if (is_array($this->rawFields[$headerName]))
					return end($this->rawFields[$headerName]);
				else
					return $this->rawFields[$headerName];
			}
			return '';
		}
	}

	public function setHeader($headerName, $value){
		$this->rawFields[strtolower($headerName)] = $value;
		$this->rawFieldsArray[strtolower($headerName)] = [$value];
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->rawFields;
	}

	/**
	 * @return array
	 */
	public function getRawHeaders()
	{
		return $this->rawHeaders;
	}

	/**
	 * @return array
	 */
	public function getRawBody() {
		return $this->rawBodyLines;
	}

	/**
	 *
	 * @param string $line
	 * @return boolean
	 */
	public static function isNewLine($line)
	{
		$line = str_replace("\r", '', $line);
		$line = str_replace("\n", '', $line);

		return (strlen($line) === 0);
	}

	/**
	 *
	 * @param string $line
	 * @return boolean
	 */
	private function isLineStartingWithPrintableChar($line)
	{
		return preg_match('/^[A-Za-z]/', $line);
	}

	/**
	 *
	 * @return integer
	 */
	public function countAttachments(){
		return count($this->rawAttachments);# + count($this->alternativeBodies);
	}

	/**
	 *
	 * @return integer
	 */
	public function countAlternatives(){
		return count($this->alternativeBodies);# + count($this->alternativeBodies);
	}

	/**
	 *
	 * @param integer $pos
	 * @return array
	 */
	public function getAttachment($pos){
		if(isset($this->rawAttachments[$pos])){
			return $this->rawAttachments[$pos];
		}else{
			return null;
		}
	}

	/**
	 *
	 * @return array
	 */
	public function getAttachments(){
		return $this->rawAttachments;
	}

	/**
	 *
	 * @param integer $pos
	 * @param string $headerName
	 * @return string
	 */
	public function getAttachmentHeader($pos,$headerName, $allowArray = false)
	{
		$headerName = strtolower($headerName);
		if ($pos >= $this->countAttachments()){
			$source = &$this->alternativeBodies;
			$pos -= $this->countAttachments();
		} else {
			$source = &$this->rawAttachments;
		}

		if(isset($source[$pos]['headers'][$headerName])){
			if (is_array($source[$pos]['headers'][$headerName]) && !$allowArray)
				return $source[$pos]['headers'][$headerName][0];
			else
				return $source[$pos]['headers'][$headerName];
		}else{
			return '';
		}
	}

	/**
	 *
	 * @param mixed $pos
	 * @return array
	 */
	public function getAttachmentBody($pos){

		if ($pos >= $this->countAttachments()){
			$source = &$this->alternativeBodies;
			$pos -= $this->countAttachments();
		} else {
			$source = &$this->rawAttachments;
		}

		if(is_array($pos) && 2 == count($pos)){
			if(!empty($source[$pos[0]]['parser']) && $source[$pos[0]]['parser'] instanceof PlancakeEmailParser){
				/** @var PlancakeEmailParser $parser */
				$parser = $source[$pos[0]]['parser'];
				return $parser->getAttachmentBody($pos[1]);
			}else{
				return '';
			}
		}

		if(isset($source[$pos])){
			$headers=&$source[$pos]['headers'];
            if (isset($headers['content-transfer-encoding'])) {
                if (is_array($headers['content-transfer-encoding'])) {
                    $cte = $headers['content-transfer-encoding'][0];
                }
                else {
                    $cte = $headers['content-transfer-encoding'];
                }
            }
			$encoding = isset($cte) ? strtolower($cte) : null;

			$body=implode("\n",$source[$pos]['body']);
			switch($encoding){
				case 'base64':
					if($this->isImapExtensionAvailable){
						return imap_base64($body);
					}else{
						return base64_decode($body);
					}
				case 'quoted-printable':
					return  quoted_printable_decode("".$body);
				default:
					return $body;
			}
		}else{
			return "";
		}
	}

    public function searchAttachmentByType($type)
    {
        $matches = [];
        foreach($this->rawAttachments as $index => $attachment){
            if (!empty($attachment['headers'])
                && !empty($attachment['headers']['content-type'])
                && strcasecmp($attachment['headers']['content-type'], $type) === 0) {
                $matches[] = $index;
            }
        }
        return $matches;
    }

	public function searchAttachmentByName($pattern){
		$matches = [];
		foreach($this->rawAttachments as $index => $rawAttachment){
			if(!empty($rawAttachment['headers']) && is_array($rawAttachment['headers'])){
				foreach($rawAttachment['headers'] as $header){
					if(!is_array($header) && (preg_match("/name\*?=\s*(['\"]){$pattern}\\1/iu", $header) || preg_match("/name\*?={$pattern}$/iu", $header))){
						$matches[] = $index;
						continue(2);
					}
				}
			}
		}
		return $matches;
	}

	/**
	 *
	 * @return bool
	 *
	 * checks when message has a forwarded or included message
	 */
	public function hasIncludedMessage(){
		$res=false;
		if($this->countAttachments()){
			foreach($this->rawAttachments as &$attach){
				if(isset($attach['parser']) && $attach['parser'] instanceof PlancakeEmailParser){
					$res=true;
					break;
				}
			}
		}
		return $res;
	}


	/**
	 * @return object or null
	 *
	 *  get PlancakeEmailParser object if one is attached
	 */
	public function getIncludedMessage(){
		if($this->countAttachments()){
			foreach($this->rawAttachments as &$attach){
				if(isset($attach['parser']) && $attach['parser'] instanceof PlancakeEmailParser){
					return $attach['parser'];
				}
			}
		}
		return null;
	}

	/**
	 * rtf -> text methods, we'll see how it works out
	 * @link https://github.com/rembish/TextAtAnyCost
	 */
	protected function rtf_isPlainText($s) {
		$failAt = array("*", "fonttbl", "colortbl", "datastore", "themedata", "stylesheet", "info", "picw", "pich");
		for ($i = 0; $i < count($failAt); $i++)
			if (!empty($s[$failAt[$i]])) return false;
		return true;
	}

	# Mac Roman charset for czech layout
	protected function from_macRoman($c) {
		$table = array(
			0x83 => 0x00c9, 0x84 => 0x00d1, 0x87 => 0x00e1, 0x8e => 0x00e9, 0x92 => 0x00ed,
			0x96 => 0x00f1, 0x97 => 0x00f3, 0x9c => 0x00fa, 0xe7 => 0x00c1, 0xea => 0x00cd,
			0xee => 0x00d3, 0xf2 => 0x00da
		);
		if (isset($table[$c]))
			$c = "&#x".sprintf("%04x", $table[$c]).";";
		return $c;
	}

	public function rtf2text($text) {

		# Speeding up via cutting binary data from large rtf's.
		if (strlen($text) > 1024 * 1024) {
			$text = preg_replace("#[\r\n]#", "", $text);
			$text = preg_replace("#[0-9a-f]{128,}#is", "", $text);
		}

		# For Unicode escaping
		$text = str_replace("\\'3f", "?", $text);
		$text = str_replace("\\'3F", "?", $text);

		$document = "";
		$stack = array();
		$j = -1;

		$fonts = array();

		for ($i = 0, $len = strlen($text); $i < $len; $i++) {
			$c = $text[$i];

			switch ($c) {
				case "\\":
					$nc = $text[$i + 1];

					if ($nc == '\\' && $this->rtf_isPlainText($stack[$j])) $document .= '\\';
					elseif ($nc == '~' && $this->rtf_isPlainText($stack[$j])) $document .= ' ';
					elseif ($nc == '_' && $this->rtf_isPlainText($stack[$j])) $document .= '-';
					elseif ($nc == '*') $stack[$j]["*"] = true;
					elseif ($nc == "'") {
						$hex = substr($text, $i + 2, 2);
						if ($this->rtf_isPlainText($stack[$j])) {
							if (!empty($stack[$j]["mac"]) || @$fonts[$stack[$j]["f"]] == 77)
								$document .= $this->from_macRoman(hexdec($hex));
							else
								$document .= "&#".hexdec($hex).";";
						}
						$i += 2;
					} elseif ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
						$word = "";
						$param = null;

						for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) {
							$nc = $text[$k];
							if ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
								if (empty($param))
									$word .= $nc;
								else
									break;
							} elseif ($nc >= '0' && $nc <= '9')
								$param .= $nc;
							elseif ($nc == '-') {
								if (empty($param))
									$param .= $nc;
								else
									break;
							} else
								break;
						}
						$i += $m - 1;

						$toText = "";
						switch (strtolower($word)) {
							case "u":
								$toText .= html_entity_decode("&#x".sprintf("%04x", $param).";");
								$ucDelta = !empty($stack[$j]["uc"]) ? @$stack[$j]["uc"] : 1;
								/*for ($k = 1, $m = $i + 2; $k <= $ucDelta && $m < strlen($text); $k++, $m++) {
									$d = $text[$m];
									if ($d == '\\') {
										$dd = $text[$m + 1];
										if ($dd == "'")
											$m += 3;
										elseif($dd == '~' || $dd == '_')
											$m++;
									}
								}
								$i = $m - 2;*/
								#$i += $m - 2;
								if ($ucDelta > 0)
									$i += $ucDelta;
								break;
							case "par": case "page": case "column": case "line": case "lbr":
							$toText .= "\n";
							break;
							case "emspace": case "enspace": case "qmspace":
							$toText .= " ";
							break;
							case "tab": $toText .= "\t"; break;
							case "chdate": $toText .= date("m.d.Y"); break;
							case "chdpl": $toText .= date("l, j F Y"); break;
							case "chdpa": $toText .= date("D, j M Y"); break;
							case "chtime": $toText .= date("H:i:s"); break;
							case "emdash": $toText .= html_entity_decode("&mdash;"); break;
							case "endash": $toText .= html_entity_decode("&ndash;"); break;
							case "bullet": $toText .= html_entity_decode("&#149;"); break;
							case "lquote": $toText .= html_entity_decode("&lsquo;"); break;
							case "rquote": $toText .= html_entity_decode("&rsquo;"); break;
							case "ldblquote": $toText .= html_entity_decode("&laquo;"); break;
							case "rdblquote": $toText .= html_entity_decode("&raquo;"); break;

							case "bin":
								$i += $param;
								break;

							case "fcharset":
								$fonts[@$stack[$j]["f"]] = $param;
								break;

							default:
								$stack[$j][strtolower($word)] = empty($param) ? true : $param;
								break;
						}
						if ($this->rtf_isPlainText($stack[$j]))
							$document .= $toText;
					} else $document .= " ";

					$i++;
					break;
				case "{":
					if ($j == -1)
						$stack[++$j] = array();
					else
						array_push($stack, $stack[$j++]);
					break;
				case "}":
					array_pop($stack);
					$j--;
					break;
				case "\0": case "\r": case "\f": case "\b": case "\t": break;
				case "\n":
					$document .= " ";
					break;
				default:
					if ($this->rtf_isPlainText($stack[$j]))
						$document .= $c;
					break;
			}
		}
		return html_entity_decode(iconv("windows-1250", "utf-8", $document), ENT_QUOTES, "UTF-8");
	}



}
?>