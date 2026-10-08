/**
 * Production-Quality Interactive 3D World Globe & Flight Simulator
 * Complies with UI/UX Pro Max Aeronautical Cyber-HUD Design System
 * Features:
 * - Accurate Natural Earth continental landmass dot matrix
 * - Photorealistic 3D spherical atmosphere, rim lighting & lat/long grid lines
 * - Elevated 3D Great-Circle geodesic trajectory with flowing photon particles
 * - Supersonic aircraft vector glyph with illuminated jet contrail & HUD tag
 * - Concentric pulsing airport sonar beacons (Origin & Destination)
 * - Multi-traffic international air corridors (ADS-B live simulation)
 * - 2D Tactical Aeronautical Radar mode with 360° phosphor sweep beam
 * - Smooth inertia physics on 3D drag & orbit navigation
 * - Local Natural Earth country outlines, independent of external map services
 */
(function () {
  'use strict';

  const DEG2RAD = Math.PI / 180;
  const RAD2DEG = 180 / Math.PI;
  const EARTH_RADIUS_KM = 6371;

  function toRadians(deg) { return deg * DEG2RAD; }
  function toDegrees(rad) { return rad * RAD2DEG; }

  /**
   * Great Circle distance in km between two lat/lng coordinates (Haversine formula)
   */
  function calcGreatCircleDistance(lat1, lon1, lat2, lon2) {
    const phi1 = toRadians(lat1);
    const phi2 = toRadians(lat2);
    const deltaPhi = toRadians(lat2 - lat1);
    const deltaLambda = toRadians(lon2 - lon1);

    const a = Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
              Math.cos(phi1) * Math.cos(phi2) *
              Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return EARTH_RADIUS_KM * c;
  }

  /**
   * Calculate initial forward bearing from point A to point B in degrees (0-360)
   */
  function calcBearing(lat1, lon1, lat2, lon2) {
    const y = Math.sin(toRadians(lon2 - lon1)) * Math.cos(toRadians(lat2));
    const x = Math.cos(toRadians(lat1)) * Math.sin(toRadians(lat2)) -
              Math.sin(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.cos(toRadians(lon2 - lon1));
    const b = Math.atan2(y, x);
    return (toDegrees(b) + 360) % 360;
  }

  /**
   * Spherical Linear Interpolation (Slerp) along a Great-Circle route
   */
  function slerpPoint(lat1, lon1, lat2, lon2, fraction) {
    const phi1 = toRadians(lat1);
    const lambda1 = toRadians(lon1);
    const phi2 = toRadians(lat2);
    const lambda2 = toRadians(lon2);

    const cosD = Math.sin(phi1) * Math.sin(phi2) + Math.cos(phi1) * Math.cos(phi2) * Math.cos(lambda2 - lambda1);
    const d = Math.acos(Math.max(-1, Math.min(1, cosD)));

    if (Math.abs(d) < 1e-6) {
      return { lat: lat1, lng: lon1 };
    }

    const sinD = Math.sin(d);
    const a = Math.sin((1 - fraction) * d) / sinD;
    const b = Math.sin(fraction * d) / sinD;

    const x = a * Math.cos(phi1) * Math.cos(lambda1) + b * Math.cos(phi2) * Math.cos(lambda2);
    const y = a * Math.cos(phi1) * Math.sin(lambda1) + b * Math.cos(phi2) * Math.sin(lambda2);
    const z = a * Math.sin(phi1) + b * Math.sin(phi2);

    return {
      lat: toDegrees(Math.atan2(z, Math.sqrt(x * x + y * y))),
      lng: toDegrees(Math.atan2(y, x))
    };
  }

  function generateGreatCirclePoints(start, dest, steps = 140) {
    const points = [];
    for (let i = 0; i <= steps; i++) {
      const t = i / steps;
      points.push(slerpPoint(start.lat, start.lng, dest.lat, dest.lng, t));
    }
    return points;
  }

  // --- FlightSimulator Controller Class ---
  class FlightSimulator {
    constructor(containerId) {
      this.container = document.getElementById(containerId);
      if (!this.container) return;

      this.canvas = this.container.querySelector('#flight-sim-canvas');
      this.ctx = this.canvas.getContext('2d');

      // State
      this.viewMode = 'globe'; // 'globe' or 'map'
      this.isPlaying = true;
      this.speedMultiplier = 1;
      this.progress = 0.28;
      this.followAircraft = false;
      this.showAllTraffic = true;

      // Active Route (Default: Jakarta CGK -> Tokyo NRT)
      this.origin = {
        iata: 'CGK',
        name: 'Soekarno-Hatta International Airport',
        city: 'Jakarta',
        country: 'Indonesia',
        lat: -6.125567,
        lng: 106.655897
      };
      this.destination = {
        iata: 'NRT',
        name: 'Narita International Airport',
        city: 'Tokyo',
        country: 'Japan',
        lat: 35.764722,
        lng: 140.386389
      };
      this.flightCode = 'GA-882';
      this.airline = 'Garuda Indonesia';

      this.routePoints = [];
      this.routeDistanceKm = 0;
      this.trafficFlights = [];

      // Canvas 3D Navigation & Inertia Physics
      this.globeRotation = { yaw: -1.8, pitch: 0.35, zoom: 1.0 };
      this.velocity = { yaw: 0, pitch: 0 };
      this.isDragging = false;
      this.lastMousePos = { x: 0, y: 0 };
      this.autoSpin = false;
      this.worldDots = [];
      this.countryRings = [];

      // Photon particles & radar timing
      this.particleOffset = 0;
      this.radarAngle = 0;

      this.lastTimestamp = performance.now();
      this.animationId = null;

      this.init();
    }

    init() {
      this.updateRouteGeometry();
      this.fetchTrafficCorridors();
      this.loadWorldGeography();
      this.bindDomEvents();
      this.handleResize();
      window.addEventListener('resize', () => this.handleResize());

      // Start 60fps Loop
      this.animationId = requestAnimationFrame((ts) => this.loop(ts));
    }

    async loadWorldGeography() {
      try {
        const response = await fetch(this.canvas.dataset.geographyUrl || '/assets/world-countries.geojson');
        if (!response.ok) throw new Error('Geography unavailable');
        const geography = await response.json();
        const mask = document.createElement('canvas');
        mask.width = 1440;
        mask.height = 720;
        const context = mask.getContext('2d', { willReadFrequently: true });
        context.fillStyle = '#fff';
        geography.features.forEach(feature => {
          const polygons = feature.geometry.type === 'Polygon' ? [feature.geometry.coordinates] : feature.geometry.coordinates;
          polygons.forEach(rings => {
            context.beginPath();
            rings.forEach(ring => {
              this.countryRings.push(ring);
              ring.forEach(([lng, lat], index) => {
                const x = (lng + 180) * 4;
                const y = (90 - lat) * 4;
                if (index === 0) context.moveTo(x, y);
                else context.lineTo(x, y);
              });
              context.closePath();
            });
            context.fill('evenodd');
          });
        });
        const pixels = context.getImageData(0, 0, mask.width, mask.height).data;
        for (let lat = -85; lat <= 85; lat += 1.3) {
          const step = 1.3 / Math.max(0.15, Math.cos(toRadians(lat)));
          for (let lng = -180; lng < 180; lng += step) {
            const x = Math.floor((lng + 180) * 4);
            const y = Math.floor((90 - lat) * 4);
            if (pixels[(y * mask.width + x) * 4 + 3] > 127) this.worldDots.push({ lat, lng });
          }
        }
        this.canvas.removeAttribute('aria-busy');
      } catch (error) {
        this.canvas.removeAttribute('aria-busy');
        const hint = this.container.querySelector('.flight-sim-canvas-hint');
        if (hint) hint.textContent = 'Peta negara belum termuat. Muat ulang halaman untuk mencoba kembali.';
      }
    }

    renderCountryBorders(ctx, project) {
      ctx.save();
      ctx.strokeStyle = 'rgba(148, 218, 246, 0.58)';
      ctx.lineWidth = 0.75;
      ctx.beginPath();
      this.countryRings.forEach(ring => {
        let previous = null;
        for (let i = 1; i < ring.length; i++) {
          const [lng1, lat1] = ring[i - 1];
          const [lng2, lat2] = ring[i];
          if (Math.abs(lng2 - lng1) > 180) { previous = null; continue; }
          const steps = Math.max(1, Math.ceil(Math.max(Math.abs(lng2 - lng1), Math.abs(lat2 - lat1)) / 1.5));
          for (let step = 0; step <= steps; step++) {
            const t = step / steps;
            const point = project(lat1 + (lat2 - lat1) * t, lng1 + (lng2 - lng1) * t);
            if (point.z !== undefined && point.z <= 0) { previous = null; continue; }
            if (previous) ctx.lineTo(point.x, point.y);
            else ctx.moveTo(point.x, point.y);
            previous = point;
          }
        }
      });
      ctx.stroke();
      ctx.restore();
    }

    handleResize() {
      const rect = this.canvas.getBoundingClientRect();
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      this.width = rect.width;
      this.height = rect.height;

      this.canvas.width = Math.round(rect.width * dpr);
      this.canvas.height = Math.round(rect.height * dpr);
      this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    updateRouteGeometry() {
      this.routePoints = generateGreatCirclePoints(this.origin, this.destination, 120);
      this.routeDistanceKm = calcGreatCircleDistance(
        this.origin.lat, this.origin.lng,
        this.destination.lat, this.destination.lng
      );
      this.updateFlightCode();
      this.updateDomFlightInfo();

      if (this.followAircraft) {
        this.focusOnCurrentPosition();
      }
    }

    updateFlightCode() {
      if (this.origin.iata === 'CGK' && this.destination.iata === 'NRT') {
        this.flightCode = 'GA-882';
        this.airline = 'Garuda Indonesia';
      } else if (this.origin.iata === 'CGK' && this.destination.iata === 'JED') {
        this.flightCode = 'SV-819';
        this.airline = 'Saudia Airlines';
      } else if (this.origin.iata === 'SUB' && this.destination.iata === 'SIN') {
        this.flightCode = 'SQ-931';
        this.airline = 'Singapore Airlines';
      } else if (this.origin.iata === 'DPS' && this.destination.iata === 'SYD') {
        this.flightCode = 'QF-44';
        this.airline = 'Qantas Airways';
      } else if (this.origin.iata === 'CGK' && this.destination.iata === 'LHR') {
        this.flightCode = 'QR-958';
        this.airline = 'Qatar Airways (via DOH)';
      } else {
        this.flightCode = `FL-${Math.floor(100 + Math.random() * 899)}`;
        this.airline = 'International Air Express';
      }
    }

    async fetchTrafficCorridors() {
      try {
        const res = await fetch('/api/flights/traffic');
        if (res.ok) {
          const json = await res.json();
          if (json.success && Array.isArray(json.data)) {
            this.trafficFlights = json.data;
          }
        }
      } catch (e) {
        // Fallback default background traffic
        this.trafficFlights = [
          { flight_iata: 'SQ951', origin_coords: { lat: 1.36, lng: 103.99 }, dest_coords: { lat: 51.47, lng: -0.45 }, progress: 0.42 },
          { flight_iata: 'EK358', origin_coords: { lat: 25.25, lng: 55.36 }, dest_coords: { lat: -6.12, lng: 106.65 }, progress: 0.65 },
          { flight_iata: 'JL720', origin_coords: { lat: 35.76, lng: 140.38 }, dest_coords: { lat: 37.61, lng: -122.37 }, progress: 0.18 },
          { flight_iata: 'QF42', origin_coords: { lat: -33.94, lng: 151.17 }, dest_coords: { lat: -8.74, lng: 115.16 }, progress: 0.81 }
        ];
      }
    }

    bindDomEvents() {
      // View Switcher: Globe 3D <-> Map 2D
      const globeBtn = this.container.querySelector('#flight-sim-view-globe');
      const mapBtn = this.container.querySelector('#flight-sim-view-map');

      globeBtn?.addEventListener('click', () => {
        this.viewMode = 'globe';
        globeBtn.classList.add('is-active');
        globeBtn.setAttribute('aria-pressed', 'true');
        mapBtn?.classList.remove('is-active');
        mapBtn?.setAttribute('aria-pressed', 'false');
      });

      mapBtn?.addEventListener('click', () => {
        this.viewMode = 'map';
        mapBtn.classList.add('is-active');
        mapBtn.setAttribute('aria-pressed', 'true');
        globeBtn?.classList.remove('is-active');
        globeBtn?.setAttribute('aria-pressed', 'false');
      });

      // Simulation Play / Pause
      const playBtn = this.container.querySelector('#flight-sim-play-btn');
      playBtn?.addEventListener('click', () => {
        this.isPlaying = !this.isPlaying;
        playBtn.innerHTML = this.isPlaying
          ? '<i class="fa-solid fa-pause" aria-hidden="true"></i> Pause'
          : '<i class="fa-solid fa-play" aria-hidden="true"></i> Resume';
      });

      // Reset
      this.container.querySelector('#flight-sim-reset-btn')?.addEventListener('click', () => {
        this.progress = 0;
        this.updateDomFlightInfo();
      });

      // Simulation Speed Select
      this.container.querySelector('#flight-sim-speed-select')?.addEventListener('change', (e) => {
        this.speedMultiplier = parseFloat(e.target.value) || 1;
      });

      // Follow Aircraft & Traffic Toggles
      const followToggle = this.container.querySelector('#flight-sim-follow-toggle');
      followToggle?.addEventListener('change', (e) => {
        this.followAircraft = e.target.checked;
        if (this.followAircraft) this.autoSpin = false;
      });

      const trafficToggle = this.container.querySelector('#flight-sim-traffic-toggle');
      trafficToggle?.addEventListener('change', (e) => {
        this.showAllTraffic = e.target.checked;
      });

      // Swap Departure & Destination Button
      this.container.querySelector('#flight-sim-swap-btn')?.addEventListener('click', () => {
        const temp = this.origin;
        this.origin = this.destination;
        this.destination = temp;
        this.progress = 0;
        this.syncInputsWithState();
        this.updateRouteGeometry();
      });

      // Preset Route Chips
      this.container.querySelectorAll('.flight-sim-chip').forEach(chip => {
        chip.addEventListener('click', () => {
          const from = chip.getAttribute('data-from');
          const to = chip.getAttribute('data-to');
          if (from && to) this.loadAirportPair(from, to);
        });
      });

      // Autocomplete Search
      this.setupAirportAutocomplete('flight-sim-origin-input', 'flight-sim-origin-dropdown', (airport) => {
        this.origin = airport;
        this.progress = 0;
        this.updateRouteGeometry();
      });
      this.setupAirportAutocomplete('flight-sim-dest-input', 'flight-sim-dest-dropdown', (airport) => {
        this.destination = airport;
        this.progress = 0;
        this.updateRouteGeometry();
      });

      // Mouse & Touch Orbit Controls with Inertia
      this.canvas.addEventListener('mousedown', (e) => this.onPointerDown(e.clientX, e.clientY));
      window.addEventListener('mousemove', (e) => this.onPointerMove(e.clientX, e.clientY));
      window.addEventListener('mouseup', () => this.onPointerUp());

      this.canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        if (e.touches.length > 0) {
          this.onPointerDown(e.touches[0].clientX, e.touches[0].clientY);
        }
      }, { passive: false });
      window.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0 && this.isDragging) {
          this.onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
        }
      }, { passive: true });
      window.addEventListener('touchend', () => this.onPointerUp());
      window.addEventListener('touchcancel', () => this.onPointerUp());
      window.addEventListener('blur', () => this.onPointerUp());
      this.canvas.addEventListener('keydown', (event) => {
        const direction = { ArrowLeft: [-25, 0], ArrowRight: [25, 0], ArrowUp: [0, -25], ArrowDown: [0, 25] }[event.key];
        if (!direction) return;
        event.preventDefault();
        this.onPointerDown(0, 0);
        this.onPointerMove(...direction);
        this.onPointerUp();
      });

      // Mouse Wheel Zoom
      this.canvas.addEventListener('wheel', (e) => {
        e.preventDefault();
        this.globeRotation.zoom = Math.max(0.65, Math.min(2.5, this.globeRotation.zoom + e.deltaY * -0.0012));
      }, { passive: false });

      // Zoom & Recenter Buttons
      this.container.querySelector('#flight-sim-zoom-in')?.addEventListener('click', () => {
        this.globeRotation.zoom = Math.min(2.5, this.globeRotation.zoom + 0.25);
      });
      this.container.querySelector('#flight-sim-zoom-out')?.addEventListener('click', () => {
        this.globeRotation.zoom = Math.max(0.65, this.globeRotation.zoom - 0.25);
      });
      this.container.querySelector('#flight-sim-center-cam')?.addEventListener('click', () => {
        this.focusOnCurrentPosition();
      });
    }

    async loadAirportPair(fromIata, toIata) {
      try {
        const [res1, res2] = await Promise.all([
          fetch(`/api/airports/${fromIata}`),
          fetch(`/api/airports/${toIata}`)
        ]);
        if (res1.ok && res2.ok) {
          const d1 = await res1.json();
          const d2 = await res2.json();
          if (d1.success && d2.success) {
            this.origin = d1.data;
            this.destination = d2.data;
            this.progress = 0;
            this.syncInputsWithState();
            this.updateRouteGeometry();
          }
        }
      } catch (err) {}
    }

    syncInputsWithState() {
      const fromInp = this.container.querySelector('#flight-sim-origin-input');
      const toInp = this.container.querySelector('#flight-sim-dest-input');
      if (fromInp) fromInp.value = `${this.origin.iata} — ${this.origin.name} (${this.origin.city})`;
      if (toInp) toInp.value = `${this.destination.iata} — ${this.destination.name} (${this.destination.city})`;
    }

    setupAirportAutocomplete(inputId, dropdownId, onSelect) {
      const input = this.container.querySelector(`#${inputId}`);
      const dropdown = this.container.querySelector(`#${dropdownId}`);
      if (!input || !dropdown) return;

      let timer = null;
      input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        timer = setTimeout(async () => {
          try {
            const url = q ? `/api/airports/search?q=${encodeURIComponent(q)}` : '/api/airports/popular';
            const res = await fetch(url);
            if (res.ok) {
              const json = await res.json();
              if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                dropdown.innerHTML = '';
                dropdown.hidden = false;
                json.data.slice(0, 8).forEach(airport => {
                  const li = document.createElement('li');
                  li.className = 'flight-sim-dropdown-item';
                  li.innerHTML = `
                    <span class="flight-sim-dropdown-badge">${airport.iata}</span>
                    <div class="flight-sim-dropdown-text">
                      <strong>${airport.name}</strong>
                      <small>${[airport.city, airport.country].filter(Boolean).join(', ')}</small>
                    </div>
                  `;
                  li.addEventListener('click', () => {
                    input.value = `${airport.iata} — ${airport.name} (${airport.city})`;
                    dropdown.hidden = true;
                    onSelect(airport);
                  });
                  dropdown.appendChild(li);
                });
              } else {
                dropdown.hidden = true;
              }
            }
          } catch (e) {
            dropdown.hidden = true;
          }
        }, 220);
      });

      document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.hidden = true;
        }
      });
    }

    onPointerDown(x, y) {
      this.isDragging = true;
      this.autoSpin = false;
      this.followAircraft = false;
      const followToggle = this.container.querySelector('#flight-sim-follow-toggle');
      if (followToggle) followToggle.checked = false;
      this.lastMousePos = { x, y };
      this.velocity = { yaw: 0, pitch: 0 };
    }

    onPointerMove(x, y) {
      if (!this.isDragging) return;
      const dx = x - this.lastMousePos.x;
      const dy = y - this.lastMousePos.y;
      this.lastMousePos = { x, y };

      const moveYaw = dx * 0.0055;
      const movePitch = -dy * 0.0055;

      this.globeRotation.yaw += moveYaw;
      this.globeRotation.pitch = Math.max(-1.15, Math.min(1.15, this.globeRotation.pitch + movePitch));

      // Capture velocity for smooth inertia
      this.velocity.yaw = moveYaw;
      this.velocity.pitch = movePitch;
    }

    onPointerUp() {
      this.isDragging = false;
      this.velocity = { yaw: 0, pitch: 0 };
    }

    focusOnCurrentPosition() {
      const currentPos = this.getCurrentPosition();
      this.globeRotation.yaw = -toRadians(currentPos.lng);
      this.globeRotation.pitch = toRadians(currentPos.lat);
      this.velocity = { yaw: 0, pitch: 0 };
    }

    getCurrentPosition() {
      if (this.routePoints.length === 0) return { lat: 0, lng: 0 };
      const idx = Math.min(this.routePoints.length - 1, Math.max(0, Math.floor(this.progress * (this.routePoints.length - 1))));
      return this.routePoints[idx];
    }

    getCurrentHeading() {
      const idx = Math.min(this.routePoints.length - 2, Math.max(0, Math.floor(this.progress * (this.routePoints.length - 1))));
      const nextIdx = Math.min(this.routePoints.length - 1, idx + 1);
      const p1 = this.routePoints[idx];
      const p2 = this.routePoints[nextIdx];
      return calcBearing(p1.lat, p1.lng, p2.lat, p2.lng);
    }

    loop(timestamp) {
      const deltaSec = Math.max(0, Math.min(0.1, (timestamp - this.lastTimestamp) / 1000));
      this.lastTimestamp = timestamp;

      // Update progress along great circle
      if (this.isPlaying && this.routeDistanceKm > 0) {
        const progressIncrement = (deltaSec / 45) * this.speedMultiplier;
        this.progress = (this.progress + progressIncrement) % 1.0;
        this.updateDomFlightInfo();
      }

      // Particle streaming offset & radar sweep angle
      this.particleOffset = (this.particleOffset + deltaSec * 0.6 * this.speedMultiplier) % 1.0;
      this.radarAngle = (this.radarAngle + deltaSec * 1.8) % (Math.PI * 2);

      // Inertia Physics & Camera Smoothing
      if (!this.isDragging) {
        if (this.followAircraft) {
          const cur = this.getCurrentPosition();
          const targetYaw = -toRadians(cur.lng);
          const targetPitch = toRadians(cur.lat);
          const yawDifference = Math.atan2(Math.sin(targetYaw - this.globeRotation.yaw), Math.cos(targetYaw - this.globeRotation.yaw));
          this.globeRotation.yaw += yawDifference * 0.045;
          this.globeRotation.pitch += (targetPitch - this.globeRotation.pitch) * 0.045;
        } else if (Math.abs(this.velocity.yaw) > 0.0001 || Math.abs(this.velocity.pitch) > 0.0001) {
          this.globeRotation.yaw += this.velocity.yaw;
          this.globeRotation.pitch = Math.max(-1.15, Math.min(1.15, this.globeRotation.pitch + this.velocity.pitch));
          this.velocity.yaw *= 0.94; // damping
          this.velocity.pitch *= 0.94;
        } else if (this.autoSpin) {
          this.globeRotation.yaw += 0.0022;
        }
      }

      this.render();
      this.animationId = requestAnimationFrame((ts) => this.loop(ts));
    }

    updateDomFlightInfo() {
      const heading = Math.round(this.getCurrentHeading());

      let altitudeM = 10800;
      if (this.progress < 0.15) {
        altitudeM = Math.round((this.progress / 0.15) * 10800);
      } else if (this.progress > 0.85) {
        altitudeM = Math.round((1 - (this.progress - 0.85) / 0.15) * 10800);
      } else {
        altitudeM = 10800 + Math.round(Math.sin(this.progress * 18) * 160);
      }

      const speedKmh = altitudeM < 2500 ? 460 : 870 + Math.round(Math.cos(this.progress * 14) * 22);
      const remainingDistanceKm = Math.round(this.routeDistanceKm * (1 - this.progress));
      const hoursLeft = remainingDistanceKm / Math.max(1, speedKmh);
      const etaMins = Math.floor((hoursLeft % 1) * 60);
      const etaHours = Math.floor(hoursLeft);
      const etaString = `${String(etaHours).padStart(2, '0')}:${String(etaMins).padStart(2, '0')} ETA`;

      const hudAlt = this.container.querySelector('#flight-sim-hud-alt');
      const hudSpeed = this.container.querySelector('#flight-sim-hud-speed');
      const hudDist = this.container.querySelector('#flight-sim-hud-dist');
      const hudEta = this.container.querySelector('#flight-sim-hud-eta');
      const barFill = this.container.querySelector('#flight-sim-bar-fill');
      const planeMarker = this.container.querySelector('#flight-sim-plane-marker');
      const percentLabel = this.container.querySelector('#flight-sim-percent-label');
      const pinOrigin = this.container.querySelector('#flight-sim-pin-origin');
      const pinDest = this.container.querySelector('#flight-sim-pin-dest');
      const cardFlight = this.container.querySelector('#flight-sim-card-flight');
      const cardRoute = this.container.querySelector('#flight-sim-card-route');

      if (hudAlt) hudAlt.innerHTML = `${altitudeM.toLocaleString('id-ID')}<small>m (FL${Math.round(altitudeM * 0.0328)})</small>`;
      if (hudSpeed) hudSpeed.innerHTML = `${speedKmh}<small>km/h</small>`;
      if (hudDist) hudDist.innerHTML = `${Math.round(this.routeDistanceKm).toLocaleString('id-ID')}<small>km</small>`;
      if (hudEta) hudEta.textContent = etaString;

      const pct = Math.round(this.progress * 100);
      if (barFill) barFill.style.width = `${pct}%`;
      if (planeMarker) {
        planeMarker.style.left = `${pct}%`;
        planeMarker.style.transform = `translate(-50%, -50%) rotate(${heading - 90}deg)`;
      }
      if (percentLabel) percentLabel.textContent = `${pct}%`;

      if (pinOrigin) pinOrigin.textContent = this.origin.iata;
      if (pinDest) pinDest.textContent = this.destination.iata;
      if (cardFlight) cardFlight.textContent = `${this.flightCode} (${this.airline})`;
      if (cardRoute) cardRoute.textContent = `${this.origin.iata} → ${this.destination.iata}`;
    }

    render() {
      const ctx = this.ctx;
      const w = this.width;
      const h = this.height;

      ctx.clearRect(0, 0, w, h);

      if (this.viewMode === 'globe') {
        this.render3DGlobe(ctx, w, h);
      } else {
        this.render2DRadarMap(ctx, w, h);
      }
    }

    /**
     * Render State-of-the-Art 3D Aeronautical Globe
     */
    render3DGlobe(ctx, w, h) {
      const cx = w / 2;
      const cy = h / 2;
      const radius = Math.min(w, h) * 0.40 * this.globeRotation.zoom;

      // 1. Outer Deep Atmospheric Corona & Ray Halo
      const outerAura = ctx.createRadialGradient(cx, cy, radius * 0.95, cx, cy, radius * 1.25);
      outerAura.addColorStop(0, 'rgba(14, 165, 233, 0.30)');
      outerAura.addColorStop(0.4, 'rgba(14, 165, 233, 0.12)');
      outerAura.addColorStop(0.8, 'rgba(2, 132, 199, 0.04)');
      outerAura.addColorStop(1, 'rgba(2, 132, 199, 0)');
      ctx.fillStyle = outerAura;
      ctx.beginPath();
      ctx.arc(cx, cy, radius * 1.25, 0, Math.PI * 2);
      ctx.fill();

      // 2. Spherical Planetary Body (Deep Oceanic Shading)
      const oceanGrad = ctx.createRadialGradient(
        cx - radius * 0.35, cy - radius * 0.35, radius * 0.08,
        cx, cy, radius
      );
      oceanGrad.addColorStop(0, '#0f2444');
      oceanGrad.addColorStop(0.45, '#09162c');
      oceanGrad.addColorStop(0.85, '#050c1b');
      oceanGrad.addColorStop(1, '#020610');
      ctx.fillStyle = oceanGrad;
      ctx.beginPath();
      ctx.arc(cx, cy, radius, 0, Math.PI * 2);
      ctx.fill();

      // 3. Inner Fresnel Rim Glow (Atmospheric Boundary)
      const fresnelGrad = ctx.createRadialGradient(cx, cy, radius * 0.88, cx, cy, radius);
      fresnelGrad.addColorStop(0, 'rgba(56, 189, 248, 0)');
      fresnelGrad.addColorStop(0.7, 'rgba(56, 189, 248, 0.18)');
      fresnelGrad.addColorStop(1, 'rgba(14, 165, 233, 0.55)');
      ctx.fillStyle = fresnelGrad;
      ctx.beginPath();
      ctx.arc(cx, cy, radius, 0, Math.PI * 2);
      ctx.fill();

      ctx.strokeStyle = 'rgba(56, 189, 248, 0.4)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      const yaw = this.globeRotation.yaw;
      const pitch = this.globeRotation.pitch;

      // 3D Spherical Coordinate Projection with Atmospheric Curvature
      const project = (lat, lng, altScale = 1.0) => {
        const phi = toRadians(lat);
        const theta = toRadians(lng) + yaw;

        let x = Math.cos(phi) * Math.sin(theta);
        let y = Math.sin(phi);
        let z = Math.cos(phi) * Math.cos(theta);

        const cosP = Math.cos(pitch);
        const sinP = Math.sin(pitch);
        const y2 = y * cosP - z * sinP;
        const z2 = y * sinP + z * cosP;

        const isVisible = z2 > -0.08;
        return {
          x: cx + x * radius * altScale,
          y: cy - y2 * radius * altScale,
          z: z2,
          isVisible
        };
      };

      // 4. Spherical Latitude & Longitude Coordinate Grid Rings
      this.renderSphericalGrid(ctx, project);

      // 5. High-Density Continental Landmass Dot Matrix (Accurate Geography)
      for (let i = 0; i < this.worldDots.length; i++) {
        const dot = this.worldDots[i];
        const p = project(dot.lat, dot.lng);
        if (p.z > 0) {
          // Perspective depth brightness and dot size
          const depthAlpha = Math.max(0.12, Math.min(0.95, (p.z + 0.15) * 1.1));
          const dotSize = Math.max(0.4, Math.min(1.5, radius * 0.0042) * Math.sqrt(p.z));

          ctx.fillStyle = `rgba(56, 189, 248, ${depthAlpha.toFixed(2)})`;
          ctx.beginPath();
          ctx.arc(p.x, p.y, dotSize, 0, Math.PI * 2);
          ctx.fill();
        }
      }

      this.renderCountryBorders(ctx, project);

      // 6. Background Multi-Air Traffic Corridors
      if (this.showAllTraffic && this.trafficFlights.length > 0) {
        this.trafficFlights.forEach((flight) => {
          this.renderAirTrafficArc(ctx, project, flight);
        });
      }

      // 7. Active Great-Circle Flight Trajectory (Elevated 3D Arc)
      if (this.routePoints.length > 1) {
        this.renderElevatedFlightArc(ctx, project);
      }

      // 8. Concentric Pulsing Sonar Beacons for Origin & Destination
      this.renderAirportSonarPin(ctx, project(this.origin.lat, this.origin.lng, 1.0), this.origin.iata, 'DEP', '#0ea5e9');
      this.renderAirportSonarPin(ctx, project(this.destination.lat, this.destination.lng, 1.0), this.destination.iata, 'ARR', '#f59e0b');

      // 9. 3D Supersonic Aircraft Vector Glyph & HUD Telemetry Tag
      const curPos = this.getCurrentPosition();
      const currentElevation = 1.0 + Math.sin(this.progress * Math.PI) * 0.18;
      const planeProj = project(curPos.lat, curPos.lng, currentElevation);

      if (planeProj.isVisible) {
        const heading = this.getCurrentHeading();
        this.renderSupersonicJet(ctx, planeProj.x, planeProj.y, heading, planeProj.z);
        this.renderAircraftHudTag(ctx, planeProj.x, planeProj.y);
      }
    }

    /**
     * Render Spherical Latitude / Longitude Curvature Grid Lines
     */
    renderSphericalGrid(ctx, project) {
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.12)';
      ctx.lineWidth = 1;

      // Parallels: Equator, ±30°, ±60°
      const parallels = [-60, -30, 0, 30, 60];
      parallels.forEach(lat => {
        ctx.beginPath();
        let started = false;
        for (let lng = -180; lng <= 180; lng += 6) {
          const p = project(lat, lng, 1.0);
          if (p.isVisible) {
            if (!started) { ctx.moveTo(p.x, p.y); started = true; }
            else { ctx.lineTo(p.x, p.y); }
          } else {
            started = false;
          }
        }
        ctx.stroke();
      });

      // Meridians: Every 45°
      for (let lng = -180; lng < 180; lng += 45) {
        ctx.beginPath();
        let started = false;
        for (let lat = -80; lat <= 80; lat += 6) {
          const p = project(lat, lng, 1.0);
          if (p.isVisible) {
            if (!started) { ctx.moveTo(p.x, p.y); started = true; }
            else { ctx.lineTo(p.x, p.y); }
          } else {
            started = false;
          }
        }
        ctx.stroke();
      }
    }

    /**
     * Render Elevated 3D Geodesic Flight Arc with Flowing Photons
     */
    renderElevatedFlightArc(ctx, project) {
      const pts = this.routePoints;
      const count = pts.length;

      // Step A: Projected Screen Coordinates with Parabolic Elevation
      const projPoints = [];
      for (let i = 0; i < count; i++) {
        const t = i / (count - 1);
        const arcElevation = 1.0 + Math.sin(t * Math.PI) * 0.18;
        projPoints.push(project(pts[i].lat, pts[i].lng, arcElevation));
      }

      // Step B: Multi-layer Luminous Laser Trajectory Line
      // Outer Glow Stroke
      ctx.beginPath();
      let started = false;
      for (let i = 0; i < count; i++) {
        const p = projPoints[i];
        if (p.isVisible) {
          if (!started) { ctx.moveTo(p.x, p.y); started = true; }
          else { ctx.lineTo(p.x, p.y); }
        } else {
          started = false;
        }
      }
      ctx.strokeStyle = 'rgba(14, 165, 233, 0.28)';
      ctx.lineWidth = 6;
      ctx.stroke();

      // Sharp Core Stroke
      ctx.beginPath();
      started = false;
      for (let i = 0; i < count; i++) {
        const p = projPoints[i];
        if (p.isVisible) {
          if (!started) { ctx.moveTo(p.x, p.y); started = true; }
          else { ctx.lineTo(p.x, p.y); }
        } else {
          started = false;
        }
      }
      ctx.strokeStyle = '#38bdf8';
      ctx.lineWidth = 2;
      ctx.stroke();

      // Step C: Contrail Trail Behind Current Aircraft Position
      const currentIdx = Math.floor(this.progress * (count - 1));
      const trailLength = Math.max(3, Math.floor(count * 0.12));
      const trailStart = Math.max(0, currentIdx - trailLength);

      if (currentIdx > trailStart) {
        ctx.beginPath();
        let trailStarted = false;
        for (let i = trailStart; i <= currentIdx; i++) {
          const p = projPoints[i];
          if (p.isVisible) {
            if (!trailStarted) { ctx.moveTo(p.x, p.y); trailStarted = true; }
            else { ctx.lineTo(p.x, p.y); }
          }
        }
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.75)';
        ctx.lineWidth = 2.8;
        ctx.stroke();
      }

      // Step D: Stream of Animated Flowing Photon Light Particles
      const numParticles = 8;
      for (let k = 0; k < numParticles; k++) {
        const pFraction = (this.particleOffset + k / numParticles) % 1.0;
        const pIdx = Math.min(count - 1, Math.floor(pFraction * (count - 1)));
        const pt = projPoints[pIdx];

        if (pt.isVisible) {
          // Flowing Photon Head
          ctx.fillStyle = '#ffffff';
          ctx.shadowColor = '#38bdf8';
          ctx.shadowBlur = 8;
          ctx.beginPath();
          ctx.arc(pt.x, pt.y, 2.5, 0, Math.PI * 2);
          ctx.fill();
          ctx.shadowBlur = 0;
        }
      }
    }

    /**
     * Concentric Pulsing Sonar Beacon Pin (DEP / ARR)
     */
    renderAirportSonarPin(ctx, pos, iata, role, color) {
      if (!pos.isVisible) return;
      const now = performance.now();
      const pulse1 = (now * 0.0018) % 1;
      const pulse2 = (now * 0.0018 + 0.5) % 1;

      // Sonar Ring 1
      ctx.strokeStyle = color;
      ctx.lineWidth = 1.2;
      ctx.globalAlpha = 1 - pulse1;
      ctx.beginPath();
      ctx.arc(pos.x, pos.y, 4 + pulse1 * 18, 0, Math.PI * 2);
      ctx.stroke();

      // Sonar Ring 2
      ctx.globalAlpha = 1 - pulse2;
      ctx.beginPath();
      ctx.arc(pos.x, pos.y, 4 + pulse2 * 18, 0, Math.PI * 2);
      ctx.stroke();
      ctx.globalAlpha = 1.0;

      // Solid Glowing Center Dot
      ctx.fillStyle = '#ffffff';
      ctx.shadowColor = color;
      ctx.shadowBlur = 10;
      ctx.beginPath();
      ctx.arc(pos.x, pos.y, 4.5, 0, Math.PI * 2);
      ctx.fill();
      ctx.shadowBlur = 0;

      // Holographic Glass Badge
      const badgeW = 48;
      const badgeH = 20;
      const bx = pos.x - badgeW / 2;
      const by = pos.y - 28;

      ctx.fillStyle = 'rgba(7, 15, 30, 0.9)';
      ctx.strokeStyle = color;
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.roundRect(bx, by, badgeW, badgeH, 6);
      ctx.fill();
      ctx.stroke();

      // Badge Text
      ctx.fillStyle = color;
      ctx.font = 'bold 8.5px "Fira Code", monospace';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(`${role} • ${iata}`, pos.x, by + badgeH / 2);
    }

    /**
     * High-Precision Supersonic Aircraft Vector Glyph
     */
    renderSupersonicJet(ctx, x, y, headingDeg, depthZ) {
      ctx.save();
      ctx.translate(x, y);
      ctx.rotate(toRadians(headingDeg));

      const size = Math.max(16, 24 * (depthZ + 0.4));

      // Jet Engine Afterburner Thrust Glow
      const thrustGrad = ctx.createRadialGradient(0, size * 0.35, 1, 0, size * 0.45, size * 0.25);
      thrustGrad.addColorStop(0, '#ffffff');
      thrustGrad.addColorStop(0.3, '#38bdf8');
      thrustGrad.addColorStop(1, 'rgba(14, 165, 233, 0)');
      ctx.fillStyle = thrustGrad;
      ctx.beginPath();
      ctx.arc(0, size * 0.38, size * 0.22, 0, Math.PI * 2);
      ctx.fill();

      // Sleek Aircraft Body Silhouette
      ctx.fillStyle = '#ffffff';
      ctx.shadowColor = '#0ea5e9';
      ctx.shadowBlur = 10;
      ctx.beginPath();
      // Nose cone
      ctx.moveTo(0, -size * 0.5);
      // Right wing
      ctx.lineTo(size * 0.12, -size * 0.1);
      ctx.lineTo(size * 0.46, size * 0.22);
      ctx.lineTo(size * 0.14, size * 0.18);
      // Right tailfin
      ctx.lineTo(size * 0.16, size * 0.44);
      // Exhaust center
      ctx.lineTo(0, size * 0.36);
      // Left tailfin
      ctx.lineTo(-size * 0.16, size * 0.44);
      // Left wing
      ctx.lineTo(-size * 0.14, size * 0.18);
      ctx.lineTo(-size * 0.46, size * 0.22);
      ctx.lineTo(-size * 0.12, -size * 0.1);
      ctx.closePath();
      ctx.fill();
      ctx.shadowBlur = 0;

      // Canopy Center Stripe
      ctx.strokeStyle = '#0284c7';
      ctx.lineWidth = 1.2;
      ctx.beginPath();
      ctx.moveTo(0, -size * 0.38);
      ctx.lineTo(0, size * 0.2);
      ctx.stroke();

      ctx.restore();
    }

    /**
     * Floating Cockpit Telemetry HUD Tag Next to Plane
     */
    renderAircraftHudTag(ctx, x, y) {
      const tagX = x + 24;
      const tagY = y - 26;

      // Fine aeronautical bracket pointer line
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.6)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(x + 4, y - 4);
      ctx.lineTo(tagX - 4, tagY + 10);
      ctx.lineTo(tagX, tagY + 10);
      ctx.stroke();

      // HUD Tag Box
      ctx.fillStyle = 'rgba(8, 16, 32, 0.9)';
      ctx.strokeStyle = '#38bdf8';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.roundRect(tagX, tagY, 82, 22, 5);
      ctx.fill();
      ctx.stroke();

      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 9px "Fira Code", monospace';
      ctx.textAlign = 'left';
      ctx.textBaseline = 'middle';
      ctx.fillText(this.flightCode, tagX + 6, tagY + 7);

      ctx.fillStyle = '#38bdf8';
      ctx.font = '8px "Fira Code", monospace';
      ctx.fillText('FL350 • 870K', tagX + 6, tagY + 16);
    }

    /**
     * Background Air Traffic Arcs & Transponders
     */
    renderAirTrafficArc(ctx, project, flight) {
      const p1 = project(flight.origin_coords.lat, flight.origin_coords.lng);
      const p2 = project(flight.dest_coords.lat, flight.dest_coords.lng);
      if (!p1.isVisible && !p2.isVisible) return;

      // Subtle airway corridor line
      ctx.strokeStyle = 'rgba(148, 163, 184, 0.2)';
      ctx.lineWidth = 1;
      ctx.setLineDash([3, 4]);
      ctx.beginPath();
      ctx.moveTo(p1.x, p1.y);
      ctx.lineTo(p2.x, p2.y);
      ctx.stroke();
      ctx.setLineDash([]);

      // Moving transponder blip
      const t = (flight.progress + (this.progress * 0.35)) % 1.0;
      const lat = flight.origin_coords.lat + (flight.dest_coords.lat - flight.origin_coords.lat) * t;
      const lng = flight.origin_coords.lng + (flight.dest_coords.lng - flight.origin_coords.lng) * t;
      const proj = project(lat, lng, 1.05);

      if (proj.isVisible) {
        ctx.fillStyle = '#94a3b8';
        ctx.beginPath();
        ctx.arc(proj.x, proj.y, 2.5, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#94a3b8';
        ctx.font = '7.5px "Fira Code", monospace';
        ctx.textAlign = 'left';
        ctx.fillText(flight.flight_iata, proj.x + 5, proj.y - 2);
      }
    }

    /**
     * Render 2D Tactical Aeronautical Radar Display
     */
    render2DRadarMap(ctx, w, h) {
      ctx.fillStyle = '#060d19';
      ctx.fillRect(0, 0, w, h);

      // Radar Range Rings (Concentric nautical circles)
      const cx = w / 2;
      const cy = h / 2;
      const maxR = Math.min(w, h) * 0.46;

      ctx.strokeStyle = 'rgba(56, 189, 248, 0.15)';
      ctx.lineWidth = 1;
      [0.25, 0.5, 0.75, 1.0].forEach(factor => {
        ctx.beginPath();
        ctx.arc(cx, cy, maxR * factor, 0, Math.PI * 2);
        ctx.stroke();
      });

      // Crosshairs & Compass Degrees
      ctx.beginPath();
      ctx.moveTo(cx, 0); ctx.lineTo(cx, h);
      ctx.moveTo(0, cy); ctx.lineTo(w, cy);
      ctx.stroke();

      const project2D = (lat, lng) => ({
        x: ((lng + 180) / 360) * w,
        y: ((90 - lat) / 180) * h,
        isVisible: true
      });

      // Continental Land Dots in 2D Radar Matrix
      ctx.fillStyle = 'rgba(56, 189, 248, 0.35)';
      for (let i = 0; i < this.worldDots.length; i++) {
        const dot = this.worldDots[i];
        const p = project2D(dot.lat, dot.lng);
        ctx.beginPath();
        ctx.arc(p.x, p.y, 1.6, 0, Math.PI * 2);
        ctx.fill();
      }

      // Background Air Traffic in 2D
      if (this.showAllTraffic && this.trafficFlights.length > 0) {
        this.trafficFlights.forEach((flight) => {
          const p1 = project2D(flight.origin_coords.lat, flight.origin_coords.lng);
          const p2 = project2D(flight.dest_coords.lat, flight.dest_coords.lng);

          ctx.strokeStyle = 'rgba(148, 163, 184, 0.22)';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.stroke();

          const t = (flight.progress + (this.progress * 0.35)) % 1.0;
          const px = p1.x + (p2.x - p1.x) * t;
          const py = p1.y + (p2.y - p1.y) * t;
          ctx.fillStyle = '#94a3b8';
          ctx.beginPath();
          ctx.arc(px, py, 2.5, 0, Math.PI * 2);
          ctx.fill();
          ctx.font = '7.5px "Fira Code", monospace';
          ctx.fillText(flight.flight_iata, px + 5, py - 3);
        });
      }

      // Active Flight Trajectory in 2D
      if (this.routePoints.length > 1) {
        ctx.beginPath();
        for (let i = 0; i < this.routePoints.length; i++) {
          const p = project2D(this.routePoints[i].lat, this.routePoints[i].lng);
          if (i === 0) ctx.moveTo(p.x, p.y);
          else ctx.lineTo(p.x, p.y);
        }
        ctx.strokeStyle = '#38bdf8';
        ctx.lineWidth = 2.2;
        ctx.stroke();
      }

      // Airport Pins in 2D
      this.renderAirportSonarPin(ctx, project2D(this.origin.lat, this.origin.lng), this.origin.iata, 'DEP', '#0ea5e9');
      this.renderAirportSonarPin(ctx, project2D(this.destination.lat, this.destination.lng), this.destination.iata, 'ARR', '#f59e0b');

      // Aircraft in 2D
      const curPos = this.getCurrentPosition();
      const plane = project2D(curPos.lat, curPos.lng);
      this.renderSupersonicJet(ctx, plane.x, plane.y, this.getCurrentHeading(), 1.0);
      this.renderAircraftHudTag(ctx, plane.x, plane.y);

      // Rotating 360° Tactical Radar Sweep Beam with Phosphor Fade
      ctx.save();
      ctx.translate(cx, cy);
      const sweepAngle = this.radarAngle;
      const sweepGrad = ctx.createRadialGradient(0, 0, 0, 0, 0, maxR);
      sweepGrad.addColorStop(0, 'rgba(56, 189, 248, 0.4)');
      sweepGrad.addColorStop(1, 'rgba(14, 165, 233, 0.05)');

      ctx.fillStyle = sweepGrad;
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.arc(0, 0, maxR, sweepAngle - 0.5, sweepAngle);
      ctx.closePath();
      ctx.fill();

      // Sharp Leading Radar Beam Line
      ctx.strokeStyle = '#38bdf8';
      ctx.lineWidth = 1.5;
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.lineTo(Math.cos(sweepAngle) * maxR, Math.sin(sweepAngle) * maxR);
      ctx.stroke();
      ctx.restore();
    }
  }

  // Auto initialize on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('flight-simulator-root')) {
      window.flightSimulator = new FlightSimulator('flight-simulator-root');
    }
  });

  window.FlightSimulator = FlightSimulator;
})();
