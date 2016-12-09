<p><?= dgettext('lizenzstatus', 'Auf dieser Seite können Veranstaltungen anhand deren Namen, dem Namen eines Lehrenden, einer Einrichtung und einem Semester gesucht werden.') ?></p>
<? if (!$error): ?>
<form name="search" action="" method="post" class="default">
    <fieldset>
        <legend><?= dgettext('lizenzstatus', 'Suche nach Veranstaltungen'); ?></legend>
        <label>
            <?= dgettext('lizenzstatus', 'Name der Veranstaltung') . ':'; ?>
            <input type="text" minlength="2" name="course_name"
                value="<?= htmlReady($course_name) ?>">
        </label>
        
        <label>
            <?= dgettext('lizenzstatus', 'Name eines Lehrenden') . ':'; ?>
            <input type="text" minlength="2" name="user_name"
                value="<?= htmlReady($user_name) ?>">
        </label>
<? if ($available_institutes): ?>
        <label>
            <?= dgettext('lizenzstatus', 'Einrichtung') . ':'; ?>
            <select name="institute_id" value="<?= htmlReady($available_institutes[0]['id']) ?>">
                <option value=""
                    <?= ($selected_institute_id == '') ? 'selected="selected"' : '' ?>>
                    <?= dgettext('lizenzstatus', 'alle') ?>
                </option>
                <? foreach($available_institutes as $institute) : ?>
                    <option value="<?= htmlReady($institute['id']) ?>"
                        <?= ($selected_institute_id == $institute['id']) ? 'selected="selected"' : '' ?>>
                        <?= htmlReady($institute['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>
<? endif ?>
        <label>
            <?= dgettext('lizenzstatus', 'Semester') . ':'; ?>
            <select name="semester_id">
                <option value=""
                    <?= ($selected_semester_id == '') ? 'selected="selected"' : '' ?>>
                    <?= dgettext('lizenzstatus', 'alle') ?>
                </option>
                <? foreach($available_semesters as $semester) : ?>
                <option value="<?= htmlReady($semester['id']) ?>"
                    <?= ($selected_semester_id == $semester['id']) ? 'selected="selected"' : '' ?>>
                    <?= htmlReady($semester['name']) ?>
                </option>
                <? endforeach ?>
            </select>
        </label>
        <? if(!$available_institutes): ?>
        <p><strong><?= dgettext('lizenzstatus', 'Hinweis') . ':' ?></strong>
            <?= dgettext('lizenzstatus', 'Da Sie keiner Einrichtung zugeordnet sind, können Sie Veranstaltungen nicht nach einer Einrichtung filtern!') ?>
        </p>
        <? endif?>
    </fieldset>
    <?= \Studip\Button::create(dgettext('lizenzstatus', 'Suchen')) ?>
</form>
<? if($search_was_executed): ?>
<form action="files" method="post" class="default">
<table class="default">
    <caption><?= dgettext('lizenzstatus', 'Suchergebnisse') ?>&nbsp;(<?=count($courses)?>)</caption>
    <thead>
        <tr>
            <th></th>
            <th><?= dgettext('lizenzstatus', 'Name der Veranstaltung') ?></th>
            <th><?= dgettext('lizenzstatus', 'Lehrende') ?></th>
            <th><?= dgettext('lizenzstatus', 'Semester') ?></th>
            <th><?= dgettext('lizenzstatus', 'Anzahl Dateien') ?></th>
        </tr>
    </thead>
    <tbody>
    <? if($courses): ?>
        <? foreach ($courses as $course): ?>
        <tr>
            <td>
                <input type="checkbox" name="course_id_list[]" value="<?= $course->id ?>">
            </td>
            <td>
                <a href="<?= PluginEngine::getLink(
                    $plugin,
                    array(
                        'cid' => $course->id
                    ),
                    'my/files'
                ) ?>"><?= (version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>='))
                    ? htmlReady($course->getFullName())
                    : htmlReady($course->name) ?></a>
            </td>
            <td>
                <? $db = DbManager::get();
                //CourseMember::findByCourseAndStatus is not available in Stud.IP 2.5!
                //StudipPDO::fetchAll and SimpleORMap::buildExisting aren't available either!
                $statement = $db->prepare("SELECT seminar_user.*, aum.vorname,aum.nachname,aum.email,
                    aum.username,ui.title_front,ui.title_rear
                    FROM seminar_user
                    LEFT JOIN auth_user_md5 aum USING (user_id)
                    LEFT JOIN user_info ui USING (user_id)
                    WHERE seminar_id = ? AND seminar_user.status IN(?) ORDER BY status,position,nachname");
                
                $statement->execute(array($course->id, 'dozent'));
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $course_members = array();
                foreach($statement as $key => $row) {
                    $new_object = new CourseMember();
                    $new_object->setData($row, true);
                    $new_object->setNew(false);
                    $course_members[$key] = $new_object;
                }
                ?>
                <? if($course_members): ?>
                <? foreach($course_members as $member): ?>
                    <? if(version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>=')): ?>
                        <?= htmlReady($member->getUserFullName('short')) ?>
                    <? else: ?>
                        <?= htmlReady($member->user->getFullName()) ?>
                    <? endif ?>
                <? endforeach ?>
                <? endif ?>
            </td>
            <td>
                <?= ($course->start_semester) ? $course->start_semester->name : '' ?>
            </td>
            <td>
                <? if($course_files_count[$course->id] > 0): ?>
                <strong><?= htmlReady($course_files_count[$course->id]) ?></strong>
                <? else: ?>
                <?= htmlReady($course_files_count[$course->id]) ?>
                <? endif ?>
            </td>
        </tr>
        <? endforeach ?>
    <? else: ?>
        <? if($no_parameters): ?>
        <tr><td colspan="5"><?= dgettext('lizenzstatus', 'Es wurden keine Suchparameter gesetzt!') ?></td></tr>
        <? else: ?>
        <tr><td colspan="5"><?= dgettext('lizenzstatus', 'Es wurden keine Veranstaltungen gefunden!') ?></td></tr>
        <? endif ?>
    <? endif ?>
    </tbody>
</table>
<?= \Studip\Button::create(dgettext('lizenzstatus', 'Dateien anzeigen')) ?>
</form>
<? endif ?>
<? endif ?>
