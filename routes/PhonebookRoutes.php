<?php

namespace RESTAPI\Routes;

use \Request, \DBManager, \Config, \Avatar, \URLHelper, \PhonebookEntry, \User, \DatafieldEntryModel,
    \DateTimeZone, \DateTime;

/**
 * PhonebookRoutes - REST routes for phone book.
 *
 * @author Thomas Hackl <thomas.hackl@uni-passau.de>
 * @category Phonebook
 */

class PhonebookRoutes extends \RESTAPI\RouteMap {

    const PHONE_PREFIX = "+49(0)851/509-";

    /**
     * Searches for phonebook entries matching the given searchterm.
     * Searchterm can be part of a phone number, a person name or an
     * institution name.
     *
     * You can specify the fields to search in via the query parameter "in",
     * offset and limit can be given accordingly via request.
     *
     * "in" contains a comma-separated list of "person_name", "phone_number",
     * "institute_name", "room", "institute_holder" and must not be empty.
     *
     * @get /phonebook/search/:searchterm
     */
    public function search($searchterm)
    {
        if (!Request::get('in')) {

            $this->error(400, 'No search fields specified.');

        } else {

            $in = explode(',', Request::get('in'));

            $offset = Request::int('offset', 0);
            $limit = Request::int('limit', $this->forceLimit ?: 100);
            $this->limit = $limit;

            $query = $this->getUserSQL($in) .
                " UNION " . $this->getPersonalPhoneSQL($in) .
                " UNION " . $this->getPhonebookSQL($in) .
                " ORDER BY lastname, firstname, username, institute, statusgroup, phone";

            $parameters =                 [
                'search' => '%' . urldecode($searchterm) . '%',
                'visibility' => words('yes always'),
                'datafield' => Config::get()->PHONEBOOK_EXTRA_INFO_DATAFIELD_ID,
                'personalphone' => Config::get()->PHONEBOOK_PERSONAL_PHONE_DATAFIELD_ID,
                'time' => date('Y-m-d H:i:s')
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
     * Fetches all phonebook entries in paginated form.
     *
     * Offset and limit can be specified via request parameters.
     *
     * @get /phonebook/all
     */
    public function getAll()
    {
        Request::set('in', 'person_name');

        $this->forceLimit = Request::int('limit', 500);

        return $this->search('%25');
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
     * Gets the entry with the given external ID. This only applies for entries in the
     * "phonebook" database table. "Normal" Stud.IP users can be fetched via
     * core API routes.
     *
     * @get /phonebook/entry/external/:id
     */
    public function getEntryByExternal_id($id)
    {
        $entry = PhonebookEntry::findOneByExternal_id($id);

        if ($entry) {
            $this->status(200);
            return $entry->toArray();
        } else {
            $this->error(404, 'Entry with the given external ID not found.');
        }
    }

    /**
     * Adds a new entry to the phonebook. Stud.IP users are included in
     * phonebook by default, so this method can be used to add manual entries
     * which do not correspond to a real user.
     *
     * "name" and "phone" must be set, "range_id", "info", "external_id",
     * "building", "room", "valid_from", "valid_until" are all optional.
     *
     * @put /phonebook/entry
     */
    public function addEntry()
    {
        /*if (!$GLOBALS['perm']->have_perm('root') &&
                !$GLOBALS['user']->getAuthenticatedUser()->hasRole('Telefonbuch-Admin')) {
            $this->error(403, 'You must be root or a phonebook admin in order to create new entries.');
        }*/

        $entry = new PhonebookEntry();

        if (!trim($this->data['name']) || !trim($this->data['phone'])) {
            $this->error(400, 'Name and phone number are required.');
        }

        $entry->name = trim($this->data['name']);
        $entry->phone = self::PHONE_PREFIX . trim($this->data['phone']);

        foreach (words('range_id info external_id building room') as $set) {
            if ($this->data[$set]) {
                $entry->$set = $this->data[$set];
            }
        }

        $tz = new DateTimeZone('Europe/Berlin');
        if ($this->data['valid_from']) {
            $ts = new DateTime(trim($this->data['valid_from']), $tz);
            $entry->valid_from = $ts->format('Y-m-d H:i:s');
        }

        if ($this->data['valid_until']) {
            $ts = new DateTime(trim($this->data['valid_until']), $tz);
            $entry->valid_until = $ts->format('Y-m-d H:i:s');
        }

        $entry->creator = User::findCurrent()->id;

        $entry->mkdate = date('Y-m-d H:i:s');
        $entry->chdate = date('Y-m-d H:i:s');

        if ($entry->store()) {

            $this->status(201);
            $this->headers([
                'Location' => URLHelper::getLink('api.php/phonebook/entry/' . $entry->id),
                'Content-Location' => URLHelper::getLink('api.php/phonebook/entry/' . $entry->id)
            ]);
            return $entry->toArray();

        } else {

            $this->error(500, 'Could not store entry to database.');

        }
    }

    /**
     * Updates the entry with the given ID.
     *
     * "name" and "phone" must be set, "range_id", "info", "external_id",
     * "building", "room", "valid_from", "valid_until" are all optional.
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

            foreach (words('range_id info external_id building room') as $set) {
                if (isset($this->data[$set]) && $this->data[$set] !== '') {
                    $entry->$set = trim($this->data[$set]);
                }
            }

            $tz = new DateTimeZone('Europe/Berlin');
            if (isset($this->data['valid_from'])) {
                if (trim($this->data['valid_from'])) {
                    $ts = new DateTime(trim($this->data['valid_from']), $tz);
                    $entry->valid_from = $ts->format('Y-m-d H:i:s');
                } else {
                    $entry->valid_from = null;
                }
            }

            if (isset($this->data['valid_until'])) {
                if (trim($this->data['valid_until'])) {
                    $ts = new DateTime(trim($this->data['valid_until']), $tz);
                    $entry->valid_until = $ts->format('Y-m-d H:i:s');
                } else {
                    $entry->valid_until = null;
                }
            }

            $entry->chdate = date('Y-m-d H:i:s');

            if ($entry->store() !== false) {

                $this->status(204);
                $this->headers([
                    'Location' => URLHelper::getLink('api.php/phonebook/entry/' . $entry->id),
                    'Content-Location' => URLHelper::getLink('api.php/phonebook/entry/' . $entry->id)
                ]);

            } else {

                $this->error(500, 'Could not store changes to database.');

            }
            return $entry->toArray();

        } else {

            $this->error(404, 'Entry with the given ID not found.');

        }
    }

    /**
     * Updates the entry with the given external ID.
     *
     * @see updateEntry
     *
     * @patch /phonebook/entry/external/:id
     */
    public function updateEntryByExternalId($id)
    {
        $entry = PhonebookEntry::findOneByExternal_id($id);
        return $this->updateEntry($entry->id);
    }

    /**
     * Deletes the given entry from phonebook.
     *
     * @delete /phonebook/entry/:id
     */
    public function deleteEntry($id)
    {
        if ($entry = PhonebookEntry::find($id)) {

            if ($entry->delete() !== false) {
                $this->status(204);
            } else {
                $this->error(500, 'Could not delete entry from database.');
            }

        } else {

            $this->error(404, 'Entry with the given ID not found.');

        }
    }

    /**
     * Deletes the given entry from phonebook.
     *
     * @see deleteEntry
     *
     * @delete /phonebook/entry/external/:id
     */
    public function deleteEntryByExternalId($id)
    {
        $entry = PhonebookEntry::findOneByExternal_id($id);
        return $this->deleteEntry($entry->id);
    }

    /**
     * Set userinfo for given user at given institute.
     *
     * Content is given via the request parameter "info".
     *
     * @put /phonebook/userinfo/:username/:institute
     */
    public function setExtraInfo($username, $institute)
    {
        if ($user = User::findOneByUsername($username)) {

            if (InstituteMember::exists($user->id, $institute)) {

                $entry = DatafieldEntryModel::find([
                    Config::get()->PHONEBOOK_EXTRA_INFO_DATAFIELD_ID,
                    $user->id,
                    $institute,
                    ''
                ]);

                if (!$entry) {
                    $entry = new DatafieldEntryModel();
                    $entry->datafield_id = Config::get()->PHONEBOOK_EXTRA_INFO_DATAFIELD_ID;
                    $entry->range_id = $user->id;
                    $entry->sec_range_id = $institute;
                    $entry->lang = '';
                }

                $entry->content = $this->data['info'];

                if ($entry->store() !== false) {
                    $this->status(200);
                    return $entry->toArray();
                } else {
                    $this->error(500, 'Could not store extra user info.');
                }

            } else {
                $this->error(400, 'User not assigned to given institute.');
            }

        } else {

            $this->error(404, 'Given username not found.');

        }
    }

    /**
     * Delete userinfo for given user at given institute.
     *
     * @delete /phonebook/userinfo/:username/:institute
     */
    public function deleteExtraInfo($username, $institute)
    {
        if ($user = User::findOneByUsername($username)) {

            $entry = DatafieldEntryModel::find([
                Config::get()->PHONEBOOK_EXTRA_INFO_DATAFIELD_ID,
                $user->id,
                $institute,
                ''
            ]);

            if (!$entry) {
                $this->error(404, 'No content found, none deleted.');
            } else {

                if ($entry->delete() !== false) {
                    $this->status(204);
                } else {
                    $this->error(500, 'Could not delete extra user info.');
                }

            }

        } else {

            $this->error(404, 'Given username not found.');

        }
    }

    /**
     * Set personal phone number for given user at given institute.
     *
     * Content is given via the request parameter "number".
     *
     * @put /phonebook/personalphone/:username
     */
    public function setPersonalPhoneNumber($username)
    {
        if ($user = User::findOneByUsername($username)) {

            $entry = DatafieldEntryModel::find([
                Config::get()->PHONEBOOK_PERSONAL_PHONE_DATAFIELD_ID,
                $user->id,
                '',
                ''
            ]);

            if (!$entry) {
                $entry = new DatafieldEntryModel();
                $entry->datafield_id = Config::get()->PHONEBOOK_PERSONAL_PHONE_DATAFIELD_ID;
                $entry->range_id = $user->id;
                $entry->lang = '';
            }

            $entry->content = $this->data['number'];

            if ($entry->store() !== false) {
                $this->status(200);
                return $entry->toArray();
            } else {
                $this->error(500, 'Could not store personal phone number.');
            }

        } else {

            $this->error(404, 'Given username not found.');

        }
    }

    /**
     * Delete personal phone number for given user.
     *
     * @delete /phonebook/personalphone/:username
     */
    public function deletePersonalPhoneNumber($username)
    {
        if ($user = User::findOneByUsername($username)) {

            $entry = DatafieldEntryModel::find([
                Config::get()->PHONEBOOK_EXTRA_PERSONAL_PHONE_ID,
                $user->id,
                '',
                ''
            ]);

            if (!$entry) {
                $this->error(404, 'No content found, none deleted.');
            } else {

                if ($entry->delete() !== false) {
                    $this->status(204);
                } else {
                    $this->error(500, 'Could not delete extra user info.');
                }

            }

        } else {

            $this->error(404, 'Given username not found.');

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
                '' AS building,
                ui.`raum` AS room,
                IFNULL(e.`content`, '') AS info,
                '' AS valid_from,
                '' AS valid_until
            FROM `auth_user_md5` a ";

        $joins = [
            "JOIN `user_info` info ON (info.`user_id` = a.`user_id`)",
            "JOIN `user_inst` ui ON (ui.`user_id` = a.`user_id`)",
            "JOIN `Institute` inst ON (inst.`Institut_id` = ui.`institut_id`)",
            "JOIN `statusgruppe_user` us ON (us.`user_id` = a.`user_id`)",
            "JOIN `statusgruppen` s ON (
                    s.`statusgruppe_id` = us.`statusgruppe_id`
                    AND s.`range_id` = inst.`Institut_id`
                )",
            "LEFT JOIN `datafields_entries` e ON (e.`range_id` = a.`user_id` AND e.`sec_range_id` = inst.`Institut_id` AND e.`datafield_id` = :datafield)"
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
     * Generates SQL for getting user data.
     *
     * @param array $in fields to search in
     * @return string SQL
     */
    private function getPersonalPhoneSQL($in)
    {
        $query = "SELECT DISTINCT
                a.`user_id` AS id,
                'user' AS type,
                a.`Vorname` AS firstname,
                a.`Nachname` AS lastname,
                info.`title_front`,
                info.`title_rear`,
                a.`username`,
                'Persönliche Telefonnummer' AS institute,
                info.`geschlecht` AS gender,
                '' AS statusgroup,
                '' AS statusgroup_male,
                '' AS statusgroup_female,
                p.`content` AS phone,
                '' AS fax,
                '' AS building,
                '' AS room,
                IFNULL(e.`content`, '') AS info,
                '' AS valid_from,
                '' AS valid_until
            FROM `auth_user_md5` a ";

        $joins = [
            "JOIN `user_info` info ON (info.`user_id` = a.`user_id`)",
            "JOIN `user_inst` ui ON (ui.`user_id` = a.`user_id`)",
            "JOIN `Institute` inst ON (inst.`Institut_id` = ui.`institut_id`)",
            "JOIN `statusgruppe_user` us ON (us.`user_id` = a.`user_id`)",
            "JOIN `statusgruppen` s ON (
                    s.`statusgruppe_id` = us.`statusgruppe_id`
                    AND s.`range_id` = inst.`Institut_id`
                )",
            "JOIN `datafields_entries` p ON (p.`range_id` = a.`user_id` AND p.`datafield_id` = :personalphone)",
            "LEFT JOIN `datafields_entries` e ON (e.`range_id` = a.`user_id` AND e.`sec_range_id` = inst.`Institut_id` AND e.`datafield_id` = :datafield)"
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
                AND a.`visible` IN (:visibility) AND p.`content` IS NOT NULL AND p.`content` != ''";
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
                '' AS building,
                '' AS room,
                '' AS info,
                '' AS valid_from,
                '' AS valid_until
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
                p.`building`,
                p.`room`,
                p.`info`,
                p.`valid_from`,
                p.`valid_until`
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

        return $query . implode(' ', $joins) . " WHERE (p.`valid_from` IS NULL AND p.`valid_until` IS NULL)
                OR (p.`valid_from` IS NULL AND p.`valid_until` >= :time)
                OR (p.`valid_until` IS NULL AND p.`valid_from` <= :time)
                OR (:time BETWEEN p.`valid_from` AND p.`valid_until`) AND (" . implode(' OR ', $where) . ")";
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
