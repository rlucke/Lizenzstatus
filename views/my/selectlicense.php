<form class="<?= $formclass ?>" action="<?= PluginEngine::getLink($plugin, array(), "my/selectlicense") ?>" method="post">
    <input type="hidden" name="semester_id" value="<?= htmlReady(Request::option("semester_id")) ?>">
    <input type="hidden" name="cid" value="<?= htmlReady(Request::option("cid")) ?>">
    <input type="hidden" name="user_id" value="<?= htmlReady(Request::option("user_id")) ?>">
    <? foreach ($files as $file) : ?>
        <input type="hidden" name="d[]" value="<?= htmlReady($file) ?>">
    <? endforeach ?>
    <fieldset>
        <legend><?= dgettext('lizenzstatus', "Lizenz auswählen") ?></legend>

        <? foreach ($licenses as $license) : ?>
        <div style="display: flex; align-items: baseline; margin-top: 20px;">
        <div>
            <input type="radio" name="license" value="<?= htmlReady($license['license_id']) ?>" id="license_<?= htmlReady($license['license_id']) ?>" required>
            </div>
            <div>
            <label for="license_<?= htmlReady($license['license_id']) ?>">
                <h3 style="margin-top: 0px;">
                    <?= htmlReady($license['name']) ?>
                    <img src="<?= $icons[$license['license_id']] ?>" height="20px" class="text-bottom">
                </h3>
                <div>
                    <?= formatReady($license['description']) ?>
                </div>
            </label>
            </div>
        </div>
        <? endforeach ?>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(dgettext('lizenzstatus', "Speichern"), 'store') ?>
    </div>
</form>