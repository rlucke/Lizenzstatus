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
    </fieldset>
    <?= \Studip\Button::create(dgettext('lizenzstatus', 'Suchen')) ?>
</form>
<? if($search_was_executed): ?>
<table class="default">
    <caption><?= dgettext('lizenzstatus', 'Suchergebnisse') ?>&nbsp;(<?=count($courses)?>)</caption>
    <thead>
        <tr>
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
                <? $course_members = CourseMember::findByCourseAndStatus($course->id, 'dozent'); ?>
                <? if($course_members): ?>
                <? foreach($course_members as $member): ?>
                <?= htmlReady($member->getUserFullName('short')) ?>
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
        <tr><td colspan="4"><?= dgettext('lizenzstatus', 'Es wurden keine Suchparameter gesetzt!') ?></td></tr>
        <? else: ?>
        <tr><td colspan="4"><?= dgettext('lizenzstatus', 'Es wurden keine Veranstaltungen gefunden!') ?></td></tr>
        <? endif ?>
    <? endif ?>
    </tbody>
</table>
<? endif ?>
<? endif ?>
