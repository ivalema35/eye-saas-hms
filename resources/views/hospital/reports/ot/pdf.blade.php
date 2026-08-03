<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $label }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1A202C;
        }
        .header {
            border-bottom: 2px solid #1B4F72;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1B4F72;
            margin: 0;
        }
        .meta {
            margin-top: 4px;
            color: #4A5568;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #1B4F72;
            color: #fff;
            text-align: left;
            padding: 7px;
            font-size: 11px;
        }
        tbody td {
            border: 1px solid #E2E8F0;
            padding: 6px;
            font-size: 11px;
        }
        tbody tr:nth-child(even) {
            background: #F8FAFC;
        }
        .text-center {
            text-align: center;
        }
        .muted {
            color: #4A5568;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">{{ $label }}</p>
        <div class="meta">
            Generated: {{ now()->format('d M Y, h:i A') }} | Date Range: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}" class="text-center muted">No data for the selected range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
