import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

function simulator() {
  const window = {};
  runInNewContext(readFileSync(new URL('../../public/js/flight-simulator.js', import.meta.url), 'utf8'), {
    window, document: { addEventListener() {} }, requestAnimationFrame() {}, setTimeout() {},
  });
  const state = Object.create(window.FlightSimulator.prototype);
  const toggle = { checked: true };
  Object.assign(state, {
    container: { querySelector: () => toggle }, followAircraft: true, autoSpin: true,
    globeRotation: { yaw: 1, pitch: 0.2, zoom: 1 }, velocity: { yaw: 0, pitch: 0 },
    lastTimestamp: 0, isPlaying: false, particleOffset: 0, radarAngle: 0, speedMultiplier: 1,
    render() {}, getCurrentPosition: () => ({ lat: 25, lng: 120 }),
  });
  return { state, toggle };
}

test('manual rotation disables aircraft follow and holds the released orientation', () => {
  const { state, toggle } = simulator();
  state.onPointerDown(10, 10);
  state.onPointerMove(80, 30);
  state.onPointerUp();
  const rotation = { ...state.globeRotation };
  for (let i = 1; i <= 600; i++) state.loop(i * 16);
  assert.equal(toggle.checked, false);
  assert.equal(state.followAircraft, false);
  assert.equal(state.autoSpin, false);
  assert.deepEqual(state.globeRotation, rotation);
});

test('explicit focus centers the aircraft and clears previous movement', () => {
  const { state } = simulator();
  state.focusOnCurrentPosition();
  assert.ok(Math.abs(state.globeRotation.yaw + 120 * Math.PI / 180) < 1e-9);
  assert.ok(Math.abs(state.globeRotation.pitch - 25 * Math.PI / 180) < 1e-9);
});

test('an animation timestamp preceding initialization cannot create negative particle indices', () => {
  const { state } = simulator();
  state.lastTimestamp = 100;
  state.loop(90);
  assert.equal(state.particleOffset, 0);
  assert.equal(state.radarAngle, 0);
});
