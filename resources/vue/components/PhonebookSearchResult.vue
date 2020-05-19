<template>
    <article id="phonebook-entries">
        <phonebook-entry v-for="(entry, index) in entries" :key="index" :entry="entry"></phonebook-entry>
    </article>
</template>

<script>
    import PhonebookEntry from './PhonebookEntry'

    export default {
        name: 'PhonebookSearchResult',
        components: {
            PhonebookEntry
        },
        props: {
            entries: {
                type: Array,
                default: () => []
            }
        }
    }
</script>

<style lang="scss">
    #phonebook-entries {
        margin-right: 25px;
        margin-top: 25px;
        max-width: 800px;

        section.phonebook-entry {
            padding: 5px;
        }
    }
</style>
