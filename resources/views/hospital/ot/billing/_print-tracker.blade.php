{{--
  Records a discharge document print (browser afterprint) so the booking moves to
  Discharged once all documents are printed. Required: $booking, $document.
  Skipped when $slug is absent (API renders these views into PDFs).
--}}
@isset($slug)
    @php
        $printDoneUrl = route('hospital.ot.print-done', ['slug' => $slug, 'bookingId' => $booking->id, 'document' => $document]);
    @endphp
    <style>
        .dc-print-toast {
            position: fixed; left: 50%; bottom: 22px; transform: translateX(-50%);
            display: none; align-items: center; gap: 12px; z-index: 9999;
            background: #1B4F72; color: #fff; border-radius: 12px; padding: 12px 18px;
            font: 600 13px/1.4 Arial, sans-serif; box-shadow: 0 12px 32px rgba(27, 79, 114, .35);
        }
        .dc-print-toast.is-visible { display: inline-flex; }
        .dc-print-toast.is-done { background: #1E8E5A; }
        .dc-print-toast a {
            color: #1B4F72; background: #fff; border-radius: 999px; padding: 5px 12px;
            text-decoration: none; font-weight: 700; white-space: nowrap;
        }
        .dc-print-toast.is-done a { color: #1E8E5A; }
        @media print { .dc-print-toast { display: none !important; } }
    </style>
    <div class="dc-print-toast" id="dcPrintToast" role="status" aria-live="polite">
        <span id="dcPrintToastText"></span>
        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Back to Dashboard</a>
    </div>
    <script>
        (function () {
            var url = @json($printDoneUrl);
            var token = @json(csrf_token());
            var sending = false;

            function show(data) {
                var toast = document.getElementById('dcPrintToast');
                var text = document.getElementById('dcPrintToastText');
                text.textContent = data.discharged || data.status === 'discharged'
                    ? 'All ' + data.total + ' documents printed — patient moved to Discharged.'
                    : data.label + ' printed · ' + data.done + ' of ' + data.total + ' documents done.';
                toast.classList.toggle('is-done', data.done >= data.total);
                toast.classList.add('is-visible');
            }

            window.addEventListener('afterprint', function () {
                if (sending) return;
                sending = true;
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) { if (data) show(data); })
                    .catch(function () {})
                    .finally(function () { sending = false; });
            });
        })();
    </script>
@endisset
