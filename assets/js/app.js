(function () {
    'use strict';
    var body = document.body;

    /* Sidebar mobile */
    document.querySelectorAll('[data-open-nav]').forEach(function (b) {
        b.addEventListener('click', function () { body.classList.add('nav-open'); });
    });
    document.querySelectorAll('[data-close-nav]').forEach(function (b) {
        b.addEventListener('click', function () { body.classList.remove('nav-open'); });
    });

    /* Tutup pesan flash */
    document.querySelectorAll('.flash-x').forEach(function (b) {
        b.addEventListener('click', function () { b.parentNode.remove(); });
    });
    setTimeout(function () {
        document.querySelectorAll('.flash-ok').forEach(function (f) {
            f.style.transition = 'opacity .5s'; f.style.opacity = '0';
            setTimeout(function () { f.remove(); }, 500);
        });
    }, 5000);

    /* Tampilkan / sembunyikan password */
    document.querySelectorAll('[data-toggle-pw]').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = b.parentNode.querySelector('input');
            i.type = i.type === 'password' ? 'text' : 'password';
        });
    });

    /* Konfirmasi sebelum submit */
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    /* Select auto-submit */
    document.querySelectorAll('[data-autosubmit]').forEach(function (s) {
        s.addEventListener('change', function () { s.form.submit(); });
    });

    /* Suka tanpa reload */
    document.querySelectorAll('.like-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = f.querySelector('.like-btn');
            fetch(f.action, {
                method: 'POST',
                body: new FormData(f),
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (d) {
                btn.classList.toggle('on', d.on);
                btn.setAttribute('aria-pressed', d.on ? 'true' : 'false');
                btn.querySelector('.like-count').textContent = d.count;
                btn.classList.remove('pop'); void btn.offsetWidth; btn.classList.add('pop');
            }).catch(function () { f.submit(); });
        });
    });

    /* Pratinjau + kompres gambar sebelum diunggah */
    function resizeFile(file, maxSide, cb) {
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return cb(file);
        var img = new Image();
        var url = URL.createObjectURL(file);
        img.onload = function () {
            URL.revokeObjectURL(url);
            var w = img.naturalWidth, h = img.naturalHeight;
            var scale = Math.min(1, maxSide / Math.max(w, h));
            if (scale === 1 && file.size < 1.5 * 1024 * 1024) return cb(file);
            var c = document.createElement('canvas');
            c.width = Math.round(w * scale); c.height = Math.round(h * scale);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            var keepPng = file.type === 'image/png' && file.size < 3 * 1024 * 1024;
            var type = keepPng ? 'image/png' : 'image/jpeg';
            c.toBlob(function (blob) {
                if (!blob) return cb(file);
                var ext = keepPng ? '.png' : '.jpg';
                cb(new File([blob], file.name.replace(/\.[^.]+$/, '') + ext, { type: type }));
            }, type, 0.86);
        };
        img.onerror = function () { cb(file); };
        img.src = url;
    }

    function showPreview(input, file) {
        var zone = input.id ? document.querySelector('label[for="' + input.id + '"][data-drop]') : null;
        var reader = new FileReader();
        reader.onload = function (e) {
            if (zone) {
                var p = zone.querySelector('[data-preview]'), h = zone.querySelector('[data-hint]');
                p.src = e.target.result; p.hidden = false; if (h) h.hidden = true;
            }
            if (input.hasAttribute('data-avatar-input')) {
                var box = document.querySelector('[data-avatar-preview] .avatar');
                if (box) box.innerHTML = '<img src="' + e.target.result + '" alt="Pratinjau">';
            }
        };
        reader.readAsDataURL(file);
    }

    document.querySelectorAll('input[type=file][data-resize]').forEach(function (input) {
        var max = parseInt(input.getAttribute('data-max') || '1800', 10);
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f) return;
            resizeFile(f, max, function (out) {
                if (out !== f && window.DataTransfer) {
                    var dt = new DataTransfer(); dt.items.add(out); input.files = dt.files;
                }
                showPreview(input, out);
            });
        });
    });

    /* Seret & lepas */
    document.querySelectorAll('[data-drop]').forEach(function (zone) {
        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('over'); });
        });
        zone.addEventListener('drop', function (e) {
            var input = document.getElementById(zone.getAttribute('for'));
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
})();
