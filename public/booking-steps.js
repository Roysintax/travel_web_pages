/* Render the review and ready pages using validated, tab-local data. */
const flowDraft = bookingFlow.read();
const flowStep = Number(document.body.dataset.bookingStep);
const flowContent = document.getElementById('flow-content');

function renderDetailList(id, values) {
  const list = document.getElementById(id);
  values.forEach(([label, value]) => {
    const row = document.createElement('div');
    const term = document.createElement('dt');
    const detail = document.createElement('dd');
    term.textContent = label;
    detail.textContent = value;
    row.append(term, detail);
    list.append(row);
  });
}

if (!flowDraft) {
  document.getElementById('flow-empty').hidden = false;
} else if (flowStep === 3 && flowDraft.reviewed !== true) {
  window.location.replace(window.BOOKING_REVIEW_URL || 'booking-review.html');
} else {
  flowContent.hidden = false;
  const trip = bookingFlow.packages[flowDraft.package];
  const image = document.getElementById('flow-image');
  image.src = `assets/${flowDraft.package}.png`;
  image.alt = trip.name;
  document.getElementById('flow-package').textContent = trip.name;
  document.getElementById('flow-duration').textContent = `${trip.days} days / ${trip.nights} nights · ${trip.country}`;
  renderDetailList('flow-trip-details', [
    ['Departure', bookingFlow.date(flowDraft.departure)],
    ['Departing from', flowDraft.origin_label || flowDraft.origin],
    ['Travelers', `${flowDraft.travelers} adult${flowDraft.travelers === 1 ? '' : 's'}`],
    ['Package / person', bookingFlow.money(trip.price)]
  ]);
  renderDetailList('flow-traveler-details', [['Full name', flowDraft.name], ['Email address', flowDraft.email]]);
  document.getElementById('flow-total').textContent = bookingFlow.money(trip.price * flowDraft.travelers);
  if (flowDraft.notes) {
    document.getElementById('flow-notes-section').hidden = false;
    document.getElementById('flow-notes').textContent = flowDraft.notes;
  }
}

document.getElementById('continue-preview')?.addEventListener('submit', (event) => {
  event.preventDefault();
  if (!flowDraft || !event.target.reportValidity()) return;
  if (!bookingFlow.save({ ...flowDraft, reviewed: true })) {
    document.getElementById('flow-error').hidden = false;
    return;
  }
  window.location.href = window.BOOKING_READY_URL || 'booking-ready.html';
});

document.getElementById('clear-preview')?.addEventListener('click', () => {
  bookingFlow.clear();
  window.location.href = window.BOOKING_PLAN_URL || 'bookings.html';
});

