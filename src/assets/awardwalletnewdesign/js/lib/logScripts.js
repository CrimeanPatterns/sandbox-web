
$( document ).ready(function() {

    var BLOCKS = [
        'Account Check Parameters',
        'Load Login Form',
        'Login',
        'Parse',
        'Parse Itineraries',
        'Parse History',
        'Account Check Result',
    ];

    // renderer json-viewer
    (function () {
        $('.json-renderer').each(function () {
            renderJson(this);
        });

        // Generate on option change
        $('p.options input[type=checkbox]').each(function () {
            $(this).on('click', function () {
                if ($(this).attr('data-type') === 'collapsed') {
                    $(this).parent().next().children(':first').prop('checked', false);
                    $(this).parent().next().next().next().next().children(':first').prop('checked', false);
                }
                if ($(this).attr('data-type') === 'collapsed-depth') {
                    $(this).parent().prev().children(':first').prop('checked', false);
                    $(this).parent().next().next().next().children(':first').prop('checked', false);
                }
                if ($(this).attr('data-type') === 'except') {
                    $(this).parent().prev().prev().prev().children(':first').prop('checked', false);
                    $(this).parent().prev().prev().prev().prev().children(':first').prop('checked', false);
                }
                renderJson($(this).parent().parent().nextAll('.json-renderer'));
            })
        });
        if ($('input[data-type="except-value"]').length > 0)
            $('input[data-type="except-value"]').keypress(function (event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    var $chb = $(this).prev();
                    if (!$chb.prop('checked'))
                        $chb.trigger('click');
                    else
                        renderJson($(this).parent().parent().nextAll('.json-renderer'));
                }
            });

    })();

    // Expand / collapse
    (function() {
        $('b:contains("response headers:"), b:contains("request headers:")').each(function(){
            var $this = $(this);
            $this.next().next('div').hide();
            $this.after('<b class="expand"> [+]</b>');
        });

        $('.collapse, .expand').click(function() {
            var $this = $(this);
            if ($this.hasClass('collapse')) {
                $this.next().next('div').hide();
                $this.removeClass('collapse');
                $this.addClass('expand');
                $this.text(' [+]');
            } else if ($this.hasClass('expand')) {
                $this.next().next('div').show();
                $this.removeClass('expand');
                $this.addClass('collapse');
                $this.text(' [-]');
            }
        });
    })();

    // unixtime extended dateString for json-renderer
    $('.json-literal').filter(function () {
        var tt = $(this).text().match(/^\d{10}$/);
        if (tt) {
            return tt.length > 0;
        }
        return false;
    }).each(function () {
        var tt = new Date($(this).text() * 1000);
        tt = tt.toUTCString().replace(' GMT','').replace(/(\d{4}) (\d{2}:\d{2})/,'$1 - $2');
        $(this).get(0).innerHTML = $(this).text()+'&nbsp;<span style="color:#85b0ff;font-weight: normal">// '+tt+'</span>';
    });

    if ($('div.tabcontent').length === 0) {
        return;
    }

    // Create table of contents
    (function() {
        $(':header').each(function() {
            const $this = $(this);
            const tagName = $this.prop('tagName');
            if (    $this.attr('class') === 'awlog-nolink' ||
                tagName === 'H1'     )
                return;
            if ($this.prop('class').indexOf('awlog') === -1)
                return;
            const id = $this.text().replace(/[^\w_]/g, '', -1) + 'LinkId';
            $this.attr("id", id);
            const headerLevel = tagName.match(/\d/)[0];
            const prefix = Array((headerLevel - 2) * 4).join('&nbsp;');
            $('#awlog-contents').append(prefix + '<a class="plainlink" href=#' + id + '>' + $this.text() + '</a><br>');
        });
        $('.plainlink').click(function() {
            var $text = $(this).text();
            var tabId = '';
            if ($text === 'Account Check Parameters' || $text === 'Load Login Form' || $text === 'Login' || $text === 'Parse') {
                tabId = 'LoadLoginParseTabId';
            } else {
                tabId = $text.replace(/ /g, '', -1) + 'TabId';
            }
            if ($text === 'Account Check Result')
                return;

            // active tab logic
            var activeTab = $('ul.tab a.active').text();
            if      (activeTab === 'All')
                return;
            else if ($text.match(/Parse Itinerary #/i)) {
                if (activeTab === 'Parse Itineraries')
                    return;
                $('ul.tab a:contains("Parse Itineraries")').click();
                $(this).click();
            }
            else if (isBlock($text) && $(this).attr('href').endsWith('0')) {
                $('a[href = "#' + tabId + '"]').click();
            } else {
                $('ul.tab a:contains("All")').click();
                $(this).click();
            }
        });
    })();

    function isBlock(text) {
        return BLOCKS.indexOf(text) > -1;
    }

    // Enumerate steps for retries
    (function() {
        var headers = $(':header.awlog-info');
        headers = $(headers.get().reverse());
        var retryIndex = 0;
        headers.get().forEach(function (header) {
            var id = $(header).attr('id');
            $(header).attr('id', id + retryIndex.toString());
            if (id.match(/AccountCheckParameters/)) {
                retryIndex += 1;
            }
        });

        var links = $('#awlog-contents .plainlink');
        links = $(links.get().reverse());
        retryIndex = 0;
        links.get().forEach(function (link) {
            var href = $(link).attr('href');
            $(link).attr('href', href + retryIndex.toString());
            if (href.match(/AccountCheckParameters/)) {
                retryIndex += 1;
            }
        });
    })();

    // Create tab menu
    // depends on enumerate above
    (function() {
        $('body').prepend('<ul class="tab"></ul>');
        var $ul = $('ul.tab');

        var link = '<li><a href="#LoadLoginParseTabId" class="tablink">Load-Login-Parse</a></li>';
        $ul.append(link);
        $(':header').each(function(){
            var $this = $(this);
            if (!isBlock($this.text()))
                return;
            if (!$(this).prop('id').match(/0$/))
                return;

            var id = $(this).text().replace(/ /g, '', -1) + 'TabId';
            $this.parent('div').prop('id', id);
            var link = '<li><a href="#' + id + '" class="tablink">' + $this.text() + '</a></li>';
            $ul.append(link);
        });

        link = '<li><a href="#AllTabId" class="tablink">All</a></li>';
        $ul.append(link);

        $('a[href = "#AccountCheckParametersTabId"]').parent('li').hide();
        // $('a[href = "#AccountCheckResultTabId"]').parent('li').hide();
        $('a[href = "#LoadLoginFormTabId"]').parent('li').hide();
        $('a[href = "#LoginTabId"]').parent('li').hide();
        $('a[href = "#ParseTabId"]').parent('li').hide();
    })();


    // Tab switch logic
    (function() {
        $('ul.tab a').click(function(e){
            e.preventDefault();
            $('html, body').scrollTop(0);

            $('div.tabcontent').hide();
            $('a.tablink').removeClass('active');
            $($(this).attr('href')).show();
            $(this).addClass('active');

            $('#AccountCheckResultTabId').show();

            if ($(this).attr('href') === '#LoadLoginParseTabId') {
                $('#AccountCheckParametersTabId').show();
                $('#LoadLoginFormTabId').show();
                $('#LoginTabId').show();
                $('#ParseTabId').show();
            }

            if ($(this).attr('href') === '#AllTabId') {
                $('div.tabcontent').show();
            }
        });

        var retries = $('.awlog-notice').text().match(/Checker signalized that retry is needed/i);
        if (retries) {
            $('a[href = "#AllTabId"]').click();
        } else {
            $('a[href = "#LoadLoginParseTabId"]').click();
        }
    }());

    /*
     // Copy itineraries to check result
     (function() {
     var itins = $('#ParseItinerariesTabId').text().match(/Itineraries:([^]+)/);
     if (itins === null)
     return;
     itins = itins[1];
     console.log(itins);

     $('#AccountCheckResultTabId').append('\n<br>\nItineraries:\n<br>\n<pre>\n' + itins + '</pre>\n<br>');
     })();
     */

    // Unescape non-empty hrefs in links
    (function() {
        var links = $('a');
        links.each(function() {
            var link = $(this);
            var text = link.text().trim();
            if (!text)
                return;
            link.text(text.replace('×', '&times'));
        });
    })();
});

function renderJson(e) {
    // remove date converted
    $(e).prev().find('span').remove();
    try {
        var data = JSON.parse($(e).prev().text());
    } catch (ex) {
        $(e).prev().show();
        $(e).prev().prev('p').find('label').hide();
        $(e).append('<span style="color:red;">' + ex.name + ': ' + ex.message + '</span>');
        return;
    }
    // main render
    var collapsedNotRoot = $(e).prevAll('p').find('input[data-type="collapsed"]').is(':checked');
    var options = {
        collapsed: collapsedNotRoot,
        rootCollapsable: true,
        withQuotes: $(e).prevAll('p').find('input[data-type="with-quotes"]').is(':checked'),
        withLinks: $(e).prevAll('p').find('input[data-type="with-links"]').is(':checked')
    };
    $(e).jsonViewer(data, options);
    if (collapsedNotRoot) {
        $(e).find('a:first').click();// open root
    }
    // render except
    var exceptFields = $(e).prevAll('p').find('input[data-type="except-value"]').val();
    if (typeof exceptFields !== "undefined") {
        exceptFields = exceptFields.trim();
        if (exceptFields.length !== 0 && $(e).prevAll('p').find('input[data-type="except"]').is(':checked')) {
            var finded = false;
            var pp, tr;
            $(e).find('li, a').each(function () {
                if ($(this).text() === exceptFields || $(this).text() === '"' + exceptFields + '"') {
                    finded = true;
                    pp = $(this).parentsUntil('pre.json-renderer');
                    pp.each(function () {
                        if (!$(this).hasClass('hasChildField')) {
                            $(this).addClass('hasChildField');
                        }
                        if (($(this).parent().prop('tagName') === 'ul' || $(this).parent().prop('tagName') === 'ol') && $(this).parent().prev().prop('tagName') === 'a' && !$(this).parent().prev().hasClass('hasChildField'))
                            $(this).parent().prev().addClass('hasChildField');
                    });
                }
                tr = new RegExp('^"?' + exceptFields + '"?:');
                if (tr.test($(this).text())) {
                    finded = true;
                    pp = $(this).parentsUntil('pre.json-renderer');
                    pp.each(function () {
                        if (!$(this).hasClass('hasChildField')) {
                            $(this).addClass('hasChildField');
                        }
                        if (!$(this).parent().hasClass('hasChildField')) {
                            $(this).parent().addClass('hasChildField');
                        }
                        if (($(this).parent().prop('tagName') === 'ul' || $(this).parent().prop('tagName') === 'ol')
                            && $(this).parent().prev().prop('tagName') === 'a'
                            && !$(this).parent().prev().hasClass('hasChildField')
                        ){
                            $(this).parent().prev().addClass('hasChildField');

                        }
                    });
                }
            });
            $(e).find('a').each(function () {
                if (!($(this).parent().hasClass('hasChildField')) && !($(this).hasClass('collapsed'))) {
                    $(this).click();
                }
            });
            if (finded && $(e).find('a:first').hasClass('collapsed'))
                $(e).find('a:first').click();// open root
            return;
        }
    }
    // render collapsed-depth
    if ($(e).prevAll('p').find('input[data-type="collapsed-depth"]').is(':checked')) {
        $(e).find('li>a.json-toggle').each(function () {
            var pp = $(this).parentsUntil('pre.json-renderer');
            var len = pp.length;
            var level = pp.parent().attr('data-depth');
            var depth = 3;
            if (typeof level !== "undefined") {
                depth = Number(level);
            }
            if (len % 2 === 0)// couple ul-li | ol-li
                len = len / 2;
            if (len >= depth && !($(this).hasClass('collapsed'))) {
                $(this).click();
            }
        });
    }
}

function textToBuffer(e) {
    var text = e.text();
    if (typeof text !== "undefined" ) {
        var input = document.createElement('textarea');
        document.body.appendChild(input);
        input.value = text;
        input.select();
        document.execCommand("copy");
        document.body.removeChild(input);
    }
}

