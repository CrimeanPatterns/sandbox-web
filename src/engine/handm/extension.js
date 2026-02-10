var plugin = {

    hosts: {
        'www2.hm.com': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://www2.hm.com/en_us/login';
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

        if ($('form[data-testid = "loginForm"]:visible').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }

        if($('button.CTA-module--action__3pIxr.CTA-module--medium__3kZou.CTA-module--secondary__135aY.CTA-module--fullWidth__319zq.CTA-module--iconPosition-start__2WXyZ').length) {
            browserAPI.log("LoggedIn");
            return true;
        }

        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        $('button.CTA-module--action__3pIxr.CTA-module--medium__3kZou.CTA-module--secondary__135aY.CTA-module--fullWidth__319zq.CTA-module--iconPosition-start__2WXyZ').click();

        const numberElement = $('button').filter(function() { return this.className.match(/codeNumber/) });

        let number = util.findRegExp(numberElement.text(), /(\d+)/i);
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
            document.location.href = 'https://www2.hm.com/en_us/logout';
        });
    },

    login: function (params) {
        browserAPI.log("login");

        if (
            typeof (params.account.itineraryAutologin) == "boolean"
            && params.account.itineraryAutologin
            && params.account.accountId === 0
        ) {
            provider.setNextStep('getConfNoItinerary');
            document.location.href = 'https://www2.hm.com/en_us/index.html';
            return;
        }

        let form = $('form[data-testid = "loginForm"]');

        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }

        browserAPI.log("submitting saved credentials");
        
        provider.eval(
            "function triggerInput(enteredName, enteredValue) {\n" +
            "  const input = document.getElementById(enteredName);\n" +
            "  const lastValue = input.value;\n" +
            "  input.value = enteredValue;\n" +
            "  const event = new Event(\"input\", { bubbles: true });\n" +
            "  const tracker = input._valueTracker;\n" +
            "  if (tracker) {\n" +
            "    tracker.setValue(lastValue);\n" +
            "  }\n" +
            "  input.dispatchEvent(event);\n" +
            "}\n" +
            "triggerInput('email', '" + params.account.login + "');\n" +  
            "triggerInput('password', '" + params.account.password + "')" 
        );
                
        provider.setNextStep('checkLoginErrors', function () {
            provider.eval(`$('button[type=submit]').get(0).click()`);
            setTimeout(plugin.checkLoginErrors, 5000, params);
        });
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        let errors = $('span[role=log]:visible:eq(0)');

        if (errors.length > 0 && util.filter(errors.text()) !== '') {
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