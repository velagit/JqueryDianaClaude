$(function () {
    const API_URL = 'api/consulta_existencias.php';

    let paginaActual = 1;

    // -----------------------------------------------------------------
    // Carga inicial: catálogo de marcas
    // -----------------------------------------------------------------
    cargarMarcas();

    // -----------------------------------------------------------------
    // Cambiar de marca recarga corridas y limpia filtros
    // -----------------------------------------------------------------
    $('#select-marca').on('change', function () {
        const marcano = $(this).val();
        $('#input-modelo').val('');
        $('#select-corrida').html('<option value="">Todas</option>');
        paginaActual = 1;

        if (marcano) {
            $('#fila-filtros-extra').show();
            cargarCorridas(marcano);
            consultar();
        } else {
            $('#fila-filtros-extra').hide();
            $('#contenedor-resultados').hide();
        }
    });

    // -----------------------------------------------------------------
    // Botón "Filtrar"
    // -----------------------------------------------------------------
    $('#form-consulta').on('submit', function (e) {
        e.preventDefault();
        paginaActual = 1;
        consultar();
    });

    // -----------------------------------------------------------------
    // Paginación
    // -----------------------------------------------------------------
    $('#paginacion-consulta').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const pagina = $(this).data('pagina');
        if (pagina) {
            paginaActual = pagina;
            consultar();
        }
    });

    /* ================================================================ */
    /*  Funciones principales                                            */
    /* ================================================================ */

    function cargarMarcas() {
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'marcas' },
            dataType: 'json',
        }).done(function (respuesta) {
            if (respuesta.success) {
                const $select = $('#select-marca');
                respuesta.data.forEach(function (marca) {
                    $select.append(`<option value="${marca.MARCANO}">${escaparHtml(marca.MARCADES)}</option>`);
                });
            }
        });
    }

    function cargarCorridas(marcano) {
        $.ajax({
            url: API_URL,
            method: 'GET',
            data: { action: 'corridas', marcano: marcano },
            dataType: 'json',
        }).done(function (respuesta) {
            if (respuesta.success) {
                const $select = $('#select-corrida');
                $select.empty().append('<option value="">Todas</option>');
                respuesta.data.forEach(function (corrida) {
                    const etiqueta = `${corrida.TALLAINI} - ${corrida.TALLAFIN} (${corrida.CLASIFIDES ?? 'Sin clasifica'})`;
                    $select.append(`<option value="${corrida.CORRIDANO}">${escaparHtml(etiqueta)}</option>`);
                });
            }
        });
    }

    function consultar() {
        const marcano = $('#select-marca').val();
        if (!marcano) {
            $('#contenedor-resultados').hide();
            return;
        }

        mostrarSpinner(true);

        $.ajax({
            url: API_URL,
            method: 'GET',
            data: {
                action: 'consulta',
                marcano: marcano,
                modelo: $('#input-modelo').val(),
                corrida: $('#select-corrida').val(),
                page: paginaActual,
            },
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (!respuesta.success) {
                    Swal.fire('Error', respuesta.message || 'No se pudo realizar la consulta.', 'error');
                    return;
                }

                const data = respuesta.data;

                if (data.sinCorridas) {
                    Swal.fire('Sin corridas', 'La marca seleccionada no tiene corridas registradas.', 'info');
                    $('#contenedor-resultados').hide();
                    return;
                }

                if (data.noModelos) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin modelos',
                        text: 'No hay modelos para esa talla',
                    });
                }

                renderizarResultados(data);
            })
            .fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                Swal.fire('Error', respuesta ? respuesta.message : 'Ocurrió un error de conexión.', 'error');
            })
            .always(function () {
                mostrarSpinner(false);
            });
    }

    function renderizarResultados(data) {
        const $thead = $('#tabla-resultados thead tr');
        const $tbody = $('#tabla-resultados tbody');

        $thead.find('.col-talla').remove();
        data.tallas.forEach(function (talla) {
            $thead.append(`<th class="col-talla">${talla}</th>`);
        });

        $tbody.empty();

        if (!data.existencias || data.existencias.length === 0) {
            $tbody.append(`<tr><td colspan="${4 + data.tallas.length}" class="text-center text-muted py-3">No hay datos disponibles.</td></tr>`);
        } else {
            data.existencias.forEach(function (fila) {
                let celdasTalla = '';
                data.tallas.forEach(function (talla) {
                    const valor = fila.tallas[talla] ?? 0;
                    celdasTalla += `<td>${valor}</td>`;
                });

                $tbody.append(`
                    <tr>
                        <td>${escaparHtml(fila.MODELODES)}</td>
                        <td>${escaparHtml(fila.MATERIAL)}</td>
                        <td>${escaparHtml(fila.COLOR)}</td>
                        <td>${escaparHtml(fila.SUELA)}</td>
                        ${celdasTalla}
                    </tr>
                `);
            });
        }

        renderizarPaginacion(data.paginaActual, data.totalPaginas);
        $('#contenedor-resultados').show();
    }

    function renderizarPaginacion(paginaActualResp, totalPaginas) {
        const $ul = $('#paginacion-consulta');
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
