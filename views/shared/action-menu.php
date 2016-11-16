
<?php
$attributes = function (array $attributes) {
    $result = array();
    foreach ($attributes as $key => $value) {
        if ($value === null) {
            $result[] = htmlReady($key);
        } else {
            $result[] = sprintf('%s="%s"', htmlReady($key), htmlReady($value));
        }
    }
    return implode(' ', $result);
};
?>
<nav class="lizenzstatus-action-menu">
    <div class="action-menu-icon" title="<?= htmlReady($title ?: _('Aktionen')) ?>">
        <img src="<?= htmlReady($icon) ?>" width="20px" height="20px" class="license">
        <? if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")) : ?>
            <?= Icon::create("decline", "clickable")->asImg("20px", array('class' => "decline"))  ?>
        <? else : ?>
            <?= Assets::img("icons/20/blue/decline", array('class' => "decline")) ?>
        <? endif ?>
    </div>
    <div class="action-menu-content">
        <div class="action-menu-title">
            <?= _('Lizenz auswählen') ?>
        </div>
        <ul class="action-menu-list">
        <? foreach ($actions as $action): ?>
            <li class="action-menu-item">
            <? if ($action['type'] === 'link'): ?>
                <? if ($action['link']) : ?>
                <a href="<?= $action['link'] ?>" <?= $attributes($action['attributes']) ?>>
                <? else : ?>
                    <div style="opacity: 0.5">
                <? endif ?>
                <? if ($action['icon']): ?>
                    <img src="<?= htmlReady($action['icon']) ?>" height="20px">
                <? else: ?>
                    <span class="action-menu-no-icon"></span>
                <? endif; ?>
                    <?= htmlReady($action['label']) ?>
                <? if ($action['link']) : ?>
                </a>
                <? else : ?>
                </div>
                <? endif ?>
            <? elseif ($action['type'] === 'button'): ?>
                <label>
                <? if ($action['icon']): ?>
                    <?= $action['icon']->asInput(array('name' => $action['name'])) ?>
                <? else: ?>
                    <span class="action-menu-no-icon"></span>
                    <button type="submit" name="<?= htmlReady($action['name']) ?>" style="display: none;"></button>
                <? endif; ?>
                    <?= htmlReady($action['label']) ?>
                </label>
            <? elseif ($action['type'] === 'multi-person-search'): ?>
                <?= $action['object']->render() ?>
            <? endif; ?>
            </li>
        <? endforeach; ?>
        </ul>
    </div>
</nav>