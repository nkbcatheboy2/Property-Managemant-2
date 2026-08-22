<?php
session_start();
require_once 'config/db.php';

$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings");
$stmt->execute();
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$phone = trim($settings['portal_contact_phone'] ?? '');
$helpline = trim($settings['helpline_number'] ?? '');
if ($phone === '' || str_contains($phone, 'X')) {
    $phone = '180018005001';
}
$helpline = ($helpline === '' || str_contains($helpline, 'X')) ? $phone : $helpline;
$email = $settings['portal_contact_email'] ?? 'support@lda-portal.gov.in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - LDA Property Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="contact-page">
    <div class="lda-navbar-top">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="fas fa-phone me-2"></i>Helpline: <?= htmlspecialchars($helpline) ?></span>
            <a href="login.php" class="text-decoration-none"><i class="fas fa-lock me-2"></i>Officer Login</a>
        </div>
    </div>

    <nav class="lda-navbar navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span>🏢</span>
                <div class="navbar-brand-text">
                    <h1>LDA Property Portal</h1>
                    <small>Lucknow Development Authority</small>
                </div>
            </a>
            <a href="index.php" class="btn btn-login"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
        </div>
    </nav>

    <main>
        <section class="contact-hero">
            <div class="container">
                <p class="eyebrow">WE ARE HERE TO HELP</p>
                <h1>Contact the LDA Property Portal</h1>
                <p>Get help with property searches, applications, payments, grievances and portal access.</p>
            </div>
        </section>

        <section class="contact-content" id="contact-form">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="contact-panel h-100">
                            <p class="section-kicker">SUPPORT CHANNELS</p>
                            <h2>Talk to our team</h2>
                            <p class="text-muted">Use the details below for quick assistance during office hours.</p>
                            <a class="contact-detail" href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $phone)) ?>">
                                <span class="contact-detail-icon"><i class="fas fa-phone"></i></span>
                                <span><strong>Phone</strong><small><?= htmlspecialchars($phone) ?></small></span>
                            </a>
                            <a class="contact-detail" href="mailto:<?= htmlspecialchars($email) ?>">
                                <span class="contact-detail-icon"><i class="fas fa-envelope"></i></span>
                                <span><strong>Email</strong><small><?= htmlspecialchars($email) ?></small></span>
                            </a>
                            <div class="contact-detail">
                                <span class="contact-detail-icon"><i class="fas fa-clock"></i></span>
                                <span><strong>Office hours</strong><small>Monday to Friday, 10:00 AM - 5:00 PM</small></span>
                            </div>
                            <div class="contact-detail">
                                <span class="contact-detail-icon"><i class="fas fa-location-dot"></i></span>
                                <span><strong>Office</strong><small>Lucknow Development Authority, Lucknow, Uttar Pradesh</small></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="contact-panel">
                            <p class="section-kicker">SEND A QUERY</p>
                            <h2>How can we help?</h2>
                            <form method="post" action="mailto:<?= htmlspecialchars($email) ?>" enctype="text/plain">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Your name</label>
                                        <input id="name" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email address</label>
                                        <input id="email" type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subject</label>
                                        <select id="subject" name="subject" class="form-select" required>
                                            <option value="">Select a topic</option>
                                            <option>Property search</option>
                                            <option>Application or payment</option>
                                            <option>Grievance</option>
                                            <option>Officer portal access</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea id="message" name="message" rows="5" class="form-control" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-contact" type="submit"><i class="fas fa-paper-plane me-2"></i>Prepare Email</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer contact-footer">
        <div class="container footer-bottom">
            <p>&copy; 2026 Lucknow Development Authority. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
