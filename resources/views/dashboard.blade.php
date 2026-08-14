@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <span class="muted">Your card progress at a glance</span>
</div>

<div class="grid six">
    <div class="stat" style="grid-column:span 2;">
        <h3>Total Cards</h3>
        <p>{{ $stats['total_cards'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2; background:#eefff8;">
        <h3>Completed Cards</h3>
        <p style="color:#0a7f52;">{{ $stats['completed_cards'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2; background:#fff5ea;">
        <h3>Due Today</h3>
        <p style="color:#c0671c;">{{ $stats['due_today'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <h3>Total Skills</h3>
        <p>{{ $stats['total_skills'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <h3>Total Reviews</h3>
        <p>{{ $stats['total_reviews'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <h3>Materials</h3>
        <p>{{ $stats['materials'] }}</p>
    </div>
</div>

<section class="panel forecast-panel" data-upcoming-due-url="{{ route('dashboard.upcoming-due') }}">
    <div class="page-header" style="margin-bottom:0.75rem;">
        <div>
            <h2>Upcoming Review Forecast</h2>
            <p class="muted" style="margin:0.2rem 0 0;">Cards that will become due in the selected future period. Cards already due are not included.</p>
        </div>
        <span class="label-chip">Live</span>
    </div>

    <div class="forecast-layout">
        <div class="forecast-result" aria-live="polite">
            <span class="forecast-count" data-upcoming-due-count>{{ $upcomingDueForecast['count'] }}</span>
            <span class="forecast-label">cards expected to become due</span>
            <span class="meta" data-upcoming-due-end>By {{ \Illuminate\Support\Carbon::parse($upcomingDueForecast['end_at'])->format('M j, Y g:i A') }}</span>
        </div>

        <div class="forecast-controls">
            <label for="forecast-preset">Future period</label>
            <select id="forecast-preset" data-forecast-preset>
                <option value="6:hours" selected>Next 6 hours</option>
                <option value="12:hours">Next 12 hours</option>
                <option value="24:hours">Next 24 hours</option>
                <option value="3:days">Next 3 days</option>
                <option value="7:days">Next 7 days</option>
                <option value="custom">Custom range…</option>
            </select>

            <div class="forecast-custom" data-forecast-custom hidden>
                <div>
                    <label for="forecast-value">Amount</label>
                    <input id="forecast-value" type="number" min="1" max="90" value="6" inputmode="numeric" data-forecast-value>
                </div>
                <div>
                    <label for="forecast-unit">Unit</label>
                    <select id="forecast-unit" data-forecast-unit>
                        <option value="hours">Hours</option>
                        <option value="days">Days</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.querySelector('.forecast-panel');
        if (!panel) return;

        const preset = panel.querySelector('[data-forecast-preset]');
        const custom = panel.querySelector('[data-forecast-custom]');
        const valueInput = panel.querySelector('[data-forecast-value]');
        const unitInput = panel.querySelector('[data-forecast-unit]');
        const count = panel.querySelector('[data-upcoming-due-count]');
        const end = panel.querySelector('[data-upcoming-due-end]');
        let refreshTimer;

        const selectedRange = function () {
            if (preset.value === 'custom') {
                return { value: valueInput.value, unit: unitInput.value };
            }

            const [value, unit] = preset.value.split(':');
            return { value: value, unit: unit };
        };

        const updateForecast = function () {
            const range = selectedRange();
            const params = new URLSearchParams(range);

            fetch(panel.dataset.upcomingDueUrl + '?' + params.toString(), {
                headers: { Accept: 'application/json' },
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Forecast request failed');
                    return response.json();
                })
                .then(function (forecast) {
                    count.textContent = forecast.count;
                    const deadline = new Date(forecast.end_at);
                    end.textContent = 'By ' + new Intl.DateTimeFormat(undefined, {
                        dateStyle: 'medium',
                        timeStyle: 'short',
                    }).format(deadline);
                })
                .catch(function () {
                    // Keep the last successful forecast visible if the connection is interrupted.
                });
        };

        const scheduleUpdate = function () {
            window.clearTimeout(refreshTimer);
            refreshTimer = window.setTimeout(updateForecast, 250);
        };

        const normalizeCustomValue = function () {
            const value = Number.parseInt(valueInput.value, 10);
            valueInput.value = Math.min(90, Math.max(1, Number.isNaN(value) ? 1 : value));
        };

        preset.addEventListener('change', function () {
            custom.hidden = preset.value !== 'custom';
            updateForecast();
        });
        valueInput.addEventListener('input', scheduleUpdate);
        valueInput.addEventListener('change', function () {
            normalizeCustomValue();
            updateForecast();
        });
        unitInput.addEventListener('change', updateForecast);

        window.setInterval(updateForecast, 60000);
    });
</script>
@endsection
