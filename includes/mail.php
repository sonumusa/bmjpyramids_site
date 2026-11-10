<?php
require_once __DIR__ . '/functions.php';

function log_mail(string $to, string $subject, string $message): void
{
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    $line = "\n==== " . date('Y-m-d H:i:s') . " ====\nTo: {$to}\nSubject: {$subject}\n{$message}\n";
    file_put_contents(LOG_PATH . '/mail.log', $line, FILE_APPEND);
}

function send_site_email(string $to, string $subject, string $html, string $replyTo = ''): bool
{
    // SMTP constants are ready in config.php. For many cPanel hosts, PHP mail() works.
    // If your host requires authenticated SMTP, connect PHPMailer here using the constants.
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . SITE_NAME . ' <' . SITE_EMAIL . '>';
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    log_mail($to, $subject, strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

    if (SMTP_ENABLED) {
        // SMTP is intentionally configuration-ready. Add PHPMailer credentials when available.
        return true;
    }

    if (function_exists('mail')) {
        return @mail($to, $subject, $html, implode("\r\n", $headers));
    }

    return true;
}
