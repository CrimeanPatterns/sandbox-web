<?php

class PDFConvert{
    /**
     * @var string contains original file
     */
    private $original;
    /**
     * @var array list of all objects in file contains sublists 'list' 'stream' & 'lines'
     */
    private $obj=array();
    /**
     * @var array for debug
     */
    private $err=array();
    /**
     * @var string version of the PDF language
     */
    private $ver='';

    /**
     * @param string $fileContains
     */
    function __construct($fileContains){
        $this->original=$fileContains;
    }

    /**
     * @return array multidimensional contains list of text lines arrangen in blocks
     */
    function textBlocks(){
        $this->obj=array();
        $this->parseObjs();
        return $this->getTextBlocks();//array($this->err,$blocks);
    }

    /**
     * @param int $id
     * @return array
     */
    private function getPageText($id){
        $blocks=array();
        $istext=false;
        $pos=array('top'=>0,'left'=>0);
        $singlet=&$this->obj[$id];
        $hdr=&$singlet['list'];
            $_stream=$singlet['stream'];
        if(isset($hdr['/Filter'])){
            $flt=$hdr['/Filter'];
            if(!is_array($hdr['/Filter']))
                $flt=array($flt);
            //$len=$hdr['/Length'];
            foreach($flt as $filter){
                if (strpos($filter,"/ASCIIHexDecode")!==false)
                    $_stream = $this->decodeAsciiHex($_stream);
                if (strpos($filter,"/ASCII85Decode")!==false)
                    $_stream = $this->decodeAscii85($_stream);
                if (strpos($filter,"/FlateDecode")!==false)
                    $_stream = gzuncompress($_stream);
            }
        }
        if(!empty($_stream)){
            $stack=array();
            //$line=$singlet['stream'];
            for(;!empty($_stream);){
                if(preg_match('#^\s*\[#s',$_stream)){
                    $stack[]=$v=$this->parseArray($_stream,false);
                }else{
                    $v=$this->parseToken($_stream);
                    if(empty($v))
                        $v=$this->parseWS($_stream);
                    else{
                        reset($v);
                        if(!in_array(key($v),array('hexString','plainString'))){
                            $v=current($v);
                        }
                    }
                    $stack[]=$v;
                }
                if($istext){
                    if($v=='ET'){
                        if(isset($lastBlock))unset($lastBlock);
                        $istext=false;
                        $stack=array();
                    }elseif($v=='TD'){
                        array_pop($stack);
                        $pos['top']=array_pop($stack);
                        $pos['left']=array_pop($stack);
                    }elseif($v=='Td'){
                        array_pop($stack);
                        $pos['top']=array_pop($stack);
                        $pos['left']=array_pop($stack);
                    }elseif($v=='Tj' or $v=='TJ'){
                        array_pop($stack);
                        $v=array_pop($stack);
                        if(isset($v['hexString']) or isset($v['plainString'])){
                            $lastBlock[]=reset($v);
                        }elseif(is_array($v)){
                            foreach($v as $k=>&$vl){
                                if(isset($vl['hexString']) or isset($vl['plainString'])){
                                    $vl=reset($vl);
                                }else{
                                    unset($v[$k]);
                                }
                            }
                            $lastBlock[]=$v;
                        }
                    }
                }
                elseif($v=='BT'){
                    if(isset($lastBlock))unset($lastBlock);
                    $lastBlock=array();
                    $blocks[]=&$lastBlock;
                    $istext=true;
                    $stack=array();
                }
            }
        }
        return $blocks;
    }
    private function getTextBlocks(){
        $r=$this->obj['trailer']['/Root'];
        $r=$this->parseWS($r);
        $cat=$this->obj[$r]['list']['/Pages'];
        $cat=$this->parseWS($cat);
        $kids=$this->obj[$cat]['list']['/Kids'];
        $pages=array();
        foreach($kids as $kid){
            $kid=$this->parseWS($kid);
            $content=$this->obj[$kid]['list']['/Contents'];
            if(is_array($content)){
                $content=reset($content);
            }
            $content=$this->parseWS($content);
            $pages[]=$this->getPageText($content);
        }
        return $pages;

    }

    /**
     * Split line at first delimiter
     *
     * @param string $line
     * @return array of preceeding string and delimeter
     */
    private function parseDelim(&$line){
        $l=preg_split('/([()\[\]<>{}\/%])/',$line,2,PREG_SPLIT_DELIM_CAPTURE);
        $line=array_pop($l);
        return $l;
    }

    /**
     * Split line at first line break
     *
     * @param string $line
     * @return string preceeding break
     */
    private function parseNL(&$line){
        $l=preg_split('/(\r?\n|\r|$)/',$line,2);
        $line=array_pop($l);
        return $l[0];
    }

    /**
     * Split line at white space right after meaningful chars
     *
     * @param string $line
     * @return string preceeding space
     */
    private function parseWS(&$line){
        $l=preg_split('/(\s+|$)/s',$line,2);
        $line=array_pop($l);
        if(empty($l[0]))return $this->parseWS($line);
        return $l[0];
    }

    /**
     * Get PDF text string
     *
     * @param string $line
     * @return string
     */
    private function parseString(&$line){
        $res='';
        while(preg_match('#^(.*?)(?<!\\\\)([()])(.*)$#s',$line,$ar)){
            if($ar[2]==')'){
                $line=$ar[3];
                $res.=$ar[1];
                break;
            }elseif($ar[2]=='('){
                $line=$ar[3];
                $res.=$ar[1].'('.$this->parseString($line).')';
            }
        }
        return $res;
    }

    /**
     * Get PDF Token
     *
     * @param string $line
     * @param string $arr if inside array is not empty
     * @return array|string
     */
    private function parseToken(&$line,$arr=''){
        if($arr=='<<'){
            $arr='(?:\s*>>)?';
        }elseif($arr=='['){
            $arr='(?:\s*\])?';
        }
        if(preg_match('/^\s*(true|false)('.$arr.'\s+(.+))?$/is',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('bool'=>$ar[1]);
        }
        if(preg_match('/^\s*(null)('.$arr.'\s+(.+))?$/is',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('null'=>$ar[1]);
        }
        if(preg_match('/^\s*(\d+\s+\d+\s+R)('.$arr.'\s+(.+))?$/s',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('recursive'=>$ar[1]);
        }
        if(preg_match('/^\s*([+-]?(?:(?:\d+)?\.)?\d+)('.$arr.'\s+(.+))?$/is',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('numeric'=>$ar[1]);
        }
        if(preg_match('/^\s*(\/[!-~]+?)('.$arr.'\s+(.+))?$/s',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('name'=>$ar[1]);
        }
        if(preg_match('/^\s*<([0-9a-fA-F]+)>('.$arr.'\s+(.+))?$/s',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('hexString'=>hex2bin($ar[1]));
        }
        if(preg_match('/^\s*(\()(.+)?$/s',$line,$ar)){
            if(!empty($ar[2]))$line=$ar[2];
            else $line='';
            return array('plainString'=>$this->parseString($line));
        }
        return "";
    }

    /**
     * @param $line
     * @param $removeType
     * @return array
     */
    private function parseArrayAssoc(&$line,$removeType){
        $res=array();
        while(!preg_match('/^\s*>>/',$line,$ar)){
            $n=$this->parseToken($line,'<<');
            if(preg_match('/^\s*(<<|\[)/',$line)){
                $res[reset($n)]=$this->parseArray($line,$removeType);
            }else{
                $v=$this->parseToken($line,'<<');
                if($removeType)$v=reset($v);
                $res[reset($n)]=$v;
            }
        }
        $line=substr($line,strlen($ar[0]));
        return $res;
    }

    /**
     * @param $line
     * @param $removeType
     * @return array
     */
    private function parseArrayIndex(&$line,$removeType){
        $res=array();
        while(!preg_match('/^\s*\]/',$line,$ar)){
            if(preg_match('/^\s*(<<|\[)/',$line)){
                $res[]=$this->parseArray($line,$removeType);
            }else{
                $v=$this->parseToken($line,'[');
                if($removeType)$v=reset($v);
                $res[]=$v;
            }
        }
        $line=substr($line,strlen($ar[0]));
        return $res;
    }

    /**
     * @param $line
     * @param bool $removeType
     * @return array
     */
    private function parseArray(&$line,$removeType=true){
        $l=preg_split('/(<<|\[)/',$line,2,PREG_SPLIT_DELIM_CAPTURE);
        $line=array_pop($l);
        $dlm=array_pop($l);
        if($dlm=='<<'){
            $resarr=$this->parseArrayAssoc($line,$removeType);
        }else{
            $resarr=$this->parseArrayIndex($line,$removeType);
        }
        return $resarr;
    }

    /**
     * @param $line
     * @return array
     */
    private function parseGetObj(&$line){
        $res='';
        for(;!empty($line);){
            $ln=preg_split('/(\r?\n|\r|$)/',$line,2,PREG_SPLIT_DELIM_CAPTURE);
            $line=array_pop($ln);
            if(!preg_match('/^\s*endobj\s*/is',$ln[0],$ar)){
                $res.=implode($ln);
            }else{
                $resarr=array();
                if(preg_match('#^\s*(<<|\[)\s*#',$res,$ar)){
                    $resarr['list']=$this->parseArray($res);
                }
                if(preg_match('#(^\s*stream)(\s+.*)?$#s',$res,$ar)){
                    $res=substr($res,strlen($ar[1]));
                    $res=array_pop(preg_split('/(\r?\n|\r|$)/',$res,2));
                    //$this->parseNL($res);
                    //file_put_contents('/www/awardwallet/wget/fileobj'.func_get_arg(1).'stream.txt',$res);
                    $resarr['stream']='';
                    if(($pos=strpos($res,'endstream'))===false){
                        $pos=strlen($res);
                    }
                    $resarr['stream']=substr($res,0,$pos);
                    //file_put_contents('/www/awardwallet/wget/fileobj'.func_get_arg(1).'stream-.txt',$resarr['stream']);
                }
                return $resarr;
            }
        }
    }

    /**
     *
     */
    private function parseObjs(){
        $ln='';
        for($line=$this->original;!empty($line);$ln=$this->parseNL($line)){
            if(preg_match('#^\s*%(PDF-\d\.\d)#',$ln,$ar)){
                $this->ver=$ar[1];
                break;
            }
        }
        for(;!empty($line);){
            /*if(preg_match('#^\s*%%EOF#',$line))break;
            else*/
            if(preg_match('/^(\s*(\d+)\s+(\d+)\s+obj)\s*/is',$line,$ar)){
                $line=substr($line,strlen($ar[1]));
                //$this->parseNL($line);
                $this->obj[$ar[2]]=$this->parseGetObj($line,$ar[2]);
            }elseif(preg_match('/^\s*trailer\s*/is',$line,$ar)){
                $line=substr($line,strlen($ar[0]));
                //$this->parseNL($line);
                if(!isset($this->obj['trailer']))$this->obj['trailer']=array();
                $this->obj['trailer']=array_merge($this->obj['trailer'],$this->parseArray($line));
            }else $this->parseNL($line);
        }
    }

    /**
     *
     * Decode stream
     *
     * @param string $input
     * @return string
     */
    private function decodeAsciiHex($input) {
        $output = "";

        $isOdd = true;
        $isComment = false;

        for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
            $c = $input[$i];

            if($isComment) {
                if ($c == '\r' || $c == '\n')
                    $isComment = false;
                continue;
            }

            switch($c) {
                case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
                case '%':
                    $isComment = true;
                    break;

                default:
                    $code = hexdec($c);
                    if($code === 0 && $c != '0')
                        return "";

                    if($isOdd)
                        $codeHigh = $code;
                    else
                        $output .= chr($codeHigh * 16 + $code);

                    $isOdd = !$isOdd;
                    break;
            }
        }

        if($input[$i] != '>')
            return "";

        if($isOdd)
            $output .= chr($codeHigh * 16);

        return $output;
    }

    /**
     *
     * Decode stream
     *
     * @param string $input
     * @return string
     */
    private function decodeAscii85($input) {
        $output = "";

        $isComment = false;
        $ords = array();

        for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
            $c = $input[$i];

            if($isComment) {
                if ($c == '\r' || $c == '\n')
                    $isComment = false;
                continue;
            }

            if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
                continue;
            if ($c == '%') {
                $isComment = true;
                continue;
            }
            if ($c == 'z' && $state === 0) {
                $output .= str_repeat(chr(0), 4);
                continue;
            }
            if ($c < '!' || $c > 'u')
                return "";

            $code = ord($input[$i]) & 0xff;
            $ords[$state++] = $code - ord('!');

            if ($state == 5) {
                $state = 0;
                for ($sum = 0, $j = 0; $j < 5; $j++)
                    $sum = $sum * 85 + $ords[$j];
                for ($j = 3; $j >= 0; $j--)
                    $output .= chr($sum >> ($j * 8));
            }
        }
        if ($state === 1)
            return "";
        elseif ($state > 1) {
            for ($i = 0, $sum = 0; $i < $state; $i++)
                $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
            for ($i = 0; $i < $state - 1; $i++)
                $output .= chr($sum >> ((3 - $i) * 8));
        }

        return $output;
    }

    /**
     *
     * Decode stream
     *
     * @param string $input
     * @return string
     */
    private function decodeFlate($input) {
        // Наиболее частый тип сжатия потока данных в PDF.
        // Очень просто реализуется функционалом библиотек.
        return gzdeflate($input);
    }


}