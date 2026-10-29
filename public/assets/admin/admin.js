/* DESTRA CMS — admin behaviours (no inline scripts required by CSP) */
(function () {
    'use strict';

    var csrfName = 'destra_csrf';
    var csrfMeta = document.querySelector('meta[name="' + csrfName + '"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4200,
        timerProgressBar: true,
        didOpen: function (toast) {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    function toast(icon, title) {
        if (window.Swal) {
            Toast.fire({ icon: icon, title: title });
        } else {
            window.alert(title);
        }
    }

    function flash() {
        var holder = document.querySelector('.dst-flash');
        if (!holder) { return; }
        var success = holder.getAttribute('data-success');
        var error = holder.getAttribute('data-error');
        if (success) { toast('success', success); }
        if (error) { toast('error', error); }
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.cookie = 'dst_theme=' + theme + ';path=/;max-age=31536000;samesite=lax';
    }

    function bindThemeToggle() {
        document.querySelectorAll('[data-dst-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-bs-theme') || 'light';
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        });
    }

    function bindConfirm() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === '1') { return; }
                event.preventDefault();
                Swal.fire({
                    title: form.getAttribute('data-confirm-title') || 'Are you sure?',
                    text: form.getAttribute('data-confirm') || 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: form.getAttribute('data-confirm-button') || 'Yes, continue',
                    cancelButtonText: 'Cancel',
                    customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
        });
    }

    function postForm(url, data) {
        var body = new FormData();
        Object.keys(data || {}).forEach(function (key) { body.append(key, data[key]); });
        if (csrfToken) { body.append(csrfName, csrfToken); }
        return fetch(url, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().catch(function () { return { success: response.ok }; });
        });
    }

    function bindAjaxToggles() {
        document.querySelectorAll('[data-dst-toggle]').forEach(function (el) {
            el.addEventListener('change', function () {
                var url = el.getAttribute('data-dst-toggle');
                postForm(url, { value: el.checked ? 1 : 0 }).then(function (json) {
                    if (json.success === false) { el.checked = !el.checked; }
                    toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Updated');
                }).catch(function () {
                    el.checked = !el.checked;
                    toast('error', 'Network error — please retry.');
                });
            });
        });
    }

    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\u0600-\u06FF]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 190);
    }

    function bindSlug() {
        document.querySelectorAll('[data-slug-source]').forEach(function (source) {
            var target = document.querySelector(source.getAttribute('data-slug-source'));
            if (!target) { return; }
            var locked = target.value.trim() !== '';
            target.addEventListener('input', function () { locked = target.value.trim() !== ''; });
            source.addEventListener('input', function () {
                if (!locked) { target.value = slugify(source.value); }
            });
        });
    }

    function bindCharacterCounts() {
        document.querySelectorAll('[data-counter]').forEach(function (field) {
            var out = document.querySelector(field.getAttribute('data-counter'));
            if (!out) { return; }
            var update = function () {
                var max = field.getAttribute('maxlength') || '';
                out.textContent = field.value.length + (max ? ' / ' + max : '');
            };
            field.addEventListener('input', update);
            update();
        });
    }

    function bindShortcut() {
        document.addEventListener('keydown', function (event) {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                var input = document.querySelector('.dst-search input');
                if (input) { event.preventDefault(); input.focus(); }
            }
        });
    }

    function bindCopy() {
        document.querySelectorAll('[data-dst-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var value = button.getAttribute('data-dst-copy');
                navigator.clipboard.writeText(value).then(function () { toast('success', 'Copied to clipboard'); });
            });
        });
    }

    function bindMediaClear() {
        document.querySelectorAll('[data-media-clear]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.querySelector(button.getAttribute('data-media-clear'));
                var preview = document.querySelector(button.getAttribute('data-media-preview'));
                if (input) { input.value = ''; }
                if (preview) {
                    preview.innerHTML = '<div class="py-4"><i class="fa-regular fa-image d-block mb-2" style="font-size:26px;"></i>No image selected</div>';
                    preview.style.border = '';
                }
            });
        });
    }

    function fileExt(name) {
        var parts = String(name || '').split('.');
        return parts.length > 1 ? parts.pop().toUpperCase().slice(0, 4) : 'IMG';
    }

    function bindMediaPicker() {
        var modalEl = document.getElementById('mediaPickerModal');
        if (!modalEl || !window.bootstrap) { return; }

        var grid = modalEl.querySelector('[data-picker-grid]');
        var empty = modalEl.querySelector('[data-picker-empty]');
        var loading = modalEl.querySelector('[data-picker-loading]');
        var search = modalEl.querySelector('[data-picker-search]');
        var uploadInput = modalEl.querySelector('[data-picker-upload-input]');
        var pickerUrl = modalEl.getAttribute('data-picker-url');
        var uploadUrl = modalEl.getAttribute('data-picker-upload');
        var modal = new bootstrap.Modal(modalEl);
        var target = null;
        var preview = null;
        var timer = null;

        function choose(item) {
            if (target) {
                target.value = item.id;
                target.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (preview) {
                preview.innerHTML = '';
                var img = document.createElement('img');
                img.src = item.url;
                img.alt = item.alt || '';
                img.style.cssText = 'width:100%;height:150px;object-fit:cover;display:block;';
                preview.appendChild(img);
                preview.style.border = '0';
            }
            modal.hide();
        }

        function render(items) {
            grid.innerHTML = '';
            items.forEach(function (item) {
                var tile = document.createElement('button');
                tile.type = 'button';
                tile.className = 'media-tile';
                tile.title = item.name + (item.width ? ' · ' + item.width + '×' + item.height : '');

                var img = document.createElement('img');
                img.src = item.thumb || item.url;
                img.alt = item.alt || item.name;
                img.loading = 'lazy';
                tile.appendChild(img);

                var ext = document.createElement('span');
                ext.className = 'tile-ext';
                ext.textContent = fileExt(item.name);
                tile.appendChild(ext);

                var meta = document.createElement('span');
                meta.className = 'tile-meta';
                meta.textContent = item.name;
                tile.appendChild(meta);

                tile.addEventListener('click', function () { choose(item); });
                grid.appendChild(tile);
            });
            empty.hidden = items.length > 0;
        }

        function load(term) {
            loading.hidden = false;
            empty.hidden = true;
            var url = pickerUrl + (term ? '?q=' + encodeURIComponent(term) : '');
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (response) { return response.json(); }).then(function (json) {
                render(json.items || []);
            }).catch(function () {
                render([]);
                toast('error', 'Could not load the media library.');
            }).then(function () {
                loading.hidden = true;
            });
        }

        document.querySelectorAll('[data-media-picker]').forEach(function (button) {
            button.addEventListener('click', function () {
                target = document.querySelector(button.getAttribute('data-media-picker'));
                preview = document.querySelector(button.getAttribute('data-media-preview'));
                if (search) { search.value = ''; }
                modal.show();
                load('');
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { load(search.value.trim()); }, 280);
            });
        }

        if (uploadInput) {
            uploadInput.addEventListener('change', function () {
                if (!uploadInput.files || uploadInput.files.length === 0) { return; }
                var body = new FormData();
                Array.prototype.forEach.call(uploadInput.files, function (file) { body.append('files[]', file); });
                if (csrfToken) { body.append(csrfName, csrfToken); }
                loading.hidden = false;
                fetch(uploadUrl, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (response) { return response.json(); }).then(function (json) {
                    if (json.success === false) {
                        toast('error', json.error || 'Upload failed.');
                    } else {
                        toast('success', json.message || 'Uploaded.');
                    }
                    load(search ? search.value.trim() : '');
                }).catch(function () {
                    toast('error', 'Upload failed — check the connection and retry.');
                    loading.hidden = true;
                }).then(function () { uploadInput.value = ''; });
            });
        }
    }

    function bindCheckAll() {
        document.querySelectorAll('[data-check-all]').forEach(function (master) {
            var scope = master.closest('form') || document;
            master.addEventListener('change', function () {
                scope.querySelectorAll('[data-check-item]').forEach(function (box) {
                    box.checked = master.checked;
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        flash();
        bindThemeToggle();
        bindConfirm();
        bindAjaxToggles();
        bindSlug();
        bindCharacterCounts();
        bindShortcut();
        bindCopy();
        bindMediaClear();
        bindMediaPicker();
        bindCheckAll();
    });

    window.Destra = {
        toast: toast,
        postForm: postForm,
        slugify: slugify,
        csrfToken: csrfToken,
        csrfName: csrfName
    };
})();
