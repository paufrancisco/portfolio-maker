@if(!empty($d['summary']))
    <h2 class="sec">Summary</h2>
    <div class="details">{!! nl2br(e($d['summary'])) !!}</div>
@endif

@if(count($d['experience'] ?? []))
    <h2 class="sec">Experience</h2>
    @foreach($d['experience'] as $x)
        <table class="row"><tr>
            <td><strong>{{ $x['role'] ?? '' }}</strong>@if(!empty($x['company'])), {{ $x['company'] }}@endif</td>
            <td class="right">{{ $x['period'] ?? '' }}</td>
        </tr></table>
        @if(!empty($x['details']))<div class="details">{!! nl2br(e($x['details'])) !!}</div>@endif
    @endforeach
@endif

@if(count($d['education'] ?? []))
    <h2 class="sec">Education</h2>
    @foreach($d['education'] as $x)
        <table class="row"><tr>
            <td><strong>{{ $x['school'] ?? '' }}</strong>@if(!empty($x['degree'])), {{ $x['degree'] }}@endif</td>
            <td class="right">{{ $x['period'] ?? '' }}</td>
        </tr></table>
        @if(!empty($x['details']))<div class="details">{!! nl2br(e($x['details'])) !!}</div>@endif
    @endforeach
@endif

@if(($showSkills ?? true) && count($d['skills'] ?? []))
    <h2 class="sec">Skills</h2>
    <div class="details">{{ implode(', ', $d['skills']) }}</div>
@endif

@if(count($d['projects'] ?? []))
    <h2 class="sec">Projects</h2>
    @foreach($d['projects'] as $x)
        <div class="row"><strong>{{ $x['name'] ?? '' }}</strong>@if(!empty($x['link'])) <span class="muted">({{ $x['link'] }})</span>@endif</div>
        @if(!empty($x['details']))<div class="details">{!! nl2br(e($x['details'])) !!}</div>@endif
    @endforeach
@endif
