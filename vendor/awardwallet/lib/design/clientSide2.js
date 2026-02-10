//this file always executes. It checks if screen resolution changed since last time the cookie was set and if it did resets the cookie...
var expdate = new Date();
expdate.setTime(expdate.getTime()+(12*30*24*60*60*1000)); // ~1 year
if(screen.width != getCookie2("vWidth") || screen.height != getCookie2("vHeight")){
	setCookie2("vWidth", screen.width, expdate, "", "", 0)
	setCookie2("vHeight", screen.height, expdate, "", "", 0)
//	alert("cookie corrected")
//	alert("vWidth = " + screen.width + " - " + expdate);
//	window.location.reload();
}
//if(!getCookie2("vWidth")) //check if browser supports cookies
//		document.location.href = '/lib/security/noCookies.php';
//alert( screen.width + " ? " + getCookie("vWidth"))
//alert( screen.height + " ? " + getCookie("vHeight"))

// functions below are for independency from /lib/scripts.js

function setCookie2(name, value, expires, path, domain, secure)
{
    document.cookie= name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires.toGMTString() : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
}

function getCookie2(name)
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

