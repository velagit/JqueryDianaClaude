$(function () {
    const API_URL = 'api/consulta_vales.php';

    buscar(); // carga inicial sin filtros (todas las notas)

    $('#input-vendedor').on('blur', function () {
        const numero = $(this).val();
        if (!numero) {
            $('#texto-nombre-vendedor').text('');
            return;
        }
        $.get(API_URL, { action: 'vendedor', numero: numero }, function (respuesta) {
            if (respuesta.success) {
                $('#texto-nombre-vendedor').text(respuesta.data.NOMBRE);
            } else {
                $('#texto-nombre-vendedor').text('');
                Swal.fire('No encontrado', respuesta.message, 'info');
            }
        });
    });

    $('#form-consulta-vales').on('submit', function (e) {
        e.preventDefault();
        buscar();
    });

    $('#btn-limpiar-filtros').on('click', function () {
        $('#form-consulta-vales')[0].reset();
        $('#texto-nombre-vendedor').text('');
        buscar();
    });

    $('#tabla-notas').on('click', 'tbody tr', function () {
        const ventano = $(this).data('ventano');
        if (!ventano) return;
        $('#tabla-notas tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
        cargarDetalle(ventano);
    });

    function buscar() {
        mostrarSpinner(true);
        $.get(API_URL, {
            action: 'buscar',
            novale: $('#input-vale').val(),
            novendedor: $('#input-vendedor').val(),
            fecha: $('#input-fecha').val(),
        }, function (respuesta) {
            if (respuesta.success) {
                renderizarNotas(respuesta.data.notas, respuesta.data.total);
            } else {
                Swal.fire('Error', respuesta.message, 'error');
            }
        }).always(function () {
            mostrarSpinner(false);
        });
    }

    function renderizarNotas(notas, total) {
        const $tbody = $('#tabla-notas tbody');
        $tbody.empty();
        $('#tabla-detalle tbody').empty();

        if (!notas || notas.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">Sin resultados.</td></tr>');
        } else {
            notas.forEach(function (n) {
                $tbody.append(`
                    <tr data-ventano="${n.VENTANO}" style="cursor:pointer;">
                        <td>${n.VENTANO}</td>
                        <td>${n.FECHAVEN}</td>
                        <td>${n.VALENO}</td>
                        <td>${escaparHtml(n.vendedor_nombre || n.VENDENO)}</td>
                        <td class="text-end">${formatoMoneda(n.IMPORTEVEN)}</td>
                    </tr>
                `);
            });
        }

        $('#texto-total-notas').text(formatoMoneda(total));
    }

    function cargarDetalle(ventano) {
        $.get(API_URL, { action: 'detalle', ventano: ventano }, function (respuesta) {
            const $tbody = $('#tabla-detalle tbody');
            $tbody.empty();

            if (respuesta.success && respuesta.data.length > 0) {
                respuesta.data.forEach(function (d) {
                    $tbody.append(`
                        <tr>
                            <td>${escaparHtml(d.CODBARRA)}</td>
                            <td>${escaparHtml(d.MODELODES)}</td>
                            <td>${escaparHtml(d.COLOR)}</td>
                            <td>${d.TALLA}</td>
                            <td class="text-end">${formatoMoneda(d.PRECIOVE)}</td>
                        </tr>
                    `);
                });
            } else {
                $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">Sin artículos.</td></tr>');
            }
        });
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
