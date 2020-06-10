<?php

/**
 * Class HolderGroupsController
 * Controller for configuring institute holder statusgroups in digital phonebook.
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

class PhonebookHolderGroupsController extends AuthenticatedController {

    /**
     * Actions and settings taking place before every page call.
     */
    public function before_filter(&$action, &$args)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException();
        }

        $this->plugin = $this->dispatcher->current_plugin;

        $this->set_layout(Request::isXhr() ? null : $GLOBALS['template_factory']->open('layouts/base'));

        $this->flash = Trails_Flash::instance();
    }

    /**
     * Show configuration form for setting institute holder statusgroups.
     */
    public function groups_action()
    {
        Navigation::activateItem('/search/phonebook');

        $this->allgroups = DBManager::get()->fetchFirst("SELECT DISTINCT s.`name`
            FROM `statusgruppen` s
                JOIN `Institute` i ON (i.`Institut_id` = s.`range_id`)
            ORDER BY s.`name`");

        $this->selected = Config::get()->PHONEBOOK_INSTITUTE_HOLDER_STATUSGROUPS ?: [];
    }

    /**
     * Store selected statusgroup to global configuration.
     */
    public function store_action()
    {
        if (Config::get()->store('PHONEBOOK_INSTITUTE_HOLDER_STATUSGROUPS', Request::getArray('groups'))) {
            Config::get()->PHONEBOOK_INSTITUTE_HOLDER_STATUSGROUPS = Request::getArray('groups');
            PageLayout::postSuccess(dgettext('phonebook', 'Die Daten wurden gespeichert.'));
        } else {
            PageLayout::postError(dgettext('phonebook', 'Die Daten konnten nicht gespeichert werden.'));
        }

        $this->relocate('phonebook_search');
    }

}
