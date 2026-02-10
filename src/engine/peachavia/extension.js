var plugin = {

    hosts: {
        'myaccount.flypeach.com': true
    },

    cashbackLink: '',

    startFromCashback: function (params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://myaccount.flypeach.com/account?lang=en';
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

            if (isLoggedIn === null && counter > 20) {
                clearInterval(start);
                provider.setError(util.errorMessages.unknownLoginState);
                return;
            }

            counter++;
        }, 500);
    },

    isLoggedIn: function (params) {
        browserAPI.log("isLoggedIn");

        if ($('div[class="login-form login"]:visible>form').length > 0) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if ($('input[autocomplete = "family-name"]:visible').length > 0) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        let email = $('input[name="email"]');
        browserAPI.log("email: " + email.val());
        return ((typeof (account.properties) != 'undefined')
                && email.length !== 0
                && (util.filter(email.val()).toLowerCase() === account.login.toLowerCase()));
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('start', function () {
            setTimeout(function () {
                $('button:contains("Menu")').click();
                setTimeout(function () {
                    $('span:contains("Logout")').click();
                    setTimeout(function () {
                        $('button:contains("Logout")').click();
                    }, 2000);
                }, 1500);
            }, 1000);
        });
    },

    login: function (params) {
        browserAPI.log("login");
        let form = $('div[class="login-form login"]:visible>form');

        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }

        browserAPI.log("submitting saved credentials");
        form.find('input[name = "email"]').val(params.account.login);
        form.find('input[name = "password"]').val(params.account.password);

        provider.eval(
            'function createNewEvent(eventName) {' +
            'var event;' +
            'if (typeof(Event) === "function") {' +
            '    event = new Event(eventName);' +
            '} else {' +
            '    event = document.createEvent("Event");' +
            '    event.initEvent(eventName, true, true);' +
            '}' +
            'return event;' +
            '}' +
            'var email = document.querySelector(\'input[name="email"]\');' +
            'email.dispatchEvent(createNewEvent(\'input\'));' +
            'email.dispatchEvent(createNewEvent(\'change\'));' +
            'email.dispatchEvent(createNewEvent(\'keyup\'));' +
            'var password = document.querySelector(\'input[name="password"]\');' +
            'password.dispatchEvent(createNewEvent(\'input\'));' +
            'password.dispatchEvent(createNewEvent(\'change\'));' +
            'password.dispatchEvent(createNewEvent(\'keyup\'));'
        );

        provider.setNextStep('checkLoginErrors', function () {
            form.find('button>div>span:contains("Login")').click();

            setTimeout(function () {
                plugin.checkLoginErrors(params);
            }, 10000);
        });
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        var counter = 0;
        let checkLoginErrors = setInterval(function (params) {
            browserAPI.log("checkLoginErrors: waiting... " + counter);
            let error = $('span[class="error-text -pre-wrap"]');
            if (
                error.length > 0
                && util.filter(error.text()) !== ''
            ) {
                browserAPI.log('error: ' + error.text());
                clearInterval(checkLoginErrors);
                provider.setError(error.text());
                return;
            }

            let family = $('input[autocomplete = "family-name"]:visible, span:contains("Welcome Mr./Ms.")');
            if (family.length > 0 || counter > 30) {
                clearInterval(checkLoginErrors);
                plugin.loginComplete(params);
                return;
            }

            counter++;
        }, 500);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    },

};
