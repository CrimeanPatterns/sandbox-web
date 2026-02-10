define(['jquery-boot', 'jqueryui', 'translator-boot'], function ($) {
    var Dialog = function (element) {
        var icon = element.dialog('option', 'type');
        if(icon)
            element.dialog('widget').find('.ui-dialog-title').prepend(icon);
        this.element = element;
    };
    Dialog.prototype = {
        isOpen: function () {
            return this.element.dialog('isOpen');
        },
        moveToTop: function () {
            this.element.dialog('moveToTop');
        },
        open: function () {
            this.element.dialog('open');
        },
        close: function () {
            this.element.dialog('close');
        },
        destroy: function () {
            this.element.dialog('destroy').remove();
        },
        getOption: function (option) {
            if (typeof(option) == 'undefined')
                return this.element.dialog("option");
            return this.element.dialog("option", option);
        },
        setOption: function (name, value) {
            if (name != null && typeof name == 'object')
                return this.element.dialog("option", extendOptions(name));

            return this.element.dialog("option", name, extendOption(name, value));
        }
    };

    var extendOptions = function (options) {
        options["open"] = options["open"];
        options["close"] = options["close"];
        $.each(options, function (key, value) {
            options[key] = extendOption(key, value);
        });

        return options;
    };
    var extendOption = function (key, option) {
        var o = option;
        switch (key) {
            case "open":
                option = function (event, ui) {
                    $('body').one('click', '.ui-widget-overlay', function () {
                        $('.ui-dialog:visible .ui-dialog-content').each(function(){
                            if ($(this).is(':data(uiDialog)') && $(this).dialog("isOpen")) {
                                $(this).dialog("close")
                            }
                        });
                    });
                    $(window).off('resize.dialog').on('resize.dialog', function () {
                        $(event.target).dialog("option", "position", {
                            my: "center",
                            at: "center",
                            of: window
                        });
                    });
                    (o || function () {
                    })(event, ui);
                };
                break;
            case "close":
                option = function (event, ui) {
                    $(window).off('resize.dialog');
                    (o || function () {
                    })(event, ui);
                };
                break;
            case "type":
                if (option && !(option instanceof Object)) {
                    option = option ? '<i class="icon-' + option + '-small"></i>' : null;
                }
                break;
        }
        return option;
    };
    return {
        dialogs: {},
        createNamed: function (name, elem, options) {
            options = extendOptions(options);
            return this.dialogs[name] = new Dialog(
                elem.dialog(options)
            );
        },
        has: function (name) {
            return typeof(this.dialogs[name]) != 'undefined';
        },
        get: function (name) {
            return this.dialogs[name];
        },
        remove: function (name) {
            if (!this.has(name)) return;
            this.get(name).destroy();
            delete this.dialogs[name];
        },
        fastCreate: function (title, content, modal, autoOpen, buttons, width, height, type) {
            var element, options;
            if (content != null && typeof content == 'object' && typeof title != 'undefined') {
                element = $('<div>' + title + '</div>');
                options = extendOptions(content);
                return new Dialog(element.dialog(options));
            }
            element = $('<div>' + (content || '') + '</div>');
            options = extendOptions({
                autoOpen: autoOpen || true,
                modal: modal || true,
                buttons: buttons || [],
                width: width || 300,
                height: height || 'auto',
                title: title || null,
                type: type || null,
                close: function () {
                    $(this).dialog('destroy').remove();
                }
            });
            var d = new Dialog(element.dialog(options));
            d.setOption("close", function() {
                d.destroy();
            });
            return d;
        },
        alert: function (text, title) {
            var element = $('<div>' + text + '</div>'),
                options = {
                    autoOpen: true,
                    modal: true,
                    buttons: [{
                        'text': Translator.trans('button.ok'),
                        'click': function () {
                            $(this).dialog("close");
                        },
                        'class': 'btn-silver'
                    }],
                    closeOnEscape: true,
                    draggable: true,
                    resizable: false,
                    width: 300,
                    height: 'auto',
                    title: title || '',
                    close: function () {
                        $(this).dialog('destroy').remove();
                    }
                };

            return new Dialog(element.dialog(options));
        },
        prompt: function (text, title, nocallback, yescallback) {
            var element = $('<div>' + text + '</div>'),
                options = {
                    autoOpen: true,
                    modal: true,
                    buttons: [
                        {
                            'text': Translator.trans('button.no'),
                            'click': function () {
                                $(this).dialog("close");
                                nocallback();
                            },
                            'class': 'btn-silver'
                        },
                        {
                            'text': Translator.trans('button.yes'),
                            'click': function () {
                                $(this).dialog("close");
                                yescallback();
                            },
                            'class': 'btn-blue'
                        }
                    ],
                    closeOnEscape: true,
                    draggable: true,
                    resizable: false,
                    width: 600,
                    height: 'auto',
                    title: title || '',
                    close: function () {
                        $(this).dialog('destroy').remove();
                    }
                };

            return new Dialog(element.dialog(options));
        }
    };
});