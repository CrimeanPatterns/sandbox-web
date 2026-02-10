var plugin = {

    hosts: {'giantfood.com': true},

    getStartingUrl: function (params) {
        return 'http://www.giantfood.com/my-account/#/account-profile';
    },

    loadLoginForm: function(params){
        browserAPI.log("loadLoginForm");
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    start: function (params) {
        browserAPI.log("start");
        var counter = 0;
        var start = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            var isLoggedIn = plugin.isLoggedIn();
            if (isLoggedIn !== null) {
                clearInterval(start);
                if (isLoggedIn) {
                    if (plugin.isSameAccount(params.account))
                        provider.complete();
                    else
                        plugin.logout(params);
                }
                else
                    plugin.login(params);
            }
            if (isLoggedIn === null && counter > 10) {
                clearInterval(start);
                provider.setError(util.errorMessages.unknownLoginState);
                return;
            }
            counter++;
        }, 500);
    },

    isLoggedIn: function () {
        browserAPI.log("isLoggedIn");
        if ($('form#login-form-modal:visible').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }
        if ($('#utility-nav-my-account:visible').length && $('#my-account-navbar:contains("Account Profile")').length) {
            browserAPI.log("LoggedIn");
            return true;
        }
        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        var number = util.findRegExp($('.a-big-text:contains(' + account.properties.Number + ')').text(), /Card Number:\s*(\d+)/);
        browserAPI.log("number: " + number);
        return typeof account.properties !== 'undefined'
            && typeof account.properties.Number !== 'undefined'
            && account.properties.Number !== ''
            && number === account.properties.Number;
    },

    logout: function () {
        browserAPI.log("logout");
        var logout = $('#utility-nav-my-account-pane-content');
        if (logout.length)
            provider.setNextStep('loadLoginForm', function () {
                logout.find('a.js-logout').get(0).click();
            });
    },

    login: function (params) {
        browserAPI.log("login");
        var form = $('form#login-form-modal');
        if (form.length > 0) {
            form.find('input#username-modal').val(params.account.login);
            form.find('input#password-modal').val(params.account.password);
            form.find('#login-submit-button-modal').get(0).click();
            plugin.checkLoginErrors(params);
        }
        else
            provider.setError(util.errorMessages.loginFormNotFound);
    },

    checkLoginErrors: function () {
        browserAPI.log("checkLoginErrors");
        var counter = 0;
        var checkLoginErrorsEs = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            var errors = $('.js-login-alert .is-error:visible');
            if (errors.length > 0 && util.trim(errors.text()) !== '') {
                clearInterval(checkLoginErrorsEs);
                provider.setError(errors.text());
            }
            if (counter > 10) {
                clearInterval(checkLoginErrorsEs);
                provider.complete();
            }
            counter++;
        }, 500);
    }

};
