<template>
    <section class="phonebook-entry">
        <div class="phonebook-picture">
            <a :href="profileUrl">
                <img :src="entry.picture" :title="entry.firstname + ' ' + entry.lastname" width="64" height="64">
            </a>
        </div>
        <div class="phonebook-person">
            <div class="phonebook-name">
                <a :href="profileUrl">
                    {{ fullname }}
                </a>
            </div>
            <div class="phonebook-institute">
                {{ entry.institute }}
                <br>
                {{ statusgroupGendered }}
            </div>
        </div>
        <div class="phonebook-phone">
            <template v-if="dialable != null">
                <a :href="'tel:' + dialable">
                    {{ entry.phone }}
                </a>
            </template>
            <template v-else>
                {{ entry.phone }}
            </template>
        </div>
    </section>
</template>

<script>
    import StudipIcon from './StudipIcon'
    import { parsePhoneNumberFromString } from 'libphonenumber-js/min'

    export default {
        name: 'PhonebookEntry',
        components: {
            StudipIcon
        },
        props: {
            entry: {
                type: Object,
                required: true
            }
        },
        computed: {
            fullname: function() {
                let name = this.entry.lastname
                if (this.entry.firstname) {
                    name += ', ' + this.entry.firstname
                }
                if (this.entry.title_front) {
                    name += ', ' + this.entry.title_front
                }
                if (this.entry.title_rear) {
                    name += ', ' + this.entry.title_rear
                }
                return name
            },
            statusgroupGendered: function() {
                switch (this.entry.gender) {
                    case '1':
                        return this.entry.statusgroup_male
                    case '2':
                        return this.entry.statusgroup_female
                    default:
                        return this.entry.statusgroup
                }
            },
            dialable: function() {
                const phoneNumber = parsePhoneNumberFromString(this.entry.phone)
                if (phoneNumber) {
                    return phoneNumber.number
                } else {
                    return null
                }
            },
            profileUrl: function() {
                return STUDIP.URLHelper.getURL('dispatch.php/profile', {username: this.entry.username})
            }
        }
    }
</script>

<style lang="scss">
    .phonebook-entry {
        border: 1px solid #d0d7e3;
        display: flex;
        flex-direction: row;

        .phonebook-picture {
            box-shadow: 2px 2px 5px #aaaaaa;
            height: 64px;
            margin-right: 20px;
            padding: 2px;
            width: 64px;
        }

        .phonebook-person {
            flex: 1;

            .phonebook-name {
                font-weight: bold;
            }

            .phonebook-institute {
                font-size: small;
                font-style: italic;
            }
        }

        .phonebook-phone {
            font-size: larger;
            font-weight: bold;
            line-height: 50pt;
            text-align: right;
            width: 250px;

            img, svg {
                vertical-align: middle;
            }
        }

        &:hover {
            background-color: #d0d7e3;
        }
    }
</style>
