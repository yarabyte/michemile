(function () {
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  var backdrop = document.querySelector('[data-nav-backdrop]');

  function setNavOpen(open) {
    if (!toggle || !nav) return;
    nav.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('is-nav-open', open);
    if (header) header.classList.toggle('is-nav-open', open);
    if (backdrop) {
      backdrop.hidden = !open;
      backdrop.classList.toggle('is-visible', open);
    }
  }

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      setNavOpen(!nav.classList.contains('open'));
    });
    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        setNavOpen(false);
      });
    });
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        setNavOpen(false);
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setNavOpen(false);
    });
    window.addEventListener('resize', function () {
      if (window.matchMedia('(min-width: 981px)').matches) setNavOpen(false);
    });
  }

  document.querySelectorAll('[data-reveal]').forEach(function (el, i) {
    if (reduce) {
      el.classList.add('is-in');
      return;
    }
    el.style.transitionDelay = ((i % 5) * 70) + 'ms';
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          el.classList.add('is-in');
          io.disconnect();
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    io.observe(el);
  });

  var filters = document.querySelector('[data-filters]');
  if (filters) {
    var buttons = filters.querySelectorAll('[data-filter]');
    var cards = document.querySelectorAll('.room-card[data-category]');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var value = btn.getAttribute('data-filter') || 'all';
        buttons.forEach(function (b) {
          var active = b === btn;
          b.classList.toggle('is-active', active);
          b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        cards.forEach(function (card) {
          var show = value === 'all' || card.getAttribute('data-category') === value;
          card.hidden = !show;
        });
      });
    });
  }

  var galleryMain = document.querySelector('[data-gallery-main]');
  if (galleryMain) {
    var thumbs = document.querySelectorAll('[data-gallery-thumb]');
    thumbs.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var src = btn.getAttribute('data-gallery-thumb');
        if (!src) return;
        galleryMain.setAttribute('src', src);
        thumbs.forEach(function (other) {
          var active = other === btn;
          other.classList.toggle('is-active', active);
          other.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
      });
    });
  }

  var hero = document.querySelector('[data-hero-slider]');
  if (hero) {
    var slides = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-slide]'));
    var dots = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-dot]'));
    var prev = hero.querySelector('[data-hero-prev]');
    var next = hero.querySelector('[data-hero-next]');
    var index = 0;
    var timer = null;
    var delay = parseInt(hero.getAttribute('data-autoplay') || '0', 10);

    function goTo(i) {
      if (!slides.length) return;
      index = (i + slides.length) % slides.length;
      slides.forEach(function (slide, n) {
        slide.classList.toggle('is-active', n === index);
      });
      dots.forEach(function (dot, n) {
        var active = n === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
    }

    function start() {
      if (reduce || !delay || slides.length < 2) return;
      stop();
      timer = window.setInterval(function () {
        goTo(index + 1);
      }, delay);
    }

    function stop() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    if (prev) prev.addEventListener('click', function () { goTo(index - 1); start(); });
    if (next) next.addEventListener('click', function () { goTo(index + 1); start(); });
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var i = parseInt(dot.getAttribute('data-hero-dot') || '0', 10);
        goTo(i);
        start();
      });
    });
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);
    start();
  }
})();
