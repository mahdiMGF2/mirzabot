<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

$langs = $textbotlang['bottext']['langs'];
$lang = isset($langs[$_GET['lang'] ?? '']) ? $_GET['lang'] : 'fa';

$flatTexts = function (array $texts): array {
    $flat = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($texts));
    foreach ($iterator as $value) {
        $path = [];
        for ($depth = 0; $depth <= $iterator->getDepth(); $depth++)
            $path[] = $iterator->getSubIterator($depth)->key();
        $flat[implode('.', $path)] = (string) $value;
    }
    return $flat;
};

$baseTexts = array_diff_key(require dirname(__DIR__) . '/lang/' . $lang . '.php', array_flip(['bottext', 'Admin', 'panel']));
$defaults = $flatTexts($baseTexts);
$overrideFile = dirname(__DIR__) . '/lang/override/' . $lang . '.php';
$overrideTexts = is_file($overrideFile) ? include $overrideFile : [];
$overrides = array_intersect_key($flatTexts(is_array($overrideTexts) ? $overrideTexts : []), $defaults);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_post();
    foreach ((array) ($_POST['texts'] ?? []) as $key => $value) {
        if (!isset($defaults[$key]) || !is_string($value))
            continue;
        $value = str_replace("\r\n", "\n", $value);
        if (trim($value) === '' || $value === $defaults[$key])
            unset($overrides[$key]);
        else
            $overrides[$key] = $value;
    }
    $nested = [];
    foreach ($overrides as $key => $value) {
        $node = &$nested;
        foreach (explode('.', $key) as $segment)
            $node = &$node[$segment];
        $node = $value;
        unset($node);
    }
    if ($nested) {
        $overrideDir = dirname($overrideFile);
        $saved = (is_dir($overrideDir) || @mkdir($overrideDir, 0755, true))
            && @file_put_contents($overrideFile, "<?php\n\nreturn " . var_export($nested, true) . ";\n", LOCK_EX) !== false;
    } else {
        $saved = !is_file($overrideFile) || @unlink($overrideFile);
    }
    if ($saved && function_exists('opcache_invalidate'))
        opcache_invalidate($overrideFile, true);
    $phpUser = function_exists('posix_geteuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? 'www-data') : 'www-data';
    flash($saved ? 'success' : 'error', $saved ? $textbotlang['panel']['bottextSaved'] : strtr($textbotlang['panel']['bottextSaveError'], [
        '{path}' => dirname($overrideFile),
        '{command}' => 'chown -R ' . $phpUser . ':' . $phpUser . ' ' . dirname($overrideFile, 2),
    ]));
    header('Location: bottext.php?' . ($_SERVER['QUERY_STRING'] ?? ''));
    exit;
}

$group = $_GET['group'] ?? array_key_first($baseTexts);
$query = trim($_GET['q'] ?? '');
$onlyChanged = !empty($_GET['changed']);

$groupCounts = array_fill_keys(array_keys($baseTexts), 0);
$visible = [];
foreach ($defaults as $key => $default) {
    $current = $overrides[$key] ?? $default;
    if ($onlyChanged && !isset($overrides[$key]))
        continue;
    if ($query !== '' && mb_stripos($key . "\n" . $default . "\n" . $current, $query) === false)
        continue;
    $groupKey = strstr($key, '.', true);
    $groupCounts[$groupKey]++;
    if ($group === '' || $groupKey === $group)
        $visible[$key] = $current;
}
$tabUrl = fn($groupKey) => 'bottext.php?' . http_build_query(['lang' => $lang, 'group' => $groupKey, 'q' => $query ?: null, 'changed' => $onlyChanged ? 1 : null]);

$pageTitle = $textbotlang['panel']['bottextPageTitle'];
$pageLede = $textbotlang['panel']['bottextPageLede'];
$activeNav = 'bottext';
include __DIR__ . '/inc/layout_head.php';
?>

<div style="display:flex;gap:4px;margin-bottom:14px;background:var(--sf);border:1px solid var(--bd);border-radius:10px;padding:5px;overflow-x:auto" class="fade-up">
    <?php foreach (['' => array_sum($groupCounts)] + $groupCounts as $groupKey => $groupCount): ?>
        <a href="<?= htmlspecialchars($tabUrl($groupKey)) ?>"
            style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:7px;font-size:.82rem;font-weight:600;white-space:nowrap;flex-shrink:0;transition:all .15s;text-decoration:none;
                  <?= $group === (string) $groupKey ? 'background:var(--acs);color:var(--ach);font-weight:700' : 'color:var(--mute)' ?>">
            <?= htmlspecialchars($groupKey === '' ? $textbotlang['panel']['bottextAllGroups'] : ($textbotlang['panel']['bottextGroups'][$groupKey] ?? $groupKey)) ?>
            <small style="opacity:.75">(<?= $groupCount ?>)</small>
        </a>
    <?php endforeach; ?>
</div>

<div class="card fade-up" style="overflow:visible">
    <div class="toolbar">
        <div class="toolbar-title"><?= $textbotlang['panel']['bottextPageTitle'] ?>
            <small>(<?= count($visible) ?> <?= $textbotlang['panel']['bottextCountLabel'] ?> · <?= count($overrides) ?> <?= $textbotlang['panel']['bottextChangedLabel'] ?>)</small>
        </div>
        <form method="GET" class="toolbar-end">
            <select name="lang" class="select" style="width:auto" title="<?= htmlspecialchars($textbotlang['panel']['bottextLangLabel']) ?>" onchange="this.form.submit()">
                <?php foreach ($langs as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $lang === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="group" value="<?= htmlspecialchars($group) ?>">
            <div class="search-box" style="min-width:220px">
                <?= icon('search', 14) ?>
                <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="<?= htmlspecialchars($textbotlang['panel']['bottextSearchPlaceholder']) ?>">
            </div>
            <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;color:var(--mute);white-space:nowrap">
                <input type="checkbox" name="changed" value="1" <?= $onlyChanged ? 'checked' : '' ?> onchange="this.form.submit()">
                <?= $textbotlang['panel']['bottextOnlyChanged'] ?>
            </label>
            <button type="submit" class="btn btn-ghost btn-sm"><?= icon('search', 13) ?> <?= $textbotlang['panel']['bottextFilterBtn'] ?></button>
        </form>
    </div>

    <?php if (!$visible): ?>
        <div class="empty" style="padding:60px 20px">
            <p><?= $textbotlang['panel']['bottextEmpty'] ?></p>
        </div>
    <?php else: ?>
        <form method="POST" onsubmit="this.querySelectorAll('textarea').forEach(function (t) { t.disabled = t.value === t.defaultValue; })">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="card-body" style="display:flex;flex-direction:column;gap:18px">
                <?php foreach ($visible as $key => $current): ?>
                    <div class="field">
                        <label style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                            <span class="cm" dir="ltr" style="font-size:.75rem;word-break:break-all"><?= htmlspecialchars($key) ?></span>
                            <?php if (isset($overrides[$key])): ?>
                                <span class="tag tag-warn"><?= $textbotlang['panel']['bottextChangedLabel'] ?></span>
                            <?php endif; ?>
                        </label>
                        <textarea name="texts[<?= htmlspecialchars($key) ?>]" class="textarea" dir="auto" rows="<?= min(8, substr_count($current, "\n") + 1) ?>">
<?= htmlspecialchars($current) ?></textarea>
                        <?php if (isset($overrides[$key])): ?>
                            <div class="field-hint" style="display:flex;align-items:flex-start;gap:8px">
                                <span style="flex:1;white-space:pre-wrap"><?= $textbotlang['panel']['bottextDefaultLabel'] ?> <?= htmlspecialchars($defaults[$key]) ?></span>
                                <button type="button" class="btn btn-ghost btn-sm" data-default="<?= htmlspecialchars($defaults[$key]) ?>"
                                    onclick="this.closest('.field').querySelector('textarea').value = this.dataset.default"><?= $textbotlang['panel']['bottextResetBtn'] ?></button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-foot" style="position:sticky;bottom:0;z-index:5;border-radius:0 0 10px 10px;box-shadow:0 -8px 20px rgba(0,0,0,.25)">
                <button type="submit" class="btn btn-primary"><?= icon('check', 13) ?> <?= $textbotlang['panel']['bottextSaveBtn'] ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>
