var forge;

define(['extension-communicator'], function (ExtensionCommunicator){

    forge = {

        onMessage: null,
        currentTabId: null,

        extension: new ExtensionCommunicator(function (message) {
            forge.onMessage(message, function (response) {
                if (typeof(message.callbackId) !== 'undefined') {
                    console.log('message response: ' + JSON.stringify(response));
                    forge.message.broadcastBackground('provider', {
                        'target': 'provider',
                        'message': 'callback',
                        'callbackId': message.callbackId,
                        'response': response
                    });
                }
            });
        }),

        message: {

            broadcastBackground: function (to, message, callback) {
                if (forge.currentTabId !== null)
                    forge.extension.sendToTab(forge.currentTabId, message);
                if (typeof(callback) === "function")
                    callback();
            },

            listen: function (type, onMessage, onError) {
                forge.onMessage = onMessage;
            }

        },


        logging: {

            log: function (message) {
                console.log(message);
            }

        },


        tabs: {

            blockImages: false,

            open: function (url, background, onSuccess, onError) {
                forge.extension.openTab(url, !background, forge.tabs.blockImages).then(function (tabId) {
                    console.log('[FORGE] tab opened, id', tabId);
                    forge.currentTabId = tabId;
                    onSuccess();
                });
            },

            closeCurrent: function () {
                forge.extension.closeCurrentTab().then(function (tabId) {
                    console.log('[FORGE] tab closed, id', tabId);
                    onSuccess();
                });
            }

        },

        document: {

            location: function (callback) {
                $(document).ready(function () {
                    callback(document.location);
                });
            }

        }

    };

});
