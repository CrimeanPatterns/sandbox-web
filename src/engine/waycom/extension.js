var plugin = {

    hosts: {
        'www.way.com': true
    },

    loadLoginForm: function (params) {
        browserAPI.log("loadLoginForm");
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://www.way.com/login';
    },

    start: function (params) {
        browserAPI.log("start");
        // Wait while captcha resolved by user
        if ($('div[id = "recaptcha_widget"]').length > 0) {
            browserAPI.log("waiting while captcha resolved...");
            provider.reCaptchaMessage();
            provider.setNextStep('start', function () {
                var counter = 0;
                var captcha = setInterval(function () {
                    browserAPI.log("waiting while captcha resolved... " + counter);
                    if (counter > 180) {
                        clearInterval(captcha);
                        provider.setError(util.errorMessages.captchaErrorMessage, true);
                    }
                    if ($('div[id = "recaptcha_widget"]').length < 1) {
                        clearInterval(captcha);
                        browserAPI.log("captcha resolved!");
                    }
                    counter++;
                }, 1000);
            });
        } // if ($('div[id = "recaptcha_widget"]').length > 0)
        else {
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
        }
    },

    isLoggedIn: function () {
        browserAPI.log("isLoggedIn");
        if ($('form[name = "userForm"]').length > 0) {
            browserAPI.log("not LoggedIn");
            return false;
        }
        if ($('a#homePage_lnkLogout').length > 0) {
            browserAPI.log("LoggedIn");
            return true;
        }
        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        var name = $('div.dashboard-heading span').text();
        browserAPI.log("name: " + name);
        return ((typeof(account.properties) != 'undefined')
                && (typeof(account.properties.Name) != 'undefined')
                && (account.properties.Name != '')
                && name
                && (-1 < account.properties.Name.toLowerCase().indexOf(name.toLowerCase())) );
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('loadLoginForm', function () {
            $('a#homePage_lnkLogout').get(0).click();
            if (provider.isMobile) {
                setTimeout(function () {
                    browserAPI.log('Force redirect');
                    plugin.loadLoginForm(params);
                }, 5000);
            }
        });
    },

    login: function (params) {
        browserAPI.log("login");
        var form = $('form[name = "userForm"]');
        if (form.length > 0) {
            browserAPI.log("submitting saved credentials");
            provider.eval(
                "var scope = angular.element(document.querySelector('form[name = userForm]')).scope();"
                + "scope.$apply(function(){"
                + "scope.vm.email = '" + params.account.login + "'; "
                + "scope.vm.password = '" + params.account.password + "';"
                + "});"
            );
            provider.setNextStep("checkLoginErrors", function () {
                form.find('button.newlogin_btn').get(0).click();
                setTimeout(function () {
                    plugin.checkLoginErrors(params);
                }, 7000);
            });
        }// if (form.length > 0)
        else
            provider.setError(util.errorMessages.loginFormNotFound);
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        var errors = $('p#loginEmail_userForm_pErrorLoginMessage');
        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }
        provider.complete();
    }

};