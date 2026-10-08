/* A tab-local draft connects the three preview pages. No data is sent to a server. */
window.bookingFlow = (() => {
  const key = 'travel.trip-preview.v1';
  const packages = window.BOOKING_PACKAGES || {
    greece: { name: 'Greek Islands Escape', country: 'Greece', days: 6, nights: 5, price: 19500000 },
    maldives: { name: 'Maldives Paradise', country: 'Maldives', days: 5, nights: 4, price: 24500000 },
    canada: { name: 'Canadian Rockies', country: 'Canada', days: 7, nights: 6, price: 27500000 },
    japan: { name: 'Japan Discovery', country: 'Japan', days: 8, nights: 7, price: 33500000 }
  };
  function read() {
    try {
      const draft = JSON.parse(sessionStorage.getItem(key));
      if (!draft || !Object.hasOwn(packages, draft.package) ||
          !Number.isInteger(draft.travelers) || draft.travelers < 1 || draft.travelers > 6 ||
          !/^\d{4}-\d{2}-\d{2}$/.test(draft.departure) ||
          !Number.isFinite(new Date(`${draft.departure}T12:00:00`).getTime()) ||
          !['origin', 'name', 'email', 'notes'].every(field => typeof draft[field] === 'string') ||
          !draft.origin.trim() || !draft.name.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(draft.email) ||
          draft.origin.length > 100 || draft.name.length > 100 || draft.email.length > 254 || draft.notes.length > 500) return null;
      return draft;
    } catch { return null; }
  }
  function save(draft) {
    try { sessionStorage.setItem(key, JSON.stringify(draft)); return true; }
    catch { return false; }
  }
  function clear() { try { sessionStorage.removeItem(key); } catch {} }
  const money = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
  const date = value => new Date(`${value}T12:00:00`).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
  return { packages, read, save, clear, money, date };
})();
