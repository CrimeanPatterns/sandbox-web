var plugin = {

    hosts: {
        'www.savemart.com': true
    },

    cashbackLink : '',

    startFromCashback : function(params) {
        browserAPI.log('startFromCashback');
        provider.setNextStep('start', function () {
            document.location.href = plugin.getStartingUrl(params);
        });
    },

    getStartingUrl: function (params) {
        return 'https://savemart.com/login';
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
        if ($('p:contains("Log In / Sign Up")').length) {
            browserAPI.log("not LoggedIn");
            return false;
        }
        if ($('p.MuiTypography-root.MuiTypography-label.css-qvekke:first').text() !== "Log In / Sign Up") {
            browserAPI.log("LoggedIn");
            return true;
        }
        return null;
    },

    isSameAccount: function (account) {
        browserAPI.log("isSameAccount");
        const firstNameElem = $('p.MuiTypography-root.MuiTypography-label.css-qvekke:first');
        if (firstNameElem.length) {
            const firstName = firstNameElem.text().trim();
            browserAPI.log("First name: " + firstName);
            return ((typeof (account.properties) != 'undefined')
                && (typeof (account.properties.Name) != 'undefined')
                && (account.properties.Name !== '')
                && firstName
                && (account.properties.Name.includes(firstName)));
        }
        return false;
    },


    logout: function (params) {
        browserAPI.log("logout");
        provider.setNextStep('start', function () {
            setTimeout(function () {
                provider.eval("document.querySelector('button[aria-label = \"Open and close side navigation menu\"]').click();");
                setTimeout(function () {
                    provider.eval("document.querySelector('div.MuiDrawer-root li:nth-of-type(1) p').click();");
                    setTimeout(function () {
                        provider.eval("document.querySelector('div.MuiDrawer-root li:nth-of-type(4) p').click();");
                    }, 1000);
                }, 1500);
            }, 2000);
        });
    },

    login: function (params) {
        browserAPI.log("login");
        let form = $('form');
        if (form.length === 0) {
            provider.setError(util.errorMessages.loginFormNotFound);
            return;
        }
        browserAPI.log("submitting saved credentials");
        provider.eval(
            "function triggerInput(selector, enteredValue) {\n" +
            "      let input = document.querySelector(selector);\n" +
            "      input.dispatchEvent(new Event('focus'));\n" +
            "      input.dispatchEvent(new KeyboardEvent('keypress',{'key':'a'}));\n" +
            "      let nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;\n" +
            "      nativeInputValueSetter.call(input, enteredValue);\n" +
            "      let inputEvent = new Event(\"input\", { bubbles: true });\n" +
            "      input.dispatchEvent(inputEvent);\n" +
            "}\n" +
            "triggerInput('input[type = \"text\"]', '" + params.account.login + "');\n" +
            "triggerInput('input[type = \"password\"]', '" + params.account.password + "');"
        );
        $('button[type = submit]').trigger('click');
        plugin.checkLoginErrors(params);
    },

    checkLoginErrors: function (params) {
        browserAPI.log("checkLoginErrors");
        let counter = 0;
        let checkLoginErrorsEs = setInterval(function () {
            browserAPI.log("waiting... " + counter);
            let errors = $('div.MuiSnackbar-root.MuiSnackbar-anchorOriginTopCenter.css-186hw1j');
            if (errors.length > 0 && util.trim(errors.text()) !== '') {
                clearInterval(checkLoginErrorsEs);
                provider.setError(errors.text());
            }
            if (counter > 10) {
                clearInterval(checkLoginErrorsEs);
                plugin.loginComplete(params);
            }
            counter++;
        }, 500);
    },

    loginComplete: function (params) {
        browserAPI.log("loginComplete");
        provider.complete();
    }
};