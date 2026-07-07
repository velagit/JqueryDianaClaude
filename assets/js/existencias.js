$(function () {
    const API_URL = 'api/existencias.php';

    const parametros = new URLSearchParams(window.location.search);
    const marcano = parametros.get('marcano');

    if (!marcano) {
        Swal.fire('Error', 'No se especificó la marca (MARCANO) en la URL.', 'error');
        return;
    }

    let tallasActuales = [];
    let filtroModelo = parametros.get('modelo') || '';

    // Prellenar el buscador si venía en la URL
    $('#buscador-modelo').val(filtroModelo);

    // -----------------------------------------------------------------
    // Carga inicial
    // -----------------------------------------------------------------
    cargarMarca();
    cargarTallasYModelos();

    // -----------------------------------------------------------------
    // Buscador de modelos
    // -----------------------------------------------------------------
    $('#form-buscar-modelo').on('submit', function (e) {
        e.preventDefault();
        filtroModelo = $('#buscador-modelo').val();
        cargarTallasYModelos();
    });

    // -----------------------------------------------------------------
    // Envío del formulario de existencias
    // -----------------------------------------------------------------
    $('#form-existencias').on('submit', function (e) {
        e.preventDefault();
        guardarExistencias();
    });

    /* ================================================================ */
    /*  Funciones principales                                            */
    /* ================================================================ */

    function cargarMarca() {
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'marca', marcano: marcano },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#titulo-marca').text(`Captura de existencias de los modelos de la marca: ${respuesta.data.MARCANO} - ${respuesta.data.MARCADES}`);
                } else {
                    Swal.fire('Error', respuesta.message || 'Marca no encontrada.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar la marca.', 'error');
            });
    }

    function cargarTallasYModelos() {
        mostrarSpinner(true);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'tallas', marcano: marcano },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (!respuesta.success) {
                    Swal.fire('Error', respuesta.message || 'No se pudieron cargar las tallas.', 'error');
                    return;
                }

                if (respuesta.data.sinCorridas) {
                    Swal.fire({
                        title: 'Sin corridas',
                        text: 'Esta marca no tiene corridas registradas. No se puede capturar existencias.',
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                    });
                    $('#contenedor-formulario').hide();
                    mostrarSpinner(false);
                    return;
                }

                tallasActuales = respuesta.data.tallas;
                cargarModelos();
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar las tallas.', 'error');
                mostrarSpinner(false);
            });
    }

    function cargarModelos() {
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'modelos', marcano: marcano, modelo: filtroModelo },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    renderizarTabla(respuesta.data, tallasActuales);
                    $('#contenedor-formulario').show();
                } else {
                    Swal.fire('Error', respuesta.message || 'No se pudieron cargar los modelos.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar los modelos.', 'error');
            })
            .always(function () {
                mostrarSpinner(false);
            });
    }

    function renderizarTabla(modelos, tallas) {
        const $thead = $('#tabla-existencias thead tr');
        const $tbody = $('#tabla-existencias tbody');

        // Encabezados fijos + una columna por talla
        $thead.find('.col-talla').remove();
        tallas.forEach(function (talla) {
            $thead.append(`<th class="col-talla">${talla}</th>`);
        });

        $tbody.empty();

        if (!modelos || modelos.length === 0) {
            $tbody.append(`<tr><td colspan="${4 + tallas.length}" class="text-center text-muted py-3">No se encontraron modelos.</td></tr>`);
            return;
        }

        modelos.forEach(function (modelo) {
            let celdasTalla = '';
            tallas.forEach(function (talla) {
                celdasTalla += `
                    <td class="celda-talla">
                        <input type="number" name="existencias[${modelo.MODELONO}][${talla}]"
                               class="form-control form-control-sm" min="0" value="0">
                    </td>`;
            });

            const fila = `
                <tr>
                    <td>${escaparHtml(modelo.MODELODES)}</td>
                    <td>${escaparHtml(modelo.MATERIAL)}</td>
                    <td>${escaparHtml(modelo.COLOR)}</td>
                    <td>${escaparHtml(modelo.SUELA)}</td>
                    ${celdasTalla}
                </tr>`;
            $tbody.append(fila);
        });
    }

    function guardarExistencias() {
        // Validación: al menos una existencia > 0 (igual que el JS original)
        let tieneExistencia = false;
        $('input[name^="existencias"]').each(function () {
            if (parseInt($(this).val(), 10) > 0) {
                tieneExistencia = true;
            }
        });

        if (!tieneExistencia) {
            Swal.fire({
                title: 'Sin existencias',
                text: 'Debes capturar al menos una existencia mayor a cero.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
            return;
        }

        Swal.fire({
            title: '¿Deseas guardar las existencias?',
            text: 'Se actualizarán los datos de modelos y artículos.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d47a1',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                enviarExistencias();
            }
        });
    }

    function enviarExistencias() {
        // Reconstruir el objeto "existencias" a partir de los inputs de la tabla
        const existencias = {};
        $('input[name^="existencias"]').each(function () {
            const nombre = $(this).attr('name'); // existencias[123][40]
            const coincidencia = nombre.match(/existencias\[(\d+)\]\[(\d+)\]/);
            if (coincidencia) {
                const modelono = coincidencia[1];
                const talla = coincidencia[2];
                if (!existencias[modelono]) {
                    existencias[modelono] = {};
                }
                existencias[modelono][talla] = parseInt($(this).val(), 10) || 0;
            }
        });

        const datos = {
            MARCANO: marcano,
            existencias: existencias,
            precio_general: $('#precio_general').val(),
            fecha_general: $('#fecha_general').val(),
        };

        $('#guardar-btn').prop('disabled', true);

        $.ajax({
            url: `${API_URL}?action=store`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        title: '¡Guardado!',
                        text: respuesta.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                    }).then(function () {
                        cargarTallasYModelos();
                        $('#precio_general').val('');
                        $('#fecha_general').val('');
                    });
                } else {
                    Swal.fire('No se pudo guardar', respuesta.message, 'error');
                }
            })
            .fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                Swal.fire('Error', respuesta ? respuesta.message : 'Ocurrió un error de conexión.', 'error');
            })
            .always(function () {
                $('#guardar-btn').prop('disabled', false);
            });
    }

    /* ================================================================ */
    /*  Utilidades                                                       */
    /* ================================================================ */

    function mostrarSpinner(mostrar) {
        $('#spinner-carga').toggle(mostrar);
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
