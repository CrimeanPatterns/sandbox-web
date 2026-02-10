var plugin = {

    hosts: {
        'www.airpremia.com': true
    },

    cashbackLink : '',
    loginLink : 'https://www.airpremia.com/login',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    startFromLogin : function(params) {
        browserAPI.log('startFromLogin');
        provider.setNextStep('start', function () {
            document.location.href = plugin.loginLink;
        });
    },

    getStartingUrl: function (params) {
        return 'https://www.airpremia.com/login';
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

        if ($('a[onclick="fn_formSubmit(\'/login\')"]').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if ($('a[onclick="fn_logout()"]').length) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        const login = $('div.email').text();
        browserAPI.log("email: " + login);
        return ((typeof (account) != 'undefined')
            && (typeof (account.login) != 'undefined')
            && (account.properties.login !== '')
            && login
            && (login.trim().toLowerCase() === account.login.toLowerCase()));
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('startFromLogin', function () {
            document.location.href = 'https://www.airpremia.com/logout';
        });
    },

    login: function (params) {
        browserAPI.log("login");
        const form = $('div#fn_login');

        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return false;
        }

        browserAPI.log("submitting saved credentials");
        form.find('input#email').val(params.account.login);
        form.find('input#password').val(params.account.password);
        form.find('button[class = "taskButton"]').get(0).click();
        provider.setNextStep('checkLoginErrors', function () {
            $('button[data-testid = login-button]').trigger('click');

            setTimeout(function () {
                plugin.checkLoginErrors(params);
            }, 10000)
        });
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        let errors = $('#alarmText');

        if (errors.length > 0 && util.trim(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }

        plugin.loginComplete(params);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    },

};