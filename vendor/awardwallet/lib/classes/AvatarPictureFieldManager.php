<?php

require_once(__DIR__ . "/TPictureFieldManager.php");

class AvatarPictureFieldManager extends TPictureFieldManager
{
    function InputHTML($sFieldName = null, $arField = null)
    {
        $avatar = (isset($this->FileVer)) ? "<a href=\"#\" class=\"photo\" onclick=\"openAWindow('{$this->PictureURL('Large')}', 'Preview', $this->picWidth + 50, $this->picHeight + 50, 1, 0); return false;\"><img src=\"{$this->PictureURL('small')}\" alt=\"\"></a>" : "<a href=\"#\" class=\"photo\" onclick=\"return false;\"><img src=\"/assets/awardwalletnewdesign/img/no-avatar.gif\" alt=\"\"></a>";

	    $ret =  "<div class=\"photo-blk\">
	        <div class=\"photo-prev\">
	        {$avatar}";
	    if (isset($this->FileVer)) {
		    $ret .= "<ul class=\"action-buttons\">
	            <li>
	                <a href=\"#\" class=\"btn-silver\" onclick=\"return rotate{$this->FieldName}Picture(3)\">
	                    <i class=\"icon-rotate-left\"></i>
	                </a>
	            </li>
	            <li>
	                <a href=\"#\" class=\"btn-silver\" onclick=\"return rotate{$this->FieldName}Picture(2)\">
	                    <i class=\"icon-rotate-around\"></i>
	                </a>
	            </li>
	            <li>
	                <a href=\"#\" class=\"btn-silver\" onclick=\"return rotate{$this->FieldName}Picture(1)\">
	                    <i class=\"icon-rotate-right\"></i>
	                </a>
	            </li>
	        </ul>
	        <input type=\"hidden\" name=\"{$this->FieldName}RotateAngle\" value=\"\">
	        <input type=\"hidden\" name=\"{$this->FieldName}Changed\" value=\"{$this->FileChanged}\">";
	    }
	    $ret .= "</div>";
	    $ret .= "<div class=\"photo-action\">";
	    if (isset($this->FileVer)) {
		    $ret .= "<div class=\"delete-photo\">
		            <input type=\"checkbox\" class=\"checkbox\" id=\"delete-photo\" name=\"{$this->FieldName}Delete\" value='1'>
		            <label class=\"label\" for=\"delete-photo\">Delete</label>
		        </div>";
	    }
	    $ret .= "<div class=\"styled-file\">
            <span>Choose File</span>
            <input type=\"file\" name=\"{$this->FieldName}\" id=\"picFile\">
        </div>
        <div class=\"file-name\"></div>
        <button class=\"btn-silver\" id=\"uploadPicture\">Upload picture</button>
    </div>";

        if(SITE_MODE != SITE_MODE_BUSINESS){
            $ret .= "<div class=\"get-picture\">
                    <span class=\"silver\">or get picture from</span>
                    <div class=\"other-picture\">
                        <a href=\"\" id=\"fromFacebook\" class=\"btn-silver f-left\"><i class=\"icon-fb\"></i>Facebook</a>
                        <div class=\"loader big\" id=\"spinner\"></div>
                        <a href=\"\" id=\"fromGravatar\" class=\"btn-silver f-right\"><i class=\"icon-gravatar\"></i>Gravatar</a>
                    </div>
                    </div>";
        }

        $ret .= "</div>

{$this->HiddenHTML()}
<script>
    function rotate{$this->FieldName}Picture(angle) {
        f = document.forms['editor_form'];
        f.{$this->FieldName}RotateAngle.value = angle;
        f.DisableFormScriptChecks.value = '1';
        CheckForm(f);
        f.submit();
        return false;
    }
</script>";
	    return $ret;
    }
}