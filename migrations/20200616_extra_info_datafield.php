<?php

/**
 * Creates a free datafield for extra phonebook related information
 * about a person's institute assignment.
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

class ExtraInfoDatafield extends Migration {

    public function description()
    {
        return 'Creates a free datafield for extra phonebook related ' .
            'information about a person\'s institute assignment.';
    }

    /**
     * Migration UP: Create a new free datafield.
     */
    public function up()
    {
        $datafield = new DataField();
        $datafield->name = 'Zusatzinformationen Telefonbuch';
        $datafield->object_type = 'userinstrole';
        $datafield->edit_perms = 'root';
        $datafield->view_perms = 'root';
        $datafield->type = 'textline';

        if ($datafield->store() !== false) {
            Config::get()->create('PHONEBOOK_EXTRA_INFO_DATAFIELD_ID', [
                'value' => $datafield->id,
                'type' => 'string',
                'range' => 'global',
                'section' => 'phonebook',
                'description' => 'Freies Datenfeld für Zusatzinformationen'
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
        Config::get()->delete('PHONEBOOK_EXTRA_INFO_DATAFIELD_ID');
        DataField::find($datafieldId)->delete();
    }

}
