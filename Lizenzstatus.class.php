<?php

require_once __DIR__."/lib/ActionMenu.php";

class Lizenzstatus extends StudIPPlugin implements SystemPlugin {

    public function __construct() {
        parent::__construct();
        bindtextdomain('lizenzstatus', dirname(__FILE__).'/locale');
        bind_textdomain_codeset('lizenzstatus', 'windows-1252');
        $nav = new Navigation(dgettext('lizenzstatus', "Lizenzstatus"));
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")) {
            $nav->setImage(
                Icon::create("files", "navigation")
            );
        } else {
            $nav->setImage(
                $this->getPluginURL()."/assets/files_lightblue.svg"
            );
        }
        $nav->setURL(PluginEngine::getURL($this, array(), "my/files"));
        $this->addStylesheet("assets/actionmenu.less");
        PageLayout::addScript($this->getPluginURL()."/assets/actionmenu.js");
        Navigation::addItem("/myprotectedfiles", $nav);
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
                            title: "'._("Informationen zu Uploads von Texten").'"
                        });
                    });
                </script>
            ');
            $_SESSION['HAS_SEEN_52A_INFO'] = true;
        }
    }
}