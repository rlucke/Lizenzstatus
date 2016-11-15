<?php

require_once 'app/controllers/plugin_controller.php';

class MyController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/myprotectedfiles");
        $this->formclass = version_compare($GLOBALS['SOFTWARE_VERSION'], "3.5", ">=") ? "default" : "studip_form";

        $this->icons = array(
            0 => $this->plugin->getPluginURL() .'/assets/check-circle.svg',
            1 => $this->plugin->getPluginURL() .'/assets/decline-circle.svg',
            2 => Assets::image_path("icons/blue/question-circle.svg"),
            3 => Assets::image_path("icons/blue/person.svg"),
            4 => $this->plugin->getPluginURL() .'/assets/cc.svg',
            5 => $this->plugin->getPluginURL() .'/assets/license.svg',
            6 => $this->plugin->getPluginURL() .'/assets/52a.svg',
            7 => $this->plugin->getPluginURL() .'/assets/52a-stopp2.svg'
        );

        Helpbar::Get()->addLink(
            _("Was bedeuten die Lizenzen?"),
            PluginEngine::getURL($this->plugin, array(), "my/licensehelp"),
            Assets::image_path("icons/white/question-circle"),
            false,
            array('data-dialog' => 1));
    }

    public function files_action()
    {
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/jquery.tablesorter-2.22.5.js");
        $this->files = StudipDocument::findBySQL("user_id = ? ORDER BY mkdate DESC", array($GLOBALS['user']->id));
        $statement = DBManager::get()->prepare("
            SELECT * FROM document_licenses ORDER BY license_id <> 2 DESC, license_id ASC
        ");
        $statement->execute();
        $this->licenses = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggle_action($dokument_id)
    {
        $this->file = new StudipDocument($dokument_id);
        if ($this->file->checkAccess($GLOBALS['user']->id) && $this->file['user_id'] === $GLOBALS['user']->id) {
            $this->file['protected'] = Request::int("protected", 0);
            $this->file->store();
        }
        $this->render_text($this->icons[$this->file['protected']]);
    }

    public function switchall_action()
    {
        $this->files = StudipDocument::findBySQL("user_id = ? ORDER BY mkdate DESC", array($GLOBALS['user']->id));
        foreach ($this->files as $file) {
            $file['protected'] = 1;
            $file->store();
        }
        PageLayout::postMessage(MessageBox::success(_("Alle Ihre Dokumente sind markiert als: „Nicht frei von Rechten Dritter“ <br><b>Achtung</b>: Damit Studierende diese Dokumente herunterladen können, müssen die betreffenden Veranstaltungen gesperrt werden.")));
        $this->redirect("my/files");
    }

    public function command_action() {
        if (Request::isPost()) {
            if (Request::get("action") === "delete") {
                foreach (Request::getArray("d") as $file_id) {
                    $file = new StudipDocument($file_id);
                    if ($file->checkAccess($GLOBALS['user']->id) && $file['user_id'] === $GLOBALS['user']->id) {
                        $file->delete();
                    }
                }
                PageLayout::postMessage(MessageBox::success(_("Ausgewählte Dateien wurden gelöscht.")));
                $this->redirect("my/files");
            } elseif (Request::get("action") === "selectlicense") {
                $_SESSION['SWITCH_FILES'] = Request::getArray("d");
                $this->redirect("my/selectlicense");
            }
        }
    }

    public function selectlicense_action() {
        PageLayout::setTitle(_("Ausgewählte Dateien verändern"));
        if (Request::isPost()) {
            foreach (Request::getArray("d") as $file) {
                $this->file = new StudipDocument($file);
                if ($this->file->checkAccess($GLOBALS['user']->id) && $this->file['user_id'] === $GLOBALS['user']->id) {
                    $this->file['protected'] = Request::int("license", 0);
                    $this->file->store();
                }
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%s Dokumente verändert."), count(Request::getArray("d")))));
            $this->redirect("my/files");
        }
        $statement = DBManager::get()->prepare("
            SELECT * FROM document_licenses WHERE license_id >= 2 ORDER BY license_id ASC
        ");
        $statement->execute();
        $this->licenses = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->files = (array) $_SESSION['SWITCH_FILES'];
        unset($_SESSION['SWITCH_FILES']);
    }

    public function licensehelp_action()
    {
        $statement = DBManager::get()->prepare("
            SELECT * FROM document_licenses
        ");
        $statement->execute();
        $this->licenses = $statement->fetchAll(PDO::FETCH_ASSOC);
    }



}