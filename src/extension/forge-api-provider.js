var forge = {

    onMessage: null,
    callbackManager: new CallbackManager(),

    message: {

        broadcastBackground: function (to, message, callback) {
            message.senderTabId = myTabId;
            if (typeof(callback) === 'function') {
                message.callbackId = forge.callbackManager.add(callback);
                console.log('[FP] registered callback for ' + message.command + ': ' + message.callbackId);
            }
            var msg = {command: "sendToTab", params: [ownerTabId, message]};
//            console.log('[FP] sendMessage', msg);
            chrome.runtime.sendMessage(msg);
        },

        listen: function (type, onMessage, onError) {
            console.log('[FP] listening', onMessage);
            forge.onMessage = onMessage;
        }

    },


    logging: {

        log: function (message) {
            console.log(message);
        }

    },


    tabs: {

        closeCurrent: function () {
            chrome.runtime.sendMessage({command: "closeCurrentTab", params: []});
        }

    },

    document: {

        location: function(callback){
            $(document).ready(function(){
                callback(document.location);
            });
        }

    }

};

chrome.runtime.onMessage.addListener(function (message, sender, sendResponse) {
    console.log('[FP] received message', message);
    if (message.params[0]['message'] === 'callback') {
        forge.callbackManager.fire(message.params[0]['callbackId'], [message.params[0]['response']]);
        return;
    }
    forge.onMessage(message.params[0], function(){});
});

console.log('[FP] forge api loaded');
