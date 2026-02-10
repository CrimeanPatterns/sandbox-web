CKEDITOR.editorConfig = function( config )
{
    config.contentsCss = '/design/mainStyle.css';
	config.bodyClass = 'ckeditorBody';
	config.enterMode = CKEDITOR.ENTER_BR;
	config.coreStyles_bold = { element : 'span', attributes : {'class': 'bold'} };
    config.allowedContent = true;
    config.filebrowserBrowseUrl = '/elfinder/default';
    config.filebrowserImageBrowseUrl = '/elfinder/default';
};