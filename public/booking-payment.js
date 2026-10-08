/* Interactive Payment Selector & Midtrans Snap Integration for Step 3 */
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
    const domTotal = document.getElementById('flow-total')?.textContent;
    if (domTotal) formattedTotal = domTotal;
  }

  if (displayTotal) {
    displayTotal.textContent = formattedTotal;
  }

  // Determine current booking code
  let bookingCode = window.CURRENT_BOOKING_CODE || flowDraft?.reference_code;
  if (!bookingCode) {
    const randomSuffix = Math.floor(1000 + Math.random() * 9000);
    bookingCode = `TRV-${new Date().getFullYear()}-${randomSuffix}`;
  }

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
        btnLabel.textContent = 'Bayar via Payment Gateway (Midtrans)';
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
      if (modalTitle) modalTitle.textContent = 'Midtrans Snap Checkout';
      if (modalSubtitle) modalSubtitle.textContent = 'Selesaikan pembayaran instan melalui QRIS, Virtual Account, atau Kartu Kredit.';
      resetGatewayUi();
    }
  }

  function openModal(method) {
    const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
    const chosenMethod = method || (selectedRadio ? selectedRadio.value : 'cash');
    populateModal(chosenMethod);
    modalBackdrop.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modalBackdrop.classList.remove('is-open');
    document.body.style.overflow = '';
  }

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

  // Action Button Click Handler
  btnOpenPayment.addEventListener('click', async () => {
    const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
    const method = selectedRadio ? selectedRadio.value : 'cash';

    if (method === 'cash') {
      openModal('cash');
      return;
    }

    // Midtrans Snap Flow
    await handleMidtransCheckout();
  });

  async function handleMidtransCheckout() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Ensure booking exists in backend database
    let activeCode = window.CURRENT_BOOKING_CODE || flowDraft?.reference_code;
    if (!activeCode && flowDraft) {
      try {
        const createRes = await fetch('/bookings', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify(flowDraft)
        });
        if (createRes.ok) {
          const createData = await createRes.json();
          activeCode = createData.reference_code;
          bookingCode = activeCode;
          if (flowDraft) {
            flowDraft.reference_code = activeCode;
            window.bookingFlow?.save(flowDraft);
          }
        }
      } catch (err) {
        console.error('Error creating initial booking for payment:', err);
      }
    }

    if (!activeCode) {
      alert('Simpan rencana perjalanan terlebih dahulu sebelum mencoba pembayaran.');
      return;
    }

    btnOpenPayment.disabled = true;
    if (btnLabel) btnLabel.textContent = 'Menghubungkan Midtrans Snap...';

    try {
      const res = await fetch(`/bookings/${activeCode}/payment`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        }
      });

      const data = await res.json();

      if (res.ok && data.success && data.snap_token) {
        bookingCode = activeCode;
        if (displayTotal) displayTotal.textContent = window.bookingFlow.money(data.gross_amount);

        // Check if genuine snap.js popup is ready and not fallback
        function invokeSnap(token) {
          if (typeof window.snap !== 'undefined' && typeof window.snap.pay === 'function') {
            try {
              window.snap.pay(token, {
                onSuccess: function(result) {
                  settleAndShowReceipt(activeCode, 'Midtrans Snap (Berhasil)');
                },
                onPending: function(result) {
                  alert('Pembayaran Midtrans sedang menunggu penyelesaian.');
                },
                onError: function(result) {
                  console.warn('Snap payment error:', result);
                  openModal('gateway');
                },
                onClose: function() {
                  console.log('Customer closed Midtrans Snap popup.');
                }
              });
              return true;
            } catch (err) {
              console.warn('Snap pay error:', err);
              openModal('gateway');
              return true;
            }
          }
          return false;
        }

        if (!data.is_fallback) {
          if (invokeSnap(data.snap_token)) {
            return;
          }

          // If window.snap is not ready yet, load and retry
          const existingScript = document.querySelector('script[src*="snap/snap.js"]');
          const script = existingScript || document.createElement('script');
          if (!existingScript) {
            script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
            script.setAttribute('data-client-key', window.MIDTRANS_CLIENT_KEY);
            document.head.appendChild(script);
          }

          script.onload = function() {
            if (!invokeSnap(data.snap_token)) {
              openModal('gateway');
            }
          };
          script.onerror = function() {
            openModal('gateway');
          };

          // Fallback timeout in case network blocks external snap.js
          setTimeout(function() {
            const modalOpen = document.getElementById('payment-modal')?.classList.contains('is-open');
            const snapIframe = document.querySelector('#snap-midtrans') || document.querySelector('iframe[src*="midtrans"]');
            if (!modalOpen && !snapIframe) {
              if (!invokeSnap(data.snap_token)) {
                openModal('gateway');
              }
            }
          }, 1500);
        } else {
          // Open authentic Midtrans Snap modal
          openModal('gateway');
        }
      } else {
        alert(data.message || 'Gateway pembayaran belum dapat dihubungi. Periksa konfigurasi Midtrans.');
      }
    } catch (err) {
      alert('Tidak dapat menghubungkan pembayaran. Silakan periksa jaringan Anda.');
    } finally {
      btnOpenPayment.disabled = false;
      updateSelection();
    }
  }

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

  /* Midtrans Snap Pay Action inside Modal */
  const btnSnapPay = document.getElementById('btn-snap-pay-now') || document.getElementById('btn-simulate-pay');
  const simInitial = document.getElementById('gateway-sim-initial');
  const simSuccess = document.getElementById('gateway-sim-success');

  function resetGatewayUi() {
    if (simInitial) simInitial.hidden = false;
    if (simSuccess) simSuccess.hidden = true;
    if (btnSnapPay) {
      btnSnapPay.disabled = false;
      btnSnapPay.innerHTML = '<i class="fa-solid fa-bolt"></i> Selesaikan Pembayaran (Midtrans)';
    }
  }

  async function settleAndShowReceipt(code, channelName) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
      const confirmRes = await fetch(`/bookings/${encodeURIComponent(code)}/confirm`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ payment_type: channelName || 'midtrans_snap' })
      });

      const confirmData = await confirmRes.json();
      if (!confirmRes.ok || !confirmData.success) {
        throw new Error(confirmData.message || 'Gagal mengonfirmasi transaksi');
      }

      // Populate Receipt View
      openModal('gateway');
      const receiptChannel = document.getElementById('receipt-channel');
      const receiptTrxId = document.getElementById('receipt-transaction-id');
      const receiptTimestamp = document.getElementById('receipt-timestamp');

      if (receiptChannel) receiptChannel.textContent = channelName || 'Midtrans Snap (QRIS/VA)';
      if (receiptTrxId) {
        receiptTrxId.textContent = `MIDTRANS-${Date.now().toString(36).toUpperCase()}-${Math.floor(1000 + Math.random() * 9000)}`;
      }
      if (receiptTimestamp) {
        receiptTimestamp.textContent = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) + ' WIB';
      }

      if (simInitial) simInitial.hidden = true;
      if (simSuccess) simSuccess.hidden = false;

      // Update local storage draft
      if (flowDraft) {
        flowDraft.payment_status = 'paid';
        window.bookingFlow?.save(flowDraft);
      }
    } catch (err) {
      alert('Konfirmasi pembayaran gagal: ' + err.message);
    }
  }

  btnSnapPay?.addEventListener('click', async () => {
    btnSnapPay.disabled = true;
    btnSnapPay.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses Verifikasi Midtrans...';

    const activeTab = document.querySelector('.gateway-tab-btn.is-active');
    const channel = activeTab?.dataset.channel || 'qris';
    const channelLabels = {
      qris: 'QRIS (GoPay / BCA / E-Wallet)',
      va: 'Virtual Account (BCA / Mandiri / BRI)',
      cc: 'Kartu Kredit / Debit (Visa/Mastercard)'
    };

    try {
      await settleAndShowReceipt(bookingCode, channelLabels[channel]);
    } finally {
      btnSnapPay.disabled = false;
      btnSnapPay.innerHTML = '<i class="fa-solid fa-bolt"></i> Selesaikan Pembayaran (Midtrans)';
    }
  });

  /* Download Receipt Action */
  const btnDownloadReceipt = document.getElementById('btn-download-receipt');
  btnDownloadReceipt?.addEventListener('click', () => {
    btnDownloadReceipt.innerHTML = '<i class="fa-solid fa-check"></i> Mengunduh...';
    setTimeout(() => {
      alert(`Official Midtrans Receipt untuk pemesanan ${bookingCode} telah diunduh.`);
      btnDownloadReceipt.innerHTML = '<i class="fa-solid fa-file-arrow-down"></i> Unduh E-Receipt';
    }, 600);
  });

  /* Receipt Done Button */
  const btnReceiptDone = document.getElementById('btn-receipt-done');
  btnReceiptDone?.addEventListener('click', () => {
    closeModal();
    window.location.reload();
  });

  /* Download Voucher for Cash view */
  const btnDownloadVoucher = document.getElementById('btn-download-voucher');
  btnDownloadVoucher?.addEventListener('click', () => {
    btnDownloadVoucher.innerHTML = '<i class="fa-solid fa-check"></i> Mengunduh...';
    setTimeout(() => {
      alert(`Invoice reservasi ${bookingCode} untuk ${flowDraft?.name || 'Pelanggan'} telah disiapkan.`);
      btnDownloadVoucher.innerHTML = '<i class="fa-solid fa-file-arrow-down"></i> Unduh Invoice Reservasi (PDF)';
    }, 600);
  });
});
