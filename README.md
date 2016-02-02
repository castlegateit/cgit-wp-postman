# Castlegate IT WP Postman #

Postman is a flexible contact form plugin for WordPress. It provides a framework for accessing, validating, and sending contact form data. The HTML output is entirely up to you; if you would like a simple template system, check out the [Postcard](http://github.com/castlegateit/cgit-wp-postcard) plugin.

## Postman ##

The `Cgit\Postman` class is used to create sets of form fields.

### `Cgit\Postman->method` ###

The default form method is `post`. You can set this to `get` to watch for `GET` requests instead.

### `Cgit\Postman->errorMessage` ###

The default error message for all form fields. This can be overridden on a per-field basis.

### `Cgit\Postman->errorTemplate` ###

If it is set and if it includes the string `%s`, this will be used to format all error messages using `sprintf()`.

### `Cgit\Postman->mailTo`,`Cgit\Postman->mailFrom`, `Cgit\Postman->mailSubject`, and `Cgit\Postman->mailHeaders` ###

The to and from email addresses and the subject used in the email. By default, the form will send to the WordPress admin email address and the subject line will be the name of the site, followed by the words "Website Enquiry". Email headers are entered as an associative array, where the keys are header names.

### `Cgit\Postman->field($name, $options = [])` ###

Add a field called `$name` to the form. Various options are available:

    $options = [
        'label' => 'Example', // used in the email message
        'required' => true,
        'validate' => [
            'type' => 'email', // email, number, tel, url
            'maxlength' => 20,
            'minlength' => 4,
            'max' => 100, // maximum value for numeric fields
            'min' => 10, // minimum value for numeric fields
            'pattern' => '/foo/', // a regular expression match
            'match' => 'bar', // exactly matches another field called bar
            'function' => 'foo', // any named function
        ],
        'error' => 'Please enter a valid email address',
    ];

Custom validation functions take the form `function($value, $data)`, where `$value` is the submitted value and `$data` is the full form data. This allows for comparisons of multiple form field values.

### `Cgit\Postman->detect($conditions)` ###

Only watch for data that contains particular fields. If this is a string, it looks for a field with that name. If it is an array, it looks for all the values as field names. If it is an associative array, it looks for the key-value pairs as field-value pairs.

Note that these fields and/or values need to appear in the request data (post or get), but do not have to be registered with `Cgit\Postman->field()`.

### `Cgit\Postman->value($name)` ###

Return the value of field `$name`.

### `Cgit\Postman->error($name)` ###

Return the error message for field `$name`, wrapped in the template set with `Cgit\Postman->errorTemplate`.

### `Cgit\Postman->submit()` ###

Try to submit the current form. Returns `true` if the form sends correctly or `false` if it does not.

## Example ##

    $form = new Cgit\Postman();

    $form->method = 'post';
    $form->errorMessage = 'That doesn\'t work';
    $form->errorTemplate = '<span>%s</span>';
    $form->mailHeaders = [
        'Reply-To': 'example@example.com'
    ];

    $form->field('username');
    $form->field('email', [
        'label' => 'Email',
        'required' => true,
        'validate' => [
            'type' => 'email',
        ],
        'error' => 'Please enter a valid email address'
    ]);

    if ($form->submit()) {
        ?>
        <p>Your message has been sent</p>
        <?php
    } else {
        ?>
        <form method="post">
            <input type="text" name="username" value="<?= $form->value('username') ?>" />
            <input type="email" name="email" value="<?= $form->value('email') ?>" />
            <?= $form->error('email') ?>
            <button>Send Message</button>
        </form>
        <?php
    }

## Logs ##

If the `CGIT_CONTACT_FORM_LOG` constant is defined, the plugin will use this directory to save contact form logs. If it is not, you will see a warning message on the WordPress dashboard.

## Filters ##

*   `cgit_postman_data` associative array of submitted form data.
*   `cgit_postman_mail_to` mail class `to` address.
*   `cgit_postman_mail_subject` mail class subject.
*   `cgit_postman_mail_content` mail class content.
*   `cgit_postman_mail_from` mail class `from` address.
*   `cgit_postman_mail_cc` mail class `cc` address.
*   `cgit_postman_mail_bcc` mail class `bcc` address.
*   `cgit_postman_mail_headers` receives an associative array of headers.

For example, the `Reply-To` header could be edited as follows:

    add_filter('cgit_postman_mail_headers', function($headers) {
        $headers['Reply-To'] = 'example@example.com';
        return $headers;
    });

## Debugging ##

If `CGIT_POSTMAN_MAIL_DUMP` is defined, the mail class will return the contents of the email message instead of sending it. This might save you from accidentally emailing a client or filling up your inbox.
