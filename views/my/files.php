<form action="<?= PluginEngine::getLink($plugin, array(), "my/command") ?>" method="post" id="action_form" data-dialog class="<?= $formclass ?>">
    <input type="hidden" id="action" name="action" value="selectlicense">

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
                            foreach ($licenses as $license) {
                                $actionmenu->addButton("license_id", $license['name'], null, array('value' => $license['license_id']));
                            }
                            echo $actionmenu->render();
                        ?>
                    <a href="#" class="license-chooser license_<?= htmlReady($file['protected']) ?>" onClick="STUDIP.MyProtectedFiles.toggle.call(this); return false;">
                        <? foreach ($licenses as $license) : ?>
                            <span class="license_<?= htmlReady($license['license_id']) ?>">
                            <? switch ($license['license_id']) {
                                case "0":
                                    echo Assets::img("icons/16/blue/checkbox-checked", array('class' => "text-bottom"));
                                    break;
                                case "1":
                                    echo Assets::img("icons/16/blue/checkbox-unchecked", array('class' => "text-bottom"));
                                    break;
                                case "3":
                                    echo "©";
                                    break;
                                case "4":
                                    echo '<img src="'. $plugin->getPluginURL() .'/assets/cc.svg" height="16px" class="text-bottom">';
                                    break;
                                case "5":
                                    echo Assets::img("icons/16/blue/medal", array('class' => "text-bottom"));
                                    break;
                                case "6":
                                    echo Assets::img("icons/16/blue/file-pic", array('class' => "text-bottom"));
                                    break;
                                case "7":
                                    echo Assets::img("icons/16/blue/literature", array('class' => "text-bottom"));
                                    break;
                                default:
                                    echo Assets::img("icons/16/blue/question-circle", array('class' => "text-bottom"));
                            } ?>
                            <?= htmlReady($license['name']) ?>
                        </span>
                        <? endforeach ?>
                    </a>
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
    .filelist .license-chooser > * {
        display: none;
    }
    .filelist .license-chooser.license_0 > .license_0 {
        display: block;
    }
    .filelist .license-chooser.license_1 > .license_1 {
        display: block;
    }
    .filelist .license-chooser.license_2 > .license_2 {
        display: block;
    }
    .filelist .license-chooser.license_3 > .license_3 {
        display: block;
    }
    .filelist .license-chooser.license_4 > .license_4 {
        display: block;
    }
    .filelist .license-chooser.license_5 > .license_5 {
        display: block;
    }
    .filelist .license-chooser.license_6 > .license_6 {
        display: block;
    }
    .filelist .license-chooser.license_7 > .license_7 {
        display: block;
    }


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
    STUDIP.MyProtectedFiles = {
        toggle: function () {
            var dokument_id = jQuery(this).closest("tr").data("dokument_id");
            var oldlicensestate = jQuery(this).attr("class");
            oldlicensestate = oldlicensestate.substr(oldlicensestate.indexOf("_") + 1);
            var newlicensestate = jQuery(this).find(".license_" + oldlicensestate).next().attr("class");
            if (typeof newlicensestate === "undefined") {
                newlicensestate = 3;
            } else {
                newlicensestate = newlicensestate.substr(newlicensestate.indexOf("_") + 1);
            }
            jQuery.ajax({
                "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/myprotectedfiles/my/toggle/" + dokument_id,
                "data": {
                    "protected": newlicensestate
                },
                "type": "post",
                "success": function () {
                    jQuery("#doc_" + dokument_id + " .license-chooser").removeClass("license_" + oldlicensestate).addClass("license_" + newlicensestate);
                }
            });
        }
    };
    jQuery(function () {
        jQuery("table.filelist").tablesorter({
            textExtraction: function (node) {
                var node = jQuery(node);
                if (node.is("tr > td:last-child")) {
                    console.log(String(node.data('timestamp') || node.text()).trim());
                }
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
    Assets::image_path("icons/16/black/remove/checkbox-unchecked"),
    array('onclick' => "jQuery('#action').val('selectlicense'); jQuery('#action_form').attr('data-dialog', '1').submit(); return false;")
);
if (Config::get()->ALLOW_MASS_FILE_DELETING) {
    $actions->addLink(
        _("Ausgewählte Dateien löschen."),
        "#",
        Assets::image_path("icons/16/black/trash"),
        array('onClick' => "if (typeof STUDIP.Dialog.confirm !== 'undefined') { STUDIP.Dialog.confirm('". _("Wirklich alle ausgewählten Dateien löschen?") ."', function () { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); }); } else if (window.confirm('". _("Wirklich alle ausgewählten Dateien löschen?") ."')) { jQuery('#action').val('delete'); jQuery('#action_form').removeAttr('data-dialog', '1').submit(); } return false;")
    );
}

Sidebar::Get()->addWidget($actions);