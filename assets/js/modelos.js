$(function () {
    const API_URL = 'api/modelos.php';

    // MARCANO viene en la URL: modelos.php?marcano=1
    const parametros = new URLSearchParams(window.location.search);
    const marcano = parametros.get('marcano');

    if (!marcano) {
        Swal.fire('Error', 'No se especificó la marca (MARCANO) en la URL.', 'error');
        return;
    }

    let paginaActual = 1;
    let terminoBusqueda = '';
    let temporizadorBusqueda = null;

    // -----------------------------------------------------------------
    // Carga inicial
    // -----------------------------------------------------------------
    cargarMarca();
    cargarCombosCorridas();
    cargarModelos();

    // -----------------------------------------------------------------
    // Buscador (con debounce)
    // -----------------------------------------------------------------
    $('#buscador-modelo').on('input', function () {
        const valor = $(this).val();
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(function () {
            terminoBusqueda = valor;
            paginaActual = 1;
            cargarModelos();
        }, 400);
    });

    // -----------------------------------------------------------------
    // Botón "Agregar Modelo"
    // -----------------------------------------------------------------
    $('#btn-agregar-modelo').on('click', function () {
        abrirModalCrear();
    });

    // -----------------------------------------------------------------
    // Delegación de eventos para botones dentro de la tabla (dinámica)
    // -----------------------------------------------------------------
    $('#tabla-modelos').on('click', '.btn-editar', function () {
        const modelono = $(this).data('modelono');
        abrirModalEditar(modelono);
    });

    $('#tabla-modelos').on('click', '.btn-eliminar', function () {
        const modelono = $(this).data('modelono');
        confirmarEliminacion(modelono);
    });

    // Paginación
    $('#paginacion-modelos').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const pagina = $(this).data('pagina');
        if (pagina) {
            paginaActual = pagina;
            cargarModelos();
        }
    });

    // -----------------------------------------------------------------
    // Envío del formulario (crear o editar según el modo del modal)
    // -----------------------------------------------------------------
    $('#form-modelo').on('submit', function (e) {
        e.preventDefault();
        guardarModelo();
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
                    $('#titulo-marca').text(`Modelos de la marca: ${respuesta.data.MARCANO} - ${respuesta.data.MARCADES}`);
                } else {
                    Swal.fire('Error', respuesta.message || 'Marca no encontrada.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar la marca.', 'error');
            });
    }

    function cargarCombosCorridas() {
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'corridas', marcano: marcano },
            dataType: 'json',
        }).done(function (respuesta) {
            if (respuesta.success) {
                const $select = $('#CORRIDANO');
                $select.empty().append('<option value="">Seleccione una corrida</option>');
                respuesta.data.forEach(function (corrida) {
                    const etiqueta = `${corrida.TALLAINI} - ${corrida.TALLAFIN} (${corrida.CLASIFIDES ?? ''})`;
                    $select.append(`<option value="${corrida.CORRIDANO}">${escaparHtml(etiqueta)}</option>`);
                });
            }
        });
    }

    function cargarModelos() {
        mostrarSpinner(true);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: {
                marcano: marcano,
                search: terminoBusqueda,
                page: paginaActual,
            },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    renderizarTabla(respuesta.data.modelos);
                    renderizarPaginacion(respuesta.data.paginaActual, respuesta.data.totalPaginas);
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

    function renderizarTabla(modelos) {
        const $tbody = $('#tabla-modelos tbody');
        $tbody.empty();

        if (!modelos || modelos.length === 0) {
            $tbody.append('<tr><td colspan="9" class="text-center text-muted py-3">No se encontraron modelos.</td></tr>');
            return;
        }

        modelos.forEach(function (modelo) {
            const corridaTexto = modelo.TALLAINI !== null && modelo.TALLAINI !== undefined
                ? `${modelo.TALLAINI} - ${modelo.TALLAFIN} (${modelo.CLASIFIDES ?? ''})`
                : '';
            const precioco = modelo.PRECIOCO !== null ? `$${parseFloat(modelo.PRECIOCO).toFixed(2)}` : '';
            const precioventa = modelo.PRECIOVE !== null ? `$${parseFloat(modelo.PRECIOVE).toFixed(2)}` : '';

            const fila = `
                <tr>
                    <td>${escaparHtml(modelo.MODELONO)}</td>
                    <td>${escaparHtml(modelo.MODELODES)}</td>
                    <td>${escaparHtml(modelo.COLOR)}</td>
                    <td>${escaparHtml(modelo.MATERIAL)}</td>
                    <td>${escaparHtml(modelo.SUELA)}</td>
                    <td>${precioco}</td>
                    <td>${precioventa}</td>
                    <td>${escaparHtml(corridaTexto)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-modelono="${modelo.MODELONO}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-modelono="${modelo.MODELONO}">
                            <i class="bi bi-trash-fill me-1"></i> Eliminar
                        </button>
                    </td>
                </tr>`;
            $tbody.append(fila);
        });
    }

    function renderizarPaginacion(paginaActualResp, totalPaginas) {
        const $ul = $('#paginacion-modelos');
        $ul.empty();

        if (totalPaginas <= 1) {
            return;
        }

        for (let i = 1; i <= totalPaginas; i++) {
            const activo = i === paginaActualResp ? 'active' : '';
            $ul.append(`
                <li class="page-item ${activo}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                </li>
            `);
        }
    }

    function abrirModalCrear() {
        limpiarFormulario();
        $('#modalModeloLabel').text('Agregar Modelo');
        $('#modo-formulario').val('crear');
        $('#form-modelo input[name="MARCANO"]').val(marcano);
        $('#modalModelo').modal('show');
    }

    function abrirModalEditar(modelono) {
        limpiarFormulario();
        $('#modalModeloLabel').text('Editar Modelo');
        $('#modo-formulario').val('editar');
        $('#form-modelo input[name="MARCANO"]').val(marcano);
        $('#form-modelo input[name="MODELONO"]').val(modelono);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { marcano: marcano, modelono: modelono },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    const m = respuesta.data;
                    $('#form-modelo input[name="MODELODES"]').val(m.MODELODES);
                    $('#form-modelo input[name="COLOR"]').val(m.COLOR);
                    $('#form-modelo input[name="MATERIAL"]').val(m.MATERIAL);
                    $('#form-modelo input[name="SUELA"]').val(m.SUELA);
                    $('#form-modelo input[name="PRECIOCO"]').val(m.PRECIOCO);
                    $('#form-modelo input[name="PRECIOVE"]').val(m.PRECIOVE);
                    $('#form-modelo select[name="SITUACION"]').val(m.SITUACION || 'A');
                    $('#form-modelo select[name="CORRIDANO"]').val(m.CORRIDANO);
                    $('#form-modelo input[name="fecha"]').val(m.fecha ? m.fecha.substring(0, 10) : '');
                    $('#modalModelo').modal('show');
                } else {
                    Swal.fire('Error', respuesta.message || 'No se encontró el modelo.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
            });
    }

    function guardarModelo() {
        const modo = $('#modo-formulario').val();
        const modelono = $('#form-modelo input[name="MODELONO"]').val();

        limpiarErrores();

        const datos = {
            MARCANO: marcano,
            MODELODES: $('#form-modelo input[name="MODELODES"]').val(),
            COLOR: $('#form-modelo input[name="COLOR"]').val(),
            MATERIAL: $('#form-modelo input[name="MATERIAL"]').val(),
            SUELA: $('#form-modelo input[name="SUELA"]').val(),
            PRECIOCO: $('#form-modelo input[name="PRECIOCO"]').val(),
            PRECIOVE: $('#form-modelo input[name="PRECIOVE"]').val(),
            SITUACION: $('#form-modelo select[name="SITUACION"]').val(),
            CORRIDANO: $('#form-modelo select[name="CORRIDANO"]').val(),
            fecha: $('#form-modelo input[name="fecha"]').val(),
        };

        const config = {
            url: modo === 'editar' ? `${API_URL}?marcano=${encodeURIComponent(marcano)}&modelono=${encodeURIComponent(modelono)}` : API_URL,
            method: modo === 'editar' ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        };

        $('#btn-guardar-modelo').prop('disabled', true);

        $.ajax(config)
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalModelo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: modo === 'editar' ? 'Modelo actualizado' : 'Modelo creado',
                        text: respuesta.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    cargarModelos();
                } else {
                    manejarErroresValidacion(respuesta);
                }
            })
            .fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                if (respuesta) {
                    manejarErroresValidacion(respuesta);
                } else {
                    Swal.fire('Error', 'Ocurrió un error de conexión al guardar.', 'error');
                }
            })
            .always(function () {
                $('#btn-guardar-modelo').prop('disabled', false);
            });
    }

    function manejarErroresValidacion(respuesta) {
        if (respuesta.data && respuesta.data.errores) {
            const errores = respuesta.data.errores;
            Object.keys(errores).forEach(function (campo) {
                const $input = $(`#form-modelo [name="${campo}"]`);
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(errores[campo]);
            });
        } else {
            Swal.fire('Error', respuesta.message || 'No se pudo guardar el modelo.', 'error');
        }
    }

    function confirmarEliminacion(modelono) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarModelo(modelono);
            }
        });
    }

    function eliminarModelo(modelono) {
        $.ajax({
            url: `${API_URL}?marcano=${encodeURIComponent(marcano)}&modelono=${encodeURIComponent(modelono)}`,
            method: 'DELETE',
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: respuesta.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    cargarModelos();
                } else {
                    Swal.fire('No se pudo eliminar', respuesta.message, 'warning');
                }
            })
            .fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                Swal.fire('No se pudo eliminar', respuesta ? respuesta.message : 'Error de conexión.', 'warning');
            });
    }

    /* ================================================================ */
    /*  Utilidades                                                       */
    /* ================================================================ */

    function limpiarFormulario() {
        $('#form-modelo')[0].reset();
        $('#form-modelo input[name="MODELONO"]').val('');
        $('#form-modelo select[name="SITUACION"]').val('A');
        limpiarErrores();
    }

    function limpiarErrores() {
        $('#form-modelo .is-invalid').removeClass('is-invalid');
        $('#form-modelo .invalid-feedback').text('');
    }

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
