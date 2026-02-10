let credentials = {
    username: "%username%",
    password: "%password%"
};

let base64proxyAuth = "%proxy-auth-base64%";

function onProxyAuth(details) {
    if (!details.isProxy) {
        return;
    }

    return {
        authCredentials: credentials
    };
}

chrome.webRequest.onAuthRequired.addListener(
        onProxyAuth,
        {urls: ["<all_urls>"]},
        ['blocking']
);

chrome.runtime.onMessageExternal.addListener(
    function (request, sender, sendResponse) {
        console.log(request);
        credentials = request.credentials;
        base64proxyAuth = btoa(credentials.username + ':' + credentials.password) 
        console.log('base64: ' + base64proxyAuth);
        sendResponse('ok');
        // return true from the event listener. This keeps the sendResponse function valid after the listener returns, so you can call it later.
        return true;
    }
);

let extraInfoSpec = ["blocking", "requestHeaders"];
if (chrome.webRequest.OnBeforeSendHeadersOptions.hasOwnProperty('EXTRA_HEADERS')) {
    extraInfoSpec.push('extraHeaders');
}

// we always add auth headers, because lpm does not require auth (does not give 401),
// but we select country/zone with auth
chrome.webRequest.onBeforeSendHeaders.addListener(function (HEADERS_INFO) {
    HEADERS_INFO.requestHeaders.push({name: 'Proxy-Authorization', value: 'Basic ' + base64proxyAuth})

    return {requestHeaders: HEADERS_INFO.requestHeaders};
}, {urls: ["<all_urls>"]}, extraInfoSpec);

console.log('started chrome-proxy-auth');