$(function () {
    const API_URL = 'api/usuarios.php';

    cargarUsuarios();

    $('#btn-agregar-usuario').on('click', function () {
        abrirModalCrear();
    });

    $('#tabla-usuarios').on('click', '.btn-editar', function () {
        abrirModalEditar($(this).data('id'));
    });

    $('#tabla-usuarios').on('click', '.btn-eliminar', function () {
        confirmarEliminacion($(this).data('id'), $(this).data('nombre'));
    });

    $('#form-usuario').on('submit', function (e) {
        e.preventDefault();
        guardarUsuario();
    });

    function cargarUsuarios() {
        mostrarSpinner(true);

        $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    renderizarTabla(respuesta.data);
                } else {
                    Swal.fire('Error', respuesta.message || 'No se pudieron cargar los usuarios.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
            })
            .always(function () {
                mostrarSpinner(false);
            });
    }

    function renderizarTabla(usuarios) {
        const $tbody = $('#tabla-usuarios tbody');
        $tbody.empty();

        if (!usuarios || usuarios.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">No hay usuarios registrados.</td></tr>');
            return;
        }

        usuarios.forEach(function (u) {
            const badgeTipo = u.tipo === 'administrador'
                ? '<span class="badge bg-primary">Administrador</span>'
                : '<span class="badge bg-secondary">General</span>';
            const badgeEstado = parseInt(u.activo, 10) === 1
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-danger">Inactivo</span>';

            $tbody.append(`
                <tr>
                    <td>${escaparHtml(u.nombre)}</td>
                    <td>${escaparHtml(u.usuario)}</td>
                    <td>${badgeTipo}</td>
                    <td>${badgeEstado}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-id="${u.id}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${u.id}" data-nombre="${escaparHtml(u.nombre)}">
                            <i class="bi bi-trash-fill me-1"></i> Eliminar
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    function abrirModalCrear() {
        limpiarFormulario();
        $('#modalUsuarioLabel').text('Agregar Usuario');
        $('#modo-formulario').val('crear');
        $('#password').prop('required', true);
        $('#texto-password').text('Contraseña');
        $('#modalUsuario').modal('show');
    }

    function abrirModalEditar(id) {
        limpiarFormulario();
        $('#modalUsuarioLabel').text('Editar Usuario');
        $('#modo-formulario').val('editar');
        $('#form-usuario input[name="id"]').val(id);
        $('#password').prop('required', false);
        $('#texto-password').text('Nueva contraseña (dejar en blanco para no cambiarla)');

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { id: id },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    const u = respuesta.data;
                    $('#form-usuario input[name="nombre"]').val(u.nombre);
                    $('#form-usuario input[name="usuario"]').val(u.usuario);
                    $('#form-usuario select[name="tipo"]').val(u.tipo);
                    $('#form-usuario input[name="activo"]').prop('checked', parseInt(u.activo, 10) === 1);
                    $('#modalUsuario').modal('show');
                } else {
                    Swal.fire('Error', respuesta.message || 'No se encontró el usuario.', 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
            });
    }

    function guardarUsuario() {
        const modo = $('#modo-formulario').val();
        const id = $('#form-usuario input[name="id"]').val();

        limpiarErrores();

        const datos = {
            nombre: $('#form-usuario input[name="nombre"]').val(),
            usuario: $('#form-usuario input[name="usuario"]').val(),
            password: $('#form-usuario input[name="password"]').val(),
            tipo: $('#form-usuario select[name="tipo"]').val(),
            activo: $('#form-usuario input[name="activo"]').is(':checked'),
        };

        const config = {
            url: modo === 'editar' ? `${API_URL}?id=${encodeURIComponent(id)}` : API_URL,
            method: modo === 'editar' ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        };

        $('#btn-guardar-usuario').prop('disabled', true);

        $.ajax(config)
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalUsuario').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: modo === 'editar' ? 'Usuario actualizado' : 'Usuario creado',
                        text: respuesta.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    cargarUsuarios();
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
                $('#btn-guardar-usuario').prop('disabled', false);
            });
    }

    function manejarErroresValidacion(respuesta) {
        if (respuesta.data && respuesta.data.errores) {
            const errores = respuesta.data.errores;
            Object.keys(errores).forEach(function (campo) {
                const $input = $(`#form-usuario [name="${campo}"]`);
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(errores[campo]);
            });
        } else {
            Swal.fire('Error', respuesta.message || 'No se pudo guardar el usuario.', 'error');
        }
    }

    function confirmarEliminacion(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Se eliminará el usuario "${nombre}" permanentemente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarUsuario(id);
            }
        });
    }

    function eliminarUsuario(id) {
        $.ajax({
            url: `${API_URL}?id=${encodeURIComponent(id)}`,
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
                    cargarUsuarios();
                } else {
                    Swal.fire('No se pudo eliminar', respuesta.message, 'warning');
                }
            })
            .fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                Swal.fire('No se pudo eliminar', respuesta ? respuesta.message : 'Error de conexión.', 'warning');
            });
    }

    function limpiarFormulario() {
        $('#form-usuario')[0].reset();
        $('#form-usuario input[name="id"]').val('');
        $('#form-usuario input[name="activo"]').prop('checked', true);
        limpiarErrores();
    }

    function limpiarErrores() {
        $('#form-usuario .is-invalid').removeClass('is-invalid');
        $('#form-usuario .invalid-feedback').text('');
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
