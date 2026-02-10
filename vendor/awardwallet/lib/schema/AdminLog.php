<?
require_once(__DIR__ . "/Forum.php");

class TAdminLogSchema extends TForumSchema
{
	var $forumNumber = 3;
	function TAdminLogSchema(){
		parent::TForumSchema();
		unset($this->Fields['FullName']);
		unset($this->Fields['Email']);
		unset($this->Fields['PostTime']);
		$this->ListClass = "TLogList";
		$this->bIncludeList = false;
	}
	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		$form->SQLParams = $form->SQLParams + array( "PostTime" => "now()");
		$form->SuccessURL = "/lib/admin/";
	}

	function TuneList( &$list ){
		parent::TuneList( $list );
		$list->bShowArchiveLink = False;
		$list->CanAdd = true;
	}
}

class TLogList extends TBaseForumList{
// draw one row for News 3
	function DrawRow()
	{
		$objRS = &$this->Query;
?>
<tr>
	<td style="font-size: 10px; color: #666666;">Last updated on: <?=$objRS->Fields["PostTime"]?> Pacific Time</td>
	<td align="right"><?=$this->GetEditLinks();?></td>
</tr>
<tr>
	<td colspan="2">
	<div align="center" style="font-weight: bold;"><?=$objRS->Fields["Title"]?></div><br>
	<?=$objRS->Fields["BodyText"]?>
	</td>
</tr>
<?
	}
	function GetEditLinks(){
		$arFields = &$this->Query->Fields;
		return "<a href=/lib/admin/table/edit.php?ID={$arFields[$this->KeyField]}{$this->URLParamsString}&Schema=AdminLog>Edit</a> | <input type=hidden name=sel{$arFields[$this->KeyField]} value=\"\"><a href='#' onclick=\"if(confirm('Are you sure you want to delete this record?')){ form = document.forms['list_{$this->Table}']; form.sel{$arFields[$this->KeyField]}.value='1'; form.action.value='delete'; form.submit();} return false;\">Delete</a>";
	}
	function Draw(){
		parent::Draw();
		echo "<br><div align='center'><input class='button' type=button value=\"Add New\" onclick=\"location.href = '/lib/admin/table/edit.php?ID=0{$this->URLParamsString}&Schema=AdminLog'\"></div>";
	}
}
?>
