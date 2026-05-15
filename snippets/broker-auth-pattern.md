# Pattern: Broker Authentication (Email + SA ID Number as Password)

## How it works
- Brokers sign up with their email and SA ID number
- When admin activates the account, the system runs: `password_hash($id_number, PASSWORD_DEFAULT)`
- Broker logs in with their email + their own ID number — no separate password to remember

---

## broker-portal-login.php

```php
<?php
require_once 'includes/config.php';

if (isBroker()) redirect('/broker-portal/index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = sanitize($_POST['email'] ?? '');
    $idNumber = trim($_POST['id_number'] ?? '');
    $ip       = getClientIp();

    // IP rate limit: 5 attempts / 15 min
    if (checkRateLimit($pdo, $ip, 'ip_login', 5, 15)) {
        $error = 'Too many attempts from your location. Please try again in 15 minutes.';
    } else {
        // Find broker by email
        $stmt = $pdo->prepare(
            "SELECT * FROM credit_brokers WHERE email = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$email]);
        $broker = $stmt->fetch();

        if ($broker) {
            // Account-level lockout check
            if (checkRateLimit($pdo, $email, 'broker_login', 10, 30)) {
                $error = 'Account temporarily locked due to too many failed attempts. Try again in 30 minutes.';
            } elseif ($broker['password_hash'] && password_verify($idNumber, $broker['password_hash'])) {
                // SUCCESS
                resetRateLimit($pdo, $email, 'broker_login');
                resetRateLimit($pdo, $ip, 'ip_login');

                session_regenerate_id(true);
                $_SESSION['broker_id']    = $broker['id'];
                $_SESSION['broker_name']  = $broker['first_name'] . ' ' . $broker['last_name'];
                $_SESSION['broker_email'] = $broker['email'];
                $_SESSION['broker_code']  = $broker['broker_code'];

                // Update last login
                $pdo->prepare("UPDATE credit_brokers SET last_login = NOW() WHERE id = ?")
                    ->execute([$broker['id']]);

                logBrokerAction($pdo, $broker['id'], 'login', 'Successful login from ' . $ip);

                redirect('/broker-portal/index.php');
            } else {
                // Failed
                incrementRateLimit($pdo, $email, 'broker_login', 10, 30);
                incrementRateLimit($pdo, $ip, 'ip_login', 5, 15);
                logBrokerAction($pdo, $broker['id'], 'failed_login', 'Failed login attempt from ' . $ip);
                $error = 'Invalid email or ID number.';
            }
        } else {
            // Don't reveal whether email exists
            incrementRateLimit($pdo, $ip, 'ip_login', 5, 15);
            $error = 'Invalid email or ID number.';
        }
    }
}
?>

<!-- HTML form -->
<form method="POST">
    <?= csrfField() ?>
    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">SA ID Number</label>
        <input type="password" name="id_number" class="form-control" maxlength="13" required>
        <div class="form-text">Your South African ID number is your password.</div>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
</form>
```

---

## Admin: Activate Broker Account (admin/broker-details.php)

```php
// When admin clicks "Activate Account" button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_broker'])) {
    verifyCsrf();

    $brokerId = (int)$_POST['broker_id'];

    // Fetch broker
    $stmt = $pdo->prepare("SELECT * FROM credit_brokers WHERE id = ? AND status = 'approved' LIMIT 1");
    $stmt->execute([$brokerId]);
    $broker = $stmt->fetch();

    if ($broker) {
        // Hash the ID number as the password
        $passwordHash = password_hash($broker['id_number'], PASSWORD_DEFAULT);

        $pdo->prepare(
            "UPDATE credit_brokers SET status = 'active', password_hash = ? WHERE id = ?"
        )->execute([$passwordHash, $brokerId]);

        // Send activation email
        sendBrokerActivationEmail($broker);

        logAdminAction($pdo, $_SESSION['admin_id'], 'activate_broker', 'Activated broker ID: ' . $brokerId);
        setFlash('success', 'Broker account activated. Login credentials sent.');
    }

    redirect('/admin/broker-details.php?id=' . $brokerId);
}
```

---

## Admin: Approve Broker (admin/brokers.php)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_broker'])) {
    verifyCsrf();

    $brokerId = (int)$_POST['broker_id'];

    $pdo->prepare("UPDATE credit_brokers SET status = 'approved' WHERE id = ? AND status = 'pending'")
        ->execute([$brokerId]);

    $stmt = $pdo->prepare("SELECT * FROM credit_brokers WHERE id = ? LIMIT 1");
    $stmt->execute([$brokerId]);
    $broker = $stmt->fetch();

    if ($broker) {
        sendBrokerApprovalEmail($broker); // tells them: email=username, ID=password
        logAdminAction($pdo, $_SESSION['admin_id'], 'approve_broker', 'Approved broker ID: ' . $brokerId);
    }

    setFlash('success', 'Broker approved. Approval email sent.');
    redirect('/admin/brokers.php');
}
```

---

## broker-signup.php pattern

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Validate + sanitize fields
    $data = [
        'first_name'             => sanitize($_POST['first_name'] ?? ''),
        'last_name'              => sanitize($_POST['last_name'] ?? ''),
        'email'                  => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
        'phone'                  => sanitize($_POST['phone'] ?? ''),
        'id_number'              => sanitize($_POST['id_number'] ?? ''),
        'company_name'           => sanitize($_POST['company_name'] ?? ''),
        'company_registration'   => sanitize($_POST['company_registration'] ?? ''),
        'province'               => sanitize($_POST['province'] ?? ''),
        'city'                   => sanitize($_POST['city'] ?? ''),
        'experience_years'       => (int)($_POST['experience_years'] ?? 0),
        'why_join'               => sanitize($_POST['why_join'] ?? ''),
    ];

    // Check for existing email OR existing ID number
    $exists = $pdo->prepare("SELECT id FROM credit_brokers WHERE email = ? OR id_number = ? LIMIT 1");
    $exists->execute([$data['email'], $data['id_number']]);
    if ($exists->fetch()) {
        $error = 'An account with this email or ID number already exists.';
    } else {
        $brokerCode = generateBrokerCode();

        // Handle ID document upload (same MIME + extension check pattern)
        $idDocPath = null;
        if (!empty($_FILES['id_document']['name'])) {
            // ... upload logic (same as apply-form-pattern.md) ...
        }

        $pdo->prepare("
            INSERT INTO credit_brokers
            (broker_code, first_name, last_name, email, phone, id_number,
             company_name, company_registration, province, city,
             experience_years, why_join, id_document_path, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ")->execute([
            $brokerCode,
            $data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['id_number'],
            $data['company_name'], $data['company_registration'], $data['province'], $data['city'],
            $data['experience_years'], $data['why_join'], $idDocPath,
        ]);

        // Notify admin of new broker signup
        // mail(ADMIN_EMAIL, 'New Broker Signup', ...) or use PHPMailer

        setFlash('success', 'Application submitted! We will review and contact you shortly.');
        redirect('/broker-signup.php');
    }
}
```
