# Pattern: Email Notifications (PHPMailer)

## Install PHPMailer
```bash
composer require phpmailer/phpmailer
```

---

## includes/mailer.php — Base send function

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = '', array $attachments = []): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(ADMIN_EMAIL, APP_NAME);

        foreach ($attachments as $att) {
            $mail->addAttachment($att['path'], $att['name']);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Wrap content in Green Cash email template
 */
function emailTemplate(string $content): string {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #2E7D32; color: #fff; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .body { padding: 30px; color: #333; line-height: 1.6; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .btn { display: inline-block; background: #2E7D32; color: #fff; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: bold; margin: 10px 0; }
            .highlight { background: #e8f5e9; border-left: 4px solid #2E7D32; padding: 15px; margin: 15px 0; border-radius: 4px; }
            .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-weight: bold; }
            .status-approved { background: #c8e6c9; color: #1b5e20; }
            .status-rejected { background: #ffcdd2; color: #b71c1c; }
            .status-disbursed { background: #bbdefb; color: #0d47a1; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Green Cash</h1>
                <p style="margin:5px 0 0 0; opacity:0.85;">Salary Advance Loans</p>
            </div>
            <div class="body">' . $content . '</div>
            <div class="footer">
                <p>Green Cash | <a href="' . APP_URL . '">' . APP_URL . '</a></p>
                <p>This email was sent to you regarding your loan application. Do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';
}
```

---

## includes/email_templates.php — All notification functions

```php
<?php
require_once __DIR__ . '/mailer.php';

/**
 * 1. Application received confirmation
 */
function sendApplicationConfirmation(array $app): void {
    $amount = formatCurrency($app['loan_amount']);
    $html = emailTemplate("
        <p>Dear {$app['first_name']},</p>
        <p>Thank you for applying with <strong>Green Cash</strong>! We have received your salary advance application.</p>
        <div class='highlight'>
            <strong>Reference Number:</strong> {$app['reference_number']}<br>
            <strong>Amount Requested:</strong> {$amount}<br>
            <strong>Term:</strong> 1 Month
        </div>
        <p>Our team will review your application and contact you within <strong>24 hours</strong>.</p>
        <p>You can track your application status at any time:</p>
        <a href='" . APP_URL . "/track-application.php' class='btn'>Track My Application</a>
        <p>If you have any questions, please contact us at <a href='mailto:" . ADMIN_EMAIL . "'>" . ADMIN_EMAIL . "</a></p>
        <p>Kind regards,<br><strong>The Green Cash Team</strong></p>
    ");
    sendMail($app['email'], $app['first_name'], 'Application Received — ' . $app['reference_number'], $html);
}

/**
 * 2. Status change notification
 */
function sendStatusChangeEmail(array $app, string $newStatus): void {
    $statusMessages = [
        'approved'     => ['label' => 'Approved', 'class' => 'status-approved',
                           'msg'   => 'Congratulations! Your salary advance application has been <strong>approved</strong>. Our team will contact you shortly to complete the disbursement process.'],
        'rejected'     => ['label' => 'Rejected', 'class' => 'status-rejected',
                           'msg'   => 'Unfortunately, after careful review, we are unable to approve your salary advance application at this time. You are welcome to reapply after 30 days.'],
        'under_review' => ['label' => 'Under Review', 'class' => '',
                           'msg'   => 'Your application is currently being reviewed by our team. We will contact you with a decision shortly.'],
        'disbursed'    => ['label' => 'Disbursed', 'class' => 'status-disbursed',
                           'msg'   => 'Great news! Your salary advance of <strong>' . formatCurrency($app['loan_amount']) . '</strong> has been disbursed to your account.'],
    ];

    if (!isset($statusMessages[$newStatus])) return;

    $s = $statusMessages[$newStatus];
    $html = emailTemplate("
        <p>Dear {$app['first_name']},</p>
        <p>There has been an update to your loan application.</p>
        <div class='highlight'>
            <strong>Reference:</strong> {$app['reference_number']}<br>
            <strong>Status:</strong> <span class='status-badge {$s['class']}'>{$s['label']}</span>
        </div>
        <p>{$s['msg']}</p>
        <a href='" . APP_URL . "/track-application.php' class='btn'>View Application Status</a>
        <p>Kind regards,<br><strong>The Green Cash Team</strong></p>
    ");

    $subject = "Application Update ({$s['label']}) — {$app['reference_number']}";
    sendMail($app['email'], $app['first_name'], $subject, $html);
}

/**
 * 3. Admin OTP (2FA)
 */
function sendAdminOTP(array $user, string $otp): void {
    $html = emailTemplate("
        <p>Dear {$user['first_name']},</p>
        <p>Your Green Cash admin login verification code is:</p>
        <div style='text-align:center; margin: 30px 0;'>
            <span style='font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #2E7D32;'>{$otp}</span>
        </div>
        <p>This code expires in <strong>10 minutes</strong>. Do not share it with anyone.</p>
        <p>If you did not attempt to log in, please secure your account immediately.</p>
    ");
    sendMail($user['email'], $user['first_name'], 'Green Cash Admin Login Code: ' . $otp, $html);
}

/**
 * 4. Broker approved email (tells broker their credentials)
 */
function sendBrokerApprovalEmail(array $broker): void {
    $html = emailTemplate("
        <p>Dear {$broker['first_name']},</p>
        <p>Great news! Your Green Cash broker application has been <strong>approved</strong>.</p>
        <p>Your broker account is being prepared. Once activated, you can log in using:</p>
        <div class='highlight'>
            <strong>Login URL:</strong> <a href='" . APP_URL . "/broker-portal-login.php'>" . APP_URL . "/broker-portal-login.php</a><br>
            <strong>Username (Email):</strong> {$broker['email']}<br>
            <strong>Password:</strong> Your South African ID Number
        </div>
        <p>You will receive another email once your account has been fully activated.</p>
        <p>Your broker code is: <strong>{$broker['broker_code']}</strong></p>
        <p>Kind regards,<br><strong>The Green Cash Team</strong></p>
    ");
    sendMail($broker['email'], $broker['first_name'], 'Broker Application Approved — Green Cash', $html);
}

/**
 * 5. Broker activation email (account is now live)
 */
function sendBrokerActivationEmail(array $broker): void {
    $loginUrl = APP_URL . '/broker-portal-login.php';
    $html = emailTemplate("
        <p>Dear {$broker['first_name']},</p>
        <p>Your Green Cash broker account is now <strong>live and ready to use</strong>!</p>
        <div class='highlight'>
            <strong>Login URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a><br>
            <strong>Email:</strong> {$broker['email']}<br>
            <strong>Password:</strong> Your South African ID Number<br>
            <strong>Broker Code:</strong> {$broker['broker_code']}
        </div>
        <a href='{$loginUrl}' class='btn'>Login to Broker Portal</a>
        <p>Start submitting salary advance applications for your clients today.</p>
        <p>Your commission rate is <strong>{$broker['commission_rate']}%</strong> per approved and disbursed application.</p>
        <p>Kind regards,<br><strong>The Green Cash Team</strong></p>
    ");
    sendMail($broker['email'], $broker['first_name'], 'Your Green Cash Broker Account is Active!', $html);
}

/**
 * 6. Password reset (admin)
 */
function sendPasswordResetEmail(array $user, string $token): void {
    $resetUrl = APP_URL . '/reset-password.php?token=' . $token;
    $html = emailTemplate("
        <p>Dear {$user['first_name']},</p>
        <p>We received a request to reset your Green Cash admin password.</p>
        <a href='{$resetUrl}' class='btn'>Reset My Password</a>
        <p>This link expires in <strong>1 hour</strong>. If you did not request this, please ignore this email.</p>
    ");
    sendMail($user['email'], $user['first_name'], 'Reset Your Green Cash Password', $html);
}
```
