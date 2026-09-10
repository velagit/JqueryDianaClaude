$(function () {
    const API_URL = 'api/ventas.php';

    let carrito = [];         // items en el ticket actual
    let folioActual = null;
    let modoDevolucion = false;

    // -----------------------------------------------------------------
    // Inicio: obtener el siguiente folio y el catálogo de marcas
    // -----------------------------------------------------------------
    cargarFolio();
    cargarMarcas();
    $('#codbarra').focus();

    // -----------------------------------------------------------------
    // Captura por código de barras (Enter para agregar)
    // -----------------------------------------------------------------
    $('#codbarra').on('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        let texto = $(this).val().trim();
        if (texto === '') return;

        // Devolución: prefijo 'D'
        if (texto[0].toUpperCase() === 'D') {
            registrarDevolucion(texto.substring(1));
            $(this).val('');
            return;
        }

        // Código completo escaneado (12 caracteres): se toman 9 centrales,
        // igual que Copy(ECodBarra.Text,3,9) en el original.
        let codigo = texto.length === 12 ? texto.substring(2, 11) : texto;

        agregarPorCodBarra(codigo);
        $(this).val('');
    });

    // -----------------------------------------------------------------
    // Búsqueda manual (marca / modelo / talla)
    // -----------------------------------------------------------------
    $('#btn-busqueda-manual').on('click', function () {
        $('#form-busqueda-manual')[0].reset();
        $('#resultado-busqueda-manual').empty();
        $('#modalBusquedaManual').modal('show');
    });

    $('#manual-marcano').on('change', function () {
        const marcano = $(this).val();
        $('#manual-modelo-select').empty().append('<option value="">Seleccione modelo</option>');
        $('#resultado-busqueda-manual').empty();
        if (!marcano) return;

        $.get(API_URL, { action: 'modelos_marca', marcano: marcano }, function (respuesta) {
            if (respuesta.success) {
                respuesta.data.forEach(function (m) {
                    const etiqueta = `${m.MODELODES} — ${m.COLOR || ''} (tallas ${m.TALLAINI}-${m.TALLAFIN})`;
                    $('#manual-modelo-select').append(
                        `<option value="${m.MODELONO}" data-tallaini="${m.TALLAINI}" data-tallafin="${m.TALLAFIN}">${escaparHtml(etiqueta)}</option>`
                    );
                });
            }
        });
    });

    $('#form-busqueda-manual').on('submit', function (e) {
        e.preventDefault();
        const marcano = $('#manual-marcano').val();
        const modelono = $('#manual-modelo-select').val();
        const talla = $('#manual-talla').val();

        if (!marcano || !modelono || !talla) {
            Swal.fire('Datos incompletos', 'Selecciona marca, modelo y talla.', 'warning');
            return;
        }

        $.get(API_URL, { action: 'buscar_manual', marcano, modelono, talla }, function (respuesta) {
            if (respuesta.success) {
                agregarArticuloAlCarrito(respuesta.data);
                $('#modalBusquedaManual').modal('hide');
            } else {
                Swal.fire('No disponible', respuesta.message, 'warning');
            }
        }).fail(function (xhr) {
            const r = xhr.responseJSON;
            Swal.fire('No disponible', r ? r.message : 'Error de conexión.', 'warning');
        });
    });

    // -----------------------------------------------------------------
    // Editar precio del último artículo agregado (equivalente F3)
    // -----------------------------------------------------------------
    $('#btn-editar-precio').on('click', function () {
        if (carrito.length === 0) {
            Swal.fire('Carrito vacío', 'Agrega un artículo primero.', 'info');
            return;
        }
        const ultimo = carrito[carrito.length - 1];
        Swal.fire({
            title: 'Editar precio',
            input: 'number',
            inputValue: ultimo.precio,
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (resultado.isConfirmed && resultado.value !== '') {
                ultimo.precio = parseFloat(resultado.value);
                renderizarCarrito();
            }
        });
    });

    // -----------------------------------------------------------------
    // Nueva venta (limpia el carrito)
    // -----------------------------------------------------------------
    $('#btn-nueva-venta').on('click', function () {
        iniciarNuevaVenta();
    });

    // -----------------------------------------------------------------
    // Abrir cajón (placeholder — ver nota en el modal)
    // -----------------------------------------------------------------
    $('#btn-abrir-cajon').on('click', function () {
        Swal.fire({
            icon: 'info',
            title: 'Abrir cajón de dinero',
            html: 'Esta función requiere una impresora de tickets con cajón conectada en red compatible con comandos ESC/POS.<br><br>' +
                  'Cuando configures ese equipo, este botón podrá enviarle la señal de apertura. Por ahora es solo un marcador de posición.',
        });
    });

    // -----------------------------------------------------------------
    // Cobrar (contado) — equivalente F12 "Facturado"
    // -----------------------------------------------------------------
    $('#btn-cobrar').on('click', function () {
        if (carrito.length === 0) {
            Swal.fire('Carrito vacío', 'Agrega al menos un artículo antes de cobrar.', 'info');
            return;
        }
        abrirModalCobro();
    });

    $('#btn-remision').on('click', function () {
        Swal.fire({
            icon: 'info',
            title: 'Vale / Remisión',
            text: 'El pago con vale de empleado se agrega en la siguiente fase de este módulo.',
        });
    });

    $('#form-cobro').on('submit', function (e) {
        e.preventDefault();
        const pagado = parseFloat($('#input-pagado').val());
        const total = calcularTotal();

        if (isNaN(pagado) || pagado < total) {
            Swal.fire('Monto insuficiente', 'El monto pagado es menor al importe total.', 'warning');
            return;
        }

        guardarVentaContado(pagado);
    });

    $('#input-pagado').on('input', function () {
        const pagado = parseFloat($(this).val()) || 0;
        const total = calcularTotal();
        const cambio = pagado - total;
        $('#texto-cambio').text(cambio >= 0 ? formatoMoneda(cambio) : '—');
    });

    /* ================================================================ */
    /*  Funciones principales                                            */
    /* ================================================================ */

    function cargarFolio() {
        $.get(API_URL, { action: 'folio' }, function (respuesta) {
            if (respuesta.success) {
                folioActual = respuesta.data.folio;
                $('#texto-folio').text(folioActual);
            }
        });
    }

    function cargarMarcas() {
        // Reutiliza el catálogo de marcas ya expuesto por el módulo de Inventario
        $.get('api/inventario.php', { action: 'marcas' }, function (marcas) {
            const $select = $('#manual-marcano');
            marcas.forEach(function (m) {
                $select.append(`<option value="${m.MARCANO}">${escaparHtml(m.MARCADES)}</option>`);
            });
        });
    }

    function agregarPorCodBarra(codigo) {
        $.get(API_URL, { action: 'buscar_codbarra', codbarra: codigo }, function (respuesta) {
            if (respuesta.success) {
                if (respuesta.data.existencia <= 0) {
                    Swal.fire('Sin existencia', 'Ese artículo no tiene piezas disponibles.', 'warning');
                    return;
                }
                agregarArticuloAlCarrito(respuesta.data);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'No encontrado',
                    text: respuesta.message || 'El código de artículo no existe.',
                });
            }
        }).fail(function (xhr) {
            const r = xhr.responseJSON;
            Swal.fire('Error', r ? r.message : 'Error de conexión.', 'error');
        });
    }

    function agregarArticuloAlCarrito(articulo) {
        carrito.push(articulo);
        renderizarCarrito();
        $('#codbarra').focus();
    }

    function renderizarCarrito() {
        const $tbody = $('#tabla-carrito tbody');
        $tbody.empty();

        if (carrito.length === 0) {
            $tbody.append('<tr><td colspan="8" class="text-center text-muted py-3">Sin artículos capturados.</td></tr>');
        } else {
            carrito.forEach(function (item, idx) {
                $tbody.append(`
                    <tr>
                        <td>${escaparHtml(item.codbarra)}</td>
                        <td>${escaparHtml(item.marcades)}</td>
                        <td>${escaparHtml(item.modelodes)}</td>
                        <td>${escaparHtml(item.color)}</td>
                        <td>${escaparHtml(item.material)}</td>
                        <td>${escaparHtml(item.suela)}</td>
                        <td>${escaparHtml(item.talla)}</td>
                        <td>${formatoMoneda(item.precio)}
                            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar ms-1" data-idx="${idx}" title="Quitar">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }

        const total = calcularTotal();
        $('#texto-total').text(formatoMoneda(total));
        $('#texto-cantidad-articulos').text(carrito.length);
    }

    $('#tabla-carrito').on('click', '.btn-quitar', function () {
        const idx = $(this).data('idx');
        carrito.splice(idx, 1);
        renderizarCarrito();
    });

    function calcularTotal() {
        return carrito.reduce(function (acumulado, item) {
            return acumulado + parseFloat(item.precio || 0);
        }, 0);
    }

    function abrirModalCobro() {
        const total = calcularTotal();
        $('#texto-total-modal').text(formatoMoneda(total));
        $('#input-pagado').val('');
        $('#texto-cambio').text('—');
        $('#modalCobro').modal('show');
    }

    function guardarVentaContado(pagado) {
        $('#btn-confirmar-cobro').prop('disabled', true);

        $.ajax({
            url: `${API_URL}?action=guardar_contado`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ carrito: carrito, pagado: pagado }),
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalCobro').modal('hide');
                    mostrarResumenVenta(respuesta.data);
                } else {
                    Swal.fire('No se pudo guardar la venta', respuesta.message, 'error');
                }
            })
            .fail(function (xhr) {
                const r = xhr.responseJSON;
                Swal.fire('Error', r ? r.message : 'Ocurrió un error de conexión.', 'error');
            })
            .always(function () {
                $('#btn-confirmar-cobro').prop('disabled', false);
            });
    }

    function mostrarResumenVenta(venta) {
        let filasExistencia = '';
        venta.detalle.forEach(function (linea) {
            filasExistencia += `
                <tr>
                    <td>${escaparHtml(linea.codbarra)}</td>
                    <td>${escaparHtml(linea.modelodes)}</td>
                    <td>${escaparHtml(linea.talla)}</td>
                    <td>${linea.existencia_restante}</td>
                </tr>`;
        });

        Swal.fire({
            icon: 'success',
            title: `Venta #${venta.folio} registrada`,
            html: `
                <div class="text-start">
                    <p class="mb-1">Subtotal: ${formatoMoneda(venta.subtotal)}</p>
                    <p class="mb-1">IVA: ${formatoMoneda(venta.iva)}</p>
                    <p class="mb-1"><strong>Total: ${formatoMoneda(venta.total)}</strong></p>
                    <p class="mb-1">Pagado: ${formatoMoneda(venta.pagado)}</p>
                    <p class="mb-3">Cambio: <strong>${formatoMoneda(venta.cambio)}</strong></p>
                    <p class="mb-1"><strong>Existencia restante por artículo:</strong></p>
                    <div class="table-responsive" style="max-height:200px; overflow-y:auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Código</th><th>Modelo</th><th>Talla</th><th>Quedan</th></tr></thead>
                            <tbody>${filasExistencia}</tbody>
                        </table>
                    </div>
                </div>
            `,
            confirmButtonText: 'Nueva venta',
            allowOutsideClick: false,
        }).then(function () {
            iniciarNuevaVenta();
        });
    }

    function iniciarNuevaVenta() {
        carrito = [];
        renderizarCarrito();
        cargarFolio();
        $('#codbarra').val('').focus();
    }

    function registrarDevolucion(codigo) {
        Swal.fire({
            title: '¿Confirmar devolución?',
            text: `Código: ${codigo}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;

            $.ajax({
                url: `${API_URL}?action=devolucion`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ codbarra: codigo }),
                dataType: 'json',
            })
                .done(function (respuesta) {
                    if (respuesta.success) {
                        Swal.fire('Devolución registrada', respuesta.message, 'success');
                    } else {
                        Swal.fire('No se pudo registrar', respuesta.message, 'warning');
                    }
                })
                .fail(function (xhr) {
                    const r = xhr.responseJSON;
                    Swal.fire('Error', r ? r.message : 'Error de conexión.', 'error');
                })
                .always(function () {
                    $('#codbarra').focus();
                });
        });
    }

    /* ================================================================ */
    /*  Utilidades                                                       */
    /* ================================================================ */

    function formatoMoneda(valor) {
        return '$' + parseFloat(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escaparHtml(texto) {
        if (texto === null || texto === undefined) return '';
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
