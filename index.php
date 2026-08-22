<?php
session_start();
require_once 'config/db.php';

// Fetch announcements
$stmt = $pdo->prepare("SELECT * FROM public_announcements WHERE is_active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY id DESC LIMIT 5");
$stmt->execute();
$announcements = $stmt->fetchAll();

if (empty($announcements)) {
    $announcements = [
        [
            'title' => 'Citizen services are now available online',
            'announcement_type' => 'General',
            'message' => 'Use the Citizen Login to view your linked property, submit Mutation or KYC requests, and track application status.',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Keep your allottee mobile number updated',
            'announcement_type' => 'Important',
            'message' => 'Your verified mobile number is used to show linked properties and access citizen services securely.',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// Fetch FAQs for homepage
$stmt = $pdo->prepare("SELECT * FROM faqs WHERE is_active = 1 LIMIT 6");
$stmt->execute();
$faqs = $stmt->fetchAll();

if (empty($faqs)) {
    $faqs = [
        [
            'id' => 'default-login',
            'question' => 'How do I access the Citizen Portal?',
            'answer' => 'Click Citizen Login, enter your mobile number, request an OTP, and use the demo OTP 111000 for local testing.'
        ],
        [
            'id' => 'default-property',
            'question' => 'Why is my property not showing?',
            'answer' => 'The allottee mobile number must be saved against the property. Contact the office if your number needs to be updated.'
        ],
        [
            'id' => 'default-services',
            'question' => 'Which services can citizens apply for?',
            'answer' => 'Citizens can submit Mutation and KYC Update requests for properties linked to their verified mobile number.'
        ],
        [
            'id' => 'default-support',
            'question' => 'How can I contact the support team?',
            'answer' => 'Call the helpline at 180018005001 or email the address shown in the Contact section.'
        ]
    ];
}

// Get system settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings");
$stmt->execute();
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$contact_phone = trim($settings['portal_contact_phone'] ?? '');
$helpline_number = trim($settings['helpline_number'] ?? '');
if ($contact_phone === '' || str_contains($contact_phone, 'X')) {
    $contact_phone = '180018005001';
}
if ($helpline_number === '' || str_contains($helpline_number, 'X')) {
    $helpline_number = '180018005001';
}

$public_campaigns = $pdo->query("SELECT id, campaign_type, lottery_name, scheme_name, plot_count, property_type, price_per_unit, start_date, end_date
    FROM lottery_campaigns WHERE status = 'Published' AND start_date <= CURDATE() AND end_date >= CURDATE()
    ORDER BY end_date ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lucknow Development Authority - Property Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR TOP -->
<div class="lda-navbar-top">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-white">📞 Helpline: <?= htmlspecialchars($helpline_number) ?></span>
            <span class="text-white ms-3">✉️ Email: <?= htmlspecialchars($settings['portal_contact_email'] ?? 'N/A') ?></span>
        </div>
        <div>
            <span class="text-white me-3">Language:</span>
            <a href="#" class="text-white text-decoration-none">English</a>
            <span class="text-white mx-2">|</span>
            <a href="#" class="text-white text-decoration-none">हिन्दी</a>
        </div>
    </div>
</div>

<!-- MAIN NAVBAR -->
<nav class="lda-navbar navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span>🏢</span>
            <div class="navbar-brand-text">
                <h1><?= htmlspecialchars($settings['portal_name'] ?? 'LDA Portal') ?></h1>
                <small>Lucknow Development Authority</small>
            </div>
        </a>
        
        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#announcements">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#faq">Help & FAQ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
                <li class="nav-item">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard/<?= isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Admin' ? 'admin' : 'officer' ?>.php" class="btn btn-login">Officer Portal</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-login">Officer Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO BANNER -->
<div class="hero-banner">
    <div class="hero-content">
        <h1>Welcome to LDA Property Portal</h1>
        <p>Transparent and Citizen-Centric Property Management System</p>
        <div class="hero-buttons">
            <button class="btn btn-hero btn-hero-primary" data-bs-toggle="modal" data-bs-target="#propertySearchModal">
                🔍 Search Property
            </button>
            <a href="#" data-bs-toggle="modal" data-bs-target="#citizenLoginModal" class="btn btn-hero btn-hero-secondary">
                📱 Citizen Login
            </a>
        </div>
    </div>
</div>

<!-- QUICK ACCESS CARDS SECTION -->
<section class="quick-access-section" id="services">
    <div class="container">
        <div class="quick-access-title">
            <h2>Quick Access Services</h2>
            <p>Apply online, check status, and manage your properties</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="quick-card" data-bs-toggle="modal" data-bs-target="#propertySearchModal">
                    <div class="quick-card-icon">🔍</div>
                    <h5>Property Search</h5>
                    <p>Verify property details by ID or scheme name</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="quick-card" data-bs-toggle="modal" data-bs-target="#citizenLoginModal">
                    <div class="quick-card-icon">📋</div>
                    <h5>Mutation Application</h5>
                    <p>Apply for name transfer online</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="quick-card" onclick="window.location.href='citizen/noc-request.php'">
                    <div class="quick-card-icon">📄</div>
                    <h5>NOC Request</h5>
                    <p>Request No Objection Certificate</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="quick-card" onclick="window.location.href='citizen/payment.php'">
                    <div class="quick-card-icon">💳</div>
                    <h5>Online Payment</h5>
                    <p>Pay dues and fees online</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="quick-card" onclick="window.location.href='citizen/grievance.php'">
                    <div class="quick-card-icon">📞</div>
                    <h5>File Grievance</h5>
                    <p>Report issues and track resolution</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="quick-card" data-bs-toggle="modal" data-bs-target="#citizenLoginModal">
                    <div class="quick-card-icon">👤</div>
                    <h5>Citizen Portal</h5>
                    <p>Track applications and check status</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="public-campaign-section" id="opportunities">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><p class="section-kicker mb-1">OPEN OPPORTUNITIES</p><h2>Lottery & E-Auction</h2></div>
            <a href="#citizenLoginModal" data-bs-toggle="modal" class="btn btn-outline-primary">Login to Apply</a>
        </div>
        <?php if (!$public_campaigns): ?>
            <div class="empty-state">No Lottery or E-Auction is open right now.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($public_campaigns as $serial => $campaign): ?>
                    <div class="col-md-6 col-lg-4"><article class="campaign-public-card"><span class="citizen-property-number">S.No. <?= $serial + 1 ?></span><span class="badge text-bg-warning mb-2"><?= htmlspecialchars($campaign['campaign_type'] ?? 'Lottery') ?></span><h3><?= htmlspecialchars($campaign['lottery_name']) ?></h3><p class="mb-1"><strong><?= htmlspecialchars($campaign['scheme_name']) ?></strong> · <?= htmlspecialchars($campaign['property_type']) ?></p><p class="text-muted mb-1"><?= (int) $campaign['plot_count'] ?> units · ₹<?= number_format($campaign['price_per_unit'], 2) ?></p><small class="text-muted"><?= htmlspecialchars($campaign['start_date']) ?> to <?= htmlspecialchars($campaign['end_date']) ?></small><a href="#citizenLoginModal" data-bs-toggle="modal" class="btn btn-sm btn-primary mt-3">Apply Now</a></article></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="public-guide-section" id="how-it-works">
    <div class="container">
        <div class="public-guide-heading">
            <div><p class="section-kicker mb-1">CITIZEN GUIDE</p><h2>Property schemes ko samjhein</h2><p>Lottery, e-auction aur property services ke baare mein simple information.</p></div>
            <a href="#faq" class="btn btn-outline-primary">Help & FAQ</a>
        </div>
        <div class="row g-4">
            <div class="col-md-4"><article class="public-guide-card"><img src="assets/images/citizen-lottery.svg" alt="Lottery property scheme"><div><span>01 · Lottery</span><h3>Lottery kya hoti hai?</h3><p>Eligible applicants ke beech computerised draw hota hai. Successful applicant ko campaign rules ke mutabik property allot hoti hai.</p></div></article></div>
            <div class="col-md-4"><article class="public-guide-card"><img src="assets/images/citizen-auction.svg" alt="E-auction property scheme"><div><span>02 · E-Auction</span><h3>E-Auction mein kya hota hai?</h3><p>Eligible bidders online property ke liye bid karte hain. Terms aur conditions ke mutabik highest valid bid par allotment hota hai.</p></div></article></div>
            <div class="col-md-4"><article class="public-guide-card"><img src="assets/images/citizen-fcfs.svg" alt="FCFS property scheme"><div><span>03 · FCFS</span><h3>First Come, First Served</h3><p>Eligible applications ko submission time ke basis par priority milti hai. Last date aur required documents zaroor check karein.</p></div></article></div>
        </div>
    </div>
</section>

<!-- ANNOUNCEMENTS SECTION -->
<section class="announcements-section" id="announcements">
    <div class="container">
        <h2 class="mb-4" style="color: var(--primary-blue); font-weight: 700;">📢 Latest Announcements</h2>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $serial => $ann): ?>
                        <div class="announcement-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6><span class="announcement-number">S.No. <?= $serial + 1 ?></span><?= htmlspecialchars($ann['title']) ?></h6>
                                <span class="announcement-badge"><?= ucfirst($ann['announcement_type'] ?? 'General') ?></span>
                            </div>
                            <p><?= htmlspecialchars(substr($ann['message'] ?? $ann['description'], 0, 200)) ?>...</p>
                            <small class="text-muted">Published: <?= date('d M Y', strtotime($ann['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">No announcements at this time.</div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                    <h6 class="text-warning mb-2">⚠️ Important Notice</h6>
                    <p style="font-size: 0.9rem; color: #666;">All online applications are processed within 15-30 days. Please keep your contact information updated for timely notifications.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section" id="faq">
    <div class="container">
        <div class="faq-title">
            <h2>Frequently Asked Questions</h2>
            <p>Find answers to common queries</p>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <?php if (!empty($faqs)): ?>
                        <?php foreach ($faqs as $i => $faq): ?>
                            <div class="accordion-item border-0 mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $i !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $faq['id'] ?>">
                                        <span class="faq-number">S.No. <?= $i + 1 ?></span><?= htmlspecialchars($faq['question']) ?>
                                    </button>
                                </h2>
                                <div id="faq<?= $faq['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer" id="contact">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <h6>🏢 About LDA</h6>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.8);">Lucknow Development Authority is committed to transparent and citizen-centric governance in property management and urban development.</p>
            </div>
            
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#announcements">Announcements</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h6>Support</h6>
                <ul>
                    <li><a href="index.php#faq">Help Center</a></li>
                    <li><a href="contact.php#contact-form">File Complaint</a></li>
                    <li><a href="contact.php#terms">Terms & Conditions</a></li>
                    <li><a href="contact.php#privacy">Privacy Policy</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h6>Contact Us</h6>
                <div class="contact-info">
                    <div class="contact-info-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($contact_phone) ?></span>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($settings['portal_contact_email'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 Lucknow Development Authority. All rights reserved. | Developed by: E-Connect Solutions</p>
        </div>
    </div>
</footer>

<!-- PROPERTY SEARCH MODAL -->
<div class="modal fade" id="propertySearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🔍 Property Search</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="modalSearchForm">
                    <div class="mb-3">
                        <label class="form-label">Property ID / Code *</label>
                        <input type="text" class="form-control" id="modalSearchCode" placeholder="Enter property code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scheme Name (Optional)</label>
                        <input type="text" class="form-control" id="modalSearchScheme" placeholder="Search by scheme">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </form>
                <div id="modalSearchResults" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- CITIZEN LOGIN MODAL -->
<div class="modal fade" id="citizenLoginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📱 Citizen Login</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="citizenLoginForm">
                    <div class="mb-3">
                        <label class="form-label">Mobile Number *</label>
                        <input type="tel" class="form-control" id="citizenPhone" placeholder="+91 XXXX XXXX XX" required>
                    </div>
                    <div id="otpSection" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">OTP *</label>
                            <input type="text" class="form-control" id="citizenOtp" placeholder="Enter 6-digit OTP" maxlength="6">
                        </div>
                        <small class="text-muted">Demo OTP: <strong>111000</strong> &middot; Valid for 10 minutes.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">Request OTP</button>
                </form>
                <p class="mt-3 text-center text-muted" style="font-size: 0.9rem;">
                    Don't have an account? <a href="citizen/register.php">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Property Search in Modal
    document.getElementById('modalSearchForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const code = document.getElementById('modalSearchCode').value;
        const scheme = document.getElementById('modalSearchScheme').value;
        
        try {
            const response = await fetch('api/search-property.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({property_code: code, scheme_name: scheme})
            });
            const data = await response.json();
            
            let html = '';
            if (data.success && data.property) {
                html = `
                    <div class="alert alert-success">
                        <h6>${data.property.scheme_name}</h6>
                        <p>Property ID: ${data.property.property_code}</p>
                        <p>Address: ${data.property.address}</p>
                        <p>Price: ₹${data.property.price}</p>
                        <p>Status: <span class="badge bg-info">${data.property.status}</span></p>
                    </div>
                `;
            } else {
                html = '<div class="alert alert-warning">Property not found. Please check details.</div>';
            }
            document.getElementById('modalSearchResults').innerHTML = html;
        } catch (error) {
            console.error('Search error:', error);
        }
    });
    
    // Citizen Login OTP demo flow
    document.getElementById('citizenLoginForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const phone = document.getElementById('citizenPhone').value;
        const otpInput = document.getElementById('citizenOtp');
        const submitButton = document.getElementById('loginSubmitBtn');
        const otpSection = document.getElementById('otpSection');

        if (submitButton.dataset.otpRequested === 'true') {
            try {
                const response = await fetch('api/citizen-otp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({phone_number: phone, otp: otpInput.value, action: 'verify'})
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    otpInput.classList.add('is-invalid');
                    otpInput.focus();
                }
            } catch (error) {
                console.error('OTP verification error:', error);
            }
            return;
        }
        
        try {
            const response = await fetch('api/citizen-otp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({phone_number: phone})
            });
            const data = await response.json();
            
            if (data.success) {
                otpSection.style.display = 'block';
                submitButton.dataset.otpRequested = 'true';
                submitButton.textContent = 'Verify OTP';
                otpInput.focus();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('OTP error:', error);
        }
    });
</script>

</body>
</html>