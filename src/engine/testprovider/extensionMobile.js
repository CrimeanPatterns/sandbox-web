var plugin = {

    flightStatus: {
        url: 'about:blank',
        match: /.+/i,
        reload: true,

        start: function(){
            var html = '<meta name="viewport" content="width=device-width">';
            html += '<meta name="format-detection" content="telephone=no">';
            html += '<style>*{word-wrap:break-word;}</style>';
            html += JSON.stringify(params);
            html += '<hr>';
            html += 'Flight Number: <b>'+params.flightNumber+'</b><br>';
            html += 'Dep Code: <b>'+params.depCode+'</b><br>';
            html += 'Arr Code: <b>'+params.arrCode+'</b><br>';
            var date = api.getDepDate();
            var dateStr = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);
            html += 'Dep Date: <b>'+dateStr+'</b><br>';
            document.write(html);
            this.finish();
        },

        finish: function(){
            api.complete();
        }
    },

    autologin: {
        url: 'about:blank',

        start: function(){
            var html = '<meta name="viewport" content="width=device-width">';
            html += '<meta name="format-detection" content="telephone=no">';
            html += '<style>*{word-wrap:break-word;}</style>';
            html += JSON.stringify(params);
            html += '<hr>';
            html += 'Login: <b>'+params.login+'</b><br>';
            html += 'Pass: <b>'+params.pass+'</b><br>';
            document.write(html);
            this.finish();
        },

        finish: function(){
            api.complete();
        }
    }
};