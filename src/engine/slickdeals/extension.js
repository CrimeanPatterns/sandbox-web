var plugin = {

    hosts: {
        '/\\w+\\.slickdeals\\.net/': true,
        'slickdeals.net': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://redeem.slickdeals.net/login';
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

        if ($('div[data-testid=input_container]').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if ($('p[data-testid = "points_balance_text"]').length) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");

        let username = $('span[data-testid="user_actions_dropdown_user_container"]').text();
        browserAPI.log("username: " + username);
        return ((typeof (account.properties) != 'undefined')
            && (typeof (account.properties.UserName) != 'undefined')
            && (account.properties.UserName !== '')
            && username
            && (username === account.properties.UserName));

    },

    logout: function (params) {
        browserAPI.log("logout");

        $('button[data-testid="rp_header_hamburger_btn"]').click();
        provider.setNextStep('start', function () {
            setTimeout(function () {
               provider.eval("document.querySelector('a[data-testid=hamburger_item_logout]').click();");
            }, 500);
        });
    },

    login: function (params) {
        browserAPI.log("login");

        if ($('div[data-testid="input_container"]').length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }
        if($('input[data-testid="login-username-field"]').length) {
            provider.eval(
                "function triggerInput(selector, enteredValue) {\n" +
                "  const input = document.querySelector(selector);\n" +
                "  const lastValue = input.value;\n" +
                "  input.value = enteredValue;\n" +
                "  const event = new Event(\"input\", { bubbles: true });\n" +
                "  const tracker = input._valueTracker;\n" +
                "  if (tracker) {\n" +
                "    tracker.setValue(lastValue);\n" +
                "  }\n" +
                "  input.dispatchEvent(event);\n" +
                "}\n" +
                "triggerInput('input[name = username]', '" + params.account.login + "');"
            );
            $('button#next').click();
        }
        setTimeout(function () {
            if ($('div.captcha-0-3-23').length || $('div.captcha-0-3-79').length) {
                plugin.enterPassword(params);
                provider.reCaptchaMessage();
                setTimeout(function () {
                    browserAPI.log('force call');
                    plugin.checkLoginErrors(params);
                }, 20000)
            }
        }, 2000);


    },

    enterPassword: function (params) {
        browserAPI.log("enterPassword");

        if ($('input[name = password]').length > 0) {
            browserAPI.log("submitting saved credentials");
            provider.eval(
                "function triggerInput(selector, enteredValue) {\n" +
                "  const input = document.querySelector(selector);\n" +
                "  const lastValue = input.value;\n" +
                "  input.value = enteredValue;\n" +
                "  const event = new Event(\"input\", { bubbles: true });\n" +
                "  const tracker = input._valueTracker;\n" +
                "  if (tracker) {\n" +
                "    tracker.setValue(lastValue);\n" +
                "  }\n" +
                "  input.dispatchEvent(event);\n" +
                "}\n" +
                "triggerInput('input[name = password]', '" + params.account.password + "');"
            );
        }
        else
            provider.setError(util.errorMessages.passwordFormNotFound);
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        let errors = $('span.error-0-3-25');

        if (errors.length > 0 && util.filter(errors.text()) !== '') {
            provider.setError(errors.text());
            return;
        }

        plugin.loginComplete(params);
    },


    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    }
};