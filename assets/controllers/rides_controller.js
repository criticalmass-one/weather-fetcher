import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'spinner', 'error', 'empty'];

    connect() {
        this.loadRides();
    }

    async loadRides() {
        this.spinnerTarget.hidden = false;
        this.errorTarget.hidden = true;
        this.emptyTarget.hidden = true;

        try {
            const response = await fetch('/api/rides');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const rides = await response.json();
            this.spinnerTarget.hidden = true;

            if (rides.length === 0) {
                this.emptyTarget.hidden = false;
                return;
            }

            this.renderRides(rides);
        } catch (error) {
            this.spinnerTarget.hidden = true;
            this.errorTarget.hidden = false;
            this.errorTarget.textContent = `Fehler beim Laden der Touren: ${error.message}`;
        }
    }

    renderRides(rides) {
        const grouped = {};

        rides.forEach(ride => {
            if (!grouped[ride.date]) {
                grouped[ride.date] = [];
            }
            grouped[ride.date].push(ride);
        });

        let html = '';

        Object.keys(grouped).sort().forEach(date => {
            const dateObj = new Date(date + 'T00:00:00');
            const formatted = dateObj.toLocaleDateString('de-DE', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            html += `<h4 class="mt-4 mb-3"><i class="bi bi-calendar-event me-2"></i>${formatted}</h4>`;
            html += '<div class="row g-3">';

            grouped[date].sort((a, b) => a.city.localeCompare(b.city)).forEach(ride => {
                html += this.renderCard(ride);
            });

            html += '</div>';
        });

        this.containerTarget.innerHTML = html;
    }

    renderCard(ride) {
        const locationHtml = ride.location
            ? `<p class="card-text text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i>${this.escapeHtml(ride.location)}</p>`
            : '';

        const coordBadge = ride.hasCoordinates
            ? '<span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-pin-map me-1"></i>Koordinaten</span>'
            : '<span class="badge bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Keine Koordinaten</span>';

        const buttonDisabled = ride.hasCoordinates ? '' : 'disabled';
        const buttonTitle = ride.hasCoordinates ? 'Wetter abrufen und speichern' : 'Keine Koordinaten vorhanden';

        return `
            <div class="col-md-6 col-lg-4">
                <div class="card ride-card h-100" data-controller="weather" data-weather-city-slug-value="${this.escapeAttr(ride.citySlug)}" data-weather-date-value="${this.escapeAttr(ride.date)}">
                    <div class="card-body">
                        <h5 class="card-title mb-1">${this.escapeHtml(ride.city)}</h5>
                        <p class="card-text text-muted small mb-1"><i class="bi bi-clock me-1"></i>${this.escapeHtml(ride.time)} Uhr</p>
                        ${locationHtml}
                        <div class="mt-2 mb-3">${coordBadge}</div>
                        <div data-weather-target="result"></div>
                        <button class="btn btn-sm btn-primary" data-action="weather#fetch" data-weather-target="button" ${buttonDisabled} title="${buttonTitle}">
                            <i class="bi bi-cloud-download me-1"></i>Wetter abrufen
                        </button>
                        <div data-weather-target="spinner" class="spinner-overlay" hidden>
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Laden…</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    escapeAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
}
