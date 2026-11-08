(() => {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const el = {
    form: $('input-form'), algo: $('algo'), data: $('data'), hint: $('input-hint'), error: $('input-error'), random: $('random'),
    codeTitle: $('code-title'), code: $('code'), stackPanel: $('stack-panel'), stack: $('stack'),
    info: $('info'), bars: $('bars'), aux: $('aux'), note: $('note'), vars: $('vars'),
    first: $('first'), prev: $('prev'), play: $('play'), next: $('next'), last: $('last'),
    speed: $('speed'), scrubber: $('scrubber'), counter: $('counter'),
  };

  const ALGOS = window.ALGOS;
  let trace = null;
  let idx = 0;
  let timer = null;
  let barEls = [];
  let lineEls = [];
  let hasAux = false;
  let range = { min: 0, max: 1 };

  // ---------- Helpers ----------
  const escapeHtml = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const TOKEN = /(\$\w+)|\b(function|array|for|foreach|while|if|else|break|return|as|true|false|void|int)\b|\b(count|intdiv|max|array_fill|array_slice|array_merge)\b|\b(\d+)\b/g;
  function highlight(src) {
    if (src.trim().startsWith('//')) return `<span class="tok-cmt">${escapeHtml(src)}</span>`;
    return escapeHtml(src).replace(TOKEN, (m, v, kw, fn, num) =>
      v ? `<span class="tok-var">${v}</span>`
        : kw ? `<span class="tok-kw">${kw}</span>`
        : fn ? `<span class="tok-fn">${fn}</span>`
        : `<span class="tok-num">${num}</span>`);
  }
  // ptrs: {label: index} → {index: "label, label"}
  function ptrLabels(ptrs, length) {
    const out = {};
    for (const [label, k] of Object.entries(ptrs || {})) {
      if (Number.isInteger(k) && k >= 0 && k < length) out[k] = out[k] ? `${out[k]}, ${label}` : label;
    }
    return out;
  }
  const fmt = (v) => (v === null || v === undefined ? '—' : typeof v === 'boolean' ? String(v) : v);

  // ---------- One-time render per trace ----------
  function renderShell() {
    const m = trace.meta;
    el.codeTitle.textContent = m.file;
    el.code.innerHTML = trace.code.map((src, k) =>
      `<li><span class="hits"></span><span class="ln">${k + 1}</span><span class="src">${highlight(src) || ' '}</span></li>`).join('');
    lineEls = [...el.code.children];

    el.info.innerHTML = `
      <h2 class="info-name">${escapeHtml(m.name)}</h2>
      <p class="info-summary">${escapeHtml(m.summary)}</p>
      <dl class="facts">
        <div><dt>Best</dt><dd>${escapeHtml(m.best)}</dd></div>
        <div><dt>Average</dt><dd>${escapeHtml(m.avg)}</dd></div>
        <div><dt>Worst</dt><dd>${escapeHtml(m.worst)}</dd></div>
        <div><dt>Space</dt><dd>${escapeHtml(m.space)}</dd></div>
        <div><dt>Stable</dt><dd>${escapeHtml(m.stable)}</dd></div>
      </dl>`;

    el.stackPanel.hidden = !m.recursive;
    hasAux = trace.steps.some((s) => s.aux.length > 0);
    el.aux.hidden = !hasAux;

    range = { min: Math.min(...trace.input, 0), max: Math.max(...trace.input) };
    el.bars.innerHTML = trace.input.map(() => '<div class="bar"><span class="val"></span><span class="idx"></span><span class="ptr"></span></div>').join('');
    barEls = [...el.bars.children];

    updateHint(m.id);
    el.algo.value = m.id;
  }

  function updateHint(id) {
    const m = ALGOS[id];
    el.hint.textContent = `2–20 whole numbers between ${m.min} and ${m.max}.`;
  }

  const heightFor = (v) => {
    const span = range.max - range.min || 1;
    return 8 + 92 * ((v - range.min) / span); // percent; floor of 8% keeps the smallest bar visible
  };

  // ---------- Per-step render ----------
  function render() {
    const step = trace.steps[idx];
    const prevStep = trace.steps[idx - 1];
    const writeLike = step.action === 'swap' || step.action === 'write';

    // Code: current line + hit counts so far
    const hits = new Array(trace.code.length).fill(0);
    for (let k = 0; k <= idx; k++) hits[trace.steps[k].line - 1]++;
    lineEls.forEach((li, k) => { li.firstChild.textContent = hits[k] || ''; li.className = ''; });
    const cur = lineEls[step.line - 1];
    cur.classList.add('current');
    if (writeLike) cur.classList.add('swap');
    if (step.action === 'return' || step.action === 'break') cur.classList.add('done');

    // Bars
    const sorted = new Set(step.sorted);
    const focus = new Set(step.focus);
    const labels = ptrLabels(step.ptrs, step.arr.length);
    const [rlo, rhi] = step.range || [0, step.arr.length - 1];
    barEls.forEach((bar, k) => {
      const v = step.arr[k];
      bar.style.height = heightFor(v) + '%';
      bar.querySelector('.val').textContent = v;
      bar.querySelector('.idx').textContent = k;
      bar.querySelector('.ptr').textContent = labels[k] || '';
      bar.className = 'bar';
      if (focus.has(k)) bar.classList.add(writeLike ? 'swap' : 'compare');
      else if (sorted.has(k)) bar.classList.add('sorted');
      if (step.range && (k < rlo || k > rhi)) bar.classList.add('out');
    });
    el.bars.setAttribute('aria-label', 'Array: ' + step.arr.join(', '));

    // Auxiliary arrays / buckets
    if (hasAux) el.aux.innerHTML = step.aux.length ? step.aux.map((a) => renderAux(a, writeLike)).join('') : '<p class="aux-empty">No temporary arrays in this frame.</p>';

    // Call stack
    if (trace.meta.recursive) {
      el.stack.innerHTML = step.stack.length
        ? [...step.stack].reverse().map((f, k) => `<li class="${k === 0 ? 'is-top' : ''}">${escapeHtml(f)}</li>`).join('')
        : '<li class="empty">No active calls</li>';
    }

    // Narration
    el.note.innerHTML = `<span class="line-tag">line ${step.line}</span>${escapeHtml(step.note)}`;

    // Variables + counters
    const rows = Object.entries(step.vars).map(([name, value]) => ({ label: '$' + name, value: fmt(value), prev: prevStep ? fmt(prevStep.vars[name]) : undefined }));
    for (const [name, value] of Object.entries(step.stats)) rows.push({ label: name, value, prev: prevStep?.stats[name], stat: true });
    el.vars.innerHTML = rows.map((r) =>
      `<div class="${r.stat ? 'stat ' : ''}${prevStep && r.prev !== r.value ? 'changed' : ''}"><dt>${escapeHtml(r.label)}</dt><dd>${escapeHtml(r.value)}</dd></div>`).join('');

    // Controls
    const last = trace.steps.length - 1;
    el.scrubber.value = idx;
    el.counter.textContent = `Step ${idx + 1} / ${last + 1}`;
    el.first.disabled = el.prev.disabled = idx === 0;
    el.next.disabled = el.last.disabled = idx === last;
  }

  function renderAux(a, writeLike) {
    const focus = new Set(a.focus);
    const hot = writeLike ? 'swap' : 'compare';
    if (a.kind === 'buckets') {
      return `<div class="aux-row"><div class="aux-label">${escapeHtml(a.label)}</div><div class="buckets">${
        a.values.map((items, d) => `<div class="bucket ${focus.has(d) ? hot : ''}"><div class="bucket-items">${
          items.map((v) => `<span>${v}</span>`).join('')}</div><div class="bucket-digit">${d}</div></div>`).join('')
      }</div></div>`;
    }
    const labels = ptrLabels(a.ptrs, a.values.length);
    return `<div class="aux-row"><div class="aux-label">${escapeHtml(a.label)}</div><div class="cells">${
      a.values.length ? a.values.map((v, k) => `<div class="cell ${focus.has(k) ? hot : ''}"><span class="cv">${v}</span><span class="ci">${k}</span><span class="cp">${labels[k] || ''}</span></div>`).join('')
        : '<span class="aux-empty">empty</span>'
    }</div></div>`;
  }

  // ---------- Playback ----------
  const delay = () => 1100 - Number(el.speed.value) * 100; // 1000ms … 100ms

  function go(k) {
    idx = Math.max(0, Math.min(trace.steps.length - 1, k));
    render();
  }
  function stop() {
    clearInterval(timer);
    timer = null;
    el.play.textContent = 'Play';
  }
  function start() {
    if (idx >= trace.steps.length - 1) go(0);
    el.play.textContent = 'Pause';
    timer = setInterval(() => (idx >= trace.steps.length - 1 ? stop() : go(idx + 1)), delay());
  }
  const toggle = () => (timer ? stop() : start());

  el.play.addEventListener('click', toggle);
  el.first.addEventListener('click', () => { stop(); go(0); });
  el.prev.addEventListener('click', () => { stop(); go(idx - 1); });
  el.next.addEventListener('click', () => { stop(); go(idx + 1); });
  el.last.addEventListener('click', () => { stop(); go(trace.steps.length - 1); });
  el.scrubber.addEventListener('input', () => { stop(); go(Number(el.scrubber.value)); });
  el.speed.addEventListener('input', () => { if (timer) { stop(); start(); } });

  document.addEventListener('keydown', (e) => {
    if (e.target.closest('input, textarea, select')) return;
    if (e.target.closest('button') && (e.key === ' ' || e.key === 'Enter')) return;
    const actions = {
      ' ': toggle,
      ArrowRight: () => { stop(); go(idx + 1); },
      ArrowLeft: () => { stop(); go(idx - 1); },
      Home: () => { stop(); go(0); },
      End: () => { stop(); go(trace.steps.length - 1); },
    };
    if (actions[e.key]) { e.preventDefault(); actions[e.key](); }
  });

  // ---------- Loading ----------
  function load(t) {
    stop();
    trace = t;
    el.scrubber.max = trace.steps.length - 1;
    renderShell();
    go(0);
    const url = new URL(location.href);
    url.searchParams.set('algo', trace.meta.id);
    history.replaceState(null, '', url);
  }

  function showError(msg) {
    el.error.textContent = msg;
    el.data.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }

  async function run() {
    showError('');
    const submit = el.form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const qs = new URLSearchParams({ algo: el.algo.value, data: el.data.value });
      const res = await fetch('trace.php?' + qs);
      const body = await res.json();
      if (!res.ok) throw new Error(body.error || `Server returned ${res.status}.`);
      load(body);
    } catch (err) {
      showError(err instanceof SyntaxError ? 'The server did not return valid JSON. Check the PHP error log.' : err.message);
      el.algo.value = trace.meta.id; // keep the dropdown in sync with what is shown
      updateHint(trace.meta.id);
    } finally {
      submit.disabled = false;
    }
  }

  el.form.addEventListener('submit', (e) => { e.preventDefault(); run(); });
  el.algo.addEventListener('change', () => { updateHint(el.algo.value); run(); });
  el.random.addEventListener('click', () => {
    const m = ALGOS[el.algo.value];
    const lo = Math.max(m.min, 1);
    const hi = m.id === 'radix' ? m.max : Math.min(m.max, 99); // radix: 3-digit values show several passes
    const size = 7 + Math.floor(Math.random() * 6); // 7–12 bars
    el.data.value = Array.from({ length: size }, () => lo + Math.floor(Math.random() * (hi - lo + 1))).join(', ');
    run();
  });

  load(window.INITIAL_TRACE);
})();
