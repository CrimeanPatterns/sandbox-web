var plugin = {

    hosts: {
        'www.kaligo.com': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://www.kaligo.com/account/profile';
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
        browserAPI.log('isLoggedIn');

        if ($('[translate = "Login / Sign-up"]:visible').length > 0) {
            browserAPI.log('not LoggedIn');
            return false;
        }

        if($('[translate = "Logout"]:visible').length > 0) {
            browserAPI.log('LoggedIn');
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log('isSameAccount');
        let email = $('div[ng-bind="userDetails.user.email"]').text();
        browserAPI.log('email: ' + email);
        return ((typeof (account.properties) != 'undefined')
                && email
                && (email.toLowerCase() === account.login.toLowerCase())
        );
    },

    logout: function (params) {
        browserAPI.log('logout');
        provider.setNextStep('start', function () {
            $('[translate = "Logout"]:visible').get(0).click();
        });
    },

    login: function (params) {
        browserAPI.log('login');
        $('[translate = "Login / Sign-up"]:visible').get(0).click();

        let counter = 0;
        let login = setInterval(function () {
            let form = $('form[name = "signInForm"]');

            if (form.length > 0) {
                clearInterval(login);
                browserAPI.log("submitting saved credentials");

                function triggerInput(selector, enteredValue) {
                    let input = document.querySelector(selector);
                    input.dispatchEvent(new Event('focus'));
                    input.dispatchEvent(new KeyboardEvent('keypress',{'key':'a'}));
                    let nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                    nativeInputValueSetter.call(input, enteredValue);
                    let inputEvent = new Event("input", { bubbles: true });
                    input.dispatchEvent(inputEvent);
                }

                triggerInput('input#user_email', params.account.login);
                triggerInput('input#user_password', params.account.password);

                provider.setNextStep('checkLoginErrors', function () {
                    form.find('button[type="submit"]').get(0).click();
                    provider.reCaptchaMessage();

                    setTimeout(function () {
                        browserAPI.log('force call');
                        plugin.checkLoginErrors(params);
                    }, 15000)
                });
            } else if (counter > 6) {
                clearInterval(login);
                provider.setError(util.errorMessages.loginFormNotFound);
                return;
            }
        }, 2000);
    },

    checkLoginErrors: function (params) {
        browserAPI.log('checkLoginErrors');
        let counter = 0;
        let checkLoginErrors = setInterval(function() {
            let error = $('div[class="error-explanation"]:visible');
            if (error.length > 0 && util.filter(error.text()) !== '') {
                clearInterval(checkLoginErrors);
                provider.setError(util.filter(error.text()));
            }

            if (counter > 10) {
                clearInterval(checkLoginErrors);

                if (typeof(params.account.itineraryAutologin) == "boolean" && params.account.itineraryAutologin && params.account.accountId > 0) {
                    provider.setNextStep('toItineraries', function () {
                        document.location.href = 'https://world.hyatt.com/content/gp/en/my-account.html?#/my-reservations';
                    });
                    return;
                }

                plugin.loginComplete(params);
            }

            counter++;
        }, 500);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    }

};
