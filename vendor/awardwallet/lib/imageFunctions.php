<?php

// -----------------------------------------------------------------------
// image related functions
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

// scale image size to defined. keep aspect ratio. return NULL or error message
// -----------------------------------------------------------------------
function ScaleImage($sSrcFile, $sDstFile, $nTargetWidth, $nTargetHeight)
{
    // get image info
    $arImageSize = getimagesize($sSrcFile);
    $nSrcWidth = $arImageSize[0];
    $nSrcHeight = $arImageSize[1];
    if (($nSrcWidth == 0) || ($nSrcHeight == 0))
        return "Can't detect image size";
    if (!preg_match("/\.(\w+)$/i", $sSrcFile, $arMatches))
        DieTrace("ScaleImage: Can't detect source file extension");
    $sSrcExt = strtolower($arMatches[1]);
    // check , may be image already match new size
    $srcx = 0;
    $srcy = 0;
    if (($nSrcWidth <= $nTargetWidth) && ($nSrcHeight <= $nTargetHeight)) {
        $nNewWidth = $nSrcWidth;
        $nNewHeight = $nSrcHeight;
    } else {
        // image too big. calculate new size, keep aspect ratio
        $nWidthRatio = floatval($nSrcWidth) / $nTargetWidth;
        $nHeightRatio = floatval($nSrcHeight) / $nTargetHeight;
        $nRatio = max($nWidthRatio, $nHeightRatio);
        if ((defined('THUMBNAIL_CROP')) && (THUMBNAIL_CROP)) {
            $nNewWidth = $nTargetWidth;
            $nNewHeight = $nTargetHeight;
            if ($nWidthRatio >= $nHeightRatio) {
                $srcx = ($nSrcWidth - $nHeightRatio * $nNewWidth) / 2;
                $nSrcWidth = $nHeightRatio * $nNewWidth;
            } else {
                $srcy = ($nSrcHeight - $nWidthRatio * $nNewHeight) / 2;
                $nSrcHeight = $nWidthRatio * $nNewHeight;
            }
        } else {
            $nNewWidth = round($nSrcWidth / $nRatio);
            $nNewHeight = round($nSrcHeight / $nRatio);
        }
    }
    // resize
    $rTarget = imagecreatetruecolor($nNewWidth, $nNewHeight);
    if (!$rTarget)
        return "Can't create new image";
    $nErrorLevel = error_reporting(E_ALL ^ E_WARNING);
    switch ($sSrcExt) {
        case "jpg":
        case "jpeg":
        case "jpe":
            $rSource = imagecreatefromjpeg($sSrcFile);
            break;
        case "gif":
            $rSource = imagecreatefromgif($sSrcFile);
            break;
        case "png":
            $rSource = imagecreatefrompng($sSrcFile);
            break;
        default:
            return "Unknown picture type: " . $sSrcExt;
    }
    error_reporting($nErrorLevel);
    if (!$rSource)
        return "Invalid image format";

    imagealphablending($rTarget, false);
    $color = imagecolorallocatealpha($rTarget, 0, 0, 0, 127);
    imagefill($rTarget, 0, 0, $color);
    imagesavealpha($rTarget, true);

    if (!imagecopyresampled($rTarget, $rSource, 0, 0, $srcx, $srcy, $nNewWidth, $nNewHeight, $nSrcWidth, $nSrcHeight))
        return "Can't create resize image";
    imagedestroy($rSource);
    if (!preg_match("/\.(\w+)$/i", $sDstFile, $arMatches))
        DieTrace("TPictureFieldManager->Scale: Can't detect destination file extension");
    $sDstExt = strtolower($arMatches[1]);

    switch ($sDstExt) {
        case "jpg":
        case "jpe":
        case "jpeg":
            $bResult = imagejpeg($rTarget, $sDstFile, 80);
            break;
        case "gif":
            $bResult = imagepng($rTarget, $sDstFile);
            break;
        case "png":
            $bResult = imagepng($rTarget, $sDstFile);
            break;
        default:
            return "Unknown picture type: " . $sDstExt;
    }
    imagedestroy($rTarget);
    if (!$bResult)
        return "Can't save resized image";
    return NULL;
}

function rotbbox(&$bbox, $angle, $px, $py)
{
    $xc = ($bbox[0] + $bbox[2]) / 2.0;
    $yc = ($bbox[1] + $bbox[7]) / 2.0;
    $rad = $angle * pi() / 180.0;
    $sa = sin($rad);
    $ca = cos($rad);
    for ($i = 0; $i < 4; $i++) {
        $x = $bbox[$i * 2 + 0] - $xc;
        $y = $bbox[$i * 2 + 1] - $yc;
        $bbox[$i * 2 + 0] = intval($ca * $x + $sa * $y + $px + 0.5);
        $bbox[$i * 2 + 1] = intval(-$sa * $x + $ca * $y + $py + 0.5);
    }
}

// automatically loads image by ext
function LoadImage($sSrcFile)
{
    // load
    if (!preg_match("/\.(\w+)$/i", $sSrcFile, $arMatches))
        DieTrace("Can't detect source file extension");
    $sSrcExt = strtolower($arMatches[1]);
    if (!file_exists($sSrcFile))
        DieTrace("File not found: $sSrcFile");
    switch ($sSrcExt) {
        case "jpg":
        case "jpeg":
        case "jpe":
            $rSource = imagecreatefromjpeg($sSrcFile);
            break;
        case "gif":
            $rSource = imagecreatefromgif($sSrcFile);
            break;
        case "png":
            $rSource = imagecreatefrompng($sSrcFile);
            break;
        default:
            return "Unknown picture type: " . $sSrcExt;
    }
    return $rSource;
}

// automatically save image in right format, by extension
function SaveImage($rSource, $sDstFile)
{
    if (!preg_match("/\.(\w+)$/i", $sDstFile, $arMatches))
        DieTrace("Can't detect destination file extension");

    $sDstExt = strtolower($arMatches[1]);
    switch ($sDstExt) {
        case "jpg":
        case "jpe":
        case "jpeg":
            $bResult = imagejpeg($rSource, $sDstFile);
            break;
        case "gif":
            $bResult = imagegif($rSource, $sDstFile);
            break;
        case "png":
            $bResult = imagepng($rSource, $sDstFile);
            break;
        default:
            return "Unknown picture type: " . $sDstExt;
    }

    imagealphablending($rSource, false);

    return $bResult;
}

// create image with text label.
function CreateLabeledImage($sSrcFile, $sDstFile, $nX, $nY, $bCenter, $nAngle, $sFont, $nFontSize, $nRed, $nGreen, $nBlue, $sText)
{
    if (IsUnix() && (strpos($sFont, "/") === false))
        $sFont = '/opt/fonts/' . $sFont;
    $rSource = LoadImage($sSrcFile);
    $arImageSize = getimagesize($sSrcFile);
    $nWidth = $arImageSize[0];
    $nHeight = $arImageSize[1];
    // write label
    $rColor = imagecolorallocate($rSource, $nRed, $nGreen, $nBlue);
    if ($bCenter) {
        // bounding box
        $bbox = imagettfbbox($nFontSize, 0, $sFont, $sText);
        // baseline point for drawing non-rotated text.
        $x0 = $bbox[6];
        $y0 = -$bbox[7];
        // fixes bounding box w.r.t. image coordinate.
        $bbox[5] = -$bbox[5] + $bbox[1];
        $bbox[7] = -$bbox[7] + $bbox[3];
        $bbox[1] = 0;
        $bbox[3] = 0;
        // get the size of image.
        $sx = $nWidth;
        $sy = $nHeight;
        // center of bounding box (xc,yc);
        $xc = ($bbox[0] + $bbox[2]) / 2.0;
        $yc = ($bbox[1] + $bbox[7]) / 2.0;
        // rotation angle in radian
        $rad = $nAngle * pi() / 180.0;
        $sa = sin($rad);
        $ca = cos($rad);
        $x1 = $x0 - $xc;
        $y1 = $y0 - $yc;
        //pivot point(here, we take the center of image)
        $px = $sx / 2.0;
        $py = $sy / 2.0;
        // new baseline point for rotated text.
        $x2 = intval($x1 * $ca + $y1 * $sa + $px + 0.5);
        $y2 = intval(-$x1 * $sa + $y1 * $ca + $py + 0.5);
        $nX += $x2;
        $nY += $y2;
    }
    imagettftext($rSource, $nFontSize, $nAngle, $nX, $nY, $rColor, $sFont, $sText);
    //if( ( $nAngle > 0  ) and ( $nAngle <= 180 ) )
    //$nY = $nHeight - $nY;
    //imagettftext( $rSource, $nFontSize, $nAngle, $nX, $nY, $rColor, $sFont, $sText );
    // save
    $bResult = SaveImage($rSource, $sDstFile);
    if (!$bResult)
        return "Can't save resized image";
    return NULL;
}

// This method rotates an image in 90 degree increments (eg count should be between 1 and 3 )
function RotateImage($src, $count = 1, $bDieOnErrors = true)
{
    $arSize = @getimagesize($src);
    if ($arSize === false) {
        if ($bDieOnErrors)
            DieTrace("Failed to get image dimensions");
        else
            return false;
    }
    list($w, $h) = $arSize;
    if (($in = LoadImage($src)) === false) {
        if ($bDieOnErrors)
            DieTrace("Failed to create image from source file");
        else
            return false;
    }
    $angle = 360 - ((($count > 0 && $count < 4) ? $count : 0) * 90);
    if ($w == $h || $angle == 180) {
        $transparency = imagecolorallocatealpha($in, 0, 0, 0, 127);
        $out = imagerotate($in, $angle, $transparency, 1);
        imagealphablending($out, false);
        imagesavealpha($out, true);
    } elseif ($angle == 90 || $angle == 270) {
        $size = ($w > $h ? $w : $h);
        // Create a square image the size of the largest side of our src image
        if (($tmp = imageCreateTrueColor($size, $size)) == false)
            DieTrace("Failed create square trueColor");
        // Exchange sides
        if (($out = imageCreateTrueColor($h, $w)) == false)
            DieTrace("Failed create trueColor");

        // Now copy our src image to tmp where we will rotate and then copy that to $out
        imageCopy($tmp, $in, 0, 0, 0, 0, $w, $h);
        $transparency = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        $tmp2 = imagerotate($tmp, $angle, $transparency, 1);
        imagealphablending($tmp2, false);
        imagesavealpha($tmp2, true);

        // Now copy tmp2 to $out;
        imagecopy($out, $tmp2, 0, 0, (($angle == 270) && ($w > $h) ? abs($w - $h) : 0), (($angle == 90) && ($w < $h) ? abs($w - $h) : 0), $h, $w);
        imageDestroy($tmp);
        imageDestroy($tmp2);
    } elseif ($angle == 360) {
        imageDestroy($in);
        return true;
    }
    SaveImage($out, $src);
    imageDestroy($in);
    imageDestroy($out);
    return true;
}

?>