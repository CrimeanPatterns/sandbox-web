<?php


namespace AwardWallet\Common\Parsing;


class PdfToImg
{

    private static $cache = [];

    /**
     * @param $rawPdf
     * @param $mode ['', 'mono', 'gray']
     * @param $ext ['', 'png', 'jpeg', 'tiff']
     * @return array|null
     */
    public static function convert($rawPdf, $ext = '', $mode = '')
    {
        if (empty($rawPdf) || !in_array($mode, ['', 'mono', 'gray']) || !in_array($ext, ['', 'png', 'jpeg', 'tiff']))
            return null;
        if (!empty($mode))
            $mode = '-'.$mode;
        if (!empty($ext))
            $ext = '-'.$ext;
        $hash = md5($rawPdf).'_'.$mode.'_'.$ext;
        if (isset(self::$cache[$hash]))
            return self::$cache[$hash];
        if (!self::commandExists())
            throw new \Exception('pdftoppm command does not exist');
        $tmpName = 'pdftoppm-' . getmypid() . '-' . str_replace('.', '-', microtime(true));
        $tmpPdf = sys_get_temp_dir().'/'.$tmpName.'.pdf';
        $outputDir = sys_get_temp_dir().'/'.$tmpName;
        $imgPrefix = $outputDir.'/img';
        if (!file_put_contents($tmpPdf, $rawPdf) || !mkdir($outputDir))
            return null;
        $command = sprintf('pdftoppm -q %s %s %s %s 2> /dev/null', $mode, $ext, $tmpPdf, $imgPrefix);
        exec($command, $output, $status);
        unlink($tmpPdf);
        if ($status !== 0) {
            return null;
        }
        if (!file_exists($outputDir))
            return null;
        $result = glob($imgPrefix.'*');
        if (count($result) === 0) {
            unlink($outputDir);
            return null;
        }
        self::$cache[$hash] = $result;
        return $result;
    }

    protected static function commandExists(){
        exec("which pdftoppm", $out, $status);
        return ($status === 0);
    }
}