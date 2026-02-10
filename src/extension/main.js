var awardwallet;

function initExtensionMain() {

    // begin update
    /*update*/awardwallet = {

        state: 'idle',
        accountId: null,
        accounts: {},
        onlyAutologin: false,
        foreground: false,
        plugin: null,
        pluginSource: null,
        temp: null,
        step: null,
        goto: null,
        offerVar: null,
        browserKey: null,
        userId: null,
        oldStateTried: false,
        version: "1.31", // this macro will be replaced by package.sh script
        lastMessage: null,
        lastMessageDate: null,
        fader: null,
        jQuery: null,
        lib: null,
        log: [],
        edge: false,
        idleTimer: null,
        idleTimeout: 90,

        init: function () {
            browserAPI.log("awardwallet.init, v" + awardwallet.version);
            var button = document.getElementById('extButton');
            if (button) {
                awardwallet.setBrowserKey();
                if (awardwallet.browserKey != '') {
                    // TODO: compatibilty, should be removed after extension upgrade
                    document.getElementById('extButton').onclick = function () {
                        awardwallet.onCommand();
                    };
                    browserAPI.listen("awardwallet", function (msg, callback) {
                        awardwallet.onMessage(msg, callback);
                    }, function () {
                        awardwallet.onError();
                    });
                    awardwallet.userId = document.getElementById('extUserId').value;
                    awardwallet.sendToPage('accountsChanged', awardwallet.accounts);
                    awardwallet.sendToPage('ready', {accounts: awardwallet.accounts, version: awardwallet.version});
                }
                else
                    browserAPI.log('no key, disabled');
            }
            else
                browserAPI.log('no extButton, disabled');
            awardwallet.lastMessageDate = new Date();
        },

        onCommand: function () {
            var command = document.getElementById('extCommand').value;
            var params = eval('(' + document.getElementById('extParams').value + ')');
            awardwallet.processCommand(command, params);
        },

        processCommand: function(command, params) {
            browserAPI.log("awardwallet command: " + command + ', params: ' + document.getElementById('extParams').value);
            var func = awardwallet[command];
            if (typeof(func) != 'undefined')
                func(params);
            else {
                browserAPI.log('unknown command');
                awardwallet.sendToPage('badCommand', command);
            }
        },

        changeAccountId: function (params) {
            awardwallet.accounts[params.toId] = awardwallet.accounts[params.fromId];
            awardwallet.accounts[params.toId].accountId = params.toId;
            delete awardwallet.accounts[params.fromId];
            browserAPI.log('accounts changed');
        },

        cancel: function (keepTabOpen) {
            browserAPI.log('cancel');
            if (awardwallet.state != 'idle' && !awardwallet.onlyAutologin && !awardwallet.keepTabOpen())
                browserAPI.send("provider", "close", keepTabOpen, null);
            awardwallet.accountId = null;
            awardwallet.plugin = null;
            awardwallet.pluginSoure = null;
            awardwallet.temp = null;
            awardwallet.state = 'idle';
            awardwallet.step = null;
            awardwallet.goto = null;
            awardwallet.fader = null;
            awardwallet.cancelIdleTimer();
        },

        clear: function () {
            awardwallet.cancel();
            forge.prefs.clearAll();
            browserAPI.log('storage cleared');
        },

        clearPassword: function (accountId) {
            browserAPI.log('clearing password for account ' + accountId);
            if (typeof(awardwallet.accounts[accountId]) != 'undefined') {
                delete awardwallet.accounts.accountId;
                browserAPI.log('cleared');
            }
            else {
                browserAPI.log('no such account, ignoring');
            }
        },

        onMessage: function (msg, callback) {
            var messageText = JSON.stringify(msg);
            if (msg.command != 'logEvent')
                browserAPI.log("message: " + msg.command + (typeof (msg.accountId) != 'undefined' ? ', accountId: ' + msg.accountId : ''));

            var date = new Date();
            if (messageText == awardwallet.lastMessage && (date.getTime() - awardwallet.lastMessageDate.getTime()) < 3) {
                browserAPI.log('repeated message, ignoring due to firefox bug');
                return;
            }
            awardwallet.lastMessage = messageText;
            awardwallet.lastMessageDate = date;

            if (typeof(msg.accountId) != 'undefined' && msg.accountId > 0 && awardwallet.accountId > 0 && awardwallet.accountId != msg.accountId) {
                browserAPI.log('incorrect accountId, expected: ' + awardwallet.accountId + ", got: " + msg.accountId + ", ignoring");
                return;
            }

            if ((msg.command == 'providerReady') && awardwallet.state == 'check') {
                if (awardwallet.providerHost(msg.params.host)) {
                    browserAPI.log("provider ready, activate it");
                    callback();
                    awardwallet.startCheck(true);
                }
                else
                    browserAPI.log("provider ready, host does not match: " + msg.params.host);
            }

            if (msg.command != 'providerReady' && !(msg.command == 'logEvent' && awardwalletHost()) && awardwallet.state != 'idle')
                awardwallet.setIdleTimer();

            if ((msg.command == 'prepareLogin') && (awardwallet.state == 'check'))
                awardwallet.prepareLogin(msg.params, callback);

            if ((msg.command == 'setFader') && (awardwallet.state == 'check'))
                awardwallet.setFader(msg.params);

            if ((msg.command == 'setNextStep') && (awardwallet.state == 'check'))
                awardwallet.setNextStep(msg.params);

            if ((msg.command == 'setError') && (awardwallet.state == 'check'))
                awardwallet.setError(msg.params);

            if ((msg.command == 'setWarning') && (awardwallet.state == 'check'))
                awardwallet.setWarning(msg.params);

            if ((msg.command == 'setNextAccount') && (awardwallet.state == 'check'))
                awardwallet.setNextAccount(msg.params);

            if ((msg.command == 'complete') && (awardwallet.state == 'check'))
                awardwallet.complete(msg.params);

            if ((msg.command == 'saveProperties') && (awardwallet.state == 'check'))
                awardwallet.saveProperties(msg.params);

            if ((msg.command == 'saveTemp') && (awardwallet.state == 'check'))
                awardwallet.saveTemp(msg.params);

            if ((msg.command == 'recognizeCaptcha') && (awardwallet.state == 'check'))
                awardwallet.recognizeCaptcha(msg.params, callback);

            if (msg.command == 'info') {
                awardwallet.log.push({type: 'message', content: msg.params, 'time': browserAPI.timeStr()});
                awardwallet.sendToPage('info', msg.params);
            }

            if (msg.command === 'keepTabOpen') {
                if (typeof(awardwallet.plugin) === 'object')
                    awardwallet.plugin.keepTabOpen = msg.params;
                browserAPI.log('will keep tab open: ' + msg.params);
            }

            if ((msg.command == 'cancel') && (awardwallet.state != 'idle'))
                awardwallet.cancel();

            if ((msg.command == 'logEvent') && (awardwallet.state != 'idle')) {
                msg.params.time = browserAPI.timeStr();
                awardwallet.log.push(msg.params);
            }

            if ((msg.command == 'setIdleTimer') && (awardwallet.state == 'check')) {
                browserAPI.log("setting idle timer to " + msg.params.seconds);
                awardwallet.idleTimeout = msg.params.seconds;
                awardwallet.setIdleTimer();
            }

            if ((msg.command == 'setTimeout') && (awardwallet.state == 'check'))
                awardwallet.setTimeout(msg.params);

        },

        setFader: function (text) {
            awardwallet.fader = text;
        },

        startCheck: function (sendPluginSource) {
            var account = util.clone(awardwallet.accounts[awardwallet.accountId]);
            var params = {
                account: account,
                data: awardwallet.temp,
                autologin: awardwallet.onlyAutologin,
                step: awardwallet.step,
                goto: awardwallet.goto,
                offerVar: awardwallet.offerVar,
                fader: awardwallet.fader
            };
            if (sendPluginSource) {
                params.plugin = awardwallet.pluginSource;
                params.jQuery = awardwallet.jQuery;
                params.lib = awardwallet.lib;
            }
            browserAPI.log('data: ' + JSON.stringify(awardwallet.temp));
            if (typeof(awardwallet.update) != 'undefined') {
                var script = 'var updater = ' + (updater + '') + '; ' + awardwallet.update + '; updater(provider, update_provider); updater(browserAPI, update_browserAPI);';
                browserAPI.send("provider", "exec", script, null, awardwallet.accountId);
            }
            browserAPI.send("provider", 'check', params, null, awardwallet.accountId);
        },

        setNextStep: function (step) {
            browserAPI.log('setNextStep: ' + step);
            awardwallet.step = step;
        },

        setNextAccount: function () {
            browserAPI.log('setNextAccount');
            if (typeof(awardwallet.accounts[awardwallet.accountId].nextAccount) == 'undefined')
                awardwallet.setError('no next account');
            awardwallet.setInfo(awardwallet.accounts[awardwallet.accountId].nextAccount);
            awardwallet.getPlugin(awardwallet.accounts[awardwallet.accountId].providerCode, function () {
                browserAPI.log('next plugin loaded');
                awardwallet.startCheck(false);
            });
        },

        setError: function (error, keepTabOpen) {
            browserAPI.log('setError: ' + error + ', keepTabOpen: ' + keepTabOpen);
            awardwallet.sendToPage('saveLog', {accountId: awardwallet.accountId, log: awardwallet.log});
            awardwallet.cancel(keepTabOpen);
            awardwallet.sendToPage('error', util.trim(error));
        },

        setWarning: function (error, keepTabOpen) {
            browserAPI.log('setWarning: ' + error + ', keepTabOpen: ' + keepTabOpen);
            awardwallet.accounts[awardwallet.accountId].errorMessage = error;
            awardwallet.accounts[awardwallet.accountId].errorCode = 9;// constant ACCOUNT_WARNING
        },

        complete: function (params) {
            if (awardwallet.goto) {
                browserAPI.send("provider", "exec", "document.location.href = '" + awardwallet.goto + "'", null, awardwallet.accountId);
            }
            browserAPI.send("provider", "disable", null, null, awardwallet.accountId);
            awardwallet.accounts[awardwallet.accountId].goto = null;
            browserAPI.log('complete');
            awardwallet.sendToPage('saveLog', {accountId: awardwallet.accountId, log: awardwallet.log});
            awardwallet.sendToPage('complete', awardwallet.accounts[awardwallet.accountId]);
            awardwallet.cancel(awardwallet.onlyAutologin);
            awardwallet.cancelIdleTimer();
        },

        providerHost: function (host) {
            return awardwallet.plugin != null
                    && typeof(awardwallet.plugin.hosts) != 'undefined'
                    && awardwallet.isHostMatched(awardwallet.plugin.hosts, host);
        },

        isHostMatched: function (hosts, host) {
            for (key in hosts) {
                if (!hosts.hasOwnProperty(key))
                    continue;
                if (key.substr(0, 1) == '/') {
                    var re = new RegExp("^" + key.replace(/\//ig, "") + "$", "i");
                    if (re.test(host)) {
                        browserAPI.log("host " + host + " matched pattern " + key);
                        return true;
                    }
                }
                else {
                    if (key == host) {
                        browserAPI.log("host " + host + " matched string " + key);
                        return true;
                    }
                }
            }

            return false;
        },

        closeProviderTab: function () {
            browserAPI.send('provider', 'close', null, null);
        },

        supplySecondaryLogins: function (fromParams, toParams) {
            if (typeof(fromParams.login2) != 'undefined')
                toParams.login2 = fromParams.login2;
            if (typeof(fromParams.login3) != 'undefined')
                toParams.login3 = fromParams.login3;
        },

        closeThisTab: function () {
            browserAPI.closeTab();
        },

        saveProperties: function (properties) {
            browserAPI.log("saving properties for account " + awardwallet.accountId + ': ' + JSON.stringify(properties));
            var filtered = util.filterProperties(properties);
            browserAPI.log("filtered: " + JSON.stringify(filtered));
            awardwallet.accounts[awardwallet.accountId].properties = filtered;
        },

        saveTemp: function (temp) {
            browserAPI.log("saving temporary: " + JSON.stringify(temp));
            awardwallet.temp = temp;
            browserAPI.log("temp: " + JSON.stringify(awardwallet.temp));
        },

        sendToPage: function (command, params) {
            awardwallet.setPageParams(params);
            document.getElementById('extCommand').value = command;
//		browserAPI.log("sending to page: " + command + ', ' + document.getElementById('extParams').value);
            document.getElementById('extListenButton').click();
        },

        setPageParams: function (params) {
            document.getElementById('extParams').value = JSON.stringify(params);
        },

        onSuccess: function () {
            browserAPI.log("success");
        },

        onError: function () {
            browserAPI.log("error");
        },

        setState: function (state) {
            browserAPI.log("entering state: " + state);
            awardwallet.state = state;
        },

        // expects: {accountId: 123, providerCode: 'aa'}, or {accountId: 123, providerCode: 'aa', login: '22233', password: '333222'}
        setInfo: function (params) {
            awardwallet.accounts[params.accountId] = {};
            delete awardwallet.accounts[params.accountId].step;
            delete awardwallet.accounts[params.accountId].goto;
            delete awardwallet.accounts[params.accountId].afterLogin;
            delete awardwallet.accounts[params.accountId].nextAccount;
            delete awardwallet.accounts[params.accountId].focusTab;
            var firstSet = (typeof(awardwallet.accounts[params.accountId].login) == 'undefined');
            if (!firstSet) {
                var oldLogin = awardwallet.accounts[params.accountId].login;
                if (typeof(params.login) != 'undefined') {
                    if (params.login != oldLogin) {
                        browserAPI.log('credentials changed, resetting data');
                        delete awardwallet.accounts[params.accountId].properties;
                    }
                }
            }

            for (key in params) {
                awardwallet.accounts[params.accountId][key] = params[key];
            }

            if (typeof(awardwallet.accounts[params.accountId].properties) == 'undefined')
                awardwallet.accounts[params.accountId].properties = {};
        },

        // expects: {accountId: 123}
        deleteAccount: function (params) {
            delete awardwallet.accounts[params.accountId];
        },

        // expects: {accountId: 123}
        revealPassword: function (params) {
            if (typeof(awardwallet.accounts[params.accountId].password) == 'undefined')
                awardwallet.setPageParams('');
            else
                awardwallet.setPageParams(awardwallet.accounts[params.accountId].password);
        },

        ajax: function (url, success) {
            browserAPI.log('loading ajax url: ' + url);
            // loading script failed in IE locally
            if (awardwallet.edge || ((util.location.hostname == 'awardwallet.local' || util.location.hostname == 'awardwallet.dev')
                            && !!navigator.userAgent.match(/Trident\/\d\./))) {
                $.ajax({
                    url: url,
                    success: function (data) {
                        browserAPI.log('loaded with ajax');
                        success(data);
                    },
                    error: function (error) {
                        browserAPI.log('error loading with ajax: ' + error);
                    },
                    dataType: "text"

                });
            }// if (util.location.hostname == 'awardwallet.local' || util.location.hostname == 'awardwallet.dev')
            else {
                forge.request.get(
                        url,
                        function (data) {
                            browserAPI.log('loaded with forge');
                            if (util.trim(data) == '') {
                                browserAPI.log('empty source, trying through ajax');
                                $.ajax({
                                    url: url,
                                    success: function (data) {
                                        browserAPI.log('loaded with ajax');
                                        success(data);
                                    },
                                    error: function (error) {
                                        browserAPI.log('error loading with ajax: ' + error);
                                    }
                                });
                            }
                            else
                                success(data);
                        },
                        function (error) {
                            browserAPI.log('error loading with forge: ' + error);
                        }
                );
            }// else (util.location.hostname != 'awardwallet.local' && util.location.hostname != 'awardwallet.dev')
        },

        loadJquery: function (success) {
            browserAPI.log('loading jquery');
            var d = new Date();
            var address = util.location.protocol + '//' + util.location.hostname;
            awardwallet.ajax(address + '/lib/3dParty/jquery/jq.js', function (content) {
                browserAPI.log('jQuery loaded');
                awardwallet.jQuery = content;
                if (typeof($) == 'undefined') {
                    browserAPI.log('setting jQuery');
                    eval(content);
                    browserAPI.log('$: ' + typeof($));
                }
                else
                    browserAPI.log('jQuery already exists');
                browserAPI.log('loading lib');
                awardwallet.ajax(
                        address + '/extension/lib.js?t=' + d.getTime(),
                        function (content) {
                            browserAPI.log('lib loaded');
                            awardwallet.lib = content;
                            success();
                        }
                );
            });
        },

        // expects: {accountId: 123, autologin: true,
        // optional:
        // 		step: startRegistration
        //		focusTab: true/false (default true)
        // }
        check: function (params) {
            awardwallet.log = [];
            if (awardwallet.jQuery == null) {
                awardwallet.loadJquery(function () {
                    awardwallet.check(params)
                });
                return false;
            }
            awardwallet.sendToPage('info', 'Starting the validation process');
            awardwallet.temp = {};
            awardwallet.step = 'start';
            awardwallet.idleTimeout = 90;
            awardwallet.goto = null;
            if (typeof(awardwallet.accounts[params.accountId].step) == 'string')
                awardwallet.step = awardwallet.accounts[params.accountId].step;
            if (typeof(awardwallet.accounts[params.accountId].goto) == 'string')
                awardwallet.goto = awardwallet.accounts[params.accountId].goto;
            if (typeof(awardwallet.accounts[params.accountId].offerVar) == 'string')
                awardwallet.offerVar = awardwallet.accounts[params.accountId].offerVar;
            if (typeof(awardwallet.accounts[params.accountId].password) == 'undefined') {
                awardwallet.accounts[params.accountId].errorMessage =
                        'Currently your password is not saved with this browser, please click here to enter your password';
                awardwallet.sendToPage('complete', awardwallet.accounts[params.accountId]);
            }
            else {
                browserAPI.send("provider", "close", null, null);
                awardwallet.accountId = params.accountId;
                browserAPI.log("checking account: " + awardwallet.accountId + ' of provider ' + awardwallet.accounts[awardwallet.accountId].providerCode);
                awardwallet.accounts[awardwallet.accountId].errorMessage = null;
                awardwallet.accounts[awardwallet.accountId].errorCode = null;
                browserAPI.send("provider", "close", null, null);
                awardwallet.setState('check');
                awardwallet.onlyAutologin = params.autologin;
                awardwallet.fader = null;
                awardwallet.getPlugin(awardwallet.accounts[awardwallet.accountId].providerCode, function () {
                    if (awardwallet.accountId === null)
                        return; // cancelled
                    var account = awardwallet.accounts[awardwallet.accountId];
                    var focusTab = params.autologin && (typeof(account.focusTab) == 'undefined' || account.focusTab);
                    if (typeof(awardwallet.plugin.getFocusTab) != 'undefined')
                        focusTab = awardwallet.plugin.getFocusTab(account, params);
                    var url = null;
                    if (
                        !awardwallet.accounts[params.accountId].skipCashbackUrl
                        && typeof(awardwallet.plugin.cashbackLink) != 'undefined'
                        && awardwallet.plugin.cashbackLink
                        && typeof(awardwallet.plugin.startFromCashback) != 'undefined'
                        && awardwallet.step == 'start'
                    ) {
                        awardwallet.step = 'startFromCashback';
                        url = awardwallet.plugin.cashbackLink;
                    } else {
                        url = awardwallet.plugin.getStartingUrl({
                            account: account,
                            step: awardwallet.step
                        });
                    }
                    if (awardwallet.step == 'startFromCashback') {
                        localStorage.setItem('autologinUrl', url);
                        url = util.location.protocol + '//' + util.location.hostname + '/account/overview#' + url;
                    }
                    awardwallet.openTab(
                            url,
                            focusTab
                    );
                    if (!params.autologin)
                        awardwallet.setIdleTimer();
                });
            }
        },

        setIdleTimer: function () {
            awardwallet.cancelIdleTimer();
            if (awardwallet.keepTabOpen())
                return;
            awardwallet.idleTimer = setTimeout(function () {
                awardwallet.setError(['Timed out', util.errorCodes.timeout], true);
            }, awardwallet.idleTimeout * 1000);
        },

        keepTabOpen: function () {
            return typeof(awardwallet.plugin) == 'object' && typeof(awardwallet.plugin.keepTabOpen) != 'undefined' && awardwallet.plugin.keepTabOpen;
        },

        cancelIdleTimer: function () {
            clearTimeout(awardwallet.idleTimer);
            awardwallet.idleTimer = null;
        },

        execAwardWallet: function (code) {
            eval('var func = ' + code);
            func();
        },

        execComplete: function () {
            awardwallet.sendToPage('execComplete');
        },

        openTab: function (url, foreground) {
            awardwallet.foreground = foreground;
            awardwallet.sendToPage('info', 'Opening a new tab');
            browserAPI.log("opening tab, foreground: " + awardwallet.foreground);
            browserAPI.openTab(url, !awardwallet.foreground, awardwallet.tabOpened, awardwallet.onError);
        },

        test: function () {
            browserAPI.log("checking listener");
            browserAPI.send("awardwallet", "checkListener", null, null);
        },

        tabOpened: function () {
            browserAPI.log("tabOpened");
        },

        getPlugin: function (providerCode, callback) {
            browserAPI.log('loading plugin for ' + providerCode);
            var d = new Date();
            var version = awardwallet.version;
            if (typeof(browserExt) === "object") {
                version = browserExt.extensionVersion;
            }
            // NOTE: /engine/xxx/extension.js URL actually handled by ExtensionController - extension.js file will be concatenated with /extension/util.js
            var url = util.location.protocol + '//' + util.location.hostname + '/engine/' + providerCode + '/extension.js?t=' + d.getTime() + '&v=' + encodeURIComponent(version);
            awardwallet.ajax(
                    url,
                    function (data) {
                        awardwallet.pluginLoaded(data, callback);
                    }
            );
        },

        pluginLoaded: function (data, callback) {
            awardwallet.pluginSource = data;
            browserAPI.log('evaling');
            eval(data);
            browserAPI.log('eval ok');
            awardwallet.plugin = plugin;
            browserAPI.log('calling back');
            callback();
        },

        setBrowserKey: function () {
            awardwallet.browserKey = document.getElementById('extBrowserKey').value;
            //awardwallet.sendToPage('log','SET secret key: '+awardwallet.browserKey);
        },

        recognizeCaptcha: function (params, callback) {
            browserAPI.log('parsing captcha: ' + JSON.stringify(params));
            $.ajax({
                type: "POST",
                url: "/captcha/recognize",
                dataType: "json",
                data: params,
                success: function (response) {
                    if (typeof(response) != 'undefined' && typeof(response.success) != 'undefined') {
                        console.log(response);
                        callback(response);
                    }// if (typeof(response) != 'undefined' && typeof(response.success) != 'undefined')
                }// function callback(response)
            });

        },

        setTimeout: function(params) {
            browserAPI.log('setTimeout: ' + JSON.stringify(params));
            setTimeout(function(){
                browserAPI.log('firing setTimeout: ' + JSON.stringify(params));
                browserAPI.send("provider", "fireTimeout", params.timerId, null);
            }, params.timeout);
        }

    };

    var /*update*/browserAPI = {

        // send message to tab
        send: function (to, command, params, callback, accountId) {
            if (typeof(accountId) == 'undefined' && provider.accountId)
                accountId = provider.accountId;

            var message = {target: to, command: command, params: params, accountId: accountId};
            forge.message.broadcastBackground(to, message, callback);
        },

        // setup listener for message of type
        listen: function (type, onMessage, onError) {
            forge.message.listen(type, onMessage, onError);
        },

        awardwalletHost: function () {
            if (typeof(browserAPI.awHostCached) == 'undefined')
                browserAPI.awHostCached = awardwalletHost();
            return browserAPI.awHostCached;
        },

        // log to console
        log: function (message) {
            forge.logging.log(browserAPI.timeStr() + ' ' + message);
            if (browserAPI.awardwalletHost()) {
                if (awardwallet.state == 'check')
                    awardwallet.log.push({type: 'message', content: message, time: browserAPI.timeStr()});
            }
            else {
                if (provider.active)
                    browserAPI.send('awardwallet', 'logEvent', {type: 'message', content: message}, null);
            }
        },

        // open new tab
        openTab: function (url, background, onSuccess, onError) {
            browserAPI.log("opening tab: " + url);
            if (typeof(awardwallet.plugin.blockImages) === 'undefined') {
                awardwallet.plugin.blockImages = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
            }
            forge.tabs.blockImages = awardwallet.plugin.blockImages && background;
            browserAPI.log("blockImages: " + forge.tabs.blockImages);
            forge.tabs.open(url, background, onSuccess, onError);
        },

        // close current tab
        closeTab: function () {
            browserAPI.log("closing current tab");
            forge.tabs.closeCurrent();
        },

        // save data
        save: function (key, data, onSuccess, onError) {
            browserAPI.log("saving " + key);
            forge.prefs.set(key, data, onSuccess, onError);
        },

        // load saved data
        load: function (key, onStateLoaded, onError) {
            forge.prefs.get(key, onStateLoaded, onError);
        },

        timeStr: function () {
            var date = new Date();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();
            minutes = minutes < 10 ? '0' + minutes : minutes;
            hours = hours < 10 ? '0' + hours : hours;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            return hours + ':' + minutes + ':' + seconds;
        }

    };

    if (typeof(util) == 'undefined')
        util = {
            location: null
        };

    var /*update*/provider = {

        active: false,
        errorMessage: null,
        errorCode: null,
        plugin: null,
        autologin: null,
        accountId: null,
        timers: {},

        init: function () {
            if (provider.active)
                return;
            browserAPI.send("awardwallet", "providerReady", {
                host: util.location.hostname.toLowerCase(),
                href: util.location.href
            }, function (params) {
                browserAPI.log("provider activated");
                provider.active = true;
                browserAPI.listen("provider", function (msg, callback) {
                    provider.onMessage(msg, callback);
                }, function () {
                    provider.onError();
                });
            });
        },

        onMessage: function (message, callback) {
            browserAPI.log("provider received message: " + message.command + (typeof (message.accountId) != 'undefined' ? ', accountId: ' + message.accountId : ''));

            if (!provider.active) {
                browserAPI.log('not active, ignoring');
                return;
            }

            if (typeof(message.accountId) != 'undefined' && message.accountId > 0 && provider.accountId > 0 && provider.accountId != message.accountId) {
                browserAPI.log('provider: incorrect accountId, expected: ' + provider.accountId + ", got: " + message.accountId + ", ignoring");
                return;
            }
            if (!provider.accountId && message.accountId) {
                browserAPI.log("set accountId: " + message.accountId);
                provider.accountId = message.accountId;
            }

            if (message.command == 'check')
                provider.check(message.params);
            if (message.command == 'fireTimeout')
                provider.fireTimeout(message.params);
            if (message.command == 'close')
                provider.close(message.params);
            if (message.command == 'exec')
                eval(message.params);
            if (message.command == 'disable')
                provider.disable();
        },

        check: function (params) {
            provider.logBody(params.step);
            browserAPI.log("starting check, step: " + params.step);
            if (params.autologin)
                browserAPI.log('only autologin');
            else
                browserAPI.log('full check');
            provider.autologin = params.autologin;
            if (typeof(params.plugin) == 'string') {
                browserAPI.log('setting plugin');
                eval(params.plugin);
                provider.plugin = plugin;
            }
            browserAPI.log('jQuery supplied: ' + typeof(params.jQuery));
            browserAPI.log('$: ' + typeof($));
            var browser = util.detectBrowser();
            var loadJquery = true;
            if (browser[0] == 'MSIE') {
                if (typeof(plugin.initIE) != 'undefined')
                    plugin.initIE();
                if (typeof(plugin.loadJqueryInIE) != 'undefined' && !plugin.loadJqueryInIE)
                    loadJquery = false;
            }
            if (typeof(params.jQuery) == 'string' && loadJquery) {
                browserAPI.log('setting jQuery');
                eval(params.jQuery);
            }
            else
                browserAPI.log('jQuery exists or not supplied');
            if (typeof(params.lib) == 'string') {
                browserAPI.log('setting lib');
                eval(params.lib);
            }
            func = provider.plugin[params.step];
            if (params.fader)
                provider.showFader(params.fader, true);
            func(params);
            provider.saveTemp(params.data);
        },

        fireTimeout: function(timerId) {
            browserAPI.log("fireTimeout: " + timerId);
            var func = provider.timers[timerId];
            func();
        },

        login: function (params) {
            browserAPI.log('saved password. logging in');
            var nextStep = provider.plugin.login(params);
        },

        setError: function (error, keepTabOpen) {
            if (!provider.active) {
                browserAPI.log('setError while not active, ignoring');
                return;
            }
            if (typeof(keepTabOpen) != 'undefined')
                browserAPI.send("awardwallet", "keepTabOpen", keepTabOpen, null);
            browserAPI.log('setError: ' + error);
            browserAPI.send("awardwallet", "setError", error, null);
            provider.hideFader();
        },

        setWarning: function (error, keepTabOpen) {
            if (typeof(keepTabOpen) != 'undefined') {
                browserAPI.send("awardwallet", "keepTabOpen", keepTabOpen, null);
                provider.hideFader();
            }
            browserAPI.send("awardwallet", "setWarning", error, null);
        },

        setNextAccount: function () {
            browserAPI.send("awardwallet", "setNextAccount", null, null);
        },

        complete: function (params) {
            if (!provider.active) {
                browserAPI.log('complete while not active, ignoring');
                return;
            }
            browserAPI.send("awardwallet", "complete", params, null);
            provider.hideFader();
        },

        disable: function () {
            if (provider.autologin) {
                browserAPI.log('autologin complete, disabling tab');
                provider.active = false;
            }
        },

        info: function (message) {
            browserAPI.send("awardwallet", "info", message, null);
        },

        setNextStep: function (step, callback) {
            if (!provider.active) {
                browserAPI.log('setNextStep while not active, ignoring');
                return;
            }
            browserAPI.log('setNextStep: ' + step);
            browserAPI.send("awardwallet", "setNextStep", step, null);
            if (callback && typeof(callback) === "function") {
                // execute the callback, passing parameters as necessary
                callback.call(this);
            }
        },

        setTimeout: function (callback, timeout) {
            if (!provider.active) {
                browserAPI.log('setTimeout while not active, ignoring');
                return;
            }
            timerId = Math.random().toString(36).substring(7);
            if (typeof(provider.timers) === 'undefined') {
                provider.timers = {};
            }
            provider.timers[timerId] = callback;
            browserAPI.log('setTimeout: ' + timerId + ', ' + timeout);
            browserAPI.send("awardwallet", "setTimeout", {timerId: timerId, timeout: timeout}, null);
        },

        close: function (keepOpen) {
            // mark tab dead, because FF not always close it
            provider.active = false;
            if (!keepOpen)
                browserAPI.closeTab();
        },

        onError: function () {
            browserAPI.log("error");
        },

        eval: function (code) {
            // not tested
            // @TODO: test with IE / non IE, refs #6299
//		if($.browser.msie){
//			var div = document.getElementsByTagName('div')[0];
//			div.innerHTML = div.innerHTML + "<SCRIPT DEFER>" + code + "</SCRIPT>";
//		}
//		else{
            var tagName = provider.ffTagName();
            var script = document.createElement(tagName);
            script.type = 'text/javascript';
            script.text = code;
            document.body.appendChild(script);
//		}
        },

        saveProperties: function (data) {
            if (!provider.active) {
                browserAPI.log('saveProperties while not active, ignoring');
                return;
            }
            browserAPI.send("awardwallet", "saveProperties", data, null);
        },

        saveTemp: function (data) {
            if (!provider.active) {
                browserAPI.log('saveTemp while not active, ignoring');
                return;
            }
            browserAPI.send("awardwallet", "saveTemp", data, null);
        },

        showFader: function (text, onlyOnce) {
            browserAPI.log('showFader: ' + text);
            if (!onlyOnce)
                browserAPI.send('awardwallet', 'setFader', text, null);
            var fader = document.getElementById('awFader');
            var message = document.getElementById('awMessage');
            if (!fader) {
                fader = document.createElement('div');
                fader.id = 'awFader';
                $(fader).attr('style', 'position: fixed; z-index: 100000000; opacity: 0.5; background-color: white; ' +
                    'top: 0; left: 0; width: 100%; height: 100% !important; display: none;');
                document.body.appendChild(fader);
            }
            if (!message) {
                message = document.createElement('div');
                message.id = 'awMessage';
                $(message).attr('style', 'position: fixed; z-index: 100000001; background-color: yellow; ' +
                    'border: 1px solid gray; color: black; font-size: 16px; padding: 20px; top: 20px; left: 20px; display: none;');
                document.body.appendChild(message);
            }
            $(fader).show();
            $(message).width($(document).width() - 80);
            message.innerHTML = "<div style='background-image: url(" + util.location.protocol + "//awardwallet.com/lib/images/progressCircle.gif); " +
                    "border: none; width: 16px; height: 16px; float: left; margin-right: 10px;'></div> <div style='float: left;'>" +
                    text + "</div>";
            $(message).show();
            var closeFader = function () {
                provider.hideFader()
            };
            fader.onclick = closeFader;
            message.onclick = closeFader;
        },

        reCaptchaMessage: function (onlyOnce) {
            provider.showFader('Message from AwardWallet: In order to log in into this account, you need to solve the CAPTCHA below and click the sign in button. Once logged in, sit back and relax, we will do the rest.', onlyOnce);
        },

        captchaMessageDesktop: function (onlyOnce) {
            provider.showFader('Message from AwardWallet: To speed things up, please solve the CAPTCHA image below (if you see one) and click the sign in button. Once logged in, sit back and relax, we will do the rest.', onlyOnce);
        },

        updateAccountMessage: function (onlyOnce) {
            provider.showFader('Message from AwardWallet: We are updating your account, please let this page load, this tab will be closed once we are done.', onlyOnce);
        },

        hideFader: function (afterReload) {
            browserAPI.log('hideFader');
            if (!afterReload) {
                $('#awFader').hide();
                $('#awMessage').hide();
            }
            browserAPI.send('awardwallet', 'setFader', null, null);
        },

        logBody: function (step) {
            browserAPI.log('logging body');
            if (typeof(document.documentElement) == 'object') {
                browserAPI.send('awardwallet', 'logEvent', {
                    type: 'file',
                    content: document.documentElement.outerHTML,
                    step: step
                }, null);
            }
        },

        ffTagName: function () {
            return 'sc' + 'ript';
        },

        setIdleTimer: function (seconds) {
            if (!provider.active) {
                browserAPI.log('setIdleTimer while not active, ignoring');
                return;
            }
            browserAPI.send("awardwallet", "setIdleTimer", {seconds: seconds}, null);
        }

    };
    // end update

// init

    function awardwalletHost() {
        var host = util.location.hostname.toLowerCase();
        var mask = /(^|\.)awardwallet\.(com|local|dev|docker)$/i;
        return host.match(mask);
    }

    var awardwalletStarted = false;

    function awardwalletStart() {
        if (awardwalletStarted)
            return;

        if (typeof(browserExt) === "object") {
            console.log("v2 mode, waiting for content scripts");
            if (document.getElementById('extCommand').value !== "content_scripts_ready")
                return;
            console.log("content scripts ready");
            awardwallet.version = document.getElementById('extParams').value;

            // patch some functions
            awardwallet.edge = true;
            awardwallet.execAwardWallet = function () {
            }; // ignore updates
        }

        forge.document.location(function (location) {
                    awardwalletStarted = true;
                    util.location = location;
                    if (window.frameElement == null) {
                        if (awardwalletHost())
                            awardwallet.init();
                        else
                            provider.init();
                    }
                },
                function (error) {
                    browserAPI.log('document.location error: ' + error);
                });
    }

// start extension after 3 seconds or document.ready, whatever occurs first
// this is to prevent slow startup on sites which uses external scripts or images (topcashback)

    setTimeout(awardwalletStart, 500);
    setTimeout(awardwalletStart, 1500);
    setTimeout(awardwalletStart, 3000);
    setTimeout(awardwalletStart, 6000);

    console.log('main js loaded');

// end init

}

if(typeof(define) === 'function'){
	define(['jquery-boot', 'forge-api-awardwallet'], function(){ initExtensionMain(); })
}
else
	initExtensionMain();
