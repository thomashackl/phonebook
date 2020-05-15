<template>
    <div>
        <form class="default">
            <section>
                <input type="text" id="searchterm" v-model="searchterm" :placeholder="placeholder">
                <span class="input-group-append">
                    <button type="submit" class="button" @click="doSearch">
                        <studip-icon shape="search"></studip-icon>
                    </button>
                    <button v-if="searchterm.length > 0" type="submit" class="button" @click="clearSearch">
                        <studip-icon shape="decline"></studip-icon>
                    </button>
                </span>
            </section>
            <fieldset class="searchterm-filter">
                <legend>
                    <translate>Suchen in...</translate>
                </legend>
                <section>
                    <input type="checkbox" id="search-in-phone-number" v-model="searchInPhoneNumber">
                    <label class="undecorated" for="search-in-phone-number">
                        <translate>
                            Telefonnummer
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" id="search-in-person-name" v-model="searchInPersonName">
                    <label class="undecorated" for="search-in-person-name">
                        <translate>
                            Vor- und Nachname
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" id="search-in-institute-name" v-model="searchInInstituteName">
                    <label class="undecorated" for="search-in-institute-name">
                        <translate>
                            Einrichtungsname
                        </translate>
                    </label>
                </section>
                <section>
                    <input type="checkbox" id="search-in-institute-holder" v-model="searchInInstituteHolder">
                    <label class="undecorated" for="search-in-institute-holder">
                        <translate>
                            Einrichtungsleitung
                        </translate>
                    </label>
                </section>
            </fieldset>
        </form>
        <phonebook-search-result v-if="searchResult.length > 0" :entries="searchResult"></phonebook-search-result>
    </div>
</template>

<script>
    import StudipIcon from './StudipIcon'
    import PhonebookSearchResult from './PhonebookSearchResult'

    export default {
        name: 'Phonebook',
        components: {
            StudipIcon,
            PhonebookSearchResult
        },
        data() {
            return {
                searchterm: '',
                searchInPhoneNumber: true,
                searchInPersonName: true,
                searchInInstituteName: true,
                searchInInstituteHolder: false,
                // Translation for placeholder attribute in search input field
                placeholder: this.$gettext('Suchen Sie nach Durchwahl, Name oder Einrichtung'),
                searchResult: []
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
        },
        methods: {
            doSearch: function(event) {
                if (event != null) {
                    event.preventDefault()
                }
                this.searchResult = []
                if (this.searchterm.trim().length > 0) {
                    fetch(STUDIP.URLHelper.getURL('api.php/phonebook/search/' + encodeURI(this.searchterm.trim())))
                        .then((response) => {
                            if (!response.ok) {
                                throw response
                            }
                            response.json().then((json) => {
                                this.searchResult = json
                            })
                        }).catch((error) => {
                            console.log('Error:')
                            console.log(error)
                        })
                } else {
                    this.clearSearch()
                }
            },
            clearSearch: function(event) {
                this.searchterm = ''
                this.searchResult = []
            }
        }
    }
</script>

<style lang="scss" scoped>
    div {
        max-width: 600px;
        width: 100%;

        form.default {
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
            }

            fieldset.searchterm-filter {
                background-color: #e7ebf1;
                font-size: smaller;
                max-width: 547px;
                position: relative;
                top: -2px;

                legend {
                    font-size: small;
                }

                section {
                    display: inline;
                }
            }
        }
    }
</style>
