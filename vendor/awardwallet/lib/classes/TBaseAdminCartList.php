<?

require_once(__DIR__."/TBaseList.php");

class TBaseAdminCartList extends TBaseList
{
	//return edit links html
	function GetEditLinks()
	{
		$arFields = &$this->Query->Fields;
		$s = "";
		if(!$this->ReadOnly){
			if($this->AllowDeletes)
				$s .= "<a href=edit.php?ID={$arFields[$this->KeyField]}{$this->URLParamsString}>Edit</a>";
			if( $this->AllowDeletes && !$this->MultiEdit )
				$s .= " | <input type=hidden name=sel{$arFields[$this->KeyField]} value=\"\">\n<a href='#' onclick=\"if(confirm('Are you sure you want to delete this record?')){ form = document.forms['list_{$this->Table}']; form.sel{$arFields[$this->KeyField]}.value='1'; form.action.value='delete'; form.submit();} return false;\">Delete</a>";
		}
		$s .= " <a href='/lib/admin/cart/contents.php?CartID={$arFields['CartID']}'>Contents</a>";
		return $s;
	}

}

?>