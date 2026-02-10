var $ = $ || {};
var DateFormat = {};
!function (a) {
    var b = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        c = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        d = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        e = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        f = {
            Jan: '01',
            Feb: '02',
            Mar: '03',
            Apr: '04',
            May: '05',
            Jun: '06',
            Jul: '07',
            Aug: '08',
            Sep: '09',
            Oct: '10',
            Nov: '11',
            Dec: '12'
        }, g = /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.?\d{0,3}[Z\-+]?(\d{2}:?\d{2})?/;
    a.format = function () {
        function a(a) {
            return b[parseInt(a, 10)] || a;
        }

        function h(a) {
            return c[parseInt(a, 10)] || a;
        }

        function i(a) {
            var b = parseInt(a, 10) - 1;
            return d[b] || a;
        }

        function j(a) {
            var b = parseInt(a, 10) - 1;
            return e[b] || a;
        }

        function k(a) {
            return f[a] || a;
        }

        function l(a) {
            var b, c, d, e, f, g = a, h = '';
            return -1 !== g.indexOf('.') && (e = g.split('.'), g = e[0], h = e[1]), f = g.split(':'), 3 === f.length ? (b = f[0], c = f[1], d = f[2].replace(/\s.+/, '').replace(/[a-z]/gi, ''), g = g.replace(/\s.+/, '').replace(/[a-z]/gi, ''), {
                time: g,
                hour: b,
                minute: c,
                second: d,
                millis: h
            }) : {time: '', hour: '', minute: '', second: '', millis: ''};
        }

        function m(a, b) {
            for (var c = b - String(a).length, d = 0; c > d; d++) a = '0' + a;
            return a;
        }

        return {
            parseDate: function (a) {
                var b = {date: null, year: null, month: null, dayOfMonth: null, dayOfWeek: null, time: null};
                if ('number' == typeof a) return this.parseDate(new Date(a));
                if ('function' == typeof a.getFullYear) b.year = String(a.getFullYear()), b.month = String(a.getMonth() + 1), b.dayOfMonth = String(a.getDate()), b.time = l(a.toTimeString()); else if (-1 != a.search(g)) values = a.split(/[T\+-]/), b.year = values[0], b.month = values[1], b.dayOfMonth = values[2], b.time = l(values[3].split('.')[0]); else switch (values = a.split(' '), 6 === values.length && isNaN(values[5]) && (values[values.length] = '()'), values.length) {
                    case 6:
                        b.year = values[5], b.month = k(values[1]), b.dayOfMonth = values[2], b.time = l(values[3]);
                        break;
                    case 2:
                        subValues = values[0].split('-'), b.year = subValues[0], b.month = subValues[1], b.dayOfMonth = subValues[2], b.time = l(values[1]);
                        break;
                    case 7:
                    case 9:
                    case 10:
                        b.year = values[3], b.month = k(values[1]), b.dayOfMonth = values[2], b.time = l(values[4]);
                        break;
                    case 1:
                        subValues = values[0].split(''), b.year = subValues[0] + subValues[1] + subValues[2] + subValues[3], b.month = subValues[5] + subValues[6], b.dayOfMonth = subValues[8] + subValues[9], b.time = l(subValues[13] + subValues[14] + subValues[15] + subValues[16] + subValues[17] + subValues[18] + subValues[19] + subValues[20]);
                        break;
                    default:
                        return null;
                }
                return b.date = new Date(b.year, b.month - 1, b.dayOfMonth), b.dayOfWeek = String(b.date.getDay()), b;
            }, date: function (b, c) {
                try {
                    var d = this.parseDate(b);
                    if (null === d) return b;
                    for (var e = (d.date, d.year), f = d.month, g = d.dayOfMonth, k = d.dayOfWeek, l = d.time, n = '', o = '', p = '', q = !1, r = 0; r < c.length; r++) {
                        var s = c.charAt(r), t = c.charAt(r + 1);
                        if (q) '\'' == s ? (o += '' === n ? '\'' : n, n = '', q = !1) : n += s; else switch (n += s, p = '', n) {
                            case'ddd':
                                o += a(k), n = '';
                                break;
                            case'dd':
                                if ('d' === t) break;
                                o += m(g, 2), n = '';
                                break;
                            case'd':
                                if ('d' === t) break;
                                o += parseInt(g, 10), n = '';
                                break;
                            case'D':
                                g = 1 == g || 21 == g || 31 == g ? parseInt(g, 10) + 'st' : 2 == g || 22 == g ? parseInt(g, 10) + 'nd' : 3 == g || 23 == g ? parseInt(g, 10) + 'rd' : parseInt(g, 10) + 'th', o += g, n = '';
                                break;
                            case'MMMM':
                                o += j(f), n = '';
                                break;
                            case'MMM':
                                if ('M' === t) break;
                                o += i(f), n = '';
                                break;
                            case'MM':
                                if ('M' === t) break;
                                o += m(f, 2), n = '';
                                break;
                            case'M':
                                if ('M' === t) break;
                                o += parseInt(f, 10), n = '';
                                break;
                            case'y':
                            case'yyy':
                                if ('y' === t) break;
                                o += n, n = '';
                                break;
                            case'yy':
                                if ('y' === t) break;
                                o += String(e).slice(-2), n = '';
                                break;
                            case'yyyy':
                                o += e, n = '';
                                break;
                            case'HH':
                                o += m(l.hour, 2), n = '';
                                break;
                            case'H':
                                if ('H' === t) break;
                                o += parseInt(l.hour, 10), n = '';
                                break;
                            case'hh':
                                hour = 0 === parseInt(l.hour, 10) ? 12 : l.hour < 13 ? l.hour : l.hour - 12, o += m(hour, 2), n = '';
                                break;
                            case'h':
                                if ('h' === t) break;
                                hour = 0 === parseInt(l.hour, 10) ? 12 : l.hour < 13 ? l.hour : l.hour - 12, o += parseInt(hour, 10), n = '';
                                break;
                            case'mm':
                                o += m(l.minute, 2), n = '';
                                break;
                            case'm':
                                if ('m' === t) break;
                                o += l.minute, n = '';
                                break;
                            case'ss':
                                o += m(l.second.substring(0, 2), 2), n = '';
                                break;
                            case's':
                                if ('s' === t) break;
                                o += l.second, n = '';
                                break;
                            case'S':
                            case'SS':
                                if ('S' === t) break;
                                o += n, n = '';
                                break;
                            case'SSS':
                                o += l.millis.substring(0, 3), n = '';
                                break;
                            case'a':
                                o += l.hour >= 12 ? 'PM' : 'AM', n = '';
                                break;
                            case'p':
                                o += l.hour >= 12 ? 'p.m.' : 'a.m.', n = '';
                                break;
                            case'E':
                                o += h(k), n = '';
                                break;
                            case'\'':
                                n = '', q = !0;
                                break;
                            default:
                                o += s, n = '';
                        }
                    }
                    return o += p;
                } catch (u) {
                    return console && console.log && console.log(u), b;
                }
            }, prettyDate: function (a) {
                var b, c, d;
                return ('string' == typeof a || 'number' == typeof a) && (b = new Date(a)), 'object' == typeof a && (b = new Date(a.toString())), c = ((new Date).getTime() - b.getTime()) / 1e3, d = Math.floor(c / 86400), isNaN(d) || 0 > d ? void 0 : 60 > c ? 'just now' : 120 > c ? '1 minute ago' : 3600 > c ? Math.floor(c / 60) + ' minutes ago' : 7200 > c ? '1 hour ago' : 86400 > c ? Math.floor(c / 3600) + ' hours ago' : 1 === d ? 'Yesterday' : 7 > d ? d + ' days ago' : 31 > d ? Math.ceil(d / 7) + ' weeks ago' : d >= 31 ? 'more than 5 weeks ago' : void 0;
            }, toBrowserTimeZone: function (a, b) {
                return this.date(new Date(a), b || 'MM/dd/yyyy HH:mm:ss');
            }
        };
    }();
}(DateFormat), function (a) {
    a.format = DateFormat.format;
}($);
var awardwallet = {
    log: []
};
var api = {
    timeoutId: null,
    isMobile: true,

    sendNativeEvent: function (eventName, event) {
        if (typeof sendEvent === 'function') {
            sendEvent(Object.assign(
                {
                    command: eventName,
                    params: params
                },
                event
            ));
        }
    },

    setNextStep: function (step, callback, immediate) {
        browserAPI.log('currentUrl: ' + document.location.href);
        browserAPI.log('setNextStep: ' + step + ', properties: ' + JSON.stringify(params));

        api.sendNativeEvent('nextstep', {
            step: step,
            immediate: immediate
        });

        if (typeof (callback) == 'function') {
            callback.call(this);
        }
    },

    error: function (error) {
        var event = {};

        browserAPI.log('currentUrl: ' + document.location.href);
        browserAPI.log('setError: ' + error);

        if (error instanceof Array) {
            event.message = error[0];
            event.errorCode = error[1];
        } else {
            event.message = error;
        }

        api.sendNativeEvent('error', event);
    },

    log: function (text) {
        browserAPI.log('currentUrl: ' + document.location.href);
        browserAPI.log('die trace: ' + text);

        api.sendNativeEvent('log', {message: text});
    },

    errorDate: function () {
        api.error('Flight status is not yet available for this date, please try again later.');
        alert('Flight status is not yet available for this date, please try again later.');
    },

    complete: function () {
        browserAPI.log('currentUrl: ' + document.location.href);
        browserAPI.log('complete');

        api.sendNativeEvent('complete');
    },

    getDepDate: function () {
        return new Date((typeof (params.depDate) != 'undefined' && params.depDate) ? (typeof params.depDate == 'object' && params.depDate.hasOwnProperty('ts') ? params.depDate.ts * 1000 : params.depDate * 1000) : Date.now());
    },

    getArrDate: function () {
        return new Date((typeof (params.arrDate) != 'undefined' && params.arrDate) ? (typeof params.arrDate == 'object' && params.arrDate.hasOwnProperty('ts') ? params.arrDate * 1000 : params.arrDate * 1000) : Date.now());
    },

    setError: function (text) {
        api.error(text);
    },

    setWarning: function (text) {
        browserAPI.log('currentUrl: ' + document.location.href);
        browserAPI.log('setWarning: ' + text);

        api.sendNativeEvent('error', {message: text, errorCode: 9});
    },

    close: function () {
        api.setNextStep('');
    },

    eval: function (code) {
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.text = code;
        document.body.appendChild(script);
    },

    info: function (message) {
        browserAPI.log(message);
    },

    showFader: function (text) {
        browserAPI.log('showFader: ' + text);
        clearTimeout(api.timeoutId);
        api.timeoutId = setTimeout(function () {
            alert(text);
        }, 1000);
    },

    hideFader: function () {
        browserAPI.log('hideFader');
    },

    reCaptchaMessage: function () {
        api.showFader('Message from AwardWallet: In order to log in into this account, you need to solve the CAPTCHA below and click the sign in button. Once logged in, sit back and relax, we will do the rest.');
    },
    captchaMessageDesktop: function () {
        api.showFader('Message from AwardWallet: To speed things up, please solve the CAPTCHA image below (if you see one) and click the sign in button. We will do the rest.');
    },

    updateAccountMessage: function (onlyOnce) {
        //api.showFader('Message from AwardWallet: We are updating your account, please let this page load, this tab will be closed once we are done.', onlyOnce);
    },

    saveProperties: function (properties) {
        browserAPI.log('saving properties ' + JSON.stringify(properties));
        if (params && params.account) {
            params.account.properties = properties;
        }
    },

    saveTemp: function (data) {
        browserAPI.log('saving temporary: disabled in mobile, properties saving on setNextStep');
    },

    command: function (name, callback) {
        browserAPI.log('command: ' + name);

        api.sendNativeEvent(name);

        if (typeof callback === 'function') {
            callback();
        }
    },

    logBody: function (step) {
        if (
            plugin &&
            plugin.options &&
            plugin.options.logHtml === false
        ) {
            return;
        }
        browserAPI.log('logging body');
        browserAPI.log('currentUrl: ' + document.location.href);
        if (typeof (document.documentElement) == 'object') {
            api.sendNativeEvent('logger', {
                message: {
                    type: 'file',
                    content: document.documentElement.outerHTML,
                    step: step
                }
            });
        }
    },

    setIdleTimer: function (seconds) {
        //not supported
    },

    setTimeout: function (fn, time) {
        setTimeout(fn, time);
    }

};

var browserAPI = {
    log: function (message) {
        var date = new Date();
        if (typeof message !== 'string') {
            if ((message instanceof HTMLElement) === false) {
                message = message.toString();
            } else {
                message = String(message);
            }
        }
        message = '[' + [date.getHours(), date.getMinutes(), date.getSeconds()].join(':') + '] ' + message;
        api.sendNativeEvent('logger', {message: message});
    },
    send: function (dst, level, text) {
        //console.log(text);
    }
};

var provider = api;

/** VERSION: 2.1 */
