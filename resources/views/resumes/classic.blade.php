@php
    $p = $d['personal'];
    $contact = collect([$p['email'] ?? '', $p['phone'] ?? '', $p['location'] ?? '', $p['website'] ?? '', $p['linkedin'] ?? ''])->filter()->implode('  |  ');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $p['name'] ?: $title }}</title>
<style>
    @@page { margin: 36px 40px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; line-height: 1.5; }
    @@media screen { body { max-width: 780px; margin: 0 auto; padding: 32px; } }
    h1 { font-size: 24px; margin: 0; text-align: center; }
    .headline { text-align: center; font-size: 13px; color: #444; margin-top: 2px; }
    .contact { text-align: center; font-size: 10px; color: #555; margin-top: 6px; }
    h2.sec { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #222; padding-bottom: 2px; margin: 16px 0 6px; }
    table.row { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.row td { padding: 0; vertical-align: top; }
    td.right { text-align: right; width: 28%; color: #555; }
    .row { margin-top: 6px; }
    .details { margin-top: 2px; }
    .muted { color: #666; }
</style>
</head>
<body>
    <h1>{{ $p['name'] }}</h1>
    @if(!empty($p['headline']))<div class="headline">{{ $p['headline'] }}</div>@endif
    @if($contact !== '')<div class="contact">{{ $contact }}</div>@endif
    @include('resumes._body')
</body>
</html>
