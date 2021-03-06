define("cool/translator",
	[
        "dojo/_base/declare",
        "dojo/_base/lang",
        "dojo/request"
], function(declare, lang, request) {
 
    return declare("cool.translator", [], {

        locale: false,
        domain: false,

        constructor: function(/*Object*/ args){
            lang.mixin(this, args);
            if(!this.locale) {
                this.locale = dojoConfig.locale;
            }
        },

        trans: function(id, parameters, domain, locale) {
            parameters = parameters || {};
            locale = locale || this.locale;
            domain = domain || this.domain;

            var translatedMessage = Translator.trans(id, {}, domain, locale);
            var hasMessage =  translatedMessage != id;

            if(hasMessage)
                return Translator.trans(id, parameters, domain, locale);
            else {

                var url = Routing.generate('getTranslation', {locale:locale, domain:domain, id:id});

                console.warn('Fetching translation for "'+domain+'/'+id+'", consider rebuilding the cool_translations.js file');

                var ret;

                request(url, {
                    handleAs: 'json',
                    sync: true
                }).then(
                    function(data){
                        ret = data;
                        if(ret == translatedMessage)
                            console.warn('The translation for "'+domain+'/'+id+'" is the same string as the id : '+translatedMessage+' this causes unnecessary server request. Please translate it with something else.');
                    },
                    function(error){
                        console.log("An error occurred: " + error);
                    }
                );

                return Translator.trans(ret, parameters, domain, locale);
            }
        }

    });
 
});