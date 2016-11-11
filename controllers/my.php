<?php

require_once 'app/controllers/plugin_controller.php';

class MyController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/myprotectedfiles");
        $this->formclass = version_compare($GLOBALS['SOFTWARE_VERSION'], "3.5", ">=") ? "default" : "studip_form";
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
        $this->render_nothing();
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
            SELECT * FROM document_licenses WHERE license_id >= 2
        ");
        $statement->execute();
        $this->licenses = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->files = (array) $_SESSION['SWITCH_FILES'];
        unset($_SESSION['SWITCH_FILES']);
    }



}