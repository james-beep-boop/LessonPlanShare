@props(['plan'])
@php
    $displayDesc = $plan->description
        ? preg_replace('/^Introduction to\b/i', 'Intro to', $plan->description)
        : null;
    $excerpt = $displayDesc
        ? mb_substr($displayDesc, 0, 24)
        : mb_substr($plan->file_name ?? '', 0, 24);
@endphp
{{ $excerpt ?: '—' }}
