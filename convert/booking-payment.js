/* Interactive Payment Selector & Simulation for Step 3 (Ready to Explore) */
document.addEventListener('DOMContentLoaded', () => {
  const flowDraft = window.bookingFlow?.read();
  const optionCards = document.querySelectorAll('.payment-option-card');
  const radioInputs = document.querySelectorAll('input[name="payment_method"]');
  const btnOpenPayment = document.getElementById('btn-open-payment');
  const btnLabel = document.getElementById('btn-payment-label');
  const modalBackdrop = document.getElementById('payment-modal');
  const modalClose = document.getElementById('payment-modal-close');
  const modalDoneBtn = document.getElementById('modal-done-btn');
  const displayTotal = document.getElementById('payment-display-total');

  if (!btnOpenPayment || !modalBackdrop) return;

  // Sync total price from draft
  let formattedTotal = 'Rp 0';
  let tripPackage = null;
  if (flowDraft && window.bookingFlow?.packages) {
    tripPackage = window.bookingFlow.packages[flowDraft.package];
    if (tripPackage) {
      formattedTotal = window.bookingFlow.money(tripPackage.price * (flowDraft.travelers || 1));
    }
  } else {
    // fallback from DOM
    const domTotal = document.getElementById('flow-total')?.textContent;
    if (domTotal) formattedTotal = domTotal;
  }

  if (displayTotal) {
    displayTotal.textContent = formattedTotal;
  }

  // Generate a realistic booking code
  const randomSuffix = Math.floor(1000 + Math.random() * 9000);
  const bookingCode = `TRV-${new Date().getFullYear()}-${randomSuffix}`;

  // Update card selection states
  function updateSelection() {
    const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
    const method = selectedRadio ? selectedRadio.value : 'cash';

    optionCards.forEach(card => {
      const radio = card.querySelector('input[type="radio"]');
      card.classList.toggle('is-selected', radio.checked);
    });

    if (btnLabel) {
      if (method === 'cash') {
        btnLabel.textContent = 'Konfirmasi Bayar Tunai di Kantor';
      } else {
        btnLabel.textContent = 'Bayar via Payment Gateway';
      }
    }
  }

  radioInputs.forEach(radio => {
    radio.addEventListener('change', updateSelection);
  });
  updateSelection();

  // Populate Modal based on chosen method
  function populateModal(method) {
    const cashView = document.getElementById('modal-view-cash');
    const gatewayView = document.getElementById('modal-view-gateway');
    const modalTitle = document.getElementById('modal-payment-title');
    const modalSubtitle = document.getElementById('modal-payment-subtitle');

    // Fill common data
    document.querySelectorAll('.js-booking-code').forEach(el => el.textContent = bookingCode);
    document.querySelectorAll('.js-booking-total').forEach(el => el.textContent = formattedTotal);
    document.querySelectorAll('.js-traveler-name').forEach(el => el.textContent = flowDraft?.name || 'Customer');
    document.querySelectorAll('.js-package-name').forEach(el => el.textContent = tripPackage?.name || 'Travel Package');

    if (method === 'cash') {
      if (cashView) cashView.hidden = false;
      if (gatewayView) gatewayView.hidden = true;
      if (modalTitle) modalTitle.textContent = 'Instruksi Pembayaran Tunai (Cash)';
      if (modalSubtitle) modalSubtitle.textContent = 'Silakan simpan kode booking ini dan lakukan pelunasan di kantor kami.';
    } else {
      if (cashView) cashView.hidden = true;
      if (gatewayView) gatewayView.hidden = false;
      if (modalTitle) modalTitle.textContent = 'Payment Gateway Checkout';
      if (modalSubtitle) modalSubtitle.textContent = 'Simulasi antarmuka Payment Gateway (Midtrans / Xendit).';
      resetGatewaySimulator();
    }
  }

  // Open & Close Modal
  function openModal() {
    const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
    const method = selectedRadio ? selectedRadio.value : 'cash';
    populateModal(method);
    modalBackdrop.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modalBackdrop.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  btnOpenPayment.addEventListener('click', openModal);
  modalClose?.addEventListener('click', closeModal);
  modalDoneBtn?.addEventListener('click', closeModal);

  modalBackdrop.addEventListener('click', (e) => {
    if (e.target === modalBackdrop) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalBackdrop.classList.contains('is-open')) {
      closeModal();
    }
  });

  /* Gateway Channel Tabs (QRIS, VA, Credit Card) */
  const gatewayTabs = document.querySelectorAll('.gateway-tab-btn');
  gatewayTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      gatewayTabs.forEach(t => t.classList.remove('is-active'));
      tab.classList.add('is-active');

      const target = tab.dataset.channel;
      document.querySelectorAll('.gateway-channel-panel').forEach(p => {
        p.hidden = p.id !== `channel-${target}`;
      });
    });
  });

  /* Copy VA button */
  const copyVaBtn = document.getElementById('btn-copy-va');
  copyVaBtn?.addEventListener('click', () => {
    const vaNum = document.getElementById('va-number-text')?.textContent;
    if (vaNum) {
      navigator.clipboard?.writeText(vaNum.replace(/\s+/g, ''));
      copyVaBtn.innerHTML = '<i class="fa-solid fa-check text-emerald-600"></i> Tersalin!';
      setTimeout(() => {
        copyVaBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Salin';
      }, 2000);
    }
  });

  /* Simulator Pay Now Button */
  const btnSimulatePay = document.getElementById('btn-simulate-pay');
  const simInitial = document.getElementById('gateway-sim-initial');
  const simSuccess = document.getElementById('gateway-sim-success');

  function resetGatewaySimulator() {
    if (simInitial) simInitial.hidden = false;
    if (simSuccess) simSuccess.hidden = true;
    if (btnSimulatePay) {
      btnSimulatePay.disabled = false;
      btnSimulatePay.innerHTML = '<i class="fa-solid fa-bolt"></i> Simulasikan Bayar Sekarang';
    }
  }

  btnSimulatePay?.addEventListener('click', () => {
    btnSimulatePay.disabled = true;
    btnSimulatePay.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses Verifikasi...';

    setTimeout(() => {
      if (simInitial) simInitial.hidden = true;
      if (simSuccess) simSuccess.hidden = false;
    }, 1200);
  });

  /* Download Voucher Simulation */
  const btnDownloadVoucher = document.getElementById('btn-download-voucher');
  btnDownloadVoucher?.addEventListener('click', () => {
    btnDownloadVoucher.innerHTML = '<i class="fa-solid fa-check"></i> Mengunduh...';
    setTimeout(() => {
      alert(`Invoice reservasi ${bookingCode} untuk ${flowDraft?.name || 'Pelanggan'} telah disiapkan.`);
      btnDownloadVoucher.innerHTML = '<i class="fa-solid fa-file-arrow-down"></i> Unduh Invoice Reservasi (PDF)';
    }, 600);
  });
});
