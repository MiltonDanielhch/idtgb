<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" dir="{{ __('voyager::generic.is_rtl') == 'true' ? 'rtl' : 'ltr' }}">
<head>
    <title>@yield('page_title', setting('admin.title') . " - " . setting('admin.description'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="assets-path" content="{{ route('voyager.voyager_assets') }}"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" /> --}}

    {{-- <link rel="stylesheet" href="{{ asset('css/dataTable/dataTable.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('css/dataTable/dataTable.css') }}">


    <link rel="stylesheet" href="{{ asset('css/style/page-title.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/small.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/h.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/input.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/label.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/p.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/li.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style/span.css') }}">
    {{-- <link rel="stylesheet" href="{{ asset('css/style/a.css') }}"> --}}


    {{-- Para person-select --}}
    <script>
        window.personListUrl = "{{ url('admin/ajax/personList') }}";
        window.defaultImage = "{{ asset('images/default.jpg') }}";
        window.storagePath = "{{ asset('storage') }}/";
    </script>



    <!-- Favicon -->
    <?php $admin_favicon = Voyager::setting('admin.icon_image', ''); ?>
    @if($admin_favicon == '')
        <link rel="shortcut icon" href="{{ voyager_asset('images/logo-icon.png') }}" type="image/png">
    @else
        <link rel="shortcut icon" href="{{ Voyager::image($admin_favicon) }}" type="image/png">
    @endif



    <!-- App CSS -->
    <link rel="stylesheet" href="{{ voyager_asset('css/app.css') }}">

    @yield('css')
    @if(__('voyager::generic.is_rtl') == 'true')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-rtl/3.4.0/css/bootstrap-rtl.css">
        <link rel="stylesheet" href="{{ voyager_asset('css/rtl.css') }}">
    @endif

    <!-- Few Dynamic Styles -->
    <style type="text/css">
        .voyager .side-menu .navbar-header {
            background:{{ config('voyager.primary_color','#22A7F0') }};
            border-color:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .widget .btn-primary{
            border-color:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .widget .btn-primary:focus, .widget .btn-primary:hover, .widget .btn-primary:active, .widget .btn-primary.active, .widget .btn-primary:active:focus{
            background:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .voyager .breadcrumb a{
            color:{{ config('voyager.primary_color','#22A7F0') }};
        }
    </style>

    @if(!empty(config('voyager.additional_css')))<!-- Additional CSS -->
        @foreach(config('voyager.additional_css') as $css)<link rel="stylesheet" type="text/css" href="{{ asset($css) }}">@endforeach
    @endif

    @yield('head')
    {{-- Solución para el error de fuente Voyager --}}
    <style>
    @font-face {
        font-family: 'voyager';
        src: url('{{ asset('fonts/voyager.eot') }}');
        src: url('{{ asset('fonts/voyager.eot') }}?#iefix') format('embedded-opentype'),
            url('{{ asset('fonts/voyager.woff') }}') format('woff'),
            url('{{ asset('fonts/voyager.ttf') }}') format('truetype'),
            url('{{ asset('fonts/voyager.svg') }}?v=1.0.0#voyager') format('svg');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    </style>
</head>

<body class="voyager @if(isset($dataType) && isset($dataType->slug)){{ $dataType->slug }}@endif">

<div id="voyager-loader" style="animation: none !important;">
    <?php $admin_loader_img = Voyager::setting('admin.loader', ''); ?>
    @if($admin_loader_img == '')
        <img src="{{ voyager_asset('images/logo-icon.png') }}" alt="Voyager Loader">
    @else
        <img src="{{ Voyager::image($admin_loader_img) }}" alt="Voyager Loader">
    @endif
</div>

@auth
    <?php
    if (\Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'http://') || \Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'https://')) {
        $user_avatar = Auth::user()->avatar;
    } else {
        $user_avatar = Voyager::image(Auth::user()->avatar);
    }
    ?>
@endauth

<div class="app-container">
    <div class="fadetoblack visible-xs"></div>
    <div class="row content-container">
        @include('voyager::dashboard.navbar')
        @include('voyager::dashboard.sidebar')
        <script>
            (function(){
                    var appContainer = document.querySelector('.app-container'),
                        sidebar = appContainer.querySelector('.side-menu'),
                        navbar = appContainer.querySelector('nav.navbar.navbar-top'),
                        loader = document.getElementById('voyager-loader'),
                        hamburgerMenu = document.querySelector('.hamburger'),
                        sidebarTransition = sidebar.style.transition,
                        navbarTransition = navbar.style.transition,
                        containerTransition = appContainer.style.transition;

                    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition =
                    appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition =
                    navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = 'none';

                    if (window.innerWidth > 768 && window.localStorage && window.localStorage['voyager.stickySidebar'] == 'true') {
                        appContainer.className += ' expanded no-animation';
                        loader.style.left = (sidebar.clientWidth/2)+'px';
                        hamburgerMenu.className += ' is-active no-animation';
                    }

                   navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = navbarTransition;
                   sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition = sidebarTransition;
                   appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition = containerTransition;
            })();
        </script>
        <!-- Main Content -->
        <div class="container-fluid">
            <div class="side-body padding-top">

                {{-- @php
                    $aux = new \App\Http\Controllers\Controller();

                    $solucionDigital = rescue(
                        fn () => \Illuminate\Support\Facades\DB::connection('solucionDigital')->table('settings')->get(),
                        fn () => collect() // Devuelve una colección vacía si falla
                    );
                @endphp

                @if($solucionDigital->isNotEmpty() && is_numeric($aux->payment_alert()) && setting('system.payment-alert'))
                    <div class="expiration-alert" style="
                        background: linear-gradient(135deg, #fffaf2 0%, #fff3e0 100%);
                        border-left: 4px solid #ff9800;
                        border-radius: 6px;
                        padding: 12px 15px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                        margin: 15px 0;
                        font-family: 'Segoe UI', sans-serif;
                        position: relative;
                        overflow: hidden;
                        font-size: 14px;
                    ">
                        <!-- Icono decorativo -->
                        <div style="
                            position: absolute;
                            right: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            opacity: 0.1;
                        ">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="#ff9800">
                                <path d="M12 2L1 21h22L12 2zm0 3.5L18.5 19h-13L12 5.5z"/>
                                <path d="M12 16c.6 0 1-.4 1-1s-.4-1-1-1-1 .4-1 1 .4 1 1 1zm0-4c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1s-1 .4-1 1v3c0 .6.4 1 1 1z"/>
                            </svg>
                        </div>

                        <!-- Contenido principal -->
                        <div style="position: relative; z-index: 2;">
                            <h3 style="
                                color: #e65100;
                                margin: 0 0 8px 0;
                                font-size: 15px;
                                font-weight: 600;
                                display: flex;
                                align-items: center;
                                gap: 8px;
                            ">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e65100" style="flex-shrink: 0;">
                                    <path d="M12 2L1 21h22L12 2zm0 3.5L18.5 19h-13L12 5.5z"/>
                                    <path d="M12 16c.6 0 1-.4 1-1s-.4-1-1-1-1 .4-1 1 .4 1 1 1zm0-4c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1s-1 .4-1 1v3c0 .6.4 1 1 1z"/>
                                </svg>
                                ¡Servicio Próximo a Finalizar!
                            </h3>
                            <p style="
                                color: #5f2120;
                                font-size: 13px;
                                line-height: 1.5;
                                margin-bottom: 10px;
                            ">
                                Su servicio finaliza @if($aux->payment_alert()==0) el día de hoy. @else en <strong>{{$aux->payment_alert()}} días.@endif</strong>
                            </p>

                            <div style="
                                background: white;
                                border-radius: 4px;
                                padding: 8px 10px;
                                box-shadow: 0 1px 4px rgba(0,0,0,0.05);
                                margin-bottom: 10px;
                            ">
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 120px;">
                                        <p style="font-weight: 600; margin: 0 0 3px 0; font-size: 13px; color: #333;">📞 Teléfono</p>
                                        <p style="margin: 0; font-size: 12px; color: #555;">{{$solucionDigital->where('key','contact.phone')->first()->value ?? ''}}</p>
                                    </div>
                                    <div style="flex: 1; min-width: 120px;">
                                        <p style="font-weight: 600; margin: 0 0 3px 0; font-size: 13px; color: #333;">✉️ Email</p>
                                        <p style="margin: 0; font-size: 12px; color: #555;">{{$solucionDigital->where('key','contact.email')->first()->value ?? ''}}</p>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <a href="https://wa.me/{{$solucionDigital->where('key','contact.phone')->first()->value}}" target="_blank" style="
                                    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
                                    color: white;
                                    border: none;
                                    padding: 6px 12px;
                                    border-radius: 4px;
                                    font-size: 13px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                    transition: all 0.2s;
                                    flex: 1;
                                    min-width: 120px;
                                    text-decoration: none;
                                    text-align: center;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 5px;
                                " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.15)'"
                                onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    WhatsApp
                                </a>

                                <button style="
                                    background: linear-gradient(135deg, #ff9800 0%, #fb8c00 100%);
                                    color: white;
                                    border: none;
                                    padding: 6px 12px;
                                    border-radius: 4px;
                                    font-size: 13px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                    transition: all 0.2s;
                                    flex: 1;
                                    min-width: 120px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 5px;
                                " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.15)'"
                                onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                                    </svg>
                                    Renovar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($solucionDigital->isNotEmpty() && $aux->payment_alert() === 'finalizado')
                    <div class="payment-alert" style="
                        background: linear-gradient(135deg, #fff8f8 0%, #ffebee 100%);
                        border-left: 4px solid #ff5252;
                        border-radius: 6px;
                        padding: 12px 15px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                        margin: 15px 0;
                        font-family: 'Segoe UI', sans-serif;
                        position: relative;
                        overflow: hidden;
                        font-size: 14px;
                    ">
                        <!-- Icono decorativo -->
                        <div style="
                            position: absolute;
                            right: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            opacity: 0.1;
                        ">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="#ff5252">
                                <path d="M12 2L1 21h22L12 2zm0 3.5L18.5 19h-13L12 5.5z"/>
                                <path d="M12 16c.6 0 1-.4 1-1s-.4-1-1-1-1 .4-1 1 .4 1 1 1zm0-4c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1s-1 .4-1 1v3c0 .6.4 1 1 1z"/>
                            </svg>
                        </div>

                        <!-- Contenido principal -->
                        <div style="position: relative; z-index: 2;">
                            <h3 style="
                                color: #d32f2f;
                                margin: 0 0 8px 0;
                                font-size: 15px;
                                font-weight: 600;
                                display: flex;
                                align-items: center;
                                gap: 8px;
                            ">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#d32f2f" style="flex-shrink: 0;">
                                    <path d="M12 2L1 21h22L12 2zm0 3.5L18.5 19h-13L12 5.5z"/>
                                    <path d="M12 16c.6 0 1-.4 1-1s-.4-1-1-1-1 .4-1 1 .4 1 1 1zm0-4c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1s-1 .4-1 1v3c0 .6.4 1 1 1z"/>
                                </svg>
                                ¡Atención: Pago Pendiente!
                            </h3>

                            <p style="
                                color: #5f2120;
                                font-size: 13px;
                                line-height: 1.5;
                                margin-bottom: 10px;
                            ">
                                Para continuar con el servicio sin interrupciones, contacte al administrador:
                            </p>

                            <div style="
                                background: white;
                                border-radius: 4px;
                                padding: 8px 10px;
                                box-shadow: 0 1px 4px rgba(0,0,0,0.05);
                                margin-bottom: 10px;
                            ">
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 120px;">
                                        <p style="font-weight: 600; margin: 0 0 3px 0; font-size: 13px; color: #333;">📞 Teléfono/WhatsApp</p>
                                        <p style="margin: 0; font-size: 12px; color: #555;">{{$solucionDigital->where('key','contact.phone')->first()->value ?? ''}}</p>
                                    </div>
                                    <div style="flex: 1; min-width: 120px;">
                                        <p style="font-weight: 600; margin: 0 0 3px 0; font-size: 13px; color: #333;">✉️ Email</p>
                                        <p style="margin: 0; font-size: 12px; color: #555;">{{$solucionDigital->where('key','contact.email')->first()->value ?? ''}}</p>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <a href="https://wa.me/{{$solucionDigital->where('key','contact.phone')->first()->value}}" target="_blank" style="
                                    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
                                    color: white;
                                    border: none;
                                    padding: 6px 12px;
                                    border-radius: 4px;
                                    font-size: 13px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                    transition: all 0.2s;
                                    flex: 1;
                                    min-width: 120px;
                                    text-decoration: none;
                                    text-align: center;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 5px;
                                " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.15)'"
                                onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    WhatsApp
                                </a>

                                <button style="
                                    background: linear-gradient(135deg, #ff9800 0%, #fb8c00 100%);
                                    color: white;
                                    border: none;
                                    padding: 6px 12px;
                                    border-radius: 4px;
                                    font-size: 13px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                    transition: all 0.2s;
                                    flex: 1;
                                    min-width: 120px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 5px;
                                " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.15)'"
                                onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                                    </svg>
                                    Renovar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif --}}


                @yield('page_header')
                <div id="voyager-notifications"></div>
                @yield('content')
            </div>
        </div>
    </div>
</div>
@include('voyager::partials.app-footer')

<!-- Javascript Libs -->
<script type="text/javascript" src="{{ asset('js/dataTable/dataTable.js') }}"></script>


{{-- <script src="{{ route('voyager.voyager_assets') }}/js/app.js"></script> --}}

<script type="text/javascript" src="{{ voyager_asset('js/app.js') }}"></script>

@if (setting('configuracion.navidad'))
    <script type="text/javascript" src="{{asset('navidad/snow.js')}}"></script>
    <script type="text/javascript">
        $(function() {
            $(document).snow({ SnowImage: "{{ asset('navidad/image/icon.png') }}", SnowImage2: "{{ asset('navidad/image/caramelo.png') }}" });
        });
    </script>
@endif

<script>
    @if(Session::has('alerts'))
        let alerts = {!! json_encode(Session::get('alerts')) !!};
        helpers.displayAlerts(alerts, toastr);
    @endif

    @if(Session::has('message'))

    // TODO: change Controllers to use AlertsMessages trait... then remove this
    var alertType = {!! json_encode(Session::get('alert-type', 'info')) !!};
    var alertMessage = {!! json_encode(Session::get('message')) !!};
    var alerter = toastr[alertType];

    if (alerter) {
        alerter(alertMessage);
    } else {
        toastr.error("toastr alert-type " + alertType + " is unknown");
    }
    @endif
</script>
@include('voyager::media.manager')
@yield('javascript')
@stack('javascript')
@if(!empty(config('voyager.additional_js')))<!-- Additional Javascript -->
    @foreach(config('voyager.additional_js') as $js)<script type="text/javascript" src="{{ asset($js) }}"></script>@endforeach
@endif

{{-- Loading --}}
<script src="{{ asset('vendor/loading/loading.js') }}"></script>
<link rel="stylesheet" href="{{ asset('vendor/loading/loading.css') }}">

</body>
</html>
