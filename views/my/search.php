<? if (!$error): ?>
<form name="courseSearch" action="" method="post" class="default">
    <fieldset>
        <legend><?= dgettext('lizenzstatus', 'Suche nach Veranstaltungen'); ?></legend>
        <label>
            <?= dgettext('lizenzstatus', 'Name der Veranstaltung') . ':'; ?>
            <input type="text" minlength="2" name="criteria"
                value="<?= htmlReady($criteria) ?>">
        </label>
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
    <caption><?= dgettext('lizenzstatus', 'Suchergebnisse') ?></caption>
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
                ) ?>"><?= htmlReady($course->getFullName()) ?></a>
            </td>
        </tr>
        <? endforeach ?>
    <? else: ?>
    <tr><td><?= dgettext('lizenzstatus', 'Es wurden keine Veranstaltungen gefunden!') ?></td></tr>
    <? endif ?>
    </tbody>
</table>
<? endif ?>
<? endif ?>
