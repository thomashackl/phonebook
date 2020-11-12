<?php

/**
 * Creates a global role for phonebook admins, thus controlling
 * access to routes for changing data.
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

class GlobalRole extends Migration {

    public function description()
    {
        return 'Creates a global role for phonebook admins, thus controlling access to routes for changing data.';
    }

    /**
     * Migration UP: Create a new role if necessary.
     */
    public function up()
    {
        $role = new Role(Role::UNKNOWN_ROLE_ID, 'Telefonbuch-Admin');
        RolePersistence::saveRole($role);
    }

    /**
     * Migration DOWN: cleanup all created data.
     */
    public function down()
    {
        $id = RolePersistence::getRoleIdByName('Telefonbuch-Admin');

        if ($id) {
            $roles = RolePersistence::getAllRoles();
            RolePersistence::deleteRole($roles[$id]);
        }
    }

}
