$(function () {
    const API_URL = 'api/corridas.php';

    // MARCANO viene en la URL: corridas.php?marcano=1
    const parametros = new URLSearchParams(window.location.search);
    const marcano = parametros.get('marcano');

    if (!marcano) {
        Swal.fire('Error', 'No se especificó la marca (MARCANO) en la URL.', 'error');
        return;
    }

    // -----------------------------------------------------------------
    // Carga inicial
    // -----------------------------------------------------------------
    cargarMarca();
    cargarClasificaciones();
    cargarCorridas();

    // -----------------------------------------------------------------
    // Botón "Agregar Corrida"
    // -----------------------------------------------------------------
    $('#btn-agregar-corrida').on('click', function () {
        abrirModalCrear();
    });

    // -----------------------------------------------------------------
    // Delegación de eventos para botones dentro de la tabla (dinámica)
    // -----------------------------------------------------------------
    $('#tabla-corridas').on('click', '.btn-editar', function () {
        const corridano = $(this).data('corridano');
        abrirModalEditar(corridano);
    });

    $('#tabla-corridas').on('click', '.btn-eliminar', function () {
        const corridano = $(this).data('corridano');
        confirmarEliminacion(corridano);
    });

    // -----------------------------------------------------------------
    // Envío del formulario (crear o editar según el modo del modal)
    // -----------------------------------------------------------------
    $('#form-corrida').on('submit', function (e) {
        e.preventDefault();
        guardarCorrida();
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
                    $('#titulo-marca').text(`Corridas de la marca: ${respuesta.data.MARCANO} - ${respuesta.data.MARCADES}`);
                    $('#link-volver').attr('href', `index.php`);
                } else {
                    Swal.fire('Error', respuesta.message || 'Marca no encontrada.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar la marca.', 'error');
            });
    }

    function cargarClasificaciones() {
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'clasificaciones' },
            dataType: 'json',
        }).done(function (respuesta) {
            if (respuesta.success) {
                const $select = $('#CLASIFICA');
                $select.empty().append('<option value="">Seleccione una opción</option>');
                respuesta.data.forEach(function (clasifica) {
                    $select.append(`<option value="${clasifica.CLASIFINO}">${escaparHtml(clasifica.CLASIFIDES)}</option>`);
                });
            }
        });
    }

    function cargarCorridas() {
        mostrarSpinner(true);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { marcano: marcano },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    renderizarTabla(respuesta.data);
                } else {
                    Swal.fire('Error', respuesta.message || 'No se pudieron cargar las corridas.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar las corridas.', 'error');
            })
            .always(function () {
                mostrarSpinner(false);
            });
    }

    function renderizarTabla(corridas) {
        const $tbody = $('#tabla-corridas tbody');
        $tbody.empty();

        if (!corridas || corridas.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">No hay corridas registradas para esta marca.</td></tr>');
            return;
        }

        corridas.forEach(function (corrida) {
            const clasifDes = corrida.CLASIFIDES ? corrida.CLASIFIDES : corrida.CLASIFICA;
            const fila = `
                <tr>
                    <td>${escaparHtml(corrida.CORRIDANO)}</td>
                    <td>${escaparHtml(corrida.TALLAINI)}</td>
                    <td>${escaparHtml(corrida.TALLAFIN)}</td>
                    <td>${escaparHtml(clasifDes)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-corridano="${corrida.CORRIDANO}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-corridano="${corrida.CORRIDANO}">
                            <i class="bi bi-trash-fill me-1"></i> Borrar
                        </button>
                    </td>
                </tr>`;
            $tbody.append(fila);
        });
    }

    function abrirModalCrear() {
        limpiarFormulario();
        $('#modalCorridaLabel').text('Agregar Corrida');
        $('#modo-formulario').val('crear');
        $('#form-corrida input[name="MARCANO"]').val(marcano);
        $('#modalCorrida').modal('show');
    }

    function abrirModalEditar(corridano) {
        limpiarFormulario();
        $('#modalCorridaLabel').text('Editar Corrida');
        $('#modo-formulario').val('editar');
        $('#form-corrida input[name="MARCANO"]').val(marcano);
        $('#form-corrida input[name="CORRIDANO"]').val(corridano);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { marcano: marcano, corridano: corridano },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#form-corrida input[name="TALLAINI"]').val(respuesta.data.TALLAINI);
                    $('#form-corrida input[name="TALLAFIN"]').val(respuesta.data.TALLAFIN);
                    $('#form-corrida select[name="CLASIFICA"]').val(respuesta.data.CLASIFICA);
                    $('#modalCorrida').modal('show');
                } else {
                    Swal.fire('Error', respuesta.message || 'No se encontró la corrida.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
            });
    }

    function guardarCorrida() {
        const modo = $('#modo-formulario').val();
        const corridano = $('#form-corrida input[name="CORRIDANO"]').val();

        limpiarErrores();

        const datos = {
            MARCANO: marcano,
            TALLAINI: $('#form-corrida input[name="TALLAINI"]').val(),
            TALLAFIN: $('#form-corrida input[name="TALLAFIN"]').val(),
            CLASIFICA: $('#form-corrida select[name="CLASIFICA"]').val(),
        };

        const config = {
            url: modo === 'editar' ? `${API_URL}?marcano=${encodeURIComponent(marcano)}&corridano=${encodeURIComponent(corridano)}` : API_URL,
            method: modo === 'editar' ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        };

        $('#btn-guardar-corrida').prop('disabled', true);

        $.ajax(config)
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalCorrida').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: modo === 'editar' ? 'Corrida actualizada' : 'Corrida creada',
                        text: respuesta.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    cargarCorridas();
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
                $('#btn-guardar-corrida').prop('disabled', false);
            });
    }

    function manejarErroresValidacion(respuesta) {
        if (respuesta.data && respuesta.data.errores) {
            const errores = respuesta.data.errores;
            Object.keys(errores).forEach(function (campo) {
                const $input = $(`#form-corrida [name="${campo}"]`);
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(errores[campo]);
            });
        } else {
            Swal.fire('Error', respuesta.message || 'No se pudo guardar la corrida.', 'error');
        }
    }

    function confirmarEliminacion(corridano) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarCorrida(corridano);
            }
        });
    }

    function eliminarCorrida(corridano) {
        $.ajax({
            url: `${API_URL}?marcano=${encodeURIComponent(marcano)}&corridano=${encodeURIComponent(corridano)}`,
            method: 'DELETE',
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminada',
                        text: respuesta.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    cargarCorridas();
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
        $('#form-corrida')[0].reset();
        $('#form-corrida input[name="CORRIDANO"]').val('');
        limpiarErrores();
    }

    function limpiarErrores() {
        $('#form-corrida .is-invalid').removeClass('is-invalid');
        $('#form-corrida .invalid-feedback').text('');
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
