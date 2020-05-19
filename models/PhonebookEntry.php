<?php

/**
 * PhonebookEntry.php
 * model class for manual phonebook entries that are no real persons.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Phonebook
 *
 * @property int entry_id database column
 * @property string name database column
 * @property string range_id database column
 * @property string phone database column
 * @property string creator database column
 * @property string mkdate database column
 * @property string chdate database column
 */

class PhonebookEntry extends SimpleORMap
{

    protected static function configure($config = [])
    {
        $config['db_table'] = 'phonebook';
        $config['belongs_to']['institute'] = [
            'class_name' => 'Institute',
            'foreign_key' => 'range_id',
            'assoc_foreign_key' => 'institut_id'
        ];
        $config['belongs_to']['user'] = [
            'class_name' => 'User',
            'foreign_key' => 'range_id',
            'assoc_foreign_key' => 'user_id'
        ];
        $config['has_one']['creator'] = [
            'class_name' => 'User',
            'foreign_key' => 'user_id',
            'assoc_foreign_key' => 'user_id'
        ];

        parent::configure($config);
    }

}
