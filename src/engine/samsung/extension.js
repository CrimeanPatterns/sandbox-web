var plugin = {

    hosts: {
        'www.samsungrewards.com': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://www.samsungrewards.com/rewards/#/main';
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
        if ($('form[name = "loginForm"]:visible').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }
        if ($('a[href *= logout]').length) {
            browserAPI.log("LoggedIn");
            return true;
        }
        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        var number = util.findRegExp($('li:contains("Account #")').text(), /Account\s*#\s*([^<]+)/i);
        browserAPI.log("number: " + number);
        return ((typeof (account.properties) != 'undefined')
            && (typeof (account.properties.Number) != 'undefined')
            && (account.properties.Number != '')
            && (number == account.properties.Number));
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('start', function () {
            document.location.href = 'https://us.account.samsung.com/logout';
        });
    },

    login: function (params) {
        browserAPI.log("login");
        if (typeof (params.account.itineraryAutologin) == "boolean" && params.account.itineraryAutologin && params.account.accountId == 0) {
            provider.setNextStep('getConfNoItinerary');
            document.location.href = 'http://www.samsung.com';
            return;
        }
        var form = $('form[name = "loginForm"]');
        if (form.length > 0) {
            browserAPI.log("submitting saved credentials");
            form.find('input[name = "username"]').val(params.account.login);
            form.find('input[name = "password"]').val(params.account.password);
            provider.setNextStep('checkLoginErrors', function () {
                form.find('a.log-in').get(0).click();
            });
        }// if (form.length > 0)
        else
            provider.setError(util.errorMessages.loginFormNotFound);
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        var errors = $('div#error:visible');
        if (errors.length > 0 && util.filter(errors.text()) !== '')
            provider.setError(errors.text());
        else
            plugin.loginComplete(params);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        if (typeof (params.account.itineraryAutologin) === "boolean" && params.account.itineraryAutologin && params.account.accountId > 0) {
            provider.setNextStep('toItineraries', function () {
                document.location.href = 'https://us.account.samsung.com/trips';
            });
            return;
        }
        provider.complete();
    },

    toItineraries: function (params) {
        browserAPI.log('toItineraries');
        var counter = 0;
        var toItineraries = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            var link = $('a[href *= "' + params.account.properties.confirmationNumber + '"]');
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

    getConfNoItinerary: function (params) {
        browserAPI.log("getConfNoItinerary");
        var properties = params.account.properties.confFields;
        util.waitFor({
            selector: 'form#findReservationForm',
            success: function () {
                var form = $('form#findReservationForm');
                form.find('input[name *= "ConfirmationNumber"]').val(properties.ConfNo);
                form.find('input[name *= "LastName"]').val(properties.LastName);
                provider.setNextStep('itLoginComplete', function () {
                    $('input[name = "btnSubmit"]').get(0).click();
                });
            },
            fail: function () {
                provider.setError(util.errorMessages.itineraryFormNotFound);
            },
            timeout: 10
        });
    },

    itLoginComplete: function (params) {
        browserAPI.log("itLoginComplete");
        provider.complete();
    }

};
