document.querySelector('.auth-form').addEventListener('submit', function () {
    document.getElementById('loginText').style.display = 'none';
    document.getElementById('loginSpin').style.display = 'inline-block';
    document.getElementById('loginBtn').disabled = true;
});
document.addEventListener('click', function (e) {
    document.querySelectorAll('details.lang-menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
});
