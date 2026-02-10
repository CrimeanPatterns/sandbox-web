// Breaks out of the content script context by injecting a specially
// constructed script tag and injecting it into the page.
const runInPageContext = (method, ...args) => {
    // The stringified method which will be parsed as a function object.
    const stringifiedMethod = method instanceof Function
            ? method.toString()
            : `() => { ${method} }`;

    // The stringified arguments for the method as JS code that will reconstruct the array.
    const stringifiedArgs = JSON.stringify(args);

    // The full content of the script tag.
    const scriptContent = `
    // Parse and run the method with its arguments.
    (${stringifiedMethod})(...${stringifiedArgs});

    // Remove the script element to cover our tracks.
    document.currentScript.parentElement
      .removeChild(document.currentScript);
  `;

    // Create a script tag and inject it into the document.
    const scriptElement = document.createElement('script');
    scriptElement.innerHTML = scriptContent;
    document.documentElement.prepend(scriptElement);
};

const overwriteFingerprints = () => {

    // see FingerprintParams php class
    // will be replaced in ChromiumStarter::replaceFingerprintParams
    const fingerprintParams = {};

    console.log('hiding selenium, params', fingerprintParams);

    if (fingerprintParams.webdriver === false) {
        if (window.navigator.webdriver !==false) {
            console.log('defining webdriver as false');
            Object.defineProperty(window.navigator, 'webdriver', {
                get: () => false,
                enumerable: true
            });
        }
    } else {
        console.log('deleting webdriver');
        const newProto = window.navigator.__proto__
        delete newProto.webdriver;
        window.navigator.__proto__ = newProto;
    }

    if (fingerprintParams.language) {
        console.log('defining language');
        Object.defineProperty(window.navigator, 'language', {
            get: () => fingerprintParams.language,
            enumerable: true
        });
    }

    if (fingerprintParams.languages) {
        console.log('defining languages');
        Object.defineProperty(window.navigator, 'languages', {
            get: () => fingerprintParams.languages,
            enumerable: true
        });
    }

    if (fingerprintParams.deviceMemory) {
        console.log('defining deviceMemory');
        Object.defineProperty(window.navigator, 'deviceMemory', {
            get: () => fingerprintParams.deviceMemory,
            enumerable: true
        });
    }

    if (fingerprintParams.appVersion) {
        console.log('defining appVersion');
        Object.defineProperty(window.navigator, 'appVersion', {
            get: () => fingerprintParams.appVersion,
            enumerable: true
        });
    }

    if (fingerprintParams.doNotTrack) {
        console.log('defining doNotTrack');
        Object.defineProperty(window.navigator, 'doNotTrack', {
            get: () => fingerprintParams.doNotTrack,
            enumerable: true
        });
    }

    if (fingerprintParams.buildID) {
        console.log('defining buildID');
        Object.defineProperty(window.navigator, 'buildID', {
            get: () => fingerprintParams.buildID,
            enumerable: true
        });
    }

    if (fingerprintParams.maxTouchPoints !== null) {
        console.log('defining maxTouchPoints');
        Object.defineProperty(window.navigator, 'maxTouchPoints', {
            get: () => fingerprintParams.maxTouchPoints,
            enumerable: true
        });
    }

    if (fingerprintParams.hardwareConcurrency) {
        console.log('defining hardwareConcurrency');
        Object.defineProperty(window.navigator, 'hardwareConcurrency', {
            get: () => fingerprintParams.hardwareConcurrency,
            enumerable: true
        });
    }

    if (fingerprintParams.plugins) {
        console.log('defining plugins');
        class Plugin extends Array {
            constructor(description, filename, name, ...items) {
                super(items);
                this.description = description;
                this.filename = filename;
                this.name = name;
            }
        }

        console.log('setting plugins');

        function mockPluginsAndMimeTypes() {
            /* global MimeType MimeTypeArray PluginArray */

            // Disguise custom functions as being native
            const makeFnsNative = (fns = []) => {
                const oldCall = Function.prototype.call

                function call() {
                    return oldCall.apply(this, arguments)
                }

                // eslint-disable-next-line
                Function.prototype.call = call

                const nativeToStringFunctionString = Error.toString().replace(
                        /Error/g,
                        'toString'
                )
                const oldToString = Function.prototype.toString

                function functionToString() {
                    for (const fn of fns) {
                        if (this === fn.ref) {
                            return `function ${fn.name}() { [native code] }`
                        }
                    }

                    if (this === functionToString) {
                        return nativeToStringFunctionString
                    }
                    return oldCall.call(oldToString, this)
                }

                // eslint-disable-next-line
                Function.prototype.toString = functionToString
            }

            const mockedFns = []

            fingerprintParams.mimeTypes.forEach(function (mimeType) {
                if (mimeType['enabledPlugin'] && mimeType['enabledPlugin'] === 'Plugin') {
                    mimeType['enabledPlugin'] = Plugin;
                }
            });

            const fakeData = {
                mimeTypes: fingerprintParams.mimeTypes,
                plugins: fingerprintParams.plugins,
                fns: {
                    namedItem: instanceName => {
                        // Returns the Plugin/MimeType with the specified name.
                        const fn = function (name) {
                            if (!arguments.length) {
                                throw new TypeError(
                                        `Failed to execute 'namedItem' on '${instanceName}': 1 argument required, but only 0 present.`
                                )
                            }
                            return this[name] || null
                        }
                        mockedFns.push({ref: fn, name: 'namedItem'})
                        return fn
                    },
                    item: instanceName => {
                        // Returns the Plugin/MimeType at the specified index into the array.
                        const fn = function (index) {
                            if (!arguments.length) {
                                throw new TypeError(
                                        `Failed to execute 'namedItem' on '${instanceName}': 1 argument required, but only 0 present.`
                                )
                            }
                            return this[index] || null
                        }
                        mockedFns.push({ref: fn, name: 'item'})
                        return fn
                    },
                    refresh: instanceName => {
                        // Refreshes all plugins on the current page, optionally reloading documents.
                        const fn = function () {
                            return undefined
                        }
                        mockedFns.push({ref: fn, name: 'refresh'})
                        return fn
                    }
                }
            }
            // Poor mans _.pluck
            const getSubset = (keys, obj) =>
                    keys.reduce((a, c) => ({...a, [c]: obj[c]}), {})

            function generateMimeTypeArray() {
                const arr = fakeData.mimeTypes
                        .map(obj => getSubset(['type', 'suffixes', 'description'], obj))
                        .map(obj => Object.setPrototypeOf(obj, MimeType.prototype))
                arr.forEach(obj => {
                    arr[obj.type] = obj
                })

                // Mock functions
                arr.namedItem = fakeData.fns.namedItem('MimeTypeArray')
                arr.item = fakeData.fns.item('MimeTypeArray')

                return Object.setPrototypeOf(arr, MimeTypeArray.prototype)
            }

            const mimeTypeArray = generateMimeTypeArray()
            Object.defineProperty(window.navigator, 'mimeTypes', {
                get: () => mimeTypeArray,
                enumerable: true
            })

            function generatePluginArray() {
                const arr = fakeData.plugins
                        .map(obj => getSubset(['name', 'filename', 'description'], obj))
                        .map(obj => {
                            const mimes = fakeData.mimeTypes.filter(
                                    m => m.__pluginName === obj.name
                            )
                            // Add mimetypes
                            mimes.forEach((mime, index) => {
                                window.navigator.mimeTypes[mime.type].enabledPlugin = obj
                                obj[mime.type] = window.navigator.mimeTypes[mime.type]
                                obj[index] = window.navigator.mimeTypes[mime.type]
                            })
                            obj.length = mimes.length
                            if (fingerprintParams.mockPluginToString) {
                                obj.toString = function () {
                                    return '[object Plugin]';
                                }
                            }
                            return obj
                        })
                        .map(obj => {
                            // Mock functions
                            obj.namedItem = fakeData.fns.namedItem('Plugin')
                            obj.item = fakeData.fns.item('Plugin')
                            return obj
                        })
                        .map(obj => Object.setPrototypeOf(obj, Plugin.prototype))
                arr.forEach(obj => {
                    arr[obj.name] = obj
                })

                // Mock functions
                arr.namedItem = fakeData.fns.namedItem('PluginArray')
                arr.item = fakeData.fns.item('PluginArray')
                arr.refresh = fakeData.fns.refresh('PluginArray')
                if (fingerprintParams.firefox) {
                    arr.toString = function () {
                        return '[object PluginArray]';
                    }
                }

                return Object.setPrototypeOf(arr, PluginArray.prototype)
            }

            const pluginArray = generatePluginArray()
            Object.defineProperty(window.navigator, 'plugins', {
                get: () => pluginArray,
                enumerable: true
            })

            // Make mockedFns toString() representation resemble a native function
            makeFnsNative(mockedFns)
        }

        try {
            const isPluginArray = window.navigator.plugins instanceof PluginArray
            const hasPlugins = isPluginArray && window.navigator.plugins.length > 0
            if (!isPluginArray || !hasPlugins || fingerprintParams.mockPlugins) {
                mockPluginsAndMimeTypes()
            }
        } catch (err) {
            console.log('failed to mock plugins', err);
        }
    }

    if (fingerprintParams.webglVendor || fingerprintParams.webglRenderer) {
        console.log('defining webGl');
        const getParameter = WebGLRenderingContext.prototype.getParameter;
        WebGLRenderingContext.prototype.getParameter = function (parameter) {
            // UNMASKED_VENDOR_WEBGL
            if (parameter === 37445) {
                return fingerprintParams.webglVendor;
            }
            // UNMASKED_RENDERER_WEBGL
            if (parameter === 37446) {
                return fingerprintParams.webglRenderer;
            }

            return getParameter.call(this, parameter);
        };
    }

    if (fingerprintParams.mockPermissions) {
        console.log('defining permissions');
        Object.defineProperty(Notification, 'permission', {
            get: () => 'default',
            enumerable: true
        });

        const originalQuery = window.navigator.permissions.query;
        window.navigator.permissions.query = (parameters) => (
                parameters.name === 'notifications' ? Promise.resolve({state: 'prompt'}) : originalQuery(parameters)
        );
    }

    if (fingerprintParams.brokenImageSize) {
        console.log('replacing broken image with size ' + fingerprintParams.brokenImageSize);
        ['height', 'width'].forEach(property => {
            // store the existing descriptor
            const imageDescriptor = Object.getOwnPropertyDescriptor(HTMLImageElement.prototype, property);

            // redefine the property with a patched descriptor
            Object.defineProperty(HTMLImageElement.prototype, property, {
                ...imageDescriptor,
                get: function () {
                    // return an arbitrary non-zero dimension if the image failed to load
                    if (this.complete && this.naturalHeight == 0) {
                        return fingerprintParams.brokenImageSize;
                    }
                    // otherwise, return the actual dimension
                    return imageDescriptor.get.apply(this);
                },
                enumerable: true
            });
        });
    }

    if (fingerprintParams.hairline) {
        console.log('defining hairline');
        // store the existing descriptor
        const elementDescriptor = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'offsetHeight');

        // redefine the property with a patched descriptor
        console.log('patching hairline');
        Object.defineProperty(HTMLDivElement.prototype, 'offsetHeight', {
            ...elementDescriptor,
            get: function () {
                if (this.id === 'modernizr') {
                    console.log('someone asked hairline');
                    return 1;
                }
                return elementDescriptor.get.apply(this);
            },
            enumerable: true
        });
    }

    // patch fonts detection, see distill.js:277
    if (fingerprintParams.fonts && fingerprintParams.fonts.length > 0) {
        console.log('patching fonts');
        ['offsetHeight', 'offsetWidth'].forEach(property => {
            // store the existing descriptor
            console.log('patching ' + property);
            const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLElement.prototype, property);

            // redefine the property with a patched descriptor
            Object.defineProperty(HTMLElement.prototype, property, {
                ...originalDescriptor,
                get: function () {
                    let result = originalDescriptor.get.apply(this);
//                    console.log('called patched ' + property + ' on font ' + this.style.fontFamily + ', original: ' + result);
                    const currentFont = this.style.fontFamily;
                    const listOfFonts = currentFont.indexOf(',') > 0;
                    if (!listOfFonts) {
                        this.originalFontResult = result;
                        this.originalFontFamily = currentFont;
                    }
                    let fontAllowed = false;
                    for (var i in fingerprintParams.fonts) {
                        if (currentFont.indexOf(fingerprintParams.fonts[i]) >= 0) {
                            let delta = Math.random() * 0.3 + 0.1;
                            if (Math.random() > 0.5) {
                                delta = 1 - delta;
                            }
                            result = Math.floor(result * delta);
                            //                          console.log('patched to ' + result);
                            fontAllowed = true;
                        }
                    }
                    if (!fontAllowed && listOfFonts && this.originalFontResult && currentFont.indexOf(this.originalFontFamily) > 0) {
                        //                    console.log('font ' + currentFont + ' is not allowed, showed results for ' + this.originalFontFamily);
                        result = this.originalFontResult;
                    }
                    return result;
                },
                enumerable: true
            });
        });
    }

    if (fingerprintParams.chrome) {
        if (typeof window.chrome === 'undefined')
        {
            console.log('adding chrome');
            window.chrome = {}
        }

        if (typeof window.chrome.runtime === 'undefined') {
            console.log('adding chrome runtime');
            window.chrome.runtime = {}
        }
    }

    console.log('calc platform|oscpu from UA');
    if (window.navigator.userAgent.search('Windows') !== -1) {
        console.log('UA: Windows');
        _platform = 'Win32';
        if (window.navigator.userAgent.search('Win64') !== -1)
            _platform = 'Win64';
        let r = navigator.userAgent.match(/Windows[^)]+/ims);
        if (typeof r[0] != 'undefined')
            _oscpu = r[0];
    } else if (window.navigator.userAgent.search('Macintosh') !== -1) {
        console.log('UA: Macintosh');
        _platform = 'MacIntel';
        let r = navigator.userAgent.match(/Intel Mac OS X \d+.\d+/ims);
        if (typeof r[0] != 'undefined')
            _oscpu = r[0];
    } else if (window.navigator.userAgent.search('Linux') !== -1 || window.navigator.userAgent.search('X11') !== -1) {
        console.log('UA: Linux');
        _platform = 'Linux x86_64';
        _oscpu = _platform;
    }

    console.log('calc browser version from UA');
    let r = navigator.userAgent.match(/ (?:Chrome|Firefox|Version)\/(\d+)\./);
    if (r && typeof r[1] != 'undefined')
        _version = r[1];

    if (fingerprintParams.preinstalled === false && typeof _platform != 'undefined' && fingerprintParams.platform !== _platform) {
        console.log('[fingerprintParams.platform] old: ' + fingerprintParams.platform + ' new' + _platform);
        fingerprintParams.platform = _platform;
    }

    if (fingerprintParams.preinstalled === false && typeof _oscpu != 'undefined' && fingerprintParams.oscpu !== _oscpu) {
        console.log('[fingerprintParams.oscpu] old: ' + fingerprintParams.oscpu + ' new' + _oscpu);
        fingerprintParams.oscpu = _oscpu;
    }

    if (fingerprintParams.platform) {
        console.log('defining platform');
        Object.defineProperty(window.navigator, 'platform', {
            get: () => fingerprintParams.platform,
            enumerable: true
        });
    }

    if (typeof window.navigator.userAgentData !== 'undefined') {
        console.log('deleting oscpu');
        Object.defineProperty(window.navigator, 'oscpu', {
            get: () => undefined,
            enumerable: true
        });
        if (fingerprintParams.platform) {
            let UAplatform = '';
            switch (fingerprintParams.platform.substr(0, 3)){
                case 'Win':
                    UAplatform = 'Windows';
                    break;
                case 'Lin':
                    UAplatform = 'Linux';
                    break;
                case 'Mac':
                    UAplatform = 'macOS';
                    break;
            }
            if (UAplatform.length > 0) {
                console.log('defining NavigatorUAData/platform');
                // var UA = Object.create(window.navigator.userAgentData);
                var UA = window.navigator.userAgentData;
                let brands = UA.brands;
                if (_version) {
                    var e = [];
                    window.navigator.userAgentData.brands.forEach(
                        function(elem)
                            {ee = elem; ee.version = _version; e.push(ee)}
                    );
                    brands = e;
                }
                Object.defineProperties(window.navigator, {
                    userAgentData: {
                        value: {"brands": brands, "mobile": false, "platform": UAplatform},
                        configurable: false,
                        enumerable: true,
                        writable: false
                    }
                });
                console.log(window.navigator.userAgentData.platform);
            }
        }
    } else if (fingerprintParams.oscpu) {
        console.log('define oscpu');
        Object.defineProperty(window.navigator, 'oscpu', {
            get: () => fingerprintParams.oscpu,
            enumerable: true
        });
    } else {
        console.log('define oscpu');
        Object.defineProperty(window.navigator, 'oscpu', {
            get: () => undefined
        });
    }

    if (fingerprintParams.chrome === false && typeof window.chrome !== 'undefined') {
        console.log('deleting chrome');
        window.chrome = undefined;
    }

    if (fingerprintParams.maskAudio) {
        console.log('masking audio context');
        const context = {
            "BUFFER": null,
            "getChannelData": function (e) {
                const getChannelData = e.prototype.getChannelData;
                Object.defineProperty(e.prototype, "getChannelData", {
                    "value": function () {
                        const results_1 = getChannelData.apply(this, arguments);
                        if (context.BUFFER !== results_1) {
                            context.BUFFER = results_1;
                            window.top.postMessage("audiocontext-fingerprint-defender-alert", '*');
                            for (var i = 0; i < results_1.length; i += 100) {
                                let index = Math.floor(fingerprintParams.random * i);
                                results_1[index] = results_1[index] + fingerprintParams.random * 0.0000001;
                            }
                        }
                        //
                        return results_1;
                    }
                });
            },
            "createAnalyser": function (e) {
                const createAnalyser = e.prototype.__proto__.createAnalyser;
                Object.defineProperty(e.prototype.__proto__, "createAnalyser", {
                    "value": function () {
                        const results_2 = createAnalyser.apply(this, arguments);
                        const getFloatFrequencyData = results_2.__proto__.getFloatFrequencyData;
                        Object.defineProperty(results_2.__proto__, "getFloatFrequencyData", {
                            "value": function () {
                                window.top.postMessage("audiocontext-fingerprint-defender-alert", '*');
                                const results_3 = getFloatFrequencyData.apply(this, arguments);
                                for (var i = 0; i < arguments[0].length; i += 100) {
                                    let index = Math.floor(fingerprintParams.random * i);
                                    arguments[0][index] = arguments[0][index] + fingerprintParams.random * 0.1;
                                }
                                //
                                return results_3;
                            }
                        });
                        //
                        return results_2;
                    }
                });
            }
        };
        context.getChannelData(AudioBuffer);
        context.createAnalyser(AudioContext);
        context.getChannelData(OfflineAudioContext);
        context.createAnalyser(OfflineAudioContext);
    }

    if (fingerprintParams.maskConsole) {
        console.log('masking console');
        window.console.debug = () => {
            return null
        }
    }

    console.log('hide-selenium complete');
    // const historyLength = Math.random() * 20 + 5;
    // for(let n = 0; n < historyLength; n++) {
    //     window.history.pushState({}, 'Awesome', '/' + n + '.some');
    // }

}

// Break out of the sandbox and run `overwriteFingerprints()` in the page context.
runInPageContext(overwriteFingerprints);