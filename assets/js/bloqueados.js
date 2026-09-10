$(function () {
    const API_URL = 'api/bloqueados.php';

    cargarBloqueados();

    $('#btn-agregar-bloqueo').on('click', function () {
        abrirModalCrear();
    });

    $('#tabla-bloqueados').on('click', '.btn-editar', function () {
        abrirModalEditar($(this).data('vendedor'), $(this).data('vale'));
    });

    $('#tabla-bloqueados').on('click', '.btn-eliminar', function () {
        confirmarEliminacion($(this).data('vendedor'), $(this).data('vale'));
    });

    $('#form-bloqueo').on('submit', function (e) {
        e.preventDefault();
        guardarBloqueo();
    });

    function cargarBloqueados() {
        mostrarSpinner(true);
        $.get(API_URL, function (respuesta) {
            if (respuesta.success) {
                renderizarTabla(respuesta.data);
            } else {
                Swal.fire('Error', respuesta.message, 'error');
            }
        }).always(function () {
            mostrarSpinner(false);
        });
    }

    function renderizarTabla(bloqueos) {
        const $tbody = $('#tabla-bloqueados tbody');
        $tbody.empty();

        if (!bloqueos || bloqueos.length === 0) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">No hay vales bloqueados.</td></tr>');
            return;
        }

        bloqueos.forEach(function (b) {
            $tbody.append(`
                <tr>
                    <td>${b.novendedor}</td>
                    <td>${escaparHtml(b.vendedor_nombre || '—')}</td>
                    <td>${b.novale}</td>
                    <td>${b.fecha || ''}</td>
                    <td>${escaparHtml(b.motivo)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-vendedor="${b.novendedor}" data-vale="${b.novale}">
                            <i class="bi bi-pencil-square me-1"></i> Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-vendedor="${b.novendedor}" data-vale="${b.novale}">
                            <i class="bi bi-unlock-fill me-1"></i> Desbloquear
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    function abrirModalCrear() {
        limpiarFormulario();
        $('#modalBloqueoLabel').text('Bloquear Vale');
        $('#modo-formulario').val('crear');
        $('#form-bloqueo input[name="novendedor"]').prop('readonly', false);
        $('#form-bloqueo input[name="novale"]').prop('readonly', false);
        $('#form-bloqueo input[name="fecha"]').val(new Date().toISOString().slice(0, 10));
        $('#modalBloqueo').modal('show');
    }

    function abrirModalEditar(novendedor, novale) {
        limpiarFormulario();
        $('#modalBloqueoLabel').text('Editar Bloqueo');
        $('#modo-formulario').val('editar');
        $('#form-bloqueo input[name="novendedor"]').val(novendedor).prop('readonly', true);
        $('#form-bloqueo input[name="novale"]').val(novale).prop('readonly', true);

        $.get(API_URL, function (respuesta) {
            if (respuesta.success) {
                const registro = respuesta.data.find(b => b.novendedor == novendedor && b.novale == novale);
                if (registro) {
                    $('#form-bloqueo input[name="fecha"]').val(registro.fecha);
                    $('#form-bloqueo input[name="motivo"]').val(registro.motivo);
                    $('#modalBloqueo').modal('show');
                }
            }
        });
    }

    function guardarBloqueo() {
        const modo = $('#modo-formulario').val();
        const novendedor = $('#form-bloqueo input[name="novendedor"]').val();
        const novale = $('#form-bloqueo input[name="novale"]').val();

        limpiarErrores();

        const datos = {
            novendedor: novendedor,
            novale: novale,
            fecha: $('#form-bloqueo input[name="fecha"]').val(),
            motivo: $('#form-bloqueo input[name="motivo"]').val(),
        };

        const config = {
            url: modo === 'editar' ? `${API_URL}?novendedor=${encodeURIComponent(novendedor)}&novale=${encodeURIComponent(novale)}` : API_URL,
            method: modo === 'editar' ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        };

        $.ajax(config)
            .done(function (respuesta) {
                if (respuesta.success) {
                    $('#modalBloqueo').modal('hide');
                    Swal.fire({ icon: 'success', title: respuesta.message, timer: 1500, showConfirmButton: false });
                    cargarBloqueados();
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
                const $input = $(`#form-bloqueo [name="${campo}"]`);
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text(respuesta.data.errores[campo]);
            });
        } else {
            Swal.fire('Error', respuesta.message || 'No se pudo guardar.', 'error');
        }
    }

    function confirmarEliminacion(novendedor, novale) {
        Swal.fire({
            title: '¿Desbloquear este vale?',
            text: `Vendedor(a) #${novendedor}, vale #${novale}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0d47a1',
            confirmButtonText: 'Sí, desbloquear',
            cancelButtonText: 'Cancelar',
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;
            $.ajax({
                url: `${API_URL}?novendedor=${encodeURIComponent(novendedor)}&novale=${encodeURIComponent(novale)}`,
                method: 'DELETE',
                dataType: 'json',
            }).done(function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({ icon: 'success', title: respuesta.message, timer: 1500, showConfirmButton: false });
                    cargarBloqueados();
                } else {
                    Swal.fire('No se pudo desbloquear', respuesta.message, 'warning');
                }
            });
        });
    }

    function limpiarFormulario() {
        $('#form-bloqueo')[0].reset();
        limpiarErrores();
    }

    function limpiarErrores() {
        $('#form-bloqueo .is-invalid').removeClass('is-invalid');
        $('#form-bloqueo .invalid-feedback').text('');
    }

    function mostrarSpinner(mostrar) {
        $('#spinner-carga').toggle(mostrar);
    }

    function escaparHtml(texto) {
        if (texto === null || texto === undefined) return '';
        return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
