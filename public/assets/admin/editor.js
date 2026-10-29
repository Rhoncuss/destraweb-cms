/* DESTRA CMS — rich text editor loader (TinyMCE 8 / CKEditor 5 / Summernote / plain) */
(function () {
    'use strict';

    var base = document.documentElement.getAttribute('data-base') || '';

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function loadStyle(href) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    }

    var area = document.querySelector('textarea[data-editor]');
    if (!area || area.disabled) { return; }

    var engines = {};
    document.querySelectorAll('textarea[data-editor]').forEach(function (el) {
        var engine = el.getAttribute('data-editor');
        engines[engine] = engines[engine] || [];
        engines[engine].push(el);
    });

    if (engines.tinymce) {
        loadScript(base + 'vendor/tinymce/tinymce.min.js').then(function () {
            tinymce.init({
                selector: 'textarea[data-editor="tinymce"]',
                license_key: 'gpl',
                height: 520,
                menubar: 'edit view insert format tools table help',
                branding: false,
                promotion: false,
                convert_urls: false,
                entity_encoding: 'raw',
                plugins: 'advlist anchor autolink charmap code emoticons fullscreen help image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | ' +
                    'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | blockquote codesample hr | code fullscreen',
                toolbar_mode: 'sliding',
                image_caption: true,
                image_advtab: true,
                paste_data_images: true,
                content_style: 'body{font-family:Inter,Segoe UI,sans-serif;font-size:15px;line-height:1.7;color:#1f2937;} img{max-width:100%;height:auto;}',
                setup: function (editor) {
                    editor.on('change input undo redo', function () {
                        editor.save();
                    });
                }
            });
        }).catch(function () {
            /* Fall back to a plain textarea. */
        });
    }

    if (engines.ckeditor) {
        loadScript(base + 'vendor/ckeditor/ckeditor.js').then(function () {
            engines.ckeditor.forEach(function (el) {
                window.ClassicEditor.create(el, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'imageUpload', 'insertTable', 'undo', 'redo'],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                            { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
                        ]
                    }
                }).then(function (editor) {
                    var form = el.closest('form');
                    if (form) {
                        form.addEventListener('submit', function () { el.value = editor.getData(); });
                    }
                }).catch(function (error) { console.warn('CKEditor failed', error); });
            });
        });
    }

    if (engines.summernote) {
        loadStyle(base + 'vendor/summernote/summernote-bs5.min.css');
        loadScript(base + 'vendor/jquery/jquery.min.js').then(function () {
            return loadScript(base + 'vendor/summernote/summernote-bs5.min.js');
        }).then(function () {
            var lang = (document.documentElement.lang || '').toLowerCase();
            var packs = { ar: 'ar-AR', id: 'id-ID' };
            var code = packs[lang.split('-')[0]];
            var ready = code
                ? loadScript(base + 'vendor/summernote/lang/summernote-' + code + '.min.js').catch(function () {})
                : Promise.resolve();

            return ready.then(function () {
                window.jQuery('textarea[data-editor="summernote"]').each(function () {
                    var el = this;
                    window.jQuery(el).summernote({
                        height: 460,
                        lang: code || undefined,
                        toolbar: [
                            ['style', ['style']],
                            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                            ['color', ['color']],
                            ['para', ['ul', 'ol', 'paragraph']],
                            ['insert', ['link', 'picture', 'video', 'table', 'hr']],
                            ['view', ['fullscreen', 'codeview', 'help']]
                        ],
                        callbacks: {
                            onChange: function (contents) { el.value = contents; }
                        }
                    });
                    var form = el.closest('form');
                    if (form) {
                        form.addEventListener('submit', function () {
                            el.value = window.jQuery(el).summernote('code');
                        });
                    }
                });
            });
        }).catch(function () {
            /* Fall back to a plain textarea. */
        });
    }
})();
