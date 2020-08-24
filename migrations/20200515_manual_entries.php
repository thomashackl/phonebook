<?php

/**
 * Creates a database table for storing manual phonebook entries.
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

class ManualEntries extends Migration {

    public function description()
    {
        return 'Creates a database table for storing manual phonebook entries.';
    }

    /**
     * Migration UP: We have just installed the plugin
     * and need to prepare all necessary data.
     */
    public function up()
    {
        // Table for manually added phonebook entries
        DBManager::get()->execute("CREATE TABLE IF NOT EXISTS `phonebook`
        (
            `entry_id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci,
            `range_id` VARCHAR(32) NULL COLLATE latin1_bin DEFAULT NULL,
            `phone` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci,
            `creator` VARCHAR(32) NOT NULL COLLATE latin1_bin,
            `mkdate` DATETIME NOT NULL,
            `chdate` DATETIME NOT NULL,
            PRIMARY KEY (`entry_id`),
            INDEX range_id (`range_id`),
            CONSTRAINT FOREIGN KEY (`creator`) REFERENCES `auth_user_md5`(`user_id`)
        ) ENGINE InnoDB ROW_FORMAT=DYNAMIC");
    }

    /**
     * Migration DOWN: cleanup all created data.
     */
    public function down()
    {
        DBManager::get()->execute("DROP TABLE IF EXISTS `phonebook_entries`");
    }

}
