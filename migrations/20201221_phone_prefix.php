<?php

/**
 * Creates a config entry for specifying a prefix for phone numbers.
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

class PhonePrefix extends Migration {

    public function description()
    {
        return 'Creates a config entry for specifying a prefix for phone numbers.';
    }

    /**
     * Migration UP: Create a new config entry.
     */
    public function up()
    {
        Config::get()->create('PHONEBOOK_PHONENUMBER_PREFIX', [
            'value' => '',
            'type' => 'string',
            'range' => 'global',
            'section' => 'phonebook',
            'description' => 'Präfix, das beim Anlegen von Telefonbucheinträgen automatisch vorangestellt wird.'
        ]);
    }

    /**
     * Migration DOWN: cleanup all created data.
     */
    public function down()
    {
        Config::get()->delete('PHONEBOOK_PHONENUMBER_PREFIX');
    }

}
