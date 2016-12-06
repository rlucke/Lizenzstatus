<? if (!$error): ?>
<form name="courseSearch" action="" method="post" class="default">
    <fieldset>
        <legend><?= dgettext('lizenzstatus', 'Suche nach Veranstaltung') ?></legend>
        <?= $course_search->render() ?>
    </fieldset>
</form>
<? endif ?>
