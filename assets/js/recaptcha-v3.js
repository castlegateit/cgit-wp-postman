document.addEventListener('DOMContentLoaded', function () {
    if (typeof grecaptcha === 'undefined' || typeof postman_recaptcha_api_v3 === 'undefined') {
        return;
    }

    for (let siteKey in postman_recaptcha_api_v3.forms) {
        for (let formId of postman_recaptcha_api_v3.forms[siteKey]) {
            let input = document.querySelector('input[name="postman_form_id"][value="' + formId + '"]');
            let form = input.closest('form');

            // Before the form is submitted by the user, get a token from the
            // ReCaptcha API. Then submit the token with the rest of the form
            // data so it can be validated on the server.
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, {action: 'submit'}).then(function (token) {
                        let tokenInput = document.createElement('input');

                        tokenInput.setAttribute('type', 'hidden');
                        tokenInput.setAttribute('name', 'g-recaptcha-response');
                        tokenInput.setAttribute('value', token);

                        form.appendChild(tokenInput);
                        form.submit();
                    });
                });
            });
        }
    }
});
