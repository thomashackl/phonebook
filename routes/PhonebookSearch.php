<?php

namespace RESTAPI\Routes;

use \DBManager, \Avatar;

/**
 * PhonebookSearch - REST routes for search in phone book.
 *
 * @author Thomas Hackl <thomas.hackl@uni-passau.de>
 * @category Phonebook
 */

class PhonebookSearch extends \RESTAPI\RouteMap {

    /**
     * Searches for phonebook entries matching the given searchterm.
     * Searchterm can be part of a phone number, a person name or an
     * institution name.
     *
     * @get /phonebook/search/:searchterm
     */
    public function searchUsers($searchterm)
    {
        $query = "SELECT DISTINCT
                a.`user_id`,
                a.`Vorname` AS firstname,
                a.`Nachname` AS lastname,
                info.`title_front`,
                info.`title_rear`,
                a.`username`,
                inst.`Name` AS institute,
                info.`geschlecht` AS gender,
                s.`name` AS statusgroup,
                s.`name_m` AS statusgroup_male,
                s.`name_w` AS statusgroup_female,
                ui.`Telefon` AS phone
            FROM `auth_user_md5` a
                JOIN `user_info` info ON (info.`user_id` = a.`user_id`)
                JOIN `user_inst` ui ON (ui.`user_id` = a.`user_id`)
                JOIN `Institute` inst ON (inst.`Institut_id` = ui.`institut_id`)
                LEFT JOIN `statusgruppe_user` us ON (us.`user_id` = a.`user_id`)
                JOIN `statusgruppen` s ON (s.`statusgruppe_id` = us.`statusgruppe_id` AND s.`range_id` = inst.`Institut_id`)
            WHERE (
                    ui.`Telefon` LIKE :search
                    OR a.`Vorname` LIKE :search
                    OR a.`Nachname` LIKE :search
                    OR CONCAT_WS(' ', a.`Vorname`, a.`Nachname`) LIKE :search
                    OR CONCAT_WS(' ', a.`Nachname`, a.`Vorname`) LIKE :search
                    OR inst.`Name` LIKE :search
                )
                AND a.`visible` IN (:visibility)
            ORDER BY lastname, firstname, username";
        $users = DBManager::get()->fetchAll($query,
            [
                'search' => '%' . urldecode($searchterm) . '%',
                'visibility' => words('yes always')
            ]);

        array_walk($users, function(&$user, $index) {
            $avatar = Avatar::getAvatar($user['user_id']);
            $user['picture'] = $avatar->getURL(Avatar::MEDIUM);
        });

        $this->etag(md5(serialize($users)));

        return $users;
    }

}
