<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
  csrf_check_post();
  $name = trim($_POST['name_product'] ?? '');
  if ($name === '') {
    flash('error', $textbotlang['panel']['productNameRequired']);
    header('Location: product.php');
    exit;
  }
  if (db_count($pdo, "SELECT COUNT(*) FROM product WHERE name_product = ?", [$name])) {
    flash('error', $textbotlang['panel']['productNameExists']);
    header('Location: product.php');
    exit;
  }
  $code = bin2hex(random_bytes(2));
  try {
    db_query(
      $pdo,
      "INSERT INTO product (name_product,code_product,price_product,Volume_constraint,Service_time,Location,agent,data_limit_reset,note,category,hide_panel,one_buy_status) VALUES (?,?,?,?,?,?,?,'no_reset',?,?,'{}','0')",
      [$name, $code, (int) ($_POST['price_product'] ?? 0), (int) ($_POST['volume_product'] ?? 0), (int) ($_POST['time_product'] ?? 0), $_POST['namepanel'] ?? '', $_POST['agent_product'] ?? '', $_POST['note_product'] ?? '', $_POST['category_product'] ?? '']
    );
    db_query($pdo, "UPDATE product SET display_order = id, limit_ip = ? WHERE id = ?", [max(0, (int) ($_POST['limit_ip'] ?? 0)), $pdo->lastInsertId()]);
    flash('success', $textbotlang['panel']['productAddedPrefix'] . $name . $textbotlang['panel']['productAddedSuffix']);
  } catch (Exception $e) {
    flash('error', $textbotlang['panel']['productDbError'] . $e->getMessage());
  }
  header('Location: product.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
  csrf_check_post();
  $pid = (int) ($_POST['edit_id'] ?? 0);
  $name = trim($_POST['name_product'] ?? '');
  if ($pid && $name !== '') {
    try {
      $pdo->beginTransaction();
      db_query(
        $pdo,
        "UPDATE product SET name_product=?,price_product=?,Volume_constraint=?,Service_time=?,Location=?,agent=?,note=?,category=?,limit_ip=? WHERE id=?",
        [$name, (int) ($_POST['price_product'] ?? 0), (int) ($_POST['volume_product'] ?? 0), (int) ($_POST['time_product'] ?? 0), $_POST['namepanel'] ?? '', $_POST['agent_product'] ?? '', $_POST['note_product'] ?? '', $_POST['category_product'] ?? '', max(0, (int) ($_POST['limit_ip'] ?? 0)), $pid]
      );
      if (isset($_POST['position'])) {
        move_display_order($pdo, 'product', $pid, (int) $_POST['position']);
      }
      $pdo->commit();
      flash('success', $textbotlang['panel']['productEdited']);
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      flash('error', $textbotlang['panel']['productErrorPrefix'] . $e->getMessage());
    }
  }
  header('Location: product.php');
  exit;
}

if (isset($_GET['delete'])) {
  csrf_check_get();
  db_query($pdo, "DELETE FROM product WHERE id = ?", [(int) $_GET['delete']]);
  flash('success', $textbotlang['panel']['productDeleted']);
  header('Location: product.php');
  exit;
}

$panels = [];
$categories = [];
try {
  $panels = db_fetchAll($pdo, "SELECT * FROM marzban_panel ORDER BY display_order, id");
  $categories = db_fetchAll($pdo, "SELECT * FROM category ORDER BY display_order, id");
} catch (Exception $e) {
}
$products = db_fetchAll($pdo, "SELECT * FROM product ORDER BY display_order, id");

$pageTitle = $textbotlang['panel']['productsTitle'];
$pageLede = $textbotlang['panel']['productsSubtitle'];
$activeNav = 'product';
include __DIR__ . '/inc/layout_head.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px" class="fade-up">
  <div style="font-size:.85rem;color:var(--mute)"><?= count($products) ?> <?= $textbotlang['panel']['productsHeading'] ?></div>
  <button class="btn btn-primary" onclick="openModal('addModal')"><?= icon('plus', 14) ?> <?= $textbotlang['panel']['productAddProductBtn'] ?></button>
</div>

<div class="card fade-up d1">
  <?php if (empty($products)): ?>
    <div class="empty" style="padding:60px 20px">
      <svg class="ill" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="40" y="30" width="120" height="100" rx="12" fill="var(--surface-3)" />
        <rect x="56" y="50" width="88" height="12" rx="6" fill="var(--border-strong)" />
        <rect x="56" y="72" width="60" height="8" rx="4" fill="var(--border)" />
        <rect x="56" y="90" width="72" height="8" rx="4" fill="var(--border)" />
        <rect x="56" y="108" width="44" height="8" rx="4" fill="var(--border)" />
        <circle cx="155" cy="125" r="22" fill="var(--accent-s)" stroke="var(--accent)" stroke-width="2" />
        <path d="M147 125h16M155 117v16" stroke="var(--accent)" stroke-width="2.5" stroke-linecap="round" />
      </svg>
      <p><?= $textbotlang['panel']['productColName'] ?></p>
      <button class="btn btn-primary" style="margin-top:14px" onclick="openModal('addModal')"><?= icon('plus', 14) ?>
        <?= $textbotlang['panel']['productColVolume'] ?></button>
    </div>
  <?php else: ?>
    <div class="toolbar">
      <div class="toolbar-title"><?= $textbotlang['panel']['productColTime'] ?> <small>(<?= count($products) ?>)</small></div>
      <div class="search-box" style="min-width:220px">
        <?= icon('search', 14) ?>
        <input type="text" placeholder="<?= htmlspecialchars($textbotlang['panel']['productSearchPlaceholder']) ?>" data-filter="prodTbl">
        <button type="button" class="search-clear">✕</button>
      </div>
    </div>
    <div class="tbl-wrap">
      <table id="prodTbl" class="tbl-xl">
        <thead>
          <tr>
            <th>#</th>
            <th><?= $textbotlang['panel']['productColPrice'] ?></th>
            <th><?= $textbotlang['panel']['productColActions'] ?></th>
            <th><?= $textbotlang['panel']['productNoProductFound'] ?></th>
            <th><?= $textbotlang['panel']['productNoProductYet'] ?></th>
            <th><?= $textbotlang['panel']['productAddProductTitle'] ?></th>
            <th><?= $textbotlang['panel']['productEditProductTitle'] ?></th>
            <th><?= $textbotlang['panel']['productFieldProductName'] ?></th>
            <th><?= $textbotlang['panel']['productFieldVolumeGb'] ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1;
          foreach ($products as $p): ?>
            <tr>
              <td class="cf"><?= $i++ ?></td>
              <td class="cs"><?= htmlspecialchars($p['name_product'] ?? '') ?></td>
              <td class="cn cs"><?= number_format((int) ($p['price_product'] ?? 0)) ?> <span class="cf"><?= $textbotlang['panel']['productFieldServiceDays'] ?></span></td>
              <td class="cn"><?= htmlspecialchars($p['Volume_constraint'] ?? '—') ?> <span class="cf">GB</span></td>
              <td class="cn"><?= htmlspecialchars($p['Service_time'] ?? '—') ?> <span class="cf"><?= $textbotlang['panel']['productFieldPriceToman'] ?></span></td>
              <td class="cf"><?= htmlspecialchars(trunc($p['Location'] ?? '—', 16)) ?></td>
              <td><?php if (!empty($p['category'])): ?><span
                    class="tag tag-info"><?= htmlspecialchars($p['category']) ?></span><?php else: ?><span
                    class="cf">—</span><?php endif; ?></td>
              <td class="cm" style="font-size:.72rem"><?= htmlspecialchars($p['code_product'] ?? '') ?></td>
              <td>
                <div style="display:flex;gap:5px">
                  <button class="btn btn-ghost btn-sm btn-icon" title="<?= htmlspecialchars($textbotlang['panel']['productEditBtn']) ?>"
                    onclick="openEditModal(<?= htmlspecialchars(json_encode($p + ['position' => $i - 1]), ENT_QUOTES) ?>)">
                    <?= icon('edit', 13) ?>
                  </button>
                  <a href="product.php?delete=<?= (int) $p['id'] ?>&_csrf=<?= csrf_token() ?>"
                    class="btn btn-no btn-sm btn-icon" title="<?= htmlspecialchars($textbotlang['panel']['productDeleteBtn']) ?>"
                    data-confirm="<?= htmlspecialchars(sprintf($textbotlang['panel']['productConfirmDeleteProduct'], $p['name_product'])) ?>">
                    <?= icon('trash', 13) ?>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="modal-veil" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3><?= $textbotlang['panel']['productFieldProductType'] ?></h3>
      <button class="modal-x" onclick="closeModal('addModal')"><?= icon('close', 14) ?></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-grid">
          <div class="field full">
            <label><?= $textbotlang['panel']['productFieldDescription'] ?></label>
            <input type="text" name="name_product" class="input" placeholder="<?= htmlspecialchars($textbotlang['panel']['productNameExample']) ?>" required>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productSaveBtn'] ?></label>
            <input type="number" name="price_product" class="input" placeholder="<?= htmlspecialchars($textbotlang['panel']['productZeroValue']) ?>" min="0">
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productVolumeGbSuffix'] ?></label>
            <input type="number" name="volume_product" class="input" placeholder="<?= htmlspecialchars($textbotlang['panel']['productFiftyValue']) ?>" min="0">
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productCancelBtn'] ?></label>
            <input type="number" name="time_product" class="input" placeholder="<?= htmlspecialchars($textbotlang['panel']['productThirtyValue']) ?>" min="0">
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productFieldLocation'] ?></label>
            <select name="category_product" class="select">
              <option value=""><?= $textbotlang['panel']['productDetailLocation'] ?></option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['remark'] ?? '') ?>">
                  <?= htmlspecialchars($cat['remark'] ?? '') ?>
                 </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productFieldCategory'] ?></label>
            <select name="namepanel" class="select">
              <option value=""><?= $textbotlang['panel']['productFieldNote'] ?></option>
              <?php foreach ($panels as $pl): ?>
                <option value="<?= htmlspecialchars($pl['name_panel'] ?? $pl['id']) ?>">
                  <?= htmlspecialchars($pl['name_panel'] ?? $pl['id']) ?>
                </option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productColId'] ?></label>
            <select name="agent_product" class="select">
              <option value="f"><?= $textbotlang['panel']['productColType'] ?></option>
              <option value="n"><?= $textbotlang['panel']['productColLocation'] ?></option>
              <option value="n2"><?= $textbotlang['panel']['productColCategory'] ?></option>
            </select>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productLimitIp'] ?></label>
            <input type="number" min="0" name="limit_ip" class="input" value="0">
          </div>
          <div class="field full">
            <label><?= $textbotlang['panel']['productColDescription'] ?></label>
            <input type="text" name="note_product" class="input" placeholder="<?= htmlspecialchars($textbotlang['panel']['productDescriptionOptional']) ?>">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="submit" class="btn btn-primary"><?= icon('plus', 13) ?> <?= $textbotlang['panel']['productColNote'] ?></button>
        <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')"><?= $textbotlang['panel']['productColCreatedAt'] ?></button>
      </div>
    </form>
  </div>
</div>

<div class="modal-veil" id="editModal">
  <div class="modal">
    <div class="modal-head">
      <h3><?= $textbotlang['panel']['productDetailTitle'] ?></h3>
      <button class="modal-x" onclick="closeModal('editModal')"><?= icon('close', 14) ?></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="form-grid">
          <div class="field full">
            <label><?= $textbotlang['panel']['productDetailName'] ?></label>
            <input type="text" name="name_product" id="edit_name" class="input" required>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productDetailVolume'] ?></label>
            <input type="number" name="price_product" id="edit_price" class="input" min="0">
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productVolumeGbSuffix'] ?></label>
            <input type="number" name="volume_product" id="edit_volume" class="input" min="0">
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productDetailTime'] ?></label>
            <input type="number" name="time_product" id="edit_time" class="input" min="0">
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productFieldPosition'] ?></label>
            <input type="number" name="position" id="edit_position" class="input" min="1" max="<?= count($products) ?>" required>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productDetailPrice'] ?></label>
            <select name="category_product" id="edit_cat" class="select">
              <option value=""><?= $textbotlang['panel']['productDetailLocation'] ?></option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['remark'] ?? '') ?>">
                  <?= htmlspecialchars($cat['remark'] ?? '') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productDetailType'] ?></label>
            <select name="namepanel" id="edit_panel" class="select">
              <option value=""><?= $textbotlang['panel']['productDetailLocation'] ?></option>
              <?php foreach ($panels as $pl): ?>
                <option value="<?= htmlspecialchars($pl['name_panel'] ?? $pl['id']) ?>">
                  <?= htmlspecialchars($pl['name_panel'] ?? $pl['id']) ?>
                </option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productDetailCategory'] ?></label>
            <select name="agent_product" id="edit_agent" class="select">
              <option value="f"><?= $textbotlang['panel']['productDetailDescription'] ?></option>
              <option value="n"><?= $textbotlang['panel']['productDetailNote'] ?></option>
              <option value="n2"><?= $textbotlang['panel']['productCloseBtn'] ?></option>
            </select>
          </div>
          <div class="field">
            <label><?= $textbotlang['panel']['productLimitIp'] ?></label>
            <input type="number" min="0" name="limit_ip" id="edit_limit_ip" class="input" value="0">
          </div>
          <div class="field full">
            <label><?= $textbotlang['panel']['productUnlimitedLabel'] ?></label>
            <input type="text" name="note_product" id="edit_note" class="input">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="submit" class="btn btn-primary"><?= icon('check', 13) ?> <?= $textbotlang['panel']['productDayUnit'] ?></button>
        <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')"><?= $textbotlang['panel']['productTomanUnit'] ?></button>
      </div>
    </form>
  </div>
</div>

<script src="js/product.js"></script>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>