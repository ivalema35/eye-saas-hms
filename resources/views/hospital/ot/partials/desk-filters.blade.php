{{--
  Shared OT desk filter: Queue | History (+ optional Refunds) and date range for History.
  Required: $slug, $routeName, $activeFilter
  Optional: $fromDate, $toDate, $showRefunds, $refundsPendingCount, $extraQuery, $filterClass,
            $alwaysShowDateRange (date range form on every tab, not only History)
--}}
@php
    $activeFilter = $activeFilter ?? 'queue';
    $fromDate = $fromDate ?? now()->toDateString();
    $toDate = $toDate ?? $fromDate;
    $showRefunds = !empty($showRefunds);
    $refundsPendingCount = (int) ($refundsPendingCount ?? 0);
    $extraQuery = $extraQuery ?? [];
    $wrapClass = $filterClass ?? 'ot-desk-filter-wrap';
    $btnClass = ($filterClass ?? 'ot-desk') . '-btn';
    $alwaysShowDateRange = !empty($alwaysShowDateRange);
@endphp

<div class="{{ $wrapClass }} d-flex flex-wrap align-items-center gap-2" role="group" aria-label="OT desk filter">
    @php
        $tabQuery = $alwaysShowDateRange ? ['from_date' => $fromDate, 'to_date' => $toDate] : [];
    @endphp
    <a href="{{ route($routeName, array_merge(['slug' => $slug, 'filter' => 'queue'], $tabQuery, $extraQuery)) }}"
       class="ot-desk-filter-btn {{ $activeFilter === 'queue' ? 'active' : '' }}">Queue</a>

    @if($showRefunds)
        <a href="{{ route($routeName, array_merge(['slug' => $slug, 'filter' => 'refunds'], $tabQuery, $extraQuery)) }}"
           class="ot-desk-filter-btn {{ $activeFilter === 'refunds' ? 'active' : '' }}">
            Refunds
        </a>
    @endif

    <a href="{{ route($routeName, array_merge(['slug' => $slug, 'filter' => 'history', 'from_date' => $fromDate, 'to_date' => $toDate], $extraQuery)) }}"
       class="ot-desk-filter-btn {{ $activeFilter === 'history' ? 'active' : '' }}">All</a>

    @if($activeFilter === 'history' || $alwaysShowDateRange)
        <form method="GET" action="{{ route($routeName, ['slug' => $slug]) }}" class="ot-desk-date-form d-inline-flex align-items-center gap-2 ms-1">
            <input type="hidden" name="filter" value="{{ $activeFilter }}">
            @foreach($extraQuery as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="text"
                   class="form-control form-control-sm ot-desk-date-input"
                   data-hms-date-range
                   data-start-name="from_date"
                   data-end-name="to_date"
                   data-start-value="{{ $fromDate }}"
                   data-end-value="{{ $toDate }}"
                   data-auto-submit="1"
                   placeholder="Date range"
                   autocomplete="off"
                   readonly
                   style="min-width:200px;">
        </form>
    @endif
</div>
