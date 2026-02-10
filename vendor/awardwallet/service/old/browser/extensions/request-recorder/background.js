var requests = [];

chrome.runtime.onMessageExternal.addListener(
    function (request, sender, sendResponse) {
        console.log(request);
        var requestsCopy = requests;
        if (request.clear) {
            console.log('clearing request history')
            requests = [];
        }
        sendResponse(requestsCopy);
        // return true from the event listener. This keeps the sendResponse function valid after the listener returns, so you can call it later.
        return true;
    }
);

chrome.runtime.onMessage.addListener(
    function (event, sender, sendResponse) {
        //console.log(event);
        requests.push(event);
        // return true from the event listener. This keeps the sendResponse function valid after the listener returns, so you can call it later.
        return true;
    });

console.log('started request recorder');