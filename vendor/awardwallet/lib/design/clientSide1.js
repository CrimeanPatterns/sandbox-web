//this resolution file sets the screen resolution into a cookie...
var expdate = new Date();
expdate.setTime(expdate.getTime()+(12*30*24*60*60*1000)); // ~1 year
if(!getCookie1("vWidth") && !getCookie1("vHeight")){
	var newWidth, newHeight;
	newWidth = screen.width;
	newHeight = screen.height;
	if(newWidth == 0 || newWidth == "")
		newWidth = 5;
	if(newHeight == 0 || newHeight == "")
		newHeight = 5;
	setCookie1("vWidth", newWidth, expdate, "", "", 0)
	setCookie1("vHeight", newHeight, expdate, "", "", 0)
//	alert("cookie set")
//	if(getCookie1("vWidth")) //check if browser supports cookies
//		window.location.reload();
//	else
//		document.location.href = '/lib/security/noCookies.php';
}

// functions below are for independency from /lib/scripts.js

function setCookie1(name, value, expires, path, domain, secure)
{
    document.cookie= name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires.toGMTString() : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
}

function getCookie1(name)
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


//alert("check");