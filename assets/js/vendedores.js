$(function () {
    const API_URL = 'api/vendedores.php';
    let temporizadorBusqueda = null;

    cargarVendedores();

    $('#buscador-vendedor').on('input', function () {
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(cargarVendedores, 400);
    });

    $('#btn-agregar-vendedor').on('click', function () {
        abrirModalCrear();
    });

    $('#tabla-vendedores').on('click', '.btn-editar', function () {
        abrirModalEditar($(this).data('id'));
    });

    $('#tabla-vendedores').on('click', '.btn-eliminar', function () {
        confirmarEliminacion($(this).data('id'), $(this).data('nombre'));
    });

    $('#form-vendedor').on('submit', function (e) {
        e.preventDefault();
        guardarVendedor();
    });

    function cargarVendedores() {
        mostrarSpinner(true);
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { buscar: $('#buscador-vendedor').val() },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    renderizarTabla(respuesta.data);
                } else {
                    Swal.fire('Error', respuesta.message, 'error');
                }
            })
            .always(function () {
                mostrarSpinner(false);
            });
    }

    function renderizarTabla(vendedores) {
        const $tbody = $('#tabla-vendedores tbody');
        $tbody.empty();

        if (!vendedores || vendedores.length === 0) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">No hay vendedores(as) registrados(as).</td></tr>');
            return;
        }

        vendedores.forEach(function (v) {
            const badgeEstado = v.ESTADO === 'C'
                ? '<span class="badge bg-danger">Cancelado</span>'
                : '<span class="badge bg-success">Activo</span>';

            $tbody.append(`
                <tr>
                    <td>${v.VENDEDORNO}</td>
                    <td>${escaparHtml(v.NOMBRE)}</td>
                    <td>${escaparHtml(v.TELEFONO1 || '')}</td>
                    <td>${v.VALEINICIA ?? ''} - ${v.VALETERMINA ?? ''}</td>
                    <td>${badgeEstado}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-id="${v.VENDEDORNO}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${v.VENDEDORNO}" data-nombre="${escaparHtml(v.NOMBRE)}">
                            <i class="bi bi-trash-fill me-1"></i> Eliminar
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    function abrirModalCrear() {
        limpiarFormulario();
        $('#modalVendedorLabel').text('Agregar Vendedor(a)');
        $('#modo-formulario').val('crear');
        $('#form-vendedor input[name="vendedorno"]').prop('readonly', false);
        $('#modalVendedor').modal('show');
    }

    function abrirModalEditar(id) {
        limpiarFormulario();
        $('#modalVendedorLabel').text('Editar Vendedor(a)');
        $('#modo-formulario').val('editar');
        $('#form-vendedor input[name="vendedorno"]').prop('readonly', true);

        $.get(API_URL, { id: id }, function (respuesta) {
            if (respuesta.success) {
                const v = respuesta.data;
                $('#form-vendedor input[name="vendedorno"]').val(v.VENDEDORNO);
                $('#form-vendedor input[name="nombre"]').val(v.NOMBRE);
                $('#form-vendedor input[name="direccion"]').val(v.DIRECCION);
                $('#form-vendedor input[name="telefono1"]').val(v.TELEFONO1);
                $('#form-vendedor input[name="telefono2"]').val(v.TELEFONO2);
                $('#form-vendedor input[name="telefono3"]').val(v.TELEFONO3);
                $('#form-vendedor input[name="valeinicia"]').val(v.VALEINICIA);
                $('#form-vendedor input[name="valetermina"]').val(v.VALETERMINA);
                $('#form-vendedor select[name="estado"]').val(v.ESTADO || 'A');
                $('#modalVendedor').modal('show');
            } else {
                Swal.fire('Error', respuesta.message, 'error');
            }
        });
    }

    function guardarVendedor() {
        const modo = $('#modo-formulario').val();
        const id = $('#form-vendedor input[name="vendedorno"]').val();

        limpiarErrores();

        const datos = {
            vendedorno: id,
            nombre: $('#form-vendedor input[name="nombre"]').val(),
            direccion: $('#form-vendedor input[name="direccion"]').val(),
            telefono1: $('#form-vendedor input[name="telefono1"]').val(),
            telefono2: $('#form-vendedor input[name="telefono2"]').val(),
            telefono3: $('#form-vendedor input[name="telefono3"]').val(),
            valeinicia: $('#form-vendedor input[name="valeinicia"]').val(),
            valetermina: $('#form-vendedor input[name="valetermina"]').val(),
            estado: $('#form-vendedor select[name="estado"]').val(),
        };

        const config = {
            url: modo === 'editar' ? `${API_URL}?id=${encodeURIComponent(id)}` : API_URL,
            method: modo === 'editar' ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        };

        $.ajax(config)
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalVendedor').modal('hide');
                    Swal.fire({ icon: 'success', title: respuesta.message, timer: 1500, showConfirmButton: false });
                    cargarVendedores();
                } else {
                    manejarErroresValidacion(respuesta);
                }
            })
            .fail(function (xhr) {
                manejarErroresValidacion(xhr.responseJSON || {});
            });
    }

    function manejarErroresValidacion(respuesta) {
        if (respuesta.data && respuesta.data.errores) {
            Object.keys(respuesta.data.errores).forEach(function (campo) {
                const $input = $(`#form-vendedor [name="${campo}"]`);
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(respuesta.data.errores[campo]);
            });
        } else {
            Swal.fire('Error', respuesta.message || 'No se pudo guardar.', 'error');
        }
    }

    function confirmarEliminacion(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Se eliminará a "${nombre}" permanentemente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;
            $.ajax({ url: `${API_URL}?id=${encodeURIComponent(id)}`, method: 'DELETE', dataType: 'json' })
                .done(function (respuesta) {
                    if (respuesta.success) {
                        Swal.fire({ icon: 'success', title: respuesta.message, timer: 1500, showConfirmButton: false });
                        cargarVendedores();
                    } else {
                        Swal.fire('No se pudo eliminar', respuesta.message, 'warning');
                    }
                });
        });
    }

    function limpiarFormulario() {
        $('#form-vendedor')[0].reset();
        limpiarErrores();
    }

    function limpiarErrores() {
        $('#form-vendedor .is-invalid').removeClass('is-invalid');
        $('#form-vendedor .invalid-feedback').text('');
    }

    function mostrarSpinner(mostrar) {
        $('#spinner-carga').toggle(mostrar);
    }

    function escaparHtml(texto) {
        if (texto === null || texto === undefined) return '';
        return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
