var plugin = {

    hosts: {
        'www.checkmytrip.com': true
    },

    loadLoginForm: function () {
        browserAPI.log("loadLoginForm");
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl();
        });
    },

    getStartingUrl: function () {
        return 'https://www.checkmytrip.com/cmtweb/web-landing.html#/login';
    },

    start: function (params) {
        browserAPI.log("start");
        // Wait while captcha resolved by user
        if ($('form[id = "distilCaptchaForm"]').length > 0) {
            browserAPI.log("waiting while captcha resolved...");
            provider.setNextStep('start', function () {
                provider.reCaptchaMessage();
                var counter = 0;
                var captcha = setInterval(function () {
                    browserAPI.log("waiting while captcha resolved... " + counter);
                    if (counter > 120) {
                        clearInterval(captcha);
                        provider.setError(util.errorMessages.captchaErrorMessage, true);
                    }
                    if ($('form[id = "distilCaptchaForm"]').length < 1) {
                        clearInterval(captcha);
                    }
                    counter++;
                }, 1000);
            });
        } else {
            var counter = 0;
            var start = setInterval(function () {
                browserAPI.log("start waiting... " + counter);
                var isLoggedIn = plugin.isLoggedIn();
                if (isLoggedIn !== null) {
                    clearInterval(start);
                    if (isLoggedIn) {
                        clearInterval(start);
                        plugin.logout(params);
                        return;
                    }
                    else
                        plugin.login(params);
                }// if (isLoggedIn !== null)
                if (isLoggedIn === null && counter > 20) {
                    clearInterval(start);
                    provider.setError(util.errorMessages.unknownLoginState);
                    return;
                }// if (isLoggedIn === null && counter > 10)
                counter++;
            }, 1000);
        }
    },

    isLoggedIn: function () {
        browserAPI.log("isLoggedIn");
        if ($('form[id = "login-form"]').length > 0) {
            browserAPI.log("not LoggedIn");
            return false;
        }
        if ($('button[id = "loadPastTripsButton"]').length > 0) {
            browserAPI.log("LoggedIn");
            return true;
        }
        return null;
    },

    logout: function (params) {
        browserAPI.log("logout");
        if (provider.isMobile) {
            browserAPI.log("open menu");
            provider.eval(
                "var e = document.getElementsByClassName('icon-more-dots')[0].parentNode;"
                + "var scope = angular.element(e).scope();"
                + "scope.showMore();"
            );
            setTimeout(function () {
                browserAPI.log("open settings");
                provider.eval(
                    "var e = document.getElementsByClassName('icon-settings')[0].parentNode;"
                    + "var scope = angular.element(e).scope();"
                    + "scope.itemClicked('', scope.menuItems[2]);"
                );

                setTimeout(function () {
                    plugin.logoutGeneral(params);
                }, 1000);

            }, 3000);
        }
        else {
            browserAPI.log("open settings");
            provider.setNextStep('logoutGeneral', function () {
                provider.eval(
                    "var e = document.getElementsByClassName('icon-settings')[0].parentNode;"
                    + "var scope = angular.element(e).scope();"
                    + "scope.item.action();"
                );
            });
        }
    },

    logoutGeneral: function(params) {
        browserAPI.log("logoutGeneral");
        browserAPI.log("click logout");
        provider.eval(
            "var e = document.getElementById('e2e-logout');"
            + "var scope = angular.element(e).scope();"
            + "scope.navigateTo('logout');"
        );
        provider.setNextStep('start', function () {
            setTimeout(function () {
                browserAPI.log("confirm logout");
                $('button.button-positive').click();
            }, 1000);
        });
    },

    login: function (params) {
        browserAPI.log("login");
        var form = $('form[id = "login-form"]');
        if (form.length > 0) {
            browserAPI.log("submitting saved credentials");
            provider.eval(
                "var scope = angular.element(document.querySelector('form[id = login-form]')).scope();"
                + "scope.$apply(function(){"
                + "scope.$parent.loginInputs.email = '" + params.account.login + "'; "
                + "scope.$parent.loginInputs.password = '" + params.account.password + "';"
                + "});"
            );
            provider.setNextStep("checkLoginErrors", function () {
                form.find('button#login-submit').get(0).click();
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
        var errors = $('div[class = "popup-body"]');
        console.log(errors);
        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }
        errors = $('span[class = "validation"]');
        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }
        provider.complete();
    }

};