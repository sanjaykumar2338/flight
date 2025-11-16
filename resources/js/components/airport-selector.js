const DATA_URL = '/data/airports.json';
const TRENDING_CODES = ['DXB', 'LHR', 'JFK', 'SIN', 'CDG', 'SYD', 'LOS', 'AMS', 'HKG', 'GRU'];
const NEARBY_RADIUS_KM = 250;

class AirportDataStore {
    constructor() {
        this._promise = null;
        this._list = [];
        this._byCode = new Map();
    }

    async load() {
        if (this._promise) {
            return this._promise;
        }

        this._promise = fetch(DATA_URL)
            .then((response) => response.json())
            .then((data) => {
                this._list = (Array.isArray(data) ? data : []).map((airport) => {
                    const iata = (airport.iata || airport.iata_code || '').toUpperCase();
                    const geoloc = airport._geoloc || {};

                    return {
                        iata,
                        name: airport.name || '',
                        city: airport.city || '',
                        country: airport.country || '',
                        lat: airport.lat ?? geoloc.lat ?? null,
                        lng: airport.lng ?? geoloc.lng ?? null,
                    };
                }).filter((airport) => airport.iata !== '');

                this._list.forEach((airport) => {
                    this._byCode.set(airport.iata, airport);
                });
                return this._list;
            })
            .catch((error) => {
                console.error('Unable to load airports.json', error);
                return [];
            });

        return this._promise;
    }

    getAll() {
        return this._list;
    }

    find(code) {
        if (!code) {
            return null;
        }
        return this._byCode.get(code.toUpperCase()) || null;
    }

    trending() {
        return TRENDING_CODES.map((code) => this.find(code)).filter(Boolean);
    }

    search(term) {
        const query = (term || '').trim().toLowerCase();
        if (query === '') {
            return this._list.slice(0, 15);
        }

        const scored = this._list
            .map((airport) => ({
                airport,
                score: Math.min(
                    this._scoreText(airport.iata, query),
                    this._scoreText(airport.city, query),
                    this._scoreText(airport.name, query),
                    this._scoreText(airport.country, query)
                ),
            }))
            .filter((entry) => Number.isFinite(entry.score))
            .sort((a, b) => a.score - b.score)
            .slice(0, 15);

        return scored.map((entry) => entry.airport);
    }

    nearby(baseAirport) {
        if (!baseAirport?.lat || !baseAirport?.lng) {
            return [];
        }

        const { lat, lng } = baseAirport;
        return this._list
            .filter((airport) => airport.iata !== baseAirport.iata)
            .map((airport) => ({
                airport,
                distance: this._haversine(lat, lng, airport.lat, airport.lng),
            }))
            .filter((entry) => entry.distance <= NEARBY_RADIUS_KM)
            .sort((a, b) => a.distance - b.distance)
            .slice(0, 8);
    }

    _scoreText(text, query) {
        const haystack = (text || '').toString().toLowerCase();
        if (haystack === '') {
            return Infinity;
        }

        let score = 0;
        let lastIndex = -1;

        for (let i = 0; i < query.length; i += 1) {
            const char = query[i];
            const nextIndex = haystack.indexOf(char, lastIndex + 1);

            if (nextIndex === -1) {
                return Infinity;
            }

            score += nextIndex - lastIndex;
            lastIndex = nextIndex;
        }

        return score + (haystack.length - query.length);
    }

    _haversine(lat1, lon1, lat2, lon2) {
        const toRad = (value) => (value * Math.PI) / 180;
        const R = 6371;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }
}

const AirportData = new AirportDataStore();

class AirportSelector {
    constructor(container) {
        this.container = container;
        this.hiddenInput = container.querySelector('input[type="hidden"]');
        this.searchInput = container.querySelector('[data-airport-search]');
        this.dropdown = container.querySelector('[data-airport-dropdown]');
        this.selectedZone = container.querySelector('[data-airport-selected]');
        this.selectedCode = (this.hiddenInput?.value || '').toUpperCase();
        this.dropdownOpen = false;
        this.optionButtons = [];
        this.activeIndex = -1;
        this.boundClickOutside = this.handleOutsideClick.bind(this);
        this.init();
    }

    static initAll() {
        const containers = document.querySelectorAll('[data-airport-selector]');
        containers.forEach((container) => {
            const selector = new AirportSelector(container);
            AirportSelectorManager.register(container.dataset.airportSelector, selector);
        });
    }

    init() {
        this.renderChip();
        if (!this.searchInput || !this.dropdown) {
            return;
        }

        this.searchInput.addEventListener('focus', () => this.openDropdown());
        this.searchInput.addEventListener('input', () => this.renderDropdown());
        this.searchInput.addEventListener('keydown', (event) => this.handleKeydown(event));

        this.renderDropdown();
    }

    handleKeydown(event) {
        if (!this.dropdownOpen) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.moveActive(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.moveActive(-1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (this.optionButtons[this.activeIndex]) {
                this.optionButtons[this.activeIndex].click();
            }
        } else if (event.key === 'Escape') {
            this.closeDropdown();
        }
    }

    moveActive(delta) {
        if (this.optionButtons.length === 0) {
            return;
        }

        this.activeIndex = (this.activeIndex + delta + this.optionButtons.length) % this.optionButtons.length;
        this.optionButtons.forEach((button, index) => {
            button.classList.toggle('bg-sky-50', index === this.activeIndex);
        });

        const active = this.optionButtons[this.activeIndex];
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    openDropdown() {
        if (this.dropdownOpen) {
            return;
        }

        this.dropdownOpen = true;
        this.dropdown.classList.remove('hidden');
        document.addEventListener('click', this.boundClickOutside);
        this.renderDropdown();
    }

    closeDropdown() {
        this.dropdownOpen = false;
        this.dropdown.classList.add('hidden');
        document.removeEventListener('click', this.boundClickOutside);
    }

    handleOutsideClick(event) {
        if (!this.container.contains(event.target)) {
            this.closeDropdown();
        }
    }

    renderChip() {
        if (!this.selectedZone) {
            return;
        }

        this.selectedZone.innerHTML = '';

        const code = this.selectedCode;
        const airport = AirportData.find(code);

        if (!code || !airport) {
            const placeholder = document.createElement('span');
            placeholder.className = 'text-xs text-gray-400';
            placeholder.textContent = 'No airport selected';
            this.selectedZone.appendChild(placeholder);
            if (this.searchInput) {
                this.searchInput.value = '';
            }
            return;
        }

        const chip = document.createElement('div');
        chip.className =
            'inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700';
        chip.innerHTML = `<span>${airport.city}, ${airport.country} (${airport.iata})</span>`;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'text-sky-600 hover:text-sky-900 focus:outline-none';
        removeBtn.setAttribute('aria-label', `Remove ${airport.city}`);
        removeBtn.textContent = '×';
        removeBtn.addEventListener('click', () => this.clearSelection());

        chip.appendChild(removeBtn);
        this.selectedZone.appendChild(chip);

        if (this.searchInput) {
            this.searchInput.value = `${airport.city}, ${airport.country}`;
        }
    }

    renderDropdown() {
        if (!this.dropdownOpen || !this.dropdown) {
            return;
        }

        const query = this.searchInput?.value || '';
        const sections = this.buildSections(query);
        this.dropdown.innerHTML = '';
        this.optionButtons = [];
        this.activeIndex = -1;

        if (sections.every((section) => section.items.length === 0)) {
            const emptyState = document.createElement('div');
            emptyState.className = 'px-4 py-6 text-center text-sm text-gray-500';
            emptyState.textContent = 'No matches found. Try another city or airport.';
            this.dropdown.appendChild(emptyState);
            return;
        }

        sections.forEach((section) => {
            if (section.items.length === 0) {
                return;
            }

            const group = document.createElement('div');
            group.className = 'border-b border-slate-100 last:border-b-0';

            const header = document.createElement('div');
            header.className = 'px-4 py-2 text-xs font-semibold uppercase text-slate-500';
            header.textContent = section.title;
            group.appendChild(header);

            section.items.forEach((entry) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className =
                    'flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm hover:bg-sky-50 focus:bg-sky-50';

                const meta = document.createElement('div');
                meta.className = 'flex flex-col';

                const mainLine = document.createElement('span');
                mainLine.className = 'font-semibold text-slate-800';
                mainLine.textContent = `${entry.airport.city}, ${entry.airport.country}`;

                const subLine = document.createElement('span');
                subLine.className = 'text-xs text-slate-500';
                const name = entry.airport.name;
                subLine.textContent = `${name} (${entry.airport.iata})`;

                meta.appendChild(mainLine);
                meta.appendChild(subLine);

                const glyph = document.createElement('span');
                glyph.className =
                    'inline-flex h-6 w-6 items-center justify-center rounded-full border text-xs font-bold';
                if (entry.isSelected) {
                    glyph.textContent = '✓';
                    glyph.classList.add('border-emerald-500', 'text-emerald-600');
                } else {
                    glyph.textContent = '+';
                    glyph.classList.add('border-slate-300', 'text-slate-500');
                }

                if (entry.distance) {
                    const distanceTag = document.createElement('span');
                    distanceTag.className = 'text-xs text-slate-400';
                    distanceTag.textContent = `${entry.distance.toFixed(0)} km away`;
                    subLine.appendChild(document.createTextNode(' • '));
                    subLine.appendChild(distanceTag);
                }

                option.appendChild(meta);
                option.appendChild(glyph);
                option.addEventListener('click', () => this.selectAirport(entry.airport.iata));

                this.optionButtons.push(option);
                group.appendChild(option);
            });

            this.dropdown.appendChild(group);
        });
    }

    buildSections(query) {
        const hasQuery = (query || '').trim() !== '';
        const sections = hasQuery
            ? [
                  { title: 'All matches', items: [] },
                  { title: 'Already included', items: [] },
                  { title: 'Nearby (250 km)', items: [] },
                  { title: 'Trending', items: [] },
              ]
            : [
                  { title: 'Already included', items: [] },
                  { title: 'Nearby (250 km)', items: [] },
                  { title: 'Trending', items: [] },
                  { title: 'All matches', items: [] },
              ];

        const selectedAirport = AirportData.find(this.selectedCode);

        if (selectedAirport) {
            const includedSection = sections.find((section) => section.title === 'Already included');
            includedSection?.items.push({
                airport: selectedAirport,
                isSelected: true,
            });

            const nearby = AirportData.nearby(selectedAirport);
            nearby.forEach((entry) => {
                const nearbySection = sections.find((section) => section.title === 'Nearby (250 km)');
                nearbySection?.items.push({
                    airport: entry.airport,
                    distance: entry.distance,
                    isSelected: entry.airport.iata === this.selectedCode,
                });
            });
        }

        const trendingSection = sections.find((section) => section.title === 'Trending');
        AirportData.trending().forEach((airport) => {
            trendingSection?.items.push({
                airport,
                isSelected: airport.iata === this.selectedCode,
            });
        });

        const matchSection = sections.find((section) => section.title === 'All matches');
        AirportData.search(query).forEach((airport) => {
            matchSection?.items.push({
                airport,
                isSelected: airport.iata === this.selectedCode,
            });
        });

        return sections;
    }

    selectAirport(code) {
        if (!this.hiddenInput) {
            return;
        }

        if (!code) {
            this.clearSelection();
            return;
        }

        const airport = AirportData.find(code);

        if (!airport) {
            this.clearSelection();
            return;
        }

        this.selectedCode = airport.iata.toUpperCase();
        this.hiddenInput.value = this.selectedCode;
        this.renderChip();
        this.closeDropdown();
    }

    clearSelection() {
        if (!this.hiddenInput) {
            return;
        }
        this.hiddenInput.value = '';
        this.selectedCode = '';
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.renderChip();
        this.renderDropdown();
    }

    refresh() {
        if (!this.hiddenInput) {
            return;
        }
        this.selectedCode = (this.hiddenInput.value || '').toUpperCase();
        this.renderChip();
        this.renderDropdown();
    }

    getValue() {
        return this.selectedCode || '';
    }
}

class AirportSelectorManagerClass {
    constructor() {
        this.instances = new Map();
    }

    register(key, instance) {
        if (!key) {
            return;
        }
        this.instances.set(key, instance);
    }

    get(key) {
        return this.instances.get(key);
    }

    swap(a, b) {
        const instA = this.get(a);
        const instB = this.get(b);

        if (!instA || !instB) {
            return;
        }

        const valueA = instA.getValue();
        const valueB = instB.getValue();

        instA.selectAirport(valueB || '');
        instB.selectAirport(valueA || '');
    }

    refreshAll() {
        this.instances.forEach((instance) => instance.refresh());
    }
}

const AirportSelectorManager = new AirportSelectorManagerClass();
window.AirportSelectorManager = AirportSelectorManager;

document.addEventListener('DOMContentLoaded', async () => {
    await AirportData.load();
    AirportSelector.initAll();
});
