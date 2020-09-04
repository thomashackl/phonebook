<?php

/**
 * Adds database fields for specifying a time range when a number will be valid.
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

class NumberValidity extends Migration {

    public function description()
    {
        return 'Adds database fields for specifying a time range when a number will be valid.';
    }

    public function up()
    {
        DBManager::get()->execute("ALTER TABLE `phonebook`
            ADD `valid_from` DATETIME NULL AFTER `external_id`,
            ADD `valid_until` DATETIME NULL AFTER `valid_from`");
    }

    public function down()
    {
        DBManager::get()->execute("ALTER TABLE `phonebook`
            DROP `valid_from`,
            DROP `valid_until`");
    }

}
