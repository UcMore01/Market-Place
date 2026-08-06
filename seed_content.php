<?php
// seed_content.php - seeds the editable info pages into content_items.
// Idempotent: skips pages whose title already exists. Run via apply_schema.php
// or directly: php seed_content.php
function seed_connect_to_marketplace() {
    $hosts = ['127.0.0.1', 'localhost'];
    $ports = [3307, 3306, 3308];
    foreach ($hosts as $host) {
        foreach ($ports as $port) {
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=marketplace;charset=utf8mb4";
                return new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    throw new RuntimeException('Unable to connect to MariaDB/MySQL on localhost ports 3306/3307/3308.');
}

try {
    $pdo = seed_connect_to_marketplace();
} catch (Exception $e) {
    echo "DB Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$pages = [
    'About Us' => <<<'HTML'
<section class="about-hero">
  <div class="container text-center">
    <h1 class="fw-bold mb-3">About Marketplace</h1>
    <p class="lead mb-0">Connecting buyers and sellers with a trusted, global online marketplace.</p>
  </div>
</section>
<div class="container py-5">
  <div class="row mb-5">
    <div class="col-lg-8 mx-auto text-center">
      <h2 class="fw-bold mb-3">Our Mission</h2>
      <p class="text-muted">Marketplace was built to make buying and selling online simple, safe, and accessible to everyone. We bring together passionate sellers and discovering buyers from around the world on a single, reliable platform.</p>
    </div>
  </div>
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="feature-icon">🌍</div>
          <h5 class="fw-bold">Global Reach</h5>
          <p class="text-muted mb-0">Discover quality products from top sellers across the globe, all in one place.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="feature-icon">🔒</div>
          <h5 class="fw-bold">Secure &amp; Trusted</h5>
          <p class="text-muted mb-0">Verified sellers, secure checkout, and buyer protection on every order.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="feature-icon">💡</div>
          <h5 class="fw-bold">Empowering Sellers</h5>
          <p class="text-muted mb-0">Easy tools to list products, manage orders, and grow your business online.</p>
        </div>
      </div>
    </div>
  </div>
  <div class="row align-items-center g-4">
    <div class="col-md-6">
      <img src="https://images.unsplash.com/photo-1556742502-ec7c0e9f34b1?w=800&h=500&fit=crop" alt="Marketplace" class="img-fluid rounded shadow-sm">
    </div>
    <div class="col-md-6">
      <h3 class="fw-bold mb-3">Why Choose Us</h3>
      <ul class="list-unstyled">
        <li class="mb-2">✅ Curated, high-quality products</li>
        <li class="mb-2">✅ Friendly customer support</li>
        <li class="mb-2">✅ Transparent pricing with no hidden fees</li>
        <li class="mb-2">✅ Fast, trackable shipping</li>
        <li class="mb-2">✅ Easy returns and refunds</li>
      </ul>
      <a href="products.html" class="btn btn-primary rounded-pill px-4 mt-2">Start Shopping</a>
    </div>
  </div>
</div>
HTML
    ,
    'FAQ' => <<<'HTML'
<div class="accordion" id="faqAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header" id="q1"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="true" aria-controls="a1">How do I create an account?</button></h2>
    <div id="a1" class="accordion-collapse collapse show" aria-labelledby="q1" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Click <strong>Sign-up</strong> in the top navigation, fill in your details, and submit the form. Seller accounts require admin approval before you can start listing products.</div></div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="q2"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false" aria-controls="a2">How do I place an order?</button></h2>
    <div id="a2" class="accordion-collapse collapse" aria-labelledby="q2" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Add items to your cart, proceed to checkout, and confirm your shipping and payment details. You can track your order from the Orders page.</div></div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="q3"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3" aria-expanded="false" aria-controls="a3">What payment methods do you accept?</button></h2>
    <div id="a3" class="accordion-collapse collapse" aria-labelledby="q3" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">We support major credit and debit cards along with other secure online payment options at checkout.</div></div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="q4"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4" aria-expanded="false" aria-controls="a4">What is your return policy?</button></h2>
    <div id="a4" class="accordion-collapse collapse" aria-labelledby="q4" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Most items can be returned within 30 days of delivery. Please review our Refund &amp; Return Policy for full details.</div></div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="q5"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a5" aria-expanded="false" aria-controls="a5">How do I become a seller?</button></h2>
    <div id="a5" class="accordion-collapse collapse" aria-labelledby="q5" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Register for an account, choose the seller option, submit your seller documents, and wait for admin approval. Once approved, you can add products from your seller dashboard.</div></div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="q6"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a6" aria-expanded="false" aria-controls="a6">I forgot my password. What do I do?</button></h2>
    <div id="a6" class="accordion-collapse collapse" aria-labelledby="q6" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Go to the Forgot Password page, enter your email, and follow the reset link we send you.</div></div>
  </div>
</div>
HTML
    ,
    'Terms & Conditions' => <<<'HTML'
<p>Last updated: January 2026. By accessing or using Marketplace, you agree to the following terms.</p>
<h2>1. Acceptance of Terms</h2>
<p>By creating an account or using our services, you agree to these Terms &amp; Conditions and our Privacy Policy. If you do not agree, please do not use the platform.</p>
<h2>2. Accounts</h2>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. You must provide accurate information and keep it up to date.</p>
<h2>3. Buying and Selling</h2>
<p>Buyers and sellers transact directly on the platform. Sellers are responsible for the accuracy of listings, product quality, and fulfilling orders. Marketplace is not a party to individual transactions.</p>
<h2>4. Prohibited Conduct</h2>
<p>You may not list illegal items, infringe intellectual property, engage in fraud, abuse other users, or interfere with the operation of the platform.</p>
<h2>5. Payments and Fees</h2>
<p>Payments are processed securely through our payment partners. Applicable fees are disclosed at checkout or in seller agreements.</p>
<h2>6. Limitation of Liability</h2>
<p>Marketplace is provided "as is" without warranties. To the fullest extent permitted by law, we are not liable for indirect or consequential damages arising from your use of the platform.</p>
<h2>7. Changes to Terms</h2>
<p>We may update these terms from time to time. Continued use after changes constitutes acceptance of the revised terms.</p>
<h2>8. Contact</h2>
<p>Questions about these terms? Reach us via our Contact page.</p>
HTML
    ,
    'Privacy Policy' => <<<'HTML'
<p>Last updated: January 2026. This Privacy Policy explains how Marketplace collects, uses, and protects your information when you use our platform.</p>
<h2>1. Information We Collect</h2>
<p>We collect information you provide directly, such as your name, email, shipping address, and payment details, as well as data generated automatically through your use of the site (such as device and usage information).</p>
<h2>2. How We Use Your Information</h2>
<p>We use your information to create and manage your account, process orders, provide customer support, improve our services, and send important updates about your account or orders.</p>
<h2>3. Cookies and Tracking</h2>
<p>We use cookies and similar technologies to keep you signed in, remember your preferences, and analyze site traffic. You can manage cookies through your browser settings.</p>
<h2>4. Sharing Your Information</h2>
<p>We share information with sellers only as needed to fulfill your orders, and with payment processors and service providers who help us operate the platform. We do not sell your personal information.</p>
<h2>5. Security</h2>
<p>We use industry-standard measures to protect your data. However, no method of transmission or storage is completely secure, and we cannot guarantee absolute security.</p>
<h2>6. Your Rights</h2>
<p>You may request access to, correction of, or deletion of your personal data by contacting us. You can also opt out of marketing communications at any time.</p>
<h2>7. Contact Us</h2>
<p>If you have questions about this Privacy Policy, please reach out via our Contact page.</p>
HTML
    ,
    'Refund & Return Policy' => <<<'HTML'
<p>We want you to be happy with your purchase. This policy explains how returns and refunds work on Marketplace.</p>
<h2>1. Return Window</h2>
<p>Most items can be returned within <strong>30 days</strong> of delivery, provided they are unused and in their original condition and packaging.</p>
<h2>2. Eligible Items</h2>
<p>Items must be in resalable condition. Certain products (such as perishables, personalized items, or digital downloads) may not be eligible for return. Sellers may list specific return conditions on the product page.</p>
<h2>3. How to Start a Return</h2>
<p>Go to your Orders page, select the order, and request a return. Our support team or the seller will review and approve the request.</p>
<h2>4. Refunds</h2>
<p>Once we receive and inspect the returned item, approved refunds are issued to your original payment method within 5–10 business days. Shipping costs are non-refundable unless the return is due to our error or a defective item.</p>
<h2>5. Damaged or Wrong Items</h2>
<p>If you receive a damaged, defective, or incorrect item, contact us within 7 days of delivery with photos, and we will arrange a replacement or full refund at no cost to you.</p>
<h2>6. Exchanges</h2>
<p>Where available, exchanges follow the same process as returns. Please note your preferred replacement in your return request.</p>
<h2>7. Questions</h2>
<p>Need help? Visit our Customer Support page or contact us.</p>
HTML
    ,
];

$sort = 1;
foreach ($pages as $title => $body) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM content_items WHERE type='page' AND title = ?");
    $check->execute([$title]);
    if ((int)$check->fetchColumn() > 0) {
        echo "Skip existing page: $title\n";
        continue;
    }
    $stmt = $pdo->prepare("INSERT INTO content_items (type, title, body, status, sort_order) VALUES ('page', ?, ?, 'active', ?)");
    $stmt->execute([$title, $body, $sort++]);
    echo "Seeded page: $title\n";
}
echo "Content seeding complete.\n";
