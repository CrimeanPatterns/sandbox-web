(function( $ ){
    $.fn.nowraptext = function(options) {
        var settings = $.extend({
            'minFontSize': '6px',
            'step': 0.3,
            'padding': 0
        }, options);

        return this.each(function(){
            var $this = $(this);
            var reducer = function() {
                var max = $this.parent().width();
                var fullsize = $this.data('fullsize');
                var fontsize = parseFloat($this.css('font-size'));
                if ($this.width() + settings.padding > max){
                    do {
                        fontsize -= settings.step;
                        $this.css('font-size', fontsize);
                    } while ($this.width() + settings.padding > max)
                    if (typeof(fullsize) == 'undefined')
                        $this.data('fullsize', fontsize);
                } else if (typeof(fullsize) != 'undefined') {
                    while (parseInt($this.css('font-size')) < fullsize && $this.width() + settings.padding < max) {
                        fontsize += settings.step;
                        $this.css('font-size', fontsize);
                    }
                }
            };
            reducer();
            //$(window).on('resize', reducer);
        });
    };
})( jQuery );