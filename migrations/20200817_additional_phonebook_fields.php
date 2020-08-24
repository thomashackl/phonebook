<?php

/**
 * Adds database fields for notes, building and room.
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

class AdditionalPhonebookFields extends Migration {

    public function description()
    {
        return 'Adds database fields for notes, building, room and external ID.';
    }

    public function up()
    {
        DBManager::get()->execute("ALTER TABLE `phonebook`
            ADD `info` TEXT NULL COLLATE utf8mb4_unicode_ci AFTER `phone`,
            ADD `building` VARCHAR(255) NULL COLLATE utf8mb4_unicode_ci AFTER `info`,
            ADD `room` VARCHAR(255) NULL COLLATE utf8mb4_unicode_ci AFTER `building`,
            ADD `external_id` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL AFTER `room`,
            ADD UNIQUE (`external_id`)");
    }

    public function down()
    {
        DBManager::get()->execute("ALTER TABLE `phonebook`
            DROP `info`,
            DROP `building`,
            DROP `room`,
            DROP `external_id`");
    }

}
