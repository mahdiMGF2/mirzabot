var _lb = (function () {
    var el   = document.getElementById('load-bar');
    var t    = null;
    var live = false;

    function setW(pct, dur) {
        if (!el) return;
        el.style.setProperty('--lb-w',  pct + '%');
        el.style.setProperty('--lb-dur', dur + 'ms');
        el.className = 'lb-go';
    }

    function start() {
        if (!el) return;
        live = true;
        clearTimeout(t);
        el.className = '';
        el.style.setProperty('--lb-w',  '0%');
        el.style.setProperty('--lb-dur','0ms');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                setW(30, 300);
                t = setTimeout(function () { setW(60, 900);  }, 350);
                t = setTimeout(function () { setW(80, 1200); }, 1300);
                t = setTimeout(function () { setW(90, 800);  }, 2600);
            });
        });
    }

    function done() {
        if (!el || !live) return;
        live = false;
        clearTimeout(t);
        el.className = 'lb-end';
        t = setTimeout(function () {
            el.className = '';
            el.style.setProperty('--lb-w', '0%');
        }, 450);
    }

    return { start: start, done: done };
}());

window.addEventListener('load', function () { _lb.done(); });

document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a) return;
    if (a.target || a.dataset.confirm) return;
    var href = a.href || '';
    if (!href || href.startsWith('javascript') || href.startsWith('#') || href.startsWith('mailto')) return;
    try { if (new URL(href).origin !== location.origin) return; } catch (x) {}
    _lb.start();
});

document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.method && f.method.toLowerCase() !== 'dialog') _lb.start();
});

var _TOAST_ICONS = {
    ok:   '<polyline points="20 6 9 17 4 12"/>',
    no:   '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
    warn: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
};

window.toast = function (msg, type, dur) {
    type = type || 'info';
    dur  = dur  || 4500;
    var area = document.getElementById('toast-area');
    if (!area) return;
    var el = document.createElement('div');
    el.className = 'toast toast-' + type;
    el.innerHTML =
        '<div class="toast-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
        (_TOAST_ICONS[type] || _TOAST_ICONS.info) +
        '</svg></div>' +
        '<div style="flex:1;color:var(--text2)">' + String(msg).replace(/</g, '&lt;') + '</div>' +
        '<button class="toast-close">✕</button>';
    area.appendChild(el);
    var timer = setTimeout(function () {
        el.classList.add('closing');
        setTimeout(function () { el.remove(); }, 260);
    }, dur);
    el.querySelector('.toast-close').addEventListener('click', function () {
        clearTimeout(timer);
        el.remove();
    });
};

var _confirmCb = null;

window.showConfirm = function (msg, cb, title) {
    document.getElementById('confirm-title').textContent = title || t('jsConfirmTitle');
    document.getElementById('confirm-msg').textContent   = msg   || t('jsConfirmMsg');
    _confirmCb = cb;
    document.getElementById('confirm-veil').classList.add('open');
};

window.closeConfirm = function () {
    document.getElementById('confirm-veil').classList.remove('open');
    _confirmCb = null;
};

document.getElementById('confirm-ok').addEventListener('click', function () {
    document.getElementById('confirm-veil').classList.remove('open');
    if (_confirmCb) { var cb = _confirmCb; _confirmCb = null; cb(); }
});

document.getElementById('confirm-veil').addEventListener('click', function (e) {
    if (e.target === this) closeConfirm();
});

document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.preventDefault();
        var href = el.href;
        showConfirm(el.dataset.confirm || t('jsConfirmDefault'), function () {
            _lb.start();
            window.location.href = href;
        });
    });
});

var _THEME_BG = {
    navy: '#0F172A', purple: '#180D2E', emerald: '#0A1F1C',
    sunset: '#1A0D0D', slate: '#080808', light: '#F1F5F9',
    linen: '#FAF7F2', mint: '#F0FDF4', lavender: '#FAF5FF'
};
var _LIGHT_THEMES = ['light', 'linen', 'mint', 'lavender'];

window.applyTheme = function (t) {
    var root = document.documentElement;
    root.setAttribute('data-theme', t);
    root.style.backgroundColor = _THEME_BG[t] || '#0F172A';
    root.style.colorScheme     = _LIGHT_THEMES.indexOf(t) >= 0 ? 'light' : 'dark';
    localStorage.setItem('panel-theme', t);
    var mtc = document.getElementById('mtc');
    if (mtc && _THEME_BG[t]) mtc.content = _THEME_BG[t];
};

window.toggleSidebar = function () {
    var sb = document.getElementById('sidebar');
    sb.classList.toggle('collapsed');
    localStorage.setItem('panel-sb-collapsed', sb.classList.contains('collapsed') ? '1' : '0');
};

function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('backdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('backdrop').classList.remove('show');
    document.body.style.overflow = '';
}

(function () {
    if (document.documentElement.classList.contains('sb-pre-collapsed')) {
        document.getElementById('sidebar').classList.add('collapsed');
        document.documentElement.classList.remove('sb-pre-collapsed');
    }
}());

var _backdrop = document.getElementById('backdrop');
if (_backdrop) _backdrop.addEventListener('click', closeSidebar);

var _swipeSb = document.getElementById('sidebar');
if (_swipeSb) {
    var _swipeX = 0;
    _swipeSb.addEventListener('touchstart', function (e) { _swipeX = e.touches[0].clientX; }, { passive: true });
    _swipeSb.addEventListener('touchmove',  function (e) { if ((e.touches[0].clientX - _swipeX) * (document.documentElement.dir === 'ltr' ? -1 : 1) > 40) closeSidebar(); }, { passive: true });
}

window.openModal = function (id) {
    var m = document.getElementById(id);
    if (m) m.classList.add('open');
};
window.closeModal = function (id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('open');
};

document.querySelectorAll('.modal-veil').forEach(function (v) {
    v.addEventListener('click', function (e) { if (e.target === v) v.classList.remove('open'); });
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-veil.open').forEach(function (m) { m.classList.remove('open'); });
        closeSidebar();
        closeConfirm();
    }
});

document.querySelectorAll('.search-box').forEach(function (box) {
    var inp = box.querySelector('input');
    var btn = box.querySelector('.search-clear');
    if (!inp || !btn) return;
    function update() { btn.style.display = inp.value ? 'grid' : 'none'; }
    inp.addEventListener('input', update);
    update();
    btn.addEventListener('click', function () {
        inp.value = '';
        inp.focus();
        update();
        inp.dispatchEvent(new Event('input'));
    });
});

document.querySelectorAll('[data-filter]').forEach(function (inp) {
    var tbl = document.getElementById(inp.dataset.filter);
    if (!tbl) return;
    inp.addEventListener('input', function () {
        var q = inp.value.trim().toLowerCase();
        tbl.querySelectorAll('tbody tr').forEach(function (tr) {
            if (tr.querySelector('.empty')) return;
            tr.style.display = !q || tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
});

setTimeout(function () {
    document.querySelectorAll('.notice').forEach(function (n) {
        n.style.transition = 'opacity .4s,transform .4s';
        n.style.opacity    = '0';
        n.style.transform  = 'translateY(-4px)';
        setTimeout(function () { n.remove(); }, 420);
    });
}, 5500);
document.addEventListener('click', function (e) {
    document.querySelectorAll('details.lang-menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
});
