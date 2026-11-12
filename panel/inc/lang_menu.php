<details class="lang-menu">
  <summary class="icon-btn" title="<?= $panelLanguages[$panelLang] ?>"><?= icon('languages', 17) ?></summary>
  <div class="lang-list">
    <?php foreach ($panelLanguages as $code => $label): ?>
      <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['lang' => $code]))) ?>" class="<?= $code === $panelLang ? 'active' : '' ?>"><?= $label ?><small><?= strtoupper($code) ?></small></a>
    <?php endforeach; ?>
  </div>
</details>
