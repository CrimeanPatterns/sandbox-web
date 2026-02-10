var plugin = {

    hosts: {
        'www.rex.com.au': true,
        'rexflyer.com.au': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://rexflyer.com.au/#/';
    },

    start: function (params) {
        browserAPI.log("start");
        let counter = 0;
        let start = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            let isLoggedIn = plugin.isLoggedIn(params);

            if (isLoggedIn !== null) {
                clearInterval(start);
                if (isLoggedIn) {
                    if (plugin.isSameAccount(params.account))
                        plugin.loginComplete(params);
                    else
                        plugin.logout(params);
                } else
                    plugin.login(params);
            }// if (isLoggedIn !== null)

            if (isLoggedIn === null && counter > 10) {
                clearInterval(start);
                provider.setError(util.errorMessages.unknownLoginState);
                return;
            }// if (isLoggedIn === null && counter > 10)

            counter++;
        }, 500);
    },

    isLoggedIn: function (params) {
        if ($('input[name=username]').length && $('input[name=password]').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if ($('span:contains("Logout")').length) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        let number = util.findRegExp($('h5:contains("Member No") > span').text(), /(.*)/i);
        browserAPI.log("number: " + number);
        return ((typeof (account.properties) != 'undefined')
                && (typeof (account.properties.Number) != 'undefined')
                && (account.properties.Number !== '')
                && number
                && (number === account.properties.Number));
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('start', function () {
            provider.eval(`$("span:contains('Logout')").click()`)
        });
    },

    login: function (params) {
        browserAPI.log("login");

        if ($('form').length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }
        browserAPI.log("submitting saved credentials");

        provider.eval(`$('input[name=username]').val('${params.account.login}')`);
        provider.eval(`$('input[name=password]').val('${params.account.password}')`);

        provider.eval(
            `function createNewEvent(eventName) {
            var event;
            if (typeof(Event) === "function") {
                event = new Event(eventName);
            } else {
                event = document.createEvent("Event");
                event.initEvent(eventName, true, true);
            }
            return event;
            }
            var email = document.querySelector('input[name=username]');
            email.dispatchEvent(createNewEvent('input')); email.dispatchEvent(createNewEvent('change'));
            var pass = document.querySelector('input[name=password]');
            pass.dispatchEvent(createNewEvent('input')); pass.dispatchEvent(createNewEvent('change'));`
            );

        provider.setNextStep('checkLoginErrors', function () {
            provider.eval("$('button[type=submit]').click()");
            plugin.checkLoginErrors(params);
        });
    },

    checkLoginErrors: function (params) {
        const checkError = (err) => {
            if (err.length > 0 && util.filter(err.text()) !== '') {
                return { error: true, text: err.text() };
            };
            return { error: false };
        };

        browserAPI.log("checkLoginErrors");
        let inputError = checkError($('div.error-container > small:eq(0):visible'));
        let authError = { error: false };
        const authErrorInterval = setInterval(() => {
            if (authError.error) {
                return;
            }
            const authErrorElement = $('div.toast-error > div');
            authError = checkError(authErrorElement);
        }, 1000);

        setTimeout(() => {
            clearInterval(authErrorInterval);
            for(const err of [inputError, authError]) {
                if (err.error) {
                    provider.setError(err.text);
                    return;
                }
            };
            plugin.loginComplete(params);
        }, 7000);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    },
};