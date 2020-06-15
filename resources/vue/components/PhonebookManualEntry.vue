<template>
    <form class="default" @submit.prevent="storeEntry">
        <fieldset>
            <legend>
                <translate>Grunddaten</translate>
            </legend>
            <section>
                <label for="entry-name">
                    <span class="required">
                        <translate>Name</translate>
                    </span>
                </label>
                <input id="entry-name" type="text" size="75" maxlength="255" v-model="name">
            </section>
            <section>
                <label for="entry-phone">
                    <span class="required">
                        <translate>Telefonnummer</translate>
                    </span>
                </label>
                <input id="entry-phone" type="text" size="75" maxlength="255" v-model="phone">
            </section>
        </fieldset>
        <fieldset>
            <legend>
                <translate>Zuordnung</translate>
            </legend>
            <section>
                <label for="range">
                    <translate>Organisatorische Zuordnung dieser Telefonnummer:</translate>
                </label>
                <template v-if="ranges.length == 0">
                    <input type="text" id="range" v-model="searchterm" @keypress="catchEnter">
                    <studip-icon shape="search" height="20" width="20"
                                 @click="searchRange"></studip-icon>
                </template>
                <template v-else>
                    <select id="range" v-model="range">
                        <option v-for="one in ranges" :value="one.id">
                            <translate v-if="one.type === 'user'">Person:</translate>
                            <translate v-if="one.type === 'institute'">Einrichtung:</translate>
                            {{ one.name }}
                        </option>
                    </select>
                    <studip-icon shape="decline" height="20" width="20"
                                 @click="clearRange"></studip-icon>
                </template>
            </section>
        </fieldset>
        <footer data-dialog-button>
            <studip-button class="accept" name="add" :label="acceptText"></studip-button>
            <studip-button class="cancel" name="cancel" :label="cancelText" data-dialog-close></studip-button>
        </footer>
    </form>
</template>

<script>
    import StudipButton from './StudipButton'
    import StudipIcon from "./StudipIcon";

    export default {
        name: 'PhonebookManualEntry',
        components: {
            StudipIcon,
            StudipButton
        },
        props: {
            dialog: {
                type: Boolean,
                default: true
            },
            institutes: {
                type: Array
            },
            entry: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                name: this.entry.name,
                phone: this.entry.phone,
                range: this.entry.range_id,
                ranges: [],
                searchterm: '',
                placeholderText: this.$gettext('Einrichtung oder Person suchen'),
                acceptText: this.$gettext('Speichern'),
                cancelText: this.$gettext('Abbrechen')
            }
        },
        mounted() {
            console.log('"' + this.entry.range_id + '"')
            if (this.entry.range_id) {
                this.ranges = [{id: this.entry.range_id, type: this.entry.range_type, name: this.entry.range_name}]
            }
        },
        methods: {
            searchRange: function(event) {
                event.preventDefault()

                fetch(
                    STUDIP.URLHelper.getURL('api.php/phonebook/ranges/' + encodeURI(this.searchterm.trim()))
                ).then((response) => {
                    if (!response.ok) {
                        throw response
                    }

                    response.json().then((json) => {
                        this.ranges = json
                    })
                }).catch((error) => {
                    let messagebox = document.createElement('div')
                    messagebox.classList.add('messagebox')
                    messagebox.classList.add('messagebox_error')
                    messagebox.innerHTML = error.statusText

                    STUDIP.Dialog.show(messagebox, { size: 'auto', title: 'Fehler ' + error.status })
                })
            },
            clearRange: function() {
                this.range = ''
                this.ranges = []
                this.searchterm = ''
            },
            catchEnter: function(event) {
                if (event.keyCode === 13) {
                    event.preventDefault()
                    this.searchRange(event)
                }
            },
            storeEntry: function(event) {
                event.preventDefault()

                let formData = new FormData()

                // Check if we need to create or update
                let url = STUDIP.URLHelper.getURL('api.php/phonebook/entry')
                let method = 'PUT'
                if (this.entry.id) {
                    url = STUDIP.URLHelper.getURL('api.php/phonebook/entry/' + this.entry.id)
                    method = 'PATCH'
                }

                if (this.name != this.entry.name) {
                    formData.append('name', this.name)
                }
                if (this.phone != this.entry.phone) {
                    formData.append('phone', this.phone)
                }
                if (this.range != this.entry.range_id) {
                    formData.append('range', this.range)
                }

                fetch(url, {
                    method: method,
                    body: formData
                }).then((response) => {
                    if (!response.ok) {
                        throw response
                    }

                    window.location.href = STUDIP.URLHelper.getURL('plugins.php/phonebook')
                }).catch((error) => {
                    let messagebox = document.createElement('div')
                    messagebox.classList.add('messagebox')
                    messagebox.classList.add('messagebox_error')
                    messagebox.innerHTML = error.statusText

                    STUDIP.Dialog.show(messagebox, { size: 'auto', title: 'Fehler ' + error.status })
                })
            }
        }
    }
</script>

<style lang="scss">
    form {
        fieldset {
            section {
                img, svg {
                    vertical-align: middle;
                }
            }
        }
    }
</style>
