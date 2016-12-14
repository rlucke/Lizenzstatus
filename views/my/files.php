<form action="<?= PluginEngine::getLink($plugin, array(), "my/command") ?>" method="post" id="action_form" data-dialog class="<?= $formclass ?>">
    <input type="hidden" id="action" name="action" value="selectlicense">
    <input type="hidden" name="semester_id" value="<?= htmlReady(Request::option("semester_id")) ?>">

    <table class="default filelist">
        <caption>
            <? if ($course): ?>
            <?= sprintf(
                dgettext('lizenzstatus', 'Hochgeladene Dokumente in Veranstaltung %s'),
                $course->name
                ) ?>&nbsp;(<?= dgettext('lizenzstatus', 'insgesamt') . ' ' . count($files)?>)
            <? elseif($user && $user_search): ?>
            <?= sprintf(
                    dgettext('lizenzstatus', "Hochgeladenen Dokumente von %s"),
                    (version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>=')
                    ? htmlReady($user->getFullName())
                    : htmlReady($user->vorname . ' ' . $user->nachname)
                    )
                ) ?>&nbsp;(<?= dgettext('lizenzstatus', 'insgesamt') . ' ' . count($files)?>)
            <? elseif($course_list): ?>
            <?= dgettext('lizenzstatus', "Hochgeladenen Dokumente aus Veranstaltungen")
                ?>&nbsp;(<?= dgettext('lizenzstatus', 'insgesamt') . ' ' . count($files)?>)
            <? else: ?>
            <?= dgettext('lizenzstatus', "Ihre selbst hochgeladenen Dokumente")
                ?>&nbsp;(<?= dgettext('lizenzstatus', 'insgesamt') . ' ' . count($files)?>)
            <? endif ?>
        </caption>
        <thead>
            <tr>
                <th>
                    <input type="checkbox" data-proxyfor=".filelist :checkbox.file">
                </th>
                <th><a><?= dgettext('lizenzstatus', "Dateiname") ?></a></th>
                <? if ($course_list or !$course): ?>
                <th><a><?= dgettext('lizenzstatus', "Veranstaltung") ?></a></th>
                <th><a><?= dgettext('lizenzstatus', "Semester") ?></a></th>
                <? endif ?>
                <? if ($course_list or $course): ?>
                <th><a><?= dgettext('lizenzstatus', 'Nutzer') ?></a></th>
                <? endif ?>
                <th><a><?= dgettext('lizenzstatus', "Datum") ?></a></th>
                <th><a><?= dgettext('lizenzstatus', "Lizenzstatus") ?></a></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($files as $file) : ?>
            <? $access = $file->checkAccess($GLOBALS['user']->id) || $user_search  || $course_list ?>
            <? if (!$access and !$course) continue; ?>
            <tr id="doc_<?= htmlReady($file->getId()) ?>" data-dokument_id="<?= htmlReady($file->getId()) ?>">
                <td>
                    <? if ($access or $course) : ?>
                        <input type="checkbox" class="file" name="d[]" value="<?= $file->getId() ?>">
                    <? endif ?>
                </td>
                <td>
                    <? if ($access) : ?>
                    <a href="<?= GetDownloadLink(
                        $file['dokument_id'],
                        $file['filename'],
                        ($file['url'] != '' ? 6 : 0)
                    )  ?>">
                    <? endif ?>
                        <?= htmlReady($file['name']) ?>
                    <? if ($access) : ?>
                    </a>
                    <? endif ?>
                </td>
                <? if ($course_list or !$course): ?>
                    <td>
                        <a href="<?= URLHelper::getLink("folder.php#anker", array(
                            'cid' => $file['seminar_id'],
                            'data' => array(
                                'cmd' => "tree",
                                'open' => array(
                                    $file['range_id'] => 1,
                                    $file->getId() => 1
                                )
                            ),
                            'open' => $file->getId()
                        )) ?>">
                        <? $file_course = Course::find($file->seminar_id); ?>

                            <?= htmlReady($file_course ? $file_course->name : $file->institute->name) ?>
                        </a>
                    </td>
                    <? if ($file_course) : ?>
                    <td data-timestamp="<?= htmlReady($file_course->start_semester->beginn) ?>">
                        <?= htmlReady($file_course->start_semester->name) ?>
                        <? if ($file_course['duration_time'] != 0) : ?>
                            -
                            <? if ($file_course['duration_time'] == -1) : ?>
                                <?= dgettext('lizenzstatus', "unbegrenzt") ?>
                            <? else : ?>
                                <?= htmlReady($file_course->end_semester->name) ?>
                            <? endif ?>
                        <? endif ?>
                    </td>
                    <? else : ?>
                        <td data-timestamp="0">-</td>
                    <? endif ?>
                <? endif ?>
                <? if ($course_list or $course): ?>
                <? $file_user = User::find($file->user_id); ?>
                    <td>
                        <a href="<?= URLHelper::getLink("dispatch.php/profile", array(
                            'username' => $file_user['username']
                        )) ?>">
                            <?= htmlReady($file_user ? $file_user->getFullName('no_title_rev') : dgettext('lizenzstatus', 'unbekannt')) ?>
                        </a>
                    </td>
                <? endif ?>
                <td data-timestamp="<?= htmlReady($file['mkdate']) ?>">
                    <?= date("d.m.Y H:i", $file['mkdate'])." ".dgettext('lizenzstatus', "Uhr") ?>
                </td>
                <td data-timestamp="<?= $file['protected'] + 1?>">
                    <? if ($access or $course) : ?>
                        <?
                            $actionmenu = MyProtectedFiles\ActionMenu::get();
                            $actionmenu->icon = $icons[$file['protected']];
                            foreach ($licenses as $license) {
                                $actionmenu->addLink(
                                    $license['license_id'] > 1 ? "#" : "",
                                    $license['name'],
                                    $icons[$license['license_id']],
                                    array(
                                        'onclick' => "STUDIP.Lizenzstatus.toggle.call(this, '" . $license['license_id'] . "'); return false;"
                                    )
                                );
                                if ($license['license_id'] == $file['protected']) {
                                    $actionmenu->title = $license['name'];
                                }
                            }
                            echo $actionmenu->render();
                        ?>
                    <? else : ?>
                        <?= Assets::img("icons/20/black/checkbox-".($file['protected'] ? "un" : "")."checked", array('class' => "unchecked")) ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
    </table>

</form>

<style>
    .filelist th.sortasc {
        padding-left: 20px;
        background-image: url(<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/arr_1down.svg' ?>);
        background-repeat: no-repeat;
        background-position: left center;
        background-size: 16px 16px;
    }
    .filelist th.sortdesc {
        padding-left: 20px;
        background-image: url(<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/data-quest/Lizenzstatus/assets/arr_1up.svg' ?>);
        background-repeat: no-repeat;
        background-position: left center;
        background-size: 16px 16px;
    }
</style>

<script>
    STUDIP.Lizenzstatus = {
        toggle: function (license_id) {
            var dokument_id = jQuery(this).closest("tr").data("dokument_id");
            jQuery.ajax({
            <? if ($course) : ?>
                "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/lizenzstatus/my/toggle/" + dokument_id + '?course_id=<?= $course->id ?>',
            <? else : ?>
                "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/lizenzstatus/my/toggle/" + dokument_id,
            <? endif ?>
                "data": {
                    "protected": license_id
                },
                "type": "post",
                "success": function (image_url) {
                    jQuery("#doc_" + dokument_id + " .action-menu-icon .license").attr("src", image_url);
                    jQuery("#doc_" + dokument_id + " .action-menu-icon").trigger("click");
                }
            });
        }
    };
    jQuery(function () {
        jQuery("table.filelist").tablesorter({
            textExtraction: function (node) {
                var node = jQuery(node);
                /*if (node.is("tr > td:last-child")) {
                    console.log(String(node.data('timestamp') || node.text()).trim());
                }*/
                return String(node.data('timestamp') || node.text()).trim();
            },
            cssAsc: 'sortasc',
            cssDesc: 'sortdesc'//,
            //sortList: [[0, 0]]
        });
    });
</script>


<?
//the sidebar is available since Stud.IP 3.1. For older versions we must build
//an infobox instead.
if(version_compare($GLOBALS['SOFTWARE_VERSION'], '3.1', '>=')) {
    //code for Stud.IP 3.1 to 3.5:
    $actions = new ActionsWidget();
    $actions->addLink(
        dgettext('lizenzstatus', "Lizenzen der ausgewählten Dokumente setzen"),
        PluginEngine::getURL($plugin, array(), "my/selectlicense"),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create($plugin->getPluginURL()."/assets/license.svg")
            : $plugin->getPluginURL()."/assets/license.svg",
        array('onclick' => "jQuery('#action').val('selectlicense'); jQuery('#action_form').attr('data-dialog', '1').submit(); return false;")
    );
    if (Config::get()->ALLOW_MASS_FILE_DELETING) {
        $actions->addLink(
            dgettext('lizenzstatus', "Ausgewählte Dateien löschen."),
            "#",
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                ? Icon::create("trash", "clickable")
                : Assets::image_path("icons/16/blue/trash"),
            array('onClick' => "if (typeof STUDIP.Dialog.confirm !== 'undefined') { STUDIP.Dialog.confirm('". dgettext('lizenzstatus', "Wirklich alle ausgewählten Dateien löschen?") ."', function () { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); }); } else if (window.confirm('". dgettext('lizenzstatus', "Wirklich alle ausgewählten Dateien löschen?") ."')) { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); } return false;")
        );
    }
    $actions->addLink(
        dgettext('lizenzstatus', "Ausgewählte Dateien herunterladen."),
        "#2",
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create("download", "clickable")
            : Assets::image_path("icons/16/blue/download"),
        array('onClick' => "jQuery('#action').val('download'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); return false;")
    );

    if(Request::get('cid') or Request::get('user_id') or
        !empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) {
        $actions->addLink(
            dgettext('lizenzstatus', 'Zurück zu meinen Dateien'),
            PluginEngine::getUrl($plugin, array(), 'my/reset'),
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                ? Icon::create('headache', 'clickable')
                : Assets::image_path('icons/16/blue/headache')
        );
    }

    Sidebar::Get()->addWidget($actions);

    if(!Request::get('cid') and !Request::get('user_id') and
        empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) {
        $semester_select = new SelectWidget(
            dgettext('lizenzstatus', "Nach Semester filtern"),
            PluginEngine::getLink($plugin, array(), "my/files"),
            "semester_id"
        );
        $semesters = array_reverse(Semester::getAll());
        $semester_select->addElement(new SelectElement("", dgettext('lizenzstatus', "Alle"), false));
        foreach ($semesters as $semester) {
            $semester_select->addElement(new SelectElement(
                $semester->getId(),
                $semester['name'],
                Request::get("semester_id") === $semester->getId()
            ));
        }
        Sidebar::Get()->addWidget($semester_select);
    }
} else {
    //code for Stud.IP 2.5 and 3.0:

    $action_links = '<a href="' . PluginEngine::getLink($plugin, array(), "my/selectlicense") . '" '
        . "onclick=\"jQuery('#action').val('selectlicense'); jQuery('#action_form').attr('data-dialog', '1').submit(); return false;\" "
        . '>' . Assets::img($plugin->getPluginURL().'/assets/license.svg',
                array('size' => '16', 'class' => 'text-bottom')
            )
        . dgettext('lizenzstatus', 'Lizenzen der ausgewählten Dokumente setzen') . '</a><br>';

    if(Config::get()->ALLOW_MASS_FILE_DELETING) {
        $action_links .= '<a href="#" onclick="'
                . "if (window.confirm('". dgettext('lizenzstatus', "Wirklich alle ausgewählten Dateien löschen?") ."')) { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); } return false;"
            . '" >' . Assets::img('icons/16/blue/trash', array('size' => '16'))
            . dgettext('lizenzstatus', 'Ausgewählte Dateien löschen') . '</a><br>';
    }

    $action_links .= '<a href="#" onclick="'
        . "jQuery('#action').val('download'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); return false;"
        . '" >' . Assets::img('icons/16/blue/download', array('size' => '16'))
        . dgettext('lizenzstatus', 'Ausgewählte Dateien herunterladen.') . '</a><br>';

    if(Request::get('cid') or Request::get('user_id') or
        !empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) {
        $action_links .= '<a href="' . PluginEngine::getLink($plugin, array(), 'my/reset') . '" >'
            . Assets::img('icons/16/blue/headache', array('class' => 'text-bottom'))
            . dgettext('lizenzstatus', 'Zurück zu meinen Dateien') . '</a><br>';
    }

    if(!Request::get('cid') and !Request::get('user_id') and
        empty($_SESSION['LIZENZSTATUS_SELECTED_COURSE_IDS'])) {
        $semesters = array_reverse(Semester::getAll());

        $semester_select = '<form novalidate="novalidate" action="'
            . PluginEngine::getLink($plugin, array(), 'my/files')
            . '" method="get"><select name="semester_id" onchange="$(this).closest(\'form\').submit();">';

        $semester_select .= '<option value="" '
                . (!Request::get('semester_id') ? 'selected="selected"' : '' )
                . '>' . dgettext('lizenzstatus', 'Alle') . '</option>';

        foreach($semesters as $semester) {
            $semester_select .= '<option value="'. $semester['id'] .'"'
                . ((Request::get('semester_id') === $semester['id']) ? 'selected="selected"' : '' )
                . '>' . $semester['name'] . '</option>';
        }

        $semester_select .= '</select><br><noscript>
            <button type="submit" class="button" name="Zuweisen">' . dgettext('lizenzstatus', 'Zuweisen')
            . '</button></noscript></form>';
    }


    $infobox = array(
        'picture' => '',
        'content' => array(
            array(
                'kategorie' => dgettext('lizenzstatus', 'Aktionen:'),
                'eintrag' => array(
                    array(
                        'icon' => 'icons/16/black/info.png',
                        'text' => $action_links
                    )
                )
            ),
        )
    );
    if(!Request::get('cid')) {
        $infobox['content'][] = array(
            'kategorie' => dgettext('lizenzstatus', 'Nach Semester filtern:'),
            'eintrag' => array(
                array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => $semester_select
                )
            )
        );
    }
}
