<?php

require_once __DIR__.'/../Lizenzstatus.class.php';
require_once 'lib/classes/PageLayout.php';
require_once 'lib/classes/searchtypes/SeminarSearch.class.php';
require_once 'lib/datei.inc.php';
require_once 'lib/models/StudipDocument.class.php';
require_once 'lib/models/Institute.class.php';


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
        PageLayout::setTitle(dgettext('lizenzstatus', "Lizenzstatus"));
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


    public function my_action()
    {

    }


    /**
     * Responsible for removing the cid-parameter
     */
    public function reset_action()
    {
        URLHelper::removeLinkParam('cid');
        URLHelper::removeLinkParam('user_id');

        $_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'] = null;

        $this->redirect(PluginEngine::getURL($this->plugin, array(), 'my/files'));
    }


    public function search_user_action()
    {
        Navigation::activateItem("/myprotectedfiles");
        if(Navigation::hasItem("/myprotectedfiles/search_user")) {
            Navigation::activateItem("/myprotectedfiles/search_user");
        }

        URLHelper::removeLinkParam('cid');

        global $perm;

        if(!$perm->have_perm('root')) {
            PageLayout::postMessage(
                MessageBox::error(
                    dgettext('lizenzstatus', 'Sie sind nicht dazu berechtigt, diese Seite aufzurufen!')
                )
            );

            $this->error = true;
            return;
        }

        $this->user_name = Request::get('user_name', null);
        if($this->user_name) {
            //searched for teacher

            $this->users = User::findBySql(
                "(vorname LIKE CONCAT('%', :user_name, '%')
                OR nachname LIKE CONCAT('%', :user_name, '%')
                OR username LIKE CONCAT('%', :user_name, '%'))
                AND perms = 'dozent'",
                array(
                    'user_name' => $this->user_name
                )
            );

            $this->search_was_executed = true;
        }

        if($this->users) {
            //calculate number of files for each user:
            $this->user_files_count = array();
            foreach($this->users as $user) {
                $this->user_files_count[$user->id] = StudipDocument::countBySql(
                    "user_id = :user_id AND url = ''",
                    array(
                        'user_id' => $user->id
                    )
                );
            }
        }
    }


    public function search_action()
    {
        Navigation::activateItem("/myprotectedfiles");
        if(Navigation::hasItem("/myprotectedfiles/search")) {
            Navigation::activateItem("/myprotectedfiles/search");
        }

        URLHelper::removeLinkParam('user_id');

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
        $this->institute_id = Request::get('institute_id', '');
        $this->course_name = Request::get('course_name', null);
        $this->user_name = Request::get('user_name', null);
        $this->selected_semester_id = $this->semester_id;



        //semester selector is always filled:
        $this->available_semesters = Semester::getAll();


        $this->available_institutes = Institute::findBySql(
            "institut_id IN
            (SELECT institut_id FROM user_inst
            WHERE user_id = :user_id)
            GROUP BY name ORDER BY name ASC",
            array(
                'user_id' => $GLOBALS['user']->id
            )
        );

        /*
        //does not work with Stud.IP 2.5:
        $this->available_institutes = Institute::findBySql(
            "INNER JOIN user_inst
            ON
            Institute.institut_id = user_inst.institut_id
            WHERE
            user_inst.user_id = :user_id
            ORDER BY name ASC",
            array(
                'user_id' => $GLOBALS['user']->id
            )
        );
        */

        if(!$this->semester_id and !$this->institute_id and !$this->course_name
            and !$this->user_name) {
                //no search was started
                $this->no_parameters = true;
                return;
        }


        $institute_id_list = array();

        $sql = 'SELECT seminare.* FROM seminare ';
        $sql_params = array();


        if($this->user_name) {
            $sql .= "INNER JOIN seminar_user ON seminare.seminar_id = seminar_user.seminar_id
                INNER JOIN auth_user_md5 ON seminar_user.user_id = auth_user_md5.user_id
                WHERE (auth_user_md5.username LIKE CONCAT('%', :user_name, '%')
                    OR auth_user_md5.vorname LIKE CONCAT('%', :user_name, '%')
                    OR auth_user_md5.nachname LIKE CONCAT('%', :user_name, '%'))
                    AND (auth_user_md5.perms = 'dozent') ";
            $sql_params['user_name'] = $this->user_name;
        }


        $institute_id_list = array();

        if($this->institute_id) {
            //The user wants to get all courses of an institute:
            $institute = Institute::find($this->institute_id);

            if($institute) {

                $institute_id_list[] = $institute->id;

                if($institute->is_fak) {
                    //for facultys, we must also look at the courses from
                    //institutes inside the faculty:
                    $sub_institutes = Institute::findByFakultaets_id($institute->id);

                    foreach($sub_institutes as $sub_institute) {
                        $institute_id_list[] = $sub_institute->id;
                    }
                }

                $this->selected_institute_id = $this->institute_id;

            }

        } else {
            //In case no institute is given we must limit the search results
            //to the institutes where the user is admin

            $current_user_id = $GLOBALS['user']->id; //for Stud.IP 2.5 compatibility

            $institute_memberships = InstituteMember::findBySql(
                'user_id = :user_id',
                array(
                    'user_id' => $current_user_id
                )
            );

            if($institute_memberships) {

                foreach($institute_memberships as $membership) {
                    $institute = $membership->institute;

                    $institute_id_list[] = $institute->id;

                    if($institute->is_fak) {
                        //for facultys, we must also look at the courses from
                        //institutes inside the faculty:
                        $sub_institutes = Institute::findByFakultaets_id($institute->id);

                        foreach($sub_institutes as $sub_institute) {
                            $institute_id_list[] = $sub_institute->id;
                        }
                    }
                }
            }
        }

        if($institute_id_list) {
            $institute_id_list = array_unique($institute_id_list);

            $sql_params['institute_id_list'] = $institute_id_list;

            if($this->user_name) {
                $sql .= ' AND ';
            } else {
                $sql .= ' WHERE ';
            }

            $sql .= "(seminare.institut_id in ( :institute_id_list )) ";


        }

        $semester = null;
        if($this->semester_id) {
            //semester-ID selected
            $semester = Semester::find($this->semester_id);

            if($semester) {
                if($this->user_name or $institute_id_list) {
                    $sql .= ' AND ';
                } else {
                    $sql .= ' WHERE ';
                }
                $sql .= "( seminare.start_time = :semester_start_time
                    OR (seminare.start_time < :semester_start_time AND seminare.duration_time = -1)
                    OR (seminare.start_time < :semester_start_time AND seminare.start_time + seminare.duration_time >= :semester_start_time) ) ";
                $sql_params['semester_start_time'] = $semester->beginn;
            }
        }


        if($this->course_name) {
            if($this->user_name or $institute_id_list or $semester) {
                $sql .= ' AND ';
            } else {
                $sql .= ' WHERE ';
            }
            $sql .= "(seminare.name LIKE CONCAT('%', :course_name, '%')
                OR seminare.untertitel LIKE CONCAT('%', :course_name, '%')
                OR seminare.beschreibung LIKE CONCAT('%', :course_name, '%')) ";
            $sql_params['course_name'] = $this->course_name;
        }

        $sql .= "GROUP BY name ORDER BY seminare.name ASC";

        //findBySql doesn't support joins in Stud.IP 2.5. We must build our
        //courses "the hard way":

        $db = DBManager::get();

        $statement = $db->prepare($sql);
        $statement->execute($sql_params);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $this->courses = array();
        foreach($statement as $key => $row) {
            $new_object = new Course();
            $new_object->setData($row, true);
            $new_object->setNew(false);
            $this->courses[$key] = $new_object;
        }

        //$this->courses = Course::findBySql($sql, $sql_params);


        $this->search_was_executed = true;


        if($this->courses) {
            //calculate number of files for each course:
            $this->course_files_count = array();
            foreach($this->courses as $course) {
                $this->course_files_count[$course->id] = StudipDocument::countBySql(
                    "seminar_id = :course_id AND url = ''",
                    array(
                        'course_id' => $course->id
                    )
                );
            }
        }

    }


    public function files_action()
    {
        global $perm;

        if(!Request::get('cid')) {
            URLHelper::removeLinkParam('cid');
        }

        if(!Request::get('user_id')) {
            URLHelper::removeLinkParam('user_id');
        }


        if(Navigation::hasItem("/myprotectedfiles/files")) {
            Navigation::activateItem("/myprotectedfiles/files");
        }

        PageLayout::addScript($this->plugin->getPluginURL()."/assets/jquery.tablesorter-2.22.5.js");

        $course_id_list = Request::getArray('course_id_list', array());
        if(!$course_id_list) {
            $course_id_list = $_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'];
        }

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
        } elseif(Request::option('user_id')) {
            //The user wants to see the files of a teacher.
            $_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'] = null; //must be an old search

            $this->user = User::find(Request::get('user_id'));
            if(!$this->user) {
                //no results
                $this->search_was_executed = false;
                URLHelper::removeLinkParam('user_id');
                $this->redirect(PluginEngine::getUrl(
                        $this->plugin,
                        $params,
                        'my/files'
                ));
                return;
            }

            if(!$perm->have_perm('root')
                or ($this->user->perms != 'dozent')) {
                //user is not root or the target user is not a teacher
                throw new AccessDeniedException();
            } else {
                $this->user_search = true;
                URLHelper::addLinkParam('user_id', $this->user->id);
            }

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
            $statement->execute(array($this->user->id));
            $data = $statement->fetchAll(PDO::FETCH_ASSOC); //fetchFirst istn't available in Stud.IP 2.5
            $this->files = array();
            foreach($data as $key => $row) {
                //SORM::buildExisting and SORM::build are not available in Stud.IP 2.5:
                $this->files[$key] = new StudipDocument();
                $this->files[$key]->setData($row, false);
                $this->files[$key]->setNew(false);
            }


        } elseif (Request::option("cid")) {
            //The user wants to see all files of a course.
            $_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'] = null; //must be an old search

            if (!$perm->have_studip_perm('admin', Request::option("cid"))) {
                throw new AccessDeniedException();
            }

            $this->course = Course::find(Request::option("cid"));
            if(!$this->course) {
                PageLayout::postMessage(
                    MessageBox::error(
                        dgettext('lizenzstatus', 'Veranstaltung nicht gefunden!')
                    )
                );
                return;
            }

            URLHelper::addLinkParam('cid', $this->course->id);

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
        } elseif (!empty($course_id_list) or !empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) {
            //The user selected a list of courses: Display all files of these courses.

            if(empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) {
                $_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'] = $course_id_list;
            }

            $this->course_list = true;

            $this->course_names = array();

            $db = DBManager::get();
            $statement = $db->prepare('SELECT name FROM seminare WHERE seminar_id = :course_id LIMIT 1');

            foreach($course_id_list as $course_id) {
                $statement->execute(
                    array(
                        'course_id' => $course_id
                    )
                );

                $course_name = $statement->fetchColumn(0);

                if (!$perm->have_studip_perm('admin', $course_id)) {
                    throw new AccessDeniedException(
                        'No permission to see files of course ' . $course_name . '!'
                    );
                }

                $this->course_names[$course_id] = $course_name;
            }


            $statement = DBManager::get()->prepare("
                SELECT dokumente.*
                FROM dokumente
                WHERE
                dokumente.seminar_id IN ( :course_id_list )
                AND
                dokumente.url = ''
                ORDER BY mkdate DESC
            ");

            $statement->execute(array(
                'course_id_list' => $course_id_list
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

        $this->file = new StudipDocument($dokument_id);
        if (
            ($this->file->checkAccess($GLOBALS['user']->id) &&
                $this->file['user_id'] === $GLOBALS['user']->id) ||
            ($perm->have_studip_perm('admin', $this->file['seminar_id']))
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
            $user = User::find(Request::get('user_id'));
            global $perm;

            $params = array();

            if(Request::option('semester_id')) {
                $params['semester_id'] = Request::option('semester_id');
            }

            if($course) {
                $params['cid'] = $course->id;
            }

            if($user) {
                $params['user_id'] = $user->id;
            }



            if (Request::get("action") === "delete") {
                $deleted_files_count = 0;
                foreach (Request::getArray("d") as $file_id) {
                    $file = new StudipDocument($file_id);
                    if (
                        ($file->checkAccess($GLOBALS['user']->id) &&
                            $file['user_id'] === $GLOBALS['user']->id) ||
                            (($course or !empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS']))
                                && $perm->have_perm('admin')) ||
                            ($user && ($user->perms == 'dozent') && $perm->have_perm('admin'))
                        ){
                        $file->delete();
                        $deleted_files_count++;
                    }
                }
                PageLayout::postMessage(MessageBox::success(
                    sprintf(
                        dgettext('lizenzstatus', "%s Dateien wurden gelöscht."),
                        $deleted_files_count
                    )
                ));


                $this->redirect(
                    PluginEngine::getUrl(
                        $this->plugin,
                        $params,
                        'my/files'
                    )
                );

            } elseif (Request::get("action") === "selectlicense") {
                $_SESSION['SWITCH_FILES'] = Request::getArray("d");

                $this->redirect(
                    PluginEngine::getUrl(
                        $this->plugin,
                        $params,
                        'my/selectlicense'
                    )
                );

            } elseif (Request::get("action") === "download") {
                $_SESSION['DOWNLOAD_FILES'] = Request::getArray("d");

                $this->redirect(
                    PluginEngine::getUrl(
                        $this->plugin,
                        $params,
                        'my/download'
                    )
                );
            }
        }
    }

    public function selectlicense_action() {
        PageLayout::setTitle(dgettext('lizenzstatus', "Ausgewählte Dateien verändern"));

        $course = Course::find(Request::get('cid'));
        $user = User::find(Request::get('user_id'));

        global $perm;

        if(($course or !empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) and !$perm->have_perm('admin')) {
            PageLayout::postMessage(
                MessageBox::error(
                    dgettext('lizenzstatus', 'Sie sind nicht dazu berechtigt, Dateien einer Veranstaltung zu ändern!')
                )
            );
            return;
        }
        if($user) {
            if(($user->perms != 'dozent') or !$perm->have_perm('admin')) {
                PageLayout::postMessage(
                    MessageBox::error(
                        dgettext('lizenzstatus', 'Sie sind nicht dazu berechtigt, Dateien eines anderen Nutzers zu ändern!')
                    )
                );
                return;
            }
        }


        if (Request::isPost()) {
            $changed_documents = 0;
            foreach (Request::getArray("d") as $file) {
                $this->file = new StudipDocument($file);
                if (($this->file->checkAccess($GLOBALS['user']->id) && $this->file['user_id'] === $GLOBALS['user']->id) ||
                    (($course or !empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS']))
                        and $perm->have_perm('admin')) ||
                    ($user and $user->perms == 'dozent' and $perm->have_perm('admin'))
                    ) {
                    $this->file['protected'] = Request::int("license", 0);
                    $this->file->store();
                    $changed_documents++;
                }
            }
            PageLayout::postMessage(MessageBox::success(sprintf(dgettext('lizenzstatus', "%s Dokumente verändert."), $changed_documents)));


            $params = array();

            if(Request::option('semester_id')) {
                $params['semester_id'] = Request::option('semester_id');
            }


            if($course) {
                $params['cid'] = $course->id;
            }

            if($user) {
                $params['user_id'] = $user->id;
            }


            $this->redirect(PluginEngine::getUrl(
                $this->plugin,
                $params,
                'my/files'
            ));

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
        global $perm, $TMP_PATH;

        $course = Course::find(Request::get('cid'));
        $user = User::find(Request::get('user_id'));


        $zipfile_id = createSelectedZip($_SESSION['DOWNLOAD_FILES'], false);

        if($course and $perm->have_perm('admin')) {
            $file_name = "Dateien von ".$course->name.'.zip';
        } elseif(($user && ($user->perms == 'dozent') && $perm->have_perm('admin'))) {
            $file_name = "Dateien von ".$user->username.'.zip';
        } else {
            $file_name = "Meine Dateien.zip";
        }
        $this->redirect(getDownloadLink( $zipfile_id, $file_name, 4, 'force'));
    }



}