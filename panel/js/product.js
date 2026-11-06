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
        if (e.target === all) { rows().forEach(function (r) { if (r.closest('tr').style.display !== 'none') r.checked = all.checked; }); sync(); }
        else if (e.target.classList && e.target.classList.contains('selRow')) sync();
    });
    form.addEventListener('submit', function (e) {
        var op = document.getElementById('bulkOp').value, n = document.getElementById('selCount').textContent;
        if (op === 'delete' && !confirm(n + ' محصول برای همیشه حذف شود؟')) e.preventDefault();
        else if (op !== 'delete' && !confirm('این تغییر روی ' + n + ' محصول اعمال شود؟')) e.preventDefault();
    });
})();
