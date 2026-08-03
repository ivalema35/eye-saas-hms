<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Discharge Document Bundle</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #eef2f6; color: #111827; }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #1B4F72;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .toolbar h1 { font-size: 16px; margin: 0; }
        .toolbar .note { font-size: 12px; opacity: .85; }
        .toolbar button {
            background: #fff;
            color: #1B4F72;
            border: 0;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
        }
        .doc-list {
            max-width: 900px;
            margin: 16px auto;
            padding: 0 16px;
        }
        .doc-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doc-card a {
            color: #1B4F72;
            font-weight: 700;
            text-decoration: none;
            font-size: 13px;
        }
        .doc-card a:hover { text-decoration: underline; }
        .frames {
            max-width: 900px;
            margin: 0 auto 24px;
            padding: 0 16px;
        }
        .frame-wrap {
            page-break-after: always;
            margin-bottom: 16px;
        }
        .frame-wrap:last-child { page-break-after: auto; }
        .frame-wrap iframe {
            width: 100%;
            height: 800px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }
        @media print {
            .toolbar, .doc-list { display: none; }
            .frame-wrap iframe { height: 100vh; border: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Discharge Document Bundle — {{ $booking->patient?->full_name ?? 'Patient' }}</h1>
            <div class="note">If "Print All" doesn't include every document in your browser, use the individual links below instead.</div>
        </div>
        <button onclick="window.print()">Print All</button>
    </div>

    <div class="doc-list">
        @foreach($documents as $doc)
            <div class="doc-card">
                <span>{{ $doc['label'] }}</span>
                <a href="{{ route($doc['route'], ['slug' => $slug, 'bookingId' => $booking->id]) }}" target="_blank">
                    Open / Print Individually →
                </a>
            </div>
        @endforeach
    </div>

    <div class="frames">
        @foreach($documents as $doc)
            <div class="frame-wrap">
                <iframe src="{{ route($doc['route'], ['slug' => $slug, 'bookingId' => $booking->id]) }}" title="{{ $doc['label'] }}"></iframe>
            </div>
        @endforeach
    </div>
</body>
</html>
