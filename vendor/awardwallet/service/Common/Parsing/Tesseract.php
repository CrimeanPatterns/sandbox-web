<?php


namespace AwardWallet\Common\Parsing;


class Tesseract
{

    private static $cache;

    public static function convertImg($raw)
    {
        if (empty($raw))
            return null;
        $hash = md5($raw);
        if (isset(self::$cache[$hash]))
            return self::$cache[$hash];
        $tmpName = sys_get_temp_dir().'/tesseract-'.str_replace('.', '-', microtime(true)) . '-' . bin2hex(random_bytes(4));
        if (!file_put_contents($tmpName, $raw))
            return null;
        self::$cache[$hash] = self::convertFile($tmpName);
        unlink($tmpName);
        return self::$cache[$hash];
    }

    /**
     * ext and mode arguments are the same as in PdfToImg
     * @param $raw
     * @param string $ext
     * @param string $mode
     * @param string $implode
     * @return null|string
     */
    public static function convertPdf($raw, $ext = '', $mode = '', $implode = "\n")
    {
        $hash = md5($raw).'_'.$mode.'_'.$ext;
        if (isset(self::$cache[$hash]))
            return implode($implode, self::$cache[$hash]);
        if (empty($files = PdfToImg::convert($raw, $ext, $mode)))
            return null;
        $result = [];
        foreach($files as $file) {
            $result[] = self::convertFile($file);
            unlink($file);
        }
        if (isset($file))
            rmdir(dirname($file));
        self::$cache[$hash] = array_filter($result);
        return implode($implode, self::$cache[$hash]);
    }

    private static function convertFile($file)
    {
        if (!self::commandExists())
            throw new \Exception('tesseract command does not exist');
        if (!file_exists($file))
            return null;
        $tmpOut = sys_get_temp_dir() . '/tesseract-' . '-' . str_replace('.', '-', microtime(true)) . '-' . bin2hex(random_bytes(4));
        $command = sprintf('tesseract %s %s 2> /dev/null', $file, $tmpOut);
        exec($command, $output, $status);
        $tmpOut .= '.txt';
        if (!file_exists($tmpOut))
            return null;
        $result = file_get_contents($tmpOut);
        unlink($tmpOut);
        if ($status !== 0)
            return null;
        return $result;
    }

    protected static function commandExists(){
        exec("which tesseract", $out, $status);
        return ($status === 0);
    }

}