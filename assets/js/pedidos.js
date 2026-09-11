$(function () {
    const API_URL = 'api/pedidos.php';

    let tallasActuales = [];
    let modelosActuales = [];
    let marcaActual = null;
    let corridaActual = null;
    let corridaDesActual = '';

    cargarMarcas();
    actualizarContadorAcumulado();

    $('#select-marca-pedido').on('change', function () {
        const marcano = $(this).val();
        $('#select-corrida-pedido').empty().append('<option value="">Seleccione corrida</option>');
        $('#contenedor-grid-pedido').hide();
        if (!marcano) return;

        $.get('api/modelos.php', { action: 'corridas', marcano: marcano }, function (respuesta) {
            if (respuesta.success) {
                respuesta.data.forEach(function (c) {
                    const etiqueta = `${c.TALLAINI}-${c.TALLAFIN} (${c.CLASIFIDES || 'Sin clasifica'})`;
                    $('#select-corrida-pedido').append(`<option value="${c.CORRIDANO}">${escaparHtml(etiqueta)}</option>`);
                });
            }
        });
    });

    $('#select-corrida-pedido').on('change', function () {
        if ($(this).val()) {
            cargarGrid();
        } else {
            $('#contenedor-grid-pedido').hide();
        }
    });

    $('#buscador-modelo-pedido').on('input', function () {
        clearTimeout(window._temporizadorPedido);
        window._temporizadorPedido = setTimeout(cargarGrid, 400);
    });

    $('#btn-agregar-pedido').on('click', function () {
        agregarAlAcumulado();
    });

    $('#btn-imprimir-pedido').on('click', function () {
        imprimirPedidoAcumulado();
    });

    $('#btn-ver-acumulado').on('click', function () {
        verAcumulado();
    });

    /* ================================================================ */

    function cargarMarcas() {
        $.get('api/inventario.php', { action: 'marcas' }, function (marcas) {
            const $select = $('#select-marca-pedido');
            marcas.forEach(function (m) {
                $select.append(`<option value="${m.MARCANO}">${escaparHtml(m.MARCADES)}</option>`);
            });
        });
    }

    function cargarGrid() {
        marcaActual = $('#select-marca-pedido').val();
        corridaActual = $('#select-corrida-pedido').val();
        corridaDesActual = $('#select-corrida-pedido option:selected').text();

        if (!marcaActual || !corridaActual) return;

        $.get(API_URL, {
            action: 'grid',
            marcano: marcaActual,
            corridano: corridaActual,
            modelo: $('#buscador-modelo-pedido').val(),
        }, function (respuesta) {
            if (respuesta.success) {
                tallasActuales = respuesta.data.tallas;
                modelosActuales = respuesta.data.modelos;
                renderizarGrid();
                $('#contenedor-grid-pedido').show();
            } else {
                Swal.fire('Error', respuesta.message, 'error');
            }
        });
    }

    function renderizarGrid() {
        const $thead = $('#tabla-pedido thead tr');
        const $tbody = $('#tabla-pedido tbody');

        $thead.find('.col-talla').remove();
        tallasActuales.forEach(function (t) {
            $thead.append(`<th class="col-talla text-center">${t}</th>`);
        });

        $tbody.empty();

        if (modelosActuales.length === 0) {
            $tbody.append(`<tr><td colspan="${5 + tallasActuales.length}" class="text-center text-muted py-3">No hay modelos para esta marca/corrida.</td></tr>`);
            return;
        }

        modelosActuales.forEach(function (modelo, idx) {
            // Fila de referencia: existencia actual (solo lectura)
            let celdasExistencia = '';
            tallasActuales.forEach(function (t) {
                celdasExistencia += `<td class="text-center text-muted small">${modelo.existencias[t] ?? 0}</td>`;
            });

            $tbody.append(`
                <tr class="table-light">
                    <td rowspan="2" class="align-middle">${escaparHtml(modelo.modelodes)}</td>
                    <td rowspan="2" class="align-middle">${escaparHtml(modelo.color)}</td>
                    <td rowspan="2" class="align-middle">${escaparHtml(modelo.material)}</td>
                    <td rowspan="2" class="align-middle">${escaparHtml(modelo.suela)}</td>
                    <td rowspan="2" class="align-middle small">Existencia →</td>
                    ${celdasExistencia}
                </tr>
            `);

            // Fila de captura: cantidad a pedir
            let celdasPedido = '';
            tallasActuales.forEach(function (t) {
                celdasPedido += `
                    <td class="p-1">
                        <input type="number" min="0" class="form-control form-control-sm input-cantidad-pedido text-center"
                               data-idx="${idx}" data-talla="${t}" value="">
                    </td>`;
            });
            $tbody.append(`<tr>${celdasPedido}</tr>`);
        });
    }

    function agregarAlAcumulado() {
        if (!marcaActual || !corridaActual) {
            Swal.fire('Datos incompletos', 'Selecciona marca y corrida primero.', 'warning');
            return;
        }

        const items = modelosActuales.map(function (modelo, idx) {
            const cantidades = {};
            $(`.input-cantidad-pedido[data-idx="${idx}"]`).each(function () {
                const talla = $(this).data('talla');
                const valor = parseInt($(this).val(), 10) || 0;
                if (valor > 0) cantidades[talla] = valor;
            });
            return {
                modelodes: modelo.modelodes,
                color: modelo.color,
                material: modelo.material,
                suela: modelo.suela,
                cantidades: cantidades,
            };
        }).filter(function (item) {
            return Object.keys(item.cantidades).length > 0;
        });

        if (items.length === 0) {
            Swal.fire('Sin cantidades', 'Captura al menos una cantidad a pedir.', 'info');
            return;
        }

        const marcaDes = $('#select-marca-pedido option:selected').text();

        $.ajax({
            url: `${API_URL}?action=agregar`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                marcano: marcaActual,
                corridano: corridaActual,
                marcades: marcaDes,
                corridades: corridaDesActual,
                tallas: tallasActuales,
                items: items,
            }),
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({ icon: 'success', title: respuesta.message, timer: 1800, showConfirmButton: false });
                    $('.input-cantidad-pedido').val('');
                    actualizarContadorAcumulado();
                } else {
                    Swal.fire('No se pudo agregar', respuesta.message, 'error');
                }
            })
            .fail(function (xhr) {
                const r = xhr.responseJSON;
                Swal.fire('Error', r ? r.message : 'Error de conexión.', 'error');
            });
    }

    function actualizarContadorAcumulado() {
        $.get(API_URL, { action: 'resumen' }, function (respuesta) {
            if (respuesta.success) {
                const cantidadModelos = respuesta.data.detalles.length;
                const cantidadMarcas = respuesta.data.cabeceras.length;
                $('#texto-acumulado').text(`${cantidadModelos} modelo(s) de ${cantidadMarcas} marca(s)/corrida(s) pendientes de imprimir`);
                $('#btn-imprimir-pedido').prop('disabled', cantidadModelos === 0);
            }
        });
    }

    function verAcumulado() {
        $.get(API_URL, { action: 'resumen' }, function (respuesta) {
            if (!respuesta.success) return;

            if (respuesta.data.detalles.length === 0) {
                Swal.fire('Sin pedido acumulado', 'Todavía no has agregado ningún modelo.', 'info');
                return;
            }

            let filas = '';
            respuesta.data.detalles.forEach(function (d) {
                filas += `<tr><td>${escaparHtml(d.MARCANO)}</td><td>${escaparHtml(d.MODELODES)}</td><td>${escaparHtml(d.COLOR)}</td><td>${d.TOTMOD}</td></tr>`;
            });

            Swal.fire({
                title: 'Pedido acumulado',
                html: `<div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Marca</th><th>Modelo</th><th>Color</th><th>Total</th></tr></thead>
                            <tbody>${filas}</tbody>
                        </table>
                       </div>`,
                width: 600,
            });
        });
    }

    function imprimirPedidoAcumulado() {
        Swal.fire({
            title: '¿Imprimir y cerrar el pedido acumulado?',
            text: 'Después de imprimir, el pedido acumulado se vaciará (no podrás seguir agregando a este mismo pedido).',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, imprimir',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;

            $.ajax({
                url: `${API_URL}?action=imprimir`,
                method: 'POST',
                dataType: 'json',
            })
                .done(function (respuesta) {
                    if (respuesta.success) {
                        generarReporteHTML(respuesta.data);
                        actualizarContadorAcumulado();
                    } else {
                        Swal.fire('No se pudo imprimir', respuesta.message, 'warning');
                    }
                })
                .fail(function (xhr) {
                    const r = xhr.responseJSON;
                    Swal.fire('Error', r ? r.message : 'Error de conexión.', 'error');
                });
        });
    }

    function generarReporteHTML(data) {
        let bloques = '';

        data.cabeceras.forEach(function (cab) {
            const detallesMarca = data.detalles.filter(d => d.MARCANO == cab.MARCANO && d.CORRIDANO == cab.CORRIDANO);
            const columnasTalla = [];
            for (let i = 1; i <= 20; i++) {
                const col = 'T_' + String(i).padStart(2, '0');
                if (cab[col] !== null && cab[col] !== undefined && cab[col] !== '') {
                    columnasTalla.push({ col: col, etiqueta: cab[col] });
                }
            }

            let filasDetalle = '';
            detallesMarca.forEach(function (d) {
                let celdas = '';
                columnasTalla.forEach(function (c) {
                    celdas += `<td class="text-center">${d[c.col] ?? ''}</td>`;
                });
                filasDetalle += `
                    <tr>
                        <td>${escaparHtml(d.MODELODES)}</td>
                        <td>${escaparHtml(d.COLOR)}</td>
                        <td>${escaparHtml(d.PIEL)}</td>
                        <td>${escaparHtml(d.SUELA)}</td>
                        ${celdas}
                        <td class="text-center"><strong>${d.TOTMOD}</strong></td>
                    </tr>`;
            });

            let encabezadosTalla = '';
            columnasTalla.forEach(function (c) {
                encabezadosTalla += `<th class="text-center">${escaparHtml(c.etiqueta)}</th>`;
            });

            bloques += `
                <h4>${escaparHtml(cab.MARCADES)} — ${escaparHtml(cab.CORRIDADES)}</h4>
                <table>
                    <thead>
                        <tr><th>Modelo</th><th>Color</th><th>Piel</th><th>Suela</th>${encabezadosTalla}<th>Total</th></tr>
                    </thead>
                    <tbody>${filasDetalle}</tbody>
                </table>
                <div class="salto"></div>
            `;
        });

        const html = `
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Pedido a Proveedor</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
                    h2 { text-align: center; }
                    h4 { margin-top: 20px; margin-bottom: 6px; background: #0d47a1; color: #fff; padding: 4px 8px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                    th, td { border: 1px solid #999; padding: 3px 5px; font-size: 11px; }
                    th { background: #e8f1fb; }
                    .salto { page-break-after: always; }
                </style>
            </head>
            <body onload="window.print()">
                <h2>Pedido a Proveedor</h2>
                <p style="text-align:center;">Fecha: ${new Date().toLocaleDateString('es-MX')}</p>
                ${bloques}
            </body>
            </html>
        `;

        const ventana = window.open('', 'pedido', 'width=900,height=700');
        if (!ventana) {
            Swal.fire('Bloqueado por el navegador', 'Permite las ventanas emergentes para imprimir.', 'warning');
            return;
        }
        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    }

    function escaparHtml(texto) {
        if (texto === null || texto === undefined) return '';
        return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
