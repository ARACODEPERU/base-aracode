<html>
    <head>
        <meta charset="UTF-8">
        <style>
            /* Configuracion de la hoja */
            @page { margin: 1cm; }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #333;
                line-height: 1.4;
                margin: 0;
                padding: 0;
                font-size: 12px;
            }

            /* Encabezado */
            .header-table { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; }
            .org-name { font-size: 18px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
            .edition-name { font-size: 12px; color: #666; }
            .acta-code { background: #1e3a8a; color: white; padding: 5px 15px; border-radius: 4px; font-weight: bold; }

            /* Banner de Encuentro */
            .match-banner {
                background: #f3f4f6;
                padding: 15px;
                text-align: center;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid #e5e7eb;
            }
            .match-title { font-size: 16px; font-weight: bold; color: #111827; margin-bottom: 5px; }
            .match-date { font-size: 11px; color: #6b7280; }

            /* Resultado Score */
            .score-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
            .score-box {
                font-size: 24px;
                font-weight: bold;
                background: #1e3a8a;
                color: white;
                width: 50px;
                text-align: center;
                border-radius: 5px;
                padding: 5px;
            }
            .team-name {
                font-size: 14px;
                font-weight: bold;
                word-wrap: break-word;
                overflow: hidden;
                text-transform: uppercase; /* Opcional: da un toque más profesional */
            }
            .vs { font-style: italic; color: #9ca3af; }

            /* Secciones de Contenido */
            .section-title {
                background: #e5e7eb;
                padding: 5px 10px;
                font-weight: bold;
                font-size: 11px;
                text-transform: uppercase;
                margin-bottom: 8px;
                border-left: 4px solid #1e3a8a;
            }
            .content-box {
                border: 1px solid #e5e7eb;
                padding: 10px;
                min-height: 60px;
                margin-bottom: 15px;
                border-radius: 4px;
                background: #fafafa;
            }

            /* Tabla de Arbitros y Delegados */
            .info-table { width: 100%; margin-bottom: 20px; }
            .info-table td { padding: 5px; border-bottom: 1px solid #f3f4f6; }
            .label { font-weight: bold; color: #4b5563; width: 30%; }

            /* Firmas */
            .signature-section { margin-top: 40px; width: 100%; }
            .signature-box { border-top: 1px solid #000; text-align: center; padding-top: 5px; width: 45%; font-size: 10px; }
            .spacer { width: 10%; }

            /* Pie de pagina */
            .footer {
                position: absolute;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                color: #9ca3af;
                border-top: 1px solid #e5e7eb;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>

        <table class="header-table" style="width: 100%; table-layout: fixed; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; text-align: left; padding-right: 10px;">
                    <div class="org-name" style="font-size: 16px; font-weight: bold; color: #1e3a8a; word-wrap: break-word; line-height: 1.2;">
                        {{ $acta['event_name'] }}
                    </div>
                    <div class="edition-name" style="font-size: 11px; color: #666; margin-top: 4px;">
                        {{ $acta['edition_name'] }}
                    </div>
                </td>

                <td style="width: 120px; vertical-align: top; text-align: right;">
                    <div class="acta-code" style="background: #1e3a8a; color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; white-space: nowrap;">
                        ACTA Nº {{ $acta['minutes_code'] }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="match-banner">
            <div class="match-title">{{ $acta['minutes_subject'] }}</div>
            <div class="match-date">ID Partido: {{ $acta['match_id'] }} | Fecha de Cierre: {{ $acta['closed_at'] }}</div>
        </div>

        <table class="score-table" style="width: 100%; table-layout: fixed; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="width: 38%; text-align: right; padding-right: 15px; vertical-align: middle;" class="team-name">
                    {{ $acta['minutes_subject_local'] ?? 'Local' }}
                </td>

                <td style="width: 55px;" align="center">
                    <div class="score-box" style="display: block; width: 45px; margin: 0 auto;">
                        {{ $acta['local_score'] }}
                    </div>
                </td>

                <td style="width: 40px; color: #9ca3af; font-style: italic; font-size: 14px;" align="center">
                    VS
                </td>

                <td style="width: 55px;" align="center">
                    <div class="score-box" style="display: block; width: 45px; margin: 0 auto;">
                        {{ $acta['visitor_score'] }}
                    </div>
                </td>

                <td style="width: 38%; text-align: left; padding-left: 15px; vertical-align: middle;" class="team-name">
                    {{ $acta['minutes_subject_visitor'] ?? 'Visitante' }}
                </td>
            </tr>
        </table>

        <div class="section-title">Autoridades y Delegados</div>
        <table class="info-table">
            <tr>
                <td class="label">Árbitros:</td>
                <td>
                    @if(!empty($acta['referees']) && is_array($acta['referees']))
                        @foreach($acta['referees'] as $index => $ref)
                            {{ $ref['full_name'] }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    @else
                        Sin árbitros registrados
                    @endif
                </td>
            </tr>
            @foreach($acta['participants'] as $index => $p)
            <tr>
                <td class="label">Delegado {{ $index == 0 ? 'Local' : 'Visitante' }}:</td>
                <td>{{ $p['full_name'] }}</td>
            </tr>
            @endforeach
        </table>

        <div class="section-title">Observaciones del Encuentro</div>
        <div class="content-box">
            @if($acta['observations'])
                {!! $acta['observations'] !!}
            @else
                {{ 'No se registraron incidentes durante el partido.' }}
            @endif
        </div>
        <div class="section-title">Reclamos y Resoluciones</div>
            <div class="content-box" style="min-height: 80px;">
                @php
                    // Verificamos si al menos uno de los dos tiene contenido
                    $tieneReclamos = !empty($acta['protest_details']['team_h']) || !empty($acta['protest_details']['team_a']);
                @endphp

                @if($tieneReclamos)
                    <table style="width: 100%; border-collapse: collapse;">
                        {{-- Reclamo del Local (team_h) --}}
                        @if(!empty($acta['protest_details']['team_h']))
                            <tr>
                                <td style="vertical-align: top; padding-bottom: 10px;">
                                    <strong style="color: #1e3a8a; font-size: 10px; display: block; text-transform: uppercase;">
                                        Reclamo Equipo Local:
                                    </strong>
                                    <div style="font-style: italic; color: #374151; margin-top: 2px; border-left: 2px solid #1e3a8a; padding-left: 8px;">
                                        "{{ $acta['protest_details']['team_h'] }}"
                                    </div>
                                </td>
                            </tr>
                        @endif

                        {{-- Reclamo del Visitante (team_a) --}}
                        @if(!empty($acta['protest_details']['team_a']))
                            <tr>
                                <td style="vertical-align: top;">
                                    <strong style="color: #b91c1c; font-size: 10px; display: block; text-transform: uppercase;">
                                        Reclamo Equipo Visitante:
                                    </strong>
                                    <div style="font-style: italic; color: #374151; margin-top: 2px; border-left: 2px solid #b91c1c; padding-left: 8px;">
                                        "{{ $acta['protest_details']['team_a'] }}"
                                    </div>
                                </td>
                            </tr>
                        @endif
                        @if(!empty($acta['resolution_details']))
                            <tr>
                                <td style="padding-top: 15px;">
                                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                                        <div style="background-color: #0f172a; color: white; padding: 4px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
                                            Resolución Oficial del Comité
                                        </div>

                                        <div style="padding: 10px; position: relative;">
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <tr>
                                                    <td style="vertical-align: top; width: 20px;">
                                                        <span style="color: #059669; font-size: 18px; line-height: 1;">•</span>
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <div style="color: #1e293b; font-size: 12px; line-height: 1.5; font-weight: bold;">
                                                            {{ $acta['resolution_details'] }}
                                                        </div>
                                                        <div style="margin-top: 5px; font-size: 9px; color: #64748b; text-transform: italic;">
                                                            Documento validado y cerrado mediante sistema administrativo.
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>
                @else
                    <div style="text-align: center; color: #9ca3af; margin-top: 20px;">
                        Sin reclamos registrados por las partes.
                    </div>
                @endif
            </div>

        <table class="signature-section">
            <tr>
                <td class="signature-box">
                    <strong>{{ $acta['participants'][0]['full_name'] }}</strong><br>
                    Delegado Local
                </td>
                <td class="spacer"></td>
                <td class="signature-box">
                    <strong>{{ $acta['participants'][1]['full_name'] }}</strong><br>
                    Delegado Visitante
                </td>
            </tr>
            <tr><td colspan="3" style="height: 40px;"></td></tr>
            <tr>
                <td class="spacer"></td>
                <td class="signature-box">
                    Firma del Árbitro Principal
                </td>
                <td class="spacer"></td>
            </tr>
        </table>

        <div class="footer">
            Este documento es un acta oficial generada por el sistema de gestión deportiva.<br>
            Organizado por: <strong>Nombre de tu Empresa/Liga</strong> | Todos los derechos reservados &copy; 2026
        </div>

    </body>
</html>
