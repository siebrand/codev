;(function(namespace, undefined) {

    function MyLocaleLib () {
        this.selectedLocale = undefined;
        this.availableLocales = {};
    }

    MyLocaleLib.prototype.addLocale = function addLocale (locale, messages) {
        if (locale == null || locale == undefined)
        {
            throw new Error('ArgumentNullException: locale');
        }

        if (messages == null || messages == undefined)
        {
            throw new Error('ArgumentNullException: messages');
        }

        if (typeof locale !== 'string' || locale.length === 0)
        {
            throw new Error('InvalidArgumentException: locale parameter should be a non-empty string');
        }

        if (locale in this.availableLocales)
        {
            throw new Error('InvalidArgumentException: locale ' + locale + ' is already set and can not be overridden');
        }

        this.availableLocales[locale] = messages;
    };

    MyLocaleLib.prototype.addMessagesForLocale = function addMessagesForLocale (locale, messages) {
        // TODO
        /*if (locale == null || locale == undefined)
        {
            throw new Error('ArgumentNullException: locale');
        }

        if (messages == null || messages == undefined)
        {
            throw new Error('ArgumentNullException: messages');
        }

        if (typeof locale !== 'string' || locale.length === 0)
        {
            throw new Error('InvalidArgumentException: locale parameter should be a non-empty string');
        }

        if (locale in this.availableLocales)
        {
            throw new Error('InvalidArgumentException: locale ' + locale + ' is already set and can not be overridden');
        }

        this.availableLocales[locale] = messages;*/
    };

    MyLocaleLib.prototype.setLocale = function setLocale (locale) {
        if (locale == null || locale == undefined)
        {
            throw new Error('ArgumentNullException: locale');
        }

        if (typeof locale !== 'string' || locale.length === 0)
        {
            throw new Error('InvalidArgumentException: locale parameter should be a non-empty string');
        }

        if (!(locale in this.availableLocales))
        {
            throw new Error('InvalidArgumentException: ' + locale + ' is not set');
        }

        this.selectedLocale = locale;
    };

    MyLocaleLib.prototype._ = function _ (message) {
        // does the current locale has been defined ?
        if (this.selectedLocale === undefined)
        {
            throw new Error('InvalidOperationException: no locale selected, call setLocale first');
        }

        // does the current locale has been loaded ?
        if (this.availableLocales[this.selectedLocale] === undefined)
        {
            return message;
        }

        // does the message exist in the current locale ?
        if (this.availableLocales[this.selectedLocale][message] === undefined)
        {
            return message;
        }

       // the translation has been found
       return this.availableLocales[this.selectedLocale][message];
    };

    // we expose the API within the namespace
    namespace.MyLocaleLib = new MyLocaleLib();

}(window));

// MyLocaleLib.setLocale("de_DE"); console.log(MyLocaleLib._("Backlog variation"));
// MyLocaleLib.setLocale("fr"); console.log(MyLocaleLib._("Backlog variation"));
// MyLocaleLib.setLocale("pt_BR"); console.log(MyLocaleLib._("Backlog variation"));
