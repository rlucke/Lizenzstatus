<?php

require_once __DIR__."/lib/ActionMenu.php";

class Lizenzstatus extends StudIPPlugin implements SystemPlugin {

    public function __construct() {
        parent::__construct();
        $nav = new Navigation(_("Lizenzstatus"));
        $nav->setImage(version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create("files", "navigation")
            : Assets::image_path("icons/lightblue/files.svg")
        );
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