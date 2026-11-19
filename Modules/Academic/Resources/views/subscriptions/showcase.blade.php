<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Suscripciones | {{ $company->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,500;1,700&display=swap">
    <style>
        /* Vista de suscripciones: misma identidad que la landing de referencia
           (bandas azul / blanco / gris, contenedor de 1160 px y acentos rojos). */
        :root {
            --navy: #00005A; --navy2: #07073F; --red: #ED1B26; --red2: #C3121B;
            --gray: #F5F5F7; --ink: #15152E; --muted: #4A4C66; --line: #E1E2EC;
            --pink: #FDEBEC; --onnavy: #C9CAF0; --white: #FFFFFF;
            --f: "Montserrat", "Segoe UI", Arial, sans-serif;
            color-scheme: light;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        body {
            margin: 0; background: var(--white); color: var(--ink);
            font-family: var(--f); font-size: 16px; line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4 { margin: 0; line-height: 1.12; text-wrap: balance; font-weight: 900; }
        p { margin: 0; }
        a { color: inherit; }
        img { display: block; max-width: 100%; }
        .wrap {
            max-width: 1160px; margin: 0 auto;
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: max(16px, env(safe-area-inset-right, 0px));
        }
        section .wrap { padding-block: 88px; }
        .kicker {
            display: flex; align-items: center; gap: 10px; font-size: 12.5px;
            font-weight: 800; letter-spacing: .16em; text-transform: uppercase;
            color: var(--red);
        }
        .kicker::before { content: ""; width: 12px; height: 12px; background: var(--red); flex: none; }
        h2 { font-size: clamp(30px, 4.2vw, 46px); margin-top: 14px; color: var(--ink); }
        h2 em, h1 em { font-style: normal; color: var(--red); }
        .lead { font-size: 18px; color: var(--muted); max-width: 68ch; margin-top: 16px; }
        .navy { background: var(--navy); color: var(--white); }
        .gridbg {
            background-image:
                linear-gradient(rgba(255, 255, 255, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .045) 1px, transparent 1px);
            background-size: 44px 44px;
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            font: 800 15px/1 var(--f); padding: 16px 24px; border-radius: 12px;
            text-decoration: none; border: 2px solid transparent; cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }
        .btn:focus-visible { outline: 3px solid #FFB800; outline-offset: 3px; }
        .btn-red { background: var(--red); color: var(--white); box-shadow: 0 10px 24px rgba(237, 27, 38, .35); }
        .btn-red:hover { transform: translateY(-2px); }
        .btn-ghost { border-color: rgba(255, 255, 255, .55); color: var(--white); }
        .btn-ghost:hover { border-color: var(--white); }

        .topbar { background: var(--red); color: var(--white); font-weight: 700; font-size: 14px; text-align: center; }
        .topbar .wrap { padding-block: 10px; }
        .topbar a { font-weight: 800; text-underline-offset: 3px; }

        .head { background: var(--navy); border-bottom: 1px solid rgba(255, 255, 255, .08); }
        .head .wrap { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-block: 16px; }
        .head nav { display: flex; gap: 26px; font-size: 14px; font-weight: 700; }
        .head nav a { color: var(--onnavy); text-decoration: none; }
        .head nav a:hover { color: var(--white); }
        .logo { height: 42px; width: auto; }

        .hero .wrap {
            display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
            gap: 48px; align-items: center; padding-block: 64px 72px;
        }
        .hero .tag {
            font-size: 12.5px; font-weight: 800; letter-spacing: .16em;
            color: #FF5A62; display: flex; gap: 10px; align-items: center;
        }
        .hero .tag::before { content: ""; width: 12px; height: 12px; background: var(--red); }
        .hero h1 { font-size: clamp(38px, 5.2vw, 60px); margin-top: 16px; color: var(--white); letter-spacing: -.01em; }
        .hero .sub { font-size: 19px; color: var(--onnavy); margin-top: 18px; max-width: 48ch; }
        .hero .sub b { color: var(--white); }
        .pricebox {
            margin-top: 28px; background: var(--white); color: var(--ink);
            border-radius: 16px; padding: 18px 20px; display: flex;
            gap: 18px; align-items: center; max-width: 540px;
        }
        .badge15 { flex: none; background: var(--red); color: var(--white); border-radius: 12px; padding: 10px 14px; text-align: center; min-width: 64px; }
        .badge15 b { display: block; font-size: 20px; font-weight: 900; line-height: 1; }
        .badge15 span { font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .pricebox .was { font-size: 13px; font-weight: 700; color: var(--muted); }
        .pricebox .now { font-size: 40px; font-weight: 900; color: var(--navy); line-height: 1.05; font-variant-numeric: tabular-nums; }
        .pricebox .now small { font-size: 18px; }
        .pricebox .eq { font-size: 13px; color: var(--muted); font-weight: 600; }
        .pricebox .usd-line { display: flex; align-items: baseline; flex-wrap: wrap; gap: 8px; margin-top: 6px; font-size: 18px; font-weight: 900; color: var(--navy); }
        .pricebox .usd-line small { font-size: 12px; color: var(--red); font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .pricebox .usd-line span { font-size: 12.5px; color: var(--muted); font-weight: 600; }
        .checks { list-style: none; padding: 0; margin: 24px 0 0; display: grid; gap: 10px; }
        .checks li { display: flex; gap: 12px; align-items: flex-start; font-weight: 600; font-size: 15.5px; }
        .checks li::before {
            content: ""; flex: none; width: 22px; height: 22px; margin-top: 1px;
            border-radius: 50%;
            background: var(--red) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M6 12.5l4 4L18 8' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/16px no-repeat;
        }
        .ctas { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
        .hero-vis { min-width: 0; }
        .stack { display: grid; gap: 14px; }
        .mini {
            background: var(--white); color: var(--ink); border-radius: 16px;
            padding: 16px 18px; border-left: 4px solid var(--red);
            box-shadow: 0 18px 40px rgba(0, 0, 30, .45);
        }
        .mini:nth-child(2) { margin-left: 28px; }
        .mini:nth-child(3) { margin-left: 56px; }
        .mini .t { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
        .mini .t b { font-size: 17px; color: var(--navy); line-height: 1.2; }
        .mini .t small { flex: none; font-size: 10px; font-weight: 800; letter-spacing: .12em; color: var(--red); text-transform: uppercase; }
        .mini .p { display: flex; gap: 16px; align-items: baseline; margin-top: 8px; }
        .mini .p em { font-style: normal; font-size: 26px; font-weight: 900; color: var(--navy); font-variant-numeric: tabular-nums; }
        .mini .p span { font-size: 13px; color: var(--muted); font-weight: 700; }
        .mini .d {
            font-size: 12.5px; color: var(--muted); margin-top: 4px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        .gray { background: var(--gray); }
        .plans { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-top: 40px; align-items: stretch; }
        .plan {
            border: 1px solid var(--line); border-radius: 20px; padding: 30px 26px;
            display: flex; flex-direction: column; background: var(--white); position: relative;
        }
        .plan .flag {
            position: absolute; top: -14px; left: 26px; background: var(--navy); color: var(--white);
            font-size: 11px; font-weight: 800; letter-spacing: .14em; padding: 6px 10px; border-radius: 6px;
        }
        .plan h3 { font-size: 27px; color: var(--navy); }
        .plan .users { font-size: 12px; font-weight: 800; letter-spacing: .14em; color: var(--red); margin-top: 4px; text-transform: uppercase; }
        .plan .for { font-size: 14.5px; line-height: 1.55; color: var(--muted); margin-top: 10px; min-height: 46px; }
        .plan .for b, .plan .for strong { font-weight: 900; }
        /* Texto libre encima del precio: tenue, pequeño y subrayado cuando no tiene formato. */
        .plan .before {
            font-size: 13px; line-height: 1.45; font-weight: 600; color: #6C6E86;
            margin-bottom: 8px; overflow-wrap: break-word;
        }
        .plan .before.plain { text-decoration: underline; }
        .plan .before b, .plan .before strong { font-weight: 800; }
        /* Texto libre debajo del precio. */
        .plan .after {
            font-size: 13px; line-height: 1.45; font-weight: 600; color: #6C6E86;
            margin-top: 10px; overflow-wrap: break-word;
        }
        .plan .after b, .plan .after strong { font-weight: 800; }
        .plan .pl { border-top: 1px solid var(--line); margin-top: 18px; padding-top: 18px; }
        .plan .cur { display: flex; align-items: baseline; gap: 5px; }
        .plan .cur + .cur { margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line); }
        .plan .sym { font-size: 16px; font-weight: 800; letter-spacing: .04em; color: var(--red); text-transform: uppercase; }
        .plan .cur.usd .sym { font-size: 12px; }
        .plan .amt { font-size: 42px; font-weight: 900; color: var(--navy); line-height: 1.05; font-variant-numeric: tabular-nums; }
        .plan .amt.usd { font-size: 28px; }
        .plan .per { font-size: 12.5px; color: var(--muted); font-weight: 600; margin-top: 2px; }
        .plan .consultar { font-size: 24px; color: var(--muted); }
        .plan .save {
            display: inline-block; margin-top: 12px; background: var(--pink); color: var(--red2);
            font-weight: 800; font-size: 12.5px; padding: 6px 10px; border-radius: 6px;
        }
        .plan ul { list-style: none; margin: 20px 0 0; padding: 0; display: grid; gap: 10px; flex: 1; align-content: start; }
        .plan li { display: flex; gap: 10px; font-size: 14.5px; align-items: flex-start; }
        .plan li::before { content: "✓"; color: var(--red); font-weight: 900; flex: none; }
        .plan.star {
            background: var(--navy); color: var(--white); border-color: var(--navy);
            box-shadow: 0 22px 50px rgba(0, 0, 90, .28); transform: translateY(-8px);
        }
        .plan.star .flag { background: var(--red); }
        .plan.star h3, .plan.star .amt, .plan.star .sym { color: var(--white); }
        .plan.star .for, .plan.star .per, .plan.star .before, .plan.star .after { color: var(--onnavy); }
        .plan.star .pl { border-color: rgba(255, 255, 255, .18); }
        .plan.star .cur + .cur { border-color: rgba(255, 255, 255, .18); }
        .plan.star .save { background: rgba(255, 255, 255, .14); color: var(--white); }

        .incl { margin-top: 34px; background: var(--gray); border-radius: 18px; padding: 28px; }
        .incl h4 { font-size: 13px; letter-spacing: .16em; color: var(--navy); text-transform: uppercase; }
        .incl ul { list-style: none; padding: 0; margin: 16px 0 0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px 24px; }
        .incl li { position: relative; padding-left: 19px; font-size: 14.5px; }
        .incl li::before { content: ""; position: absolute; left: 0; top: 8px; width: 9px; height: 9px; background: var(--red); }

        .empty { margin-top: 34px; background: var(--gray); border-radius: 18px; padding: 34px; text-align: center; }
        .empty h3 { font-size: 22px; color: var(--navy); }
        .empty p { color: var(--muted); margin-top: 8px; }
        .empty a { color: var(--red2); font-weight: 800; }

        .foot { background: var(--navy2); color: var(--onnavy); font-size: 14px; }
        .foot .wrap { padding-block: 44px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 24px; }
        .foot .brand { max-width: 420px; }
        .foot .logo { height: 34px; margin-bottom: 14px; }
        .foot h5 { margin: 0 0 10px; font-size: 12px; letter-spacing: .16em; color: var(--white); }
        .foot ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
        .foot a { text-decoration: none; }
        .foot a:hover { color: var(--white); }
        .foot .base { border-top: 1px solid rgba(255, 255, 255, .1); }
        .foot .base .wrap { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding-block: 18px; font-size: 12.5px; }

        .mbar { display: none; }

        @media (max-width: 980px) {
            .hero .wrap { grid-template-columns: minmax(0, 1fr); gap: 32px; }
            .head nav { display: none; }
            .plans { grid-template-columns: minmax(0, 1fr); }
            .plan.star { transform: none; }
            .mini:nth-child(2) { margin-left: 16px; }
            .mini:nth-child(3) { margin-left: 32px; }
            .incl ul { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 600px) {
            section .wrap { padding-block: 60px; }
            .hero .wrap { padding-block: 36px 44px; }
            .hero .sub { font-size: 17px; }
            .logo { height: 34px; }
            .pricebox { gap: 14px; padding: 14px; }
            .pricebox .now { font-size: 34px; }
            .ctas .btn { width: 100%; }
            .incl ul { grid-template-columns: minmax(0, 1fr); }
            .foot .wrap { padding-block: 36px; }
            .mbar {
                display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 20;
                background: var(--white); border-top: 1px solid var(--line);
                box-shadow: 0 -6px 20px rgba(0, 0, 40, .12);
                padding: 10px max(16px, env(safe-area-inset-right, 0px)) calc(10px + env(safe-area-inset-bottom, 0px)) max(16px, env(safe-area-inset-left, 0px));
                align-items: center; justify-content: space-between; gap: 12px;
            }
            .mbar b { display: block; font-size: 20px; color: var(--navy); font-weight: 900; line-height: 1.1; }
            .mbar span { font-size: 11.5px; color: var(--muted); font-weight: 600; }
            .mbar .btn { padding: 14px 18px; font-size: 15px; }
            body { padding-bottom: 84px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="wrap">
            Suscripciones vigentes · Precios en soles{{ $plans->contains(fn ($plan) => $plan['usd'] !== null) ? ' y dólares' : '' }}
            · <a href="#planes">Ver planes →</a>
        </div>
    </div>

    <header class="head">
        <div class="wrap">
            <a href="{{ route('dashboard') }}" aria-label="{{ $company->name ?? config('app.name') }}">
                <img class="logo" src="{{ $logo }}" alt="{{ $company->name ?? config('app.name') }}">
            </a>
            <nav aria-label="Secciones">
                <a href="#planes">Planes</a>
                @if(count($included) > 0)
                    <a href="#incluye">Qué incluye</a>
                @endif
                <a href="{{ route('dashboard') }}">Volver al panel</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero navy gridbg" id="inicio">
            <div class="wrap">
                <div>
                    <div class="tag">SUSCRIPCIONES</div>
                    <h1>Tu formación continua, <em>en un solo plan</em></h1>
                    <p class="sub">
                        Revisa los planes vigentes con todos sus detalles y el precio en
                        <b>soles</b>{{ $plans->contains(fn ($plan) => $plan['usd'] !== null) ? ' y en dólares' : '' }}.
                    </p>

                    @if($featured && ($featured['pen'] || $featured['usd']))
                        <div class="pricebox">
                            <div class="badge15">
                                <b>{{ $featured['pen'] ? 'S/' : 'US$' }}</b>
                                <span>{{ $featured['pen'] ? 'Soles' : 'Dólares' }}</span>
                            </div>
                            <div>
                                <div class="was">{{ $featured['title'] }}@if($featured['period']) · {{ $featured['period'] }}@endif</div>
                                @if($featured['pen'])
                                    <div class="now"><small>S/ </small>{{ $featured['pen']['formatted'] }}</div>
                                    @if($featured['pen']['detail'])
                                        <div class="eq">{{ $featured['pen']['detail'] }}</div>
                                    @endif
                                @else
                                    <div class="now"><small>US$ </small>{{ $featured['usd']['formatted'] }}</div>
                                    @if($featured['usd']['detail'])
                                        <div class="eq">{{ $featured['usd']['detail'] }}</div>
                                    @endif
                                @endif
                                @if($featured['pen'] && $featured['usd'])
                                    <div class="usd-line">
                                        <small>US$</small> {{ $featured['usd']['formatted'] }}
                                        @if($featured['usd']['detail'])
                                            <span>{{ $featured['usd']['detail'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($featured && count($featured['details']) > 0)
                        <ul class="checks">
                            @foreach(array_slice($featured['details'], 0, 3) as $detail)
                                <li>{{ $detail }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="ctas">
                        <a class="btn btn-red" href="#planes">Ver todos los planes</a>
                        <a class="btn btn-ghost" href="{{ route('dashboard') }}">Volver al panel</a>
                    </div>
                </div>

                @if($plans->isNotEmpty())
                    <div class="hero-vis" aria-hidden="true">
                        <div class="stack">
                            @foreach($plans->take(3) as $plan)
                                <div class="mini">
                                    <div class="t">
                                        <b>{{ $plan['title'] }}</b>
                                        <small>{{ $plan['period'] ?: 'Plan' }}</small>
                                    </div>
                                    <div class="p">
                                        @if($plan['pen'])
                                            <em>S/ {{ $plan['pen']['formatted'] }}</em>
                                        @endif
                                        @if($plan['usd'])
                                            <span>US$ {{ $plan['usd']['formatted'] }}</span>
                                        @endif
                                    </div>
                                    @if($plan['description'])
                                        <div class="d">{{ $plan['description'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="gray" id="planes">
            <div class="wrap">
                <div class="kicker">Planes vigentes</div>
                <h2>Compara y elige <em>tu suscripción</em></h2>
                <p class="lead">Cada plan detalla lo que incluye. El precio en dólares se muestra solo en los planes que lo tienen habilitado.</p>

                @if($plans->isEmpty())
                    <div class="empty">
                        <h3>Todavía no hay suscripciones activas</h3>
                        <p>
                            Crea o activa un plan en
                            <a href="{{ route('aca_subscriptions_list') }}">Académico → Tipo de suscripción</a>
                            para verlo aquí.
                        </p>
                    </div>
                @else
                    <div class="plans">
                        @foreach($plans as $plan)
                            <article class="plan {{ $loop->first ? 'star' : '' }}">
                                @if($loop->first)
                                    <span class="flag">DESTACADO</span>
                                @endif
                                <h3>{{ $plan['title'] }}</h3>
                                <div class="users">{{ $plan['period'] ?: 'Suscripción' }}</div>
                                @if(filled($plan['description_markup']))
                                    <p class="for">{!! $plan['description_markup'] !!}</p>
                                @endif

                                <div class="pl">
                                    @if(filled($plan['price_note']))
                                        <div class="before{{ $plan['price_note_is_plain'] ? ' plain' : '' }}">{!! $plan['price_note'] !!}</div>
                                    @endif

                                    @if($plan['pen'])
                                        <div class="cur">
                                            <span class="sym">S/</span>
                                            <span class="amt">{{ $plan['pen']['formatted'] }}</span>
                                        </div>
                                        @if($plan['pen']['detail'])
                                            <div class="per">{{ $plan['pen']['detail'] }}</div>
                                        @endif
                                    @endif

                                    @if($plan['usd'])
                                        <div class="cur usd">
                                            <span class="sym">US$</span>
                                            <span class="amt usd">{{ $plan['usd']['formatted'] }}</span>
                                        </div>
                                        @if($plan['usd']['detail'])
                                            <div class="per">{{ $plan['usd']['detail'] }}</div>
                                        @endif
                                    @endif

                                    @if(! $plan['pen'] && ! $plan['usd'])
                                        <div class="amt consultar">Precio a consultar</div>
                                    @endif

                                    @if(filled($plan['price_note_below']))
                                        <div class="after">{!! $plan['price_note_below'] !!}</div>
                                    @endif

                                    @if($plan['recurring'])
                                        <span class="save">Cobro automático</span>
                                    @endif
                                </div>

                                @if(count($plan['details']) > 0)
                                    <ul>
                                        @foreach($plan['details'] as $detail)
                                            <li>{{ $detail }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if(count($included) > 0)
            <section id="incluye">
                <div class="wrap">
                    <div class="kicker">Incluido en todos los planes</div>
                    <h2>La base que comparten <em>todas las suscripciones</em></h2>
                    <div class="incl">
                        <h4>Beneficios comunes</h4>
                        <ul>
                            @foreach($included as $benefit)
                                <li>{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer class="foot">
        <div class="wrap">
            <div class="brand">
                <img class="logo" src="{{ $logo }}" alt="{{ $company->name ?? config('app.name') }}">
                <p>{{ $company->name ?? config('app.name') }}</p>
                <p>Planes de suscripción con acceso a la formación de la plataforma.</p>
            </div>
            <div>
                <h5>CONTACTO</h5>
                <ul>
                    @if($company?->email)
                        <li>{{ $company->email }}</li>
                    @endif
                    @if($company?->phone)
                        <li>{{ $company->phone }}</li>
                    @endif
                </ul>
            </div>
            <div>
                <h5>ACCESO</h5>
                <ul>
                    <li><a href="{{ route('dashboard') }}">Volver al panel</a></li>
                    <li><a href="#planes">Ver planes</a></li>
                </ul>
            </div>
        </div>
        <div class="base">
            <div class="wrap">
                <span>© {{ date('Y') }} {{ $company->name ?? config('app.name') }}</span>
                <span>Precios en soles y, según el plan, en dólares.</span>
            </div>
        </div>
    </footer>

    @if($featured && ($featured['pen'] || $featured['usd']))
        <div class="mbar" role="region" aria-label="Planes de suscripción">
            <div>
                <b>
                    {{ $featured['pen'] ? 'S/ ' . $featured['pen']['formatted'] : 'US$ ' . $featured['usd']['formatted'] }}
                </b>
                <span>{{ $featured['title'] }}@if($featured['period']) · {{ $featured['period'] }}@endif</span>
            </div>
            <a class="btn btn-red" href="#planes">Ver planes</a>
        </div>
    @endif
</body>
</html>
