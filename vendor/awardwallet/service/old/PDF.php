<?php

class PDF {
    const MODE_SIMPLE = 1;
    const MODE_COMPLEX = 2;

    protected static $commandData = [
        'pdftohtml' => [
            'arguments' => [
                self::MODE_COMPLEX => '-i -q -nodrm -hidden -s -c',
                self::MODE_SIMPLE  => '-i -q -nodrm -hidden -noframes'
            ],
            'outputFilePostfix' => [
                self::MODE_COMPLEX => '-html.html',
                self::MODE_SIMPLE => '.html',
            ]
        ],
        'pdftotext' => [
            'arguments' => '-layout -nopgbrk',
        ]
    ];

    private static $cache = [];

    protected static function convert($raw, $command, $mode = null){
        if(empty($raw)){
            return null;
        }

        $hash = md5($raw . $command. $mode);
        if(array_key_exists($hash, self::$cache))
            return self::$cache[$hash];
        self::$cache[$hash] = null;
        while(count(self::$cache) > 10)
            array_shift(self::$cache);

        if(!self::commandExists($command)){
            throw new \Exception("{$command} is not installed");
        }
        $tmpName = "/tmp/{$command}-" . getmypid() . "-" . str_replace(' ', '_', microtime(null));
        $pdfFile = $tmpName . '.pdf';
        if(isset(self::$commandData[$command]['outputFilePostfix'])){
            if(is_array(self::$commandData[$command]['outputFilePostfix'])){
                $postfix = self::$commandData[$command]['outputFilePostfix'][$mode];
            }else{
                $postfix = self::$commandData[$command]['outputFilePostfix'];
            }
            $outputFile = $tmpName . $postfix;
        }else{
            $outputFile = $tmpName;
        }
        $writeStatus = file_put_contents($pdfFile, $raw);
        if(!$writeStatus){
            return null;
        }
        if (!self::isPdfFile($pdfFile)) {
            unlink($pdfFile);
            return null;
        }
        if(is_array(self::$commandData[$command]['arguments'])){
            $arguments = self::$commandData[$command]['arguments'][$mode];
        }else{
            $arguments = self::$commandData[$command]['arguments'];
        }
        exec("{$command} {$arguments} {$pdfFile} {$tmpName} 2>/dev/null", $output, $status); // hide 'Document has copy-protection bit set.'
        if(0 !== $status){
            unlink($pdfFile);
            return null;
        }
        if(!file_exists($outputFile)){
            unlink($pdfFile);
			throw new \Exception("{$command}: unable to find output file");
        }
        $output = file_get_contents($outputFile);
        unlink($pdfFile);
        unlink($outputFile);

        self::$cache[$hash] = $output;
        return $output;
    }

    public static function isPdfFile($filePath) {
		// todo: temporary, bcd/it-1788393.eml does not get detected as pdf file
		return true;
		exec("file -b {$filePath}", $out, $status);
        return (0 === $status && preg_match('/PDF/ims', implode(' ', $out)));
    }

    public static function convertToHtml($raw, $mode = self::MODE_COMPLEX){
        return self::convert($raw, 'pdftohtml', $mode);
    }

    public static function convertToText($raw, $isTrim = true){
        $r = self::convert($raw, 'pdftotext');
        return $isTrim? trim($r) : $r;
    }

    public static function checkExistsFunctions(){
        return self::commandExists('pdftohtml') && self::commandExists('pdftotext');
    }

    protected static function commandExists($command){
        exec("which {$command}", $out, $status);
        return ($status === 0);
    }

    /**
     * Text data in complex mode positioned via "position:absolute;" css attribute.
     * Gruop text nodes taking into account "top:" Y-position with some variation.
     * O(n^2)
     * @param HttpBrowser $http
     * @param int $maxYdeviation
     * @param mixed $filterNodeValue
     * @return bool
     */
    public static function sortNodes($http, $maxYdeviation = 3, $filterNodeValue = null){

        $nodes = [];
        $pageOffsetY = 0;
        // converted document can be splitted by pages <div id="page1-div">
        $pageNodes = $http->XPath->query('//div[contains(@id, "page")]');
        foreach($pageNodes as $pageNode){
            $rawNodes = $http->XPath->query('.//p[contains(@style, "position:absolute") and not(.//p[contains(@style, "position:absolute")])]', $pageNode);
            foreach ($rawNodes as $rawNode) {
                if (preg_match('/top:(\d+)px;left:(\d+)px/ims', $rawNode->getAttribute('style'), $styleMatches)) {
                    $node = [
                        'topOrig' => (int)$styleMatches[1],
                        'leftOrig' => (int)$styleMatches[2],
                        'top' => (int)$styleMatches[1] + $pageOffsetY
                    ];
                    $nodeFull = [
                        &$node,
                        &$rawNode
                    ];
                    if (isset($nodes[$node['top']]) || empty($nodes)) {
                        $nodes[$node['top']][] = & $nodeFull;
                    } else {
                        // add node to existing line with approximation set by $maxYdeviation variabl
                        $nearbyLines = [];
                        foreach ($nodes as $lineTop => &$lineNodes) {
                            if (abs($node['top'] - $lineTop) <= $maxYdeviation) {
                                $nearbyLines[] = $lineTop;
                            }
                        }
                        if(empty($nearbyLines)){
                            $nodes[$node['top']][] = & $nodeFull;
                        }else{
                            sort($nearbyLines);
                            $nodes[$nearbyLines[0]][] = & $nodeFull;
                        }
                        unset($lineNodes);
                    }
                }
                unset($node);
                unset($nodeFull);
                unset($rawNode);
            }
            ksort($nodes);
            end($nodes);
            $pageOffsetY = key($nodes);
            reset($nodes);
        }

        if (!empty($nodes)) {
            $document = new DOMDocument();
            ksort($nodes);
            foreach ($nodes as $lineTop => &$lineNodes) {
                // sort by X
                usort(
                    $lineNodes,
                    function (&$node1, &$node2){
                        if ($node1[0]['leftOrig'] < $node2[0]['leftOrig']) {
                            return -1;
                        } elseif ($node1[0]['leftOrig'] > $node2[0]['leftOrig']) {
                            return 1;
                        } else {
                            return 0;
                        }
                    }
                );
                $line = $document->createElement('line');
                foreach ($lineNodes as &$node) {
                    // DEBUG ONLY
                    $node[1]->removeAttribute('style');
                    $node[1]->setAttribute('style', "position:absolute;top:{$node[0]['top']}px;left:{$node[0]['leftOrig']}px;white-space:nowrap");
                    $node[1]->setAttribute('left', $node[0]['leftOrig']);
                    $importedNode = $document->importNode($node[1], true);
                    $line->appendChild($importedNode);
                    // DEBUG ONLY
                    //$document->appendChild($document->importNode($node[1], true));
                }
                unset($node);
                $document->appendChild($line);
            }
            unset($lineNodes);
            // sort by Y
            ksort($nodes);

            $http->DOM = $document;
            $http->XPath = new DOMXPath($http->DOM);

            if(isset($filterNodeValue)){
                $textNodes = $http->XPath->query('//text()');
                foreach($textNodes as $textNode){
                    if(is_callable($filterNodeValue)){
                        $textNode->nodeValue = $filterNodeValue($textNode->nodeValue);
                    }elseif(true === $filterNodeValue){
                        $textNode->nodeValue = CleanXMLValue($textNode->nodeValue);
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }
} 