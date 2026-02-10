var plugin = {

    hosts: {
        'www.travelzoo.com': true
    },

    cashbackLink: '',

    startFromCashback: function (params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://www.travelzoo.com/MyAccount/MyPurchases/?view=1';
    },

    start: function (params) {
        browserAPI.log("start");
        var counter = 0;
        var start = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            var isLoggedIn = plugin.isLoggedIn(params);
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
        browserAPI.log("isLoggedIn");
        if ($('li.signin:visible').length > 0) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if ($('a[href="/MyAccount/logout/"]:first').text()) {
            browserAPI.log("LoggedIn");
            return true;
        }
        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        var name = util.filter($("div.member-info-name").text());
        browserAPI.log("member: " + name);
        return ((typeof (account.properties) != 'undefined')
                && (typeof (account.properties.Name) != 'undefined')
                && (account.properties.Name != '')
                && (name.toLowerCase() == account.properties.Name.toLowerCase()));
    },

    logout: function (params) {
        browserAPI.log("logout");
            provider.setNextStep('start', function () {
            document.location.href = 'https://www.travelzoo.com/MyAccount/logout';
        });
    },

    login: function (params) {
        browserAPI.log("login");
        $('a:contains("Sign in")').get(0).click();
        var form = $('form#register-form');
        if (form.length > 0) {
            browserAPI.log("submitting saved credentials");
            form.find('input[name = "emailLogin"]').val(params.account.login);
            form.find('input[name = "passwordLogin"]').val(params.account.password);
            provider.setNextStep('checkLoginErrors', function () {
                //form.find('button[type = "submit"]').prop('disabled', false);
                form.find('button[id = "btnLogin"]').get(0).click();
                setTimeout(function () {
                    plugin.checkLoginErrors(params);
                }, 5000);
            });
        }// if (form.length > 0)
        else
            provider.setError(util.errorMessages.loginFormNotFound);
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        var errors = $('span.alert-bubble-error:contains("The email address or password entered is incorrect. Please try again.")');
        var errorsTwo = $('span.alert-bubble-error:contains("Please enter a valid email address")');
        var errorsOther = $('span.alert-bubble-error').text();
        if (errors.length > 0 && util.filter(errors.text()) !== '' || errorsTwo.length > 0 && util.filter(errorsTwo.text()) !== '' || errorsOther.length > 0 && util.filter(errorsOther.text()) !== '')
            provider.setError(errors.text() || errorsTwo.text() || errorsOther.text());
        else
            plugin.loginComplete(params);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        if (typeof (params.account.itineraryAutologin) === "boolean" && params.account.itineraryAutologin && params.account.accountId > 0) {
            return;
        }
        provider.complete();
    },

    itLoginComplete: function (params) {
        browserAPI.log("itLoginComplete");
        provider.complete();
    }

};