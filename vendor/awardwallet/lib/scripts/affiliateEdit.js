// enable/disable location fields
function affLocationChanged( form, enabled )
{
	if( form['AffCity'].type == 'hidden' )
		return;
	form['AffCity'].disabled = !enabled;
	form['AffCountryID'].disabled = !enabled;
	form['AffStateID'].disabled = !enabled;
	form['AffZip'].disabled = !enabled;
	form['AffAddress1'].disabled = !enabled;
	form['AffAddress2'].disabled = !enabled;
	return;
}

var affForm = document.forms['editor_form'];
affLocationChanged( affForm, radioValue( affForm, 'AffAddressSameAsUser' ) == '0' );
