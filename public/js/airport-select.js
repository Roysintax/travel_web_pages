/**
 * Airport Searchable Dropdown Component
 * Integrates AirLabs Airport API via internal Laravel Proxy (/api/airports)
 */
(function () {
  'use strict';

  class AirportSelect {
    constructor(containerId, options = {}) {
      this.container = document.getElementById(containerId);
      if (!this.container) return;

      this.options = Object.assign({
        hiddenInputId: 'trip-origin',
        hiddenLabelInputId: 'trip-origin-label',
        searchInputId: 'trip-origin-input',
        menuId: 'airport-dropdown-menu',
        resultsListId: 'airport-results',
        toggleBtnId: 'airport-toggle-btn',
        clearBtnId: 'airport-clear-btn',
        loadingStateId: 'airport-loading',
        errorStateId: 'airport-error',
        emptyStateId: 'airport-empty',
        retryBtnId: 'airport-retry-btn',
        headerTitleId: 'airport-dropdown-title',
        searchEndpoint: '/api/airports/search',
        popularEndpoint: '/api/airports/popular',
        debounceMs: 320,
        onSelect: null
      }, options);

      this.hiddenInput = document.getElementById(this.options.hiddenInputId);
      this.hiddenLabelInput = document.getElementById(this.options.hiddenLabelInputId);
      this.searchInput = document.getElementById(this.options.searchInputId);
      this.menu = document.getElementById(this.options.menuId);
      this.resultsList = document.getElementById(this.options.resultsListId);
      this.toggleBtn = document.getElementById(this.options.toggleBtnId);
      this.clearBtn = document.getElementById(this.options.clearBtnId);
      this.loadingState = document.getElementById(this.options.loadingStateId);
      this.errorState = document.getElementById(this.options.errorStateId);
      this.emptyState = document.getElementById(this.options.emptyStateId);
      this.retryBtn = document.getElementById(this.options.retryBtnId);
      this.headerTitle = document.getElementById(this.options.headerTitleId);

      this.airports = [];
      this.popularAirports = [];
      this.activeIndex = -1;
      this.isOpen = false;
      this.debounceTimer = null;
      this.abortController = null;
      this.selectedAirport = null;
      this.lastQuery = '';

      this.init();
    }

    init() {
      // Input event listeners
      this.searchInput.addEventListener('input', () => this.handleInput());
      this.searchInput.addEventListener('focus', () => this.open());
      this.searchInput.addEventListener('keydown', (e) => this.handleKeyDown(e));

      // Toggle & Clear buttons
      this.toggleBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (this.isOpen) {
          this.close();
        } else {
          this.searchInput.focus();
          this.open();
        }
      });

      this.clearBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.clear();
        this.searchInput.focus();
        this.open();
      });

      this.retryBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        this.fetchAirports(this.searchInput.value.trim());
      });

      // Close when clicking outside
      document.addEventListener('click', (e) => {
        if (!this.container.contains(e.target)) {
          this.close();
        }
      });

      // Pre-fetch popular airports in background
      this.fetchPopularAirports();

      // Check if draft or existing value needs restoration
      this.restoreExistingValue();
    }

    restoreExistingValue() {
      const currentVal = this.hiddenInput ? this.hiddenInput.value.trim() : '';
      const currentLabel = this.hiddenLabelInput ? this.hiddenLabelInput.value.trim() : '';
      if (currentLabel) {
        this.searchInput.value = currentLabel;
        this.updateClearBtnVisibility();
      } else if (currentVal) {
        this.searchInput.value = currentVal;
        this.updateClearBtnVisibility();
      }
    }

    handleInput() {
      const query = this.searchInput.value.trim();
      this.updateClearBtnVisibility();

      // If user clears the input manually
      if (!query) {
        if (this.hiddenInput) this.hiddenInput.value = '';
        if (this.hiddenLabelInput) this.hiddenLabelInput.value = '';
        this.selectedAirport = null;
        this.triggerChange();
      }

      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.open();
        this.fetchAirports(query);
      }, this.options.debounceMs);
    }

    handleKeyDown(e) {
      if (!this.isOpen) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
          this.open();
          return;
        }
      }

      const items = this.resultsList.querySelectorAll('.airport-item');
      const count = items.length;

      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          if (count === 0) return;
          this.activeIndex = (this.activeIndex + 1) % count;
          this.updateActiveItem(items);
          break;

        case 'ArrowUp':
          e.preventDefault();
          if (count === 0) return;
          this.activeIndex = (this.activeIndex - 1 + count) % count;
          this.updateActiveItem(items);
          break;

        case 'Enter':
          if (this.isOpen && this.activeIndex >= 0 && this.activeIndex < count) {
            e.preventDefault();
            const selectedItem = items[this.activeIndex];
            const iata = selectedItem.dataset.iata;
            const airport = this.airports.find(a => a.iata === iata);
            if (airport) {
              this.selectAirport(airport);
            }
          }
          break;

        case 'Escape':
          e.preventDefault();
          this.close();
          break;

        case 'Tab':
          this.close();
          break;
      }
    }

    updateActiveItem(items) {
      items.forEach((item, index) => {
        const isActive = index === this.activeIndex;
        item.classList.toggle('is-active', isActive);
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        if (isActive) {
          item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
    }

    open() {
      if (this.isOpen) return;
      this.isOpen = true;
      this.container.classList.add('is-open');
      this.menu.hidden = false;
      this.container.setAttribute('aria-expanded', 'true');

      const query = this.searchInput.value.trim();
      // If results are empty or query changed, fetch
      if (this.airports.length === 0 || query !== this.lastQuery) {
        this.fetchAirports(query);
      }
    }

    close() {
      if (!this.isOpen) return;
      this.isOpen = false;
      this.container.classList.remove('is-open');
      this.menu.hidden = true;
      this.container.setAttribute('aria-expanded', 'false');
      this.activeIndex = -1;

      // If user typed something but didn't select from list and closed,
      // either maintain selection or format query
      if (this.selectedAirport) {
        this.searchInput.value = this.getFormattedDisplay(this.selectedAirport);
      } else if (!this.hiddenInput.value && this.searchInput.value.trim()) {
        // Fallback: If free-text was typed, use as origin if desired
        this.hiddenInput.value = this.searchInput.value.trim();
        this.triggerChange();
      }
      this.updateClearBtnVisibility();
    }

    clear() {
      this.searchInput.value = '';
      if (this.hiddenInput) this.hiddenInput.value = '';
      if (this.hiddenLabelInput) this.hiddenLabelInput.value = '';
      this.selectedAirport = null;
      this.airports = [];
      this.activeIndex = -1;
      this.updateClearBtnVisibility();
      this.triggerChange();
      this.fetchAirports('');
    }

    updateClearBtnVisibility() {
      if (this.clearBtn) {
        this.clearBtn.hidden = !this.searchInput.value.trim();
      }
    }

    setState(state) {
      this.loadingState.hidden = state !== 'loading';
      this.errorState.hidden = state !== 'error';
      this.emptyState.hidden = state !== 'empty';
      this.resultsList.hidden = state !== 'success';
    }

    async fetchPopularAirports() {
      try {
        const response = await fetch(this.options.popularEndpoint);
        if (response.ok) {
          const res = await response.json();
          if (res.success && Array.isArray(res.data)) {
            this.popularAirports = res.data;
          }
        }
      } catch (err) {
        // Silent catch for background prefetch
      }
    }

    async fetchAirports(query) {
      this.lastQuery = query;

      // Cancel previous pending request
      if (this.abortController) {
        this.abortController.abort();
      }
      this.abortController = new AbortController();

      // If query is empty, show popular hubs
      if (!query) {
        if (this.headerTitle) {
          this.headerTitle.textContent = 'Popular Departure Airports';
        }
        if (this.popularAirports.length > 0) {
          this.airports = this.popularAirports;
          this.renderResults(this.airports);
          this.setState('success');
          return;
        }
      } else {
        if (this.headerTitle) {
          this.headerTitle.textContent = `Airports matching "${query}"`;
        }
      }

      this.setState('loading');

      try {
        const url = `${this.options.searchEndpoint}?q=${encodeURIComponent(query)}`;
        const response = await fetch(url, { signal: this.abortController.signal });

        if (!response.ok) {
          throw new Error(`HTTP error ${response.status}`);
        }

        const data = await response.json();
        if (data.success && Array.isArray(data.data)) {
          this.airports = data.data;
          if (this.airports.length === 0) {
            this.setState('empty');
          } else {
            this.renderResults(this.airports);
            this.setState('success');
          }
        } else {
          this.setState('empty');
        }
      } catch (err) {
        if (err.name === 'AbortError') {
          // Request aborted, ignore
          return;
        }
        console.error('Failed to load airports:', err);
        this.setState('error');
      }
    }

    renderResults(airports) {
      this.resultsList.innerHTML = '';
      this.activeIndex = -1;

      const currentIata = this.hiddenInput ? this.hiddenInput.value : '';

      airports.forEach((airport, index) => {
        const li = document.createElement('li');
        li.className = 'airport-item';
        li.role = 'option';
        li.id = `airport-option-${airport.iata}`;
        li.dataset.iata = airport.iata;
        li.setAttribute('aria-selected', airport.iata === currentIata ? 'true' : 'false');
        if (airport.iata === currentIata) {
          li.classList.add('is-selected');
        }

        const locationText = [airport.city, airport.country].filter(Boolean).join(', ');

        li.innerHTML = `
          <div class="airport-item-code">
            <span class="iata-badge">${this.escapeHtml(airport.iata)}</span>
            ${airport.icao ? `<span class="icao-badge">${this.escapeHtml(airport.icao)}</span>` : ''}
          </div>
          <div class="airport-item-info">
            <strong class="airport-item-name">${this.escapeHtml(airport.name)}</strong>
            <span class="airport-item-location">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
              ${this.escapeHtml(locationText)}
            </span>
          </div>
          <i class="fa-solid fa-check airport-selected-check" aria-hidden="true"></i>
        `;

        li.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.selectAirport(airport);
        });

        li.addEventListener('mouseenter', () => {
          this.activeIndex = index;
          const items = this.resultsList.querySelectorAll('.airport-item');
          this.updateActiveItem(items);
        });

        this.resultsList.appendChild(li);
      });
    }

    selectAirport(airport) {
      this.selectedAirport = airport;
      const formatted = this.getFormattedDisplay(airport);

      // Save IATA code as primary form value (or full descriptive string if needed)
      if (this.hiddenInput) {
        this.hiddenInput.value = airport.iata;
      }
      if (this.hiddenLabelInput) {
        this.hiddenLabelInput.value = formatted;
      }

      this.searchInput.value = formatted;
      this.updateClearBtnVisibility();
      this.close();

      this.triggerChange();

      if (typeof this.options.onSelect === 'function') {
        this.options.onSelect(airport, formatted);
      }
    }

    getFormattedDisplay(airport) {
      const city = airport.city ? ` (${airport.city})` : '';
      return `${airport.iata} — ${airport.name}${city}`;
    }

    triggerChange() {
      // Dispatch standard input and change events so trip summary & form updates automatically
      if (this.hiddenInput) {
        this.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      this.searchInput.dispatchEvent(new Event('input', { bubbles: true }));
      this.searchInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  }

  // Export to window
  window.AirportSelect = AirportSelect;
})();
