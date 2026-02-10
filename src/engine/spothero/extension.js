var plugin = {

    hosts: {
        '/\\w+\\.spothero\\.com/': true,
        'spothero.com': true
    },

    cashbackLink: '',

    startFromCashback: function (params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://spothero.com/login';
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
                    if (!document.location.href.includes('account-settings')) {
                        document.location.href = 'https://spothero.com/account-settings';
                    };
                    setTimeout(() => {
                        if (plugin.isSameAccount(params.account))
                            plugin.loginComplete(params);
                        else
                            plugin.logout(params);
                    }, 3000);
                } else {
                    if (!document.location.href.includes('accounts.spothero.com/u/login')) {
                        document.location.href = 'https://spothero.com/login';
                    };
                    setTimeout(() => {
                        plugin.login(params);
                    }, 3000);
                }
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
        browserAPI.log("isLoggedIn");

        const form = $('form').filter(function () { return this.className.match(/form-login/) });
        const link = $('a[href *= restricted-login]');

        if (form.length) {
            browserAPI.log("not LoggedIn");
            return false;
        };

        if (link.length) {
            browserAPI.log("not LoggedIn");
            return false;
        };

        if ($('a[href *= logout], a[href *= account-settings]').length) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");

        const loginContainer = $('p.account-email.lo_sensitive');
        if (loginContainer.length) {
            const login = loginContainer.text().trim();
            return ((typeof (account) != 'undefined')
                && (typeof (account.login) != 'undefined')
                && (account.properties.login !== '')
                && login
                && (login === account.login));
        };

        return false;
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('start', function () {
            document.location.href = 'https://spothero.com/logout';
        });
    },

    _login: function (params) {
        browserAPI.log("login");

        if (typeof (params.account.itineraryAutologin) == "boolean"
            && params.account.itineraryAutologin
            && params.account.accountId === 0) {
            provider.setNextStep('getConfNoItinerary');
            document.location.href = 'https://spothero.com/';
            return;
        }

        setTimeout(function () {
            const form = $('form._form-login-id');

            if (form.length === 0) {
                provider.setError(util.errorMessages.loginFormNotFound);
                return;
            }


            browserAPI.log("submitting saved credentials");

            form.find('input#username').val(params.account.login);

            provider.eval(
                "function triggerInput(enteredName, enteredValue) {\n" +
                "  const input = document.getElementById(enteredName);\n" +
                "  const lastValue = input.value;\n" +
                "  input.value = enteredValue;\n" +
                "  const event = new Event(\"input\", { bubbles: true });\n" +
                "  const tracker = input._valueTracker;\n" +
                "  if (tracker) {\n" +
                "    tracker.setValue(lastValue);\n" +
                "  }\n" +
                "  input.dispatchEvent(event);\n" +
                "}\n" +
                "triggerInput('username', '" + params.account.login + "');"
            );
            provider.setNextStep('checkLoginErrorsSubmitLogin', function () {
                if ($('div#ulp-recaptcha:visible').length) {
                    provider.reCaptchaMessage();
                    return;
                }
                form.submit();
            });

        }, 3000);
    },
    get login() {
        return this._login;
    },
    set login(value) {
        this._login = value;
    },

    enterPassword: function (params) {
        browserAPI.log("enterPassword");
        const form = $('form._form-login-password');
        if (form.length > 0) {
            browserAPI.log("submitting saved credentials");
            form.find('input#password').val(params.account.password);
            provider.eval(
                "function triggerInput(enteredName, enteredValue) {\n" +
                "  const input = document.getElementById(enteredName);\n" +
                "  const lastValue = input.value;\n" +
                "  input.value = enteredValue;\n" +
                "  const event = new Event(\"input\", { bubbles: true });\n" +
                "  const tracker = input._valueTracker;\n" +
                "  if (tracker) {\n" +
                "    tracker.setValue(lastValue);\n" +
                "  }\n" +
                "  input.dispatchEvent(event);\n" +
                "}\n" +
                "triggerInput('username', '" + params.account.login + "');"
            );
            provider.setNextStep('checkLoginErrors', function () {
                form.submit();
            });
        }
        else
            provider.setError(util.errorMessages.passwordFormNotFound);
    },

    checkLoginErrorsSubmitLogin: function () {
        browserAPI.log("checkLoginErrorsSubmitLogin");
        let errors = $('span#error-element-username');

        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        };

        plugin.enterPassword(params);
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        let errors = $('div#prompt-alert:visible');

        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }

        plugin.loginComplete(params);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        setTimeout(function () {
            if (typeof (params.account.itineraryAutologin) === "boolean" && params.account.itineraryAutologin && params.account.accountId > 0) {
                provider.setNextStep('toItineraries', function () {
                    provider.eval(`document.location.href = 'https://spothero.com/account-reservations'`);
                });
                return;
            }
            provider.complete();
        }, 7000);
    },

    toItineraries: function (params) {
        browserAPI.log('toItineraries');
        let counter = 0;
        let toItineraries = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            let link = $('a[href *= "' + params.account.properties.confirmationNumber + '"]');
            browserAPI.log('link ' + link);

            if (link.length) {
                clearInterval(toItineraries);
                provider.setNextStep('itLoginComplete', function () {
                    link.get(0).click();
                });
                return;
            }// if (link)

            if (counter > 20) {
                clearInterval(toItineraries);
                provider.setError(util.errorMessages.itineraryNotFound);
                return;
            }// if (counter > 20)

            counter++;
        }, 500);
    },

    itLoginComplete: function (params) {
        browserAPI.log("itLoginComplete");
        provider.complete();
    }

};