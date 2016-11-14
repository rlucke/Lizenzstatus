<? foreach ($licenses as $license) : ?>
<div>
    <h2><?= htmlReady($license['name']) ?></h2>
    <div>
        <?= formatReady($license['description']) ?>
    </div>
</div>
<? endforeach ?>