<?php

/**
 * Creates a config entry for storing the statusgroup names that are considered for institute holders.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Phonebook
 */

class InstituteHolderGroups extends Migration {

    public function description()
    {
        return 'Creates a config entry for storing the statusgroup names that are considered for institute holders.';
    }

    /**
     * Migration UP: We have just installed the plugin
     * and need to prepare all necessary data.
     */
    public function up()
    {
        Config::get()->create('PHONEBOOK_INSTITUTE_HOLDER_STATUSGROUPS', [
            'value' => '',
            'type' => 'array',
            'range' => 'global',
            'section' => 'phonebook',
            'description' => 'Statusgruppennamen, die für Einrichtungsinhaber berücksichtigt werden'
        ]);
    }

    /**
     * Migration DOWN: cleanup all created data.
     */
    public function down()
    {
        // Remove config entries.
        foreach (words('INSTITUTE_HOLDER_STATUSGROUPS') as $entry) {
            Config::get()->delete('PHONEBOOK_' . $entry);
        }
    }

}
