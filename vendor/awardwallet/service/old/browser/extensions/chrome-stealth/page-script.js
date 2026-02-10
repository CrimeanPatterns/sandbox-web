(function () {
    'use strict'
    const windowDebugBackup = window.debug;

    window.debug = function debug(...args)
    {
        console.log(...args);
    }

    const fingerprintParams = {};
    console.log('PAGE: hiding selenium, params', fingerprintParams);

    const pluginLauncher = require("./plugin-launcher.js")
    const stealthPlugin = require('puppeteer-extra-plugin-stealth')
    pluginLauncher.use(stealthPlugin())
    pluginLauncher.launch().then(function(){
        console.log('disabling debug');
        if (typeof(windowDebugBackup) === 'undefined') {
            delete window.debug;
        } else {
            window.debug = windowDebugBackup;
        }
    })



    document.currentScript.parentElement.removeChild(document.currentScript);
    console.log('PAGE: hide-selenium complete');
}());