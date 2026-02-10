if(typeof(util) == 'undefined')
    util = {};

util.errorCodes = {
    //unchecked: 0,
    //checked: 1,
    invalidPassword: 2,
    lockout: 3,
    providerError: 4,
    //providerDisabled: 5,
    engineError: 6,
    missingPassword: 7,
    //preventLockout: 8,
    warning: 9,
    question: 10,
    timeout: 11
};

util.errorMessages = {
    unknownLoginState: ["Can't determine login state", util.errorCodes.engineError],
    loginFormNotFound: ["Login form not found", util.errorCodes.engineError],
    passwordFormNotFound: ["Password form not found", util.errorCodes.engineError],
    itineraryFormNotFound: ["Itinerary form not found", util.errorCodes.engineError],
    itineraryNotFound: ["Itinerary not found", util.errorCodes.providerError],
    captchaErrorMessage: ['We could not recognize captcha. Please try again later.', util.errorCodes.providerError],
    providerErrorMessage: ['The website is experiencing technical difficulties, please try to check your balance at a later time.', util.errorCodes.providerError],
    notMemberMessage: ['You are not a member of this loyalty program.', util.errorCodes.providerError]
};

util.trim =  function(str){
    if(str == null)
        return '';
    if(typeof(str) != 'string')
        return str;
    return str.replace(/^\s*|\s*$/g, "");
};

util.filter = function(str){
    if(str == null)
        return '';
    if(typeof(str) != 'string')
        return str;
    return util.trim(str.replace(/[\n\t\r\s]+/g, " "));
};

util.clone = function(obj) {
    // Handle the 3 simple types, and null or undefined
    if (null == obj || "object" != typeof obj) return obj;

    // Handle Date
    if (obj instanceof Date) {
        var copy = new Date();
        copy.setTime(obj.getTime());
        return copy;
    }

    // Handle Array
    if (obj instanceof Array) {
        var copy = [];
        var len = obj.length;
        for (var i = 0; i < len; ++i) {
            copy[i] = util.clone(obj[i]);
        }
        return copy;
    }

    // Handle Object
    if (obj instanceof Object) {
        var copy = {};
        for (var attr in obj) {
            if (obj.hasOwnProperty(attr)) copy[attr] = util.clone(obj[attr]);
        }
        return copy;
    }

    throw new Error("Unable to copy obj! Its type isn't supported.");
};

util.filterProperties = function(properties){
    var filtered = {};
    for(var key in properties){
        if((properties.hasOwnProperty(key))){
            switch(typeof(properties[key])){
                case 'object':
                case 'array':
                    filtered[key] = util.filterProperties(properties[key]);
                    break;
                default:
                    var value = util.filter(properties[key]);
                    if(value != ''){
                        filtered[key] = value;
                    }
                    break;
            }
        }
    }
    return filtered;
};

util.detectBrowser = function () {
    var
            UA = window.navigator.userAgent,
    //--------------------------------------------------------------------------------
            OperaB = /Opera[ \/]+\w+\.\w+/i,
            OperaV = /Version[ \/]+\w+\.\w+/i,
            FirefoxB = /Firefox\/\w+\.\w+/i,
            ChromeB = /Chrome\/\w+\.\w+/i,
            SafariB = /Version\/\w+\.\w+/i,
            IEB = /MSIE *\d+\.\w+/i,
            SafariV = /Safari\/\w+\.\w+/i,
            iPad = /(iPad|iPhone|iPod).*Safari/i,
            aolDesktop = /AOL (\d)\.(\d)/i,
    //--------------------------------------------------------------------------------
            browser = new Array(),
            browserSplit = /[ \/\.]/i,
            OperaV = UA.match(OperaV),
            Firefox = UA.match(FirefoxB),
            Chrome = UA.match(ChromeB),
            Safari = UA.match(SafariB),
            SafariV = UA.match(SafariV),
            IE = UA.match(IEB),
            Opera = UA.match(OperaB);

    //----- Opera ----
    if ((!Opera == "") & (!OperaV == "")) browser[0] = OperaV[0].replace(/Version/, "Opera")
    else if (!Opera == "") browser[0] = Opera[0]
    else
    //----- IE -----
    if (!IE == "") browser[0] = IE[0]
    else
    //----- Firefox ----
    if (!Firefox == "") browser[0] = Firefox[0]
    else
    //----- Chrome ----
    if (!Chrome == "") browser[0] = Chrome[0]
    else
    //----- Safari ----
    if ((!Safari == "") && (!SafariV == "")) browser[0] = Safari[0].replace("Version", "Safari");

    var outputData;
    if (browser[0] != null) outputData = browser[0].split(browserSplit);
    if (outputData != null) {
        chrAfterPoint = outputData[2].length;
        outputData[2] = outputData[2].substring(0, chrAfterPoint);
        // correct iPad
        if (outputData[0] == 'Safari' && UA.match(iPad))
            outputData[0] = 'Mobile Safari';

        if (outputData[0] == 'Safari' && Math.round(Math.round(outputData[1]) * 10 + Math.round(outputData[2])) < 51)
            outputData[0] = 'OldSafari';
        if (outputData[0] == 'Firefox' && Math.round(outputData[1]) < 5)
            outputData[0] = 'OldFirefox';
        if (outputData[0] == 'Chrome' && Math.round(outputData[1]) < 18)
            outputData[0] = 'OldChrome';
        if (outputData[0] == 'MSIE' && Math.round(outputData[1]) < 8)
            outputData[0] = 'OldIE';
        if (outputData[0] == 'MSIE' && UA.match(aolDesktop)) {
            match = aolDesktop.exec(UA);
            outputData[0] = 'AOL Desktop';
            outputData[1] = match[1];
            outputData[2] = match[2];
        }

        return(outputData);
    }
    else return(false);
};

util.setInputValue = function(element, value){
    browserAPI.log('setting input value');
    if (typeof(element) != 'undefined'
        && typeof(element.attr('maxlength')) != 'undefined'
        && !isNaN(parseFloat(element.attr('maxlength')))
        && value.length > element.attr('maxlength')) {
        browserAPI.log('truncating value to ' + element.attr('maxlength') + ' chars');
        value = value.substring(0, element.attr('maxlength'));
    }
    else if (typeof(element) != 'undefined'
        && typeof(element.attr('data-max-length')) != 'undefined'
        && !isNaN(parseFloat(element.attr('data-max-length')))
        && value.length > element.attr('data-max-length')) {
        browserAPI.log('truncating value to ' + element.attr('data-max-length') + ' chars');
        value = value.substring(0, element.attr('data-max-length'));
    }
    element.val(value);
};

util.beautifulName = function ( str ) {
    if (str != 'undefined' && str !== null) {
        str = str.toLowerCase();
        str = str.replace(/\s\s/g, ' ');
        str = str.replace(/-/g, ' - ');
        // Uppercase the first character of each word in a string
        str = str.replace(/^\s*(.)|\s(.)/g, function ( $1 ) { return $1.toUpperCase(); } );
        str = str.replace(/ - /g, '-');
        str = str.replace(/^\s+|\s+$/g,"");
        return str;
    }
    else
        browserAPI.log("beautifulName: variable undefined");

    return null;
};

util.findRegExp = function (elem, regExp, required) {
    var matches = regExp.exec( elem );
    var result;
    if (matches) {
        browserAPI.log('matched regexp: ' + regExp);
        result = util.trim(matches[1]);
    }
    else {
        browserAPI.log('failed regexp: ' + regExp);
        if (required)
            browserAPI.log('regexp not found');
        else
            result = null;
    }
    return result;
};

util.waitFor = function(structure) {
    // timeout in seconds
    var timeout = structure.timeout;
    if (!timeout)
        timeout = 5;
    var timeoutStep = structure.timeoutStep;
    if (!timeoutStep)
        timeoutStep = 1000;
    var counter = 0;
    var wait = setInterval(function() {
        browserAPI.log('waiting.. ' + counter + '/' + timeout);
        var elem = null;
        if (structure.frameSelector)
            elem = $(frameSelector).contents().find(structure.selector);
        else
            elem = $(structure.selector);
        if (elem.length > 0) {
            clearInterval(wait);
            structure.success(elem);
        }
        if (counter >= timeout) {
            clearInterval(wait);
            structure.fail();
        }
        counter += 1;
    }, timeoutStep);
};

util.modifyDateFormat = function (date, separator) {
    if (!separator)
        separator = '/';
    var LogSplitter = "-----------------------------";
    browserAPI.log(LogSplitter);
    browserAPI.log("Transfer Date to Other Format");
    browserAPI.log("Date: " + date);
    browserAPI.log("Separator: " + separator);

    if (date != null) {
        var new_date = date.split(separator);
        if (typeof(new_date[1]) != 'undefined')
            date = new_date[1] + '/' + new_date[0] + '/' + new_date[2];
        else {
            browserAPI.log("Please set the correct separator!");
            browserAPI.log(LogSplitter);
            return null;
        }
        browserAPI.log("Date In New Format: " + date);
        browserAPI.log(LogSplitter);
        return date;
    }
    else {
        browserAPI.log("Date format is not valid!");
        browserAPI.log(LogSplitter);
        return null;
    }
};


util.sendEvent = function (element, eventName) {
    var event; // The custom event that will be created
    browserAPI.log('sending event ' + eventName);
    console.log(element);

    if (document.createEvent) {
        event = document.createEvent("HTMLEvents");
        event.initEvent(eventName, true, true);
    } else {
        event = document.createEventObject();
        event.eventType = eventName;
    }

    event.eventName = eventName;

    if (document.createEvent) {
        element.dispatchEvent(event);
    } else {
        element.fireEvent("on" + event.eventType, event);
    }
};


util.stristr = function (haystack, needle, bool) {
    var pos = 0;
    haystack += '';
    pos = haystack.toLowerCase().indexOf((needle + '').toLowerCase());
    if (pos == -1) {
        return false;
    } else {
        if (bool) {
            return haystack.substr(0, pos);
        } else {
            return haystack.slice(pos);
        }
    }
};

util.unionArray = function (elem, separator, unique, reverse) {
    // $.map not working in IE 8, so iterating through items
    var result = [];
    if (reverse) {
        for (var i = elem.length-1; i >= 0; i--) {
            var text = util.trim(elem.eq(i).text());
            if (text != "" && (!unique || result.indexOf(text) == -1))
                result.push(text);
        }// for (var i = elem.length-1; i >= 0; i--)
    }
    else {
        for (var i = 0; i < elem.length; i++) {
            var text = util.trim(elem.eq(i).text());
            if (text != "" && (!unique || result.indexOf(text) == -1))
                result.push(text);
        }// for (var i = 0; i < elem.length; i++)
    }
    return result.join( separator );
};

util.dateStringToEnglish = function (date, lang, separator) {
    browserAPI.log("dateStringToEnglish");
    if (!separator)
        separator = '/';
    var LogSplitter = "-----------------------------";
    browserAPI.log(LogSplitter);
    browserAPI.log("Transfer Date to English");
    browserAPI.log("Date: " + date);
    browserAPI.log("Separator: " + separator);
    if (!date) {
        browserAPI.log("Date format is not valid!");
        browserAPI.log(LogSplitter);
        return null;
    }
    var new_date = date.split(separator);
    if (typeof(new_date[2]) == 'undefined') {
        browserAPI.log("Please set the correct separator!");
        browserAPI.log(LogSplitter);
        return null;
    }
    var month = new_date[1];

    var month_array = {
        "en": {
            "january": 0,
            "february": 1,
            "march": 2,
            "april": 3,
            "may": 4,
            "june": 5,
            "july": 6,
            "august": 7,
            "september": 8,
            "october": 9,
            "november": 10,
            "december": 11
        },
        "fr": {
            "janv": 0, "janvier": 0,
            "févr": 1, "fevrier": 1, "février": 1,
            "mars": 2,
            "avril": 3, "avr": 3,
            "mai": 4,
            "juin": 5,
            "juillet": 6, "juil": 6,
            "août": 7, "aout": 7,
            "sept": 8, "septembre": 8,
            "oct": 9, "octobre": 9,
            "novembre": 10, "nov": 10,
            "decembre": 11, "décembre": 11, "déc": 11
        },
        "ru": {
            "январь": 0, "янв": 0, "января": 0,
            "февраля": 1, "фев": 1, "февраль": 1,
            "марта": 2, "мар": 2, "март": 2,
            "апреля": 3, "апр": 3, "апрель": 3,
            "мая": 4, "май": 4,
            "июн": 5, "июня": 5, "июнь": 5,
            "июля": 6, "июль": 6, "июл": 6,
            "августа": 7, "авг": 7, "август": 7,
            "сен": 8, "сентябрь": 8, "сентября": 8,
            "окт": 9, "октября": 9, "октябрь": 9,
            "ноя": 10, "ноября": 10, "ноябрь": 10,
            "дек": 11, "декабрь": 11, "декабря": 11
        },
        "de": {
            "januar": 0, "jan": 0,
            "februar": 1, "feb": 1,
            "mae": 2, "maerz": 2, "märz": 2, "mrz": 2,
            "apr": 3, "april": 3,
            "mai": 4,
            "juni": 5, "jun": 5,
            "jul": 6, "juli": 6,
            "august": 7, "aug": 7,
            "september": 8, "sep": 8,
            "oktober": 9, "okt": 9,
            "nov": 10, "november": 10,
            "dez": 11, "dezember": 11
        },
        "nl": {
            "januari": 0,
            "februari": 1,
            "mrt": 2, "maart": 2,
            "april": 3,
            "mei": 4,
            "juni": 5,
            "juli": 6,
            "augustus": 7,
            "september": 8,
            "oktober": 9,
            "november": 10,
            "december": 11
        },
        "no": {
            "januar": 0, "jan": 0,
            "febr": 1, "februar": 1,
            "mars": 2,
            "april": 3,
            "mai": 4, "kan": 4,
            "juni": 5,
            "juli": 6,
            "august": 7, "aug": 7,
            "september": 8, "sept": 8,
            "okt": 9, "oktober": 9,
            "nov": 10, "november": 10,
            "des": 11, "desember": 11
        },
        "es": {
            "enero": 0,
            "feb": 1, "febrero": 1,
            "marzo": 2,
            "abr": 3, "abril": 3,
            "mayo": 4,
            "jun": 5, "junio": 5,
            "julio": 6, "jul": 6,
            "agosto": 7,
            "sept": 8, "septiembre": 8,
            "oct": 9, "octubre": 9,
            "nov": 10, "noviembre": 10,
            "dic": 11, "diciembre": 11
        },
        "pt": {
            "jan": 0, "janeiro": 0,
            "fev": 1, "fevereiro": 1,
            "março": 2, "mar": 2,
            "abr": 3, "abril": 3,
            "maio": 4, "mai": 4,
            "jun": 5, "junho": 5,
            "julho": 6, "jul": 6,
            "ago": 7, "agosto": 7,
            "setembro": 8, "set": 8,
            "out": 9, "outubro": 9,
            "novembro": 10, "non": 10,
            "dez": 11, "dezembro": 11
        },
        "it": {
            "gen": 0, "gennaio": 0,
            "feb": 1, "febbraio": 1,
            "marzo": 2, "mar": 2,
            "apr": 3, "aprile": 3,
            "maggio": 4, "mag": 4,
            "giu": 5, "giugno": 5,
            "luglio": 6, "lug": 6,
            "ago": 7, "agosto": 7,
            "settembre": 8, "set": 8,
            "ott": 9, "ottobre": 9,
            "novembre": 10, "nov": 10,
            "dic": 11, "dicembre": 11
        },
        "fi": {
            "tammikuuta": 0,
            "helmikuuta": 1,
            "maaliskuuta": 2,
            "huhtikuuta": 3,
            "toukokuuta": 4,
            "kesäkuuta": 5,
            "heinäkuuta": 6,
            "elokuuta": 7,
            "syyskuuta": 8,
            "lokakuuta": 9,
            "marraskuuta": 10,
            "joulukuuta": 11
        },
        "da": {
            "januar": 0,
            "februar": 1,
            "marts": 2,
            "april": 3,
            "maj": 4,
            "juni": 5,
            "juli": 6,
            "august": 7,
            "september": 8,
            "oktober": 9,
            "november": 10,
            "december": 11
        },
        "tr": {
            "ocak": 0,
            "şubat": 1,
            "mart": 2,
            "nisan": 3,
            "mayıs": 4,
            "haziran": 5,
            "temmuz": 6,
            "ağustos": 7,
            "eylül": 8,
            "ekim": 9,
            "kasım": 10,
            "aralık": 11
        },
        "pl": {
            "styczeń": 0, "styczen": 0,
            "luty": 1,
            "marzec": 2,
            "kwiecień": 3, "kwiecien": 3,
            "maj": 4,
            "czerwiec": 5,
            "lipiec": 6, "lipca": 6,
            "sierpien": 7, "sierpień": 7,
            "wrzesien": 8, "wrzesień": 8,
            "pazdziernik": 9, "październik": 9, "października": 9,
            "listopad": 10,
            "grudzien": 11, "grudzień": 11
        },
        "zh": {
            "一月": 0,
            "二月": 1,
            "三月": 2,
            "四月": 3,
            "五月": 4,
            "六月": 5,
            "七月": 6,
            "八月": 7,
            "九月": 8,
            "十月": 9,
            "十一月": 10,
            "十二月": 11
        },
        "hu": {
            "január": 0,
            "február": 1,
            "március": 2,
            "április": 3,
            "május": 4,
            "június": 5,
            "július": 6,
            "augusztus": 7,
            "szeptember": 8,
            "október": 9,
            "november": 10,
            "december": 11
        },
        "sv": {
            "januari": 0,
            "februari": 1,
            "mars": 2,
            "april": 3,
            "maj": 4,
            "juni": 5,
            "juli": 6,
            "augusti": 7,
            "september": 8,
            "oktober": 9,
            "november": 10,
            "december": 11
        },
        "cs": {
            "ledna": 0,
            "únor": 1,
            "březen": 2,
            "dubna": 3,
            "květen": 4,
            "června": 5,
            "července": 6,
            "vznešený": 7,
            "září": 8,
            "říjen": 9,
            "listopadu": 10,
            "prosince": 11
        },
        "ro": {
            "ian": 0,
            "feb": 1,
            "mar": 2,
            "apr": 3,
            "mai": 4,
            "iun": 5,
            "iul": 6,
            "aug": 7,
            "sep": 8,
            "oct": 9,
            "noi": 10,
            "dec": 11
        },
        "ca": {
            "gener": 0,
            "febrer": 1,
            "març": 2,
            "abril": 3,
            "maig": 4,
            "juny": 5,
            "juliol": 6,
            "agost": 7,
            "setembre": 8,
            "octubre": 9,
            "novembre": 10,
            "desembre": 11
        },
        "el": {
            "απρ": 3,
            "ιουνιουν": 5,
            "ιουλ": 6,
            "αυγ": 7,
            "οκτ": 9
        }
    };
    var monthsOutMonths = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    if (typeof (month_array[lang]) != 'undefined' && typeof (month_array[lang][month]) != 'undefined')
        month = monthsOutMonths[month_array[lang][month]];

    date = new_date[0] + ' ' + month + ' ' + new_date[2];
    browserAPI.log("Date In English: " + date);
    browserAPI.log(LogSplitter);

    return date;
};
