var plugin = {

    hosts: {
        'login.wynnresorts.com': true,
        'profile.wynnresorts.com': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://profile.wynnresorts.com/Profile';
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
        browserAPI.log("isLoggedIn");

        if ($('form[action = "/Account/Login"]').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if ($('#logoutForm').length) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        
        let number = util.findRegExp($('div.rc-user-info-wrap > p:nth-child(2)').text(), /(.*)/i);
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
            $('#logoutForm').submit();
        });
    },

    login: function (params) {
        browserAPI.log("login");

        let form = $('form[action = "/Account/Login"]');

        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }

        browserAPI.log("submitting saved credentials");

        form.find('input[name = "Username"]').val(params.account.login);
        form.find('input[name = "Password"]').val(params.account.password);
        provider.setNextStep('checkLoginErrors', function () {
            form.find('button#loginSubmit').get(0).click();
        });
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");

        setTimeout(() => {
            const error = $('span#Username-error:visible, span#Password-error:visible, div.alert.alert-danger > p:nth-child(2)');
            if (error.length > 0 && util.filter(error.text()) !== '') {
                provider.setError(error.text());
                return;
            };
            plugin.loginComplete(params);    
        }, 5000);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    },

};