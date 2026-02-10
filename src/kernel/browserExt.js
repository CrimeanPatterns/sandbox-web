var browserExt = {

	installed: false,
	onReady: [],
	onInfo: null,
	accounts: null,
	expectedVersion: "0.98", // this macro will be replaced by package.sh script
	extensionId: null,
	extensionVersion: null,
	portCommSupported: false,
	requiredValidExtension: false,
	receiveFromBrowser: false,
	totals: null,
	onProviderReady: null,
	state: null,
	onComplete: null,
	onError: null,
	errorPrefix: 'Something unexpected just happened while trying to register your account.' +
		'<br/><br/>Please do not click any links or buttons until the registration is finished, ' +
		'once the process is done we will switch you back to awardwallet.com automatically.<br/><br/>',
	progressImage: "<img src='/lib/images/progressCircle.gif' " +
		"style='border: none; float: left; width: 16px; height: 16px; margin-right: 10px; margin-bottom: 26px;'>",
	browser: null,
	safariTimer: null,
	safariChecked: false,

	/* --------------- command mappers ----------------------- */

	sendCommand: function(command, params){
		if(typeof(params) == 'function')
			params = params.toString();

		if (browserExt.v2mode()) {
			awardwallet.processCommand(command, params);
			return;
		}

		document.getElementById('extCommand').value = command;
		document.getElementById('extParams').value = JSON.stringify(params);
		document.getElementById('extButton').click(params);
	},

	receiveCommand: function(){
		var command = document.getElementById('extCommand').value;
        var params = JSON.parse(document.getElementById('extParams').value);
//		browserExt.log("received command: " + command + ', params: ' + document.getElementById('extParams').value);
		var func = browserExt[command];
		if(typeof(func) != 'undefined')
			setTimeout(function(){
				func(params);
			}, 10);
		else
			browserExt.log("unknown command");
	},

	/* ---------------- commands from extension --------------- */

	complete: function(params){
		if(browserExt.onComplete)
			browserExt.onComplete(params);
	},

	error: function(params){
		if(browserExt.onError)
			browserExt.onError(params);
	},

	providerReady: function(){
		if(browserExt.onProviderReady)
			browserExt.onProviderReady();
		else
			browserExt.log('onProviderReady handler not set');
	},

	info: function(params){
		if(browserExt.onInfo)
			browserExt.onInfo(params);
	},

	ready: function(params){
		browserExt.log('browserExt ready, v' +  params.version);
		if(browserExt.installed)
			return;

		// refs #7043, duplicate safari extensions, discard old one
		if(browserExt.browser[0] == 'Safari'){
			// we will check for duplicate versions once a week
			var d = new Date();
			d = d.getTime() / 1000;
			var dupChecked = localStorage.getItem('safariDupChecked1');
			dupChecked = dupChecked && (dupChecked > (d - 3600));
			var newVerInstalled = localStorage.getItem('safariNewVerInstalled');
			newVerInstalled = newVerInstalled && (newVerInstalled > (d - 3600));
			browserExt.log('new ver: '  + newVerInstalled + ', ready version: ' + params.version);
			if(newVerInstalled && params.version < '1.11'){
				browserExt.log('ignoring old version');
				return;
			}
			if(!browserExt.safariChecked && !dupChecked && params.version < '1.11' && !browserExt.installed){
				// wait a second to receive ready event from new version
				browserExt.log('safari: waiting for new version');
				browserExt.safariTimer = setTimeout(function(){ browserExt.safariChecked = true; browserExt.ready(params); }, 1000);
				localStorage.setItem('safariDupChecked1', d);
				return;
			}
			if(browserExt.safariTimer){
				browserExt.log('cancelling timer');
				clearTimeout(browserExt.safariTimer);
				browserExt.safariTimer = null;
			}
			if(params.version >='1.11'){
				try{
					localStorage.setItem('safariNewVerInstalled', d);
				}catch(exception){
					browserExt.log('is private browsing enabled?');
				}
			}
		}

		browserExt.installed = true;
		browserExt.extensionVersion = params.version;
		clearTimeout(browserExt.installTimer);
		if(browserExt.requiredValidExtension && !browserExt.available()){
			browserExt.installExtension();
		}
		else{
			browserExt.reportExtensionVersion();
			for(var key in browserExt.onReady){
				var handler = browserExt.onReady[key];
				handler();
			}
		}
		browserExt.available(); // call to set cookie
	},

	reportExtensionVersion: function()
	{
		var reportedVersion = localStorage.getItem("extension_version");
		var reportedDate = localStorage.getItem("extension_date");
		var d = new Date();
  		date = d.getDate();
		if (browserExt.extensionVersion != reportedVersion || date != reportedDate) {
			$.ajax({
				url: Routing.generate("aw_extension_version_report", {"v": browserExt.extensionVersion}),
				method: "POST",
				success: function(){
					console.log('saved ext version', date, browserExt.extensionVersion);
					localStorage.setItem("extension_date", date);
					localStorage.setItem("extension_version", browserExt.extensionVersion);
				}
			})
		}
	},

	saveCheckedAccount: function(params, onComplete, parseItineraries, providerCode){
		var url = "/account/receive-from-browser?ParseIts=" + parseItineraries;
		if(providerCode)
			url += '&providerCode=' + providerCode;
		$.ajax({
			url: url,
			type: 'POST',
			data: params,
			success: function(response){
				if((response.Status != 'OK')){
					params.errorMessage = response.Status;
				}
				else
					params.properties.Balance = response.Balance;
				onComplete(params);
			},
			error: ajaxError
		});
	},

	v2mode: function() {
		return parseFloat(browserExt.extensionVersion) > 1.999;
	},

	updateExtension: function(onComplete){
		var d = new Date();
		if(browserExt.v2mode()) {
			onComplete();
			return;
		}
		$.ajax({
	  			url: "/extension/main.js?t=" + d.getTime(),
	  			dataType: 'text',
	  			success: function(data){
	  				var s = data.indexOf('// begin update') + '// begin update'.length;
	  				var e = data.indexOf('// end update');
	  				data = 'var ' + trim(data.substr(s, e - s));
	  				data = data.replace(/var \/\*update\*\//mig, 'var update_');
	  				data = 'function(){ awardwallet.update = ' + JSON.stringify(data) + '; ' + data + 'updater = ' + (browserExt.updater + '')
	  				+ '; updater(awardwallet, update_awardwallet); updater(browserAPI, update_browserAPI); updater(provider, update_provider); awardwallet.clear(); }';
	  				if(parseFloat(browserExt.extensionVersion) >= 1.20){
	  					browserExt.sendCommand('execAwardWallet', data);
	  				}
					onComplete();
				},
				error: ajaxError
		});
	},

	autoLoginAccountById: function (accountId, onComplete, onError, onRequirePassword) {
		browserExt.updateExtension(function() {
            var url = "/account/browser-check/" + accountId;
            browserExt.xhr = $.ajax({
                url: url,
                dataType: 'json',
                type: 'POST',
                data: {Version: browserExt.extensionVersion},
                success: function (data) {
                    if (typeof(data.requirePassword) == 'boolean' && data.requirePassword && typeof(onRequirePassword) != 'undefined') {
                        onRequirePassword(data);
                        return false;
                    } else if (typeof data.error == 'string') {
                        showMessagePopup('error', 'Error', data.error);
                        onError(data.error);
                        return false;
                    }
                    if (typeof(data.login) != 'undefined' && typeof(data.password) != 'undefined')
                        browserExt.setAccountInfo(data);
                    browserExt.autologin(
                            accountId,
                            function () {
                                browserExt.submitStat(data.providerCode, true, "undefined", accountId);
                                onComplete();
                            },
                            function (error) {
                                browserExt.submitStat(data.providerCode, false, error, accountId);
                                onError(error);
                            }
                    );
                },
                error: ajaxError
            });
        });
	},

	checkAccount: function(accountId, onComplete, parseItineraries, providerCode, onServerError){
		browserExt.updateExtension(function(){
			var url = "/account/browser-check/" + accountId + '?';
			if(providerCode)
				url += '&providerCode=' + providerCode;
			$.ajax({
				url: url,
				dataType: 'json',
				type: 'POST',
				data: {Version: browserExt.extensionVersion},
				success: function(data){
					if(typeof data.error == 'string'){
						showMessagePopup('error', 'Error', data.error);
						if (typeof onServerError != 'undefined') onServerError(data.error);
						return false;
					}

					if(data.receiveFromBrowser) {
						browserExt.onComplete = function (params) {
							params.oldBalance = data.balance;
							browserExt.saveCheckedAccount(params, onComplete, parseItineraries, providerCode);
						}
					}
					else
						browserExt.onComplete = onComplete;
					if(typeof(data.login) != 'undefined' && typeof(data.password) != 'undefined'){
						if(typeof(parseItineraries) == 'boolean'){
							data.parseItineraries = parseItineraries;
						}
						browserExt.setAccountInfo(data)
					}
					browserExt.onError = function(error) {
						var params;
						if (typeof (error) == "object")
							params = { accountId: accountId, errorMessage: error[0], errorCode: error[1] };
						else
							params = { accountId: accountId, errorMessage: error };
						if (data.receiveFromBrowser) {
							params.oldBalance = data.balance;
							browserExt.saveCheckedAccount(params, onComplete, parseItineraries, providerCode);
						}
						else {
							browserExt.accounts[accountId].errorMessage = error;
							onComplete(params);
						}
					};
					browserExt.sendCommand('check', {accountId: accountId});
				},
				error: ajaxError
			});
		});
	},

	retrieveByConfNo: function(providerCode, fields, onComplete, onError, version, selectedUserId, familyMemberId, clientId){
		browserExt.updateExtension(function(){
			fields.accountId = 0;
			fields.providerCode = providerCode;
			fields.mode = 'confirmation';
			console.log(fields);
			browserExt.setAccountInfo(fields);
			browserExt.onComplete = function(result){

				if(selectedUserId){
					result.selectedUserId = selectedUserId;
					result.familyMemberId = familyMemberId;
					result.clientId = clientId;
				}

				$.ajax({
					url: "/account/receive-by-confirmation",
					dataType: 'json',
					type: 'POST',
					data: result,
					success: function(data){
						if(data.answer === 'ok')
							onComplete(data.redirectUrl);
						else {
							if(version === 'old')
								browserExt.onError(data.message);
							if(version === 'new')
								onError(data.message);
						}
					},
					error: ajaxError
				});
			};
			browserExt.onError = function(result){
				if(version === 'old') {
					showMessagePopup('info', 'Error', result);
					onError();
				}
				if(version === 'new')
					onError(result);
			};
			browserExt.sendCommand('check', {accountId: fields.accountId})
		});
	},

	updater: function(target, source){
		for(key in source){
			if(source.hasOwnProperty(key) && typeof(source[key]) == 'function'){
				target[key] = source[key];
			}
		}
	},

	accountsChanged: function(accounts){
		browserExt.accounts = accounts;
		var totals = [];
		totals.all = 0;
		for(var accountId in accounts){
			if(!accounts.hasOwnProperty(accountId))
				continue;
			var account = accounts[accountId];
			var row = $('#row' + accountId);
			if(row.length > 0 && row.attr('data-checkinbrowser') == '1'
			&& row.find('a.multipleAccounts').length == 0 /* not business multiple accounts line */){
				if(typeof(account.login) != 'undefined')
					row.find('span.login').html(account.login);
				if(typeof(account.properties) != 'undefined'){
					if(typeof(account.properties.Balance) != 'undefined'){
						browserExt.setRowBalance(account.properties.Balance, row);
						var balanceVal = browserExt.numericBalance(account);
						var info = getAccountInfoFromLink(row.find('a.checkLink'));
						browserExt.countTotal(balanceVal, info.kind, totals);
						//balance progress bar
						var goalTitle = row.find('td.balance .goal').attr('title');
						if(goalTitle){
							var matches = goalTitle.match(/goal\s(\d+)/);
							var goal = matches[1];							
							if(goal != null){
								var goalVal = Math.round(goal);
								var val = (goalVal < balanceVal)?goalVal:balanceVal;
								var progress = val/goal*100;
								row.find('td.balance .goal').attr('title','You goal '+goal+', progress: '+progress+'%');
								row.find('td.balance .goal .progress').css('width',progress+'%');
							}
						}
						//status
						if(account.properties.Status != null){
							row.find('td.Status').html("<div class='cont'>"+account.properties.Status+"</div>");
							//alert(row.find('td.Status').attr('eliteLevels'));
							var eliteLevels = $.parseJSON(row.find('td.Status').attr('eliteLevels'));
							var eliteLevelsCount = row.find('td.Status').attr('eliteLevelsCount');
							var rank = 0;							
							for(var key in eliteLevels){
								var level = eliteLevels[key];
								if(level.ValueText.toLowerCase() == account.properties.Status.toLowerCase()){
									rank = level.Rank;
									row.find('td.Status div.cont').html(level.Name);
									if(trim(level.AllianceName) != ''){
										var cell = row.find('td.program');
										var bg = 'url(/images/alliances/' + cell.attr('data-alliance')
											+ level.AllianceName.toLowerCase() + '.png)';
										cell.css('background-image', bg);
									}
								}
							}						
							var elitizm = ((eliteLevelsCount == 0) ? 0 : (rank/eliteLevelsCount).toFixed(2))*100;							
							row.find('td.Status').append("<div class='goal' title='Progress to the highest possible elite level on this program: "
							+elitizm+"%'><div class='progress' style='width: "+elitizm+"%'></div></div>");
						}
					}
					if(typeof(account.properties.ExpirationDate) != 'undefined'){
						var date = parseDate(account.properties.ExpirationDate);
						if(date == null)
							date = account.properties.ExpirationDate;
						else
							date = formatDate(date, dateFormat);
						row.find('td.expiration').html(date);
					}
				}
			}
		}

		browserExt.setBusinessBalances(accounts, totals);

		if(totals.all > 0){
			browserExt.correctTotalBalance(totals);
			browserExt.totals = totals;
		}
	},

	setRowBalance: function(balance, row){
		row.removeClass('error');
		row.find('div.balance').html(balance);
		row.find('div.redBar').hide();
		row.find('td.balance').removeClass('error');
	},

	countTotal: function(balanceVal, kind, totals){
		totals.all += balanceVal;
		if(typeof(totals[kind]) == 'undefined')
			totals[kind] = 0;
		totals[kind] += balanceVal;
	},

	numericBalance: function(account){
		return Math.round(account.properties.Balance.replace(/,/g, ''));
	},

	getBalances: function(){
		var data = {};
		if(browserExt.accounts != null)
			for(var accountId in browserExt.accounts){
				var account = browserExt.accounts[accountId];
				if(typeof(account.properties) != 'undefined' && typeof(account.properties.Balance) != 'undefined')
					data[accountId]  = browserExt.numericBalance(account);
			}
		return data;
	},

	setBusinessBalances: function(accounts, totals){
		$('#tblAccounts a.checkAllLink').each(function(index, element){
			var el = $(element);
			var ids = el.attr('data-ids').split(',');
			var allBalance = 0;
			var balanceSet = false;
			for(var key in ids){
				var accountId = ids[key];
				if(typeof(accounts[accountId]) != 'undefined' && typeof(accounts[accountId].properties) != 'undefined'
				&& typeof(accounts[accountId].properties.Balance) != 'undefined'){
					allBalance += browserExt.numericBalance(accounts[accountId]);
					balanceSet = true;
				}
			}
			if(balanceSet){
				var row = el.closest('tr');
				browserExt.setRowBalance(formatNumberBy3(allBalance, ".", thousandsSeparator), row);
				browserExt.countTotal(allBalance, el.attr('data-providerKind'), totals);
			}
		});
	},

	correctTotalBalance: function(totals){
		browserExt.log("correcting total balance: " + totals.all);
		var row = $('#tblAccounts tr.totals');
		browserExt.addTotal(row.find('td.balance'), totals.all);
		for(var kind in totals){
			if(kind != 'all')
				browserExt.addTotal(row.find('tr.kind' + kind + ' span.balance'), totals[kind]);
		}
	},

	addTotal: function(balanceNode, addition){
		if(balanceNode.length > 0){
			var balance = trim(balanceNode.text());
			balance = balance.replace(/[\,\ \.]+/g, '');
			balance = Math.round(balance);
			balance += addition;
			balanceNode.html(formatNumberBy3(balance, ".", thousandsSeparator));
		}
	},
	
	fillPopupProperties: function(accountId){
		if(!browserExt.installed){ //info in popup if extension is not installed
			$('#tabs_'+accountId+'_content').html('In order to see detailed information on this program ' +
			'you need to <a href="/extension/">install the AwardWallet browser extension</a>.'); /*checked*/
			return;
		}
		if(!browserExt.accounts){ //info in popup if accounts array is null
			$('#tabs_'+accountId+'_content').html('You do not have any information gathered for this program yet. ' +
			'Please click the update button to retrieve the data.'); /*checked*/
			return;
		}
		if(!browserExt.accounts[accountId]){ //info in popup if data of current accountId is null
			$('#tabs_'+accountId+'_content').html('You do not have any information gathered for this program yet. ' +
			'Please click the update button to retrieve the data.'); /*checked*/
		} else {
			var data = browserExt.accounts[accountId];
			var props = {};
			if(data.properties)
				props = data.properties;
			
			/*==== main Properties ====*/
			var mainProps = $('#tabs_'+accountId+'_details').find('table.mainProps');
			mainProps.find('td:contains(Last Change:)').parent('tr').remove();
			mainProps.find('td:contains(Last retrieved on:)').parent('tr').remove();
			//balance
			if(props.Balance != null && props.Balance != '')
				mainProps.find('td:contains(Balance:) ~ td').html(props.Balance);
			else	
				mainProps.find('td:contains(Balance:)').parent('tr').remove();
			//Expiration
			if(props.ExpirationDate != null && props.ExpirationDate != '')
				mainProps.find('td:contains(Expiration:) ~ td').html(props.ExpirationDate);
			else	
				mainProps.find('td:contains(Expiration:)').parent('tr').remove();
			//Status
			if(props.Status != null && props.Status != '')
				mainProps.find('td:contains(Status:) ~ td').html(props.Status);
			else	
				mainProps.find('td:contains(Status:)').parent('tr').remove();
			//Account
			if(props.Number != null && props.Number != '')
				mainProps.find('td:contains(Account:) ~ td').html(props.Number);
			else	
				mainProps.find('td:contains(Account:)').parent('tr').remove();
				
			/*==== extProps ====*/
			var extProps = $('#tabs_'+accountId+'_details').find('table.extProps tbody tr');
			for(i = 0; i < extProps.length; i++){
				if($(extProps[i]).attr('prop') != null){					
					var prop = $(extProps[i]).attr('prop');
					if(props[prop] != null && props[prop] != '')
						$('#tabs_'+accountId+'_details').find('tr[prop='+prop+'] td.value').html(props[prop]);
					else	
						$('#tabs_'+accountId+'_details').find('tr[prop='+prop+']').remove();						
				}
			}
			
			/*==== Errors ====*/
			if(data.errorMessage != null && data.errorMessage != '')
				$('#tabs_'+accountId+'_details').find('div.accountErrorMessage').find('.boxRed .center').html(
					browserExt.formatAccountError(data.errorMessage, data.accountId));
			else{
				$('#extRow'+accountId+' table tr td:eq(1) div.state').removeClass('errorState').addClass('successState').attr('title','Success');
				$('#tabs_'+accountId+'_details').find('div.accountErrorMessage').remove();
			}
				
		}
		
	},

	/* ----------------------- commands from page --------------------------- */

	available: function(){
		return browserExt.installed && browserExt.supportedBrowser() && browserExt.validVersion(browserExt.extensionVersion, browserExt.requiredVersion());
	},

	validVersion: function(actualVersionStr, requiredVersionStr) {
		var actualVersionArr = actualVersionStr.split(".");
		var requiredVersionArr = requiredVersionStr.split(".");

		while(actualVersionArr.length < requiredVersionArr.length) {
			actualVersionArr.push("0");
		}

		while(requiredVersionArr.length < actualVersionArr.length) {
			requiredVersionArr.push("0");
		}

		while(requiredVersionArr.length > 0) {
			var required = Math.round(requiredVersionArr.shift());
			var actual = Math.round(actualVersionArr.shift());
			if (actual < required) {
				return false;
			}
			if (actual > required) {
				return true;
			}
		}

		return true;
	},

	requiredVersion: function(){
		var userId = parseInt(document.getElementById('extUserId').value);
		if(browserExt.browser == null)
			browserExt.supportedBrowser();
		if(browserExt.browser[0] == 'MSIE')
			return '1.26';
		if(browserExt.browser[0] == 'Edge')
			return '2.2';
		if(browserExt.browser[0] === 'Firefox') {
			if(browserExt.browser[1] >= 57)
				return '2.0';
			else
				return '1.34';
        }
		if(browserExt.browser[0] == 'Chrome') {
			if(browserExt.browser[1] >= 69)
            	return '2.0';
			else
				return '1.36';
        }
		if(browserExt.browser[0] == 'Safari')
			return '1.35';
		return browserExt.expectedVersion;
	},

	autologin: function(accountId, onComplete, onError, continueToStep){
		browserExt.onComplete = onComplete;
		browserExt.onError = onError;
		if(continueToStep)
			browserExt.sendCommand("execAwardWallet", "function(){ " +
				"if(awardwallet.state == 'idle'){" +
					"awardwallet.setError('code 1001');" +
					"return false;" +
				"}" +
				"awardwallet.onlyAutologin = true; " +
				"awardwallet.step = '"+continueToStep+"'; " +
				"awardwallet.startCheck(true);}");
		else
			browserExt.sendCommand("check", { accountId: accountId, autologin: true });
		return false;
	},		

	requireValidExtension: function(){
		if(browserExt.installed)
			return;
		browserExt.installTimer = setTimeout(browserExt.installExtension, 3000);
		browserExt.requiredValidExtension = true;
	},

	installExtension: function(){
		//document.location.href = '/extension/?BackTo=' + encodeURIComponent(document.location.href);
		document.location.href = '/extension-install?BackTo=' + encodeURIComponent(document.location.href);
	},

	pushOnReady: function(handler){
		if(browserExt.available())
			handler();
		else {
			if(browserExt.installed)
				browserExt.installExtension();
			else
				browserExt.onReady.push(handler);
			}
	},

	changeAccountId: function(fromId, toId){
		browserExt.sendCommand("changeAccountId", {fromId: fromId, toId: toId});
	},

	getAccountInfo: function(accountId){
		if(typeof(browserExt.accounts[accountId]) != 'undefined')
			return browserExt.accounts[accountId];
		else
			return null;
	},

	// expects: {accountId: 123, providerCode: 'aa'},
	// or {accountId: 123, providerCode: 'aa', login: '22233', password: '333222'}
	setAccountInfo: function(data){
		browserExt.sendCommand('setInfo', data);
		return true;
	},

	deleteAccount: function(accountId){
		browserExt.sendCommand('deleteAccount', {accountId: accountId});
	},

	revealPassword: function(accountId){
		return browserExt.sendCommand('revealPassword', {accountId: accountId});
	},

	cancel: function(){
		if(browserExt.xhr && browserExt.xhr.readyState < 4)
			browserExt.xhr.abort();
		browserExt.sendCommand('cancel', null);
	},

	closeThisTab: function(){
		browserExt.sendCommand('closeThisTab', null);
	},

	clear: function(){
		browserExt.sendCommand('clear', null);
	},

	getBalancesOfProvider: function(providerCode){
		if(browserExt.accounts == null)
			return {};
		var result = {};
		for(var accountId in browserExt.accounts){
			var account = browserExt.accounts[accountId];
			if(typeof(account.properties) != 'undefined' && typeof(account.properties.Balance) != 'undefined')
				result[accountId] = browserExt.numericBalance(account);
		}
		return result;
	},

	cancelCheck: function(){
		console.log('cancel');
		cancelPopup();
		browserExt.cancel();
		browserExt.sendCommand('closeProviderTab', null);
	},

	autoLoginFallback: function(info, link){
		if(link)
			return true;
		else{
			var url = 'http://' + document.location.host + '/account/' + info.redirectUrl;
			browserExt.sendCommand(
				'execAwardWallet',
				"function(){ awardwallet.openTab('" + url + "', true) }"
			);
			return false;
		}
	},

	autoLoginClick: function(accountId, link){
		// IE does not reliably initialize extension in new window opened with target=blank,
		// we will open new window through js for it
		if(!browserExt.supportedBrowser() || browserExt.browser[0] != 'MSIE')
			return true;
		var w = window.open("about:blank", "_blank");
		w = w.open(link.href, "_self");
		w.focus();
		return false;
	},

    autoRegistration: function(providerId, accountId,  properties, redirectId, extension) {
        $.ajax({
            url: "/extension/prepareRegistration.php",
            type: 'POST',
            data: { providerId: providerId, properties: properties, redirectId: redirectId },
            success: function(response){
                if (response.partnerAccount.accountExist == 0) {
                    // registration via extension
                    if (extension) {
                        eval(response.partnerAccount.plugin);
                        // TODO: if(!browserExt.available()) - need to test without extension
                        if(!browserExt.available()){
                            document.location.hash = 'autologin' + accountId;
                            browserExt.installExtension();
                            return false;
                        }
						browserExt.updateExtension(function () {
                            browserExt.setAccountInfo(response.partnerAccount);
                            showMessagePopup(
                                    'info',
                                    'Registration in progress',
                                    browserExt.progressImage + 'Please wait while we are registering you for '
                                    + response.partnerAccount.providerName + ', this takes about 1 - 2 minutes',
                                    false,
                                    'Stop'
                            );
                            browserExt.autologin(
                                    0,
                                    function () {
                                        browserExt.onInfo = null;
                                        $.ajax({
                                            url: "/extension/addPartnerAccount.php",
                                            type: 'POST',
                                            data: response.partnerAccount,
                                            success: function (error) {
                                                if (error == 'OK') {
                                                    browserExt.sendCommand('closeProviderTab', null);
                                                    browserExt.autoRegComplete(response);
                                                    reloadPageContent();
                                                }
                                            },
                                            error: ajaxError
                                        });
                                    },
                                    function (error) {
                                        var message = browserExt.errorPrefix;
                                        if (error != 'code 1001')
                                            browserExt.onInfo = null;
                                        if (error.indexOf('code ') != 0)
                                            showMessagePopup('error', 'Registration failed', message + error);
                                        else
                                            showMessagePopup('error', 'Registration failed', message + ' Please try again.');
                                    }
                            );// browserExt.autologin(
                        });
                    }// if (extension)
                    // registration via server
                    else {
                        showMessagePopup(
                            'info',
                            'Registration in progress',
                            browserExt.progressImage + 'Please wait while we are registering you for '
                                + response.partnerAccount.providerName + ', this takes about 1 - 2 minutes',
                            false,
                            'Stop'
                        );
                        $.ajax({
                            url: "/extension/serverRegistration.php",
                            type: 'POST',
                            data: { properties: properties, providerCode: response.partnerAccount.providerCode },
                            success: function (error) {
                                // Registration failed
                                if (error.success == false) {
                                    var message = browserExt.errorPrefix;
                                    if (error.errorMessage != '')
                                        showMessagePopup('error', 'Registration failed', message + error.errorMessage);
                                    else
                                        showMessagePopup('error', 'Registration failed', message + ' Please try again.');
                                }
                                // Registration successful
                                if (error.success == true) {
                                    $.ajax({
                                        url: "/extension/addPartnerAccount.php",
                                        type: 'POST',
                                        data: response.partnerAccount,
                                        success: function (error) {
                                            if (error == 'OK') {
                                                browserExt.autoRegComplete(response);
                                                reloadPageContent();
                                            }
                                        },
                                        error: ajaxError
                                    });
                                }
                            },
                            error: ajaxError
                        });
                    }
                }// if (response.partnerAccount.accountExist == 0)
                else
                    showMessagePopup(
                        'info',
                        'Account has already been added',
                        // TODO: a/an article
                        'You already have an '+response.partnerAccount.providerName+' account added to your AwardWallet profile'
                    );
            },
            error: ajaxError
        });
    },

	autoRegMessage: function(response){
		return "We've signed you up for " + response.partnerAccount.providerName + " with the following credentials:<br/><br/>"
			+ 'Login: ' + response.partnerAccount.login +
			"<br/>Password: " + response.partnerAccount.password +
			"<br/><br/>Don't worry this account is now added to AwardWallet, " +
			"you can always retrieve the password by clicking \"Reveal Password\" under that account. " +
			"<br/><br/>Please click the link in your email to confirm your registration and click next. " +
			"(please note that verification email could be in your spam folder)";
	},

	autoRegComplete: function (response) {
		showMessagePopup(
			'success',
			"Registration completed",
			browserExt.autoRegMessage(response),
			false,
			"Next"
		);
		document.getElementById('messageOKButton').onclick = function(){
			window.location = '/account/list.php?UserAgentID=All';
		}
	},

	showRegisterProgress: function(response){
		showMessagePopup(
			'info',
			'Registration in progress',
			browserExt.progressImage + 'Please wait, we are registering you on ' + response.partnerAccount.providerName
			+ ', this takes about 1 - 2 minutes',
			false,
			'Stop'
		);
		document.getElementById('messageOKButton').onclick = browserExt.cancelCheck;
		$('#messageCancelButton').hide();
	},

	getRegCompleteMessage: function(response){
		return "We've signed you up with the following credentials:<br/><br/>"
					+ 'Login: ' + response.partnerAccount.login +
					"<br/>Password: " + response.partnerAccount.password +
					"<br/><br/>Don't worry this account is now added to AwardWallet, " +
					"you can always retrieve the password by clicking \"Reveal Password\" under that account. " +
					"<br/><br/>Please click the link in your email to confirm your registration and click next. " +
					"(please note that verification email could be in your spam folder)" +
					"<br/><br/>You are on your way to save " + response.partnerAccount.price
					+ " on " + response.partnerAccount.nextAccount.providerName + " purchases.";
	},

	registrationComplete: function(partnerId, accountId, response){
		showMessagePopup(
			'success',
			"Registration completed",
			browserExt.getRegCompleteMessage(response),
			false,
			"Next"
		);
		document.getElementById('messageOKButton').onclick = function(){
			browserExt.autoLoginThroughPartner(partnerId, accountId);
		}
	},

	submitStat: function(code, success, error, accountId) {
		success = (success) ? 1 : 0;
		var errorCode = 2;
		if (typeof(error) == "undefined" || success == 1) {
			errorCode = 1;
			error = "";
		}// if (typeof(error) == "undefined" || success == 1)
		else {
			if (typeof (error) == "object") {
				errorCode = error[1];
				error = error[0];
			}
		}
		$.post(
			"/extension/extensionStats.php",	{
				providerCode: code,
				success: success,
				errorMessage: error,
				errorCode: errorCode,
				accountId: accountId
			});
	},

	/* ----------------------- utility methods ---------------------- */

	funcWithParamsToString: function(func) {
		var args = [].slice.call(arguments, 1);
		var str = 'function() { return (' + func.toString() + ')(';
		for (var i = 0, l = args.length; i < l; i++) {
			var arg = args[i];
			if (/object|string/.test(typeof arg)) {
				str += 'JSON.parse(' + JSON.stringify(JSON.stringify(arg)) + '),';
			} else {
				str += arg + ',';
			}
		}
		str = str.replace(/,$/, '); }');
		return str;
	},

	// IE does not have console until dev tools opened
	log: function(s){
		if(typeof(console) != 'undefined')
			console.log(s);
	},

	formatAccountError: function(errorMessage, accountId){
		return errorMessage.replace('enter your password', '<a href="/account/edit.php?ID=' + accountId + '">enter your password</a>');
	},

	detectBrowser: function(){
		browserExt.browser = browserDetectNav(true);
	},

	supportedBrowser: function(){
		var data = browserExt.browser;
		var result = ((data[0] === 'Edge' && data[1] >= 14 && data[2] >= 15063) || data[0] === 'Safari' || data[0] === 'MSIE' || data[0] === 'Firefox' || data[0] === 'Chrome' || (data[0] === 'Opera' && typeof(data[1]) !== 'undefined' && data[1] >= 15));
		setCookie('SB', result, null, '/');
		return result;
	},

	showInfoBrowser: function()
	{
		var data = browserExt.browser;
		return data[0] === 'Edge' && data[2] < 15063;
	},

	saveLog: function(data){
		$.ajax({
			url: '/account/receive-browser-log',
			type: 'POST',
			data: {accountId: data.accountId, log: JSON.stringify(data.log)}
		});
	}

};

browserExt.detectBrowser();
if(browserExt.browser[0] === 'Edge'){
	console.log('detected edge browser');
	require(['extension-boot'], function(){});
}
else{
	var getExtensionVersion = function() {
		var version_data = JSON.parse(document.getElementById('extParams').value);

		console.log('detected extension, ', version_data);

		if (typeof version_data === 'object') {
			browserExt.extensionVersion = version_data.version;
			browserExt.extensionId = version_data.id;
		} else {
			browserExt.extensionVersion = String(version_data);
		}

		browserExt.portCommSupported = browserExt.browser[0] === 'Chrome';

		if (browserExt.browser[0] === 'Opera' && browserExt.v2mode()) {
			browserExt.portCommSupported = true;
		}

		if (browserExt.portCommSupported) {
			console.log('extension: using portComm mode');
		}
	};

	var interval;

	if (browserExt.browser[0] === 'Safari') {
		document.addEventListener('content_scripts_ready', function () {
			const version = browserExt.extensionVersion;

			getExtensionVersion();
			clearInterval(interval);
			require(['extension-boot'], function () {
				if (version && version !== browserExt.extensionVersion) {
					console.log('browserExt, detected old version: ' + version + ', re-init new version: ' + browserExt.extensionVersion);
					browserExt.ready({
						version: browserExt.extensionVersion
					});
				}
			});

		});
		document.dispatchEvent(new CustomEvent('init_modern_extension', {}));
	}

	// detect already loaded edge extension
	var attempt = 0;

	interval = setInterval(function(){
		var command = document.getElementById('extCommand');

		if (command && command.value === "content_scripts_ready") {
			if (browserExt.extensionVersion === null) {
				getExtensionVersion();
			}
			clearInterval(interval);
			require(['extension-boot'], function(){});
		}

		attempt++;

		if (attempt > 30) {
			clearInterval(interval);
		}

	}, 100);
}
