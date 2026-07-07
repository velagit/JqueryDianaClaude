$(function () {
    $('#form-login').on('submit', function (e) {
        e.preventDefault();

        $('#form-login .is-invalid').removeClass('is-invalid');
        $('#form-login .invalid-feedback').text('');

        const datos = {
            usuario: $('#usuario').val(),
            password: $('#password').val(),
        };

        $('#btn-login').prop('disabled', true);

        $.ajax({
            url: 'api/auth.php?action=login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
        })
            .done(function (respuesta) {
                if (respuesta.success) {
                    window.location.href = 'index.php';
                } else {
                    Swal.fire('Error', respuesta.message || 'No se pudo iniciar sesión.', 'error');
                }
            })
            .fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                if (respuesta && respuesta.message) {
                    Swal.fire('Error', respuesta.message, 'error');
                } else {
                    Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
                }
            })
            .always(function () {
                $('#btn-login').prop('disabled', false);
            });
    });
});
