/* ==========================================================================
   ARABCIA — experience layer
   ==========================================================================
   Progressive enhancement only. Every feature here is additive: with this file
   blocked, missing, or throwing, the site still renders and reads correctly.

   Two rules this file follows without exception:

     1. Honour `prefers-reduced-motion`. The OS setting is a request, and a
        site that ignores it makes some people ill. Every moving feature is
        skipped when it is set, and reveal elements are shown immediately
        rather than waiting for an observer that will never run.

     2. Never throw. Each feature is wrapped independently, so one failure
        cannot take the others down with it.

   No dependencies on purpose: jQuery 1.12 is already on the page, and a second
   animation runtime beside it is how two libraries end up fighting over the
   same property.
   ========================================================================== */

(function () {
    'use strict';

    var motionQuery = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : { matches: false, addEventListener: null };

    function prefersReducedMotion() {
        return motionQuery.matches;
    }

    /* Isolated, so one failure cannot take the others with it. */
    function safely(label, fn) {
        try {
            fn();
        } catch (error) {
            if (window.console && console.warn) {
                console.warn('[ux] ' + label + ' failed:', error);
            }
        }
    }

    function each(selector, callback, scope) {
        var nodes = (scope || document).querySelectorAll(selector);

        for (var i = 0; i < nodes.length; i++) {
            callback(nodes[i], i);
        }
    }

    /* =====================================================================
       Scroll progress
       =====================================================================
       Written as a CSS custom property rather than by setting `style.width`,
       so the bar animates on the compositor and the scroll handler never
       touches layout. rAF-throttled because scroll fires far faster than the
       screen refreshes.
       ===================================================================== */

    safely('scroll progress', function () {
        var bar = document.querySelector('.ux-progress');

        if (!bar) {
            return;
        }

        var ticking = false;

        function update() {
            var scrollable = document.documentElement.scrollHeight - window.innerHeight;
            var ratio = scrollable > 0 ? window.scrollY / scrollable : 0;

            bar.style.setProperty('--ux-progress', Math.min(1, Math.max(0, ratio)));
            ticking = false;
        }

        function onScroll() {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    });

    /* =====================================================================
       Reveal on scroll
       =====================================================================
       The `.no-js` class on <html> is what keeps the "hidden" CSS from
       applying when scripting is unavailable. Removing it is the switch that
       turns the reveal system on, so it happens first, before anything that
       might throw.

       Staggering is computed per group rather than per element: siblings inside
       a shared container fade in together in reading order, which reads as one
       group arriving rather than as a queue.
       ===================================================================== */

    safely('reveal setup', function () {
        document.documentElement.classList.remove('no-js');

        if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
            // Nothing will animate them, so show them now rather than leaving a
            // page of invisible content behind.
            each('.ux-reveal', function (node) {
                node.classList.add('is-revealed');
            });

            return;
        }

        each('[data-ux-stagger]', function (group) {
            var step = parseInt(group.getAttribute('data-ux-stagger'), 10) || 90;
            var items = group.querySelectorAll('.ux-reveal');
            var cap = 8; // past this it stops reading as a group, it reads as lag

            for (var i = 0; i < items.length; i++) {
                items[i].style.setProperty('--ux-reveal-delay', Math.min(i, cap) * step + 'ms');
            }
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-revealed');
                // One-shot: an element that has arrived needs no further
                // watching, and unobserving is what keeps this cheap on a long
                // programme page.
                observer.unobserve(entry.target);
            });
        }, {
            // Fire before the element's top edge reaches the viewport so it has
            // finished moving by the time it is properly on screen.
            rootMargin: '0px 0px -12% 0px',
            threshold: 0.08
        });

        each('.ux-reveal', function (node) {
            observer.observe(node);
        });
    });

    /* =====================================================================
   Counters
   =====================================================================
   Counts from 0 to the number already in the markup. The final value is read
   from the text rather than from a data attribute, so the server stays the
   single source of truth for the figure and this can never disagree with it.

   rAF with an ease-out curve: linear counting reads as a loading bar, this
   reads as something arriving at a value.
   ===================================================================== */

    safely('counters', function () {
        var targets = document.querySelectorAll('.ux-stat__value');

        if (targets.length === 0 || prefersReducedMotion()) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            return;
        }

        function parse(text) {
            var cleaned = String(text).replace(/[^\d]/g, '');

            return cleaned === '' ? null : parseInt(cleaned, 10);
        }

        function format(value, original) {
            // Restore the server's own grouping rather than inventing one.
            if (/[ , ]/.test(original)) {
                return value.toLocaleString();
            }

            return String(value);
        }

        function run(node) {
            var original = node.textContent.trim();
            var target = parse(original);

            if (target === null || target === 0) {
                return;
            }

            var duration = 1400;
            var start = null;

            function step(timestamp) {
                if (start === null) {
                    start = timestamp;
                }

                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);

                node.textContent = format(Math.round(target * eased), original);

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            }

            node.textContent = format(0, original);
            window.requestAnimationFrame(step);
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                run(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.4 });

        each('.ux-stat__value', function (node) {
            observer.observe(node);
        });
    });

    /* =====================================================================
   Pointer effects: magnetic buttons and card tilt
   =====================================================================
   Both are pointer-device only. On touch there is no hover state to respond to,
   and listening anyway costs battery for nothing.

   Both are also deliberately weak. A magnet that travels 20px pulls the button
   out from under the cursor, which makes it harder to click; the point is a
   hint of life, not a toy.
   ===================================================================== */

    safely('pointer effects', function () {
        if (prefersReducedMotion()) {
            return;
        }

        // No fine pointer means touch-only, where hover simply does not exist.
        if (window.matchMedia && window.matchMedia('(hover: none)').matches) {
            return;
        }

        each('[data-ux-magnetic]', function (node) {
            var strength = parseFloat(node.getAttribute('data-ux-magnetic')) || 0.28;

            node.addEventListener('pointermove', function (event) {
                var box = node.getBoundingClientRect();
                var x = (event.clientX - box.left - box.width / 2) / box.width;
                var y = (event.clientY - box.top - box.height / 2) / box.height;

                node.style.setProperty('--mx', (x * strength * 100).toFixed(2) + 'px');
                node.style.setProperty('--my', (y * strength * 100).toFixed(2) + 'px');
            });

            node.addEventListener('pointerleave', function () {
                node.style.setProperty('--mx', '0px');
                node.style.setProperty('--my', '0px');
            });
        });

        each('[data-ux-tilt]', function (node) {
            var max = parseFloat(node.getAttribute('data-ux-tilt')) || 6;

            node.addEventListener('pointerenter', function () {
                node.classList.add('ux-tilt-active');
            });

            node.addEventListener('pointermove', function (event) {
                var box = node.getBoundingClientRect();
                var x = (event.clientX - box.left) / box.width - 0.5;
                var y = (event.clientY - box.top) / box.height - 0.5;

                node.style.setProperty('--tilt-x', (-y * max).toFixed(2) + 'deg');
                node.style.setProperty('--tilt-y', (x * max).toFixed(2) + 'deg');
            });

            node.addEventListener('pointerleave', function () {
                node.classList.remove('ux-tilt-active');
                node.style.setProperty('--tilt-x', '0deg');
                node.style.setProperty('--tilt-y', '0deg');
            });
        });
    });

    /* =====================================================================
   Hero parallax
   =====================================================================
   Only the aurora fields move, and only a little. The text does not move,
   because moving body text under a pointer is a readability problem before it
   is an effect.

   Each field already carries a slow CSS drift animation. This adds the scroll
   offset as a separate custom property so the two compose rather than
   overwriting each other's transform.
   ===================================================================== */

    safely('hero parallax', function () {
        var fields = document.querySelectorAll('[data-ux-parallax]');

        if (fields.length === 0 || prefersReducedMotion()) {
            return;
        }

        var ticking = false;

        function update() {
            var y = window.scrollY;

            each('[data-ux-parallax]', function (node, index) {
                var speed = parseFloat(node.getAttribute('data-ux-parallax')) || 0.12;
                // index offsets each field so they separate rather than sliding
                // as one block.
                node.style.setProperty(
                    '--px',
                    (y * speed * (index + 1) * 0.4).toFixed(1) + 'px'
                );
            });

            ticking = false;
        }

        function onScroll() {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        update();
    });

    /* =====================================================================
   Header shrink
   =====================================================================
   A class rather than an inline style, so the transition lives in CSS and the
   JS only decides when the state changes.
   ===================================================================== */

    safely('header shrink', function () {
        var header = document.querySelector('.ux-header');

        if (!header) {
            return;
        }

        var ticking = false;

        function update() {
            header.classList.toggle('is-scrolled', window.scrollY > 40);
            ticking = false;
        }

        function onScroll() {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        update();
    });

    /* =====================================================================
   Live region
   =====================================================================
   The day switcher on the programme page filters content client-side. A
   sighted visitor sees the list change; a screen-reader user would otherwise
   get no confirmation at all, so the result count is announced politely.

   Created up front rather than on demand so the first filter has somewhere to
   announce into.
   ===================================================================== */

    safely('live region', function () {
        if (document.getElementById('ux-live')) {
            return;
        }

        var region = document.createElement('div');

        region.id = 'ux-live';
        region.className = 'sr-only';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'true');

        document.body.appendChild(region);
    });

    /* =====================================================================
   Live preference changes
   =====================================================================
   A visitor can switch reduced motion on while the page is open. CSS handles
   the visual side instantly through the media query; this only has to release
   anything the observer was holding, or it stays invisible.
   ===================================================================== */

    if (motionQuery.addEventListener) {
        motionQuery.addEventListener('change', function () {
            if (!motionQuery.matches) {
                return;
            }

            each('.ux-reveal', function (node) {
                node.classList.add('is-revealed');
            });
        });
    }
})();