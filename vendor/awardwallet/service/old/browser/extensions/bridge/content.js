console.log('[bc] running content.js');

function sendResponseToPage(response) {
    console.log('[bc] sendResponseToPage', response)
    responseElement.setAttribute('data-response', JSON.stringify(response));
    responseElement.click();
}

function onBackgroundResponse(response) {
    console.log('[bc] received bg response');
    console.log(response, chrome.runtime.lastError);
    if (response === null) {
        sendResponseToPage([false, chrome.runtime.lastError]);
        return;
    }
    sendResponseToPage([true, response]);
}

function onMessageFromPage() {
    console.log('[bc] click received');
    const request = JSON.parse(requestElement.getAttribute('data-request'));
    console.log('[bc] request', request);
    chrome.runtime.sendMessage(request, onBackgroundResponse);
}

const requestElement = document.createElement("%tag%");
requestElement.id = '%request-element-id%';
requestElement.onclick = onMessageFromPage;
requestElement.style.display = 'none';
document.body.appendChild(requestElement);

const responseElement = document.createElement("%tag%");
responseElement.id = '%response-element-id%';
responseElement.style.display = 'none';
document.body.appendChild(responseElement);

console.log('[bc] control elements created, %request-element-id%, %response-element-id%');