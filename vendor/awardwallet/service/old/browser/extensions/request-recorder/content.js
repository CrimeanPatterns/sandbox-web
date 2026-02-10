let requestRecorderInjected = false
if (!requestRecorderInjected) {
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

    function enableRecorder() {
        console.log('patching XmlHttpRequest');

        function captureXMLHttpRequest(recorder) {
            var XHR = XMLHttpRequest.prototype;

            var open = XHR.open;
            var send = XHR.send;
            var setRequestHeader = XHR.setRequestHeader;

            // Collect data:
            XHR.open = function (method, url) {
                this._method = method;
                this._url = url;
                this._requestHeaders = {};
                this._startTime = (new Date()).toISOString();
                return open.apply(this, arguments);
            };

            XHR.setRequestHeader = function (header, value) {
                this._requestHeaders[header] = value;
                return setRequestHeader.apply(this, arguments);
            };

            XHR.send = function (postData) {
                this.addEventListener('load', function () {
                    var endTime = (new Date()).toISOString();

                    if (recorder) {
                        var myUrl = this._url ? this._url.toLowerCase() : this._url;
                        if (myUrl) {

                            var requestModel = {
                                'uri': this._url,
                                'verb': this._method,
                                'time': this._startTime,
                                'headers': this._requestHeaders
                            };

                            if (postData) {
                                if (typeof postData === 'string') {
                                    console.log('request post data is string');
                                    try {
                                        requestModel['body'] = JSON.parse(postData);
                                    } catch (err) {
                                        console.log('JSON decode failed');
                                        console.log(err);
                                        requestModel['body'] = postData;
                                    }
                                } else if (typeof postData === 'object' || typeof postData === 'array' || typeof postData === 'number' || typeof postData === 'boolean') {
                                    requestModel['body'] = postData;
                                }
                            }

                            var responseHeaders = this.getAllResponseHeaders();

                            var responseModel = {
                                'status': this.status,
                                'time': endTime,
                                'headers': responseHeaders
                            };

                            if (this.responseText) {
                                // responseText is string or null
                                try {
                                    responseModel['body'] = JSON.parse(this.responseText);
                                } catch (err) {
                                    responseModel['body'] = this.responseText;
                                }
                            }

                            var event = {
                                'request': requestModel,
                                'response': responseModel
                            };

                            recorder(event);
                        }
                    }
                });
                return send.apply(this, arguments);
            };

            var undoPatch = function () {
                XHR.open = open;
                XHR.send = send;
                XHR.setRequestHeader = setRequestHeader;
            };

            console.log('patched XmlHttpRequest');
            return undoPatch;
            // so caller have a handle to undo the patch if needed.
        }

        function recordRequest(event) {
            //console.log(event);
            var data = {type: "RECORDED_REQUEST", event: event};
            window.postMessage(data, "*");
        }

        captureXMLHttpRequest(recordRequest)
    }

    console.log('setup request recorder');
    window.addEventListener("message", function (event) {
        if (event.data.type && (event.data.type == "RECORDED_REQUEST")) {
            //console.log(event.data.event);
            chrome.runtime.sendMessage(event.data.event);
        }
    });

    console.log('injecting request recorder');
    runInPageContext(enableRecorder);

    requestRecorderInjected = true;
}