const callbacks = {};

function onMessage(request, sender, sendResponse) {
    console.log('onMessage', request)

    if (request.callback) {
        callbacks[request.callback](request.response);
        delete callbacks[request.callback]
        return
    }

    console.log('eval', request.code);
    console.log('arguments: ', request.arguments);
    console.log('sendResponse: ' + typeof (sendResponse));
    var arguments = request.arguments;
    try {
        eval(request.code);
        console.log('eval fired');
    } catch (e) {
        console.log('exception: ', e);
        sendResponse({error:e.message});
    }
    console.log('eval finished');
    // return true from the event listener. This keeps the sendResponse function valid after the listener returns, so you can call it later.
    return true;
}

chrome.runtime.onMessage.addListener(onMessage);
chrome.runtime.onMessageExternal.addListener(function(request, sender, sendResponse) {



    setTimeout(function() {
            onMessage(request, sender, (response) => {
                console.log('calling sendResponse with', response)
                sendResponse(response)
            });
        },
        1
    );

    return true;
});

function blockRequest(details) {
    console.log("Blocked: ", details.url);
    return {
        cancel: true
    };
}

function isValidPattern(urlPattern) {
    var validPattern = /^(file:\/\/.+)|(https?|ftp|\*):\/\/(\*|\*\.([^\/*]+)|([^\/*]+))\//g;
    return !!urlPattern.match(validPattern);
}

function waitTab(tabId, sendResponse) {
    const waitTab = function() {
        chrome.tabs.get(tabId, function(tab) {
            if (tab.status === 'complete') {
                console.log('navigation complete')
                sendResponse("ok")
            } else {
                console.log('waiting tab navigation')
                setTimeout(waitTab, 500)
            }
        })
    }
        
    waitTab()
}

console.log('started');