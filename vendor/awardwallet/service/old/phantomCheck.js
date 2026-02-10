var page = require('webpage').create(),
    system = require('system'),
	fs = require('fs');

var constants = {
	errorCode: {
		checked: 1,
		invalidPassword: 2,
		lockout: 3,
		providerError: 4,
		engineError: 6,
		warning: 9,
		question: 10
	}
};

var output = {
	errorCode: constants.errorCode.engineError,
	errorMessage: 'Unknown error',
	question: null,
	keepState: false,
	logHeaders: false,
	properties: {},
    state: {}
};

var util = {

	login: null,
	password: null,
	nextStep: null,
	nextStepName: null,
	stepCondition: null,
	stepIndex: 0,
	subStepIndex: 0,
	workingDirectory: null,
	stepTimer: null,
	autoInjectJquery: false,
	stepConditionTimeout: 5000,
	stepPause: 8000,
	nextStepTimer: null,
	ignoredUrls: ['https://ad.yieldmanager.com'],
    globalTimeout: 115000,

	init: function(){
		if(system.args.length != 2)
			util.exit('usage: phantomCheck.js <working directory>');
		util.workingDirectory = system.args[1];
		console.log("working directory: " + util.workingDirectory);
		page.viewportSize = { width: 600, height: 600 };
		page.onLoadFinished = util.loadFinished;
		page.onError = util.onError;
		page.onUrlChanged = util.urlChanged;
		//phantom.libraryPath = system.arguments[1];
		fs.changeWorkingDirectory(util.workingDirectory);
		phantom.injectJs(util.workingDirectory + '/input.js');
		console.log('loading plugin ' + input.providerCode);
		phantom.injectJs('../engine/' + input.providerCode + '/phantom.js');
		console.log('answers on enter: ' + util.countProperties(input.answers));
		page.onConsoleMessage = util.onConsoleMessage;
        //console.log('Default user agent: ' + page.settings.userAgent);
		page.settings.userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.82 Safari/537.36';
        console.log("User agent: " + page.settings.userAgent);
        console.log("\n-----------------------\n");

		console.log('enabling network monitor');
		page.onResourceReceived = util.onResourceReceived;
		page.onResourceRequested = util.onResourceRequested;
		//page.viewportSize = { width: 1024, height: 768 };

		setTimeout(util.globalTimeoutHandler, util.globalTimeout);
	},

	globalTimeoutHandler: function(){
		util.exit('global timeout');
	},

	onConsoleMessage: function (msg) {
		console.log(msg);
	},

	onError: function (msg, trace) {
		console.log(msg);
		trace.forEach(function(item) {
			console.log('  ', item.file, ':', item.line);
		});
		//phantom.exit(1);
	},

	onUrlChanged: function () {
		console.log('url changed');
	},

	onResourceRequested: function (req) {
     	//console.log('requested: ' + JSON.stringify(req, undefined, 4));
		if(util.nextStepTimer && !util.urlIgnored(req.url)){
			console.log('clearing next step timer: ' + req.url);
			util.scheduleNextStep();
		}
        if(output.logHeaders && req && req.url && (!req.contentType || req.contentType.indexOf('image/') == -1)){
            console.log(req.method + ' ' + req.url);
            util.logHeaders(req.headers);
        }
 	},

	urlIgnored: function(url){
		var result = false;
		for(var key in util.ignoredUrls){
			var ignored = util.ignoredUrls[key];
			if(url.indexOf(ignored) == 0){
				result = true;
				break;
			}
		}
		return result;
	},

	onResourceReceived: function (res) {
		if(res && res.contentType && res.contentType.indexOf('image/') == 0)
			return;
		if(res.url && res.stage == 'end'){
            console.log('RESPONSE ' + res.url);
            if(output.logHeaders)
                util.logHeaders(res.headers);
        }
 	},

    logHeaders: function(headers){
        for(index in headers){
            var header = headers[index];
            console.log(header.name + ': ' + header.value);
        }
        console.log('-------');
    },

	evaluate: function(func) {
		var args = [].slice.call(arguments, 1);
		var str = 'function() { return (' + func.toString() + ')(';
		for (var i = 0, l = args.length; i < l; i++) {
			var arg = args[i];
			if (/object|string/.test(typeof arg)) {
				str += 'JSON.parse(' + JSON.stringify(JSON.stringify(arg)) + '),';
			} else {
				str += arg + ',';
			}
		}
		str = str.replace(/,$/, '); }');
		return page.evaluate(str);
	},

	querySelector: function(selector, required, regExp){
		result = util.evaluate(function(){
			var element = document.querySelector(arguments[0]);
			var result = null;
			if(element)
				result = element.innerText;
			return result;
		}, selector);
		console.log('querySelector: ' + selector + ' -> ' + result);
		if(required && !result)
			util.exit('selector not found');
		if(regExp){
			var matches = regExp.exec(result);
			if(matches){
				console.log('matched regexp: ' + regExp);
				result = matches[1];
			}
			else{
				console.log('failed regexp: ' + regExp);
				if(required)
					util.exit('regexp not found');
				else
					result = null;
			}
			console.log('result after regexp: ' + result);
		}
		if(result == '')
			result = null;
		return result;
	},

	findRegExp: function(regExp, required){
		var matches = regExp.exec(page.content);
		if(matches){
			console.log('matched regexp: ' + regExp);
			if(typeof(matches[1]) != 'undefined')
				result = matches[1];
			else
				result = matches[0];
		}
		else{
			console.log('failed regexp: ' + regExp);
			if(required)
				util.exit('regexp not found');
			else
				result = null;
		}
		return result;
	},

	click: function(selector){
		console.log('click: ' + selector);
		if(!util.evaluate(function(){
			var elem = document.querySelector(arguments[0]);
			if(elem){
				console.log('button found, clicking');
				var evt = document.createEvent("MouseEvents");
				evt.initMouseEvent("click", true, true, window, 1, 1, 1, 1, 1, false, false, false, false, 0, elem);
				// dispatchEvent return value is false if at least one of the event
				// handlers which handled this event called preventDefault
				if(elem.dispatchEvent(evt))
					console.log('clicked');
				else
					console.log('click intercepted');
				return true;
			}
			else{
				console.log('button not found');
				return false;
			}
		}, selector))
			util.exit("selector not found");
	},

	mouseClick: function(selector){
		console.log('mouseClick: ' + selector);
		var offset = util.evaluate(function(){
			var elem = document.querySelector(arguments[0]);
			if(elem){
				console.log('button found, returning coords');
				var bounds = elem.getBoundingClientRect();
				return { left: bounds.left + window.pageXOffset, top: bounds.top + window.pageYOffset };
			}
			else{
				console.log('button not found');
				return false;
			}
		}, selector);
		if(!offset)
			util.exit("selector not found");
		console.log('offset: ' + offset.left + ', ' + offset.top);
		page.sendEvent('click', offset.left + 1, offset.top + 1);
	},

	recordStep: function(){
		page.render(util.stepIndex + '.' + util.nextStepName + '.png');
		fs.write(util.stepIndex + '.' + util.nextStepName + '.html', page.content, "w");
	},

	prepareNextStep: function(){
		util.stepIndex++;
		if(util.stepIndex > 30)
			util.exit('too many steps');
		util.subStepIndex = 0;
	},

	loadFinished: function(status){
		console.log("\n-----------------------\n")
		console.log('step ' + util.stepIndex + ', ' + util.nextStepName + ' - load finished');
		var url = page.evaluate(function(){ return document.location.href });
		console.log('URL: ' + url);
		util.recordStep();
		util.scheduleStepTimer();

		if (status !== 'success')
			console.log('step load failed: ' + status);

		util.prepareNextStep();

		var re = /<meta\s+http\-equiv="refresh"\s+content="(\d+);\s*URL=([^"]+)">/i;
		var matches = re.exec(page.content);
		if(matches){
			var seconds = Math.round(matches[1]);
			if(seconds <= 10){
				console.log('found meta-redirect: ' + seconds + ', ' + matches[2] + ', waiting');
				//document.location.href = matches[2];
				return;
			}
		}

		if(util.autoInjectJquery)
			util.injectJquery();

		util.scheduleNextStep();
		console.log('loadFinished exit');
	},

	scheduleNextStep: function(){
		console.log('scheduling next step');
		if(util.nextStepTimer){
			clearTimeout(util.nextStepTimer);
			console.log('next step timer cancelled');
		}
		util.nextStepTimer = setTimeout(function(){
			console.log('next step timer fired');
			util.nextStepTimer = null;
			if(util.stepCondition)
				util.waitFor(util.stepCondition, util.nextStep, util.stepConditionTimeout);
			else
				util.nextStep();
		}, util.stepPause);
	},

	stepTimedOut: function(){
		console.log('step timed out');
		util.exit('timed out');
	},

	screenshot: function(name){
		console.log("taking screenshot: " + name);
		page.render(util.stepIndex + '.' + util.nextStepName + '-' + util.subStepIndex + '.' + name + '.png');
	},

	setNextStep: function(stepFunc){
		util.nextStep = stepFunc;
		util.nextStepName = util.extractFunctionName(stepFunc);
		console.log('setting next step: ' + util.nextStepName);
		util.scheduleStepTimer();
		console.log('timer set');
	},

	scheduleStepTimer: function(){
		if(util.stepTimer)
			clearTimeout(util.stepTimer);
		util.stepTimer = setTimeout(util.stepTimedOut, 60000);
	},

	extractFunctionName: function(func){
		var tokens =
			/^[\s\r\n]*function[\s\r\n]*([^\(\s\r\n]*?)[\s\r\n]*\([^\)\s\r\n]*\)[\s\r\n]*\{((?:[^}]*\}?)+)\}\s*$/
			.exec(func.toString());

		if (!tokens) {
			throw "Invalid function.";
		}

		return tokens[1];
	},

	injectJquery: function(){
		console.log('injecting jquery');
		page.injectJs('../../lib/3dParty/jquery/jq.js');
	},

	checkError: function(message, code){
		if(message)
			util.returnError(message, code);
	},

	exit: function(message){
		if(message){
			console.log('error: ' + message);
		}
		util.prepareNextStep();
		util.nextStepName = 'exit';
		util.recordStep();
		fs.write('output.js', JSON.stringify(output), "w");
		console.log('exit');
		phantom.exit();
	},

	returnError: function(message, code){
		if(code)
			output.errorCode = code;
		else
			output.errorCode = constants.errorCode.invalidPassword;
		output.errorMessage = message;
		console.log('returning error: ' + message);
		util.exit();
	},

	countProperties: function (obj) {
		var count = 0;
		for (var prop in obj) {
			if (obj.hasOwnProperty(prop))
				++count;
		}
		return count;
	},

	getObjectProperty: function (obj, index) {
		var pos = 0;
		for (var prop in obj) {
			if (obj.hasOwnProperty(prop)){
				if(pos == index)
					return obj[prop];
				else
					pos++;
			}
		}
		return null;
	},

	getObjectPropertyName: function (obj, index) {
		var pos = 0;
		for (var prop in obj) {
			if (obj.hasOwnProperty(prop)){
				if(pos == index)
					return prop;
				else
					pos++;
			}
		}
		return null;
	},

	askQuestion: function(question, error){
		console.log('askQuestion: ' + question, ', error: ' + error);
		output.question = question;
		output.errorCode = constants.errorCode.question;
		if(error)
			output.errorMessage = error;
	},

	setBalance: function(balance){
		console.log('setBalance: ' + balance);
		if(balance != '' && balance != null){
			console.log('successful');
			output.balance = balance;
			output.errorCode = constants.errorCode.checked;
            return true;
		}
		else{
            console.log('invalid value');
            return null;
        }
	},

	setBalanceNA: function(){
		console.log('setBalanceNA');
		output.balance = null;
		output.errorCode = constants.errorCode.checked;
		return true;
	},

	setProperty: function(code, value){
		console.log('setProperty: ' + code + ' to ' + value);
		if(value != null && value != ''){
			console.log('set');
			output.properties[code] = value;
		}
	},

	waitFor: function(testFx, onReady, timeOutMillis) {
		console.log("waitFor: " + testFx.toString());
	    var maxtimeOutMillis = timeOutMillis ? timeOutMillis : 3000, //< Default Max Timout is 3s
	        start = new Date().getTime(),
	        condition = false,
	        interval = setInterval(function() {
	            if ( (new Date().getTime() - start < maxtimeOutMillis) && !condition ) {
	                // If not time-out yet and condition not yet fulfilled
	                condition = page.evaluate(testFx);
	            } else {
	                if(!condition) {
	                    // If condition still not fulfilled (timeout but condition is 'false')
	                    console.log("waitFor timeout");
	                    util.exit();
	                } else {
	                    // Condition fulfilled (timeout and/or condition is 'true')
	                    console.log("waitFor finished in " + (new Date().getTime() - start) + "ms.");
	                    typeof(onReady) === "string" ? eval(onReady) : onReady(); //< Do what it's supposed to do once the condition is fulfilled
	                    clearInterval(interval); //< Stop this interval
	                }
	            }
	        }, 250); //< repeat check every 250ms
	},

	setStepCondition: function(fx){
		if(fx)
			console.log("setStepCondition: " + fx.toString());
		else
			console.log("setStepCondition: null");
		util.stepCondition = fx;
	},

	jqueryLoaded: function(){
		return page.evaluate(function(){
			return typeof($) == 'function';
		});
	},

    modifyDateFormat: function (date, separator) {
        if (!separator)
            separator = '/';
        var LogSplitter = "-----------------------------";
        console.log(LogSplitter);
        console.log("Transfer Date In Other Format");
        console.log("Date: " + date);
        console.log("Separator: " + separator);

        if (date != null) {
            var new_date = date.split(separator);
            if (typeof(new_date[1]) != 'undefined')
                date = new_date[1] + '/' + new_date[0] + '/' + new_date[2];
            else {
                console.log("Please set the correct separator!");
                console.log(LogSplitter);
                return null;
            }
            console.log("Date In New Format: " + date);
            console.log(LogSplitter);
            return date;
        }
        else {
            console.log("Date format is not valid!");
            console.log(LogSplitter);
            return null;
        }
    },

    beautifulName: function ( str ) {
        if (str != 'undefined' && str !== null) {
            str = str.toLowerCase();
			str = str.replace(/\r\t\n/g, ' ');
			str = str.replace(/(\&nbsp;)/g, ' ');
            str = str.replace(/\s\s/g, ' ');
            str = str.replace(/-/g, ' - ');
            // Uppercase the first character of each word in a string
            str = str.replace(/^\s*(.)|\s(.)/g, function ( $1 ) { return $1.toUpperCase(); } );
            str = str.replace(/ - /g, '-');
            str = str.replace(/^\s+|\s+$/g,"");
            return str;
        }
        else
            console.log("beautifulName: variable undefined");

        return null;
    }


}

try{
	util.init();
	start();
}
catch(e){
	util.exit(e);
}