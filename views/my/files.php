<form action="<?= PluginEngine::getLink($plugin, array(), "my/command") ?>" method="post" id="action_form" data-dialog class="<?= $formclass ?>">
    <input type="hidden" id="action" name="action" value="selectlicense">
    <input type="hidden" name="semester_id" value="<?= htmlReady(Request::option("semester_id")) ?>">

    <table class="default filelist">
        <caption>
            <?= _("Ihre selbst hochgeladenen Dokumente") ?>
        </caption>
        <thead>
            <tr>
                <th>
                    <input type="checkbox" data-proxyfor=".filelist :checkbox.file">
                </th>
                <th><a><?= _("Dateiname") ?></a></th>
                <th><a><?= _("Veranstaltung") ?></a></th>
                <th><a><?= _("Semester") ?></a></th>
                <th><a><?= _("Datum") ?></a></th>
                <th><a><?= _("Lizenzstatus") ?></a></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($files as $file) : ?>
            <tr id="doc_<?= htmlReady($file->getId()) ?>" data-dokument_id="<?= htmlReady($file->getId()) ?>">
                <? $access = $file->checkAccess($GLOBALS['user']->id) ?>
                <td>
                    <? if ($access) : ?>
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
                        <?= htmlReady($file->course->name) ?>
                    </a>
                </td>
                <td data-timestamp="<?= htmlReady($file->course->start_semester->beginn) ?>">
                    <?= htmlReady($file->course->start_semester->name) ?>
                    <? if ($file->course['duration_time'] != 0) : ?>
                        -
                        <? if ($file->course['duration_time'] == -1) : ?>
                            <?= _("unbegrenzt") ?>
                        <? else : ?>
                            <?= htmlReady($file->course->end_semester->name) ?>
                        <? endif ?>
                    <? endif ?>
                </td>
                <td data-timestamp="<?= htmlReady($file['mkdate']) ?>">
                    <?= date("d.m.Y H:i", $file['mkdate'])." "._("Uhr") ?>
                </td>
                <td data-timestamp="<?= htmlReady(($file['protected'] ? 'a' : 'b').$file['name']) ?>">
                    <? if ($access) : ?>
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
        background-image: url(<?= Assets::image_path("icons/blue/arr_1down.svg") ?>);
        background-repeat: no-repeat;
        background-position: left center;
        background-size: 16px 16px;
    }
    .filelist th.sortdesc {
        padding-left: 20px;
        background-image: url(<?= Assets::image_path("icons/blue/arr_1up.svg") ?>);
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
                "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/lizenzstatus/my/toggle/" + dokument_id,
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
$actions = new ActionsWidget();
$actions->addLink(
    _("Lizenzen der ausgewählten Dokumente setzen"),
    PluginEngine::getURL($plugin, array(), "my/selectlicense"),
    version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
        ? Icon::create($plugin->getPluginURL()."/assets/license.svg")
        : $plugin->getPluginURL()."/assets/license.svg",
    array('onclick' => "jQuery('#action').val('selectlicense'); jQuery('#action_form').attr('data-dialog', '1').submit(); return false;")
);
if (Config::get()->ALLOW_MASS_FILE_DELETING) {
    $actions->addLink(
        _("Ausgewählte Dateien löschen."),
        "#",
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create("trash", "info")
            : Assets::image_path("icons/16/black/trash"),
        array('onClick' => "if (typeof STUDIP.Dialog.confirm !== 'undefined') { STUDIP.Dialog.confirm('". _("Wirklich alle ausgewählten Dateien löschen?") ."', function () { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); }); } else if (window.confirm('". _("Wirklich alle ausgewählten Dateien löschen?") ."')) { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); } return false;")
    );
}
Sidebar::Get()->addWidget($actions);

$semester_select = new SelectWidget(
    _("Nach Semester filtern"),
    PluginEngine::getLink($plugin, array(), "my/files"),
    "semester_id"
);
$semesters = array_reverse(Semester::getAll());
$semesterdata = array("" => _("Alle"));
foreach ($semesters as $semester) {
    $semesterdata[$semester->getId()] = $semester['name'];
}
$semester_select->setOptions($semesterdata);
Sidebar::Get()->addWidget($semester_select);
