# Contact form email

Contact submissions are queued separately for every admin and super admin whose
**Profile settings → Receive contact form emails** preference is enabled. This is
enabled by default for existing and new admins. Ordinary users never receive these
emails. The recipient's role and preference are checked again before sending.

## Deployment

1. Run `composer install` and `php artisan migrate`.
2. Create a Resend API key and verify your sending domain in Resend.
3. Set the following environment variables (keep the API key out of source control):

   ```dotenv
   MAIL_MAILER=resend
   RESEND_API_KEY=re_your_key
   MAIL_FROM_ADDRESS=gallery@your-verified-domain.com
   MAIL_FROM_NAME="Gallery"
   QUEUE_CONNECTION=database
   ```

4. Refresh cached configuration with `php artisan config:cache`.
5. Run a supervised `php artisan queue:work` process. Restart existing workers with
   `php artisan queue:restart` after deployment.

The configured sender is used for domain authentication; Reply-To is the visitor's
email address. Each recipient gets a separate email. User content is escaped in
the email template. Requests are validated and limited to five per minute per IP.
If no admins have email enabled, the form shows an error instead of accepting the
message. Success means queued for delivery, not confirmed inbox delivery. Failed
deliveries retry three times with backoff; inspect `php artisan queue:failed` and
Resend logs for delivery failures.

For local development without sending real email, use `MAIL_MAILER=log` or an SMTP
test inbox. No Resend key is included in the repository.

## Windows certificate errors

`cURL error 60: unable to get local issuer certificate` means PHP cannot verify
the HTTPS certificate chain. Configure PHP with a current CA certificate bundle
from [curl's CA Extract page](https://curl.se/docs/caextract.html). For example,
save the bundle at `C:/php84/extras/ssl/cacert.pem`, then set these entries in the
loaded `php.ini` (use `php --ini` to locate it):

```ini
curl.cainfo="C:/php84/extras/ssl/cacert.pem"
openssl.cafile="C:/php84/extras/ssl/cacert.pem"
```

Restart the PHP server and queue workers. If the web server uses a different PHP
installation, configure its loaded INI file too. Keep TLS verification enabled.
If an organization or antivirus intercepts HTTPS, its trusted root certificate
may also need to be included in the bundle.

References: [Laravel Resend driver](https://laravel.com/docs/13.x/mail#resend-driver)
and [Resend setup](https://resend.com/docs/send-with-laravel).
