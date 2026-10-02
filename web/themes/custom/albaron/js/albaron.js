/**
 * Al-Baron theme behaviours.
 */
((Drupal, once) => {
  'use strict';

  /** Collect a navigable image group from a clicked thumbnail. */
  const buildGroup = (clickedImg) => {
    const ctx =
      clickedImg.closest('.product-gallery') ||
      clickedImg.closest('.masonry') ||
      clickedImg.closest('[data-lightbox]');
    let imgs;
    if (ctx && ctx.classList.contains('product-gallery')) {
      const thumbs = ctx.querySelectorAll('.product-gallery__thumbs img');
      imgs = thumbs.length ? [...thumbs] : [clickedImg];
    } else if (ctx) {
      imgs = [...ctx.querySelectorAll('img')];
    } else {
      imgs = [clickedImg];
    }
    const seen = new Set();
    const group = [];
    imgs.forEach((im) => {
      const large = im.getAttribute('data-large') || im.currentSrc || im.src;
      if (seen.has(large)) {
        return;
      }
      seen.add(large);
      const figCap = im.closest('figure') ? im.closest('figure').querySelector('figcaption') : null;
      const caption =
        im.getAttribute('data-caption') || (figCap ? figCap.textContent.trim() : '') || im.alt || '';
      group.push({ large, alt: im.alt || '', caption });
    });
    const clickedLarge = clickedImg.getAttribute('data-large') || clickedImg.currentSrc || clickedImg.src;
    let index = group.findIndex((g) => g.large === clickedLarge);
    if (index < 0) {
      index = 0;
    }
    return { group, index };
  };

  /** Create (once) the shared gallery lightbox and return it. */
  const ensureLightbox = () => {
    let box = document.querySelector('.lightbox');
    if (box) {
      return box;
    }
    box = document.createElement('div');
    box.className = 'lightbox';
    box.innerHTML =
      '<button class="lightbox__close" aria-label="Close">&times;</button>' +
      '<button class="lightbox__nav lightbox__prev" aria-label="Previous image">&#8249;</button>' +
      '<figure class="lightbox__stage"><img class="lightbox__img" alt=""><figcaption class="lightbox__caption"></figcaption></figure>' +
      '<button class="lightbox__nav lightbox__next" aria-label="Next image">&#8250;</button>' +
      '<div class="lightbox__counter"></div>';
    document.body.appendChild(box);

    const state = { group: [], index: 0 };
    const imgEl = box.querySelector('.lightbox__img');
    const capEl = box.querySelector('.lightbox__caption');
    const countEl = box.querySelector('.lightbox__counter');
    const prevBtn = box.querySelector('.lightbox__prev');
    const nextBtn = box.querySelector('.lightbox__next');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let animating = false;

    const setImage = (item, index) => {
      imgEl.src = item.large;
      imgEl.alt = item.alt;
      capEl.textContent = item.caption || '';
      const multi = state.group.length > 1;
      countEl.textContent = multi ? `${index + 1} / ${state.group.length}` : '';
      prevBtn.hidden = !multi;
      nextBtn.hidden = !multi;
    };

    // Fade + slide + zoom the image in from the given x offset.
    const enter = (fromX) => {
      if (reduced) {
        imgEl.style.opacity = '1';
        imgEl.style.transform = 'none';
        return;
      }
      imgEl.style.transition = 'none';
      imgEl.style.opacity = '0';
      imgEl.style.transform = `translateX(${fromX}px) scale(0.96)`;
      void imgEl.offsetWidth;
      imgEl.style.transition = 'opacity 0.3s ease, transform 0.45s cubic-bezier(0.22, 1, 0.36, 1)';
      imgEl.style.opacity = '1';
      imgEl.style.transform = 'translateX(0) scale(1)';
    };

    const animateTo = (d) => {
      const n = state.group.length;
      if (n < 2 || animating) {
        return;
      }
      const nextIndex = (state.index + d + n) % n;
      const item = state.group[nextIndex];
      if (reduced) {
        state.index = nextIndex;
        setImage(item, nextIndex);
        return;
      }
      animating = true;
      const preload = new Image();
      const proceed = () => {
        // Exit current image towards the opposite side.
        imgEl.style.transition = 'opacity 0.2s ease, transform 0.25s ease';
        imgEl.style.opacity = '0';
        imgEl.style.transform = `translateX(${-d * 48}px) scale(0.97)`;
        window.setTimeout(() => {
          state.index = nextIndex;
          setImage(item, nextIndex);
          enter(d * 48);
          window.setTimeout(() => { animating = false; }, 420);
        }, 180);
      };
      preload.onload = proceed;
      preload.onerror = proceed;
      preload.src = item.large;
    };

    const close = () => {
      box.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    box._open = ({ group, index }) => {
      state.group = group;
      state.index = index;
      setImage(group[index], index);
      box.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      enter(0);
    };

    box.querySelector('.lightbox__close').addEventListener('click', close);
    prevBtn.addEventListener('click', (e) => { e.stopPropagation(); animateTo(-1); });
    nextBtn.addEventListener('click', (e) => { e.stopPropagation(); animateTo(1); });
    box.addEventListener('click', (e) => {
      if (e.target === box || e.target.classList.contains('lightbox__stage')) {
        close();
      }
    });
    document.addEventListener('keydown', (e) => {
      if (!box.classList.contains('is-open')) {
        return;
      }
      if (e.key === 'Escape') {
        close();
      } else if (e.key === 'ArrowLeft') {
        animateTo(-1);
      } else if (e.key === 'ArrowRight') {
        animateTo(1);
      }
    });
    return box;
  };

  /** Sticky header state on scroll. */
  Drupal.behaviors.albaronHeader = {
    attach(context) {
      once('albaron-header', '.site-header', context).forEach((header) => {
        const onScroll = () => {
          header.classList.toggle('is-scrolled', window.scrollY > 24);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
      });
    },
  };

  /** Mobile navigation toggle. */
  Drupal.behaviors.albaronNav = {
    attach(context) {
      once('albaron-nav', '.nav-toggle', context).forEach((toggle) => {
        const nav = document.querySelector('.primary-nav');
        const backdrop = document.querySelector('.nav-backdrop');
        if (!nav) {
          return;
        }
        const setState = (open) => {
          toggle.classList.toggle('is-open', open);
          nav.classList.toggle('is-open', open);
          toggle.setAttribute('aria-expanded', String(open));
          if (backdrop) {
            backdrop.classList.toggle('is-visible', open);
          }
          document.body.style.overflow = open ? 'hidden' : '';
        };
        toggle.addEventListener('click', () => setState(!nav.classList.contains('is-open')));
        if (backdrop) {
          backdrop.addEventListener('click', () => setState(false));
        }
        nav.querySelectorAll('a').forEach((link) =>
          link.addEventListener('click', () => setState(false)),
        );
      });
    },
  };

  /** Product detail: swap main gallery image from thumbnails. */
  Drupal.behaviors.albaronProductGallery = {
    attach(context) {
      once('albaron-pgallery', '.product-gallery', context).forEach((gallery) => {
        const main = gallery.querySelector('.product-gallery__main img');
        if (!main) {
          return;
        }
        gallery.querySelectorAll('.product-gallery__thumbs img').forEach((thumb) => {
          thumb.addEventListener('click', () => {
            const next = thumb.getAttribute('data-large') || thumb.src;
            main.src = next;
          });
        });
      });
    },
  };

  /** Gallery lightbox with prev/next navigation, counter and captions. */
  Drupal.behaviors.albaronLightbox = {
    attach(context) {
      const triggers = once(
        'albaron-lightbox',
        '.masonry__item img, .product-gallery__main img, [data-lightbox] img',
        context,
      );
      if (!triggers.length) {
        return;
      }
      const box = ensureLightbox();
      triggers.forEach((img) => {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', () => box._open(buildGroup(img)));
      });
    },
  };

  /** Animated count-up for the stat numbers when they scroll into view. */
  Drupal.behaviors.albaronCounters = {
    attach(context) {
      const counters = once('albaron-counter', '.stat__num', context);
      if (!counters.length) {
        return;
      }
      const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      const run = (el) => {
        const match = String(el.textContent).trim().match(/^(\D*)([\d.,]+)(\D*)$/);
        if (!match) {
          return;
        }
        const [, prefix, rawNum, suffix] = match;
        const target = parseFloat(rawNum.replace(/,/g, ''));
        if (Number.isNaN(target)) {
          return;
        }
        if (reduced) {
          el.textContent = `${prefix}${target}${suffix}`;
          return;
        }
        const duration = 1600;
        const start = performance.now();
        const tick = (now) => {
          const p = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - p, 3);
          const value = Math.round(target * eased);
          el.textContent = `${prefix}${value}${suffix}`;
          if (p < 1) {
            requestAnimationFrame(tick);
          }
        };
        requestAnimationFrame(tick);
      };

      if (!('IntersectionObserver' in window)) {
        counters.forEach(run);
        return;
      }
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            run(entry.target);
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.4 });
      counters.forEach((el) => observer.observe(el));
    },
  };

  /** AJAX product filtering: swap results without a full page reload. */
  Drupal.behaviors.albaronProductFilter = {
    attach(context) {
      once('albaron-pfilter', '[data-products-app]', context).forEach((app) => {
        const toAjaxPath = (pathname) => pathname.replace(/\/products$/, '/products-ajax');
        // Authoritative client state (kept in sync as the user clicks), so rapid
        // multi-selects never read a stale server-rendered link.
        let currentSearch = window.location.search;
        let reqId = 0;
        // Mobile filter drawer state — persists across AJAX fragment swaps.
        let filtersOpen = false;
        const mobileFilters = window.matchMedia('(max-width: 900px)');
        let scrollLocked = false;
        let pageScrollTop = 0;

        const syncFiltersOpen = () => {
          const shouldLock = filtersOpen && mobileFilters.matches;
          if (shouldLock !== scrollLocked) {
            if (shouldLock) {
              pageScrollTop = window.scrollY;
              document.body.style.setProperty('--filter-scroll-top', `-${pageScrollTop}px`);
            }
            document.documentElement.classList.toggle('filters-scroll-locked', shouldLock);
            scrollLocked = shouldLock;
            if (!shouldLock) {
              document.body.style.removeProperty('--filter-scroll-top');
              window.scrollTo({ top: pageScrollTop, behavior: 'instant' });
            }
          }
          app.classList.toggle('filters-open', filtersOpen);
          const tgl = app.querySelector('.filters-toggle');
          if (tgl) {
            tgl.setAttribute('aria-expanded', filtersOpen ? 'true' : 'false');
          }
        };

        const onFilterViewportChange = () => {
          if (!mobileFilters.matches) {
            filtersOpen = false;
          }
          syncFiltersOpen();
        };
        if (typeof mobileFilters.addEventListener === 'function') {
          mobileFilters.addEventListener('change', onFilterViewportChange);
        } else {
          mobileFilters.addListener(onFilterViewportChange);
        }

        const apply = (search, push) => {
          currentSearch = search;
          const pathname = window.location.pathname;
          const niceUrl = pathname + search;
          const fetchUrl = toAjaxPath(pathname) + search;
          const myReq = ++reqId;
          app.classList.add('is-loading');
          fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          })
            .then((r) => (r.ok ? r.text() : Promise.reject(r)))
            .then((html) => {
              if (myReq !== reqId) {
                return; // A newer request superseded this one.
              }
              const filterScrollTop = app.querySelector('.filters')?.scrollTop || 0;
              app.innerHTML = html;
              app.classList.remove('is-loading');
              syncFiltersOpen();
              if (filtersOpen) {
                app.querySelector('.filters').scrollTop = filterScrollTop;
              }
              if (push) {
                window.history.pushState({ albaron: true }, '', niceUrl);
              }
              Drupal.attachBehaviors(app);
              // Only scroll to results when the drawer is closed (desktop or
              // after the user dismisses it) to avoid jumping while filtering.
              if (!filtersOpen && window.innerWidth < 900) {
                app.scrollIntoView({ behavior: 'smooth', block: 'start' });
              }
            })
            .catch(() => {
              if (myReq === reqId) {
                window.location.href = pathname + search;
              }
            });
        };

        // Toggle a term id within a facet against the current client state.
        const toggle = (facet, tid) => {
          const params = new URLSearchParams(currentSearch);
          const set = (params.get(facet) || '').split(',').filter(Boolean);
          const i = set.indexOf(tid);
          if (i >= 0) {
            set.splice(i, 1);
          } else {
            set.push(tid);
          }
          if (set.length) {
            params.set(facet, set.join(','));
          } else {
            params.delete(facet);
          }
          const qs = params.toString();
          return qs ? `?${qs}` : '';
        };

        app.addEventListener('click', (e) => {
          // Mobile filter drawer open/close.
          if (e.target.closest('.filters-toggle')) {
            e.preventDefault();
            filtersOpen = true;
            syncFiltersOpen();
            return;
          }
          if (e.target.closest('.filters__close') || e.target.closest('.filters-backdrop')) {
            e.preventDefault();
            filtersOpen = false;
            syncFiltersOpen();
            return;
          }

          const link = e.target.closest('a.js-filter');
          if (!link || !app.contains(link)) {
            return;
          }
          e.preventDefault();
          const facet = link.getAttribute('data-facet');
          const tid = link.getAttribute('data-tid');
          let search;
          if (facet && tid) {
            search = toggle(facet, tid);
          } else {
            // Reset / clear-all links: take the search straight from the href.
            search = new URL(link.href, window.location.origin).search;
          }
          apply(search, true);
        });

        // Close the drawer with Escape.
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && filtersOpen) {
            filtersOpen = false;
            syncFiltersOpen();
          }
        });

        window.addEventListener('popstate', () => {
          if (document.body.contains(app)) {
            apply(window.location.search, false);
          }
        });
      });
    },
  };

  /** Reveal elements with [data-reveal] as they scroll into view. */
  Drupal.behaviors.albaronRevealEls = {
    attach(context) {
      const els = once('albaron-reveal-el', '[data-reveal]', context);
      if (!els.length) {
        return;
      }
      if (!('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
      }
      const io = new IntersectionObserver(
        (entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
      );
      els.forEach((el) => io.observe(el));
    },
  };
})(Drupal, once);