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

You can use the `mailerSettings` property directly to change the email settings:

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

However, the `mailer` and `header` methods are preferred for editing these settings.


### Methods ###


#### Mailer configuration

Add or edit mailer settings (i.e. `$to` and `$subject` parameters passed to `wp_mail` function):

~~~ php
$postman->mailer('to', 'example@example.com');
$postman->mailer('subject', 'Example message');
~~~

Note that you can prevent mail from being sent by setting `to` to `null`.


#### Headers

Add or edit email headers (i.e. `$headers` parameter passed to `wp_mail` function):

~~~ php
$postman->header('from', 'example@example.com');
$postman->header('x-foo', 'example@example.com');
~~~


#### Fields

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

Custom validation is available for 'url', 'tel', 'number'. All other fields are treated as text inputs, though custom validation functions may be added. These take the form `function($value, $data)`, where `$value` is the submitted value and `$data` is the full form data. This allows for comparisons of multiple form field values.

You can specify separate error messages for different validation errors using an array instead of a string for the error parameter:

~~~ php
$postman->field('example_name', [
    'validate' => [
        'type' => 'email',
        'maxlength' => 10,
    ],
    'error' => [
        'required' => 'This is a required field',
        'type' => 'Please enter a valid email address',
        'maxlength' => 'Must have 10 characters or fewer',
    ],
]);
~~~

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


#### Values and error messages

Return data for the field `$name`:

~~~ php
$postman->value($name); // value
$postman->error($name); // error message, within error template
~~~


## Example ##

~~~ php
$form = new \Cgit\Postman('contact');

$form->method = 'post';
$form->errorMessage = 'That doesn\'t work';
$form->errorTemplate = '<span class="error">%s</span>';

// Set mail recipient and subject
$form->mailer('to', 'example@example.com');
$form->mailer('subject', 'Test message');

// Set mail headers
$form->header('Reply-To', 'example@example.com');

// Define form fields
$form->field('fullname', [
    'label' => 'Name',
    'required' => true,
    'error' => 'Please enter your name',
]);

$form->field('email', [
    'label' => 'Email',
    'required' => true,
    'error' => 'Please enter a valid email address',
    'validate' => [
        'type' => 'email',
    ],
]);

// Enable ReCaptcha
$form->enableReCaptcha($site_key, $secret_key);

// Enable Akismet
$form->enableAkismet('contact-form', [
    'comment_author' => 'fullname',
    'comment_author_email' => 'email',
]);

// Check for form submission
$form->submit();

// Show form or success message
if ($form->sent()) {
    echo 'Your message has been sent. Thank you.';
} else {
    // Mailer has failed to send (e.g. email service problem)?
    if ($form->failed()) {
        echo 'Failed to send message.';
    }

    // Form has been submitted with errors?
    if ($form->errors()) {
        echo 'Your message contains errors.';

        // Submission has not passed Akismet's spam check?
        echo $form->error('akismet');
    }

    ?>

    <form action="" method="post">
        <input type="hidden" name="postman_form_id" value="contact">

        <label for="fullname">Name</label>
        <input type="text" name="fullname" id="fullname"
            value="<?= $form->value('fullname') ?>" required>
        <?= $form->error('fullname') ?>

        <label for="email">Email</label>
        <input type="email" name="email" id="email"
            value="<?= $form->value('email') ?>" required>
        <?= $form->error('email') ?>

        <?= $form->renderReCaptcha() ?>
        <?= $form->error('recaptcha') ?>

        <button type="submit">Send</button>
    </form>

    <?php
}
~~~


## Akismet

Postman can check form submissions for spam using the [Akismet](https://akismet.com/) service. For this to work, you must first install and activate the [WordPress Akismet plugin](https://wordpress.org/plugins/akismet/) with a valid license key.

When Akismet is active and registered, you can enable Akismet support using the `enableAkismet` method on the `Postman` form instance:

~~~ php
$form = new \Cgit\Postman('contact');

$form->fields([
    'fullname' => [],
    'email' => [],
    'subject' => [],
    'message' => [],
]);

$form->enableAkismet('contact-form', [
    'comment_author' => 'fullname',
    'comment_author_email' => 'email',
    'comment_content' => [
        'subject',
        'message',
    ],
]);
~~~

The first parameter is the type of form submission and must one of the following [Akismet comment types](https://akismet.com/development/api/#comment-check):

*   `blog-post`
*   `comment`
*   `contact-form`
*   `forum-post`
*   `message`
*   `reply`
*   `signup`

The second parameter is an array that maps the Postman fields to the relevant [Akismet fields](https://akismet.com/development/api/#comment-check). You can combine multiple Postman fields into one Akismet field by providing an array of Postman field names instead of a single field name. The following Akismet fields are supported:

*   `comment_author`
*   `comment_author_email`
*   `comment_author_url`
*   `comment_content`

Form submissions identified as spam will have an `akismet` validation error key:

~~~ php
if ($form->error('akismet')) {
    // submission is spam
}
~~~

You can [force a submission to be identified as spam](https://akismet.com/development/api/#comment-check), and so test the Akismet integration, by submitting `viagra-test-123` as the `comment_author` or by submitting `akismet-guaranteed-spam@example.com` as the `comment_author_email`.

See the [Akismet developer documentation](https://akismet.com/development/api/) for more information.


## ReCaptcha ##

Postman can check for bot submissions using [ReCaptcha v2](https://developers.google.com/recaptcha). To enable this, use the `enableReCaptcha()` method:

~~~ php
$form->enableReCaptcha();
~~~

You can then output the ReCaptcha field and error message where you want them in your template:

~~~ php
echo $form->renderReCaptcha();
echo $form->error('recaptcha');
~~~

Note that ReCaptcha will **only** work if the ReCaptcha site and secret API keys have been set. You can set these when you enable ReCaptcha:

~~~ php
$form->enableReCaptcha('my-site-key', 'my-secret-key');
~~~

or using methods:

~~~ php
$form->setReCaptchaSiteKey('my-site-key');
$form->setReCaptchaSecretKey('my-secret-key');
~~~

or using constants:

~~~ php
define('RECAPTCHA_SITE_KEY', 'my-site-key');
define('RECAPTCHA_SECRET_KEY', 'my-secret-key');
~~~


## Logs ##

The plugin will log all contact form submissions to the database.


### Log file download

Postman will add an entry in the __Tools__ menu in WordPress that allows you to download the contact form logs in CSV format. You can change how this works using filters:

*   `cgit_postman_log_capability` edits the minimum user role that is required for log file downloads. The default is `edit_pages`, which means that administrators and editors can download log files.

*   `cgit_postman_log_groups` edits the way the logs are grouped. The default value is `['form_id']`, which means that logs are grouped by form ID.

*   `cgit_postman_log_aliases` provides alternative, human-readable names for form IDs. If you make it return an associative array with keys corresponding to form IDs, the values will be displayed in WordPress instead.


### Deleting logs

As of version **2.8.2** Postman will automatically truncate log files older than 180 days (an averaged 6 months). You can override this by looking at the contents below.
Do not activate a version of Postman later than **2.8.1** without defining the appropriate constant if you do not want this to happen!

You can also use the __Tools__ menu to delete old log files. If there are currently log entries in the database, you will be able to delete logs by number or date or to delete all logs. This process can be automated by setting one or more constants in `wp-config.php`:

~~~ php
define('CGIT_POSTMAN_LOG_LIMIT', 100); // keep the most recent 100 logs
define('CGIT_POSTMAN_LOG_LIMIT_DAYS', 30); // keep the most recent 30 days logs
define('CGIT_POSTMAN_LOG_DELETE_ALL', true); // delete all logs
~~~

If these constants are set, the deletion process will take place when a user accesses the Wordpress admin panel.


## Debugging ##

If `CGIT_POSTMAN_MAIL_DUMP` is defined, the mail class will return the contents of the email message instead of sending it. This might save you from accidentally emailing a client or filling up your inbox.


## Filters ##

*   `cgit_postman_data_pre_validate` associative array of submitted form data, before validation.*
*   `cgit_postman_data_post_validate` associative array of submitted form data, after validation but before being sent.*
*   `cgit_postman_data` associative array of submitted form data, validated and just prior to sending.*
*   `cgit_postman_fields` associative array of all field data, just prior to sending.*
*   `cgit_postman_value_{$name}` value of field `$name`.*
*   `cgit_postman_error_{$name}` error value of field `$name`.*
*   `cgit_postman_message_content` assembled email content.*
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

The filters marked with an asterisk accept the form ID as a second parameter so you can distinguish between different forms when filtering data. For example:

~~~ php
add_filter('cgit_postman_data', function ($data, $form_id) {
    if ($form_id != 'contact_form') {
        return $data;
    }

    // do something to data here

    return $data;
});
~~~


## Changes since version 2.0 ##

*   Forms now require a unique identifier, set in the constructor. This makes submission detection automatic and allows accurate logging.

*   Mailer settings have been moved from individual properties, such as `mailTo` to a single property called `mailerSettings` that consists of an array of mailer settings.

*   The `detect()` method has been removed. Form identification is now automatic, based on the unique form ID set in the constructor.

*   The new `exclude` field option prevents fields appearing in email messages or log downloads.

*   You can now download log files from the WordPress admin panel.

*   You can now change or replace the mailer and validator classes.


## Changes since version 3.0

*   ReCaptcha support has been completely rewritten and is no longer compatible with v2.x. Please review the documentation and adapt your code accordingly.

*   You can no longer replace the mailer, log spooler, and validation classes.


## License

Copyright (c) 2019 Castlegate IT. All rights reserved.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
