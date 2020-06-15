<?php
/**
 * Phonebook.php
 *
 * Digital phonebook for Stud.IP.
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

class Phonebook extends StudIPPlugin implements SystemPlugin, RESTAPIPlugin {

    public function __construct() {
        parent::__construct();

        StudipAutoloader::addAutoloadPath(__DIR__ . '/models');

        // Localization
        bindtextdomain('phonebook', realpath(__DIR__.'/locale'));

        $navigation = new Navigation(dgettext('phonebook', 'Telefonbuch'),
            PluginEngine::getURL($this, [], 'phonebook_search'));
        $navigation->addSubNavigation('search', new Navigation(
            dgettext('phonebook', 'Suche'),
            PluginEngine::getURL($this, [], 'phonebook_search')));
        Navigation::addItem('/search/phonebook', $navigation);
    }

    public function getRouteMaps() {

        // Autoload models if required
        StudipAutoloader::addAutoloadPath(__DIR__ . '/models');

        // Load all routes
        foreach (glob(__DIR__ . '/routes/*') as $filename) {
            require_once $filename;
            $classname = '\RESTAPI\Routes\\'.basename($filename, '.php');
            $routes[] = new $classname;
        }

        return $routes;
    }

    /**
     * Plugin name to show in navigation.
     */
    public function getDisplayName()
    {
        return dgettext('phonebook', 'Telefonbuch');
    }

    public function getVersion()
    {
        $metadata = $this->getMetadata();
        return $metadata['version'];
    }

    public function perform($unconsumed_path) {
        $range_id = Request::option('cid', Context::get()->id);

        URLHelper::removeLinkParam('cid');
        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, [], null), '/'),
            'phonebook_search'
        );

        $dispatcher->current_plugin = $this;
        $dispatcher->range_id       = $range_id;
        $dispatcher->dispatch($unconsumed_path);
    }

}
