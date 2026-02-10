./node_modules/.bin/browserify \
    vendor/awardwallet/service/old/browser/extensions/chrome-stealth/page-script.js \
    -o vendor/awardwallet/service/old/browser/extensions/chrome-stealth/compiled-page-script.js \
    -r puppeteer-extra-plugin-stealth/evasions/chrome.runtime \
    -r puppeteer-extra-plugin-stealth/evasions/console.debug \
    -r puppeteer-extra-plugin-stealth/evasions/iframe.contentWindow \
    -r puppeteer-extra-plugin-stealth/evasions/media.codecs \
    -r puppeteer-extra-plugin-stealth/evasions/navigator.languages \
    -r puppeteer-extra-plugin-stealth/evasions/navigator.permissions  \
    -r puppeteer-extra-plugin-stealth/evasions/navigator.plugins \
    -r puppeteer-extra-plugin-stealth/evasions/navigator.webdriver \
    -r puppeteer-extra-plugin-stealth/evasions/user-agent-override \
    -r puppeteer-extra-plugin-stealth/evasions/webgl.vendor \
    -r puppeteer-extra-plugin-stealth/evasions/window.outerdimensions
