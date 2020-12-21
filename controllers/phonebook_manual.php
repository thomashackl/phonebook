<?php

/**
 * Class PhonebookManualController
 * Controller for administrating manual phonebook entries.
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

class PhonebookManualController extends AuthenticatedController {

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
        PageLayout::addScript($this->plugin->getPluginURL() . '/assets/javascripts/phonebook.js');
    }

    /**
     * Add a new entry.
     */
    public function edit_action($id = 0)
    {
        $add = Navigation::getItem('/search/phonebook');
        $add->addSubNavigation('edit', new Navigation($id == 0 ?
            dgettext('phonebook', 'Eintrag im Telefonbuch anlegen') :
            dgettext('phonebook', 'Eintrag im Telefonbuch bearbeiten'),
            PluginEngine::getURL($this->plugin, [], 'phonebook_manual/edit')));
        Navigation::activateItem('/search/phonebook/edit');

        $this->isDialog = Request::isXhr();

        $this->institutes = [];

        foreach (Institute::getMyInstitutes() as $one) {
            $this->institutes[] = [
                'id' => $one['Institut_id'],
                'name' => $one['Name'],
                'is_fak' => $one['is_fak'],
                'faculty' => $one['fakultaets_id']
            ];
        }

        if ($id != 0) {
            $entry = PhonebookEntry::find($id);
        } else {
            $entry = new PhonebookEntry();
        }

        $this->prefix = Config::get()->PHONEBOOK_PHONENUMBER_PREFIX;

        // Check which type of object the range_id points to.
        if ($entry->range_id) {
            $range = User::find($entry->range_id);
            if ($range) {
                $rangeName = $range->getFullname();
            } else {
                $range = Institute::find($entry->range_id);
                $rangeName = (string) $range->name;
            }
        }

        $tz = new DateTimeZone('Europe/Berlin');
        if ($entry->valid_from) {
            $from = new DateTime($entry->valid_from, $tz);
            $from = $from->format('d.m.Y H:i');
        } else {
            $from = '';
        }
        if ($entry->valid_until) {
            $until = new DateTime($entry->valid_until, $tz);
            $until = $until->format('d.m.Y H:i');
        } else {
            $until = '';
        }

        $this->entry = [
            'id' => $entry->id,
            'name' => $entry->name,
            'range_id' => $entry->range_id,
            'range_name' => $rangeName,
            'range_type' => $range ? strtolower(get_class($range)) : '',
            'phone' => str_replace($this->prefix, '', $entry->phone),
            'info' => $entry->info,
            'building' => $entry->building,
            'room' => $entry->room,
            'external_id' => $entry->external_id,
            'valid_from' => $from,
            'valid_until' => $until
        ];
    }

}
