<template>
    <div>
        <form class="default">
            <section>
                <input type="text" id="searchterm" v-model="searchterm" :placeholder="placeholder">
                <span class="input-group-append">
                    <button type="submit" class="button" @click="doSearch">
                        <studip-icon shape="search"></studip-icon>
                    </button>
                    <button v-if="searchterm.length > 0" type="submit" id="clear-search-button" @click="clearSearch">
                        <studip-icon shape="decline"></studip-icon>
                    </button>
                </span>
            </section>
            <fieldset class="searchterm-filter">
                <legend>
                    <translate>Suchen in...</translate>
                </legend>
                <section>
                    <input type="checkbox" value="phone_number"
                           id="search-in-phone-number" v-model="searchInPhoneNumber" @click="doSearch()">
                    <label class="undecorated" for="search-in-phone-number">
                        <translate>
                            Telefonnummer
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" value="person_name"
                           id="search-in-person-name" v-model="searchInPersonName" @click="doSearch()">
                    <label class="undecorated" for="search-in-person-name">
                        <translate>
                            Vor- und Nachname
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" value="institute_name"
                           id="search-in-institute-name" v-model="searchInInstituteName" @click="doSearch()">
                    <label class="undecorated" for="search-in-institute-name">
                        <translate>
                            Einrichtungsname
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" value="room"
                           id="search-in-room" v-model="searchInRoom" @click="doSearch()">
                    <label class="undecorated" for="search-in-room">
                        <translate>
                            Raum
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" value="institute_holder"
                           id="search-in-institute-holder" v-model="searchInInstituteHolder" @click="doSearch()">
                    <label class="undecorated" for="search-in-institute-holder">
                        <translate>
                            Einrichtungsleitung
                        </translate>
                    </label>
                </section>
            </fieldset>
        </form>
        <vue-simple-spinner v-if="searching" size="48"></vue-simple-spinner>
        <phonebook-search-result v-if="searchResult.length > 0" :entries="searchResult" :permission="permission"
                                 :total-entries="total" :offset="offset" :limit="limit"></phonebook-search-result>
        <studip-messagebox v-if="noResults" type="info"
                           :message="noResultMessage"></studip-messagebox>
    </div>
</template>

<script>
    import bus from 'jsassets/bus'
    import StudipIcon from './StudipIcon'
    import VueSimpleSpinner from 'vue-simple-spinner'
    import PhonebookSearchResult from './PhonebookSearchResult'
    import StudipMessagebox from "./StudipMessagebox";

    export default {
        name: 'Phonebook',
        components: {
            StudipMessagebox,
            StudipIcon,
            VueSimpleSpinner,
            PhonebookSearchResult
        },
        props: {
            permission: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                searchterm: '',
                searchInPhoneNumber: true,
                searchInPersonName: true,
                searchInInstituteName: true,
                searchInRoom: false,
                searchInInstituteHolder: false,
                // Translation for placeholder attribute in search input field
                placeholder: this.$gettext('Suchen Sie nach Durchwahl, Name oder Einrichtung'),
                // Translation for message when no results are found
                noResultMessage: this.$gettext('Keine Einträge gefunden.'),
                noResults: false,
                searchResult: [],
                total: 0,
                offset: 0,
                limit: 100,
                searching: false
            }
        },
        mounted() {
            // Start searching 500 ms after user stopped typing.
            $('#searchterm').keyup(_.debounce(() => {
                this.doSearch()
            }, 500));
            // Start search when user presses enter in searchterm field.
            $('#searchterm').on('keypress', (event) => {
                if (event.which === 13) {
                    this.doSearch()
                    return false;
                }
            });

            bus.$on('change-page', (pageNum) => {
                this.offset = this.limit * (pageNum - 1)
                this.doSearch()
            })
        },
        methods: {
            doSearch: function(event) {
                if (event != null) {
                    event.preventDefault()
                }

                if (!this.searching) {
                    this.searching = true
                    this.searchResult = []

                    const inputs = this.$el.querySelectorAll('.searchterm-filter input[type="checkbox"]:checked')

                    if (this.searchterm.trim().length > 0 && inputs.length > 0) {

                        let params = []
                        for (let i = 0; i < inputs.length; i++) {
                            params.push(inputs[i].value)
                        }

                        fetch(STUDIP.URLHelper.getURL(
                            'api.php/phonebook/search/' + encodeURI(this.searchterm.trim()),
                                {
                                    in: params.join(','),
                                    offset: this.offset,
                                    limit: this.limit
                                })
                        ).then((response) => {
                            if (!response.ok) {
                                throw response
                            }
                            response.json().then((json) => {
                                this.searchResult = json.collection
                                this.total = json.pagination.total
                                this.searching = false
                                this.noResults = (json.pagination.total == 0)
                            })
                        }).catch((error) => {
                            let messagebox = document.createElement('div')
                            messagebox.classList.add('messagebox')
                            messagebox.classList.add('messagebox_error')
                            messagebox.innerHTML = error.statusText

                            STUDIP.Dialog.show(messagebox, {size: 'auto', title: 'Fehler ' + error.status})
                            this.searching = false
                        })
                    } else {
                        this.clearSearch()
                    }
                }
            },
            clearSearch: function(event) {
                this.searchterm = ''
                this.searchResult = []
                this.total = 0
                this.offset = 0
                this.searching = false
                this.noResults = false
            }
        }
    }
</script>

<style lang="scss" scoped>
    div {
        form.default {
            max-width: 600px;
            width: 100%;

            section {
                input {
                    max-width: 500px;
                    vertical-align: baseline;
                }
            }

            span.input-group-append {
                button.button, button.button:hover {
                    background-color: #e7ebf1;
                    border: 1px solid #c5c7ca;
                    border-left: none;
                    color: #28497c;
                    left: -4px;
                    line-height: 1.45;
                    margin: 0;
                    min-width: auto;
                    position: relative;
                    top: -1px;

                    img, svg {
                        vertical-align: middle;
                    }
                }

                #clear-search-button {
                    border: 0;
                    left: -85px;
                    position: relative;

                    img, svg {
                        vertical-align: middle;
                    }
                }
            }

            fieldset.searchterm-filter {
                background-color: #e7ebf1;
                font-size: smaller;
                max-width: 547px;
                padding: 0;
                position: relative;
                top: -2px;

                legend {
                    font-size: small;
                    margin-left: -1px;
                    width: calc(100% + 2px);
                }

                section {
                    display: inline;

                    &:first-of-type {
                        margin-left: 10px;
                    }
                }
            }
        }
    }
</style>
