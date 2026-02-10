let extraInfoSpec = ["blocking", "requestHeaders"];
if (chrome.webRequest.OnBeforeSendHeadersOptions.hasOwnProperty('EXTRA_HEADERS')) {
    extraInfoSpec.push('extraHeaders');
}

chrome.webRequest.onBeforeSendHeaders.addListener(function (HEADERS_INFO) {
    console.log('onBeforeSendHeaders');
    for (var header of HEADERS_INFO.requestHeaders) {
        if (header.name == "Accept-Language") {
            header.value = "en-US,en;q=0.9";
        }
    }

    return {requestHeaders: HEADERS_INFO.requestHeaders};
}, {urls: ["<all_urls>"]}, extraInfoSpec);

console.log('started');