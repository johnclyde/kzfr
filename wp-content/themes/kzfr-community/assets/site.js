(() => {
  'use strict';
  const audio = document.querySelector('#live-audio');
  const status = document.querySelector('#player-status');
  const menu = document.querySelector('.menu-toggle');
  let activeController;
  const designChoices = [...document.querySelectorAll('[data-design-choice]')];
  function currentDesign() { return document.documentElement.dataset.design || 'original'; }
  function applyDesign(design) {
    const selected = designChoices.find(link => link.dataset.designChoice === design) || designChoices[0];
    document.documentElement.dataset.design = selected.dataset.designChoice;
    document.querySelector('.design-description').textContent = selected.title;
    document.querySelectorAll('a[href]:not([data-design-choice])').forEach(link => {
      if (link.getAttribute('href').startsWith('#')) return;
      const next = new URL(link.href, location.href);
      if (next.origin !== location.origin || /\/wp-(admin|login|json)/i.test(next.pathname) || link.closest('#wpadminbar')) return;
      next.searchParams.set('design', selected.dataset.designChoice);
      link.href = next.href;
    });
    designChoices.forEach(link => {
      if (link === selected) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
      const choiceUrl = new URL(location.href); choiceUrl.searchParams.set('design',link.dataset.designChoice);
      link.href = choiceUrl.href;
    });
  }
  function withDesign(url) {
    if (!url.searchParams.has('design')) url.searchParams.set('design',currentDesign());
    return url;
  }
  const dock = document.querySelector('.player-dock');
  const sizeDock = () => document.documentElement.style.setProperty('--player-height', `${dock.getBoundingClientRect().height}px`);
  if ('ResizeObserver' in window) new ResizeObserver(sizeDock).observe(dock);
  sizeDock();
  const recordingControls = document.querySelector('#recording-controls');
  const recordingToggle = document.querySelector('#recording-toggle');
  const volume = document.querySelector('#recording-volume');
  const playerTitle = document.querySelector('#player-title');
  const fallback = document.querySelector('.player-fallback');
  let mode = 'live';
  function syncPlayer() {
    const playing = !audio.paused;
    document.querySelectorAll('[data-listen]').forEach(button => {
      const active = playing && mode === 'live';
      button.innerHTML = active ? '<span aria-hidden="true">Ⅱ</span> Pause radio' : '<span aria-hidden="true">▶</span> Listen live';
      button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-recording]').forEach(button => {
      const active = mode === 'recording' && playing && button.dataset.recording === audio.src;
      button.innerHTML = active ? '<span aria-hidden="true">Ⅱ</span> Pause' : '<span aria-hidden="true">▶</span> Play';
      button.setAttribute('aria-label', `${active ? 'Pause' : 'Play'} ${button.dataset.recordingTitle}`);
      button.setAttribute('aria-pressed', String(active));
    });
    recordingToggle.textContent = playing ? 'Pause recording' : 'Play recording';
    recordingToggle.setAttribute('aria-pressed', String(playing));
    document.body.classList.toggle('is-playing', playing);
  }
  async function playAudio() {
    try { await audio.play(); }
    catch (_) { status.textContent = 'Playback unavailable. Use the recording page or official player.'; }
    syncPlayer();
  }
  async function listen() {
    if (mode === 'live' && !audio.paused) { audio.pause(); return; }
    const stream = audio.dataset.stream || 'https://listen.kzfr.org/stream';
    if (mode !== 'live' || !audio.src) audio.src = stream;
    mode = 'live'; audio.hidden = false; audio.controls = true;
    recordingControls.hidden = true;
    playerTitle.textContent = 'KZFR live';
    audio.setAttribute('aria-label','KZFR live stream');
    fallback.href = 'https://www.kzfr.org/listen'; fallback.textContent = 'Official player';
    status.textContent = 'Connecting to KZFR…';
    await playAudio();
  }
  async function playRecording(button) {
    const same = mode === 'recording' && audio.src === button.dataset.recording;
    if (same && !audio.paused) { audio.pause(); return; }
    mode = 'recording';
    if (!same) audio.src = button.dataset.recording;
    const noSeek = button.dataset.disableSeek === 'true';
    audio.hidden = noSeek; audio.controls = !noSeek;
    recordingControls.hidden = !noSeek;
    volume.value = String(audio.volume);
    playerTitle.textContent = button.dataset.recordingTitle;
    audio.setAttribute('aria-label',button.dataset.recordingTitle);
    fallback.href = button.dataset.recordingSource; fallback.textContent = 'Recording page';
    status.textContent = 'Loading recording…';
    await playAudio();
  }
  recordingToggle.addEventListener('click', () => { if(audio.paused) playAudio(); else audio.pause(); });
  volume.addEventListener('input', () => { audio.volume = Number(volume.value); });
  audio.addEventListener('play', syncPlayer);
  audio.addEventListener('pause', () => { status.textContent = 'Paused'; syncPlayer(); });
  audio.addEventListener('ended', () => { status.textContent = 'Recording finished'; syncPlayer(); });
  audio.addEventListener('error', () => { status.textContent = 'Audio unavailable. Try the official player.'; syncPlayer(); });
  audio.addEventListener('waiting', () => { status.textContent = 'Buffering…'; });
  audio.addEventListener('playing', () => { status.textContent = mode === 'live' ? 'Live from KZFR · 90.1 FM' : 'Recorded broadcast'; });
  menu.addEventListener('click', () => {
    const expanded = menu.getAttribute('aria-expanded') !== 'true';
    menu.setAttribute('aria-expanded', String(expanded));
    document.querySelector('#primary-navigation').classList.toggle('is-open', expanded);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menu.getAttribute('aria-expanded') === 'true') {
      menu.setAttribute('aria-expanded','false'); document.querySelector('#primary-navigation').classList.remove('is-open'); menu.focus();
    }
  });
  // Keep the radio running during internal navigation; ordinary links remain a complete fallback.
  async function navigate(url, push = true) {
    if (push) withDesign(url);
    if (activeController) activeController.abort();
    const controller = new AbortController(); activeController = controller;
    document.querySelector('#main').setAttribute('aria-busy', 'true');
    try {
      const response = await fetch(url.href, {signal: controller.signal, headers: {'X-KZFR-Navigation':'1'}});
      if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) throw new Error('Navigation failed');
      const page = new DOMParser().parseFromString(await response.text(), 'text/html');
      const main = page.querySelector('#main');
      if (!main) throw new Error('Page unavailable');
      document.querySelector('#main').replaceWith(main);
      document.title = page.title;
      const nextNav = page.querySelector('#primary-navigation');
      if (nextNav) document.querySelector('#primary-navigation').replaceWith(nextNav);
      menu.setAttribute('aria-expanded','false');
      if (push) history.pushState({}, '', url.href);
      applyDesign(url.searchParams.get('design') || 'original');
      main.removeAttribute('aria-busy');
      const heading = main.querySelector('h1');
      if (heading) { heading.setAttribute('tabindex','-1'); heading.focus({preventScroll:true}); }
      else main.focus({preventScroll:true});
      window.scrollTo({top:0, behavior:'instant'});
      syncPlayer();
    } catch (error) {
      if (error.name !== 'AbortError') window.location.assign(url.href);
    } finally { if(activeController === controller) activeController = null; }
  }
  document.addEventListener('click', event => {
    const designLink = event.target.closest('[data-design-choice]');
    if (designLink && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
      event.preventDefault();
      const nextUrl = new URL(location.href); nextUrl.searchParams.set('design',designLink.dataset.designChoice);
      navigate(nextUrl);
      return;
    }
    const recording = event.target.closest('[data-recording]');
    if (recording) { event.preventDefault(); playRecording(recording); return; }
    const button = event.target.closest('[data-listen]');
    if (button) { event.preventDefault(); listen(); return; }
    const link = event.target.closest('a');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.download || link.closest('#wpadminbar')) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || url.hash || /\/wp-(admin|login|json)|\.(xml|pdf|zip|ics)$/i.test(url.pathname)) return;
    if (!document.querySelector('#wpadminbar')) { event.preventDefault(); navigate(url); }
  });
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('.filter-bar') || document.querySelector('#wpadminbar')) return;
    event.preventDefault();
    const url = new URL(location.href); url.search = new URLSearchParams(new FormData(form)).toString();
    navigate(withDesign(url));
  });
  window.addEventListener('popstate', () => navigate(new URL(location.href), false));
  applyDesign(currentDesign());
  syncPlayer();
})();
