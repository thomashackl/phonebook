<?php

/**
 * Class PhonebookSearchController
 * Controller for digital phonebook.
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

class PhonebookSearchController extends AuthenticatedController {

    /**
     * Actions and settings taking place before every page call.
     */
    public function before_filter(&$action, &$args)
    {
        $this->plugin = $this->dispatcher->current_plugin;

        $this->set_layout(Request::isXhr() ? null : $GLOBALS['template_factory']->open('layouts/base'));

        $this->flash = Trails_Flash::instance();

        PageLayout::addScript($this->plugin->getPluginURL() . '/assets/javascripts/phonebook.js');
    }

    /**
     * Show phonebook search form and results if applicable
     */
    public function index_action()
    {
        if (!$GLOBALS['perm']->have_perm('user')) {
            throw new AccessDeniedException();
        }

        Navigation::activateItem('/search/phonebook');

        if ($GLOBALS['perm']->have_perm('root')) {
            $sidebar = Sidebar::get();
            $actions = new ActionsWidget();
            $actions->addLink(dgettext('phonebook', 'Manuellen Eintrag hinzufügen'),
                $this->link_for('phonebook_manual/edit'),
                Icon::create('phone+add'));
            $actions->addLink(dgettext('phonebook', 'Einrichtungsleitungen verwalten'),
                $this->link_for('phonebook_holder_groups'),
                Icon::create('group2'));
            $sidebar->addWidget($actions);
        }
    }

}
