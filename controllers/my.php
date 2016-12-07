<?php

require_once __DIR__.'/../Lizenzstatus.class.php';
require_once 'lib/classes/PageLayout.php';
require_once 'lib/classes/searchtypes/SeminarSearch.class.php';


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

        /*$GLOBALS['LICENSE_ICONS'] = array( //hannover
            0 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/check-circle.svg',
            1 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/decline-circle.svg',
            2 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/question-circle.svg',
            3 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/public-domain.svg', //gemeinfrei
            4 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/cc.svg',
            5 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/own-license.svg',
            6 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/license.svg',
            7 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/52a-stopp2.svg',
            8 => $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/52a.svg'
        );*/
        if ($GLOBALS['LICENSE_ICONS']) {
            $this->icons = $GLOBALS['LICENSE_ICONS'];

        } else {
            $this->icons = array(
                0 => $this->plugin->getPluginURL() . '/assets/check-circle.svg',
                1 => $this->plugin->getPluginURL() . '/assets/decline-circle.svg',
                2 => $this->plugin->getPluginURL() . '/assets/question-circle.svg',
                3 => $this->plugin->getPluginURL() . '/assets/own-license.svg',
                4 => $this->plugin->getPluginURL() . '/assets/cc.svg',
                5 => $this->plugin->getPluginURL() . '/assets/license.svg',
                6 => $this->plugin->getPluginURL() . '/assets/52a.svg',
                7 => $this->plugin->getPluginURL() . '/assets/52a-stopp2.svg'
            );
        }

        PageLayout::setHelpKeyword("Basis.DateienLizenzstatus"); // added by Fliegner
        //The Helpbar isn't available in Stud.IP 2.5 and 3.0!
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>=')) {
        Helpbar::Get()->addLink(
            dgettext('lizenzstatus', "Was bedeuten die Lizenzen?"),
            PluginEngine::getURL($this->plugin, array(), "my/licensehelp"),
            Assets::image_path("icons/white/question-circle"),
            false,
            array('data-dialog' => 1));
            }
    }

    function after_filter($action, $args)
    {
        parent::after_filter($action, $args);
        textdomain('studip');
    }


    public function my_action()
    {

    }

    
    /**
     * Responsible for removing the cid-parameter
     */
    public function reset_action()
    {
        URLHelper::removeLinkParam('cid');
        
        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'my/files'));
    }

    public function search_action()
    {
        Navigation::activateItem("/myprotectedfiles");
        if(Navigation::hasItem("/myprotectedfiles/search")) {
            Navigation::activateItem("/myprotectedfiles/search");
        }
        
        global $perm;
        
        if(!$perm->have_perm('admin')) {
            PageLayout::postMessage(
                MessageBox::error(
                    dgettext('lizenzstatus', 'Sie sind nicht dazu berechtigt, diese Seite aufzurufen!')
                )
            );
            
            $this->error = true;
            return;
        }
        
        $this->semester_id = Request::get('semester_id', '');
        $this->criteria = Request::get('criteria', null);
        $this->selected_semester_id = $this->semester_id;
        
        
        //semester selector is always filled:
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], '3.2', '>=')) {
            $this->available_semesters = Semester::getAll();
        } else {
            $this->available_semesters = SemesterData::getAllSemesterData();
        }
        
        
        if($this->semester_id or $this->criteria) {
            //semester-ID or criteria (or both) are given:
            //The user wants to know the courses from the specified semester
            //which have a certain name.
            
            
            if($this->criteria and $this->semester_id) {
                //semester and course name selected
                
                $semester = Semester::find($this->semester_id);
                
                if($semester) {
                    $this->courses = Course::findBySql(
                        "(
                            (:semester_start_time <= start_time 
                            AND :semester_end_time >= (start_time + duration_time))
                        OR
                            (:semester_start_time < (start_time + duration_time)
                            AND :semester_end_time >= (start_time + duration_time))
                        OR
                            (:semester_start_time <= start_time
                            AND :semester_end_time > start_time)
                        )
                        AND
                        (name LIKE CONCAT('%', :criteria, '%')
                        OR untertitel LIKE CONCAT('%', :criteria, '%')
                        OR beschreibung LIKE CONCAT('%', :criteria, '%')) ",
                        array(
                            'semester_start_time' => $semester->beginn,
                            'semester_end_time' => $semester->ende,
                            'criteria' => $this->criteria
                        )
                    );
                }
                
                $this->search_was_executed = true;
            } elseif($this->semester_id and !$this->criteria) {
                //semester and no course name selected
                $this->courses = Course::findBySql(
                    "(start_time = :semester_id) ",
                    array(
                        'semester_id' => $this->semester_id
                    )
                );
                $this->search_was_executed = true;
            } else {
                //no semester selected
                $this->courses = Course::findBySql(
                    "(name LIKE CONCAT('%', :criteria, '%')
                    OR untertitel LIKE CONCAT('%', :criteria, '%')
                    OR beschreibung LIKE CONCAT('%', :criteria, '%')) ",
                    array(
                        'criteria' => $this->criteria
                    )
                );
                $this->search_was_executed = true;
            }
            
        } elseif($this->course_id) {
            //The search has ended: We can call the files-action with that
            //course-ID to display all files of the course.
            $this->redirect(
                PluginEngine::getUrl(
                    $this->plugin,
                    array(
                        'cid' => Request::get('course_id')
                    ),
                    'my/files'
                )
            );
        }
    }
    
    
    public function files_action()
    {
        if(!Request::get('cid')) {
            URLHelper::removeLinkParam('cid');
        }
        
        if(Navigation::hasItem("/myprotectedfiles/files")) {
            Navigation::activateItem("/myprotectedfiles/files");
        }
        
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
            }
        } elseif (Request::option("cid")) {
            //user wants to see all files of a course
            $this->course = Course::find(Request::option("cid"));
            if(!$this->course) {
                PageLayout::postMessage(
                    MessageBox::error(
                        dgettext('lizenzstatus', 'Veranstaltung nicht gefunden!')
                    )
                );
                return;
            }
            
            
            $statement = DBManager::get()->prepare("
                SELECT dokumente.*
                FROM dokumente
                WHERE
                dokumente.seminar_id = :course_id
                AND
                dokumente.url = ''
                ORDER BY mkdate DESC
            ");
            $statement->execute(array(
                'course_id' => $this->course->id
            ));
            $documents_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            $this->files = array();
            foreach ($documents_data as $key => $row) {
                //SORM::buildExisting and SORM::build are not available in Stud.IP 2.5:
                $this->files[$key] = new StudipDocument();
                $this->files[$key]->setData($row, false);
                $this->files[$key]->setNew(false);
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
            $data = $statement->fetchAll(PDO::FETCH_ASSOC); //fetchFirst istn't available in Stud.IP 2.5
            $this->files = array();
            foreach($data as $key => $row) {
                //SORM::buildExisting and SORM::build are not available in Stud.IP 2.5:
                $this->files[$key] = new StudipDocument();
                $this->files[$key]->setData($row, false);
                $this->files[$key]->setNew(false);
            }

        }
        $statement = DBManager::get()->prepare("
            SELECT * FROM document_licenses ORDER BY license_id <> 2 DESC, license_id ASC
        ");
        $statement->execute();
        $this->licenses = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggle_action($dokument_id)
    {
        global $perm;
        $course = Course::find(Request::get('cid'));
        
        
        $this->file = new StudipDocument($dokument_id);
        if (
            ($this->file->checkAccess($GLOBALS['user']->id) && 
                $this->file['user_id'] === $GLOBALS['user']->id) ||
            ($course && $perm->have_perm('admin'))
            )
        {
            $this->file['protected'] = Request::int("protected", 0);
            $this->file->store();
        }
        $this->render_text($this->icons[$this->file['protected']]);
    }

    public function command_action() {
        if (Request::isPost()) {
            $course = Course::find(Request::get('cid'));
            global $perm;
            
            if (Request::get("action") === "delete") {
                foreach (Request::getArray("d") as $file_id) {
                    $file = new StudipDocument($file_id);
                    if (
                        ($file->checkAccess($GLOBALS['user']->id) &&
                            $file['user_id'] === $GLOBALS['user']->id) ||
                            ($course && $perm->have_perm('admin'))
                        ){
                        $file->delete();
                    }
                }
                PageLayout::postMessage(MessageBox::success(dgettext('lizenzstatus', "Ausgewählte Dateien wurden gelöscht.")));
                $this->redirect(
                    PluginEngine::getUrl(
                        $this->plugin,
                        array(
                            'semester_id' => Request::option("semester_id"),
                            'cid' => Request::get('cid')
                        ),
                        'my/files'
                    )
                );
            } elseif (Request::get("action") === "selectlicense") {
                $_SESSION['SWITCH_FILES'] = Request::getArray("d");
                $this->redirect(
                    PluginEngine::getUrl(
                        $this->plugin,
                        array(
                            'semester_id' => Request::option("semester_id"),
                            'cid' => Request::get('cid')
                        ),
                        'my/selectlicense'
                    )
                );
            } elseif (Request::get("action") === "download") {
                $_SESSION['DOWNLOAD_FILES'] = Request::getArray("d");
                $this->redirect(
                    PluginEngine::getUrl(
                        $this->plugin,
                        array(
                            'semester_id' => Request::option("semester_id"),
                            'cid' => Request::get('cid')
                        ),
                        'my/download'
                    )
                );
            }
        }
    }

    public function selectlicense_action() {
        PageLayout::setTitle(dgettext('lizenzstatus', "Ausgewählte Dateien verändern"));
        
        $course = Course::find(Request::get('cid'));
        global $perm;
        if($course and !$perm->have_perm('admin')) {
            PageLayout::postMessage(
                MessageBox::error(
                    dgettext('lizenzstatus', 'Sie sind nicht dazu berechtigt, Dateien einer Veranstaltung zu ändern!')
                )
            );
            return;
        }
        
        if (Request::isPost()) {
            foreach (Request::getArray("d") as $file) {
                $this->file = new StudipDocument($file);
                if (($this->file->checkAccess($GLOBALS['user']->id) && $this->file['user_id'] === $GLOBALS['user']->id) || 
                    ($course and $perm->have_perm('admin'))) {
                    $this->file['protected'] = Request::int("license", 0);
                    $this->file->store();
                }
            }
            PageLayout::postMessage(MessageBox::success(sprintf(dgettext('lizenzstatus', "%s Dokumente verändert."), count(Request::getArray("d")))));
            if(Request::option('semester_id')) {
                $this->redirect(PluginEngine::getUrl(
                    $this->plugin,
                    array(
                        'semester_id' => Request::option('semester_id')
                    ),
                    'my/files'
                ));
            } elseif(Request::get('cid')) {
                $this->redirect(PluginEngine::getUrl(
                    $this->plugin,
                    array(
                        'cid' => Request::get('cid')
                    ),
                    'my/files'
                ));
            } else {
                $this->redirect(PluginEngine::getUrl(
                    $this->plugin,
                    array(),
                    'my/files'
                ));
            }
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

    public function download_action()
    {
        global $perm;
        $course = Course::find(Request::get('cid'));
        
        $folder = $GLOBALS['TMP_PATH']."/".md5(uniqid());
        mkdir($folder);
        foreach ($_SESSION['DOWNLOAD_FILES'] as $file_id) {
            $datei = new StudipDocument($file_id);
            if (($datei->checkAccess($GLOBALS['user']->id)) ||
                $course && $perm->have_perm('admin')) {
                $i = "";
                $filename_parts = explode(".", $datei['filename']);
                if (count($filename_parts) > 1) {
                    $ending = array_pop($filename_parts);
                } else {
                    $ending = "";
                }
                $name = implode(".", $filename_parts);
                $filename = $folder."/".$name.($i ? "(".$i.")" : "").($ending ? ".".$ending : "");
                while (file_exists($filename)) {
                    $i++;
                    $filename = $folder."/".$name.($i ? "(".$i.")" : "").($ending ? ".".$ending : "");
                }
                copy(get_upload_file_path($file_id), $filename);
            }
        }
        unset($_SESSION['DOWNLOAD_FILES']);
        create_zip_from_directory($folder, $folder.".zip");
        echo file_get_contents($folder.".zip");
        rmdirr($folder);
        @unlink($folder.".zip");
        if($course and $perm->have_perm('admin')) {
            $this->response->add_header("Content-Type", "application/zip; filename=\"Dateien von ".$course->name. ".zip\"");
            $this->response->add_header("Content-Disposition", "attachment; filename=\"Dateien von ".$course->name. ".zip\"");
        } else {
            $this->response->add_header("Content-Type", "application/zip; filename=\"Meine Dateien.zip\"");
            $this->response->add_header("Content-Disposition", "attachment; filename=\"Meine Dateien.zip\"");
        }
        $this->render_nothing();
    }



}