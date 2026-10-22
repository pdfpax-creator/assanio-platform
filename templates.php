<?php
// Templates gallery: the 4 real templates. "Use this template" starts the
// create wizard with that template pre-selected.
require_once __DIR__ . '/lib.php';
$user = require_login();
page_head('Templates');
rp_app_open($user, 'templates', [
    'title' => 'Templates',
    'sub'   => 'Four premium designs, built for Pakistani restaurants. Pick one to start your website.',
    'actions' => '<a class="rp-btn rp-btn-red" href="create-store.php">+ New Restaurant</a>',
]);
$tplClass = ['pizza-fastfood' => 'tpl-pizza', 'desi-bbq' => 'tpl-desi', 'cafe' => 'tpl-cafe', 'fine-dining' => 'tpl-fine'];
?>
<div class="rp-tplcards">
  <?php foreach (RP_TEMPLATES as $key => $t): ?>
    <div class="rp-tplcard <?= $tplClass[$key] ?? '' ?>">
      <div class="tpl-visual"><img src="<?= e($t['img'] ?? '') ?>" alt="<?= e($t['name']) ?> template preview" loading="lazy"></div>
      <div class="bd">
        <b><?= e($t['name']) ?></b>
        <span class="tg"><?= e($t['label']) ?></span>
        <p><?= e($t['blurb']) ?></p>
        <a class="rp-btn rp-btn-red rp-btn-sm" href="create-store.php?template=<?= e($key) ?>">Use this template →</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php rp_app_close(); page_foot(); ?>
