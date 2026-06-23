@props([
    'title',
    'description',
    'keywords' => '',
    'canonical' => null,
    'ogType' => 'website',
    'ogImage' => null,
])

@php
    use App\Models\SiteSetting;

    $canonical = $canonical ?? url()->current();
    $siteName = SiteSetting::generalConfig()['site_name'];
    $fullTitle = str_contains($title, $siteName) ? $title : $title . ' | ' . $siteName;
    $ogImage = $ogImage ?: SiteSetting::faviconUrl();
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}">
@endif
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="en_IN">
<meta property="og:image" content="{{ $ogImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">
