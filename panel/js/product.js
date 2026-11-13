window.openEditModal = function (p) {
    document.getElementById('edit_id').value = p.id || '';
    document.getElementById('edit_name').value = p.name_product || '';
    document.getElementById('edit_price').value = p.price_product || '';
    document.getElementById('edit_volume').value = p.Volume_constraint || '';
    document.getElementById('edit_time').value = p.Service_time || '';
    document.getElementById('edit_cat').value = p.category || '';
    document.getElementById('edit_agent').value = p.agent || '';
    document.getElementById('edit_note').value = p.note || '';
    document.getElementById('edit_position').value = p.position || '';

    var sel = document.getElementById('edit_panel');
    if (sel) {
        for (var i = 0; i < sel.options.length; i++) {
            sel.options[i].selected = sel.options[i].value === (p.Location || '');
        }
    }

    openModal('editModal');
};

(function () {
    var all = document.getElementById('selAll'), form = document.getElementById('bulkForm');
    if (!form) return;
    function rows() { return [].slice.call(document.querySelectorAll('.selRow')); }
    function sync() {
        var ch = rows().filter(function (r) { return r.checked; });
        document.getElementById('selCount').textContent = ch.length;
        form.style.display = ch.length ? '' : 'none';
        [].slice.call(form.querySelectorAll('input[name="ids[]"]')).forEach(function (n) { n.remove(); });
        ch.forEach(function (r) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'ids[]'; h.value = r.value; form.appendChild(h); });
    }
    document.addEventListener('change', function (e) {
        if (e.target === all) { rows().forEach(function (r) { if (r.closest('tr').style.display !== 'none' && !r.closest('tr').classList.contains('pf-hide')) r.checked = all.checked; }); sync(); }
        else if (e.target.classList && e.target.classList.contains('selRow')) sync();
    });
    var opSel = document.getElementById('bulkOp'), hint = document.getElementById('bulkHint');
    function showField() {
        var op = opSel.value;
        [].slice.call(form.querySelectorAll('.bulk-val')).forEach(function (w) {
            var on = w.getAttribute('data-op') === op;
            w.style.display = on ? '' : 'none';
            [].slice.call(w.querySelectorAll('input,select')).forEach(function (c) { c.disabled = !on; if (on && c.tagName === 'INPUT' && c.hasAttribute('min') && c.value === '') c.value = ''; });
        });
        hint.textContent = hint.getAttribute('data-' + op) || '';
        hint.style.color = op === 'delete' ? 'var(--danger, #d33)' : '';
    }
    opSel.addEventListener('change', showField);
    showField();
    document.getElementById('bulkClear').addEventListener('click', function () {
        rows().forEach(function (r) { r.checked = false; });
        if (all) all.checked = false;
        sync();
    });
    form.addEventListener('submit', function (e) {
        var op = opSel.value, n = document.getElementById('selCount').textContent;
        var msg = form.getAttribute(op === 'delete' ? 'data-confirm-delete' : 'data-confirm-apply').replace('{n}', n);
        if (!confirm(msg)) e.preventDefault();
    });
})();

(function () {
    var bar = document.getElementById('prodFilters');
    if (!bar) return;
    var selects = [].slice.call(bar.querySelectorAll('select[data-pf]'));
    var count = document.getElementById('pfCount');
    function apply() {
        var want = {};
        selects.forEach(function (s) { want[s.getAttribute('data-pf')] = s.value; });
        var shown = 0, total = 0;
        [].slice.call(document.querySelectorAll('#prodTbl tbody tr')).forEach(function (tr) {
            if (tr.querySelector('.empty')) return;
            total++;
            var ok = true;
            selects.forEach(function (s) {
                var k = s.getAttribute('data-pf');
                if (s.value !== '' && tr.getAttribute('data-' + k) !== s.value) ok = false;
            });
            tr.classList.toggle('pf-hide', !ok);
            if (!ok) {
                var cb = tr.querySelector('.selRow');
                if (cb && cb.checked) { cb.checked = false; cb.dispatchEvent(new Event('change', { bubbles: true })); }
            } else {
                shown++;
            }
        });
        if (count) count.textContent = count.getAttribute('data-label') + ' ' + shown + ' / ' + total;
    }
    selects.forEach(function (s) { s.addEventListener('change', apply); });
    document.getElementById('pfReset').addEventListener('click', function () {
        selects.forEach(function (s) { s.value = ''; });
        apply();
    });
    apply();
})();
