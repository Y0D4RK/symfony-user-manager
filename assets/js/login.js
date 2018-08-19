// Login form ajax
body.on('submit', '#ajax-form-login', function (e) {

    const LOGIN_FORM = $('#ajax-form-login');

    // Prevents submit button default behaviour
    e.preventDefault();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize()
    })
    // Triggered if response status == 200 (form is valid and data has been processed successfully)
        .done(function (response) {
            // Redirects to url contained in the JSON response
            window.location.href = response.url;
        })
        // Triggered if response status == 400 (form has errors)
        .fail(function (response) {
            const LOGIN_ERROR_ALERT = $('#login-error-alert');
            const PASSWORD_FIELD = LOGIN_FORM.find('#password');

            PASSWORD_FIELD.val('');
            PASSWORD_FIELD.removeAttr('required');
            LOGIN_ERROR_ALERT.removeClass('d-none');
            LOGIN_ERROR_ALERT.html(response.responseJSON.errorMessage);
        });
});
