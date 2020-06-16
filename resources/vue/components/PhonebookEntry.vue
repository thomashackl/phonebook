<template>
    <section class="phonebook-entry">
        <div class="phonebook-picture">
            <a v-if="entry.type == 'user' && entry.picture != null" :href="profileUrl">
                <img :src="entry.picture" :title="entry.firstname + ' ' + entry.lastname" width="64" height="64">
            </a>
            <a v-if="entry.type != 'user' && entry.picture != null">
                <img :src="entry.picture" :title="entry.firstname + ' ' + entry.lastname" width="64" height="64">
            </a>
        </div>
        <div class="phonebook-person">
            <div class="phonebook-name">
                <a v-if="entry.link != ''" :href="entry.link">
                    {{ fullname }}
                </a>
                <template v-else>
                    {{ fullname }}
                </template>
                <div v-if="entry.info" class="phonebook-extra-info">
                    ({{ entry.info }})
                </div>
                <studip-icon v-if="entry.type == 'phonebook' && editPermission" shape="edit"
                             width="20" height="20" @click="editEntry(entry.id)"></studip-icon>
            </div>
            <div class="phonebook-institute">
                {{ entry.institute }}
                <br>
                {{ statusgroupGendered }}
                <template v-if="entry.room != ''">
                    <template v-if="statusgroupGendered != ''">,</template>
                    <translate>Raum</translate> {{ entry.room }}
                </template>
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
            <div v-if="entry.fax != ''" class="phonebook-fax">
                Fax: {{ entry.fax }}
            </div>
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
            },
            editPermission: {
                type: Boolean,
                default: false
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
                        return this.entry.statusgroup_male ? this.entry.statusgroup_male : this.entry.statusgroup
                    case '2':
                        return this.entry.statusgroup_female ? this.entry.statusgroup_female : this.entry.statusgroup
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
            }
        },
        methods: {
            editEntry: function(id) {
                window.location = STUDIP.URLHelper.getURL('plugins.php/phonebook/phonebook_manual/edit/' + id)
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
            height: 64px;
            margin-right: 20px;
            padding: 2px;
            width: 64px;

            img {
                box-shadow: 2px 2px 5px #aaaaaa;
            }
        }

        .phonebook-person {
            flex: 1;

            .phonebook-name {
                font-weight: bold;

                img, svg {
                    cursor: pointer;
                }

                .phonebook-extra-info {
                    display: inline;
                    font-weight: normal;
                }
            }

            .phonebook-institute {
                font-size: small;
                font-style: italic;
            }
        }

        .phonebook-phone {
            font-size: larger;
            font-weight: bold;
            margin-left: 10px;
            margin-right: 10px;
            margin-top: 10px;
            text-align: right;
            width: 250px;

            img, svg {
                vertical-align: middle;
            }

            div.phonebook-fax {
                font-size: small;
                line-height: 12px;
                margin-top: 5px;
            }
        }

        &:hover {
            background-color: #d0d7e3;
        }
    }
</style>
