$(function () {
	window.csrfRetryCounter = 0;
    // show error dialogs on ajax errors
    $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
        // retry with fresh CSRF token, if we got CSRF error from server
		var csrfRetry = typeof(ajaxSettings.csrfRetry) == 'undefined';
		var msie = document.documentMode;
		if (jqXHR.status == 403 && jqXHR.getResponseHeader('X-XSRF-FAILED') == 'true' && csrfRetry) {
			if (msie) {
				// disable cycle csrfRetry on IE
				window.csrfRetryCounter++;
			}
			if (window.csrfRetryCounter <= 2) {
				console.log('retrying with fresh CSRF, should receive on in cookies');
				ajaxSettings.csrfRetry = true;
				$.ajax(ajaxSettings);
				return;
			}
        }
        if (typeof(require) != 'undefined') { // exclude old site
			var modules = ['lib/errorDialog']; // modules on another line from require call to exclude this call from optimizer r.js
			require(modules, function (showErrorDialog) {
				showErrorDialog({
					status: jqXHR.status,
					data: jqXHR.responseJSON ? jqXHR.responseJSON : jqXHR.responseText,
					config: {
						method: ajaxSettings.type,
						url: ajaxSettings.url,
						data: decodeURI(ajaxSettings.data)
					}
				}, (typeof(ajaxSettings.disableErrorDialog) != 'undefined' && ajaxSettings.disableErrorDialog));
			});
		} else {
			if (jqXHR.responseText == 'unauthorized') {
				try {
					if (window.parent != window){
						parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
						return;
					}
				}
				catch(e){}
				location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
			}
		}
    });

    // add CSRF header to ajax POST requests
    $(document).ajaxSend(function (elm, xhr, s) {
		// ie11 fix, see #10625
		var cookie = $.cookie();
		if (cookie.hasOwnProperty('XSRF-TOKEN')) {
			cookie = cookie['XSRF-TOKEN'];
		} else {
			cookie = $.cookie('XSRF-TOKEN');
		}
        xhr.setRequestHeader('X-XSRF-TOKEN', cookie);
		//xhr.setRequestHeader('X-XSRF-TOKEN', $.cookie('XSRF-TOKEN'));
    });


	$(document).ajaxSuccess(function(event, jqXHR, settings){
		var mailErrors = $.trim(jqXHR.getResponseHeader('x-aw-mail-failed'));
		if(mailErrors != '' && !settings.suppressErrors){
			var modules = ['lib/mailErrorDialog']; // modules on another line from require call to exclude this call from optimizer r.js
			require(modules, function (showErrorDialog) {
				showErrorDialog(mailErrors);
			});
		}
	});

});

