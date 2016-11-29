<?php

require_once __DIR__.'/../Lizenzstatus.class.php';

//for Stud.IP 2.5 compatibility:
if(!class_exists(Trails_Controller)) {
    require_once 'vendor/trails/src/controller.php';
}

//depending if it's Stud.IP 2.5 or something newer we have
//to include different classes to get the PluginController class.
if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.0", ">=")) {
    require_once 'app/controllers/plugin_controller.php';
} else {
    require_once __DIR__.'/../lib/PluginController25.class.php';
}




class MyController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/myprotectedfiles");
        $this->formclass = version_compare($GLOBALS['SOFTWARE_VERSION'], "3.5", ">=") ? "default" : "studip_form";

        $this->icons = array(
            0 => $this->plugin->getPluginURL() .'/assets/check-circle.svg',
            1 => $this->plugin->getPluginURL() .'/assets/decline-circle.svg',
            2 => $this->plugin->getPluginURL() .'/assets/question-circle.svg',
            3 => $this->plugin->getPluginURL() .'/assets/own-license.svg',
            4 => $this->plugin->getPluginURL() .'/assets/cc.svg',
            5 => $this->plugin->getPluginURL() .'/assets/license.svg',
            6 => $this->plugin->getPluginURL() .'/assets/52a.svg',
            7 => $this->plugin->getPluginURL() .'/assets/52a-stopp2.svg'
        );

        //The Helpbar isn't available in Stud.IP 2.5 and 3.0!
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>=')) {
            Helpbar::Get()->addLink(
                _("Was bedeuten die Lizenzen?"),
                PluginEngine::getURL($this->plugin, array(), "my/licensehelp"),
                Assets::image_path("icons/white/question-circle"),
                false,
                array('data-dialog' => 1));
        }
    }

    public function my_action()
    {
    
    }
    
    
    public function files_action()
    {
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/jquery.tablesorter-2.22.5.js");
        if (Request::option("semester_id")) {
            $semester = Semester::find(Request::option("semester_id"));
            $statement = DBManager::get()->prepare("
                SELECT dokumente.*
                FROM dokumente
                    INNER JOIN seminare ON (dokumente.seminar_id = seminare.Seminar_id)
                WHERE user_id = :user_id
                    AND (
                        seminare.start_time = :beginn
                        OR (seminare.start_time < :beginn AND seminare.duration_time = -1)
                        OR (seminare.start_time < :beginn AND seminare.start_time + seminare.duration_time >= :beginn)
                    )
                    AND dokumente.url = ''
                ORDER BY mkdate DESC
            ");
            $statement->execute(array(
                'user_id' => $GLOBALS['user']->id,
                'beginn' => $semester['beginn']
            ));
            $documents_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            $this->files = array();
            foreach ($documents_data as $key => $row) {
                //SORM::buildExisting and SORM::build are not available in Stud.IP 2.5:
                $this->files[$key] = new StudipDocument();
                $this->files[$key]->setData($row, false);
                $this->files[$key]->setNew(false);
                //$this->files[] = StudipDocument::build($data, false);
            }
        } else {
            //StudipPDO::fetchAll isn't available in Stud.IP 2.5,
            //so we have to clone the functionality of that method in here:
            
            $sql = "SELECT dokumente.*
                FROM dokumente INNER JOIN (
                    SELECT seminar_id as id FROM seminare
                    UNION
                    SELECT institut_id as id FROM Institute
                ) bla ON bla.id = dokumente.seminar_id
                WHERE user_id = ?
                AND dokumente.url = ''
                ORDER BY mkdate DESC";
            
            $db = DBManager::get();
            $statement = $db->prepare($sql);
            $statement->execute(array($GLOBALS['user']->id));
            $data = $statement->fetchAll(PDO::FETCH_COLUMN); //fetchFirst istn't available in Stud.IP 2.5
            foreach($data as $key => $row) {
                //SORM::buildExisting and SORM::build are not available in Stud.IP 2.5:
                $data[$key] = new StudipDocument();
                $data[$key]->setData($row, false);
                $data[$key]->setNew(false);
            }
            
            $this->files = $data;
        }
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
                $this->redirect("my/files".(Request::option("semester_id") ? "?semester_id=".Request::option("semester_id"): ""));
            } elseif (Request::get("action") === "selectlicense") {
                $_SESSION['SWITCH_FILES'] = Request::getArray("d");
                $this->redirect("my/selectlicense".(Request::option("semester_id") ? "?semester_id=".Request::option("semester_id"): ""));
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
            $this->redirect("my/files".(Request::option("semester_id") ? "?semester_id=".Request::option("semester_id"): ""));
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