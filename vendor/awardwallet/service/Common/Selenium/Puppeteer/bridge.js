const puppeteer = require('puppeteer')

class Bridge {

    async connectToPage() {
        const endPoint = process.argv[2]
        console.log('connecting to ' + endPoint)
        const browser = await puppeteer.connect(
            {browserWSEndpoint: endPoint}
        );
        console.log('connected')
        const pages = await browser.pages();
        const page = pages[0];
        console.log('got page')

        return page
    }

    async exitWithResponse(response) {
        console.log('[RESPONSE_BEGIN]')
        console.log(response)
        console.log('[RESPONSE_END]')
        process.exit(0)
    }
}

module.exports = new Bridge()