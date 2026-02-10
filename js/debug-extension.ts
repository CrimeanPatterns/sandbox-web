import {connect, getTransports, getExtensionInfo} from "@awardwallet/extension-client"
import {Centrifuge} from 'centrifuge'

function connectExtensionChannel(centrifugoJwtToken:string, sessionId:string) {
    connect(
        centrifugoJwtToken,
        sessionId,
(message:string) => {
            alert(message)
        }
    )
}

function connectLog(debugChannelJwtToken:string) {
    const centrifuge = new Centrifuge(getTransports(), {
        token: debugChannelJwtToken,
        emulationEndpoint: '/connection/emulation',
    });

    centrifuge.on('connected', function(ctx) {
        console.log('debug channel connected')
    });

    centrifuge.on('publication', async function(ctx) {
        const payload = ctx.data;
        const logDiv = document.getElementById('log') as HTMLElement
        const element = document.createElement('div')
        let html = payload.formatted
        html = html.replace(/writing logs to ([^\s<>]+)/g, 'writing logs to <a href="/admin/common/logFile.php?Dir=$1&File=log.html" target="_blank">$1</a>')
        element.innerHTML = html
        logDiv.append(element)
    });

    centrifuge.connect()
}

function log(message:string, level:string = 'INFO') {
    const logDiv = document.getElementById('log') as HTMLElement
    const element = document.createElement('div')
    element.innerText = message
    element.className = 'log-' + level.toLowerCase()
    logDiv.append(element)
}

async function checkExtensionInstalled()
{
    const info = await getExtensionInfo()
    console.log('checkExtensionInstalled', info)
    if (!info.installed) {
        (document.getElementById('extension-install') as HTMLElement).style.display = 'block'
    }
}

export {connectExtensionChannel, connectLog, checkExtensionInstalled}
