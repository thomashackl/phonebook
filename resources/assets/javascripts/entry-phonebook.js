__webpack_public_path__ = window.STUDIP.ABSOLUTE_URI_STUDIP + 'plugins_packages/upa/Phonebook/assets/'

import Vue from 'vue'
import GetTextPlugin from 'vue-gettext'
import translations from 'locale/en/LC_MESSAGES/phonebook.json'

Vue.use(GetTextPlugin, {
    defaultLanguage: 'de_DE',
    availableLanguages: {
        en_GB: 'British English'
    },
    muteLanguages: ['de_DE'],
    translations: translations
})

window.Vue = Vue

Vue.config.language = String.locale

/**
 * The following block of code is used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

const files = require.context('../../vue/components', true, /Phonebook|PhonebookManualEntry$/i)

files.keys().map(key =>
    Vue.component(
        key
            .split('/')
            .pop()
            .split('.')[0],
        files(key).default
    )
);
