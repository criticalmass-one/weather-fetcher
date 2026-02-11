import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'spinner', 'result'];
    static values = {
        citySlug: String,
        date: String,
    };

    async fetch() {
        this.buttonTarget.disabled = true;
        this.spinnerTarget.hidden = false;
        this.resultTarget.innerHTML = '';

        try {
            const response = await fetch(`/api/weather/${this.citySlugValue}/${this.dateValue}`, {
                method: 'POST',
            });

            const data = await response.json();
            this.spinnerTarget.hidden = true;

            if (!response.ok) {
                this.renderError(data.error || 'Unbekannter Fehler');
                this.buttonTarget.disabled = false;
                return;
            }

            this.renderWeather(data);
        } catch (error) {
            this.spinnerTarget.hidden = true;
            this.renderError(`Netzwerkfehler: ${error.message}`);
            this.buttonTarget.disabled = false;
        }
    }

    renderWeather(data) {
        const w = data.weather;
        const iconUrl = w.weatherIcon ? `https://openweathermap.org/img/wn/${w.weatherIcon}@2x.png` : '';
        const pushedBadge = data.pushed
            ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Gespeichert</span>'
            : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Nicht gespeichert</span>';

        this.resultTarget.innerHTML = `
            <div class="weather-result mb-3">
                <div class="d-flex align-items-center mb-2">
                    ${iconUrl ? `<img src="${iconUrl}" alt="${w.weatherDescription || ''}" width="50" height="50">` : ''}
                    <div>
                        <strong>${w.weather || '—'}</strong><br>
                        <small class="text-muted">${w.weatherDescription || ''}</small>
                    </div>
                    <div class="ms-auto">${pushedBadge}</div>
                </div>
                <div class="row g-2 small">
                    <div class="col-6"><i class="bi bi-thermometer-half me-1"></i>Min: ${w.temperatureMin !== null ? w.temperatureMin + ' °C' : '—'}</div>
                    <div class="col-6"><i class="bi bi-thermometer-high me-1"></i>Max: ${w.temperatureMax !== null ? w.temperatureMax + ' °C' : '—'}</div>
                    <div class="col-6"><i class="bi bi-wind me-1"></i>Wind: ${w.windSpeed !== null ? w.windSpeed + ' m/s' : '—'}</div>
                    <div class="col-6"><i class="bi bi-droplet me-1"></i>Feuchte: ${w.humidity !== null ? w.humidity + ' %' : '—'}</div>
                    <div class="col-6"><i class="bi bi-cloud-rain me-1"></i>Niederschlag: ${w.precipitation !== null ? w.precipitation + ' mm' : '—'}</div>
                    <div class="col-6"><i class="bi bi-clouds me-1"></i>Wolken: ${w.clouds !== null ? w.clouds + ' %' : '—'}</div>
                </div>
            </div>`;

        this.buttonTarget.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Erneut abrufen';
        this.buttonTarget.disabled = false;
    }

    renderError(message) {
        this.resultTarget.innerHTML = `
            <div class="alert alert-warning py-2 small mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>${message}
            </div>`;
    }
}
