{{-- Shared hero + tabs + date filter for the two transaction logs.
     $active is 'pos' or 'deliveries'; $subtitle describes the current tab. --}}
@push('styles')
<style>
    .log-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .log-tab {
        padding: 10px 20px;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--color-border);
        background: var(--color-surface);
        color: var(--color-text-muted);
        font-weight: 600;
        text-decoration: none;
        transition: border-color var(--dur-fast) var(--ease-out),
            background var(--dur-fast) var(--ease-out),
            color var(--dur-fast) var(--ease-out);
    }

    .log-tab:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .log-tab.is-active {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: #fff;
    }

    .log-filter {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .log-filter label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--color-text-muted);
        margin-bottom: 6px;
    }

    .log-filter input[type="date"] {
        padding: 10px 12px;
        border-radius: var(--radius-sm);
        border: 1.5px solid #e5e7eb;
        font: inherit;
        background: var(--color-page-bg);
    }

    /* Same icon button as the Inventory filter (inventory.blade.php:106), so
       "filter" looks identical wherever it appears. Sized to the date inputs
       beside it rather than the 52px there, which sits next to a taller search
       field. */
    .log-filter__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        padding: 0;
        border-radius: var(--radius-md);
        border: 2px solid var(--color-primary);
        background: var(--color-page-bg);
        color: var(--color-primary);
        cursor: pointer;
        box-shadow: var(--shadow-md);
        transition: background var(--dur-fast) var(--ease-out),
            color var(--dur-fast) var(--ease-out);
    }

    .log-filter__btn:hover {
        background: var(--color-primary);
        color: #fff;
    }

    .log-filter__btn:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .log-filter input[type="date"].is-invalid {
        border-color: var(--color-danger);
        background: var(--color-danger-bg);
    }

    .log-filter__error {
        flex-basis: 100%;
        margin-top: -8px;
        color: var(--color-danger-text);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .log-empty {
        text-align: center;
        color: var(--color-text-muted);
        padding: 40px 16px;
    }

    .log-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }
</style>
@endpush

<div class="dashboard-hero">
    <div>
        <div class="dashboard-hero-greeting">Transaction Logs</div>
        <div class="dashboard-hero-sub">{{ $subtitle }}</div>
    </div>
</div>

<div class="log-tabs">
    <a href="{{ route('logs.pos') }}" class="log-tab {{ $active === 'pos' ? 'is-active' : '' }}">POS Sales</a>
    <a href="{{ route('logs.deliveries') }}" class="log-tab {{ $active === 'deliveries' ? 'is-active' : '' }}">Supplier Deliveries</a>
</div>

@php $dateError = $dateError ?? null; @endphp

<form method="GET" class="log-filter" id="logFilterForm">
    <div>
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="{{ $from }}"
            class="{{ $dateError ? 'is-invalid' : '' }}">
    </div>
    <div>
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="{{ $to }}"
            class="{{ $dateError ? 'is-invalid' : '' }}">
    </div>
    <button type="submit" class="log-filter__btn" title="Apply filter" aria-label="Apply filter">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path d="M4 5h16M7 12h10M10 19h4" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
    @if($from || $to)
        <a href="{{ url()->current() }}" class="btn-action ghost">Clear</a>
    @endif

    @if($dateError)
        <div class="log-filter__error" role="alert">{{ $dateError }}</div>
    @endif
</form>

<script>
    (function () {
        var form = document.getElementById('logFilterForm');
        if (!form) return;

        var from = document.getElementById('from');
        var to = document.getElementById('to');

        // Constrain each picker by the other so an inverted range is hard to
        // enter at all. The server still validates: these params are in the URL
        // and can be edited by hand.
        function sync() {
            to.min = from.value || '';
            from.max = to.value || '';
        }

        from.addEventListener('change', sync);
        to.addEventListener('change', sync);
        sync();

        form.addEventListener('submit', function (e) {
            if (from.value && to.value && to.value < from.value) {
                e.preventDefault();
                to.setCustomValidity('The To date must be on or after the From date.');
                to.reportValidity();
            }
        });

        // Clear the custom message as soon as it is corrected, otherwise the
        // field stays permanently invalid.
        to.addEventListener('input', function () { to.setCustomValidity(''); });
        from.addEventListener('input', function () { to.setCustomValidity(''); });
    })();
</script>
