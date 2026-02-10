define(['jquery-boot', 'extension-callback-manager'], function($, CallbackManager) {

    return function ExtensionCommunicator(onMessage) {

        var callbacks = new CallbackManager();
        var self = this;
        var loadingTabs = {};
        var completeTabTimers = {};
        var waitingTabScripts = {};
        var tabScripts;
        var events = {
            pageToBg: 'aw_page_to_bg',
            contentToPage: 'aw_content_to_page'
        };

        if(browserExt.browser[0] === 'Safari' && browserExt.v2mode()) {
            events = {
                pageToBg: 'aw_page_to_bg_v2',
                contentToPage: 'aw_content_to_page_v2'
            }
        }

        this.bgConnection = null;
        this.onMessage = onMessage;

        this.sendToBg = function (command, params) {
            var result = $.Deferred();
            var message = {command: command, params: params, callbackId: callbacks.add(result.resolve)};
            console.log("[EC] message to bg", message);

            var allowedExtensionIds = [
                /* chrome prod */ 'lppkddfmnlpjbojooindbmcokchjgbib',
                /* chrome beta */ 'mnghodkdfjabhoeemdnappaaoablbnhj'
            ];

            if (browserExt.portCommSupported && allowedExtensionIds.includes(browserExt.extensionId)) {
                if (self.bgConnection === null) {
                    console.log("creating bg connection");
                    self.bgConnection = chrome.runtime.connect(browserExt.extensionId);
                    self.bgConnection.onMessage.addListener(self.onMessageFromBg);
                }
                self.bgConnection.postMessage(message);
            } else {
                // TODO: compatibility, should be removed after extension upgrade
                var event = new CustomEvent(events.pageToBg, {
                    detail: {
                        command: command,
                        params: params,
                        callbackId: callbacks.add(result.resolve)
                    }
                });
                document.dispatchEvent(event);
            }

            return result;
        };

        this.onMessageFromBg = function(message) {
            console.log("[EC] message from bg", message);
            if (typeof(self[message.type]) === 'undefined')
                throw "unknown bg message type: " + message.type;
            self[message.type].apply(self, message.params);
        };

        this.openTab = function (url, active, blockImages) {
            return new Promise(function (resolve, reject) {
                self.sendToBg('openTab', [url, active, blockImages]).then(function (tabId) {
                    console.log('added loading tab', tabId);
                    loadingTabs[tabId] = resolve;
                });
            });
        };

        this.closeCurrentTab = function (url, active) {
            return new Promise(function (resolve, reject) {
                self.sendToBg('closeCurrentTab', []).then(function (tabId) {
                    console.log('closed current tab', tabId);
                });
            });
        };

        this.sendToTab = function (tabId, message) {
            self.sendToBg('sendToTab', [tabId, message]);
        };

        this.loadTabScripts = function () {
            return new Promise(function (resolve, reject) {
                if (tabScripts)
                    resolve(tabScripts);
                else {
                    var d = new Date();
                    var code = {};
                    $.when(
                            $.get('/assets/common/vendors/jquery/dist/jquery.min.js?t=' + d.getTime(), null, function (s) {
                                code.jquery = s;
                            }, "text"),
                            $.get('/extension/forge-api-provider.js?t=' + d.getTime(), null, function (s) {
                                code.forgeApiEmulator = s;
                            }, "text"),
                            $.get('/extension/CallbackManager.js?t=' + d.getTime(), null, function (s) {
                                var start = s.indexOf('/** start **/');
                                var end = s.indexOf('/** end **/');
                                s = s.substr(start, end - start);
                                code.callbackManager = s;
                            }, "text"),
                            $.get('/extension/main.js?t=' + d.getTime(), null, function (s) {
                                code.main = s;
                            }, "text"),
                            self.sendToBg('getMyTabId', [])
                    ).then(function (q1, q2, q3, q4, tabId) {
                        code.tabId = tabId;
                        console.log('tab scripts loaded, my tab id: ' + code.tabId);
                        tabScripts = "setTimeout(function(){ console.log('aw scripts start, owner tab id: " + code.tabId + ", my tab id: %target_tab_id%'); var myTabId = %target_tab_id%; var ownerTabId = " + code.tabId + ";\n\n" + code.jquery + "\n\n" + code.callbackManager + "\n\n" + code.forgeApiEmulator + "\n\n" + code.main + "\n\nconsole.log('aw scripts end'); }, 10);";
                        resolve(tabScripts);
                    });
                }
            });
        };

        this.tabUpdated = function (tabId, changeInfo, tab) {
            if (typeof(changeInfo.status) === "string" && typeof(loadingTabs[tabId]) === "function") {
                console.log('tabUpdated', changeInfo, tab);
                if (changeInfo.status !== "complete" && typeof(completeTabTimers[tabId]) !== "undefined") {
                    console.log('cancelling complete timer for tab ' + tabId);
                    clearTimeout(completeTabTimers[tabId]);
                    delete completeTabTimers[tabId];
                }
                if (changeInfo.status === "complete") {
                    console.log('setting complete timer for tab ' + tabId);
                    completeTabTimers[tabId] = setTimeout(function(){
                        delete completeTabTimers[tabId];
                        console.log('loaded tab ' + tabId);
                        var callback = loadingTabs[tabId];
                        self.loadTabScripts().then(function () {
                            var s = tabScripts.replace(new RegExp('%target_tab_id%', 'g'), tabId);

                            var injectScripts = function() {
                                forge.extension.sendToBg('executeScript', [tabId, s]).then(function (result) {
                                    console.log('script executed on tab', tabId, result);
                                    callback(tabId);
                                });

                                // if (typeof(waitingTabScripts[tabId]) !== 'undefined') {
                                //     clearTimeout(waitingTabScripts[tabId]);
                                //     delete waitingTabScripts[tabId];
                                // }
                                // waitingTabScripts[tabId] = setTimeout(function(){
                                //     if (waitingTabScripts[tabId]) {
                                //         console.log('no response from loading tab ' + tabId + ', reinject scripts?');
                                //         //injectScripts();
                                //     }
                                // }, 3000);
                            };

                            injectScripts();
                        });
                    }, 200);
                }
            }
        };

        this.response = function (request, response) {
            callbacks.fire(request.callbackId, response);
        };

        this.message = function (message) {
            if (typeof(waitingTabScripts[message.senderTabId]) !== 'undefined') {
                console.log('got message, clearing previous timer for tab ' + message.senderTabId);
                clearTimeout(waitingTabScripts[message.senderTabId]);
                delete waitingTabScripts[message.senderTabId];
            }
            self.onMessage(message);
        };

        this.installed = function (reason) {
            if (reason === "install" || reason === "update") {
                document.location.reload();
            }
        };

        // TODO: remove, compatibility, should be removed after extension upgrade
        document.addEventListener(events.contentToPage, function (event) {
            var detail = JSON.parse(event.detail);
            if (typeof(self[detail.type]) === 'undefined')
                throw "unknown event type: " + detail.type;
            self[detail.type].apply(self, detail.params);
        });

    }
});
