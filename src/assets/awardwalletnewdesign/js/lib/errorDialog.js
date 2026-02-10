/* global debugMode */

define(['lib/dialog', 'translator-boot'], function (dialog) {

	return function (error, disablePopup) {
		disablePopup = disablePopup || false;

		if(error.status == 500 || error.status == 400 || error.status == 404 || error.status == 403){
			if (error.data == 'unauthorized') {
				try {
					if (window.parent != window) {
						parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
						return;
					}
				}
				// eslint-disable-next-line
				catch (e) {
				}
				location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
				return;
			}

			if (disablePopup) return;

			let title = Translator.trans('error.server.other.title');
			if(error.data && typeof(error.data.title) == 'string')
				title = error.data.title;

            let message;
			if(error.data && typeof(error.data.message) == 'string')
				message = error.data.message;
			else {
				if (debugMode && typeof(error.data) == 'string')
					message = error.data;
				else
					message = Translator.trans('alerts.text.error');
			}
			message += '<img src="/ajax_error.gif?message=' + encodeURIComponent(title);
			message += '&status=' + encodeURIComponent(error.status);
			if (typeof(error.config) == 'object') {
				message += '&url=' + encodeURIComponent(error.config.url);
				message += '&method=' + encodeURIComponent(error.config.method);
                if (typeof(error.config.data) != 'undefined') {
                    message += '&req=' + encodeURIComponent(JSON.stringify(error.config.data).substring(0, 50));
                }
			}
			if (error.data) {
				message += '&res=' + encodeURIComponent(JSON.stringify(error.data).substring(0, 50));
			}
			message += '" width="1" height="1">';

			dialog.fastCreate(
				title,
				message,
				true,
				true,
				[
					{
						text: Translator.trans(/** @Desc("Reload") */ 'reload'),
						'class': 'btn-blue',
						click: function () {
							document.location.reload();
						}
					}
				],
				500,
				null,
				'error'
			);
		}

	}

});