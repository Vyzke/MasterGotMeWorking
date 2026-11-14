// uni wise — small UI behaviours (menu, dropdown, theme). No data logic here.
(function () {
  var root = document.documentElement;

  // ---------- Light / dark theme ----------
  // Light is the default; dark only when the user picks it.
  function currentTheme() {
    return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }
  function applyTheme(theme) {
    if (theme === 'dark') root.setAttribute('data-theme', 'dark');
    else root.removeAttribute('data-theme');
    try { localStorage.setItem('uniwise-theme', theme); } catch (e) {}
    document.querySelectorAll('[data-set-theme]').forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.getAttribute('data-set-theme') === theme));
    });
  }
  document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () { applyTheme(currentTheme() === 'dark' ? 'light' : 'dark'); });
  });
  // Light / Dark choice cards on the settings page
  document.querySelectorAll('[data-set-theme]').forEach(function (btn) {
    btn.setAttribute('aria-pressed', String(btn.getAttribute('data-set-theme') === currentTheme()));
    btn.addEventListener('click', function () { applyTheme(btn.getAttribute('data-set-theme')); });
  });

  // ---------- Profile picture inputs ----------
  // Account page: upload as soon as a file is picked
  document.querySelectorAll('input[type=file][data-autosubmit]').forEach(function (input) {
    input.addEventListener('change', function () { if (input.files.length) input.form.submit(); });
  });
  // Settings page: show the chosen file name + a live preview before saving
  document.querySelectorAll('input[type=file][data-filename]').forEach(function (input) {
    input.addEventListener('change', function () {
      var wrap = input.closest('.photo-setting');
      var file = input.files[0];
      if (!wrap || !file) return;
      var label = wrap.querySelector('.file-name');
      if (label) label.textContent = file.name;
      var avatar = wrap.querySelector('.avatar');
      if (avatar && /^image\//.test(file.type)) {
        var img = avatar.querySelector('img') || document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(file);
        avatar.textContent = '';
        avatar.appendChild(img);
        avatar.classList.add('has-photo');
      }
    });
  });

  // ---------- Mobile menu ----------
  var toggle = document.querySelector('.nav-toggle');
  var menu = document.getElementById('nav-menu');
  function setMenu(open) {
    menu.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    document.body.classList.toggle('nav-open', open);
  }
  if (toggle && menu) {
    toggle.addEventListener('click', function () { setMenu(!menu.classList.contains('is-open')); });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 960 && menu.classList.contains('is-open')) setMenu(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menu.classList.contains('is-open')) setMenu(false);
    });
  }

  // ---------- "My tools" dropdown (click / tap) ----------
  document.querySelectorAll('.nav-dropdown-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var open = btn.parentElement.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', String(open));
    });
  });
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.nav-dropdown.is-open').forEach(function (dd) {
      if (!dd.contains(e.target)) {
        dd.classList.remove('is-open');
        dd.querySelector('.nav-dropdown-toggle').setAttribute('aria-expanded', 'false');
      }
    });
  });
})();
