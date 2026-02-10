function makeSize(el, type) {
	switch (type) {
		case '0':
        case '7':
			var caption = el.children[0];
            if (el.offsetWidth - caption.offsetWidth < 3 || el.offsetHeight - caption.offsetHeight < 2) {
                caption.style.display = 'none';
            }
		break;
		case '1':
		case '2':
		case '3':
        case '4':
        case '5':
            el.style.background = "url('/images/levels/reached" + type + ".png') no-repeat";
            el.style.backgroundSize = "100% 100%";
			var elName = el.children[0];
			if (elName.offsetLeft < 10) {
				elName.style.top = (el.offsetHeight / 2 - elName.offsetHeight / 2) + 'px';
				elName.style.right = '5px';
			}
			else {
				elName.style.top = (el.offsetTop + el.offsetHeight / 2 - elName.offsetHeight / 2) + 'px';
				elName.style.left = (el.offsetLeft + el.offsetWidth - elName.offsetWidth - 5) + 'px';
			}
			if (type > 2)
				elName.style.color = "white";
        break;
	}
}

function tuneEliteTab(tabID, subAccountId) {
    var tabID = tabID + '-' + subAccountId;
    var tableEl = $('table.' + tabID + '_elite_chart');
    if(!tableEl.data('rendered')){
    $(function(){
        $("img.eliteLevelInfo").each(function(){
            $(this).tipTip({fadeOut:400, defaultPosition: 'top', content: '<div class="eliteCommentText">' + $('div#' + $(this).attr('id')).html() + '</div>'})
        });
    });
    var tableWidth = tableEl.innerWidth();
    var levelsCount = tableEl.data('levelsCount');
    $('table.' + tabID + '_elite_chart td.' + tabID + '_eliteCell').each(
        function(e){
            var gap = 1;
            if($(this).hasClass('hasDelimiter'))
                gap += 9 + 2; // 9
            if($(this).hasClass('hasGroup'))
                gap += 10 + 1; // 10 - img width, 2 - grouping borders

            $(this).width(Math.floor($(this).data('initialLength') / levelsCount * tableWidth) - gap);
        }
    );

	var types = ["progress", "reached_1", "reached_2", "reached_3", "reached_4", "reached_5", "delimiter", "empty", "group"];
	for (var i in types) {
		var els = document.getElementsByClassName(tabID + "_" + types[i]);
		var j = 0;
		while (els[j] != undefined) {
			makeSize(els[j], i);
			j++;
		}
	}
    	// update height popup
	if (typeof(activePopup) != "undefined" && activePopup != null) {
		var popup = document.getElementById('extRow'+activePopup);
		if (typeof(top) != "undefined" && top != null && popup != null)
			updatePopupSize(popup, top);
	}
    tableEl.data('rendered', 1);
    }
}

function showStatus(mode, errorCode, errorMessage){
	var s;
	switch(errorCode){
		case 0:
			s = "Account not checked";
			break;
		case 1:
			s = "Account checked";
			break;
		case 2:
			s = "Invalid password";
			break;
		case 3:
			s = "Account locked out";
			break;
		case 4:
			s = "Provider error";
			break;
		case 5:
			s = "Provider disabled";
			break;
		case 6:
			s = "Engine error";
			break;
		case 7:
			s = "Missing password";
			break;
	}
	if(errorMessage != "")
		s = s + ': ' + errorMessage;
	alert(s);
}

function showExpirationWarning(message, style){
	showMessagePopup(style, 'Expiration warning', message);
}

function showDetectedCards(message, style){
    showMessagePopup(style, 'Detected cards', message);
}

function showRenewNote(accountId){
	$.ajax(
		{
			url: "/account/renewNote.php?ID="+accountId,
			success: function(data){
				showMessagePopup('info', 'Renew note', data);
			},
			error: ajaxError
		}
	);
}

function showMessagePopup(style, header, message, noButtons, okbuttonname, onShow){
    document.getElementById('messageHeader').innerHTML = header;
    if (style == 'detectedCards') {
        document.getElementById('messagePopup').style.width = '670px';
    }
    else
        document.getElementById('messagePopup').style.width = '540px';
    if (style == 'scroll') {
        document.getElementById('messageText').style.maxHeight = '400px';
        document.getElementById('messageText').style.overflowY = 'auto';
    }
    else {
        document.getElementById('messageText').style.height = '';
        document.getElementById('messageText').style.overflow = '';
    }
	if(style == 'cashback'){
		$('#messagePopupClose').css('display', 'none');
	}
    document.getElementById('messageText').innerHTML = message;
    if (noButtons)
        $('#messageButtons').css('display', 'none');
    else
        $('#messageButtons').css('display', 'block');
    if (okbuttonname) {
        $('#messageOKButton input').attr('value', okbuttonname.toString());  $('button#messageOKButton').html(okbuttonname.toString());
    }
    else {
	    $('#messageOKButton input').attr('value', "OK");    $('button#messageOKButton').html("OK");
    }
    document.getElementById('messageOKButton').onclick = new Function("cancelPopup()");
    document.getElementById('messageCancelButton').style.display = "none";
    document.getElementById('messageCancelButton').onclick = new Function("cancelPopup()");
    $('#messageCancelButton input').css('width', '120px');   $('button#messageCancelButton').css('width', '120px');
    $('#messageCancelButton input').attr('value', "Cancel"); $('button#messageCancelButton').html("Cancel");
    var fader = document.getElementById('fader');
    fader.onclick = function () {
        cancelPopup()
    };
    showPopupWindow(document.getElementById('messagePopup'), true, onShow);
}

function showBecomeAFanPopup(){
	document.getElementById('messageHeader').innerHTML = 'Become our fan on Facebook';
	document.getElementById('messageText').style.height = '';
	document.getElementById('messageText').style.overflow = '';
	document.getElementById('messageText').innerHTML = '<a href="http://www.facebook.com/apps/application.php?id=75330755697" target="_blank" onclick="cancelPopup(); return true;"><img src="/images/becomeAFan.png" width="658" height="250" border="0"></a><br><br>Please go to Facebook, and click on the "Become a Fan" link'; /*checked by Alexi*/
	var height = $('#messagePopupBody').height() + 30;
	var maxHeight = $('window').height();
	if(height > maxHeight){
		height = maxHeight;
		document.getElementById('messageText').style.height = height - 150 + 'px';
		document.getElementById('messageText').style.overflow = 'auto';
	}
	document.getElementById('messagePopup').style.height = height + 'px';
	document.getElementById('messagePopup').style.width = '700px';
	$('#messageOKButton input').attr('value', "Open Facebook.com");
	document.getElementById('messageOKButton').style.width = '200px';
	document.getElementById('messageOKButton').onclick = new Function("window.open('http://www.facebook.com/apps/application.php?id=75330755697', 'facebook', ''); cancelPopup();");
	// menubar=no,toolbar=no,location=no,directories=no,status=yes,scrollbars=yes,resizable=no,dependent,width=530,height=450,left=50,top=50
	document.getElementById('messageCancelButton').style.display = "none";
	showPopupWindow(document.getElementById('messagePopup'), true);
}

function showQuestionPopup(style, header, message, okCaption, okScript, cancelCaption, cancelScript){
	document.getElementById('messageHeader').innerHTML = header;
	document.getElementById('messageText').innerHTML = message;
	document.getElementById('messagePopup').style.height = $('#messagePopupBody').height() + 30 + 'px';
	$('messageOKButton input').attr('value', okCaption);
	document.getElementById('messageOKButton').onclick = new Function(okScript);
	$('messageCancelButton input').attr('value', cancelCaption);
	document.getElementById('messageCancelButton').onclick = new Function(cancelScript);
	document.getElementById('messageCancelButton').style.display = "";
	showPopupWindow(document.getElementById('messagePopup'), true);
}

var checkCancelled = false;

function setHistoryRows(idx, cycle, accounts){
	var rows = 0;
	if(cycle){
		rows = rows + idx;
		if(rows > 5){
			document.getElementById('checkHistory').style.overflow = 'auto';
			$('#checkHistory').animate({
				scrollTop: '+=20'
			}, {queue: false, duration: 500});
			rows = 5;
		}
		else
			document.getElementById('checkHistory').style.overflow = 'hidden';
	}
	else
		document.getElementById('checkHistory').style.overflow = 'hidden';
	if(rows > 0)
		$('#checkHistory').animate({height: 20*rows}, {queue: false, duration: 500});
	else
		document.getElementById('checkHistory').style.height = (20 * rows) + 'px';
	if(rows == 0)
		document.getElementById('checkHistory').style.display = 'none';
	else
		document.getElementById('checkHistory').style.display = '';
	var popupHeight = 425 + 20 * rows;
	if(rows > 0){
		$('#checkPopup').animate({height: popupHeight}, {queue: false, duration: 500});
		var top = Math.round( getWindowHeight() / 2 - popupHeight / 2 + getScrollTop() );
		$('#checkPopup').animate({top: top}, {queue: false, duration: 500});
//		$('#checkPopupInner').animate({height: popupHeight}, {queue: false, duration: 500});
	}
	else{
		document.getElementById('checkPopup').style.height = popupHeight + 'px';
	}
	if((idx == 0) || !cycle)
		document.getElementById('checkHistoryText').innerHTML = '';
}

var checkRequest = null;
var checkRequestTimer = 0;
var checkRequestRetry = 0;
var checkTimedout = false;

//function checkRequestTimeout(accounts, idx, forTrips){
//	checkRequest.abort();
//	var account = $('a.checkLink:eq('+idx+')');
//	var accountId = account.attr('accountId');
//	var message = '<img src=/lib/images/error.gif> '+document.getElementById('rewardName'+accountId).innerHTML+' - Timed out';
//	cycleAccountChecked(accounts, message, idx, forTrips);
//}
//
function checkAccountId(id){
	$('#extRow'+activePopup).stop(true, true);
	popupHidden();
	$('td#manageCell'+id+' a.checkLink').click();
}

function checkSuccessfull(accounts, forTrips){
	ids = [];
	$.each(accounts, function(index, account){
		ids.push(account.accountId);
	});
	if(forTrips) {
		$('#checkStopButton input').attr('value', 'Please Wait...');
		$('#checkStopButton input').attr('disabled', 'disabled');
		document.location.href = "/trips/accountChecked.php?ID="+encodeURI(ids.join(","))+"&TravelPlanID="+activeTravelPlanId;
	}
}

function resetCheckRequestTimer(){
	if(checkRequestTimer != 0){
		clearTimeout(checkRequestTimer);
		checkRequestTimer = 0;
	}
}

function browserExtCallback(response){

}

function checkAccountIdx(accounts, idx, forTrips){
	checkCancelled = false;
	var thisPopup, travelPlan;
	if(idx < accounts.length){
		setHistoryRows(idx, accounts.length > 1, accounts);
		var account = accounts[idx];
		document.getElementById('checkStopButton').onclick = stopChecking;
		if(accounts.length > 1)
			document.getElementById('checkPopupHeader').innerHTML = 'Checking account '+(idx+1)+' of '+accounts.length+': '+account.userName;
		else
			document.getElementById('checkPopupHeader').innerHTML = 'Checking <span style="font-weight: bold;">"'+account.displayName+ '"</span> for '+account.userName;
		var displayName = account.displayName;
		if (displayName.length > 35) {
			displayName = displayName.substring(0, 35);
			displayName = displayName.substring(0, displayName.lastIndexOf(' '));
			displayName += '...';
		}
		document.getElementById('checkText').innerHTML = '<img src="/lib/images/progressCircle.gif"> Currently checking: <span style="font-weight: bold;">'+displayName+'</span>';
		document.getElementById('checkNote').innerHTML = '*All advertising links open in a new window, checking balances will not stop.';
		if(document.getElementById('checkPopup').style.visibility != 'visible'){
			onPopupCancelled = stopChecking;
			showPopupWindow(document.getElementById('checkPopup'), true);
			$('#checkStopButton input').removeAttr('disabled');
		}
		$.ajax({
			url: "/top100",
			data: {account: account.accountId},
			dataType: 'json',
			success: function(response){
				displayAd(response);
			},
			error: ajaxError
		});
		resetCheckRequestTimer();

		var onResponse = function(response){
			if(accounts.length <= 1){
				document.getElementById('checkText').innerHTML = response.Message;
				$('#checkStopButton input').attr('value', 'Close');
				if(!forTrips)
					document.getElementById('checkStopButton').onclick = function(){stopChecking();reloadPage();};
				if(response.Detail != ""){
					showCheckDetails(response.Detail);
				}
				else
					checkSuccessfull(accounts, forTrips);
			}
			else{
				cycleAccountChecked(accounts, response.Message, idx, forTrips);
			}
		};

		if (account.checkInBrowser == 1 || (account.checkInBrowser == 2 && browserExt.supportedBrowser() && !disableExtension)){
			if (browserExt.available()){
				browserExt.checkAccount(account.accountId, function(params){
					resetCheckRequestTimer();
					var icon;
					if(params.errorMessage){
						icon = "<img src=/lib/images/error.gif title='Error'>";
						response = {
							Status: 'OK',
							Message: icon + ' ' + account.displayName + " - Error occured.</span>",
							Detail: browserExt.formatAccountError(params.errorMessage, account.accountId)
						};
					}
					else{
						icon = "<img src=/lib/images/success.gif title='Success'>";
						var info  = "Balance: <span style='font-weight: bold;'>" + params.properties.Balance +  "</span>";
						if(typeof(params.oldBalance) != 'undefined')
							if(params.oldBalance != params.properties.Balance) {
								icon = "<img src=/images/changed.gif title='Balance was changed within last 24 hours'>";
								info = "Successfully updated from <span style='font-weight: bold;'>" + params.oldBalance + "</span> to <span style='font-weight: bold;'>" + params.properties.Balance + "</span>";
							}
							else
								info = "Balance remains the same: <span style='font-weight: bold;'>" + params.properties.Balance + "</span>";
						response = {
							Status: 'OK',
							Message: icon + ' ' + account.displayName + ' - ' + info,
							Detail: ''
						};
					}
					onResponse(response);
				}, forTrips);
			}
			else{
				document.location.href = '/extension/';
//				var icon = "<img src=/lib/images/error.gif>";
//				response = {
//					Status: 'Error',
//					Message: icon + " Error occurred.",
//					Detail: 'You need to <a href="/extension/">install AwardWallet browser extension</a> to check this program.'
//				};
//				onResponse(response);
			}
		}
		else{
			if (typeof activeTravelPlanId == 'undefined' || activeTravelPlanId == 0)
				travelPlan = '';
			else
				travelPlan = '&travelPlan='+activeTravelPlanId;
			checkRequest = $.ajax({
				url: "/account/ajaxCheck.php?ID="+account.accountId+'&Cycle='+(accounts.length > 1?"1":"0")+'&CheckIts='+(forTrips?"1":"0")+(idx==0?"&ClearCache=1":"")+travelPlan,
				dataType: 'json',
				success: function(response){
					if(response == null)
						response = {Status: 'OK', Message: "<img src=/lib/images/error.gif> " + account.displayName + "No response", Detail: ''};
					resetCheckRequestTimer();
					if(checkCancelled)
						return false;
                    var onCancel;
                    if(accounts.length > 1)
                        onCancel = function(){
                            cycleAccountChecked(accounts, response.Message, idx);
                        };
                    else
                        onCancel = function(){
                            stopChecking();
                            reloadPage();
                        };
                    var onSuccess = function(){
                        checkAccountIdx(accounts, idx, forTrips);
                    };
                    switch(response.Status){
                        case "Unauthorized":
						    authorize();
                            break;
                        case "MissingLocalPassword":
                            askAccountPassword(account.accountId, response.DisplayName, response.Login, response.UserName, onSuccess, onCancel, false);
                            break;
                        case 'Question':
							askAccountQuestion(account.accountId, response.DisplayName, response.Question, response.ErrorMessage, onSuccess, onCancel);
						    break;
                        default:
							onResponse(response);
					}
				},
				error: function(XMLHttpRequest){
					resetCheckRequestTimer();
					ajaxError(XMLHttpRequest);
					if(!checkTimedout)
						onResponse({Message: '<img src=/lib/images/error.gif> ' + account.displayName + ' - Unknown error', Detail: ''});
				}
			});
		}
		checkRequestTimer = setTimeout(function(){
			checkTimedout = true;
			if(checkRequest)
				checkRequest.abort();
			checkTimedout = false;
			onResponse({Message: '<img src=/lib/images/error.gif> ' + account.displayName + ' - Timed out', Detail: ''});
		}, 180000);
	}
	else
		reloadPage();
}

var storedPopups = [];

function pushActivePopup(){
    if(activePopupWindow){
        storedPopup = activePopupWindow;
        activePopupWindow = null;
        storedPopup.style.zIndex = '9';
        storedPopups.push(storedPopup);
    }
}

function popActivePopup(){
    if(storedPopups.length > 0){
        storedPopup = storedPopups.pop();
        activePopupWindow = storedPopup;
        activePopupWindow.style.zIndex = "50";
    }
    else{
        activePopupWindow = null;
        cancelPopup();
    }
}

var lastAskedPassword;

function askAccountPassword(accountId, providerName, login, userName, onSuccess, onCancel, toDatabase){
	var prompt = 'Enter password for account ' + login + ' (' + userName + '):';
    pushActivePopup();
	showMessagePopup('', 'Missing password for ' + providerName, prompt+'<br><input class="inputTxt" id="accountPassword" type="password" style="width:454px;">');
	setTimeout("document.getElementById('accountPassword').focus(); setSavePasswordHandler();", 500);
	document.getElementById('messageCancelButton').style.display = "";
	document.getElementById('messageCancelButton').onclick = function(){
        hidePopupWindow();
        popActivePopup();
        onCancel();
	};
	document.getElementById('messageOKButton').onclick = function(){
		if(trim(document.getElementById('accountPassword').value) == ""){
			document.getElementById('accountPassword').focus();
			$('#accountPassword').animate({left: '+=10'}, 200);
			$('#accountPassword').animate({left: '-=20'}, 200);
			$('#accountPassword').animate({left: '+=10'}, 200);
			return;
		}
		hidePopupWindow();
        popActivePopup();
		var params = {};
		params["AccountID"] = accountId;
		params["Password"] = trim(document.getElementById('accountPassword').value);
        params["ToDatabase"] = toDatabase;
        lastAskedPassword = params["Password"];
		$.ajax({
            url: '/account/saveAccountPassword.php',
            type: 'POST',
            data: params,
            success: function(response){
                if(response != 'OK'){
                    alert(response);
                    cancelPopup();
                }
                else{
                    onSuccess();
                }
            },
            error:ajaxError
        });
	};
}

function setSavePasswordHandler(){
    $('#accountPassword').keypress(function(event){
   		if(event.keyCode == 13)
   			document.getElementById('messageOKButton').onclick();
   	});
   	$('#accountPassword').keydown(function(event){
   		if(event.keyCode == '27')
   			document.getElementById('messageCancelButton').onclick();
   	});
}

function showCheckDetails(details){
	document.getElementById('checkAdContent').style.display = 'none';
	document.getElementById('checkAdMessage').style.display = '';
	setTimeout(function(){
		var msgHeight = $('#checkAdMessage').height();
		if((msgHeight + 220) > getWindowHeight()){
			msgHeight = $('window').height() - 220;
		}
		document.getElementById('checkAd').style.height = msgHeight + 'px';
		document.getElementById('checkAd').style.overflow = 'auto';
		var popupHeight = $('#checkPopup div:first').height();
		$('#checkPopup').animate({height: popupHeight}, 500);
		var top = Math.round( getWindowHeight() / 2 - popupHeight / 2 + getScrollTop() );
		if(top < 10)
			top = 10;
		$('#checkPopup').animate({top: top}, 500);
		$('#checkPopupInner').animate({height: popupHeight}, 500);
	}, 100);
	document.getElementById('checkAdMessage').innerHTML = "<div id=checkAdError style=\"text-align: left; padding: 5px; font-size: 16px;\">"+details+"</div>";
}

function cycleAccountChecked(accounts, message, idx, forTrips){
	document.getElementById('checkHistoryText').innerHTML += '<div>'+message+'</div>';
	setHistoryRows(idx + 1, true, accounts);
	if(idx == (accounts.length - 1)){
		document.getElementById('checkText').innerHTML = '<span style="font-weight: bold;">All accounts checked</span>'; /*checked by Alexi*/
		$('#checkStopButton input').attr('value', 'Close');
		document.getElementById('checkStopButton').onclick = function(){cancelPopup();reloadPage();};
		checkSuccessfull(accounts, forTrips);
	}
	else if (!checkCancelled)
		setTimeout(function(){checkAccountIdx(accounts, idx+1, forTrips)}, 10);
}

function stopChecking(){
	clearTimeout(checkRequestTimer);
	checkCancelled = true;
	if(browserExt.installed)
		browserExt.cancel();
	cancelPopup();
	document.getElementById('checkStopButton').onclick = stopChecking;
	document.getElementById('checkAdMessage').style.display = 'none';
	document.getElementById('checkAdContent').style.display = '';
	document.getElementById('checkAd').style.border = '1px solid #0b70b8';
	reloadPage();
}

function checkAll(){
		var accounts = [];
		$('a.checkLink').each(function(index, link){
			accounts.push(getAccountInfoFromList(index));
		});
		checkAccountIdx(accounts, 0, false);
}

var PredefinedAccountInfo = {};
function getAccountInfoById(id){
	if (id in PredefinedAccountInfo) return PredefinedAccountInfo[id];
	var link = $('tr#row' + id + ' a.checkLink');
	var info = {
		accountId: id,
		displayName: document.getElementById('rewardName'+id).innerHTML
	};
	if (link.length > 0)
		info = getAccountInfoFromLink(link);
	info.redirectUrl = $('tr#row' + id + ' a.alLink').attr('href');
	return info;
}

function getAccountInfoFromLink(link){
	var accountId = link.attr('accountId');
	return {
		accountId: accountId,
		displayName: document.getElementById('rewardName'+accountId).innerHTML,
		userName: link.attr('UserName'),
		providerCode: link.attr('ProviderCode'),
		checkInBrowser: Math.round(link.attr('CheckInBrowser')),
		receiveFromBrowser: link.attr('CheckInBrowser') == '2',
		kind: link.attr('ProviderKind')
	}
}

function getAccountInfoFromList(index){
	account = $('a.checkLink:eq('+index+')');
	return getAccountInfoFromLink(account);
}

function accountInfo(index){
	var accounts = [];
	accounts.push(getAccountInfoFromList(index));
	return accounts;
}

function stopRedirecting(){
	window.close();
}

function displayAd(response){
	if(response.Content != ''){
		document.getElementById('checkAdContent').innerHTML = response.Content;
		$('#checkAdContent a').each(function(index, link){
			link = $(link);
			link.attr('target', "_blank");
		});
		$('#checkAdContent a').click(function(){
			$.ajax({
					url: "/top100/click?ad="+response.SocialAdID,
					error: ajaxError
				});
		});
	}
}

function redirectAccount(accountId, programName, providerName, autoLogin, askLocalPassword, login, userName){
	checkCancelled = false;
	onPopupCancelled = stopRedirecting;
	document.getElementById('checkStopButton').onclick = stopRedirecting;
	if(autoLogin){
		document.getElementById('checkPopupHeader').innerHTML = 'Auto-login to <span style="font-weight: bold;">"'+programName+ '"</span> website';
		document.getElementById('checkText').innerHTML = '<img src="/lib/images/progressCircle.gif"> We are attempting to automatically log you in';
	}
	else{
		document.getElementById('checkPopupHeader').innerHTML = 'Redirecting to <span style="font-weight: bold;">"'+programName+ '"</span> website';
		document.getElementById('checkText').innerHTML = '<img src="/lib/images/progressCircle.gif"> Unfortunately auto-login is not yet available for this program';
	}
	document.getElementById('checkNote').innerHTML = '*All advertising links open in a new window, auto-login will not stop.';
	if(document.getElementById('checkPopup').style.visibility != 'visible')
		showPopupWindow(document.getElementById('checkPopup'), true);

    var askPasswordAndGo = function(){
        if(askLocalPassword)
            askAccountPassword(accountId, providerName, login, userName, processRedirect, processRedirect, false);
        else
            processRedirect();
    };

	var message = getCookie('redirect_message_' + accountId);
	if(message && message != ''){
		setCookie('redirect_message_' + accountId, '');
		document.getElementById('checkAdContent').innerHTML = "<div class='message'>" + message + "</div>";
		askPasswordAndGo();
		document.getElementById('checkNote').innerHTML = '';
	}
	else {
		$.ajax(
			{
				url: "/top100",
				data: {account: accountId},
				dataType: 'json',
				success: function (response) {
					displayAd(response);
					askPasswordAndGo();
				},
				error: ajaxError
			}
		);
	}
}

function getDateString(y_obj,m_obj,d_obj)
{
  var y = y_obj.options[y_obj.selectedIndex].value;
  var m = m_obj.options[m_obj.selectedIndex].value;
  var d = d_obj.options[d_obj.selectedIndex].value;
  if (y=="" || m=="") {return null;}
  if (d=="") {d=1;}
  return str= y+'-'+m+'-'+d;
}

function AdjustDates( sName, sInput1, sInput2 )
{
  y_obj1 = document.SearchForm[sInput1 + '_year'];
  m_obj1 = document.SearchForm[sInput1 + '_month'];
  d_obj1 = document.SearchForm[sInput1 + '_day'];
  y_obj2 = document.SearchForm[sInput2 + '_year'];
  m_obj2 = document.SearchForm[sInput2 + '_month'];
  d_obj2 = document.SearchForm[sInput2 + '_day'];
  var y1 = y_obj1.selectedIndex;
  var m1 = m_obj1.selectedIndex;
  var d1 = d_obj1.selectedIndex;
  var y2 = y_obj2.selectedIndex;
  var m2 = m_obj2.selectedIndex;
  var d2 = d_obj2.selectedIndex;
  days1 = y1 * 400 + m1 * 40 + d1;
  days2 = y2 * 400 + m2 * 40 + d2;
  if( ( sName == sInput1 ) && (
  ( y1 > y2 )
  || ( ( y1 == y2 ) && ( m1 > m2 ) )
  || ( ( y1 == y2 ) && ( m1 == m2 ) && ( d1 > d2 ) ) ) )
  {
    y_obj2.selectedIndex = y_obj1.selectedIndex;
    m_obj2.selectedIndex = m_obj1.selectedIndex;
    d_obj2.selectedIndex = d_obj1.selectedIndex;
  }
  var y1 = y_obj1.selectedIndex;
  var m1 = m_obj1.selectedIndex;
  var d1 = d_obj1.selectedIndex;
  var y2 = y_obj2.selectedIndex;
  var m2 = m_obj2.selectedIndex;
  var d2 = d_obj2.selectedIndex;
  if( ( sName == "Form" ) && (
  ( y1 > y2 )
  || ( ( y1 == y2 ) && ( m1 > m2 ) )
  || ( ( y1 == y2 ) && ( m1 == m2 ) && ( d1 > d2 ) ) ) )
  {
  	alert( 'The <?=$sDate2Caption?> has to be after the <?=$sDate1Caption?>' );
  	return false;
  }
  return true;
}

function switchExt( n ){
	var extImage = document.getElementById('extImage'+n);
	var extRow = document.getElementById('extRow'+n);
	var expdate = new Date();
	expdate.setTime(expdate.getTime()+(12*30*24*60*60*1000));
	if( extImage.src.indexOf("/lib/images/bulletPlus1.gif") >= 0 ){
		extImage.src = "/lib/images/bulletMinus1.gif";
		extRow.style.display = '';
		setCookie('ext'+n, '1', expdate, "/account/list.php");
	}
	else{
		extImage.src = "/lib/images/bulletPlus1.gif";
		extRow.style.display = 'none';
		setCookie('ext'+n, '0', expdate, "/account/list.php");
	}
}

function forceExt( n, enabled ){
	var extImage = document.getElementById('extImage'+n);
	var extRow = document.getElementById('extRow'+n);
	var expdate = new Date();
	expdate.setTime(expdate.getTime()+(12*30*24*60*60*1000));
	if( enabled ){
		extImage.src = "/lib/images/bulletMinus1.gif";
		extRow.style.display = '';
		setCookie('ext'+n, '1', expdate, "/account/list.php");
	}
	else{
		extImage.src = "/lib/images/bulletPlus1.gif";
		extRow.style.display = 'none';
		setCookie('ext'+n, '0', expdate, "/account/list.php");
	}
}

function switchSection(id){
	var arrow = $('#'+id+'_head a.arrow');
	arrow.toggleClass('closedArrow');
	var body = $('#'+id+'_body > td > div.blockBody');
	body.css('overflow', 'hidden');
	if(arrow.hasClass('closedArrow')){
		body.animate({height: 0}, {duration: 500});
	}
	else{
		var bodyInner = $('#'+id+'_body > td > div.blockBody > div.blockBodyInner');
		body.animate({height: bodyInner.height()}, {duration: 500});
	}
}

function sectionState( n ){
	var extImage = document.getElementById('allExtImage'+n);
	return ( extImage.src.indexOf("/lib/images/bulletPlus1.gif") >= 0 );
}

var activePopup = 0;
var nextPopup = 0;
var showTimer = 0;
var popupHeight;
var popupTop;

function clickRow( id ){
	var fader = document.getElementById('fader');
	fader.style.height = $(document).height() + 'px';
	fader.style.visibility = 'visible';
	fader.onclick = function(){
		hidePopup(id);
		fader.style.visibility = 'hidden';
		fader.onclick = function(){cancelPopup()};
	};
	if( showTimer > 0 )
		clearTimeout( showTimer );
	showPopup(id);
	setTimeout('clearTimeout(showTimer)', 100);
}

function overRow( id ){
	var fader = document.getElementById('fader');
	if(fader.style.visibility == 'visible')
		return false;
	if( showTimer > 0 )
		clearTimeout( showTimer );
	showTimer = setTimeout('showPopup("'+id+'")', 200);
}

function outRow( id ){
	var fader = document.getElementById('fader');
	if(fader.style.visibility == 'visible')
		return false;
	if( showTimer > 0 )
		clearTimeout( showTimer );
	showTimer = setTimeout('hidePopup("'+id+'")', 200);
}

function showPopup( id ){
	var popup = document.getElementById('extRow'+id);
	// hide current other popup, if any
	if( ( activePopup != 0 ) && ( activePopup != id ) ){
		$('#extRow'+activePopup).stop(true, true);
		popupHidden();
	}
	// stop if hiding current popup
	if(activePopup == id){
		$('#extRow'+activePopup).stop(true, true);
//		activePopup = 0;
		nextPopup = 0;
	}
	// show new popup if any
	if( popup && ( activePopup != id )){
		activePopup = id;
		var tblAccounts = document.getElementById('tblAccounts');
		var width = $(tblAccounts).width() - 65;
		if(id.match('sa')) {
			if(width > 850)
				width = 850;
		}
		else {
			if(width > 900)
				width = 900;
		}
		if(popup.innerHTML == 'Loading..'){
			popup.innerHTML = document.getElementById('loadingTemplate').innerHTML;
			var url;
			var params = {};
			if(popup.getAttribute('accounts') > 1){
				url = '/account/providerInfo.php?ID='+popup.getAttribute('providerId');
				if(popup.getAttribute('checkInBrowser') != '0')
					params = browserExt.getBalancesOfProvider(popup.getAttribute('providerCode'));
			}
			else
				url = '/account/moreInfo.php?TableName='+popup.getAttribute('tableName')+'&ID='+popup.getAttribute('rowId')+'&SubAccountID='+popup.getAttribute('subAccountId')+'&Width='+width;
			$.ajax({
				url: url,
				type: 'POST',
				data: params,
				success: function(response){
					popup.innerHTML = response;
					var row = document.getElementById('row'+id);
					if( row && row.getAttribute('checkinbrowser') == "1")
						browserExt.fillPopupProperties(id);
					updatePopupSize(popup, top);
					if(popup.getAttribute('tableName') == 'Account')
						updateInfoWindow(popup, popup.getAttribute('rowId'), popup.getAttribute('subAccountId'));
				}
			});
		}
		//var accountCell = document.getElementById('accountCell'+parseInt(id));
		var plus = $('#plus'+id);
		var top = plus.offset().top + plus.height() / 2;
		var left = $(plus).offset().left + $(plus).height() + 10 ;
		popup.style.left = left + 'px';
		popup.style.width = width + 'px';
		popup.style.display = '';
		// marker
		var marker = document.getElementById('rowPopupMarker');
		marker.style.left = (left - 11 ) + 'px';
		updatePopupSize(popup, top);
		marker.style.top = (top - 10) + 'px';
		marker.style.visibility = 'hidden';
		marker.style.display = 'block';
		if(($(marker).offset().top > ($(popup).offset().top + 10))
		&& (($(marker).offset().top + $(marker).height())  < ($(popup).offset().top + $(popup).height() - 15)))
			marker.style.visibility = 'visible';
	}
}

function updateInfoWindow(popup, accountId, subAccountId){
	var table = $(popup).find("table.mainProps tbody");
	var centerDiv = $(popup).find('div.fCenterDiv');
	centerDiv.css('min-height', centerDiv.height()+'px');
	var template = table.find("tr:has(td:contains('Expiration'))");
    var expiration;
    if (subAccountId > 0) {
        accountId += 'sa' + subAccountId;
        expiration = $('tr#row'+accountId+' td.expiration div.date');
    }
    else
	    expiration = $('tr#row'+accountId+' td.expiration a:first');
	if(expiration.length > 0){
		template.find('td.value').html('').addClass('expiration');
		$('tr#row'+accountId+' td.expiration div.greenCorner').clone().appendTo(template.find('td.value'));
		var newExiration = expiration.clone().appendTo(template.find('td.value'));
		newExiration.find('br').replaceWith('<span> </span>');
	}
	else{
		var row = document.getElementById('row'+accountId);
		if( row && row.getAttribute('checkinbrowser') > 0)
			template.remove();
	}
	table.children("tr:even").filter(':has(td.name)').addClass('odd').removeClass('even');
	table.children("tr:odd").filter(':has(td.name)').addClass('even').removeClass('odd');
}

function updatePopupSize(popup, top){
	var inDiv = $(popup).children().get(0);
	inDiv.style.width = popup.style.width;
	var height = $(inDiv).height();
	if(height < 200)
		height = 200;
	var scrollOffset = getScrollTop();
	top -= Math.round(height / 2);
	var bottomLimit = getScrollTop() + getWindowHeight();
	if((top + height) > bottomLimit)
		top = bottomLimit - height;
	if(top < (scrollOffset + 10))
		top = scrollOffset + 10;
	popup.style.height = height + 'px';
	popupHeight = height;
	// popup down
	popupTop = false;
	popup.style.top = top + 'px';
}

function getBodyHeight() {
	if (self.innerWidth) return self.innerHeight;
	else if (document.documentElement && document.documentElement.clientWidth) return document.documentElement.clientHeight;
	else if (document.body) return document.body.clientHeight;
}


function popupShown(){
}

function hidePopup( id ){
	// hide popup
	if(activePopup != 0){
		var popup = document.getElementById('extRow'+activePopup);
		document.getElementById('rowPopupMarker').style.display = 'none';
		popupHidden();
	}
}

function popupHidden(){
	if( activePopup != 0 ){
		var popup = document.getElementById('extRow'+activePopup);
		popup.style.display = 'none';
		popup.style.height = '1px';
		popup.style.marginTop = '0px';
		activePopup = 0;
		if( nextPopup != 0 ){
			setTimeout('showPopup('+nextPopup+')', 200);
			nextPopup = 0;
		}
	}
}

var revealLink;
var revealAccountId;
var login2revealed;

function revealPassword( fieldId, link, accountId ){
	if(link.innerHTML == 'Hide password'){
		var field = document.getElementById(document.getElementById('revealFieldId').value);
		var field2 = createInput('password');
		field2.value = field.value;
		field.parentNode.replaceChild(field2,field);
		link.innerHTML = 'Reveal password';
		$(field2).bind('focus', null, formControlFocused).bind('blur', null, formControlBlurred);
		field2.focus();

		if(login2revealed){
			var field = document.getElementById('fldLogin2');
			var field2 = createInput('password');
			field2.value = field.value;
			field.id = 'fldLogin2Old';
			field2.id = 'fldLogin2';
			field.parentNode.replaceChild(field2, field);
			login2revealed = false;
		}

		return;
	}
	revealAccountId = accountId;
	var box = document.getElementById('revealPasswordBox');
	var input = document.getElementById(fieldId);
	revealLink = link;
	var pos = $(input).offset();
	box.style.top = pos.top + $(input).height() + 20 + 'px';
	box.style.left = pos.left - 5 + 'px';
	box.style.width = $(input).width();
	document.getElementById('revealFieldId').value = fieldId;
	$('#revealPassword').unbind('keypress');
	$('#revealPassword').keypress(function(event){
		if(event.keyCode == '13')
			checkRevealPassword();
	});
	$('#revealPasswordBox').css('visibility', 'visible');
	$('#revealPasswordBox').fadeIn(300, function(){
		activePopupWindow = box;
		document.getElementById('revealPassword').focus();
	});
}

function cancelReveal(){
	$('#revealPasswordBox').fadeOut(300, function() {document.getElementById(document.getElementById('revealFieldId').value).focus();});
}

function checkRevealPassword(){
    $('#revealPasswordProgress').show();
	$.ajax({
		url: '/account/checkUserPassword.php',
		success: function () {
            $('#revealPasswordProgress').hide();
            checkUserPasswordSuccess.apply(null, arguments);
        },
        type : 'POST',
		dataType: 'json',
		data: "Password="+encodeURIComponent(document.getElementById('revealPassword').value)+'&AccountID='+encodeURIComponent(revealAccountId),
		error: function () {
            $('#revealPasswordProgress').hide();
            ajaxError.apply(null, arguments);
        }
	});
}

function createInput(type){
	var field2 = document.createElement("input");
	// <input class="inputTxt" type="text" name="Pass" autocomplete="off" id="fldPass" maxlength="40" size="42" value="********">
	field2.setAttribute('type', type);
	field2.setAttribute('className', 'inputTxt');
	field2.setAttribute('class', 'inputTxt');
	field2.setAttribute('name', 'Pass');
	field2.setAttribute('id', 'fldPass');
	field2.setAttribute('maxlength', '40');
	field2.setAttribute('size', '42');
	return field2;
}

function checkUserPasswordSuccess(response){
	if (response.Error == ''){
		var field = document.getElementById(document.getElementById('revealFieldId').value);
		var field2 = createInput('text');
		if(localPassword)
			field2.value = localPassword;
		else
			field2.value = response.Password;
		field.parentNode.replaceChild(field2,field);
		cancelReveal();
		$(field2).bind('focus', null, formControlFocused).bind('blur', null, formControlBlurred);
		field2.focus();

		var login2 = $('#fldLogin2');
		if(login2.length > 0 && login2.attr('type') == 'password'){
			var field = document.getElementById('fldLogin2');
			var field2 = createInput('text');
			field2.value = login2.val();
			field.id = 'fldLogin2Old';
			field2.id = 'fldLogin2';
			field.parentNode.replaceChild(field2, field);
			login2revealed = true;
		}

		revealLink.innerHTML = 'Hide password';
	}
	else{
		alert(response.Error);
		if ($('#revealPassword') != null)
			$('#revealPassword').focus().select();
	}
}

var activePopup;
var activePopupWindow;
var reloadAfterClose;

function adjustPopupSize(popup){
	var childHeight = $(popup).children().height();
	var newHeight = (childHeight + 15);
	var maxHeight = getWindowHeight();
	var docHeight = $(document).height();
	if(docHeight > maxHeight)
		maxHeight = docHeight;
	if(newHeight > maxHeight){
		newHeight = maxHeight;
		cell = $(popup).find("table.frame tr.middle td.center *:first");
		cell.height(newHeight - 160);
		cell.css('overflow', 'auto');
	}
	popup.style.height = newHeight+'px';
}

function showPopupWindow(popup, autoSize, onShow, immediate){
	if(activePopupWindow && (activePopupWindow != popup))
		activePopupWindow.style.display = 'none';
	if(autoSize){
		popup.style.visibility = 'hidden';
		popup.style.left = 0;
		popup.style.top = 0;
 		popup.style.display = '';
		adjustPopupSize(popup);
		popup.style.display = 'none';
		popup.style.visibility = 'visible';

	}
	reloadAfterClose = 0;
	activePopupWindow = popup;
	popup.style.visibility = 'hidden';
	popup.style.display = '';
	placeWindowAtCenter(popup);
	popup.style.display = 'none';
	popup.style.visibility = 'visible';
	showFader();
	var onComplete = function(){
		if((typeof(parentHeight) != "undefined") && (parentHeight > 0)){
			updateFrameSize();
		}
		if(onShow)
			onShow();
	};
	if(immediate){
		popup.style.display = '';
		onComplete();
	}
	else{
		setTimeout(function(){$('#'+popup.id).fadeIn(300)}, 1 );
		setTimeout(onComplete, 350);
	}
}

function showFader(){
	var fader = document.getElementById('fader');
	fader.style.height = $(document).height() + 'px';
	fader.style.visibility = 'visible';
}

function placeWindowAtCenter(popup){
	//var fader = document.getElementById('fader');
	//$(popup).position({
	//	my: "center",
	//	at: "center center",
	//	of: window
	//});
	//return;
	//if($(popup).find('iframe').length == 0 && $(popup).parent().prop('tagName') != 'BODY')
	//	$(popup).remove().appendTo("body"); // absolute position from body, not content
	var offset = $(popup).offset();
	var position = $(popup).position();
	popup.style.left = ( $(window).width() / 2 - $(popup).width() / 2 - (offset.left - position.left) ) + 'px';
	var scrollTop = getScrollTop();
	var windowHeight = getWindowHeight();
	var top = ( windowHeight / 2 - $(popup).height() / 2 + scrollTop - (offset.top - position.top) );
	if(top < scrollTop)
		top = scrollTop;
	popup.style.top = top + 'px';
}

// get scroll top, frame-compatible
function getScrollTop(){
	var scrollTop = $(window).scrollTop();
	if((typeof(parentScrollTop) != "undefined") && (parentScrollTop > parentOffsetTop))
		scrollTop = parentScrollTop - parentOffsetTop;
	return scrollTop;
}

// get window height, frame-compatible
function getWindowHeight(){
	var windowHeight = $(window).height();
	if(typeof(parentHeight) != "undefined")
		windowHeight = parentHeight;
	return windowHeight;
}

function hidePopupWindow(){
	if(activePopupWindow){
		activePopupWindow.style.visibility = 'hidden';
		activePopupWindow.style.display = 'none';
		//if(document.getElementById('messagePopup')){
		//	document.getElementById('messagePopup').style.width = '';
		//}
	}
}

var onPopupCancelled = null;

function cancelPopup(){
	if(activePopupWindow){
		$(activePopupWindow).fadeOut(200);
		if( activePopupWindow.id == 'framePopup' )
			setTimeout('document.getElementById("popupFrameContainer").innerHTML = ""', 500);
	}
	setTimeout('popupCancelled()', 200 );
}

function faderClick(){
	if(!(activePopupWindow && (activePopupWindow.id == 'checkPopup')))
		cancelPopup();
}

function popupCancelled(){
	var fader = document.getElementById('fader');
	fader.style.visibility = 'hidden';
	hidePopupWindow();
	if( reloadAfterClose == 1 )
		reloadPage();
	activePopupWindow = null;
	if(typeof onPopupCancelled == 'function'){
		onPopupCancelled();
		onPopupCancelled = null;
	}
}

function showPolicy(){
	if( window.frameElement != null )
		parent.showPolicy();
	else{
		$.ajax({
			url: "/user/termsOfUse.php",
			success: function(response){
				document.getElementById('termsText').innerHTML = response;
				var oldPopup = null;
				var newPopup = document.getElementById('termsPopup');
				if(activePopupWindow && (activePopupWindow != newPopup)){
					oldPopup = activePopupWindow;
					onPopupCancelled = function(){
						oldPopup.style.display = '';
						activePopupWindow = oldPopup;
						var fader = document.getElementById('fader');
						fader.style.visibility = 'visible';
					};
				}
				showPopupWindow(newPopup, true);
			}
		})
	}
}

var frameOnShow = null;

var frameTimer;
var frameLoaded;

function showFrame(src, width, title, onShow){
	frameOnShow = onShow;
	document.getElementById('framePopup').style.visibility = 'hidden';
	document.getElementById('framePopup').style.width = width + 'px';
	document.getElementById('framePopup').style.height = '100px';
	document.getElementById('framePopup').style.display = '';
	document.getElementById('frameHeader').innerHTML = title;
	if(title){
		$('#framePopup table.frame').removeClass('headerLess');
		$('#popupFrameContainer').css('padding-top', '20px');
	}
	else{
		$('#framePopup table.frame').addClass('headerLess');
		$('#popupFrameContainer').css('padding-top', '0px');
	}
	document.getElementById('popupFrameContainer').innerHTML = '<iframe name="popupFrame" id="popupFrame" src="'+src+'" width="100%" height="50" border="0" onload="popupFrameLoadComplete()" frameborder="0"></iframe>';
	frameTimer = null;
	frameLoaded = false;
	//document.getElementById('popupFrame').src = src;
}

function popupFrameLoadComplete(){
	if(!frameLoaded && (document.getElementById('popupFrame').src != 'about:blank')){
		frameTimer = setTimeout(function(){popupFrameLoaded(0, 300, 0, 0)}, 3000);
	}
}

function popupFrameLoaded(success, height, closePopup, reloadParent){
	frameLoaded = true;
	clearTimeout(frameTimer);
	var frame = document.getElementById('popupFrame');
	var popup = document.getElementById('framePopup');
	if( closePopup == 1 ){
		reloadAfterClose = reloadParent;
		cancelPopup();
		return;
	}
	if( popup.style.visibility == 'visible' ){
		// only update size
		if( $(frame).height() < height ){
			$(frame).height(height);
			childHeight = $(popup).children().height() + 5;
			$(popup).animate({height: childHeight}, 200);
		}
		return;
	}
	if( height < 50 )
		height = 50;
	frame.style.height = height + 'px';
	showPopupWindow(popup, true, frameOnShow);
}
/*
function kayakSearch(){
	showFrame('/kayakSearch.php');
}
*/

function tradeMiles(accountId){
	$.ajax({
		url: '/account/tradeText.php?ID='+accountId,
		dataType: 'json',
		success: function(response){
			if(response.Status == "Unauthorized"){
				authorize();
				return false;
			}
			showMessagePopup('info', response.Title, response.Text);
			$('messageOKButton input').attr('value', "Close");
			return true;
		},
		error: ajaxError
	});
}

function showGoals(){
	$('td.goal').each(function(index, cell){
		var offset = -1000 + Math.round(cell.width() * cell.attr("goalComplete"));
		cell.attr('background-position', offset + 'px');
	});
}

function setGoal(){
	showFrame('/account/setGoal.php');
}

var reviewProviderId;

function showLastReview(a, providerId){
	clearTimeout(hideReviewTimer);
	if(reviewProviderId == providerId)
		return;
	var top = $(a).offset().top + 20;
	var left = $(a).offset().left;
	var popup = document.getElementById('reviewPopup');
	popup.style.left = left + 'px';
	popup.style.top = top + 'px';
	popup.style.visibility = 'visible';
	popup.style.height = '30px';
	document.getElementById('reviewPopupText').innerHTML = 'Loading..';
	popup.style.display = '';
	reviewProviderId = providerId;
	$.ajax({
		url: '/rating/reviewText.php?ID='+providerId,
		dataType: 'json',
		success: function(response){
			if(reviewProviderId == response.providerId){
				document.getElementById('reviewPopupText').innerHTML = response.review;
				popup.style.height = $('#reviewPopupText').height() + 20 + 'px';
				return true;
			}
			return false;
		},
		error: ajaxError
	});
}

var hideReviewTimer = 0;

function hideLastReview(){
	hideReviewTimer = setTimeout(function(){
		var popup = document.getElementById('reviewPopup');
		popup.style.display = 'none';
		reviewProviderId = 0;
	}, 300);
}

function rateReview(link, reviewId, providerId, score){
	var params = {};
	params["ReviewID"] = reviewId;
	params["ProviderID"] = providerId;
	params["Useful"] = score;
	$.ajax({
		url: "/rating/useful.php",
		type: 'POST',
		data: params,
		success: function(response){
			if(response != "OK"){
				alert(response);
				return false;
			}
			link.parentNode.innerHTML = "Thank you.";
			return true;
		},
		error: ajaxError
		}
	);

}

function explainRating(field){
	$.ajax({
		url: "/rating/explain.php?Field="+encodeURI(field),
		success: function(response){
			showMessagePopup('info', field.replace(/([a-z])([A-Z])/g, "$1 $2"), response);
			return true;
		},
		error: ajaxError
		}
	);
}

function ungroupClick(){
	grouped = !document.getElementById('ungroupCheck').checked;
	d = new Date();
	setCookie("grouped", grouped, new Date(d.getFullYear() + 2, 1, 1), "/account/", "", false);
	reloadPage();
}

function onlyCouponsClick(){
	onlyCoupons = document.getElementById('couponCheck').checked;
	var url = document.location.href;
	url = url.replace(/[\&\?]Coupons=\d+/ig, "");
	url = url.replace(/#[^#]+/ig, "");
	if(onlyCoupons){
		if(url.indexOf("?") >= 0)
			url = url + "&";
		else
			url = url + "?";
		url = url + "Coupons=1";
	}
	document.location.href = url;
}

function expiredCouponsClick(){
	expiredCoupons = document.getElementById('expcouponCheck').checked;
	var url = document.location.href;
	url = url.replace(/#[^#]+/ig, "");
	url = url.replace(/[\&\?]ExpCoupons=\d+/ig, "");
	if(expiredCoupons){
		if(url.indexOf("?") >= 0)
			url = url + "&";
		else
			url = url + "?";
		url = url + "ExpCoupons=1";
	}
	document.location.href = url;
}


function sendReminder(agentId, element, source){
	$.ajax({
			url: "/agent/sendReminder.php?UserID="+agentId+'&Source='+source,
			success: function(response){
				element.style.display = 'none';
				$(element).html(response);
				$(element).slideDown(500);
				$(element).unbind();
			},
			error: ajaxError
		}
	);
}

function resizeTabs(){
	if($.browser.msie)
		return;
	var tabs = $('#tabs');
	var contentWidth = tabs.width();
	var defaultFontSize = 18;
	var fontSize = defaultFontSize;
	var totalPadding = 12;
	while(fontSize > 12){
		var width = 0;
		tabs.children().each(function(index, div){
			width += $(div).width();
		});
		if(width <= contentWidth)
			break;
		fontSize--;
		totalPadding++;
		var topPadding = Math.round(totalPadding / 2);
		var bottomPadding = totalPadding - topPadding;
		tabs.find('div.caption').each(function(index, div){
			div.style.fontSize = fontSize + 'px';
			div.style.height = fontSize + 'px';
			div.style.paddingTop = topPadding + 'px';
			div.style.paddingBottom = bottomPadding + 'px';
		});
	}
	tabs.css('min-width', width+'px');
}

function formControlFocused(){
	var id = this.id.substring(3);
	var id2 = this.id;
	$(this).closest('tr#tr'+id+', tr#tr'+id2).addClass('focused');
	$(this).closest('table.inputFrame').addClass('ifFocused');
}

function formControlBlurred(){
	var id = this.id.substring(3);
	var id2 = this.id;
	$(this).closest('tr#tr'+id+', tr#tr'+id2).removeClass('focused');
	$(this).closest('table.inputFrame').removeClass('ifFocused');
}

function attachFormEvents(formName){
	$(document.forms[formName]).find('input, select, textarea').bind('focus', null, formControlFocused).bind('blur', null, formControlBlurred);
}

function authorizeUser(){
	if( window.frameElement != null )
	  	parent.location.href = "/security/unauthorized.php?" + query_string();
	else
	  	location.href = "/?" + query_string();
}

function showLoginBox(){
	if(document.getElementById('framePopup') == null){
		location.href = '/?BackTo='+encodeURI(location.href);
		return;
	}
	var backTo = getParameterByName('BackTo');
	var url;
	if(useHttps() && (document.location.protocol.toLowerCase() != 'https:')){
		url = 'https://'+document.location.host+'/?Login=1';
		if(backTo != '')
			url += "&BackTo="+url_encode(backTo);
		document.location.href = url;
	}
	else{
		url = '/security/loginFrame.php?form=login';
		if(backTo != '')
			url += "&BackTo="+url_encode(backTo);
		showFrame(url, 420, "Existing User", function(){
			var frame = document.getElementById('popupFrame');
			var frameDoc;
			if(frame.contentWindow)
				frameDoc = frame.contentWindow.document;
			else
				frameDoc = frame.contentDocument;
			frameDoc.getElementById('fldLogin').focus();
		});
	}
}

function useHttps(){
	var host = document.location.host.toLowerCase();
	return (host == 'awardwallet.com') || (host == 'iframe.awardwallet.com') || (host == 'business.awardwallet.com');
}

function showRegisterBox(){
	if(document.getElementById('framePopup') == null){
		location.href = '/?Register=1';
		return;
	}
	if(document.getElementById('framePopup').style.display != 'none'){
		$('#framePopup').fadeOut(200, showRegisterBox);
		return;
	}
	var url;
	var acceptUserId = getParameterByName('AcceptUserID');
	if(useHttps() && (document.location.protocol.toLowerCase() != 'https:')){
		url = 'https://'+document.location.host+'/?Register=1';
		if(acceptUserId != '')
			url += "&AcceptUserID="+acceptUserId;
		document.location.href = url;
	}
	else{
		url = '/user/quickRegFrame.php?d=1'; // dummy
		if(acceptUserId != '')
			url += "&AcceptUserID="+acceptUserId;
		var backTo = getParameterByName('BackTo');
		if(backTo != '')
			url += "&BackTo="+encodeURIComponent(backTo);
		showFrame(url, 815, "Quick Registration<br /><span class='alreadyAccount'>Already have an account? <a href='#' onclick='showLoginBox(); return false;'>Click to login</a> </span>", function(){
			var frame = document.getElementById('popupFrame');
			var frameDoc;
			if(frame.contentWindow)
				frameDoc = frame.contentWindow.document;
			else
				frameDoc = frame.contentDocument;
			frameDoc.getElementById('fldLogin').focus();
		});
	}
}

function toggleFaq(link){
	var qRow = $(link).closest('div.boxToll');
	qRow.toggleClass('boxBlueClosed');
	qRow.next().toggleClass('afterBlueClosed').slideToggle();
}


function alignAccountList(){
	$('div.redBar').each(function(index, bar){
		bar = $(bar);
		var height = bar.closest('td').height();
		var tr = bar.closest('tr');
		if(tr.hasClass('afterName') || tr.hasClass('afterHead'))
			height -= 5;
		bar.height(height);
	});
	$('#tblAccounts span.login').dblclick(function(event){
		selectElementText(this);
	});
}

function selectElementText(text) {
    if ($.browser.msie) {
        var range = document.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if ($.browser.mozilla || $.browser.opera) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    } else if ($.browser.safari) {
        var selection = window.getSelection();
        selection.setBaseAndExtent(text, 0, text, 1);
    }
}

function clearEmailFeild(feild){
	if(feild.value == "Type your friend's email here...")
		feild.value = "";
}

function sendInvite(frm){
	var email = trim(frm.inviteEmail.value);
	if(/^[_a-zA-Z\d\-\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+)$/.test(email)){
		$.ajax({
			url: "/lib/processInviteForm.php",
			type: "POST",
			data: {
				inviteEmail: email,
				requestType: "json",
                CSRF: frm.CSRF.value
			},
			error: ajaxError,
			success: function(response){
				frm.inviteEmail.value = "Type your friend's email here...";
				showMessagePopup("success", "Thank you", "Invitation was sent to "+email);
			}
		});
	}
	else{
		showMessagePopup("error", "Error", "The email address is not in a valid format");
	}
	return false;
}

function checkBrowser(){
	if($.browser.msie && ($.browser.version == '6.0')){
		$('#browserWarning').prependTo($('body')).show();
	}
	var height = screen.height;
	var width = screen.width;
	var expDate = new Date();
	expDate.setTime(expDate.getTime()+(30*24*3600));
	setCookie("Browser", "sh=" + height + "&sw=" + width, expDate, "/");
}

function facebookShare(refCode){
	var info;
	var balances = browserExt.getBalances();
	if(browserExt.totals)
		browserMiles = browserExt.totals.all;
	$.ajax({
		url: "/account/shareMessage.php",
		type: 'POST',
		data: {Balances: balances},
		async: false,
		success: function(response){
			info = response;
		}
	});
	FB.ui(
	{
		method: 'feed',
		name: 'AwardWallet.com',
		link: 'http://awardwallet.com/?r='+refCode,
		picture: info.Image,
		caption: 'AwardWallet.com tracks frequent flyer miles and other loyalty program points for free.',
		description: ' ',
		message: info.Message,
		display: 'popup'
	},
	function(response) {
		if (response && response.post_id) {
//       alert('Post was published.');
		} else {
//       alert('Post was not published.');
		}
	}
	);
}


function askBusinessName(){
	showFrame('/agent/askBusinessName.php', 520, 'Convert to business');
}

function ucfirst (str) {
    str += '';
    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1);
}

function selectTab(tabsId, tab){
	var lastClass = 'first';
	var tabs = $('#'+tabsId);
	var children = tabs.children('a');
	var activeId = 'tab_'+tabsId+'_'+tab;
	children.each(function(index, el){
		var className = 'normal';
		if(el.id == activeId)
			className = 'active';
		el.className = className;
		var q = $(el);
		q.children('div').attr('class', className);
		q.prev('div').attr('class', lastClass+'To'+ucfirst(className));
		lastClass = className;
	});
	tabs.children('#div_'+tabsId+'_last').attr('class', lastClass+'ToLast');
	var content = $('#'+tabsId+'_content');
	content.children('div').css('display', 'none');
	content.children('#'+tabsId+'_'+tab).css('display', 'block');
	
	// update height popup
	if (typeof(activePopup) != "undefined" && activePopup != null) {
		var popup = document.getElementById('extRow'+activePopup);
		if (typeof(top) != "undefined" && top != null && popup != null)
			updatePopupSize(popup, top);
	}
}

function printCoupon(id, accountId, providerId) {
	$.ajax({
		url: '/account/couponPrint.php?id='+id + "&accid="+accountId + "&prid="+providerId,
		dataType: 'json',
		success: function(response){
			if(response.Status == "Unauthorized"){
				authorize();
				return false;
			}
			showMessagePopup('info', 'Print Coupons', response.Body, true);
			return true;
		},
		error: ajaxError
	});
}

function printCoupons(accountId, subAccountId) {
	var url = '/account/redirect?ID='+accountId+"&Mode=download&deal="+subAccountId;
	var select = $('#messageText input[type=radio]:checked').attr('value');
	if (select == undefined) return false;
	window.open(url+'&coupon='+select);
	cancelPopup();
}

function inviteFamilyMember(sender, userAgentId, email){
	var box = document.getElementById('askEmailBox');
	var pos = $(sender).offset();
	box.style.top = pos.top + $(sender).height() + 5 + 'px';
	box.style.left = pos.left - $(box).width() + $(sender).width() + 'px';
	document.getElementById('userAgentId').value = userAgentId;
    $('#email').val(email);
	$('#email').keypress(function(event){if(event.keyCode == Event.KEY_RETURN) sendFamilyInvite();});
	showPopupWindow($('#askEmailBox').get(0), true, function(){document.getElementById('email').focus()});
//	$('#askEmailBox').fadeIn(700, function() { document.getElementById('email').focus(); });
}

function sendFamilyInvite(){
	$.ajax({
		url: '/invites/invite-member/' + document.getElementById('userAgentId').value,
		success: function(response){
			if(response.success){
				cancelInvite();
				reloadPage();
			}
			else{
				document.getElementById('email').focus();
				alert(response.error);
			}
		},
		type: 'POST',
		data: 'email='+encodeURIComponent(document.getElementById('email').value),
		error: ajaxError
	});
}

function cancelInvite(){
	cancelPopup();
}

function cancelInvitation(userAgentId){
	$.ajax({
		url: '/agent/uninviteFamilyMember.php',
		type: 'POST',
		data: "UserAgentID="+encodeURI(userAgentId),
		success: function(response){
			if(response == 'OK'){
				reloadPage();
			}
			else{
				alert(response);
			}
		},
		error: ajaxError
	});
}

function sendEmailReminder(inviteCodeId, element, source){
	$.ajax(
		{
			url: "/agent/sendEmailReminder.php?InviteCodeID="+inviteCodeId+'&Source='+source,
			success: function(response){
				element.style.display = 'none';
				element.innerHTML = response;
				$(element).fadeIn();
			},
			error: ajaxError
		}
	);
}

function checkAllBusiness(){
	$.ajax({
		url: '/account/jsonListAccounts.php',
		type: 'GET',
		data: '',
		dataType: 'json',
		success: function(response){
			checkAccountIdx(response, 0, false);
		},
		error: ajaxError
	});
}

function vote(provider){
	jQuery.post('/status/index.php?vote', {provider: provider}, function(data){
		if(data == 'ok'){
			var votes = parseInt($('#cvotes' + provider).html());
			$('#cvotes' + provider).html(votes + 1);
			$('#votes' + provider).html('<div class="support"></div>');
		}
		else{
			$('#votes' + provider).html('error');
		}
	});
}

function resetVotes(providerID){
	if(confirm('Are you sure?'))
		$.post('/status/index.php?resetVotes', {providerID: providerID}, function(data){
			if(data == 'ok'){
				$('#resetVotes' + providerID).html('OK');
				$('#cvotes' + providerID).html(0);
			}
			else{
				$('#resetVotes' + providerID).html('error');
			}
		});
}

function changeProviderState(action, newState, providerID, position, providerName){
	if (typeof newState == 'undefined')
		newState = document.getElementById('newState').value;
	if (typeof providerID == 'undefined')
		providerID = document.getElementById('providerID').value;
	var regExp = /([0-9]+(,[0-9]+)*)/;
	
	if (newState == 'fixed' || newState == 'added'){
		if (action == 'openDialog'){
			drawAddUserIDBox(newState, providerID, position, regExp, providerName);
		}
		else if (action == 'addUsers'){
			if (checkCorrectInput(document.getElementById('userIDs').value, regExp)){
				markAsEvent(newState, providerID, false, document.getElementById('userIDs').value);
			}
		}
		else if (action == 'noThanks'){
			markAsEvent(newState, providerID);
		}
		else if (action == 'dontSendEmails'){
			markAsEvent(newState, providerID, true);
		}
		else if (action == 'justLink'){
			if (checkCorrectInput(document.getElementById('userIDs').value, regExp)){
				document.getElementById('justLink').innerHTML = 'http://awardwallet.com' + makeALink(newState, providerID, false, document.getElementById('userIDs').value);
				document.getElementById('justLink').style.display = 'block';
				document.getElementById('example').style.display = 'none';
				document.getElementById('note').style.display = 'none';
			}
		}
	}
	else if (newState == 'broken')
			markAsEvent(newState, providerID, false);
}

function checkCorrectInput(string, regExp){
	if (string.match(regExp) || (string == '')){
		return true;
	}
	else{
		alert('Incorrect format');
		return false;
	}
}

function drawAddUserIDBox(newState, providerID, position, regExp, providerName){
	var box = document.getElementById('askUserIDBox');
	var pos = $(position).offset();
	box.style.top = pos.top + $(position).height() + 5 + 'px';
	box.style.left = pos.left - $(box).width() + $(position).width() + 'px';
	document.getElementById('newState').value = newState;
	document.getElementById('providerID').value = providerID;
	if (providerName)
		document.getElementById('providerName').innerHTML = providerName;
	document.getElementById('userIDs').value = '';
	document.getElementById('regExp').innerHTML = regExp;
	document.getElementById('justLink').style.display = 'none';
	document.getElementById('example').style.display = 'block';
	document.getElementById('note').style.display = 'block';
//	$('#userIDs').keypress(function(event){if(event.keyCode == Event.KEY_RETURN) return true;});
	showPopupWindow($('#askUserIDBox').get(0), true, function(){document.getElementById('userIDs').focus()});
}

function makeALink(newState, providerID, dontSendEmails, addUsers){
	link = '/manager/voteMailer.php?Action=' + newState + '&ID=' + providerID;
	if (dontSendEmails == true){
		link += '&DontSendEmails=1';
	}
	if (addUsers != ''){
		link += '&addUserID='+addUsers;
	}
	link += '&state=notset';
	return link;
}

function markAsEvent(newState, providerID, dontSendEmails, addUsers){
	link = makeALink(newState, providerID, dontSendEmails, addUsers);
	$.post(link, {post: 1}, function(data){
        setTimeout(function(){ window.location.reload(); }, 2000);
	});
}

$(document).ready(function(){
	var timer;
	$(".iconLinksBlock .iconLink").hover(
		function(){
			var obj = this;
			var hand = function(){
				$(obj).children(".title").fadeIn(400);
			};
			timer = setTimeout(hand,600);
		},
		function(){					
			clearTimeout(timer);
			$(this).children(".title").css('display','none');
		}
	);
});

function deleteAccountConfirmation(accountId){
	if(window.confirm( 'Delete account?')){
		if(browserExt.installed)
			browserExt.deleteAccount(accountId);
		showMessagePopup('info', 'Deleting..', 'Please wait..', true);
		$.ajax({
			url: '/account/delete.php',
			type: 'POST',
			data: {ID: accountId},
			success: function(){document.location.reload();},
			error: ajaxError
		});
		return false;
	}
	else
		return false;
}

function deleteCouponConfirmation(couponId){
	if(window.confirm( 'Delete voucher/coupon?')){
		showMessagePopup('info', 'Deleting..', 'Please wait..', true);
		$.ajax({
			url: '/coupon/delete.php',
			type: 'POST',
			data: {ID: couponId},
			success: function(){document.location.reload();},
			error: ajaxError
		});
		return false;
	}
	else
		return false;
}

function deletePendingAccount(accountId, all) {
	var url;
	if (all)
		url = '/account/deletePending.php';
	else
		url = '/account/delete.php';
	showMessagePopup('info', 'Deleting..', 'Please wait..', true);
	$.ajax({
		url: url,
		type: 'POST',
		data: {ID: accountId},
		success: function () {
			document.location.reload();
		},
		error: ajaxError
	});
	return false;
}

function skipPendingAccount(accountId, all) {
	var exp = new Date();
	exp.setTime(exp.getTime() + 30 * 60 * 1000);
	if (all)
		setCookie("skipPendingAll", "1", exp);
	else
		setCookie("skipPending" + accountId, "1", exp);
	document.location.reload();
	return false;
}

/*
Author: Robert Hashemian
http://www.hashemian.com/

You can use this code in any manner so long as the author's
name, Web address and this disclaimer is kept intact.
********************************************************
*/
// function to format a number with separators. returns formatted number.
// num - the number to be formatted
// decpoint - the decimal point character. if skipped, "." is used
// sep - the separator character. if skipped, "," is used
function formatNumberBy3(num, decpoint, sep) {
	// check for missing parameters and use defaults if so
	if (arguments.length == 2) {
		sep = ",";
	}
	if (arguments.length == 1) {
		sep = ",";
		decpoint = ".";
	}
	// need a string for operations
	num = num.toString();
	// separate the whole number and the fraction if possible
	a = num.split(decpoint);
	x = a[0]; // decimal
	y = a[1]; // fraction
	z = "";


	if (typeof(x) != "undefined") {
		// reverse the digits. regexp works from left to right.
		for (i = x.length - 1; i >= 0; i--)
			z += x.charAt(i);
		// add seperators. but undo the trailing one, if there
		z = z.replace(/(\d{3})/g, "$1" + sep);
		if (z.slice(-sep.length) == sep)
			z = z.slice(0, -sep.length);
		x = "";
		// reverse again to get back the number
		for (i = z.length - 1; i >= 0; i--)
			x += z.charAt(i);
		// add the fraction back in, if it was there
		if (typeof(y) != "undefined" && y.length > 0)
			x += decpoint + y;
	}
	return x;
}

function browserDetectNav(forExtension){
var
    UA = window.navigator.userAgent,
    //--------------------------------------------------------------------------------
    OperaB = /(Opera|OPR)[ \/]+\w+\.\w+/i,
    OperaV = /Version[ \/]+\w+\.\w+/i,
    FirefoxB = /Firefox\/\w+\.\w+/i,
    ChromeB = /Chrome\/\w+\.\w+/i,
    SafariB = /Version\/\w+\.\w+/i,
    IEB = /MSIE *\d+\.\w+/i,
    SafariV = /Safari\/\w+\.\w+/i,
	iPad = /(iPad|iPhone|iPod).*Safari/i,
	aolDesktop = /AOL (\d)\.(\d)/i,
	MaxthonB = /Maxthon\/(\w+)\.(\w+)/i,
    Edge = /Edge\/\w+\.\w+/i,
	//--------------------------------------------------------------------------------
    browser = [],
    browserSplit = /[ \/\.]/i,
    OperaV = UA.match(OperaV),
    Firefox = UA.match(FirefoxB),
    Chrome = UA.match(ChromeB),
    Safari = UA.match(SafariB),
    SafariV = UA.match(SafariV),
    IE = UA.match(IEB),
    Opera = UA.match(OperaB),
	Maxthon = UA.match(MaxthonB);
    Edge = UA.match(Edge);

    if(Edge) browser[0] = Edge[0];
	else
        //----- Opera ----
        if(Maxthon) browser[0] = Maxthon[0];
        else
            if ((!Opera == "") && (!OperaV == "")) browser[0] = OperaV[0].replace(/Version/, "Opera");
            else
                if (!Opera == "") browser[0] = Opera[0].replace(/OPR/, "Opera");
                else
                    //----- IE -----
                    if (!IE == "") browser[0] = IE[0];
                    else
                        //----- Firefox ----
                        if (!Firefox == "") browser[0] = Firefox[0];
                        else
                            //----- Chrome ----
                            if (!Chrome == "") browser[0] = Chrome[0];
                            else
                                //----- Safari ----
                                if ((!Safari == "")&&(!SafariV == "")) browser[0] = Safari[0].replace("Version", "Safari");

    if (browser[0] != null) outputData = browser[0].split(browserSplit);
	// IE 11
	if (navigator.appName == 'Netscape') {
		var ua = navigator.userAgent;
		var re = new RegExp("Trident/.*rv:([0-9]{1,})");
		if (re.exec(ua) != null){
			rv = parseFloat(RegExp.$1);
			outputData = ['MSIE', rv, 0];
            
			try {
                var isActiveX = !!new ActiveXObject('htmlfile');
            } catch (e) {
                var isActiveX = false;
            }
            if(Math.round(outputData[1]) == 11 && UA.match(/Windows NT 6\.3/i) && (!UA.match(/WOW64/i) && UA.match(/Win64/i) || !isActiveX)){
                outputData[0] = 'MSIE Metro UI';
            }
            return outputData;
		}
	}
	var outputData;
    if (outputData != null){
		chrAfterPoint = outputData[2].length;
		outputData[2] = outputData[2].substring(0, chrAfterPoint);
		// correct iPad
		if(outputData[0] == 'Safari' && UA.match(iPad))
			outputData[0] = 'Mobile Safari';

		if(forExtension && outputData[0] != "Edge"){
			if(outputData[0] == 'Safari' && Math.round(Math.round(outputData[1]) * 10 + Math.round(outputData[2])) < 51)
				outputData[0] = 'OldSafari';
			if(outputData[0] == 'Firefox' && Math.round(outputData[1]) < 5)
				outputData[0] = 'OldFirefox';
			if(outputData[0] == 'Chrome' && Math.round(outputData[1]) < 18)
				outputData[0] = 'OldChrome';
			if(outputData[0] == 'MSIE' && Math.round(outputData[1]) < 8)
				outputData[0] = 'OldIE';
			if(outputData[0] == 'MSIE' && UA.match(aolDesktop)){
				match = aolDesktop.exec(UA);
				outputData[0] = 'AOL Desktop';
				outputData[1] = match[1];
				outputData[2] = match[2];
			}
		}
		
		return(outputData);
	}
		else return(false);
}

function applyToBeta(){
	data = browserDetectNav(true);
	if(data[0] == 'Chrome' || data[0] == 'Firefox' || data[0] == 'Safari')
		document.location.href = '/participateInBeta/approve.php?BackTo=' + encodeURIComponent(document.location.href);
}

function checkHtml5Form(form){
	var result = {'error': '', 'element': ''};
	this.addError = function(text, elem) {
	 	result.error = text;
	 	result.element = elem;
	};
	this.getLabel = function(field) {
	 	return $(field).closest('tr[id^="tr"]').find('.labelText').text();
	};
	this.setFocus = function(field) {
	 	return $(field).focus();
	};
	this.sendMessage = function(message) {
	 	alert(message);
	};
	$(form).find('input, select, textarea').each(function(i,elem) {
		var type = 'unknown';
		if (typeof $(elem).attr('type') !== 'undefined')
			type = $(elem).attr('type');
		if (type == 'hidden' || type == 'submit')
			return;
		if (typeof $(elem).attr('required') !== 'undefined' && $.trim($(elem).val()) == '') {
			self.addError('Field "'+ self.getLabel(elem) +'" is required', elem);
			return false;
		}
	});
	if (result.error != '') {
		self.setFocus(result.element);
		self.sendMessage(result.error);
		return false;
	}
	
	return true;
}

function reloadPage(){
	var url = document.location.pathname + document.location.search;
	var date = new Date();
	var time = date.getTime() + 5 * 1000;
	date.setTime(time);
	if(url.match(/r=/))
		url = url.replace(/r=\d+/, "r=" + date.getTime());
	else{
		if(url.match(/\?/))
			url = url + '&';
		else
			url = url + '?';
		url = url + "r=" + date.getTime();
	}
	setCookie('scrollTop', $(window).scrollTop(), date, '/');
	document.location.href = url;
}

function reloadPageContent(){
	$.ajax({
		url: document.location.href,
		type: 'GET',
		headers: {'Content-Only': 'true'},
		success: function(content){
			$('#contentTableCell').html(content);
		}
	});
}

function uncheckNdr(obj){
	$(obj).find('input').attr('disabled','');
	$('#newEmailStat').show();
	$('#newEmailStat td').html('Please wait...');
	$('#newEmailStat td').css('color','#000');
	$.ajax({
		url: '/user/uncheckNdr.php',
		type: 'POST',
		data: {email: $('input[name=newEmail]').val()},
		success: function(response){
			if(response != 'OK'){	
				$(obj).find('input').removeAttr('disabled');
				$('#newEmailStat td').html(response);
				$('#newEmailStat td').css('color','#E21616');
				return;
			}
			reloadPage();
		},
		error: ajaxError
	});
}

var allDatepickers = [];
var dateOptions;
function activateDatepickers(action){	
	if(action){
		if(action == 'active')
			for(i = 0; i < allDatepickers.length; i++){				
				$( 'input[name='+allDatepickers[i].name+']' ).datepicker(allDatepickers[i].options);				
			}
		else if(action == 'destroy'){
			for(i = 0; i < allDatepickers.length; i++){
				$( 'input[name='+allDatepickers[i].name+']' ).datepicker('destroy');
			}
		}
	}
}

function buttonAjax(obj, url, params, requestType, method, success, before){
    if(!params)      params = {};
    if(!requestType) requestType = 'json';
    if(!method)      method = 'POST';
    
    var id = [];        
    this.addImage = function(obj, item) {
        id[item] = $(obj).id;
        var objWidth = $(obj).width();
        var objHeight = $(obj).height();
        var objPaddingLeft = parseFloat($(obj).css('padding-left').replace(/px/,''));
        var objPaddingRight = parseFloat($(obj).css('padding-right').replace(/px/,''));
        var imageLeft = (objWidth+objPaddingLeft+objPaddingRight)/2 - 8;
        var imageTop = objHeight/2 - 8;
        $(obj).find('input').attr('disabled','disbled');
        $(obj).append('<img id="progress_'+id[item]+'" class="progressImage" src="/lib/images/progressCircle.gif" style="left:'+imageLeft+'px; top:'+imageTop+'px" />');
    };    
    this.removeImage = function(obj, item) {
        $(obj).find('input').removeAttr('disabled','');
        $('#progress_'+id[item]).remove();
    };
    
    if(typeof(type) == 'undefined')
        type = 'text';
    
    if(obj){
        if(obj[0] == 'isArray'){   
            for(i in obj){
                if(i != 0)
                    this.addImage(obj[i], i);
            }            
        } else {
            this.addImage(obj, 1);
        }
    }
    
    var thisObj = this;    
    $.ajax({
    	url:        url,
    	dataType:   requestType,
    	type:       method,
    	data:       params,
    	timeout:    30000,
    	beforeSend: function(){
    		if (typeof before == "function")
    			before();
    	},
    	success:    function(json, textStatus){
    		if (typeof success == "function")
    			success(json);
            if(obj){
                if(obj[0] == 'isArray'){   
                    for(i in obj){
                        if(i != 0)
                            thisObj.removeImage(obj[i], i);
                    }            
                } else {
                    thisObj.removeImage(obj, 1);
                }
            }
    	}			
    });
}

function startAccountOperations(){
	var re = /autologin(\d+)/i;
	var matches = re.exec(document.location.hash);
	if(matches)
		browserExt.pushOnReady(function(){
			$('td#accountCell' + matches[1] + ' a:first').click();
		});
}

function strToValidDate(str, dateType){
    if(dateType == undefined)
        dateType = "DATE_EU";
    if(dateType == "DATE_EU"){
        var matches = str.match(/(\d{2})\/(\d{2})\/(\d{4})/);
        return matches[2]+"/"+matches[1]+"/"+matches[3];
    }
    return str;
}

function flashBackground(cell, color, flashCount, pause){
	var flashes = 0;
	var lightOn = false;
	var switchLight = function(){
		if(!lightOn)
			cell.css('background-color', color);
		else
			cell.css('background-color', '');
		lightOn = !lightOn;
		flashes++;
		if(flashes < flashCount)
			setTimeout(switchLight, pause);
	};
	setTimeout(switchLight, pause);
}

function emptyObject(obj) {
    for (var i in obj) {
        return false;
    }
    return true;
}

var varMarkAsFollow         = 'Follow up';
var varMarkAsUnfollow       = 'Undo follow up';
var varMarkAsApply          = 'Mark as applied';
var varMarkAsUnapply        = 'Unmark as applied';

var markDealMsg;
markDealMsg = {
    'Apply': [],
    'Follow': []
};

function markDeal(dealID, status, action) {
    if (typeof(markDealMsg[action][dealID]) != 'undefined')
        status = markDealMsg[action][dealID];
    var varMarkAsAction;
    var varMarkAsUnaction;
    switch(action){
        case 'Apply': varMarkAsAction = varMarkAsApply; varMarkAsUnaction = varMarkAsUnapply; break;
        case 'Follow': varMarkAsAction = varMarkAsFollow; varMarkAsUnaction = varMarkAsUnfollow; break;
    }
    buttonAjax($('#mark' + action + '_' + dealID), "../../promos/mark" + action + ".json", {dealID: dealID, status:status}, 'json', 'POST', function(data) {
        if (data.content != 'OK') {
            alert(data.error);
        } else {
            if (status == 1) {
                $('#dealHead_' + dealID).removeClass('listItem' + action).removeClass("listItemDouble");
                $('#mark' + action + '_' + dealID).removeClass('mark' + action + 'Off').addClass('mark' + action + 'On');
                $('#mark' + action + '_' + dealID + ' input').val(varMarkAsAction);
                markDealMsg[action][dealID] = 0;
            } else {
                $('#dealHead_' + dealID).addClass('listItem' + action);
                if($('#dealHead_' + dealID).hasClass('listItemApply') && $('#dealHead_' + dealID).hasClass('listItemFollow'))
                    $('#dealHead_' + dealID).addClass('listItemDouble');
                $('#mark' + action + '_' + dealID).addClass('mark' + action + 'Off').removeClass('mark' + action + 'On');
                $('#mark' + action + '_' + dealID + ' input').val(varMarkAsUnaction);
                markDealMsg[action][dealID] = 1;
            }
        }
    });
}

function forgotPassword(){
    url = '/security/forgotPassword.php';
    showFrame(url, 420, "Forgot password?", function(){
        var frame = document.getElementById('popupFrame');
        var frameDoc;
        if(frame.contentWindow)
            frameDoc = frame.contentWindow.document;
        else
            frameDoc = frame.contentDocument;
        frameDoc.getElementById('fldEmail').focus();
    });
}

function waitAjaxRequest(url){
	showMessagePopup('info', 'Updating...', 'Processing, please wait...', true); /*review*/
	$.ajax({
		type: 'POST',
		url: url,
		success: reloadPage
	});
}

function autoLoginAccountById(accountId){
	if(!browserExt.supportedBrowser())
		// allow open link in new window
		return true;
	showMessagePopup('info', 'Auto-login', 'We are attempting to automatically log you in, please wait...', false, 'Cancel'); /*review*/
	onPopupCancelled = function(){
		browserExt.cancel();
	};
	browserExt.requireValidExtension();
	browserExt.pushOnReady(function(){
		browserExt.autoLoginAccountById(
			accountId,
			function(){
				cancelPopup();
			},
			function(){
				cancelPopup();
			}
		);
	});
	return false;
}