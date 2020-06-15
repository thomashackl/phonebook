<template>
    <article id="phonebook-entries">
        <header>
            <translate :translate-n="totalEntries" translate-plural="%{ totalEntries } Einträge gefunden.">
                %{ totalEntries } Eintrag gefunden.
            </translate>
            <translate>Zeige %{ lowerLimit } - %{ upperLimit }</translate>
        </header>
        <paginate v-if="totalEntries > limit" v-model="currentPage"
                  prev-text="&lt; " next-text=" &gt;"
                  :page-count="totalPages" :click-handler="changePage"
                  container-class="phonebook-paginate" page-class="phonebook-page"></paginate>
        <phonebook-entry v-for="(entry, index) in entries" :key="index" :entry="entry"
                         :edit-permission="mayEdit"></phonebook-entry>
    </article>
</template>

<script>
    import bus from 'jsassets/bus'
    import Paginate from 'vuejs-paginate'
    import PhonebookEntry from './PhonebookEntry'

    export default {
        name: 'PhonebookSearchResult',
        components: {
            Paginate,
            PhonebookEntry
        },
        props: {
            entries: {
                type: Array,
                required: true
            },
            totalEntries: {
                type: Number,
                required: true
            },
            offset: {
                type: Number,
                default: 0
            },
            limit: {
                type: Number,
                default: 100
            },
            permission: {
                type: String,
                default: 'autor'
            }
        },
        data() {
            return {
                loading: false,
                prevText: this.$gettext('< Zurück'),
                nextText: this.$gettext('Weiter >'),
                mayEdit: this.permission == 'root'
            }
        },
        computed: {
            totalPages: function() {
                return Math.ceil(this.totalEntries / this.limit)
            },
            currentPage: function() {
                return Math.floor(this.offset / this.limit) + 1
            },
            lowerLimit: function() {
                return this.offset + 1
            },
            upperLimit: function() {
                return Math.min(this.offset + this.limit, this.totalEntries)
            }
        },
        methods: {
            changePage: function(pageNum) {
                bus.$emit('change-page', pageNum)
            }
        }
    }
</script>

<style lang="scss">
    article {
        header {
            font-weight: bold;
            margin-bottom: 10px;
        }

        ul.phonebook-paginate {
            background-color: #e7ebf1;
            border: 1px solid #d0d7e3;
            display: flex;
            flex-direction: row;
            height: 25px;
            margin-bottom: 10px;
            margin-top: 10px;

            li {
                line-height: 25px;
                list-style-type: none;
                padding-right: 5px;

                &:first-of-type.disabled, &:last-of-type.disabled {
                    display: none;
                }

                &.phonebook-page {
                    text-align: center;
                    width: 25px;

                    &.active {
                        background-color: #24437c;
                        font-weight: bold;

                        a {
                            color: #ffffff;
                        }
                    }
                }
            }
        }

        #phonebook-entries {
            margin-right: 25px;
            margin-top: 25px;
            max-width: 800px;

            section.phonebook-entry {
                padding: 5px;
            }
        }
    }
</style>
