<?php
require_once __DIR__ . '/lib.php';
if (file_exists(__DIR__ . '/custom-domain.php')) {
    require_once __DIR__ . '/custom-domain.php';
    /* ---- Custom domain: serve restaurant website directly ---- */
    if (function_exists('assanio_handle_custom_domain')) {
        assanio_handle_custom_domain(PLATFORM_DOMAIN);
    }
}

$user = current_user();
if ($user) { header('Location: dashboard.php'); exit; }
page_head('Home', 'Assanio builds your restaurant a professional website with online ordering. Zero commission, WhatsApp ordering, one flat monthly fee. Live in 48 hours.');
?>
<link rel="stylesheet" href="assets/landing.css?v=20261022">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<div class="al-grain" aria-hidden="true"></div>
<noscript><style>.al-rev{opacity:1 !important;transform:none !important}</style></noscript>

<nav class="al-nav">
  <div class="al-nav-in">
    <a class="al-brand" href="index.php" aria-label="Assanio home">
      <span class="al-mark">R</span><span class="al-word"><?= e(PLATFORM_NAME) ?></span>
    </a>
    <div class="al-links">
      <a href="#templates">Templates</a>
      <a href="#math">The math</a>
      <a href="#pricing">Pricing</a>
      <a href="discover.php">Discover</a>
      <a href="#faq">FAQ</a>
    </div>
    <div class="al-nav-cta">
      <a class="al-login" href="login.php">Log in</a>
      <a class="al-btn al-btn-sm" href="register.php">Start free</a>
    </div>
  </div>
</nav>

<header class="al-hero al-hero-centered">
  <div class="al-wrap">
    <div class="al-rev al-hero-center">
      <p class="al-kicker">🚀 Pakistan's restaurant growth platform</p>
      <h1>Your restaurant.<br>Your website.<br><em>Zero commission.</em></h1>
      <p class="al-lede">Stop losing 25% of every order to marketplaces. Get your own professional website with WhatsApp ordering, customer database, and marketing tools — for one flat monthly fee.</p>
      <div class="al-cta-row al-cta-center">
        <a class="al-btn" href="register.php">Claim Free Setup →</a>
        <a class="al-btn al-btn-ghost" href="#math">See how much you save</a>
      </div>
      <div class="al-trust">
        <span>✅ No commission ever</span>
        <span>✅ Live in 48 hours</span>
        <span>✅ WhatsApp ordering</span>
      </div>
    </div>
  </div>
</header>

<div class="al-marquee" aria-hidden="true">
  <div class="al-marquee-in"><span>ZERO COMMISSION</span><span>&bull;</span><span>YOUR OWN WEBSITE</span><span>&bull;</span><span>WHATSAPP ORDERS</span><span>&bull;</span><span>LIVE IN 48 HOURS</span><span>&bull;</span><span>NO MARKETPLACE CUT</span><span>&bull;</span><span>ZERO COMMISSION</span><span>&bull;</span><span>YOUR OWN WEBSITE</span><span>&bull;</span><span>WHATSAPP ORDERS</span><span>&bull;</span><span>LIVE IN 48 HOURS</span><span>&bull;</span><span>NO MARKETPLACE CUT</span><span>&bull;</span></div>
</div>

<section class="al-sec" id="platform">
  <div class="al-wrap">
    <p class="al-secnum al-rev">01 &mdash; Platform</p>
    <h2 class="al-rev">Everything you need to sell direct.</h2>
    <p class="al-sub al-rev">One platform for your website, orders, customers and marketing. No juggling five different tools.</p>
    <div class="al-feat-grid">
      <div class="al-feat al-rev">
        <div class="al-feat-icon">🌐</div>
        <h3>Your own website</h3>
        <p>A professional restaurant website with your branding, menu, and online ordering. Looks great on every phone. No designer or developer needed.</p>
      </div>
      <div class="al-feat al-rev">
        <div class="al-feat-icon">💬</div>
        <h3>WhatsApp ordering</h3>
        <p>Every order arrives on your WhatsApp as a clean message — items, quantities, total, customer details. Confirm with one tap.</p>
      </div>
      <div class="al-feat al-rev">
        <div class="al-feat-icon">📊</div>
        <h3>Customer database</h3>
        <p>Every customer is saved automatically. See who orders what, send them deals on WhatsApp, and bring them back again and again.</p>
      </div>
    </div>
  </div>
</section>

<section class="al-sec" id="math" style="background:var(--al-paper2)">
  <div class="al-wrap">
    <p class="al-secnum al-rev">02 &mdash; The math</p>
    <h2 class="al-rev">Keep Rs 146,501 more. Every month.</h2>
    <p class="al-sub al-rev">On Rs 600,000 monthly online sales, here's the difference between marketplace commission and <?= e(PLATFORM_NAME) ?>'s flat fee:</p>
    <div class="al-math-grid">
      <div class="al-math-card al-rev">
        <h3>Marketplace commission</h3>
        <div class="al-math-row"><span class="lbl">Monthly online sales</span><span class="val">Rs 600,000</span></div>
        <div class="al-math-row neg"><span class="lbl">Commission (25%)</span><span class="val">&minus;Rs 150,000</span></div>
        <div class="al-math-row keep"><span class="lbl">You keep</span><span class="val">Rs 450,000</span></div>
      </div>
      <div class="al-math-card hot al-rev">
        <h3><?= e(PLATFORM_NAME) ?> flat fee</h3>
        <div class="al-math-row"><span class="lbl">Monthly online sales</span><span class="val">Rs 600,000</span></div>
        <div class="al-math-row"><span class="lbl">Business plan</span><span class="val">&minus;Rs 3,499</span></div>
        <div class="al-math-row keep"><span class="lbl">You keep</span><span class="val">Rs 596,501</span></div>
      </div>
    </div>
    <p class="al-math-foot al-rev">Example based on 25% marketplace commission. Your sales will vary; the flat fee stays the same.</p>
  </div>
</section>

<section class="al-sec" id="templates">
  <div class="al-wrap">
    <p class="al-secnum al-rev">03 &mdash; Templates</p>
    <h2 class="al-rev">Pick a design. Your site is live in minutes.</h2>
    <p class="al-sub al-rev">Four designs built for Pakistani restaurants. Choose one, add your menu — done. No designer needed.</p>
    <div class="al-tpl-grid">
      <div class="al-tpl al-rev">
        <div class="al-browser"><img src="assets/landing/t1-pizza.jpg" alt="Crust House pizza template" loading="lazy"></div>
        <div class="al-tpl-body">
          <div class="tag">Pizza &amp; Fast Food</div>
          <h3>Crust House</h3>
          <p>Built for deals, combos and family bundles. Your daily offers front and center — customers order in two taps.</p>
          <a class="al-tpl-link" href="register.php?template=pizza-fastfood">Use this template &rarr;</a>
        </div>
      </div>
      <div class="al-tpl al-rev">
        <div class="al-browser"><img src="assets/landing/t2-dera.jpg" alt="Dera BBQ House desi BBQ template" loading="lazy"></div>
        <div class="al-tpl-body">
          <div class="tag">Desi BBQ</div>
          <h3>Dera BBQ House</h3>
          <p>Warm, appetizing design for BBQ platters, karahi and family dining. Makes your food look as good as it tastes.</p>
          <a class="al-tpl-link" href="register.php?template=desi-bbq">Use this template &rarr;</a>
        </div>
      </div>
      <div class="al-tpl al-rev">
        <div class="al-browser"><img src="assets/landing/t3-cafe.jpg" alt="The Quiet Cup cafe template" loading="lazy"></div>
        <div class="al-tpl-body">
          <div class="tag">Cafe &amp; Bakery</div>
          <h3>The Quiet Cup</h3>
          <p>Clean, cozy layout for coffee, desserts and all-day breakfast. Perfect for dine-in menus and takeaway orders.</p>
          <a class="al-tpl-link" href="register.php?template=cafe">Use this template &rarr;</a>
        </div>
      </div>
      <div class="al-tpl al-rev">
        <div class="al-browser"><img src="assets/landing/t4-noore.jpg" alt="NOOR&Eacute; fine dining template" loading="lazy"></div>
        <div class="al-tpl-body">
          <div class="tag">Fine Dining</div>
          <h3>NOOR&Eacute;</h3>
          <p>Dark, premium design for upscale dining. Tasting menus, reservations and a look that matches your prices.</p>
          <a class="al-tpl-link" href="register.php?template=fine-dining">Use this template &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="al-sec" id="whatsapp" style="background:var(--al-paper2)">
  <div class="al-wrap al-wa-grid">
    <div class="al-wa-copy al-rev">
      <p class="al-secnum">04 &mdash; WhatsApp</p>
      <h2>Orders land where you already live: WhatsApp.</h2>
      <p>Every order arrives as a clean, readable WhatsApp message on your number &mdash; items, quantities, total, customer details. No new app to learn, no missed orders on a busy night.</p>
      <a class="al-btn" href="register.php" style="margin-top:12px">Start free</a>
    </div>
    <div class="al-rev">
      <div class="al-phone">
        <div class="al-phone-screen">
          <div class="al-wa-head">
            <span class="al-wa-ava">D</span>
            <div><b>Dera BBQ House</b><small>online</small></div>
          </div>
          <div class="al-wa-chat">
            <div class="al-bub in">Assalam-o-Alaikum! I'd like to order 2 chicken tikkas and 1 seekh kabab platter, please.<div class="al-wa-time">7:42 pm</div></div>
            <div class="al-bub in">
              <div class="ord-head">NEW ORDER &middot; ORD-55F2AA</div>
              <div class="ord-row"><span>2&times; Chicken Tikka</span><span>Rs 760</span></div>
              <div class="ord-row"><span>1&times; Seekh Kabab</span><span>Rs 550</span></div>
              <div class="ord-row ord-tot"><span>Total</span><span>Rs 1,310</span></div>
              <div class="ord-meta">Name: Ahmed<br>Address: Bahria Town Lhr</div>
              <div class="al-wa-time">7:43 pm</div>
            </div>
            <div class="al-bub out">Walaikum Assalam! Order confirmed &#10003; Delivery in 35&ndash;40 minutes.<div class="al-wa-time">7:44 pm</div></div>
          </div>
          <div class="al-wa-input"><span class="fake">Message</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="al-sec" id="how">
  <div class="al-wrap">
    <p class="al-secnum al-rev">05 &mdash; How it works</p>
    <h2 class="al-rev">Online in three steps.</h2>
    <p class="al-sub al-rev">No developers. No agency fees. No marketplace cuts.</p>
    <div class="al-how-grid">
      <div class="al-how al-rev">
        <div class="num">01</div>
        <h3>Pick a template</h3>
        <p>Four designs made for Pakistani restaurants. Choose the one that feels like yours.</p>
      </div>
      <div class="al-how al-rev">
        <div class="num">02</div>
        <h3>Make it yours</h3>
        <p>Add your name, WhatsApp number and menu in the visual editor. Change prices and deals anytime &mdash; from your phone.</p>
      </div>
      <div class="al-how al-rev">
        <div class="num">03</div>
        <h3>Take orders</h3>
        <p>Share your link. Customers order on your site; every order lands in your dashboard and on WhatsApp.</p>
      </div>
    </div>
  </div>
</section>

<section class="al-sec" id="pricing" style="background:var(--al-paper2)">
  <div class="al-wrap">
    <p class="al-secnum al-rev">06 &mdash; Pricing</p>
    <h2 class="al-rev">Flat monthly pricing.</h2>
    <p class="al-sub al-rev">No commission on your orders. No hidden charges. You keep 100% of every sale.</p>
    <div class="al-price-grid">
      <div class="al-plan al-rev">
        <p class="pname">Starter</p>
        <p class="al-pprice">Rs 1,999<small>/mo</small></p>
        <ul>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>1 restaurant website</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>WhatsApp ordering built in</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Visual editor</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>1 template design</li>
        </ul>
        <a class="al-btn al-btn-ghost" href="register.php">Start with Starter</a>
      </div>
      <div class="al-plan hot al-rev">
        <span class="al-badge">MOST POPULAR</span>
        <p class="pname">Business</p>
        <p class="al-pprice">Rs 3,499<small>/mo</small></p>
        <ul>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Everything in Starter</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Website checkout &mdash; cash on delivery</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Coupons &amp; promo codes</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Combo deals &amp; deal-of-the-day</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Own domain ready</li>
        </ul>
        <a class="al-btn" href="register.php">Start with Business</a>
      </div>
      <div class="al-plan al-rev">
        <p class="pname">Funnel Pro</p>
        <p class="al-pprice">Rs 5,999<small>/mo</small></p>
        <ul>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Everything in Business</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>WhatsApp deals signup band</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Orders &amp; revenue analytics</li>
          <li><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3.2 3L13 4.5" stroke="#C8102E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Priority support</li>
        </ul>
        <a class="al-btn al-btn-ghost" href="register.php">Start with Funnel Pro</a>
      </div>
    </div>
    <p class="al-price-foot al-rev">Yearly billing = 10&times; monthly. One-time setup Rs 9,999 &mdash; <b>waived with yearly billing.</b></p>
  </div>
</section>

<section class="al-sec">
  <div class="al-wrap">
    <div class="al-pilot al-rev">
      <div>
        <p class="al-secnum">07 &mdash; White-glove setup</p>
        <h2>We set up everything for you — personally.</h2>
        <p>No fake reviews, no rented testimonials. We earn trust the old way: we set up your menu, your deals and your WhatsApp ordering ourselves &mdash; and waive the setup fee entirely with yearly billing.</p>
        <a class="al-btn" href="register.php">Get started &rarr;</a>
      </div>
      <div class="al-pilot-list">
        <h3>Your pilot setup includes</h3>
        <ul>
          <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Full menu built from your dishes &amp; rates</li>
          <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>WhatsApp ordering on your number</li>
          <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Deal of the day &amp; coupons configured</li>
          <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Rs 9,999 setup fee &mdash; <b>waived</b></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="al-sec" id="faq" style="background:var(--al-paper2)">
  <div class="al-wrap">
    <p class="al-secnum al-rev">08 &mdash; FAQ</p>
    <h2 class="al-rev">Straight answers.</h2>
    <div class="al-faq al-rev">
      <details open><summary>Do I need coding skills?</summary><div class="a">No. If you can use WhatsApp, you can run your <?= e(PLATFORM_NAME) ?> site. The editor is point-and-click &mdash; and pilot setups are done for you.</div></details>
      <details><summary>How do customers pay?</summary><div class="a">Cash on delivery, JazzCash/Easypaisa, or direct bank transfer &mdash; you choose which options your checkout shows.</div></details>
      <details><summary>Which cities does this work in?</summary><div class="a">Anywhere in Pakistan. Your website works nationwide; you set your own delivery areas.</div></details>
      <details><summary>Can customers order through WhatsApp?</summary><div class="a">Yes &mdash; it&rsquo;s the default. Every order also lands in your dashboard, so nothing gets lost on a busy night.</div></details>
      <details><summary>What happens after the pilot?</summary><div class="a">The same flat monthly fee. No commission, ever &mdash; that&rsquo;s the whole point.</div></details>
      <details><summary>Can I use my own domain?</summary><div class="a">Yes, on Business and above &mdash; custom domains arrive in v1.5, and your platform address works fully until then.</div></details>
    </div>
  </div>
</section>

<section class="al-final">
  <div class="al-wrap al-rev">
    <h2>Your competitors are already saving Rs 146,501/month.<br>Are you?</h2>
    <p>Get your restaurant online in 48 hours — <b>setup FREE</b> with yearly billing.</p>
    <a class="al-btn" href="register.php">Claim My Free Setup →</a>
  </div>
</section>

<footer class="al-foot">
  <div class="al-foot-in">
    <span><span class="al-mark" style="width:26px;height:26px;font-size:15px;border-radius:7px">R</span> <span class="al-word"><?= e(PLATFORM_NAME) ?></span></span>
    <span>&copy; 2026 <?= e(PLATFORM_NAME) ?> &mdash; restaurant websites &amp; online ordering for Pakistan.<br>Your restaurant website + online ordering &mdash; live in 48 hours.</span>
  </div>
</footer>

<script>
(function(){
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var els = document.querySelectorAll('.al-rev');
  if (!('IntersectionObserver' in window)) { els.forEach(function(e){e.classList.add('in')}); return; }
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(en){ if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, {threshold: 0.12});
  els.forEach(function(e){ io.observe(e); });
})();
</script>
<?php page_foot(); ?>
