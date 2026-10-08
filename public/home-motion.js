/* Scroll-scrub the supplied 300-frame master sequence with a bounded bitmap cache. */
(() => {
  const journey = document.querySelector('.cinematic-journey');
  const stage = document.querySelector('.journey-stage');
  const canvas = document.getElementById('journey-canvas');
  const context = canvas.getContext('2d');
  const header = document.querySelector('.site-header');
  const title = document.getElementById('journey-title');
  const chapter = document.getElementById('journey-chapter');
  const description = document.getElementById('journey-description');
  const cta = document.getElementById('journey-cta');
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const frames = new Map();
  const pending = new Map();
  const total = 300;
  let desired = 1;
  let previous = 1;
  let progress = 0;
  let scheduled = false;
  let failed = false;
  let activeChapter = -1;
  let width = 0;
  let height = 0;

  const chapters = [
    ['01 / THE JOURNEY BEGINS', 'Every journey<br>starts with a view.'],
    ['02 / A NEW PERSPECTIVE', 'Look beyond.'],
    ['03 / ABOVE THE CLOUDS', 'A world awaits.'],
    ['04 / YOUR NEXT CHAPTER', 'The world is closer<br>than you think.']
  ];

  // Focus tracks the actual window center in this master, rather than a generic crop.
  function focusAt(value) {
    const points = [[0, 0.52], [0.4, 0.49], [0.7, 0.5], [1, 0.52]];
    const end = points.findIndex((point) => point[0] >= value);
    if (end <= 0) return points[0][1];
    const a = points[end - 1];
    const b = points[end];
    const t = (value - a[0]) / (b[0] - a[0]);
    const eased = t * t * (3 - 2 * t);
    return a[1] + (b[1] - a[1]) * eased;
  }

  function drawFrame() {
    if (!context || !frames.size) return;
    const nearest = [...frames.keys()].reduce((a, b) =>
      Math.abs(b - desired) < Math.abs(a - desired) ? b : a);
    const bitmap = frames.get(nearest);
    const scale = Math.max(width / bitmap.width, height / bitmap.height);
    const drawWidth = bitmap.width * scale;
    const drawHeight = bitmap.height * scale;
    const focus = width < 768 ? focusAt(progress) : 0.5;
    const x = Math.max(width - drawWidth, Math.min(0, width / 2 - drawWidth * focus));
    context.clearRect(0, 0, width, height);
    context.drawImage(bitmap, x, (height - drawHeight) / 2, drawWidth, drawHeight);
    canvas.classList.add('has-frame');
    canvas.dataset.frame = String(nearest);
  }

  function trimCache() {
    while (frames.size > 32) {
      const farthest = [...frames.keys()].reduce((a, b) =>
        Math.abs(b - desired) > Math.abs(a - desired) ? b : a);
      frames.get(farthest).close();
      frames.delete(farthest);
    }
  }

  function requestNearbyFrames() {
    if (motion.matches || failed || !context) return;
    const direction = desired >= previous ? 1 : -1;
    previous = desired;
    const wanted = [desired];
    for (let offset = 1; offset <= 8; offset += 1) {
      wanted.push(desired + offset * direction, desired - offset * direction);
    }
    const valid = wanted.filter((frame) => frame >= 1 && frame <= total);
    pending.forEach((controller, frame) => {
      if (!valid.includes(frame)) controller.abort();
    });

    for (const frame of valid) {
      if (pending.size >= 4) break;
      if (frames.has(frame) || pending.has(frame)) continue;
      const controller = new AbortController();
      pending.set(frame, controller);
      const name = String(frame).padStart(3, '0');
      fetch(`assets/journey/ezgif-frame-${name}.jpg?v=cabin-1`, { signal: controller.signal })
        .then((response) => {
          if (!response.ok) throw new Error('Frame unavailable');
          return response.blob();
        })
        .then((blob) => createImageBitmap(blob))
        .then((bitmap) => {
          if (controller.signal.aborted || motion.matches) { bitmap.close(); return; }
          frames.set(frame, bitmap);
          trimCache();
          drawFrame();
        })
        .catch((error) => {
          if (error.name !== 'AbortError') {
            failed = true;
            journey.classList.add('is-static');
            canvas.classList.remove('has-frame');
            pending.forEach((request) => request.abort());
          }
        })
        .finally(() => {
          pending.delete(frame);
          if (!failed && !motion.matches) requestNearbyFrames();
        });
    }
  }

  function update() {
    scheduled = false;
    const bounds = journey.getBoundingClientRect();
    progress = Math.max(0, Math.min(1, -bounds.top / Math.max(1, journey.offsetHeight - stage.offsetHeight)));
    desired = Math.max(1, Math.min(total, 1 + Math.round(progress * (total - 1))));
    header.classList.toggle('header--solid', bounds.bottom <= header.offsetHeight);
    stage.style.setProperty('--journey-progress', progress);

    const nextChapter = motion.matches || failed ? 0 : progress < 0.2 ? 0 : progress < 0.5 ? 1 : progress < 0.78 ? 2 : 3;
    if (nextChapter !== activeChapter) {
      activeChapter = nextChapter;
      chapter.textContent = chapters[nextChapter][0];
      title.innerHTML = chapters[nextChapter][1];
      description.hidden = nextChapter === 1 || nextChapter === 2;
      cta.hidden = description.hidden;
    }
    if (bounds.bottom > 0 && bounds.top < innerHeight && !motion.matches && !failed) {
      drawFrame();
      requestNearbyFrames();
    }
  }

  function scheduleUpdate() {
    if (!scheduled) { scheduled = true; requestAnimationFrame(update); }
  }

  function resize() {
    const dpr = Math.min(devicePixelRatio || 1, 2);
    width = stage.clientWidth;
    height = stage.clientHeight;
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    context?.setTransform(dpr, 0, 0, dpr, 0, 0);
    scheduleUpdate();
  }

  function configureMotion() {
    pending.forEach((request) => request.abort());
    frames.forEach((bitmap) => bitmap.close());
    frames.clear();
    canvas.classList.remove('has-frame');
    journey.classList.toggle('is-static', motion.matches || failed || !context);
    activeChapter = -1;
    resize();
  }

  // Add a booking action to the mobile menu without altering other pages.
  const bookingItem = document.createElement('li');
  bookingItem.innerHTML = '<a class="nav-booking" href="#booking">Plan your journey ↗</a>';
  header.querySelector('.nav-list').append(bookingItem);

  const revealCards = document.querySelectorAll('.home-page .package, .home-page .plan, .home-page .step');
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        entry.target.classList.toggle('is-visible', entry.isIntersecting && !motion.matches);
      });
    }, { threshold: 0.15 });
    revealCards.forEach((card) => { card.classList.add('home-reveal'); observer.observe(card); });
  }

  window.addEventListener('scroll', scheduleUpdate, { passive: true });
  window.addEventListener('resize', resize);
  window.addEventListener('orientationchange', resize);
  window.addEventListener('hashchange', scheduleUpdate);
  window.addEventListener('pageshow', scheduleUpdate);
  motion.addEventListener('change', configureMotion);
  configureMotion();
})();
