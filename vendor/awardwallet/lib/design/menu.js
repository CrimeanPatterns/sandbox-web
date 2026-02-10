var ns4, ie4, ie5, ns6, op;

function InitMenu(){
	ns4 = (document.layers)? true:false
	ie4 = (document.all)? true:false
	ns6 = (!document.all && document.getElementById)? true:false
	op = (window.opera) ? true : false;
	if (ie4) {
		if (navigator.userAgent.indexOf('MSIE 5')>0 || navigator.userAgent.indexOf('MSIE 6')>0) {
			ie5 = true;
		} else {
			ie5 = false; }
	} else {
		ie5 = false;
	}
//	infinitelyHideMenus();
}

function infinitelyHideMenus(){
	HideMenus();
	window.setTimeout("infinitelyHideMenus()",10000);
}

function HideMenus(){
	for (i=1; i<smIds.length; i++) {
		el = document.getElementById('sm'+smIds[i]);
		if(el){
			el.style.visibility = "hidden";
			el.style.backgroundColor='#F6A436';
		}
	}
}

function showSubMenu(obj, sm){
	var leftOffset = 48;
	var topOffset = 20;
	HideMenus();
	if(sm!="sm"){
		obj.style.backgroundColor="#DD7F02";
		if(ie4){
			eval ("document.all." + sm + ".style.top = findPosY(obj) + topOffset");
			eval ("document.all." + sm + ".style.left = findPosX(obj) + leftOffset");
			eval ("document.all." + sm + ".style.visibility = 'visible'");
		}
		if(ns6){
			document.getElementById(sm).style.top = findPosY(obj) + topOffset + "px";
			document.getElementById(sm).style.left = findPosX(obj) + leftOffset + "px";;
			document.getElementById(sm).style.visibility = "visible";
		}
	}
}

/*
Begin: Taken from http://www.quirksmode.org/js/findpos.html
*/

function findPosX(obj)
{
	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

function findPosY(obj)
{
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	return curtop;
}
/*
End: Taken from http://www.quirksmode.org/js/findpos.html
*/

function lyrWidth(obj) {
return obj.clientWidth
    if (ns6){
		return obj.document.width
//		return obj.document.height
	}
    else{
		return obj.clientWidth
//		return obj.clientHeight
	}
}
