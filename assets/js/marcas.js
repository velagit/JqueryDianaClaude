$(function () {
    const API_URL = 'api/marcas.php';

    let paginaActual = 1;
    let terminoBusqueda = '';
    let temporizadorBusqueda = null;

    // -----------------------------------------------------------------
    // Carga inicial
    // -----------------------------------------------------------------
    cargarMarcas();

    // -----------------------------------------------------------------
    // Buscador (con debounce, igual que el "presione enter" pero más ágil)
    // -----------------------------------------------------------------
    $('#buscador-marca').on('input', function () {
        const valor = $(this).val();
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(function () {
            terminoBusqueda = valor;
            paginaActual = 1;
            cargarMarcas();
        }, 400);
    });

    // -----------------------------------------------------------------
    // Botón "Agregar Marca"
    // -----------------------------------------------------------------
    $('#btn-agregar-marca').on('click', function () {
        abrirModalCrear();
    });

    // -----------------------------------------------------------------
    // Delegación de eventos para botones dentro de la tabla (dinámica)
    // -----------------------------------------------------------------
    $('#tabla-marcas').on('click', '.btn-editar', function () {
        const marcano = $(this).data('marcano');
        abrirModalEditar(marcano);
    });

    $('#tabla-marcas').on('click', '.btn-eliminar', function () {
        const marcano = $(this).data('marcano');
        confirmarEliminacion(marcano);
    });

    // Paginación (delegado porque los botones se regeneran)
    $('#paginacion-marcas').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const pagina = $(this).data('pagina');
        if (pagina) {
            paginaActual = pagina;
            cargarMarcas();
        }
    });

    // -----------------------------------------------------------------
    // Envío del formulario (crear o editar según el modo del modal)
    // -----------------------------------------------------------------
    $('#form-marca').on('submit', function (e) {
        e.preventDefault();
        guardarMarca();
    });

    /* ================================================================ */
    /*  Funciones principales                                            */
    /* ================================================================ */

    function cargarMarcas() {
        mostrarSpinner(true);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: {
                buscar: terminoBusqueda,
                page: paginaActual,
            },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    renderizarTabla(respuesta.data.marcas);
                    renderizarPaginacion(respuesta.data.paginaActual, respuesta.data.totalPaginas);
                } else {
                    Swal.fire('Error', respuesta.message || 'No se pudieron cargar las marcas.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión al cargar las marcas.', 'error');
            })
            .always(function () {
                mostrarSpinner(false);
            });
    }

    function renderizarTabla(marcas) {
        const $tbody = $('#tabla-marcas tbody');
        $tbody.empty();

        if (!marcas || marcas.length === 0) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">No se encontraron marcas.</td></tr>');
            return;
        }

        marcas.forEach(function (marca) {
            const fila = `
                <tr>
                    <td>${escaparHtml(marca.MARCANO)}</td>
                    <td>${escaparHtml(marca.MARCADES)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-marcano="${marca.MARCANO}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-marcano="${marca.MARCANO}">
                            <i class="bi bi-trash-fill me-1"></i> Eliminar
                        </button>
                    </td>
                    <td>
                        <a href="corridas.php?marcano=${marca.MARCANO}" class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-plus me-1"></i> Corridas
                        </a>
                    </td>
                    <td>
                        <a href="modelos.php?marcano=${marca.MARCANO}" class="btn btn-sm btn-secondary">
                            <i class="bi bi-file-earmark-plus me-1"></i> Modelos
                        </a>
                    </td>
                    <td>
                        <a href="existencias.php?marcano=${marca.MARCANO}" class="btn btn-sm btn-info">
                            <i class="bi bi-file-earmark-plus me-1"></i> Entradas
                        </a>
                    </td>
                </tr>`;
            $tbody.append(fila);
        });
    }

    function renderizarPaginacion(paginaActualResp, totalPaginas) {
        const $ul = $('#paginacion-marcas');
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
        $('#modalMarcaLabel').text('Agregar Nueva Marca');
        $('#form-marca input[name="MARCANO"]').prop('readonly', true);
        $('#modo-formulario').val('crear');

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'nuevo_codigo' },
            dataType: 'json',
        }).done(function (respuesta) {
            if (respuesta.success) {
                $('#form-marca input[name="MARCANO"]').val(respuesta.data.siguienteCodigo);
            }
            $('#modalMarca').modal('show');
        });
    }

    function abrirModalEditar(marcano) {
        limpiarFormulario();
        $('#modalMarcaLabel').text('Modificar Marca');
        $('#form-marca input[name="MARCANO"]').prop('readonly', true);
        $('#modo-formulario').val('editar');

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { MARCANO: marcano },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#form-marca input[name="MARCANO"]').val(respuesta.data.MARCANO);
                    $('#form-marca input[name="MARCADES"]').val(respuesta.data.MARCADES);
                    $('#modalMarca').modal('show');
                } else {
                    Swal.fire('Error', respuesta.message || 'No se encontró la marca.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
            });
    }

    function guardarMarca() {
        const modo = $('#modo-formulario').val();
        const marcano = $('#form-marca input[name="MARCANO"]').val();
        const marcades = $('#form-marca input[name="MARCADES"]').val();

        limpiarErrores();

        const datos = { MARCANO: marcano, MARCADES: marcades };

        const config = {
            url: modo === 'editar' ? `${API_URL}?MARCANO=${encodeURIComponent(marcano)}` : API_URL,
            method: modo === 'editar' ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        };

        $('#btn-guardar-marca').prop('disabled', true);

        $.ajax(config)
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalMarca').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: modo === 'editar' ? 'Marca actualizada' : 'Marca creada',
                        text: respuesta.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    cargarMarcas();
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
                $('#btn-guardar-marca').prop('disabled', false);
            });
    }

    function manejarErroresValidacion(respuesta) {
        if (respuesta.data && respuesta.data.errores) {
            const errores = respuesta.data.errores;
            Object.keys(errores).forEach(function (campo) {
                const $input = $(`#form-marca [name="${campo}"]`);
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(errores[campo]);
            });
        } else {
            Swal.fire('Error', respuesta.message || 'No se pudo guardar la marca.', 'error');
        }
    }

    function confirmarEliminacion(marcano) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará la marca permanentemente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarMarca(marcano);
            }
        });
    }

    function eliminarMarca(marcano) {
        $.ajax({
            url: `${API_URL}?MARCANO=${encodeURIComponent(marcano)}`,
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
                    cargarMarcas();
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
        $('#form-marca')[0].reset();
        limpiarErrores();
    }

    function limpiarErrores() {
        $('#form-marca .is-invalid').removeClass('is-invalid');
        $('#form-marca .invalid-feedback').text('');
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
