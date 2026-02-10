define(['jquery-boot'], function(){

    /** start **/
    function CallbackManager() {
        var self = this;

        this.callbacks = {};

        this.add = function (callback) {
            var callbackId = Math.random().toString(36).substring(7);
            clean();
            this.callbacks[callbackId] = callback;
            return callbackId;
        };

        this.fire = function (callbackId, params) {
            var arrayParams;

            if (typeof(params) === 'object' && params !== null)
                arrayParams = $.map(params, function (value, index) {
                    return [value];
                });
            else
                arrayParams = [params];
            this.callbacks[callbackId].apply(this, arrayParams);
        };

        function clean() {
            var n = 0;
            for (var key in self.callbacks) {
                if (self.callbacks.hasOwnProperty(key)) {
                    if (n > 50)
                        delete self.callbacks[key];
                    n++;
                }
            }
        }
    }
    /** end **/

    return CallbackManager;

});

