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

const userAgent = {

    // @TODO: __toString and initial value
    replace: function(from, to) {
        const userAgent = navigator.userAgent
        console.log('userAgent replace', from, to);
        result = userAgent.replace(from, to);
        console.log('userAgent replaced', result)
        return result
    }

}

const browser = {

    on: function(event, callback) {
        console.log('browser.on', event)
        if (event === 'targetcreated') {
            onTargetCreated.push(callback)
        }
    },

    userAgent: async function() {
        return userAgent
    }

}

const client = {

    send: function(method, ...args) {
        console.log('client.send', method, ...args)
    }

}

const page = {

    evaluateOnNewDocument: async function (pageFunction, ...args) {
        console.log('page.evaluateOnNewDocument', pageFunction, ...args);
        runInPageContext(pageFunction, ...args)
    },

    browser: function() {
        return browser
    },

    _client: client
}

const target = {

    type: function() {
        return 'page';
    },

    page: async function() {
        console.log('page called');
        return page;
    }
}

let onTargetCreated = []

function start() {
    console.log('starting')

    const { PuppeteerExtra } = require('./puppeteer-extra-mock')
    const puppeteer = new PuppeteerExtra()

    puppeteer.use(require('puppeteer-extra-plugin-user-preferences')())
    puppeteer.use(require('puppeteer-extra-plugin-user-data-dir')())

    const evasions = [
        'chrome.app',
        'chrome.csi',
        'chrome.loadTimes',
        'chrome.runtime',
        'iframe.contentWindow',
        'media.codecs',
        'navigator.hardwareConcurrency',
        'navigator.languages',
        'navigator.permissions',
        'navigator.plugins',
        'navigator.webdriver',
        'sourceurl',
        'user-agent-override',
        'webgl.vendor',
        'window.outerdimensions'
    ]

    evasions.map(e => {
        let Plugin = require('puppeteer-extra-plugin-stealth/evasions/' + e + '/index.js')
        let plugin = Plugin()
        puppeteer.use(plugin)
    })

    puppeteer.run(browser).then(() => {
        console.log('puppeteer run finished')
        console.log('onTargetCreated count', onTargetCreated.length)
        onTargetCreated.map(callback => {
            callback(target);
        })
    })

    // plugin.onPluginRegistered().then(() => {
    //     console.log('plugin registered')
    //     plugin.onPageCreated(page).then(() => {
    //         console.log('onPageCreated finished');
    //     })
    // })
    console.log('started')
}

start();

//runInPageContext(runStealth);