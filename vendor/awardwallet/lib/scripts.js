// -----------------------------------------------------------------------
// java-script functions
//		contains site-specific, often used functions
//		included to every page
//		move rare-used functions to separate file
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------
var ns4, ie4, ie5, ie7, ns6, op;

function gracefullyClose(url){
	if (parent.opener){
		if (typeof(url) != "undefined")
			parent.opener.location.href = url
		else
			parent.opener.location.reload();
	}
	window.close();
}
function browser(){
	ns4 = (document.layers)? true:false
	ie4 = (document.all)? true:false
	ns6 = (!document.all && document.getElementById)? true:false
	op = (window.opera) ? true : false;
//alert(navigator.userAgent);
	if (ie4) {
		if (navigator.userAgent.indexOf('MSIE 5')>0 || navigator.userAgent.indexOf('MSIE 6')>0) {
			ie5 = true;
		} else {
			ie5 = false; }
	} else {
		ie5 = false;
	}
	ie7 = false;
	if (navigator.userAgent.indexOf('MSIE 7')>0)
		ie7 = true;
}
function getDom(currElem){
	if(document.layers){	// Netscape 4+
		dom = document.layers[currElem].style;
	}else if(document.getElementById){	// Netscape 6+, gecko, IE 5+
		dom = document.getElementById(currElem).style;
	}else if(document.all){	// IE 4+
		dom = document.all[currElem].style;
	}else{	// Browser unknown; do nothing
		dom = false;
	}
	return dom;
}

function url(src) {
	document.location.href = src;
}
function urlNew(src) {
	affiliatePage = window.open(src, "_blank")
	affiliatePage.focus()
}
function newBg(cell, newcolor){
	cell.bgColor = newcolor;
	cell.style.cursor = 'hand';
}
function IsNumeric(sText){
	var ValidChars = "0123456789.";
	var IsNumber=true;
	var Char;
	if(sText.length > 0){
		for (i = 0; i < sText.length && IsNumber == true; i++){
			Char = sText.charAt(i);
			if (ValidChars.indexOf(Char) == -1)
				IsNumber = false;
		}
	}
	else
		IsNumber = false;
	return IsNumber;
}
function IsValidText(vText){
	var InvalidChars = "\"'<>";
	var validText=true;
	var Char;
	for (i = 0; i < vText.length && validText == true; i++){
		Char = vText.charAt(i);
		if (InvalidChars.indexOf(Char) != -1)
			validText = false;
	}
	return validText;
}

/**
 * Sets a Cookie with the given name and value.
 *
 * name       Name of the cookie
 * value      Value of the cookie
 * [expires]  Expiration date of the cookie (default: end of current session)
 * [path]     Path where the cookie is valid (default: path of calling document)
 * [domain]   Domain where the cookie is valid
 *              (default: domain of calling document)
 * [secure]   Boolean value indicating if the cookie transmission requires a
 *              secure transmission
 */
function setCookie(name, value, expires, path, domain, secure)
{
	s = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires.toGMTString() : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
    document.cookie = s;
}

function getCookie(name)
{
    var dc = document.cookie;
    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);
    if (begin == -1)
    {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    }
    else
    {
        begin += 2;
    }
    var end = document.cookie.indexOf(";", begin);
    if (end == -1)
    {
        end = dc.length;
    }
    return unescape(dc.substring(begin + prefix.length, end));
}

function deleteCookie(name, path, domain)
{
    if (getCookie(name))
    {
        document.cookie = name + "=" +
            ((path) ? "; path=" + path : "") +
            ((domain) ? "; domain=" + domain : "") +
            "; expires=Thu, 01-Jan-70 00:00:01 GMT";
    }
}

function popup( name, url )
{
  popupWin = window.open(url, name, 'menubar=no,toolbar=no,location=no,directories=no,status=yes,scrollbars=yes,resizable=no,dependent,width=530,height=450,left=50,top=50');
  popupWin.focus();
}

function openAWindow( pageToLoad, winName, width, height, center, scroll) {
	xposition=0; yposition=0;
	if ((parseInt(navigator.appVersion) >= 4 ) &&(center)) {
		xposition = (screen.width - width) / 2;
		yposition = (screen.height - height) / 2;
	}
	args = "width=" + width + ","
	+ "height=" + height + ","
	+ "location=0,"
	+ "menubar=0,"
	+ "resizable=1,"
	+ "scrollbars="+scroll+","
	+ "status=0,"
	+ "titlebar=0,"
	+ "toolbar=0,"
	+ "hotkeys=0,"
	+ "screenx=" + xposition + "," //NN Only
	+ "screeny=" + yposition + "," //NN Only
	+ "left=" + xposition + "," //IE Only
	+ "top=" + yposition; //IE Only
	window.open( pageToLoad, winName, args );
}

function openPrintWindow( pageToLoad, width, height) {
  xposition=0; yposition=0;
  if ((parseInt(navigator.appVersion) >= 4 ) ) {
   xposition = (screen.width - width) / 2;
   yposition = (screen.height - height) / 2;
  }
  args = "width=" + width + ","
  + "height=" + height + ","
  + "location=0,"
  + "menubar=1,"
  + "resizable=1,"
  + "scrollbars=1,"
  + "status=0,"
  + "titlebar=0,"
  + "toolbar=1,"
  + "hotkeys=0,"
  + "screenx=" + xposition + "," //NN Only
  + "screeny=" + yposition + "," //NN Only
  + "left=" + xposition + "," //IE Only
  + "top=" + yposition; //IE Only
  window.open( pageToLoad, 'print', args );
}

// url encode string
function url_encode( s )
{
  s = s.replace( new RegExp('\\#', "ig" ), "%23" );
  s = s.replace( new RegExp('\\ ', "ig" ), "+" );
  s = s.replace( new RegExp('\\/', "ig" ), "%2F" );
  s = s.replace( new RegExp('\\?', "ig" ), "%3F" );
  s = s.replace( new RegExp('\\=', "ig" ), "%3D" );
  s = s.replace( new RegExp('\\&', "ig" ), "%26" );
  return s;
}

function query_string()
{
  s = new String( document.location );
  n = s.indexOf( "?", 0 );
  if( n >= 0 )
    s = s.substr( n + 1 );
  return s;
}

function trim(str)
{
   return str.replace(/^\s*|\s*$/g,"");
}

// check that at least one radio is checked
function radioChecked( form, radioName )
{
	result = false;
	for( i=0; i < form.length; i++ )
	{
		var element=form.elements[i];
	  	if( ( element.type.toLowerCase() == "radio" )
	  	&& ( element.name == radioName )
	  	&& element.checked )
	    	result = true;
	}
	return result;
}

// check that at least one radio is checked
function radioValue( form, radioName )
{
	result = '';
	for( i=0; i < form.length; i++ )
	{
		var element=form.elements[i];
	  	if( element.name == radioName )
	  	{
	  		if( element.type.toLowerCase() == "radio" )
	  		{
	  			if( element.checked )
	    			result = element.value;
	  		}
	  		else
    			result = element.value;
	  	}
	}
	return result;
}

// enable radio buttons
function enableRadio( form, radioName, enable )
{
	for( i=0; i < form.length; i++ )
	{
		var element=form.elements[i];
	  	if( ( element.type.toLowerCase() == "radio" )
	  	&& ( element.name == radioName ) )
	    	element.disabled = !enable;
	}
}

function submitonce(theform, enable) {
  if (document.all || document.getElementById) {
    for (i=0;i<theform.length;i++) {
      var tempobj=theform.elements[i];
      if(tempobj.type.toLowerCase()=="submit"||tempobj.type.toLowerCase()=="reset") {
        tempobj.disabled=!enable;
      }
    }
  }
}

// return count of selected options in <select>
function selectedOptionsCount( select )
{
	var result = 0;
	options = select.options;
    for (i=0; i< options.length; i++)
        if( options[i].selected )
        	result++;
    return result;
}

// clear selected
function clearSelect( select )
{
	var result = 0;
	if( select.type == "select-one" )
	    select.selectedIndex = 0;
	else
	{
		options = select.options;
	    for (i=0; i< options.length; i++)
	        if( options[i].selected )
	        	options[i].selected = false;
	}
}

// clear selected
function selectText( select )
{
	var result = '';
	for (i=0; i< select.options.length; i++){
		var option = select.options[i];
		if( option.selected )
			result = option.innerHTML;
	}
	return result;
}

// clear form
function clearForm(form) {
    for (i=0;i<form.length;i++) {
      var input=form.elements[i];
      if(input.type.toLowerCase()=="text"||input.type.toLowerCase()=="password")
	        input.value="";
      if(input.type.toLowerCase()=="select-one")
	        input.selectedIndex = 0;
    }
}

function checkedCount(form, prefix) {
	nCount = 0;
    for (i=0;i<form.length;i++) {
      var input=form.elements[i];
      if((input.type.toLowerCase()=="checkbox") && ( !prefix || ( input.name.indexOf(prefix) == 0 ) ) )
	        if(input.checked)
	        	nCount++;
    }
	return nCount;
}

function selectCheckBoxes( form, prefix, checked )
{
    for (i=0;i<form.length;i++) {
      var input=form.elements[i];
      if( (input.type.toLowerCase()=="checkbox") && ( input.name.indexOf(prefix) == 0 ) )
	        input.checked = checked;
    }
}

function selectedCheckBoxes( form, prefix )
{
	result = "";
    for (i=0;i<form.length;i++) {
      var input=form.elements[i];
      if( (input.type.toLowerCase()=="checkbox") && ( input.name.indexOf(prefix) == 0 ))
      	if( input.checked )
      	{
      		if( result != "" )
      			result = result + ",";
	        result = result + input.value;
      	}
    }
    return result;
}

var onBodyLoaded = "";

function bodyLoaded()
{
	if( onBodyLoaded != "" )
		eval( onBodyLoaded );
}

function clickCheckBoxes( form, name ) {
    for (i=0;i<form.length;i++) {
      var input=form.elements[i];
      if( (input.type.toLowerCase()=="checkbox")
      && !input.checked
      && ( input.name == name ) )
      	input.click();
    }
}

function clickCheckBoxesId( form, id ) {
	for (i=0;i<form.length;i++) {
		var input=form.elements[i];
		if( (input.type.toLowerCase()=="checkbox")
			&& !input.checked
			&& ( input.id == id ) )
			input.click();
	}
}

function markCheckBoxes( form, name, check ) {
    for (i=0;i<form.length;i++) {
      var input=form.elements[i];
      if( (input.type.toLowerCase()=="checkbox")
      && ( input.checked != check )
      && ( input.name == name ) )
      	input.checked = check;
    }
}

function authorize(){
	if( window.frameElement != null )
	  parent.location.href = "/security/unauthorized.php?" + query_string();
	else
	  location.href = "/security/login.php?" + query_string();
}

function getBodyWidth() {
	if (self.innerHeight) return self.innerHeight;
	else if (document.documentElement && document.documentElement.clientWidth) return document.documentElement.clientWidth;
	else if (document.body) return document.body.clientWidth;
}


function execOnSaveForm(action, id){
	window.opener.formSaved(window, id);
}

function getParameterByName( name ){
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if( results == null )
		return "";
	else
		return decodeURIComponent(results[1].replace(/\+/g, " "));
}

function getUrlPathAndQuery( url ){
    var parser = document.createElement('a');
    parser.href = url;
    return parser.pathname.replace("//", "/") + parser.search;
}

function ajaxError(XMLHttpRequest, textStatus, errorThrown){
    if(typeof(debugMode) == 'undefined')
        debugMode = false;
	if(XMLHttpRequest.responseText && XMLHttpRequest.responseText.toLowerCase() == 'unauthorized'){
		try{
			if(window.parent != window){
				parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
				return;
			}
		}
		catch(e){}
		location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
		return;
	}
	if(XMLHttpRequest.status != 0)
		if(debugMode){
			try {
				data = JSON.parse(XMLHttpRequest.responseText);
				if (typeof(data) == 'object' && typeof(data.error) == 'string' && data.error == 'CSRF')
					return;
			}
			catch(e){}
            alert('Error ' + XMLHttpRequest.status + ' ' + XMLHttpRequest.statusText + "\n" + XMLHttpRequest.responseText);
        }
}

var libScriptsLoaded = true;

function attachFormEvents(formName){
	$(document.forms[formName]).find('input, select, textarea').bind('focus', null, formControlFocused).bind('blur', null, formControlBlurred);
}

function formControlFocused(){
	var id = this.id.substring(3);
	var id2 = this.id;
	$(this).closest('tr#tr'+id+', tr#tr'+id2).addClass('focused');
	$(this).closest('table.inputFrame').addClass('ifFocused');
}

function formControlBlurred(){
	var id = this.id.substring(3);
	var id2 = this.id;
	$(this).closest('tr#tr'+id+', tr#tr'+id2).removeClass('focused');
	$(this).closest('table.inputFrame').removeClass('ifFocused');
}

var allDatepickers = [];
var dateOptions;
function activateDatepickers(action){	
	if(action){
		if(action == 'active')
			for(i = 0; i < allDatepickers.length; i++){				
				$( 'input[name='+allDatepickers[i].name+']' ).datepicker(allDatepickers[i].options);				
			}
		else if(action == 'destroy'){
			for(i = 0; i < allDatepickers.length; i++){
				$( 'input[name='+allDatepickers[i].name+']' ).datepicker('destroy');
			}
		}
	}
}

var escapeTag = document.createElement('textarea');
function escapeHTML(html) {
    escapeTag.innerHTML = html;
    return escapeTag.innerHTML;
}

function unescapeHTML(html) {
    escapeTag.innerHTML = html;
    return escapeTag.value;
}

function getCommonPalette() {
	return ["#6685FF", "#9466FF", "#E066FF", "#FF66D1", "#FF6685", "#FF9466", "#FFE066", "#D1FF66", "#85FF66", "#66FF94",
		"#66FFE0", "#66D1FF", "#2954FF", "#002FEB", "#FFD429", "#EBBC00", "#b2ffdc", "#e5fff3",	"#bd9aa4", "#9aa4bd", "#6f7da1",
		"#e8de54", "#eba29a", "#663399", "#7a378b", "#ffd7a7", "#976b93", "#62773a", "#ffc663", "#411d42", "#43e8d8", "#d2b48c",
		"#3c967c", "#4bbc9c", "#ff7f50", "#145a14"];
}
