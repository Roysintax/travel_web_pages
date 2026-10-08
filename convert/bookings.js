/* Local booking estimate: no persistence or requests containing traveler details. */
const tripPackages = bookingFlow.packages;
const tripForm = document.getElementById('trip-form');
const tripDate = document.getElementById('trip-date');
const tripTravelers = document.getElementById('trip-travelers');
const tripOrigin = document.getElementById('trip-origin');
const money = { format: (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID') };
const today = new Date();
const localDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
tripDate.min = localDate;

function tripSelection() {
  const key = tripForm.elements.package.value;
  return { key, ...tripPackages[key], travelers: Number(tripTravelers.value) };
}

function formatTripDate() {
  if (!tripDate.value) return 'Choose a date';
  return new Date(`${tripDate.value}T12:00:00`).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}

function updateTripSummary() {
  const trip = tripSelection();
  document.getElementById('summary-title').textContent = trip.name;
  document.getElementById('summary-duration').textContent = `${trip.days} days / ${trip.nights} nights · ${trip.country}`;
  const image = document.getElementById('summary-image');
  const source = `assets/${trip.key}.png`;
  if (image.getAttribute('src') !== source) image.setAttribute('src', source);
  image.alt = trip.name;
  document.getElementById('summary-date').textContent = formatTripDate();
  document.getElementById('summary-travelers').textContent = `${trip.travelers} adult${trip.travelers === 1 ? '' : 's'}`;
  document.getElementById('summary-origin').textContent = tripOrigin.value.trim() || 'Your departure city';
  document.getElementById('summary-unit').textContent = money.format(trip.price);
  document.getElementById('summary-total').textContent = money.format(trip.price * trip.travelers);
}

tripForm.addEventListener('input', updateTripSummary);
tripForm.addEventListener('change', updateTripSummary);
tripForm.addEventListener('submit', (event) => {
  event.preventDefault();
  // Native validation handles required fields, email, and past departure dates.
  for (const id of ['trip-origin', 'trip-name']) {
    const input = document.getElementById(id);
    input.setCustomValidity(input.value.trim() ? '' : 'Please enter this detail.');
  }
  if (!tripForm.reportValidity()) return;

  const draft = {
    package: tripForm.elements.package.value,
    departure: tripDate.value,
    travelers: Number(tripTravelers.value),
    origin: tripOrigin.value.trim(),
    name: document.getElementById('trip-name').value.trim(),
    email: document.getElementById('trip-email').value.trim(),
    notes: document.getElementById('trip-notes').value.trim(),
    reviewed: false
  };
  if (!bookingFlow.save(draft)) {
    document.getElementById('draft-error').hidden = false;
    return;
  }
  window.location.href = 'booking-review.html';
});

['trip-origin', 'trip-name'].forEach((id) => {
  document.getElementById(id).addEventListener('input', (event) => event.target.setCustomValidity(''));
});
const savedDraft = bookingFlow.read();
if (savedDraft) {
  tripForm.elements.package.value = savedDraft.package;
  tripDate.value = savedDraft.departure;
  tripTravelers.value = String(savedDraft.travelers);
  tripOrigin.value = savedDraft.origin;
  document.getElementById('trip-name').value = savedDraft.name;
  document.getElementById('trip-email').value = savedDraft.email;
  document.getElementById('trip-notes').value = savedDraft.notes;
}
updateTripSummary();
