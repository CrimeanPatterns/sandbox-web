function DeleteSelectedFromList( form )
{
	nCount = checkedCount( form, 'sel' );
	if( nCount > 0 )
	{
		if( window.confirm( 'You want to delete ' + nCount + ' items?' ) )
		{
			form.action.value = 'delete';
			form.submit();
		}
	}
	else
		window.alert('No items selected');
}

function RemoveAccounts( form )
{
    nCount = checkedCount( form, 'sel' );
    if( nCount == 1 )
    {
        if( window.confirm( 'You want to remove accounts from this provider?' ) )
        {
            var providerId = selectedCheckBoxes( form, 'sel' );
            var providerCode = $('input[name = "sel' + providerId + '"]').parent().siblings()[1].innerHTML;
            url = 'https://jenkins.awardwallet.com/job/Frontend/job/remove-accounts/parambuild?remove=true&providerCode=' + providerCode;
            window.open(url, '_blank');
        }
    }
    else if (nCount === 0)
        window.alert('No items selected');
    else if (nCount > 1)
        window.alert('More than one item is selected');
}

function EditSelectedFromList( form, url )
{
	selected = selectedCheckBoxes( form, 'sel' );
	if( selected != "" )
		location.href = url + '&Selection=' + selected;
	else
		window.alert('No items selected');
}

function selectAll(box){
	if(box.checked)
		selectCheckBoxes( box.form, 'sel', true );
	else
		selectCheckBoxes( box.form, 'sel', false );
}

