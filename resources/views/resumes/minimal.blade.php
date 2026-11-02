@php
    $p = $d['personal'];
    $contact = collect([$p['email'] ?? '', $p['phone'] ?? '', $p['location'] ?? '', $p['website'] ?? '', $p['linkedin'] ?? ''])->filter()->implode('  /  ');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $p['name'] ?: $title }}</title>
<style>
    @@page { margin: 40px 44px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #333; line-height: 1.55; }
    @@media screen { body { max-width: 760px; margin: 0 auto; padding: 36px; } }
    h1 { font-size: 20px; font-weight: normal; margin: 0; letter-spacing: 1px; }
    .headline { font-size: 11px; color: #666; margin-top: 2px; }
    .contact { font-size: 9.5px; color: #777; margin-top: 4px; }
    h2.sec { font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; color: #777; margin: 18px 0 4px; }
    table.row { width: 100%; border-collapse: collapse; margin-top: 5px; }
    table.row td { padding: 0; vertical-align: top; }
    td.right { text-align: right; width: 28%; color: #777; }
    .row { margin-top: 5px; }
    .details { margin-top: 2px; }
    .muted { color: #777; }
</style>
</head>
<body>
    <h1>{{ $p['name'] }}</h1>
    @if(!empty($p['headline']))<div class="headline">{{ $p['headline'] }}</div>@endif
    @if($contact !== '')<div class="contact">{{ $contact }}</div>@endif
    @include('resumes._body')
</body>
</html>
