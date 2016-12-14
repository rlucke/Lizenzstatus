<?php
/*
 *  Copyright (c) 2016 data-quest <info@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


require_once __DIR__."/lib/ActionMenu.php";
//require_once __DIR__."/controllers/my.php"; //for Stud.IP 2.5 compatibility

class Lizenzstatus extends StudIPPlugin implements SystemPlugin {

    public function __construct() {
        parent::__construct();
        bindtextdomain('lizenzstatus', dirname(__FILE__).'/locale');
        bind_textdomain_codeset('lizenzstatus', 'windows-1252');

        if(!Request::get('cid')) {
            URLHelper::removeLinkParam('cid');
        }


        $nav = new Navigation(dgettext('lizenzstatus', "Lizenzstatus"));
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")) {
            $nav->setImage(
                Icon::create("files", "navigation")
            );
        } else {
            if(version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>=')) {
                $nav->setImage(
                    $this->getPluginURL()."/assets/files_lightblue.svg"
                );
            } else {
                //special icon for Stud.IP 2.5:
                $nav->setImage(
                    $this->getPluginURL()."/assets/lizenzstatus.png"
                );
            }
        }
        $nav->setURL(PluginEngine::getURL($this, array(), "my/files"));
        $this->addStylesheet("assets/actionmenu.less");
        PageLayout::addScript($this->getPluginURL()."/assets/actionmenu.js");
        Navigation::addItem("/myprotectedfiles", $nav);

        global $perm;
        if($perm->have_perm('admin')) {
            //This feature is only available for admins:
            $subnav = new Navigation(dgettext('lizenzstatus', 'Dateien'));
            $subnav->setURL(PluginEngine::getURL($this, array(), 'my/files'));
            $nav->addSubNavigation('files', $subnav);

            $subnav = new Navigation(dgettext('lizenzstatus', 'Suche nach Veranstaltungen'));
            $subnav->setUrl(PluginEngine::getURL($this, array(), 'my/search'));
            $nav->addSubNavigation('search', $subnav);
            if($perm->have_perm('root')) {
                $subnav = new Navigation(dgettext('lizenzstatus', 'Suche nach Lehrenden'));
                $subnav->setUrl(PluginEngine::getURL($this, array(), 'my/search_user'));
                $nav->addSubNavigation('search_user', $subnav);
            }
        }


        if (((($GLOBALS['i_page'] === "folder.php") && $GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar']))
                    || (stripos($_SERVER['REQUEST_URI'], "plugins.php/myprotectedfiles") !== false))
                && !$_SESSION['HAS_SEEN_52A_INFO']
                && trim(Config::get()->INFO_TEXT_52A)) {
            PageLayout::addBodyElements('
                <div id="52a_info_text" style="display: none;">
                '.formatReady(Config::get()->INFO_TEXT_52A).'
                </div>
                <script>
                    jQuery(function () {
                        STUDIP.Dialog.show(jQuery("#52a_info_text").html(), {
                            title: "'.dgettext('lizenzstatus', "Informationen zu Uploads von Texten").'"
                        });
                    });
                </script>
            ');
            $_SESSION['HAS_SEEN_52A_INFO'] = true;
        }
    }


    /**
    * This method dispatches and displays all actions. It uses the template
    * method design pattern, so you may want to implement the methods #route
    * and/or #display to adapt to your needs.
    *
    * The source code was taken from the Blubber core plugin of Stud.IP 2.5,
    * Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
    *
    * @param  string  the part of the dispatch path, that were not consumed yet
    *
    * @return void
    */
    public function perform($unconsumed_path) {
        if(!$unconsumed_path) {
            header("Location: " . PluginEngine::getUrl($this), 302);
            return false;
        }
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, null, 'show');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
