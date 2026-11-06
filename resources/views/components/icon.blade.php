@props(['name', 'size' => 18, 'class' => '', 'strokeWidth' => 2])

@php
    $style = "display: inline-block; vertical-align: middle; width: {$size}px; height: {$size}px; flex-shrink: 0;";
@endphp

@switch($name)
    @case('recycle')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5" />
            <path d="M11 19h8.2a1.8 1.8 0 0 0 1.6-1 1.8 1.8 0 0 0 0-1.8L18 11" />
            <path d="m11 13 3 3-3 3" />
            <path d="m18 11 3-3-3-3" />
            <path d="m7 7 3-3-3-3" />
            <path d="M10.8 5.6A1.8 1.8 0 0 0 10 4a1.8 1.8 0 0 0-1.6 1L4.5 12" />
        </svg>
        @break

    @case('credit-card')
    @case('wallet')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="14" x="2" y="5" rx="2" />
            <line x1="2" x2="22" y1="10" y2="10" />
        </svg>
        @break

    @case('users')
    @case('community')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        @break

    @case('user')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </svg>
        @break

    @case('scale')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z" />
            <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z" />
            <path d="M7 21h10" />
            <path d="M12 3v18" />
            <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2" />
        </svg>
        @break

    @case('tag')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z" />
            <circle cx="7" cy="7" r="1.5" fill="currentColor" />
        </svg>
        @break

    @case('chart')
    @case('trending-up')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
            <polyline points="16 7 22 7 22 13" />
        </svg>
        @break

    @case('bar-chart')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" x2="18" y1="20" y2="10" />
            <line x1="12" x2="12" y1="20" y2="4" />
            <line x1="6" x2="6" y1="20" y2="14" />
        </svg>
        @break

    @case('shield')
    @case('shield-check')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
            <path d="m9 12 2 2 4-4" />
        </svg>
        @break

    @case('book')
    @case('book-open')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
        </svg>
        @break

    @case('globe')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
            <path d="M2 12h20" />
        </svg>
        @break

    @case('log-out')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" x2="9" y1="12" y2="12" />
        </svg>
        @break

    @case('search')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
        </svg>
        @break

    @case('lock')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
        @break

    @case('key')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" />
            <path d="m21 2-9.6 9.6" />
            <circle cx="7.5" cy="16.5" r="5.5" />
        </svg>
        @break

    @case('check')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12" />
        </svg>
        @break

    @case('check-circle')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
        @break

    @case('alert-circle')
    @case('alert-triangle')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" x2="12" y1="8" y2="12" />
            <line x1="12" x2="12.01" y1="16" y2="16" />
        </svg>
        @break

    @case('download')
    @case('arrow-down-circle')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" x2="12" y1="15" y2="3" />
        </svg>
        @break

    @case('upload')
    @case('arrow-up-circle')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" x2="12" y1="3" y2="15" />
        </svg>
        @break

    @case('deposit')
    @case('arrow-down')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" x2="12" y1="5" y2="19" />
            <polyline points="19 12 12 19 5 12" />
        </svg>
        @break

    @case('withdrawal')
    @case('arrow-up')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" x2="12" y1="19" y2="5" />
            <polyline points="5 12 12 5 19 12" />
        </svg>
        @break

    @case('trash')
    @case('trash-2')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18" />
            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
            <line x1="10" x2="10" y1="11" y2="17" />
            <line x1="14" x2="14" y1="11" y2="17" />
        </svg>
        @break

    @case('file-text')
    @case('receipt')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" x2="8" y1="13" y2="13" />
            <line x1="16" x2="8" y1="17" y2="17" />
            <polyline points="10 9 9 9 8 9" />
        </svg>
        @break

    @case('edit')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            <path d="m15 5 4 4" />
        </svg>
        @break

    @case('sparkles')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
            <path d="M5 3v4" />
            <path d="M19 17v4" />
            <path d="M3 5h4" />
            <path d="M17 19h4" />
        </svg>
        @break

    @case('package')
    @case('box')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="m7.5 4.27 9 5.15" />
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
            <path d="m3.3 7 8.7 5 8.7-5" />
            <path d="M12 22V12" />
        </svg>
        @break

    @case('leaf')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
        </svg>
        @break

    @case('phone')
    @case('smartphone')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect width="14" height="20" x="5" y="2" rx="2" ry="2" />
            <path d="M12 18h.01" />
        </svg>
        @break

    @case('message-square')
    @case('chat')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </svg>
        @break

    @case('coins')
    @case('dollar-sign')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="8" r="6" />
            <path d="M18.09 10.37A6 6 0 1 1 10.34 18" />
            <path d="M7 6h2v4H7z" />
        </svg>
        @break

    @case('trophy')
    @case('award')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
            <path d="M4 22h16" />
            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
        </svg>
        @break

    @case('home')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
        @break

    @case('map-pin')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
            <circle cx="12" cy="10" r="3" />
        </svg>
        @break

    @case('help-circle')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
            <line x1="12" x2="12.01" y1="17" y2="17" />
        </svg>
        @break

    @case('clock')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
        </svg>
        @break

    @case('save')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
            <polyline points="17 21 17 13 7 13 7 21" />
            <polyline points="7 3 7 8 15 8" />
        </svg>
        @break

    @case('eye')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        @break

    @case('eye-off')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
            <line x1="2" x2="22" y1="2" y2="22" />
        </svg>
        @break

    @case('camera')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
            <circle cx="12" cy="13" r="3"/>
        </svg>
        @break

    @case('close')
    @case('x')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
        @break

    @case('plus')
    @case('add')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        @break

    @case('chevron-down')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9" />
        </svg>
        @break

    @case('chevron-up')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15" />
        </svg>
        @break

    @case('chevron-right')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6" />
        </svg>
        @break

    @case('chevron-left')
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6" />
        </svg>
        @break

    @default
        <svg style="{{ $style }}" class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" x2="12" y1="8" y2="12" />
            <line x1="12" x2="12.01" y1="16" y2="16" />
        </svg>
@endswitch
