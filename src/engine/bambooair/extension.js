var plugin = {

    hosts: {
        'bambooclub.bambooairways.com': true
    },

    cashbackLink: '',

    startFromCashback: function (params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://bambooclub.bambooairways.com/BambooAirways/login?locale=en';
    },

    start: function (params) {
        browserAPI.log('start');
        let counter = 0;
        let start = setInterval(function () {
            browserAPI.log('waiting... ' + counter);
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
        browserAPI.log('isLoggedIn');

        if ($('div.normallogin > form:visible').length) {
            browserAPI.log('not LoggedIn');
            return false;
        }

        if ($('a#logoutButton').length) {
            browserAPI.log('LoggedIn');
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log('isSameAccount');
        let number = util.findRegExp($('p[id="displayLoyaltyNumber"]').text(), /([\d*]+)/i);
        browserAPI.log('number: ' + number);

        return ((typeof (account.properties) != 'undefined')
                && (typeof (account.properties.LoyaltyNumber) != 'undefined')
                && (account.properties.LoyaltyNumber != '')
                && number
                && (number == account.properties.LoyaltyNumber));
    },

    logout: function (params) {
        browserAPI.log('logout');
        provider.setNextStep('start', function () {
            $('#logoutButton').get(0).click();
        });
    },

    login: function (params) {
        browserAPI.log('login');

        let form = $('div.normallogin > form:visible');

        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }

        browserAPI.log('submitting saved credentials');

        function triggerInput(selector, enteredValue) {
            let input = document.querySelector(selector);
            input.dispatchEvent(new Event('focus'));
            input.dispatchEvent(new KeyboardEvent('keypress', {'key': 'a'}));
            let nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
            nativeInputValueSetter.call(input, enteredValue);
            let inputEvent = new Event("input", {bubbles: true});
            input.dispatchEvent(inputEvent);
        }
        triggerInput('#username', params.account.login);
        triggerInput('#password', params.account.password);
        form.find('#loginButton').get(0).click();

        provider.setNextStep('checkLoginErrors', function () {
            setTimeout(function () {
                plugin.checkLoginErrors(params);
            }, 5000);
        });
    },

    checkLoginErrors: function (params) {
        browserAPI.log('checkLoginErrors');
        let errors = $('.error_txt:visible');

        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }

        plugin.loginComplete(params);
    },

    loginComplete: function (params) {
        browserAPI.log('loginComplete');
        provider.complete();
    },

};
