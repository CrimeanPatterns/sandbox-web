const puppeteer = require('puppeteer');

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

(async () => {
    const browser = await puppeteer.launch({
        headless: false
    });
    const page = await browser.newPage();
    await page.goto('https://loyalty.awardwallet.com/test/fetch.html');
    page.on('request', request => {
        console.log('request: ' + request.resourceType() + ':' + request?.response()?.text())
    });
    page.on('console', async message => {
        console.log(message)
    })
    page.on('response', async response => {
        if (response.request().resourceType() === 'fetch') {
            console.log('response on ' + response.request().url() + ': ' + await response.text())
            process.exit(0)
        }
    })
    await page.screenshot({path: 'example.png'});
    await sleep(10000);
    await browser.close();
})();

