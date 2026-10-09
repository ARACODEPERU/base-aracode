        /*
         * Carné en tamaño tarjeta estándar (54mm x 85.6mm) para imprimir y recortar.
         * Distribución vertical pensada para que el QR quede grande (24mm) y los
         * textos respiren: header 14mm + footer 6mm + cuerpo ~65mm de aire.
         */
        .carnet {
            position: relative;
            width: 54mm;
            height: 85.6mm;
            margin: 0 auto;
            border: 1px solid #94a3b8;
            border-radius: 3mm;
            overflow: hidden;
            background: #ffffff;
        }

        /* ---------- Encabezado (institución) ---------- */
        .carnet-header {
            background: #4361ee;
            color: #ffffff;
            text-align: center;
            padding: 1.8mm 2mm 1.6mm 2mm;
            height: 14mm;
        }

        .carnet-header .escudo {
            width: 8.6mm;
            height: 8.6mm;
            background: #ffffff;
            border-radius: 50%;
            text-align: center;
            line-height: 8.6mm;
            font-size: 8pt;
            font-weight: bold;
            color: #4361ee;
            margin: 0 auto 1mm auto;
            overflow: hidden;
        }

        .carnet-header .escudo img {
            width: 8.6mm;
            height: 8.6mm;
        }

        .carnet-header .nombre-colegio {
            font-size: 6.4pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
            letter-spacing: 0.2pt;
        }

        /* ---------- Cuerpo (datos del alumno) ---------- */
        .carnet-body {
            text-align: center;
            padding: 1.4mm 3mm 7.5mm 3mm;
        }

        .etiqueta {
            display: inline-block;
            background: #eef2ff;
            color: #4361ee;
            border: 0.3mm solid #c7d2fe;
            border-radius: 1.5mm;
            font-size: 5.4pt;
            font-weight: bold;
            letter-spacing: 0.6pt;
            padding: 0.7mm 2.4mm;
            margin: 0px;
        }

        .alumno-nombre {
            font-size: 9.5pt;
            font-weight: bold;
            line-height: 0.95;
            margin-top: 0.2mm;
        }

        .alumno-dato {
            font-size: 6pt;
            color: #64748b;
            margin-top: 0.8mm;
            letter-spacing: 0.1pt;
        }

        .alumno-dato .dato-clave {
            color: #475569;
            font-weight: bold;
        }

        .separador {
            border-top: 0.3mm dashed #cbd5e1;
            margin: 0.7mm 2mm 0.8mm 2mm;
        }

        .info-matricula {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.2pt;
        }

        .info-matricula td {
            padding: 0.08mm 0;
        }

        .info-matricula .etiqueta-col {
            color: #64748b;
            font-weight: bold;
            width: 49%;
            text-align: right;
            padding-right: 1mm;
        }

        .info-matricula .valor-col {
            text-align: left;
            font-weight: bold;
            color: #1e293b;
        }

        /* ---------- Código QR ---------- */
        .qr {
            width: 24mm;
            height: 24mm;
            margin: 0.5mm auto 0 auto;
        }

        .qr-caption {
            font-size: 5pt;
            color: #64748b;
            margin-top: 0px;
        }

        /* ---------- Pie (contacto) ---------- */
        .carnet-footer {
            position: absolute;
            bottom: 0;
            width: 54mm;
            background: #f1f5f9;
            border-top: 0.3mm solid #cbd5e1;
            text-align: center;
            font-size: 4.8pt;
            color: #64748b;
            padding: 1.1mm 1.5mm;
            line-height: 1.3;
        }
