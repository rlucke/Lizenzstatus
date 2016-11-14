<form class="<?= $formclass ?>" action="<?= PluginEngine::getLink($plugin, array(), "my/selectlicense") ?>" method="post">
    <? foreach ($files as $file) : ?>
        <input type="hidden" name="d[]" value="<?= htmlReady($file) ?>">
    <? endforeach ?>
    <fieldset>
        <legend><?= _("Lizenz auswählen") ?></legend>

        <? foreach ($licenses as $license) : ?>
        <div style="display: flex; align-items: baseline; margin-top: 20px;">
            <input type="radio" name="license" value="<?= htmlReady($license['license_id']) ?>" id="license_<?= htmlReady($license['license_id']) ?>" required>
            <label for="license_<?= htmlReady($license['license_id']) ?>">
                <h3 style="margin-top: 0px;"><?= htmlReady($license['name']) ?></h3>
                <div>
                    <?= formatReady($license['description']) ?>
                </div>
            </label>
        </div>
        <? endforeach ?>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>