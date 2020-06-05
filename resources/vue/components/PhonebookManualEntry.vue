<template>
    <form class="default" @submit.prevent="createEntry">
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
                    <translate>Zu wem gehört diese Telefonnummer?</translate>
                </label>
                <input v-if="ranges.length == 0" id="range" type="text" size="75" v-model="range"
                       :placeholder="placeholderText">
                <select v-else class="nested-select" id="range" v-model="range">
                    <option v-for="one in ranges" :key="one.id" :value="one.id">
                        <translate v-if="one.type === 'user'">Person:</translate>
                        <translate v-if="one.type === 'institute'">Einrichtung:</translate>
                        {{ one.name }}
                    </option>
                </select>
                <studip-icon shape="search" height="20" width="20" @click="searchRange"></studip-icon>
            </section>
            <section v-if="ranges.length > 0">
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
            }
        },
        data() {
            return {
                name: '',
                phone: '',
                range: '',
                ranges: [],
                placeholderText: this.$gettext('Einrichtung oder Person suchen'),
                acceptText: this.$gettext('Speichern'),
                cancelText: this.$gettext('Abbrechen')
            }
        },
        methods: {
            searchRange: function(event) {
                event.preventDefault()

                fetch(
                    STUDIP.URLHelper.getURL('api.php/phonebook/ranges/' + encodeURI(this.range.trim()))
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
            createEntry: function(event) {
                event.preventDefault()

                let formData = new FormData()
                formData.append('name', this.name)
                formData.append('phone', this.phone)
                if (this.range != '') {
                    formData.append('range_id', this.range)
                }

                fetch(STUDIP.URLHelper.getURL('api.php/phonebook/entry'), {
                    method: 'PUT',
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
