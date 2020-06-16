<?php

namespace RESTAPI\Routes;

use \Request, \DBManager, \Config, \Avatar, \URLHelper, \PhonebookEntry;

/**
 * PhonebookRoutes - REST routes for phone book.
 *
 * @author Thomas Hackl <thomas.hackl@uni-passau.de>
 * @category Phonebook
 */

class PhonebookRoutes extends \RESTAPI\RouteMap {

    /**
     * Searches for phonebook entries matching the given searchterm.
     * Searchterm can be part of a phone number, a person name or an
     * institution name.
     *
     * You can specify the fields to search in via the query parameter "in"
     *
     * @get /phonebook/search/:searchterm
     */
    public function search($searchterm)
    {
        if (!Request::get('in')) {

            $this->error(400, 'No search fields specified.');

        } else {

            $in = explode(',', Request::get('in'));

            $offset = Request::int('offset') ?: 0;
            $limit = Request::int('limit') ?: 100;

            $query = $this->getUserSQL($in) .
                " UNION " . $this->getPhonebookSQL($in) .
                " ORDER BY lastname, firstname, username, institute, statusgroup, phone";

            $parameters =                 [
                'search' => '%' . urldecode($searchterm) . '%',
                'visibility' => words('yes always')
            ];

            if (in_array('institute_holder', $in)) {
                $groups = Config::get()->PHONEBOOK_INSTITUTE_HOLDER_STATUSGROUPS ?: [];
                $parameters['groups'] = $groups;
            }

            $entries = DBManager::get()->fetchAll($query, $parameters);

            array_walk($entries, function (&$entry, $index) {
                if (in_array($entry['type'], ['user', 'institute'])) {
                    $avatar = Avatar::getAvatar($entry['id']);

                    $entry['picture'] = $avatar->getURL(Avatar::MEDIUM);
                    if ($avatar->is_customized()) {
                        $entry['picture_customized'] = true;
                    }
                } else {
                    $entry['picture'] = null;
                }

                switch ($entry['type']) {
                    case 'user':
                        $entry['link'] = URLHelper::getLink('dispatch.php/profile', ['username' => $entry['username']]);
                        break;
                    case 'institute':
                        $entry['link'] = URLHelper::getLink('dispatch.php/institute/overview', ['cid' => $entry['id']]);
                        break;
                    default:
                        $entry['link'] = '';
                        break;
                }
            });

            $this->etag(md5(serialize($entries)));

            $params = [
                'in' => Request::get('in')
            ];

            return $this->paginated(array_slice($entries, $offset, $limit),
                count($entries), compact('searchterm'), $params);
        }
    }

    /**
     * Gets the entry with the given ID. This only applies for entries in the
     * "phonebook" database table. "Normal" Stud.IP users can be fetched via
     * core API routes.
     *
     * @get /phonebook/entry/:id
     */
    public function getEntry($id)
    {
        $entry = PhonebookEntry::find($id);

        if ($entry) {
            $this->status(200);
            return $entry->toArray();
        } else {
            $this->error(404, 'Entry with the given ID not found.');
        }
    }

    /**
     * Adds a new entry to the phonebook. Stud.IP users are included in
     * phonebook by default, so this method can be used to add manual entries
     * which do not correspond to a real user.
     *
     * @put /phonebook/entry
     */
    public function addEntry()
    {
        if (!$GLOBALS['perm']->have_perm('root') &&
                !$GLOBALS['user']->getAuthenticatedUser()->hasRole('Telefonbuch-Admin')) {
            $this->error(403, 'You must be root or a phonebook admin in order to create new entries.');
        }

        $entry = new PhonebookEntry();

        if (!trim($this->data['name']) || !trim($this->data['phone'])) {
            $this->error(400, 'Name and phone number are required.');
        }

        $entry->name = trim($this->data['name']);
        $entry->phone = trim($this->data['phone']);

        if ($this->data['range_id']) {
            $entry->range_id = $this->data['range_id'];
        }

        $entry->mkdate = date('Y-m-d H:i:s');
        $entry->chdate = date('Y-m-d H:i:s');

        if ($entry->store()) {

            $this->status(201);
            $this->headers(['Location' => URLHelper::getLink('api.php/phonebook/entry/' . $entry->id)]);
            return $entry->toArray();

        } else {

            $this->error(500, 'Could not store entry to database.');

        }
    }

    /**
     * Updates the entry with the given ID.
     *
     * @patch /phonebook/entry/:id
     */
    public function updateEntry($id)
    {
        $entry = PhonebookEntry::find($id);

        if ($entry) {

            if (isset($this->data['name']) && $this->data['name'] !== '') {
                if (trim($this->data['name']) != '') {
                    $entry->name = trim($this->data['name']);
                } else {
                    $this->error(422, 'A name for the entry is required.');
                }
            }

            if (isset($this->data['phone']) && $this->data['phone'] !== '') {
                if (trim($this->data['phone']) != '') {
                    $entry->phone = trim($this->data['phone']);
                } else {
                    $this->error(422, 'A phone number for the entry is required.');
                }
            }

            if (isset($this->data['range'])) {
                $entry->range_id = trim($this->data['range']) ?: null;
            }

            $entry->chdate = date('Y-m-d H:i:s');

            if ($entry->store() !== false) {

                $this->status(204);
                $this->headers(['Content-Location' => URLHelper::getLink('api.php/phonebook/entry/' . $entry->id)]);
                $this->etag(md5(serialize($entry->toArray())));

            } else {

                $this->error(500, 'Could not store changes to database.');

            }
            return $entry->toArray();

        } else {

            $this->error(404, 'Entry with the given ID not found.');

        }
    }

    /**
     * Finds ranges (institutes and users) matching the given searchterm.
     *
     * @get /phonebook/ranges/:searchterm
     */
    public function getRanges($searchterm)
    {
        $users = DBManager::get()->fetchAll("SELECT DISTINCT
                a.`user_id` AS id,
                CONCAT(
                    CONCAT_WS(
                        ', ',
                        CONCAT_WS(
                            ', ',
                            CONCAT_WS(', ', a.`Nachname`, a.`Vorname`),
                            IF(info.`title_front` != '', info.`title_front`, NULL),
                            IF(info.`title_rear` != '', info.`title_rear`, NULL)
                        )
                    ),
                    CONCAT(' (', a.`username`, ')')
                ) AS name,
                'user' AS type
            FROM `auth_user_md5` a
                JOIN `user_info` info ON (info.`user_id` = a.`user_id`)
            WHERE (
                    a.`Vorname` LIKE :search
                    OR a.`Nachname` LIKE :search
                    OR CONCAT_WS(' ', a.`Vorname`, a.`Nachname`) LIKE :search
                    OR CONCAT_WS(' ', a.`Nachname`, a.`Vorname`) LIKE :search
                    OR a.`username` LIKE :search
                ) AND a.`visible` IN (:visibilities)
            ORDER BY a.`Nachname`, a.`Vorname`, a.`username`",
            [
                'search' => '%' . urldecode($searchterm) . '%',
                'visibilities' => words('yes always')
            ]);

        $institutes = DBManager::get()->fetchAll("SELECT `Institut_id` AS id, `Name` AS name, 'institute' AS type
            FROM `Institute`
            WHERE `Name` LIKE :search
            ORDER BY `Name`",
            ['search' => '%' . urldecode($searchterm) . '%']);

        $result = array_merge($users, $institutes);
        usort($result, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        $this->status(200);
        return $result;
    }

    /**
     * Generates SQL for getting user data.
     *
     * @param array $in fields to search in
     * @return string SQL
     */
    private function getUserSQL($in)
    {
        $query = "SELECT DISTINCT
                a.`user_id` AS id,
                'user' AS type,
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
                ui.`Telefon` AS phone,
                ui.`Fax` AS fax,
                ui.`raum` AS room
            FROM `auth_user_md5` a ";

        $joins = [
            "JOIN `user_info` info ON (info.`user_id` = a.`user_id`)",
            "JOIN `user_inst` ui ON (ui.`user_id` = a.`user_id`)",
            "JOIN `Institute` inst ON (inst.`Institut_id` = ui.`institut_id`)",
            "JOIN `statusgruppe_user` us ON (us.`user_id` = a.`user_id`)",
            "JOIN `statusgruppen` s ON (
                    s.`statusgruppe_id` = us.`statusgruppe_id`
                    AND s.`range_id` = inst.`Institut_id`
                )"
        ];

        $where = [];
        if (in_array('phone_number', $in)) {
            $where[] = "ui.`Telefon` LIKE :search";
            $where[] = "ui.`Fax` LIKE :search";
        }

        if (in_array('person_name', $in)) {
            $where[] = "a.`Vorname` LIKE :search";
            $where[] = "a.`Nachname` LIKE :search";
            $where[] = "CONCAT_WS(' ', a.`Vorname`, a.`Nachname`) LIKE :search";
            $where[] = "CONCAT_WS(' ', a.`Nachname`, a.`Vorname`) LIKE :search";
            $where[] = "a.`username` LIKE :search";
        }

        if (in_array('institute_name', $in)) {
            $where[] = "inst.`Name` LIKE :search";
        }

        if (in_array('room', $in)) {
            $where[] = "ui.`raum` LIKE :search";
        }

        if (in_array('institute_holder', $in)) {
            $where[] = "inst.`Institut_id` IN (" . $this->getHolderSQL() . ")";
        }

        return $query . implode(' ', $joins) . " WHERE (" . implode(' OR ', $where) . ")
                AND a.`visible` IN (:visibility) AND ui.`Telefon` != ''";
    }

    /**
     * Generates SQL for getting institute data.
     *
     * @param array $in fields to search in
     * @return string SQL
     */
    private function getInstituteSQL($in)
    {
        $query = "SELECT DISTINCT
                i.`Institut_id` AS id,
                'institute' AS type,
                '' AS firstname,
                i.`Name` AS lastname,
                '' AS title_front,
                '' AS title_rear,
                '' AS username,
                '' AS institute,
                0 AS gender,
                '' AS statusgroup,
                '' AS statusgroup_male,
                '' AS statusgroup_female,
                i.`telefon` AS phone,
                i.`fax` AS fax,
                '' AS room
            FROM `Institute` i ";

        $where = [];
        if (in_array('phone_number', $in)) {
            $where[] = "i.`telefon` LIKE :search";
            $where[] = "i.`fax` LIKE :search";
        }

        if (in_array('person_name', $in)) {
            $where[] = "i.`Name` LIKE :search";
        }

        if (in_array('institute_name', $in)) {
            $where[] = "i.`Name` LIKE :search";
        }

        if (in_array('institute_holder', $in)) {
            $where[] = "i.`Institut_id` IN (" . $this->getHolderSQL() . ")";
        }

        return $query . " WHERE (" . implode(' OR ', $where) . ") AND i.`telefon` != ''";
    }

    /**
     * Generates SQL for getting data from phonebook.
     *
     * @param array $in fields to search in
     * @return string SQL
     */
    private function getPhonebookSQL($in)
    {
        $query = "SELECT DISTINCT
                p.`entry_id` AS id,
                'phonebook' AS type,
                '' AS firstname,
                p.`name` AS lastname,
                '' AS title_front,
                '' AS title_rear,
                null AS username,
                IFNULL(inst.`Name`, CONCAT_WS(
                    ' ', u.`title_front`, a.`Vorname`, a.`Nachname`,
                        IF(u.`title_rear` != '', CONCAT(', ', u.`title_rear`), ''))
                    ) AS institute,
                null AS gender,
                '' AS statusgroup,
                '' AS statusgroup_male,
                '' AS statusgroup_female,
                p.`phone`,
                '' AS fax,
                '' AS room
            FROM `phonebook` p ";

        $joins = [
            "LEFT JOIN `Institute` inst ON (inst.`Institut_id` = p.`range_id`)",
            "LEFT JOIN `auth_user_md5` a ON (a.`user_id` = p.`range_id`)",
            "LEFT JOIN `user_info` u ON (u.`user_id` = a.`user_id`)"
        ];

        $where = [
            "p.`name` LIKE :search"
        ];
        if (in_array('phone_number', $in)) {
            $where[] = "p.`phone` LIKE :search";
        }

        if (in_array('person_name', $in)) {
            $where[] = "a.`Vorname` LIKE :search";
            $where[] = "a.`Nachname` LIKE :search";
            $where[] = "CONCAT_WS(' ', a.`Vorname`, a.`Nachname`) LIKE :search";
            $where[] = "CONCAT_WS(' ', a.`Nachname`, a.`Vorname`) LIKE :search";
            $where[] = "a.`username` LIKE :search";
        }

        if (in_array('institute_name', $in)) {
            $where[] = "inst.`Name` LIKE :search";
        }

        if (in_array('institute_holder', $in)) {
            $where[] = "p.`range_id` IN (" . $this->getHolderSQL() . ")";
        }

        return $query . implode(' ', $joins) . " WHERE (" . implode(' OR ', $where) . ")";
    }

    private function getHolderSQL()
    {
        return "SELECT ui.`institut_id`
            FROM `user_inst` ui
                JOIN `auth_user_md5` a ON (a.`user_id` = ui.`user_id`)
                JOIN `statusgruppen` s ON (s.`range_id` = ui.`institut_id`)
                JOIN `statusgruppe_user` su ON (
                        su.`statusgruppe_id` = s.`statusgruppe_id` AND su.`user_id` = a.`user_id`
                    )
            WHERE s.`name` IN (:groups)
                AND (
                    a.`Vorname` LIKE :search
                    OR a.`Nachname` LIKE :search
                    OR CONCAT_WS(' ', a.`Vorname`, a.`Nachname`) LIKE :search
                    OR CONCAT_WS(' ', a.`Nachname`, a.`Vorname`) LIKE :search
                    OR a.`username` LIKE :search
                )";
    }

}
