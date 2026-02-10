var plugin = {

    hosts: {
        'www.rebatesme.com': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    loadLoginForm: function (params) {
        browserAPI.log("loadLoginForm");
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://www.rebatesme.com/login?redirect_to=%2Fmyaccount%2Fcashback';
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
                    else {
                        plugin.logout(params);
                    }
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

        if (
            $('form[name = "frm-login"]:visible').length
            || (provider.isMobile && $('div#login:visible').length > 0)
        ) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if (
            $('div[class="user-nav"]:visible>div[class="nav-box"]>a[href *= "/myaccount"]').length > 0
            || (provider.isMobile && $('div[id="m-header-logo"]:visible').length > 0)
        ) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        return null;
    },

    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('loadLoginForm');
        if (provider.isMobile) {
            window.document.location = 'https://www.rebatesme.com/logout'
        } else {
            $('a[href *= "logout"]').get(0).click();
        }
    },

    login: function (params) {
        browserAPI.log("login");
        let form = $('form[name = "frm-login"]');

        if (provider.isMobile) {
            form = $('div#login');
        }

        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }

        browserAPI.log("submitting saved credentials");

        if (provider.isMobile) {
            form.find('#inputEmail').val(params.account.login);
            form.find('#inputPassword').val(params.account.password);

            provider.setNextStep('checkLoginErrors', function () {
                form.find('a[id="mobile-login"]').get(0).click();

                setTimeout(function () {
                    if ($('div#login-page-captcha:visible').length) {
                        provider.reCaptchaMessage();
                    }

                    let counter = 0;
                    let wait = setInterval(function() {
                        browserAPI.log('waiting ... ' + counter);
                        let error = $('div.alert_box:visible');
                        if (
                            error.length > 0 && util.filter(error.text()) !== ''
                        ) {
                            clearInterval(wait);
                            provider.setError(error.text());
                            return;
                        }
                        if (counter > 180) {
                            clearInterval(wait);
                            provider.setError(util.errorMessages.captchaErrorMessage);
                            return;
                        }
                        counter++;
                    }, 500);
                }, 2000)
            });

            return;
        }

        form.find('input[name = "email"]').val(params.account.login);
        form.find('input[name = "password"]').val(params.account.password);
        provider.setNextStep('checkLoginErrors', function () {
            provider.reCaptchaMessage();

            let counter = 0;
            let wait = setInterval(function() {
                let msgs = $('p[class="m-popup-error-tip"]:visible');

                if (
                    msgs.length !== null
                    && util.filter(msgs.text()) !== ''
                ) {
                    clearInterval(wait);
                    provider.setError(msgs.text());
                    return;
                }
                if (counter > 180) {
                    clearInterval(wait);
                    provider.setError(util.errorMessages.captchaErrorMessage);
                    return;
                }
                counter++;
                browserAPI.log('wait msgs ... ' + counter + ' from');
            }, 500);
        });
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        plugin.loginComplete(params);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    },

};
