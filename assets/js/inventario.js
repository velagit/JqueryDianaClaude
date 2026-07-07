$(function () {
    const API_URL = 'api/inventario.php';

    // -----------------------------------------------------------------
    // Cargar catálogo de marcas al iniciar
    // -----------------------------------------------------------------
    $.get(API_URL, { action: 'marcas' }, function (marcas) {
        const $select = $('#marca');
        marcas.forEach(function (marca) {
            $select.append(`<option value="${marca.MARCANO}">${escaparHtml(marca.MARCADES)}</option>`);
        });
    });

    // -----------------------------------------------------------------
    // Cambiar de marca recarga el combo de corridas
    // -----------------------------------------------------------------
    $('#marca').change(function () {
        let marcano = $(this).val();
        $('#corrida').empty().append('<option value="">Todas</option>');
        $('#tabla-resultados').hide();

        if (!marcano) return;

        $.get(API_URL, { action: 'corridas', marcano: marcano }, function (data) {
            if (data.length === 0) {
                Swal.fire('Sin corridas', 'Esta marca no tiene corridas registradas.', 'info');
            } else {
                data.forEach(function (c) {
                    const clasifDes = c.clasifica ? c.clasifica.CLASIFIDES : '';
                    $('#corrida').append(`<option value="${c.CORRIDANO}">${c.TALLAINI} - ${c.TALLAFIN} (${escaparHtml(clasifDes)})</option>`);
                });
            }
        });
    });

    // -----------------------------------------------------------------
    // Botón "Consultar"
    // -----------------------------------------------------------------
    $('#consultar').click(function () {
        let marcano = $('#marca').val();
        let modelodes = $('#modelodes').val();
        let corridano = $('#corrida').val();

        if (!marcano) {
            Swal.fire('Error', 'Debe seleccionar una marca.', 'error');
            return;
        }

        $.get(API_URL, { action: 'consulta', marcano, modelodes, corridano }, function (res) {
            let html = '<table class="table table-bordered table-striped table-hover"><thead class="table-primary"><tr><th>Modelo</th><th>Material</th><th>Color</th><th>Suela</th>';
            res.tallas.forEach(function (t) {
                html += `<th>${t}</th>`;
            });
            html += '</tr></thead><tbody>';

            if (res.datos.length === 0) {
                html += `<tr><td colspan="${4 + res.tallas.length}" class="text-center text-muted py-3">No hay datos disponibles.</td></tr>`;
            } else {
                res.datos.forEach(function (row) {
                    html += '<tr>';
                    html += `<td>${escaparHtml(row.MODELODES)}</td><td>${escaparHtml(row.MATERIAL)}</td><td>${escaparHtml(row.COLOR)}</td><td>${escaparHtml(row.SUELA)}</td>`;
                    res.tallas.forEach(function (t) {
                        html += `<td>${row[t]}</td>`;
                    });
                    html += '</tr>';
                });
            }

            html += '</tbody></table>';
            $('#tabla-resultados .card-body').html(html);
            $('#tabla-resultados').show();
        }).fail(function (err) {
            $('#tabla-resultados').hide();
            Swal.fire('Sin resultados', (err.responseJSON && err.responseJSON.error) || 'No se encontraron datos.', 'info');
        });
    });

    function escaparHtml(texto) {
        if (texto === null || texto === undefined) return '';
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
