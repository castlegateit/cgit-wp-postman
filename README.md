# Castlegate IT WP Postman #

Postman is a flexible contact form plugin for WordPress. It provides a framework for accessing, validating, and sending contact form data. The HTML output is entirely up to you; if you would like a simple template system, check out the [Postcard](http://github.com/castlegateit/cgit-wp-postcard) plugin.

## Postman ##

The `Cgit\Postman` class is used to create sets of form fields. Each instance represents a separate form. Since version 2.0, the constructor requires a single argument, which is used as a unique identifier for that form:

~~~ php
$postman = new \Cgit\Postman('foo');
~~~

### Properties ###

The default form method is `post`. You can set this to `get` to watch for `GET` requests instead:

~~~ php
$postman->method = 'get';
~~~

The default error message for each field is "Invalid input". You can set a different error message (each field can also have its own separate error message):

~~~ php
$postman->errorMessage = 'Bad value';
~~~

By default, the error message is returned as a string. If you supply a template that includes the string `%s`, this will be used to format all error messages using `sprintf()`:

~~~ php
$postman->errorTemplate = '<span class="error">%s</span>';
~~~

You can use the `mailerSettings` property to change the email settings:

~~~ php
$postman->mailerSettings = [
    'to' => 'example@example.com',
    'from' => 'foo@bar.com',
    'subject' => 'Example message',
    'headers' => [
        'X-Foo' => 'Example header',
        'X-Bar' => 'Another example header',
    ],
];
~~~

### Methods ###

Add a field, with various options:

~~~ php
$postman->field('example_name', [
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
    'value' => 'The default field value',
    'exclude' => true, // exclude from email message body
]);
~~~

Custom validation functions take the form `function($value, $data)`, where `$value` is the submitted value and `$data` is the full form data. This allows for comparisons of multiple form field values.

The `fields()` method can be used as a shortcut to add multiple fields at once, by passing in an array containing a mapping of `'name' => [ 'options' ]`:

~~~ php
$postman->fields([
    'name' => [
        'label' => 'Name',
        'required' => true
    ],
    'phone' => [
        'label' => 'Phone',
        'required' => true,
        'type' => 'tel'
    ],
    'email' => [
        'label' => 'Email',
        'required' => true,
        'type' => 'email'
    ]
]);
~~~

Return data for the field `$name`:

~~~ php
$postman->value($name); // value
$postman->error($name); // error message, within error template
~~~

## Example ##

~~~ php
$form = new Cgit\Postman('contact');

$form->method = 'post';
$form->errorMessage = 'That doesn\'t work';
$form->errorTemplate = '<span>%s</span>';
$form->mailerSettings['headers'] = [
    'Reply-To' => 'example@example.com'
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
        <input type="hidden" name="postman_form_id" value="contact" />
        <input type="text" name="username" value="<?= $form->value('username') ?>" />
        <input type="email" name="email" value="<?= $form->value('email') ?>" />
        <?= $form->error('email') ?>
        <button>Send Message</button>
    </form>
    <?php
}
~~~

## Logs ##

The plugin will log all contact form submissions to the database.

## Filters ##

*   `cgit_postman_data_pre_validate` associative array of submitted form data, before validation.
*   `cgit_postman_data_post_validate` associative array of submitted form data, after validation but before being sent.
*   `cgit_postman_data` associative array of submitted form data, validated and just prior to sending.
*   `cgit_postman_fields` associative array of all field data, just prior to sending.
*   `cgit_postman_value_{$name}` value of field `$name`.
*   `cgit_postman_error_{$name}` error value of field `$name`.
*   `cgit_postman_mail_to` mail class `to` address.
*   `cgit_postman_mail_subject` mail class subject.
*   `cgit_postman_mail_content` mail class content.
*   `cgit_postman_mail_from` mail class `from` address.
*   `cgit_postman_mail_cc` mail class `cc` address.
*   `cgit_postman_mail_bcc` mail class `bcc` address.
*   `cgit_postman_mail_headers` receives an associative array of headers.

For example, the `Reply-To` header could be edited as follows:

~~~ php
add_filter('cgit_postman_mail_headers', function($headers) {
    $headers['Reply-To'] = 'example@example.com';
    return $headers;
});
~~~

## Log file download ##

Postman will add an entry in the __Tools__ menu in WordPress that allows you to download the contact form logs in CSV format. You can change how this works using filters:

*   `cgit_postman_log_capability` edits the minimum user role that is required for log file downloads. The default is `edit_pages`, which means that administrators and editors can download log files.

*   `cgit_postman_log_groups` edits the way the logs are grouped. The default value is `['post_id', 'form_id']`, which means that logs are grouped by both post and form ID. You can remove either of these items from the array with this filter to allow users to download logs from one form ID across all pages or from one page across all forms.

*   `cgit_postman_log_aliases` provides alternative, human-readable names for form IDs. If you make it return an associative array with keys corresponding to form IDs, the values will be displayed in WordPress instead.

## Custom mailer and validator ##

If you want to change how the mailer or the validator work or replace them entirely, you can use the `mailer` and `validator` properties to specify different classes to use. For example, to customize the mailer for the Postman instance `$postman`:

~~~ php
use Cgit\Postman\Norman as Mailer;

class Foo extends Mailer
{
    public function send()
    {
        // new send method
    }
}

$postman->mailer = 'Foo';
~~~

## ReCaptcha Support ##

Postman can optionally support recaptcha v2. If you enable this functionality you will need to ensure that RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY are defined for your environment.

To enable ReCaptcha Support, simply invoke the enableCaptcha method on your form object:

~~~ php
    $form->enableCaptcha();
~~~

Postman will asyncronously load the required API itself, but you will need to position your Captcha in the markup of your form by doing the following:

~~~ php

<div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>

~~~

## Debugging ##

If `CGIT_POSTMAN_MAIL_DUMP` is defined, the mail class will return the contents of the email message instead of sending it. This might save you from accidentally emailing a client or filling up your inbox.

## Changes since version 2.0 ##

*   Forms now require a unique identifier, set in the constructor. This makes submission detection automatic and allows accurate logging.

*   Mailer settings have been moved from individual properties, such as `mailTo` to a single property called `mailerSettings` that consists of an array of mailer settings.

*   The `detect()` method has been removed. Form identification is now automatic, based on the unique form ID set in the constructor.

*   The new `exclude` field option prevents fields appearing in email messages or log downloads.

*   You can now download log files from the WordPress admin panel.

*   You can now change or replace the mailer and validator classes.
