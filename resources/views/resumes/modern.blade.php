@php
    $p = $d['personal'];
    $contactItems = collect([$p['email'] ?? '', $p['phone'] ?? '', $p['location'] ?? '', $p['website'] ?? '', $p['linkedin'] ?? ''])->filter();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $p['name'] ?: $title }}</title>
<style>
    @@page { margin: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #222; line-height: 1.5; margin: 0; }
    @@media screen { body { max-width: 820px; margin: 0 auto; } }
    table.layout { width: 100%; border-collapse: collapse; }
    td.side { width: 30%; background: #1e293b; color: #ffffff; padding: 36px 20px; vertical-align: top; }
    td.main { width: 70%; padding: 36px 30px; vertical-align: top; }
    td.side h1 { font-size: 19px; margin: 0; }
    td.side .headline { font-size: 11px; color: #cbd5e1; margin-top: 4px; }
    td.side h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin: 18px 0 4px; }
    td.side .item { font-size: 9.5px; margin-bottom: 3px; color: #e2e8f0; }
    h2.sec { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #1e293b; border-bottom: 2px solid #6366f1; padding-bottom: 2px; margin: 0 0 6px; margin-top: 16px; }
    table.row { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.row td { padding: 0; vertical-align: top; }
    td.right { text-align: right; width: 28%; color: #555; }
    .row { margin-top: 6px; }
    .details { margin-top: 2px; }
    .muted { color: #666; }
</style>
</head>
<body>
<table class="layout"><tr>
    <td class="side">
        <h1>{{ $p['name'] }}</h1>
        @if(!empty($p['headline']))<div class="headline">{{ $p['headline'] }}</div>@endif

        @if($contactItems->isNotEmpty())
            <h3>Contact</h3>
            @foreach($contactItems as $c)<div class="item">{{ $c }}</div>@endforeach
        @endif

        @if(count($d['skills'] ?? []))
            <h3>Skills</h3>
            @foreach($d['skills'] as $skill)<div class="item">{{ $skill }}</div>@endforeach
        @endif
    </td>
    <td class="main">
        @include('resumes._body', ['showSkills' => false])
    </td>
</tr></table>
</body>
</html>
