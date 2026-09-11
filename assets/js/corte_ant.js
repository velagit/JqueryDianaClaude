$(function () {
    const API_URL = 'api/corte.php';
    let datosEmpresaActual = null;

    cargarEstado();

    $('#btn-imprimir-corte').on('click', function () {
        if (!datosEmpresaActual) return;
        imprimirCorte(datosEmpresaActual, false);
    });

    $('#btn-confirmar-corte').on('click', function () {
        if (!datosEmpresaActual) return;

        Swal.fire({
            title: '¿Confirmar corte de caja?',
            html: 'Esta acción <strong>reiniciará a cero</strong> los contadores del día (recibos, subtotal, ventas, IVA).<br>' +
                  'Asegúrate de haber impreso el corte antes de continuar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, hacer el corte',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;
            confirmarCorte();
        });
    });

    function cargarEstado() {
        mostrarSpinner(true);
        $.get(API_URL, { action: 'estado' }, function (respuesta) {
            if (respuesta.success) {
                datosEmpresaActual = respuesta.data.empresa;
                renderizarEstado(respuesta.data);
            } else {
                Swal.fire('Error', respuesta.message, 'error');
            }
        }).always(function () {
            mostrarSpinner(false);
        });
    }

    function renderizarEstado(data) {
        const e = data.empresa;
        $('#texto-fecha').text(data.fecha);
        $('#texto-recibos').text(e.RECIBOSDIA);
        $('#texto-subtotal').text(formatoMoneda(e.SUBTOTAL));
        $('#texto-iva').text(formatoMoneda(e.IVADIA));
        $('#texto-total').text(formatoMoneda(e.VENTADIA));
    }

    function confirmarCorte() {
        $('#btn-confirmar-corte').prop('disabled', true);

        $.ajax({
            url: `${API_URL}?action=confirmar`,
            method: 'POST',
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    imprimirCorte(respuesta.data, true);
                    Swal.fire('Corte realizado', respuesta.message, 'success').then(function () {
                        cargarEstado();
                    });
                } else {
                    Swal.fire('No se pudo realizar el corte', respuesta.message, 'error');
                }
            })
            .fail(function (xhr) {
                const r = xhr.responseJSON;
                Swal.fire('Error', r ? r.message : 'Error de conexión.', 'error');
            })
            .always(function () {
                $('#btn-confirmar-corte').prop('disabled', false);
            });
    }

    /**
     * Genera el reporte "Corte de Ventas del Día" como HTML imprimible,
     * reemplazo del reporte Rave VentasDia.
     */
    function imprimirCorte(datos, esConfirmado) {
        let empresa, totales, fecha;

        if (esConfirmado) {
            empresa = { RSOCIAL: datos.rsocial };
            totales = {
                RECIBOSDIA: datos.totalesCorte.recibosdia,
                SUBTOTAL: datos.totalesCorte.subtotal,
                VENTADIA: datos.totalesCorte.ventadia,
                IVADIA: datos.totalesCorte.ivadia,
            };
            fecha = datos.fecha;
        } else {
            empresa = datos.empresa;
            totales = datos.empresa;
            fecha = datos.fecha;
        }

        const html = `
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Corte de Caja</title>
                <style>
                    @page { margin: 0; }
                    body { font-family: 'Courier New', monospace; font-size: 13px; width: 280px; margin: 0 auto; padding: 10px; color: #000; }
                    h3 { text-align: center; margin: 4px 0; }
                    p { margin: 3px 0; display: flex; justify-content: space-between; }
                    .linea-punteada { border-top: 1px dashed #000; margin: 8px 0; }
                    .text-center { text-align: center; }
                </style>
            </head>
            <body onload="window.print()">
                <h3>${escaparHtml(empresa.RSOCIAL || '')}</h3>
                <p class="text-center" style="display:block;">CORTE DE VENTAS DEL DÍA</p>
                <p class="text-center" style="display:block;">${fecha}</p>
                <div class="linea-punteada"></div>
                <p><span>Recibos del día:</span> <strong>${totales.RECIBOSDIA}</strong></p>
                <p><span>Subtotal:</span> <strong>${formatoMoneda(totales.SUBTOTAL)}</strong></p>
                <p><span>IVA:</span> <strong>${formatoMoneda(totales.IVADIA)}</strong></p>
                <p><span>Total del día:</span> <strong>${formatoMoneda(totales.VENTADIA)}</strong></p>
                <div class="linea-punteada"></div>
                <p class="text-center" style="display:block;">${esConfirmado ? 'CORTE CONFIRMADO — contadores reiniciados' : '(vista previa, sin confirmar)'}</p>
            </body>
            </html>
        `;

        const ventana = window.open('', 'corte', 'width=350,height=500');
        if (!ventana) {
            Swal.fire('Bloqueado por el navegador', 'Permite las ventanas emergentes para imprimir.', 'warning');
            return;
        }
        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    }

    function mostrarSpinner(mostrar) {
        $('#spinner-carga').toggle(mostrar);
    }

    function formatoMoneda(valor) {
        return '$' + parseFloat(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escaparHtml(texto) {
        if (texto === null || texto === undefined) return '';
        return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
