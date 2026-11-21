<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Autorizar acceso · {{ config('app.name') }}</title>
    <style>
        :root {
            --brand: #4361ee;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --surface: #ffffff;
            --bg: #f1f5f9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font-family: "Nunito", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .eyebrow {
            margin: 0 0 6px;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 22px;
            line-height: 1.25;
        }

        p { margin: 0 0 16px; color: var(--muted); font-size: 14px; line-height: 1.55; }

        .client {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(67, 97, 238, .1);
            color: var(--brand);
            font-weight: 700;
            font-size: 13px;
        }

        ul { margin: 0 0 18px; padding: 0; list-style: none; }

        li {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-top: 1px solid var(--border);
            font-size: 14px;
        }

        li:first-child { border-top: 0; }

        li span.dot {
            flex: none;
            width: 8px;
            height: 8px;
            margin-top: 7px;
            border-radius: 50%;
            background: var(--brand);
        }

        .meta { font-size: 12px; color: var(--muted); word-break: break-word; }

        .consent {
            margin: 0 0 18px;
            padding: 14px;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            background: #fffbeb;
        }

        .consent label {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            color: #78350f;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
            cursor: pointer;
        }

        .consent input { margin-top: 3px; flex: none; }

        .consent .risk {
            display: block;
            margin: 7px 0 0;
            color: #92400e;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.5;
        }

        .actions { display: flex; gap: 10px; margin-top: 22px; }

        button {
            flex: 1;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }

        button[value="approve"] { background: var(--brand); color: #fff; }
        button[value="deny"] { background: #e2e8f0; color: var(--text); }
        button:focus-visible { outline: 3px solid rgba(67, 97, 238, .45); outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <p class="eyebrow">Servidor MCP del Académico</p>
        <h1>Autorizar a un agente de IA</h1>

        <p>
            <span class="client">{{ $client_name }}</span>
            quiere conectarse a tu cuenta de {{ config('app.name') }} para crear cursos.
        </p>

        <ul>
            @foreach ($scopes as $scope)
                <li>
                    <span class="dot"></span>
                    <span>
                        @if ($scope === 'academic:write')
                            <strong>Crear cursos en borrador</strong><br>
                            Módulos, clases, contenidos y exámenes. No puede activar ni publicar cursos.
                        @elseif ($scope === 'academic:write_active')
                            <strong>Editar cursos activos y landings</strong><br>
                            Solo con confirmación explícita. No puede cambiar precios, publicar,
                            cambiar el slug ni eliminar cursos. La portada sí se puede reemplazar o quitar.
                        @else
                            <strong>Consultar el catálogo</strong><br>
                            Categorías, modalidades, docentes y cursos existentes.
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        @if ($active_edit_requested)
            <div class="consent">
                <strong style="display:block;color:#78350f;font-size:14px;line-height:1.4;">
                    Edición de cursos activos: máximo cuidado
                </strong>
                <span class="risk">
                    Este permiso permite cambiar textos, enlaces, archivos, imágenes y secciones de la
                    landing de un curso que ya tiene alumnos. Cada herramienta exige confirmación y motivo.
                    No permite cambiar precios, descuentos, tipo de curso, estado, slug ni publicación, y
                    no permite borrar el curso, módulos, clases, contenidos, exámenes o documentos.
                    Si no estás seguro de lo que se va a cambiar, cancela y revísalo en el ERP.
                </span>
            </div>
        @endif

        @if ($autonomy_allowed)
            <div class="consent">
                <label>
                    <input type="checkbox" name="autonomy" value="1" @checked(old('autonomy'))>
                    <span>
                        Permiso absoluto para crear y editar
                        <span class="risk">
                            El agente podrá crear y editar cursos, módulos, clases, contenidos, exámenes,
                            archivos y la landing sin volver a pedirte confirmación al servidor.
                            Riesgos: puede crear hasta 10 cursos por hora, subir archivos, cambiar textos y
                            publicar una landing si se lo pides; todo queda auditado. No puede activar el
                            curso ni borrar sin una confirmación explícita. Si no lo marcas, el agente te
                            pedirá confirmación antes de cada lote de cambios. Tu cliente MCP puede seguir
                            mostrando sus propios avisos de seguridad.
                        </span>
                    </span>
                </label>
            </div>
        @endif

        <p class="meta">
            Volverá a {{ $redirect_host }} y todo curso queda en borrador hasta que una persona lo active en el ERP.
        </p>

        <form method="POST" action="{{ url('/oauth/authorize') }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $client_id }}">
            <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">
            <input type="hidden" name="state" value="{{ $state }}">
            <input type="hidden" name="code_challenge" value="{{ $code_challenge }}">
            <input type="hidden" name="code_challenge_method" value="S256">
            <input type="hidden" name="response_type" value="code">
            <input type="hidden" name="scope" value="{{ $scope }}">

            <div class="actions">
                <button type="submit" name="decision" value="deny">Cancelar</button>
                <button type="submit" name="decision" value="approve">Autorizar</button>
            </div>
        </form>
    </main>
</body>
</html>
