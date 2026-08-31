/* GoodTripLove — front-end behaviour.
   No framework, no build step. The one rule that matters here: a real YouTube
   player is only created when the visitor asks for it. Everything else on the
   page is a thumbnail. */

(function () {
  'use strict';

  var csrf = document.querySelector('meta[name="csrf-token"]');
  var CSRF = csrf ? csrf.getAttribute('content') : '';


  /* ---------- cookie consent ----------
     Third-party players are never created before the visitor has allowed
     them. Clicking play without consent shows the choice in place, which is
     also a valid moment to consent. */
  var CONSENT_COOKIE = 'gtl_consent';

  function readConsent() {
    var match = document.cookie.match(new RegExp('(?:^|; )' + CONSENT_COOKIE + '=([^;]*)'));
    if (!match) return null;
    try { return JSON.parse(decodeURIComponent(match[1])); } catch (e) { return null; }
  }

  function allows(category) {
    var stored = readConsent();
    return !!(stored && stored.c && stored.c[category]);
  }

  function saveConsent(choices, done) {
    fetch(document.body.dataset.cookieUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ choices: choices })
    }).then(function (r) { return r.json(); })
      .then(function () {
        var banner = document.querySelector('[data-cookie-banner]');
        if (banner) banner.hidden = true;
        if (done) done();
      }).catch(function () { /* the visitor can retry */ });
  }

  var banner = document.querySelector('[data-cookie-banner]');

  if (banner && !readConsent()) banner.hidden = false;

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-cookie-settings]')) {
      e.preventDefault();
      if (banner) {
        banner.hidden = false;
        var panel = banner.querySelector('[data-cookie-choices]');
        if (panel) panel.hidden = false;
        var save = banner.querySelector('[data-cookie-save]');
        if (save) save.hidden = false;
      }
      return;
    }

    if (e.target.closest('[data-cookie-accept]')) {
      saveConsent({ video: true, analytics: true });
    } else if (e.target.closest('[data-cookie-reject]')) {
      saveConsent({ video: false, analytics: false });
    } else if (e.target.closest('[data-cookie-customize]')) {
      var panel = banner.querySelector('[data-cookie-choices]');
      var save = banner.querySelector('[data-cookie-save]');
      if (panel) panel.hidden = false;
      if (save) save.hidden = false;
    } else if (e.target.closest('[data-cookie-save]')) {
      var choices = {};
      banner.querySelectorAll('[data-consent]').forEach(function (input) {
        choices[input.dataset.consent] = input.checked;
      });
      saveConsent(choices);
    }
  });

  function askForVideoConsent(container, onGranted) {
    if (container.querySelector('.consent-gate')) return;

    var gate = document.createElement('div');
    gate.className = 'consent-gate';
    gate.innerHTML =
      '<div><p>' + (document.body.dataset.consentText || '') + '</p>' +
      '<button class="btn btn--primary btn--sm" type="button" data-gate-accept></button>' +
      '<button class="btn btn--ghost btn--sm" type="button" data-gate-close></button></div>';
    gate.querySelector('[data-gate-accept]').textContent = document.body.dataset.consentAccept || 'OK';
    gate.querySelector('[data-gate-close]').textContent = document.body.dataset.consentClose || 'X';

    gate.addEventListener('click', function (ev) {
      if (ev.target.closest('[data-gate-accept]')) {
        var current = readConsent();
        var choices = { video: true, analytics: !!(current && current.c && current.c.analytics) };
        saveConsent(choices, function () { gate.remove(); onGranted(); });
      } else if (ev.target.closest('[data-gate-close]')) {
        gate.remove();
      }
    });

    container.appendChild(gate);
  }

  /* ---------- language menu / burger ---------- */
  document.addEventListener('click', function (e) {
    var langBtn = e.target.closest('.lang__btn');
    var lang = document.querySelector('.lang');

    if (lang) {
      if (langBtn) {
        lang.classList.toggle('is-open');
      } else if (!e.target.closest('.lang__menu')) {
        lang.classList.remove('is-open');
      }
    }

    if (e.target.closest('.burger')) {
      var nav = document.querySelector('.nav');
      if (nav) nav.classList.toggle('is-open');
    }
  });

  /* ---------- lazy player facade ----------
     Replaces the thumbnail with the platform's iframe on click and registers
     the GoodTripLove view at the same time.

     The address comes from data-embed-url, written by the server, so YouTube,
     TikTok, Instagram and Facebook all arrive here the same way and this
     function knows about none of them. Building the URL here was what made the
     player YouTube-only. */
  function mountPlayer(container, videoId, playUrl) {
    var src = container.getAttribute('data-embed-url');

    // No embed address means the platform is one we cannot play. Leaving the
    // thumbnail up is better than an empty black frame.
    if (!src) return;

    var iframe = document.createElement('iframe');
    iframe.src = src;
    iframe.title = container.getAttribute('data-title') || 'GoodTripLove';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    iframe.allowFullscreen = true;
    iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');

    container.innerHTML = '';
    container.appendChild(iframe);
    container.classList.add('is-playing');

    // TikTok and Reels are filmed vertically. The card stays 16/9 so the grid
    // keeps its shape, but once a vertical clip is actually playing the frame
    // follows it — otherwise the video is a narrow strip between two black
    // bars. Capped in CSS so a tall card cannot fill the whole screen.
    var aspect = container.getAttribute('data-aspect');
    if (aspect) container.style.aspectRatio = aspect;

    if (playUrl) {
      fetch(playUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
      }).then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (!data) return;
          var counter = document.querySelector('[data-gtl-views="' + container.dataset.videoId + '"]');
          if (counter && data.gtl_views != null) {
            counter.textContent = new Intl.NumberFormat(document.documentElement.lang).format(data.gtl_views);
          }
        }).catch(function () { /* the counter is not worth an error to the visitor */ });
    }
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-play]');
    if (!trigger) return;

    var container = trigger.closest('.player');
    if (!container || container.classList.contains('is-playing')) return;

    e.preventDefault();

    if (!allows('video')) {
      askForVideoConsent(container, function () {
        mountPlayer(container, container.dataset.videoId, container.dataset.playUrl);
      });
      return;
    }

    mountPlayer(container, container.dataset.videoId, container.dataset.playUrl);
  });

  /* ---------- GoodTripLove TV ---------- */
  var tv = document.querySelector('[data-tv]');

  if (tv) {
    var stage = document.querySelector('[data-tv-stage]');
    var continuous = tv.querySelector('[data-tv-continuous]');

    tv.addEventListener('click', function (e) {
      var item = e.target.closest('.tv-item');
      if (!item || !stage) return;

      tv.querySelectorAll('.tv-item').forEach(function (el) { el.classList.remove('is-current'); });
      item.classList.add('is-current');

      stage.dataset.videoId = item.dataset.videoId;
      stage.dataset.playUrl = item.dataset.playUrl || '';
      // The embed address belongs to the track, not to the stage. Copying it
      // is what makes a TikTok track after a YouTube one play the TikTok.
      stage.dataset.embedUrl = item.dataset.embedUrl || '';
      stage.dataset.aspect = item.dataset.aspect || '';
      stage.classList.remove('is-playing');
      stage.style.aspectRatio = '';

      var title = document.querySelector('[data-tv-title]');
      var loc = document.querySelector('[data-tv-location]');
      if (title) title.textContent = item.dataset.title || '';
      if (loc) loc.textContent = item.dataset.location || '';

      if (!allows('video')) {
        askForVideoConsent(stage, function () {
          mountPlayer(stage, item.dataset.videoId, item.dataset.playUrl);
        });
        return;
      }

      mountPlayer(stage, item.dataset.videoId, item.dataset.playUrl);
    });

    if (continuous) {
      continuous.addEventListener('click', function () {
        continuous.classList.toggle('is-on');
        try {
          localStorage.setItem('gtl_tv_continuous', continuous.classList.contains('is-on') ? '1' : '0');
        } catch (err) { /* private mode */ }
      });

      try {
        if (localStorage.getItem('gtl_tv_continuous') === '0') continuous.classList.remove('is-on');
      } catch (err) { /* private mode */ }
    }
  }

  /* ---------- tabs ---------- */
  document.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-tab]');
    if (!tab) return;

    var group = tab.closest('[data-tabs]');
    if (!group) return;

    group.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.remove('is-active'); });
    tab.classList.add('is-active');

    var scope = group.parentElement;
    scope.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('is-active'); });

    var panel = scope.querySelector('#' + tab.dataset.tab);
    if (panel) panel.classList.add('is-active');
  });

  /* ---------- favourites ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-favorite]');
    if (!btn) return;

    e.preventDefault();

    if (btn.dataset.guest === '1') {
      window.location.href = btn.dataset.loginUrl;
      return;
    }

    fetch(btn.dataset.url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ type: btn.dataset.favorite, id: btn.dataset.id })
    }).then(function (r) { return r.json(); })
      .then(function (data) { btn.classList.toggle('is-on', !!data.favorited); })
      .catch(function () { /* silent */ });
  });

  /* ---------- search suggestions ---------- */
  var searchInput = document.querySelector('[data-suggest]');

  if (searchInput) {
    var box = document.querySelector('[data-suggest-results]');
    var timer = null;

    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      var term = searchInput.value.trim();

      if (term.length < 2) {
        if (box) box.innerHTML = '';
        return;
      }

      timer = setTimeout(function () {
        fetch(searchInput.dataset.suggest + '?q=' + encodeURIComponent(term), {
          headers: { 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); })
          .then(function (data) {
            if (!box) return;
            box.innerHTML = data.items.map(function (item) {
              return '<a href="' + item.url + '"><strong>' + item.label + '</strong>' +
                (item.sub ? ' <span>' + item.sub + '</span>' : '') + '</a>';
            }).join('');
          }).catch(function () { /* silent */ });
      }, 220);
    });
  }

  /* ---------- country -> city dependent select ---------- */
  document.querySelectorAll('[data-cities-for]').forEach(function (select) {
    var target = document.querySelector(select.dataset.citiesTarget);
    if (!target) return;

    select.addEventListener('change', function () {
      if (!select.value) {
        target.innerHTML = '<option value="">—</option>';
        return;
      }

      fetch(select.dataset.citiesFor.replace('__ID__', select.value), {
        headers: { 'Accept': 'application/json' }
      }).then(function (r) { return r.json(); })
        .then(function (data) {
          target.innerHTML = '<option value="">—</option>' + data.cities.map(function (c) {
            return '<option value="' + c.id + '">' + c.name + '</option>';
          }).join('');
        }).catch(function () { /* silent */ });
    });
  });
})();
