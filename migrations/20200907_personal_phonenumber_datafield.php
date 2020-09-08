<?php

/**
 * Creates a free datafield for a person's personal phone number.
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

class PersonalPhonenumberDatafield extends Migration {

    public function description()
    {
        return 'Creates a free datafield for a person\'s personal phone number.';
    }

    /**
     * Migration UP: Create a new free datafield.
     */
    public function up()
    {
        $datafield = new DataField();
        $datafield->name = 'Persönliche Telefonnummer';
        $datafield->object_type = 'user';
        $datafield->edit_perms = 'root';
        $datafield->view_perms = 'user';
        $datafield->type = 'textline';

        if ($datafield->store() !== false) {
            Config::get()->create('PHONEBOOK_PERSONAL_PHONE_DATAFIELD_ID', [
                'value' => $datafield->id,
                'type' => 'string',
                'range' => 'global',
                'section' => 'phonebook',
                'description' => 'Freies Datenfeld für eine persönliche Telefonnummer'
            ]);
        }
    }

    /**
     * Migration DOWN: cleanup all created data.
     */
    public function down()
    {
        $datafieldId = Config::get()->PHONEBOOK_EXTRA_INFO_DATAFIELD_ID;
        // Remove config entry and datafield.
        Config::get()->delete('PHONEBOOK_PERSONAL_PHONE_DATAFIELD_ID');
        DataField::find($datafieldId)->delete();
    }

}
