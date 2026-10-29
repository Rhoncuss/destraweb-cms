(function () {
    'use strict';

    var doc = document;
    var body = doc.body;
    var root = doc.documentElement;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    root.classList.remove('no-js');
    root.classList.add('js');

    function $(selector, scope) {
        return (scope || doc).querySelector(selector);
    }

    function $$(selector, scope) {
        return Array.prototype.slice.call((scope || doc).querySelectorAll(selector));
    }

    function baseUrl() {
        return body.getAttribute('data-base') || '/';
    }

    function csrf() {
        var header = 'X-CSRF-TOKEN';
        var token = '';
        var headerMeta = $('meta[name="csrf-header"]');

        if (headerMeta && headerMeta.content) {
            header = headerMeta.content;
        }

        $$('meta').some(function (meta) {
            if (meta.name && meta.name !== 'csrf-header' && /csrf/i.test(meta.name) && meta.content) {
                token = meta.content;

                return true;
            }

            return false;
        });

        return { header: header, token: token };
    }

    function setMessage(el, text, state) {
        if (!el) {
            return;
        }

        el.textContent = text || '';
        el.classList.remove('is-error', 'is-success');

        if (state) {
            el.classList.add(state === 'error' ? 'is-error' : 'is-success');
        }
    }

    function syncScrollLock() {
        var locked = body.classList.contains('dst-nav-open')
            || $('.dst-search.is-open') !== null
            || $('.dst-popup.is-open') !== null
            || $('.dst-lightbox.is-open') !== null;

        body.style.overflow = locked ? 'hidden' : '';
    }

    async function submitJson(url, formData) {
        var token = csrf();
        var headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (token.token) {
            headers[token.header] = token.token;
        }

        var response;

        try {
            response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: headers,
                credentials: 'same-origin'
            });
        } catch (networkError) {
            throw new Error('Network error. Please check your connection and try again.');
        }

        var payload = null;

        try {
            payload = await response.json();
        } catch (parseError) {
            payload = null;
        }

        if (!payload) {
            throw new Error(response.status === 403
                ? 'Your session expired. Please refresh the page and try again.'
                : 'Unexpected response from the server. Please try again.');
        }

        if (!response.ok || payload.success === false) {
            var error = new Error(payload.message || 'Something went wrong. Please try again.');

            error.status = response.status;
            error.payload = payload;

            throw error;
        }

        return payload;
    }

    function setLoading(button, loading) {
        if (!button) {
            return;
        }

        button.classList.toggle('is-loading', loading);
        button.disabled = loading;
    }

    /* ---------------------------------------------------------------------
     * Preloader
     * ------------------------------------------------------------------- */

    (function preloader() {
        var el = $('[data-preloader]');

        if (!el) {
            return;
        }

        var done = function () {
            el.classList.add('is-done');

            window.setTimeout(function () {
                el.remove();
            }, 600);
        };

        if (doc.readyState === 'complete') {
            window.setTimeout(done, 220);
        } else {
            window.addEventListener('load', function () {
                window.setTimeout(done, 220);
            });
            window.setTimeout(done, 4000);
        }
    })();

    /* ---------------------------------------------------------------------
     * Header, reading progress, back to top
     * ------------------------------------------------------------------- */

    (function scrollUI() {
        var header = $('[data-header]');
        var progress = $('[data-reading-progress] span');
        var toTop = $('[data-totop]');
        var ticking = false;

        var update = function () {
            var y = window.scrollY || window.pageYOffset;

            if (header) {
                header.classList.toggle('is-stuck', y > 10);
            }

            if (progress) {
                var height = doc.documentElement.scrollHeight - window.innerHeight;
                var ratio = height > 0 ? Math.min(1, Math.max(0, y / height)) : 0;

                progress.style.width = (ratio * 100).toFixed(2) + '%';
            }

            if (toTop) {
                toTop.classList.toggle('is-visible', y > 640);
            }

            ticking = false;
        };

        var onScroll = function () {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();

        if (toTop) {
            toTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
            });
        }
    })();

    /* ---------------------------------------------------------------------
     * Drawer & search overlay
     * ------------------------------------------------------------------- */

    var drawer = $('[data-drawer]');
    var searchPanel = $('[data-search-panel]');
    var backdrop = $('[data-backdrop]');
    var lastFocus = null;

    function toggleDrawer(open) {
        if (!drawer) {
            return;
        }

        if (open) {
            lastFocus = doc.activeElement;
        }

        body.classList.toggle('dst-nav-open', open);
        drawer.setAttribute('aria-hidden', open ? 'false' : 'true');

        $$('[data-menu-open]').forEach(function (button) {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        if (backdrop) {
            if (open) {
                backdrop.hidden = false;
                window.requestAnimationFrame(function () {
                    backdrop.classList.add('is-open');
                });
            } else {
                backdrop.classList.remove('is-open');
                window.setTimeout(function () {
                    if (!body.classList.contains('dst-nav-open')) {
                        backdrop.hidden = true;
                    }
                }, 260);
            }
        }

        syncScrollLock();

        if (open) {
            var target = drawer.querySelector('a, button');

            if (target) {
                window.setTimeout(function () {
                    target.focus({ preventScroll: true });
                }, 40);
            }
        } else if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus({ preventScroll: true });
        }
    }

    function toggleSearch(open) {
        if (!searchPanel) {
            return;
        }

        if (open) {
            lastFocus = doc.activeElement;
        }

        searchPanel.classList.toggle('is-open', open);
        searchPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
        syncScrollLock();

        if (open) {
            var input = $('[data-search-input]', searchPanel);

            if (input) {
                window.setTimeout(function () {
                    input.focus();
                }, 120);
            }
        } else if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus({ preventScroll: true });
        }
    }

    doc.addEventListener('click', function (event) {
        if (event.target.closest('[data-menu-open]')) {
            event.preventDefault();
            toggleDrawer(true);

            return;
        }

        if (event.target.closest('[data-menu-close]') && !event.target.closest('a[href^="#"]')) {
            event.preventDefault();
            toggleDrawer(false);

            return;
        }

        if (event.target.closest('[data-search-open]')) {
            event.preventDefault();
            toggleSearch(true);

            return;
        }

        if (event.target.closest('[data-search-close]')) {
            event.preventDefault();
            toggleSearch(false);

            return;
        }

        if (event.target === searchPanel) {
            toggleSearch(false);
        }
    });

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            toggleDrawer(false);
        });
    }

    $$('[data-drawer] a[href]').forEach(function (link) {
        link.addEventListener('click', function () {
            if (body.classList.contains('dst-nav-open')) {
                toggleDrawer(false);
            }
        });
    });

    /* ---------------------------------------------------------------------
     * Language dropdown
     * ------------------------------------------------------------------- */

    (function languageDropdown() {
        var details = $$('details.dst-lang');

        if (details.length === 0) {
            return;
        }

        doc.addEventListener('click', function (event) {
            details.forEach(function (item) {
                if (!item.contains(event.target)) {
                    item.open = false;
                }
            });
        });
    })();

    /* ---------------------------------------------------------------------
     * Reveal on scroll
     * ------------------------------------------------------------------- */

    (function reveal() {
        var items = $$('[data-reveal]');

        if (items.length === 0) {
            return;
        }

        if (reducedMotion || !('IntersectionObserver' in window)) {
            root.classList.add('dst-anim');
            items.forEach(function (item) {
                item.classList.add('is-visible');
            });

            return;
        }

        root.classList.add('dst-anim');

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -60px 0px', threshold: 0.08 });

        items.forEach(function (item) {
            observer.observe(item);
        });
    })();

    /* ---------------------------------------------------------------------
     * Hero slider
     * ------------------------------------------------------------------- */

    (function heroSlider() {
        var slider = $('[data-slider]');

        if (!slider || typeof window.Swiper !== 'function') {
            return;
        }

        var slides = $$('.swiper-slide', slider).length;

        // A single banner is rendered as a static slide: Swiper would otherwise
        // inject control markup and inline slide widths that can overflow.
        if (slides <= 1) {
            return;
        }

        var options = {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: slides > 2,
            speed: 620,
            grabCursor: true,
            observer: true,
            observeParents: true,
            keyboard: { enabled: true },
            a11y: { enabled: true },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev'
            }
        };

        if (slides > 1 && !reducedMotion) {
            options.autoplay = { delay: 6500, disableOnInteraction: true, pauseOnMouseEnter: true };
        }

        new window.Swiper(slider, options);
    })();

    /* ---------------------------------------------------------------------
     * Table of contents (single pages)
     * ------------------------------------------------------------------- */

    (function tableOfContents() {
        var nav = $('[data-toc]');
        var list = $('[data-toc-list]');
        var article = $('[data-article-body]');

        if (!nav || !list || !article) {
            return;
        }

        var slug = function (value) {
            return (value || '')
                .toLowerCase()
                .replace(/[^\w\u00C0-\u024F]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 64);
        };

        var headings = $$('h2, h3', article);

        if (headings.length < 2) {
            return;
        }

        var used = Object.create(null);
        var links = [];

        headings.forEach(function (heading, index) {
            var id = heading.id;

            if (!id) {
                var seed = slug(heading.textContent) || 'section-' + (index + 1);
                id = seed;
                var suffix = 2;

                while (used[id] || doc.getElementById(id)) {
                    id = seed + '-' + suffix;
                    suffix += 1;
                }

                heading.id = id;
            }

            used[id] = true;

            var item = doc.createElement('li');

            if (heading.tagName === 'H3') {
                item.className = 'is-sub';
            }

            var link = doc.createElement('a');
            link.href = '#' + id;
            link.textContent = (heading.textContent || '').trim();
            item.appendChild(link);
            list.appendChild(item);
            links.push(link);
        });

        nav.hidden = false;

        var setActive = function (id) {
            links.forEach(function (link) {
                var active = link.getAttribute('href') === '#' + id;
                link.classList.toggle('is-active', active);

                if (active) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        setActive(entry.target.id);
                    }
                });
            }, { rootMargin: '-18% 0px -70% 0px', threshold: 0 });

            headings.forEach(function (heading) {
                observer.observe(heading);
            });
        }
    })();

    /* ---------------------------------------------------------------------
     * Lightbox for gallery shortcodes
     * ------------------------------------------------------------------- */

    (function lightbox() {
        var triggers = $$('[data-lightbox]');

        if (triggers.length === 0) {
            return;
        }

        var overlay = null;
        var lastTrigger = null;

        var close = function () {
            if (!overlay) {
                return;
            }

            overlay.classList.remove('is-open');
            var node = overlay;
            overlay = null;
            syncScrollLock();
            window.setTimeout(function () {
                node.remove();
            }, 220);

            if (lastTrigger) {
                lastTrigger.focus({ preventScroll: true });
            }
        };

        var open = function (trigger) {
            var href = trigger.getAttribute('href');
            var image = trigger.querySelector('img');

            if (!href) {
                return;
            }

            lastTrigger = trigger;
            overlay = doc.createElement('div');
            overlay.className = 'dst-lightbox';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.setAttribute('aria-label', (image && image.alt) || 'Image preview');

            var preview = doc.createElement('img');
            preview.src = href;
            preview.alt = (image && image.alt) || '';

            var button = doc.createElement('button');
            button.type = 'button';
            button.className = 'dst-lightbox__close';
            button.setAttribute('aria-label', 'Close');
            button.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';

            overlay.appendChild(preview);
            overlay.appendChild(button);
            body.appendChild(overlay);

            overlay.addEventListener('click', function (event) {
                if (event.target === overlay || event.target.closest('.dst-lightbox__close')) {
                    close();
                }
            });

            window.requestAnimationFrame(function () {
                overlay.classList.add('is-open');
            });

            syncScrollLock();
            button.focus({ preventScroll: true });
        };

        triggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                open(trigger);
            });
        });

        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });
    })();

    /* ---------------------------------------------------------------------
     * Share buttons
     * ------------------------------------------------------------------- */

    (function share() {
        if (!navigator.clipboard && !doc.queryCommandSupported) {
            return;
        }

        var copy = function (text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }

            return new Promise(function (resolve, reject) {
                var area = doc.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.top = '-1000px';
                body.appendChild(area);
                area.select();

                try {
                    doc.execCommand('copy');
                    resolve();
                } catch (error) {
                    reject(error);
                } finally {
                    area.remove();
                }
            });
        };

        $$('[data-copy-link]').forEach(function (button) {
            button.addEventListener('click', function () {
                copy(window.location.origin + window.location.pathname + window.location.search)
                    .then(function () {
                        button.classList.add('is-copied');
                        button.setAttribute('data-copied', '1');
                        window.setTimeout(function () {
                            button.classList.remove('is-copied');
                            button.removeAttribute('data-copied');
                        }, 1600);
                    })
                    .catch(function () {
                        setMessage(button.parentNode, 'Could not copy the link.', 'error');
                    });
            });
        });
    })();

    /* ---------------------------------------------------------------------
     * Newsletter
     * ------------------------------------------------------------------- */

    (function newsletter() {
        $$('[data-newsletter]').forEach(function (form) {
            var message = $('[data-newsletter-msg]', form);
            var button = $('button[type="submit"]', form);

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                var email = $('input[name="email"]', form);
                var privacy = $('input[name="privacy"]', form);

                if (!email || !email.value.trim() || !email.checkValidity()) {
                    setMessage(message, 'Please enter a valid email address.', 'error');
                    if (email) {
                        email.focus();
                    }

                    return;
                }

                if (privacy && !privacy.checked) {
                    setMessage(message, 'Please accept the privacy notice to subscribe.', 'error');
                    privacy.focus();

                    return;
                }

                setLoading(button, true);
                setMessage(message, '');

                try {
                    var data = await submitJson(baseUrl() + 'newsletter', new FormData(form));
                    setMessage(message, data.message || 'Thank you for subscribing.', 'success');
                    form.reset();
                } catch (error) {
                    setMessage(message, error.message, 'error');
                } finally {
                    setLoading(button, false);
                }
            });
        });
    })();

    /* ---------------------------------------------------------------------
     * Comments
     * ------------------------------------------------------------------- */

    (function comments() {
        var form = $('[data-comment-form]');

        if (!form) {
            return;
        }

        var bodyField = $('textarea[name="body"]', form);
        var counter = $('[data-comment-counter]', form);
        var parentField = $('[data-parent-id]', form);
        var replyNote = $('[data-reply-note]', form);
        var title = $('[data-comment-title]', form);
        var message = $('[data-comment-msg]', form);
        var button = $('button[type="submit"]', form);
        var defaultTitle = title ? title.textContent : 'Leave a comment';
        var replyName = 'this comment';

        var updateCounter = function () {
            if (!counter || !bodyField) {
                return;
            }

            counter.textContent = bodyField.value.length + ' / ' + bodyField.maxLength;
        };

        if (bodyField) {
            bodyField.addEventListener('input', updateCounter);
        }

        var resetReply = function () {
            if (parentField) {
                parentField.value = '0';
            }

            if (replyNote) {
                replyNote.hidden = true;
            }

            if (title) {
                title.textContent = defaultTitle;
            }

            replyName = 'this comment';
        };

        doc.addEventListener('click', function (event) {
            var reply = event.target.closest('[data-reply]');

            if (reply) {
                event.preventDefault();

                var id = reply.getAttribute('data-reply');
                var comment = reply.closest('.dst-comment');
                var author = comment ? $('.dst-comment__meta strong', comment) : null;

                replyName = author ? author.textContent : 'this comment';

                if (parentField) {
                    parentField.value = id || '0';
                }

                if (replyNote) {
                    replyNote.hidden = false;
                    var label = $('[data-reply-label]', replyNote);

                    if (label) {
                        label.textContent = 'Replying to ' + replyName;
                    }
                }

                if (title) {
                    title.textContent = 'Reply to ' + replyName;
                }

                form.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'center' });

                if (bodyField) {
                    window.setTimeout(function () {
                        bodyField.focus();
                    }, reducedMotion ? 0 : 360);
                }

                return;
            }

            if (event.target.closest('[data-cancel-reply]')) {
                event.preventDefault();
                resetReply();
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!bodyField || bodyField.value.trim().length < 2) {
                setMessage(message, 'Please write a comment before posting.', 'error');
                if (bodyField) {
                    bodyField.focus();
                }

                return;
            }

            setLoading(button, true);
            setMessage(message, '');

            try {
                var data = await submitJson(baseUrl() + 'comments', new FormData(form));

                if (data.status === 'approved') {
                    try {
                        window.sessionStorage.setItem('dst:comment-notice', data.message || 'Your comment is live.');
                    } catch (storageError) {
                        // Storage can be unavailable in private mode.
                    }

                    window.setTimeout(function () {
                        window.location.reload();
                    }, 500);

                    return;
                }

                setMessage(message, data.message || 'Your comment is awaiting moderation.', 'success');
                form.reset();
                resetReply();
                updateCounter();
            } catch (error) {
                setMessage(message, error.message, 'error');
            } finally {
                setLoading(button, false);
            }
        });

        try {
            var notice = window.sessionStorage.getItem('dst:comment-notice');

            if (notice) {
                window.sessionStorage.removeItem('dst:comment-notice');
                setMessage(message, notice, 'success');
            }
        } catch (storageError) {
            // Ignore storage errors.
        }

        updateCounter();
    })();

    /* ---------------------------------------------------------------------
     * AJAX forms (contact, order requests)
     * ------------------------------------------------------------------- */

    (function ajaxForms() {
        $$('form[data-ajax-form]').forEach(function (form) {
            var message = $('[data-form-msg]', form);
            var button = $('button[type="submit"]', form);

            var clearErrors = function () {
                $$('[data-error-for]', form).forEach(function (slot) {
                    slot.textContent = '';
                });
                $$('.dst-field.has-error', form).forEach(function (field) {
                    field.classList.remove('has-error');
                });
                $$('[aria-invalid="true"]', form).forEach(function (field) {
                    field.removeAttribute('aria-invalid');
                });
            };

            var applyErrors = function (errors) {
                Object.keys(errors || {}).forEach(function (key) {
                    var slot = $('[data-error-for="' + key + '"]', form);

                    if (slot) {
                        slot.textContent = errors[key];
                        var field = slot.closest('.dst-field');
                        if (field) {
                            field.classList.add('has-error');
                        }
                    }
                });

                var firstInvalid = $('.dst-field.has-error', form);

                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'center' });
                }
            };

            $$('input, select, textarea', form).forEach(function (field) {
                var clearFieldError = function () {
                    var fieldWrapper = field.closest('.dst-field');

                    if (fieldWrapper) {
                        fieldWrapper.classList.remove('has-error');
                    }
                };

                field.addEventListener('input', clearFieldError);
                field.addEventListener('change', clearFieldError);
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearErrors();
                setMessage(message, '');

                if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                    return;
                }

                setLoading(button, true);

                try {
                    var data = await submitJson(form.getAttribute('action'), new FormData(form));
                    setMessage(message, data.message || 'Thank you! Your message has been sent.', 'success');
                    form.reset();

                    var captcha = $('.cf-turnstile, .h-captcha, .g-recaptcha', form);
                    if (captcha) {
                        captcha.innerHTML = '';
                    }
                } catch (error) {
                    setMessage(message, error.message, 'error');

                    if (error.payload && error.payload.errors) {
                        applyErrors(error.payload.errors);
                    }
                } finally {
                    setLoading(button, false);
                }
            });
        });
    })();

    /* ---------------------------------------------------------------------
     * Popups
     * ------------------------------------------------------------------- */

    (function popups() {
        var nodes = $$('[data-popup]');

        if (nodes.length === 0) {
            return;
        }

        var active = null;

        var storage = {
            day: 86400000,
            week: 604800000
        };

        var seen = function (node) {
            var id = node.getAttribute('data-popup-id') || '';
            var frequency = node.getAttribute('data-frequency') || 'session';

            if (frequency === 'always') {
                return false;
            }

            var key = 'dst:popup:' + id;

            try {
                if (frequency === 'session') {
                    return window.sessionStorage.getItem(key) === '1';
                }

                var stamp = window.localStorage.getItem(key);

                if (!stamp) {
                    return false;
                }

                var maxAge = storage[frequency] || storage.day;

                return Date.now() - parseInt(stamp, 10) < maxAge;
            } catch (error) {
                return false;
            }
        };

        var markSeen = function (node) {
            var id = node.getAttribute('data-popup-id') || '';
            var frequency = node.getAttribute('data-frequency') || 'session';
            var key = 'dst:popup:' + id;

            try {
                if (frequency === 'session') {
                    window.sessionStorage.setItem(key, '1');
                } else if (frequency !== 'always') {
                    window.localStorage.setItem(key, String(Date.now()));
                }
            } catch (error) {
                // Storage is optional.
            }
        };

        var close = function (node) {
            node.classList.remove('is-open');
            node.setAttribute('aria-hidden', 'true');

            if (active === node) {
                active = null;
            }

            syncScrollLock();
        };

        var open = function (node) {
            if (active) {
                close(active);
            }

            active = node;
            markSeen(node);
            node.classList.add('is-open');
            node.setAttribute('aria-hidden', 'false');
            syncScrollLock();

            var focusable = node.querySelector('[data-popup-close]');

            if (focusable) {
                window.setTimeout(function () {
                    focusable.focus({ preventScroll: true });
                }, 80);
            }
        };

        var schedule = function (node) {
            if (seen(node)) {
                return;
            }

            var trigger = node.getAttribute('data-trigger') || 'delay';
            var value = parseInt(node.getAttribute('data-value') || '0', 10);

            if (trigger === 'delay') {
                var delay = (value > 0 ? value : 4) * 1000;
                window.setTimeout(function () {
                    open(node);
                }, delay);

                return;
            }

            if (trigger === 'scroll') {
                var percent = Math.min(100, Math.max(5, value || 50));
                var fired = false;

                var onScroll = function () {
                    if (fired) {
                        return;
                    }

                    var height = doc.documentElement.scrollHeight - window.innerHeight;
                    var ratio = height > 0 ? (window.scrollY / height) * 100 : 0;

                    if (ratio >= percent) {
                        fired = true;
                        window.removeEventListener('scroll', onScroll);
                        open(node);
                    }
                };

                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();

                return;
            }

            if (trigger === 'exit') {
                var onLeave = function (event) {
                    if (event.clientY <= 0 && !event.relatedTarget) {
                        doc.removeEventListener('mouseout', onLeave);
                        open(node);
                    }
                };

                doc.addEventListener('mouseout', onLeave);

                return;
            }

            if (trigger === 'click') {
                var onClick = function () {
                    doc.removeEventListener('click', onClick, true);
                    open(node);
                };

                window.setTimeout(function () {
                    doc.addEventListener('click', onClick, true);
                }, 400);
            }
        };

        nodes.forEach(function (node) {
            node.addEventListener('click', function (event) {
                if (event.target.closest('[data-popup-close]')) {
                    event.preventDefault();
                    close(node);
                }
            });

            schedule(node);
        });
    })();

    /* ---------------------------------------------------------------------
     * Global keyboard shortcuts
     * ------------------------------------------------------------------- */

    doc.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (body.classList.contains('dst-nav-open')) {
            toggleDrawer(false);
        }

        if (searchPanel && searchPanel.classList.contains('is-open')) {
            toggleSearch(false);
        }

        var popup = $('.dst-popup.is-open');

        if (popup) {
            popup.classList.remove('is-open');
            popup.setAttribute('aria-hidden', 'true');
            syncScrollLock();
        }
    });

    /* ---------------------------------------------------------------------
     * Image fallbacks & lazy svg logos
     * ------------------------------------------------------------------- */

    doc.addEventListener('error', function (event) {
        var target = event.target;

        if (target && target.tagName === 'IMG' && !target.dataset.fallbackApplied) {
            target.dataset.fallbackApplied = '1';
            target.style.visibility = 'hidden';
        }
    }, true);
})();
