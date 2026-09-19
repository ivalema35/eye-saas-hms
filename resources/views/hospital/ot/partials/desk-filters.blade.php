{{--
  Shared OT desk filter: Queue | History (+ optional Refunds) and date range for History.
  Required: $slug, $routeName, $activeFilter
  Optional: $fromDate, $toDate, $showRefunds, $refundsPendingCount, $extraQuery, $filterClass
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
@endphp

<div class="{{ $wrapClass }} d-flex flex-wrap align-items-center gap-2" role="group" aria-label="OT desk filter">
    <a href="{{ route($routeName, array_merge(['slug' => $slug, 'filter' => 'queue'], $extraQuery)) }}"
       class="ot-desk-filter-btn {{ $activeFilter === 'queue' ? 'active' : '' }}">Queue</a>

    @if($showRefunds)
        <a href="{{ route($routeName, array_merge(['slug' => $slug, 'filter' => 'refunds'], $extraQuery)) }}"
           class="ot-desk-filter-btn {{ $activeFilter === 'refunds' ? 'active' : '' }}">
            Refunds
            @if($refundsPendingCount > 0)
                <span class="badge text-bg-danger ms-1">{{ $refundsPendingCount }}</span>
            @endif
        </a>
    @endif

    <a href="{{ route($routeName, array_merge(['slug' => $slug, 'filter' => 'history', 'from_date' => $fromDate, 'to_date' => $toDate], $extraQuery)) }}"
       class="ot-desk-filter-btn {{ $activeFilter === 'history' ? 'active' : '' }}">All</a>

    @if($activeFilter === 'history')
        <form method="GET" action="{{ route($routeName, ['slug' => $slug]) }}" class="ot-desk-date-form d-inline-flex align-items-center gap-2 ms-1">
            <input type="hidden" name="filter" value="history">
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
