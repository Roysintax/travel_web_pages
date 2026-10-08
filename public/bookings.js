/* Local booking estimate: no persistence or requests containing traveler details. */
const tripPackages = bookingFlow.packages;
const tripForm = document.getElementById('trip-form');
const tripDate = document.getElementById('trip-date');
const tripTravelers = document.getElementById('trip-travelers');
const tripOrigin = document.getElementById('trip-origin');
const tripOriginLabel = document.getElementById('trip-origin-label');
const tripOriginInput = document.getElementById('trip-origin-input');
const money = { format: (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID') };
const today = new Date();
const localDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
tripDate.min = localDate;

// Initialize Airport Searchable Dropdown
let departureAirportSelect = null;
if (typeof window.AirportSelect === 'function' && document.getElementById('airport-combobox')) {
  departureAirportSelect = new window.AirportSelect('airport-combobox', {
    onSelect: () => {
      updateTripSummary();
    }
  });
}

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
  
  const displayOrigin = tripOriginLabel?.value.trim() || tripOriginInput?.value.trim() || tripOrigin?.value.trim() || 'Your departure city';
  document.getElementById('summary-origin').textContent = displayOrigin;
  document.getElementById('summary-unit').textContent = money.format(trip.price);
  document.getElementById('summary-total').textContent = money.format(trip.price * trip.travelers);
}

tripForm.addEventListener('input', updateTripSummary);
tripForm.addEventListener('change', updateTripSummary);
tripForm.addEventListener('submit', (event) => {
  event.preventDefault();

  // Validate departure airport & traveler name
  if (tripOriginInput) {
    const hasOrigin = Boolean(tripOrigin.value.trim() || tripOriginInput.value.trim());
    tripOriginInput.setCustomValidity(hasOrigin ? '' : 'Please search and select a departure airport.');
    if (!tripOrigin.value.trim() && tripOriginInput.value.trim()) {
      tripOrigin.value = tripOriginInput.value.trim();
    }
  }

  const tripNameInput = document.getElementById('trip-name');
  if (tripNameInput) {
    tripNameInput.setCustomValidity(tripNameInput.value.trim() ? '' : 'Please enter this detail.');
  }

  if (!tripForm.reportValidity()) return;

  const draft = {
    package: tripForm.elements.package.value,
    departure: tripDate.value,
    travelers: Number(tripTravelers.value),
    origin: tripOrigin.value.trim() || tripOriginInput.value.trim(),
    origin_label: tripOriginLabel?.value.trim() || tripOriginInput?.value.trim() || tripOrigin.value.trim(),
    name: document.getElementById('trip-name').value.trim(),
    email: document.getElementById('trip-email').value.trim(),
    notes: document.getElementById('trip-notes').value.trim(),
    reviewed: false
  };

  const submitBtn = tripForm.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Saving Trip Plan... <i class="fa-solid fa-spinner fa-spin"></i>';
  }

  fetch('/bookings', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    },
    body: JSON.stringify(draft)
  }).then(async (res) => {
    if (res.ok) {
      const data = await res.json();
      if (data.reference_code) {
        draft.reference_code = data.reference_code;
      }
    }
  }).catch(() => {})
  .finally(() => {
    if (!bookingFlow.save(draft)) {
      document.getElementById('draft-error').hidden = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Review My Trip <i class="fa-solid fa-arrow-right"></i>';
      }
      return;
    }
    window.location.href = window.BOOKING_REVIEW_URL || 'booking-review.html';
  });
});

if (tripOriginInput) {
  tripOriginInput.addEventListener('input', () => tripOriginInput.setCustomValidity(''));
}
const tripNameField = document.getElementById('trip-name');
if (tripNameField) {
  tripNameField.addEventListener('input', () => tripNameField.setCustomValidity(''));
}

const savedDraft = bookingFlow.read();
if (savedDraft) {
  tripForm.elements.package.value = savedDraft.package;
  tripDate.value = savedDraft.departure;
  tripTravelers.value = String(savedDraft.travelers);
  if (tripOrigin) tripOrigin.value = savedDraft.origin || '';
  if (tripOriginLabel) tripOriginLabel.value = savedDraft.origin_label || savedDraft.origin || '';
  if (tripOriginInput) tripOriginInput.value = savedDraft.origin_label || savedDraft.origin || '';
  document.getElementById('trip-name').value = savedDraft.name;
  document.getElementById('trip-email').value = savedDraft.email;
  document.getElementById('trip-notes').value = savedDraft.notes;
}
updateTripSummary();

