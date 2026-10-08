import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/packages.js', import.meta.url), 'utf8');

function renderCatalog() {
  const elements = new Map();
  const element = id => {
    if (!elements.has(id)) elements.set(id, {
      innerHTML: '', textContent: '', classList: { toggle() {} }, focus() {},
    });
    return elements.get(id);
  };
  const attack = '\"><img src=x onerror=alert(1)>';
  const pkg = {
    id: attack, title: attack, destination: attack, region: 'Europe',
    category: 'beach', badge: attack, badgeClass: attack,
    image: attack, alt: attack, days: 6, nights: 5, price: 100,
    originalPrice: null, rating: 5, desc: attack, features: [attack],
    itinerary: [{ day: attack, title: attack, desc: attack }],
    included: [attack], hotel: { name: attack, stars: attack, perks: attack },
  };
  runInNewContext(source + '\nrenderPackages(); openItineraryModal(PACKAGES_DATA[0].id);', {
    window: { PACKAGES_DATA: [pkg] },
    localStorage: { getItem: () => null },
    document: {
      getElementById: element, querySelectorAll: () => [], addEventListener() {},
      body: { style: {} },
    },
  });
  return elements;
}

test('catalog cards and modal escape database text and attributes', () => {
  const elements = renderCatalog();
  for (const id of ['packages-grid', 'modal-location', 'modal-timeline', 'modal-included-list', 'modal-hotel-content']) {
    const html = elements.get(id).innerHTML;
    assert.ok(!html.includes('<img src=x onerror=alert(1)>'), `${id} renders active markup`);
    assert.ok(html.includes('&lt;img'), `${id} lost the catalog text`);
  }
});
