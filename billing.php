<?php
require_once __DIR__ . '/includes/functions.php';
ss_session_start();
require_login('/login.php');
$user = ss_get_current_user();
$user_name = $user ? htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : 'User';
?>
<style>
:root{
  --gold:#D4A017;--gold2:#F0C040;--gold3:#B8860B;--gold-bright:#FFD700;
  --navy:#0A1F44;--navy2:#1A3A6B;--navy3:#0D1B3E;--navy4:#071230;
  --blue:#2E5EAA;--blue2:#1E90FF;--blue3:#4A7EC7;
  --red:#DC2626;--green:#16A34A;
  --white:#FFFFFF;--cream:#F5E6B2;--muted:#8BA3CC;
  --card:rgba(13,27,62,0.94);--border:rgba(212,160,23,0.22);
  --border2:rgba(46,94,170,0.3);
  --font-head:'Playfair Display',Georgia,serif;
  --font-body:'Lato','Trebuchet MS',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-body);background:var(--navy3);color:var(--text);display:flex;min-height:100vh;overflow-x:hidden}

.sidebar{width:var(--sidebar);background:var(--navy);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .3s ease;overflow-y:auto}
.sidebar-brand{padding:1.5rem 1.25rem;display:flex;align-items:center;gap:.75rem;font-family:var(--font-head);font-size:1.2rem;font-weight:700;border-bottom:1px solid rgba(250,246,240,.1);flex-shrink:0;color:var(--navy3)}
.sidebar-brand-icon{width:36px;height:36px;background:var(--gold);border-radius:8px;display:grid;place-items:center;font-size:.95rem;color:var(--navy3);flex-shrink:0}
.sidebar-brand span{color:var(--gold2)}
.sidebar-org{padding:.75rem 1.25rem;display:flex;align-items:center;gap:.75rem;background:rgba(250,246,240,.04);border-bottom:1px solid rgba(250,246,240,.08);flex-shrink:0}
.org-avatar{width:32px;height:32px;background:var(--gold);border-radius:8px;display:grid;place-items:center;font-family:var(--font-head);font-size:.75rem;font-weight:700;color:var(--navy3);flex-shrink:0}
.org-name{font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--navy3)}
.org-plan{font-size:.7rem;color:var(--gold2);background:rgba(200,169,110,.15);padding:.1rem .4rem;border-radius:4px}
.sidebar-nav{flex:1;padding:1rem 0}
.nav-section-label{padding:.25rem 1.25rem .25rem;font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:rgba(250,246,240,.35);margin-top:.75rem}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.65rem 1.25rem;color:rgba(250,246,240,.55);text-decoration:none;font-size:.875rem;font-weight:400;transition:all .25s;cursor:pointer;position:relative;border:none;background:none;width:100%}
.nav-item:hover{color:var(--navy3);background:rgba(250,246,240,.06)}
.nav-item.active{color:var(--gold2);background:rgba(200,169,110,.12)}
.nav-item.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--gold2);border-radius:0 3px 3px 0}
.nav-item i{width:20px;text-align:center;font-size:.9rem;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:.65rem;font-weight:700;padding:.15rem .45rem;border-radius:50px}
.nav-badge.warn{background:var(--gold);color:var(--navy)}
.sidebar-footer{padding:1rem 1.25rem;border-top:1px solid rgba(250,246,240,.1);flex-shrink:0}
.user-info{display:flex;align-items:center;gap:.75rem}
.user-avatar{width:36px;height:36px;border-radius:50%;background:var(--gold);display:grid;place-items:center;font-family:var(--font-head);font-size:.8rem;font-weight:700;color:var(--navy3);flex-shrink:0}
.user-name{font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--navy3)}
.user-role{font-size:.7rem;color:rgba(250,246,240,.45)}
.logout-btn{margin-left:auto;color:rgba(250,246,240,.4);background:none;border:none;cursor:pointer;font-size:.9rem;transition:color .2s}
.logout-btn:hover{color:var(--red)}

.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--navy3);position:sticky;top:0;z-index:100;gap:1rem}
.page-title{font-family:var(--font-head);font-size:1.2rem;font-weight:700;color:var(--navy)}
.topbar-right{display:flex;align-items:center;gap:.75rem}
.icon-btn{width:36px;height:36px;background:var(--navy3);border:1.5px solid var(--border);border-radius:8px;display:grid;place-items:center;cursor:pointer;color:var(--muted);font-size:.9rem;transition:all .2s;position:relative;text-decoration:none}
.icon-btn:hover{color:var(--navy);border-color:var(--gold)}
.notif-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--red);border-radius:50%;border:2px solid var(--navy3)}
.content{padding:1.5rem;flex:1;background:var(--navy3)}

.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
.card-title{font-family:var(--font-head);font-size:.95rem;font-weight:700;display:flex;align-items:center;gap:.5rem;color:var(--navy)}
.card-title i{color:var(--gold)}

/* BILLING SPECIFIC */
.plan-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:2rem}
.plan-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;transition:all .3s;position:relative;overflow:hidden}
.plan-card.current{border-color:var(--gold);box-shadow:0 0 0 3px rgba(212,160,23,.12)}
.plan-card:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.plan-badge{display:inline-block;padding:.25rem .75rem;border-radius:50px;font-size:.7rem;font-weight:700;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.05em}
.pb-current{background:rgba(212,160,23,.12);color:var(--gold)}
.pb-popular{background:rgba(90,122,74,.12);color:var(--green)}
.plan-name{font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:var(--navy);margin-bottom:.3rem}
.plan-price{font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--gold);line-height:1.2;margin-bottom:.25rem}
.plan-period{font-size:.8rem;color:var(--muted)}
.plan-features{list-style:none;margin:1rem 0 1.5rem;display:flex;flex-direction:column;gap:.5rem}
.plan-features li{font-size:.85rem;color:var(--text);display:flex;align-items:center;gap:.5rem}
.plan-features li i{color:var(--green);font-size:.8rem}
.plan-features li.no i{color:var(--muted)}
.plan-features li.no{color:var(--muted)}
.btn-plan{width:100%;padding:.75rem;border-radius:8px;font-family:var(--font-head);font-size:.875rem;font-weight:700;cursor:pointer;transition:all .3s;border:none}
.btn-plan-primary{background:var(--gold);color:var(--navy3)}
.btn-plan-primary:hover{background:var(--blue)}
.btn-plan-outline{background:transparent;border:1.5px solid var(--border);color:var(--gold)}
.btn-plan-outline:hover{background:var(--navy3);border-color:var(--gold)}
.btn-plan-ghost{background:transparent;border:1.5px solid var(--border);color:var(--muted)}
.btn-plan-ghost:disabled{opacity:.5;cursor:not-allowed}

.invoice-table{width:100%;border-collapse:collapse}
.invoice-table th{text-align:left;padding:.75rem 1rem;font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid var(--border)}
.invoice-table td{padding:.85rem 1rem;font-size:.875rem;color:var(--text);border-bottom:1px solid var(--border)}
.invoice-table tr:last-child td{border:none}
.invoice-table tr:hover td{background:var(--navy3)}
.badge{display:inline-block;padding:.2rem .6rem;border-radius:50px;font-size:.7rem;font-weight:700}
.badge-paid{background:rgba(90,122,74,.12);color:var(--green)}
.badge-pending{background:rgba(212,133,58,.12);color:var(--gold)}
.badge-failed{background:rgba(192,74,42,.12);color:var(--red)}

.mpesa-section{background:var(--navy3);border:1.5px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem}
.mpesa-logo{font-family:var(--font-head);font-size:1.5rem;font-weight:800;color:var(--green)}
.usage-bar-wrap{background:var(--navy3);border-radius:50px;height:8px;overflow:hidden;margin:.5rem 0}
.usage-bar{height:100%;border-radius:50px;background:var(--gold);transition:width 1s ease}
.usage-bar.warn{background:var(--gold)}
.usage-bar.crit{background:var(--red)}

.sec-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.sec-hdr h1{font-family:var(--font-head);font-size:1.4rem;font-weight:800;color:var(--navy)}
.sec-hdr p{font-size:.85rem;color:var(--muted);margin-top:.15rem}
.btn-teal{padding:.65rem 1.25rem;background:var(--gold);border:none;border-radius:8px;color:var(--navy3);font-family:var(--font-head);font-size:.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.5rem;transition:.3s}
.btn-teal:hover{background:var(--blue)}
.btn-ghost-sm{padding:.5rem 1rem;background:transparent;border:1.5px solid var(--border);border-radius:8px;color:var(--gold);font-family:var(--font-head);font-size:.8rem;font-weight:700;cursor:pointer;transition:.3s}
.btn-ghost-sm:hover{background:var(--navy3);border-color:var(--gold)}

.menu-overlay{display:none;position:fixed;inset:0;background:rgba(10,31,68,.4);z-index:190}
.mobile-menu-btn{display:none;background:none;border:none;color:var(--navy);font-size:1.1rem;cursor:pointer}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0}
  .menu-overlay.show{display:block}
  .mobile-menu-btn{display:block}
  .plan-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem">
  <a href="dashboard.html" style="color:var(--muted);text-decoration:none;font-size:.875rem"><i class="fas fa-arrow-left"></i> Dashboard</a>
</div>

<h1>Billing & Subscription</h1>
<p class="page-sub">Manage your plan, pay with M-Pesa, and download invoices.</p>

<!-- CURRENT PLAN -->
<div class="card">
  <div class="card-title"><i class="fas fa-layer-group"></i> Current Plan</div>
  <div class="plan-banner">
    <div>
      <div class="plan-status">Active</div>
      <div class="plan-name">Professional Plan</div>
      <div style="font-size:.875rem;color:var(--muted);margin-bottom:1rem">KES 7,999/month · Renews December 31, 2025</div>
      <div class="plan-meta">
        <div class="pm-item">
          <div class="pm-val">5 <span style="font-size:.8rem;color:var(--muted)">/ 20</span></div>
          <div class="pm-lbl">Silos Used</div>
          <div class="usage-bar"><div class="usage-fill" style="width:25%"></div></div>
        </div>
        <div class="pm-item">
          <div class="pm-val">8 <span style="font-size:.8rem;color:var(--muted)">/ 50</span></div>
          <div class="pm-lbl">Users</div>
          <div class="usage-bar"><div class="usage-fill" style="width:16%"></div></div>
        </div>
        <div class="pm-item">
          <div class="pm-val">365</div>
          <div class="pm-lbl">Data Retention (days)</div>
        </div>
        <div class="pm-item">
          <div class="pm-val">47</div>
          <div class="pm-lbl">Days Remaining</div>
        </div>
      </div>
    </div>
    <div style="text-align:center">
      <div style="font-family:var(--font-head);font-size:3rem;font-weight:800;color:var(--gold)">47</div>
      <div style="font-size:.8rem;color:var(--muted)">days remaining</div>
      <div style="margin-top:1rem;width:80px;height:80px;border-radius:50%;background:rgba(212,160,23,.08);border:3px solid rgba(212,160,23,.3);display:grid;place-items:center;margin:1rem auto 0;position:relative">
        <svg viewBox="0 0 80 80" width="80" height="80" style="position:absolute;inset:0;transform:rotate(-90deg)">
          <circle fill="none" stroke="rgba(255,255,255,.06)" stroke-width="6" cx="40" cy="40" r="34"/>
          <circle fill="none" stroke="var(--gold)" stroke-width="6" stroke-linecap="round" cx="40" cy="40" r="34" stroke-dasharray="214" stroke-dashoffset="78"/>
        </svg>
        <span style="font-family:var(--font-head);font-size:.75rem;font-weight:700;color:var(--gold);position:relative">63%</span>
      </div>
    </div>
  </div>
</div>

<!-- PLAN SELECTION -->
<div class="card">
  <div class="card-title"><i class="fas fa-exchange-alt"></i> Change Plan</div>

  <div class="billing-toggle">
    <span style="color:var(--muted)">Monthly</span>
    <div class="toggle-track" id="billingToggle" onclick="toggleBilling()">
      <div class="toggle-thumb"></div>
    </div>
    <span>Yearly <span class="save-badge">Save ~17%</span></span>
  </div>

  <div class="plans-grid">
    <div class="plan-card" onclick="selectPlan(this,1,'Starter',2999,29999)">
      <div class="plan-card-name">Starter</div>
      <div class="plan-card-price" id="price1">KES 2,999 <small>/month</small></div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> 5 silos / 10 users</li>
        <li><i class="fas fa-check"></i> Basic monitoring</li>
        <li><i class="fas fa-check"></i> Alerts & tasks</li>
        <li><i class="fas fa-check"></i> 90-day data retention</li>
      </ul>
    </div>
    <div class="plan-card selected" id="planCard2" onclick="selectPlan(this,2,'Professional',7999,79999)">
      <div class="plan-badge">Current Plan</div>
      <div class="plan-card-name">Professional</div>
      <div class="plan-card-price" id="price2">KES 7,999 <small>/month</small></div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> 20 silos / 50 users</li>
        <li><i class="fas fa-check"></i> AI predictions</li>
        <li><i class="fas fa-check"></i> Excel analytics</li>
        <li><i class="fas fa-check"></i> Digital Twin</li>
        <li><i class="fas fa-check"></i> 365-day retention</li>
      </ul>
    </div>
    <div class="plan-card" onclick="selectPlan(this,3,'Enterprise',19999,199999)">
      <div class="plan-card-name">Enterprise</div>
      <div class="plan-card-price" id="price3">KES 19,999 <small>/month</small></div>
      <ul class="plan-features">
        <li><i class="fas fa-check"></i> Unlimited silos & users</li>
        <li><i class="fas fa-check"></i> White-label branding</li>
        <li><i class="fas fa-check"></i> Blockchain audit</li>
        <li><i class="fas fa-check"></i> AR maintenance</li>
        <li><i class="fas fa-check"></i> 3-year retention</li>
      </ul>
    </div>
  </div>

  <!-- MPESA PAYMENT -->
  <div class="mpesa-box" id="mpesaBox">
    <div class="mpesa-header">
      <div class="mpesa-logo">M-<br>PESA</div>
      <div>
        <div class="mpesa-title">Pay with M-Pesa</div>
        <div class="mpesa-desc">You'll receive an STK Push prompt on your phone</div>
      </div>
    </div>

    <div class="input-group">
      <label>M-Pesa Phone Number</label>
      <input type="tel" id="mpesaPhone" placeholder="+254 700 000 000" value="+254711111111">
    </div>

    <div id="paymentSummary">
      <div class="summary-row"><span>Plan</span><span id="sumPlan">Professional</span></div>
      <div class="summary-row"><span>Billing Period</span><span id="sumPeriod">Monthly</span></div>
      <div class="summary-row"><span>Amount</span><span id="sumAmount" style="color:var(--gold);font-weight:700">KES 7,999</span></div>
      <div class="summary-row" style="border-top:1px solid var(--border);margin-top:.5rem;padding-top:.75rem"><span>Total Due Today</span><span style="color:var(--gold)" id="sumTotal">KES 7,999</span></div>
    </div>

    <button class="btn-mpesa" id="payBtn" onclick="initiateMpesa()">
      <i class="fas fa-mobile-alt"></i> Pay with M-Pesa
    </button>
  </div>

  <!-- STK PUSH STATUS -->
  <div class="stk-status" id="stkStatus">
    <div class="stk-icon">📱</div>
    <div class="stk-title">STK Push Sent!</div>
    <div class="stk-sub">Check your phone <strong id="stkPhone"></strong> and enter your M-Pesa PIN</div>
    <div class="stk-timer" id="stkTimer">60</div>
    <div style="font-size:.8rem;color:var(--muted)">seconds to complete payment</div>
    <div class="progress-bar"><div class="progress-fill"></div></div>
    <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
      <button onclick="cancelPayment()" style="padding:.6rem 1.2rem;background:rgba(255,71,87,.08);border:1px solid rgba(255,71,87,.25);border-radius:8px;color:var(--red);cursor:pointer;font-size:.85rem">Cancel</button>
      <button onclick="checkPaymentStatus()" style="padding:.6rem 1.2rem;background:rgba(212,160,23,.08);border:1px solid rgba(212,160,23,.2);border-radius:8px;color:var(--gold);cursor:pointer;font-size:.85rem">Check Status</button>
    </div>
  </div>
</div>

<!-- PAYMENT HISTORY -->
<div class="card">
  <div class="card-title"><i class="fas fa-history"></i> Payment History</div>
  <table>
    <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Method</th><th>Status</th><th>Invoice</th></tr></thead>
    <tbody>
      <tr><td style="font-size:.8rem;color:var(--muted)">Nov 30, 2025</td><td>Professional Plan – Monthly</td><td style="font-weight:600">KES 7,999</td><td><span style="color:#00b946;font-size:.8rem"><i class="fas fa-mobile-alt"></i> M-Pesa</span></td><td><span class="status-pill sp-paid">Paid</span></td><td><button class="download-btn" onclick="downloadInvoice('INV-20251130')"><i class="fas fa-download"></i> INV-1130</button></td></tr>
      <tr><td style="font-size:.8rem;color:var(--muted)">Oct 31, 2025</td><td>Professional Plan – Monthly</td><td style="font-weight:600">KES 7,999</td><td><span style="color:#00b946;font-size:.8rem"><i class="fas fa-mobile-alt"></i> M-Pesa</span></td><td><span class="status-pill sp-paid">Paid</span></td><td><button class="download-btn" onclick="downloadInvoice('INV-20251031')"><i class="fas fa-download"></i> INV-1031</button></td></tr>
      <tr><td style="font-size:.8rem;color:var(--muted)">Sep 30, 2025</td><td>Professional Plan – Monthly</td><td style="font-weight:600">KES 7,999</td><td><span style="color:#00b946;font-size:.8rem"><i class="fas fa-mobile-alt"></i> M-Pesa</span></td><td><span class="status-pill sp-paid">Paid</span></td><td><button class="download-btn" onclick="downloadInvoice('INV-20250930')"><i class="fas fa-download"></i> INV-0930</button></td></tr>
      <tr><td style="font-size:.8rem;color:var(--muted)">Aug 31, 2025</td><td>Upgrade: Starter → Professional</td><td style="font-weight:600">KES 5,000</td><td><span style="color:#00b946;font-size:.8rem"><i class="fas fa-mobile-alt"></i> M-Pesa</span></td><td><span class="status-pill sp-paid">Paid</span></td><td><button class="download-btn" onclick="downloadInvoice('INV-20250831')"><i class="fas fa-download"></i> INV-0831</button></td></tr>
      <tr><td style="font-size:.8rem;color:var(--muted)">Aug 01, 2025</td><td>Starter Plan – Monthly</td><td style="font-weight:600">KES 2,999</td><td><span style="color:#00b946;font-size:.8rem"><i class="fas fa-mobile-alt"></i> M-Pesa</span></td><td><span class="status-pill sp-failed">Failed</span></td><td><span style="color:var(--muted);font-size:.8rem">N/A</span></td></tr>
    </tbody>
  </table>
</div>

<script>
// STATE
let selectedPlan = {id:2, name:'Professional', monthly:7999, yearly:79999};
let billingYearly = false;
let timerInterval, checkInterval;
let checkoutId = null;

// BILLING TOGGLE
function toggleBilling() {
  billingYearly = !billingYearly;
  const t = document.getElementById('billingToggle');
  t.classList.toggle('on', billingYearly);
  updatePrices();
  updateSummary();
}

function updatePrices() {
  const plans = [
    {id:1, monthly:2999, yearly:29999},
    {id:2, monthly:7999, yearly:79999},
    {id:3, monthly:19999, yearly:199999}
  ];
  plans.forEach((p,i) => {
    const el = document.getElementById(`price${i+1}`);
    const price = billingYearly ? p.yearly : p.monthly;
    const period = billingYearly ? '/year' : '/month';
    el.innerHTML = `KES ${price.toLocaleString()} <small>${period}</small>`;
  });
}

// PLAN SELECTION
function selectPlan(card, id, name, monthly, yearly) {
  document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  selectedPlan = {id, name, monthly, yearly};
  updateSummary();
}

function updateSummary() {
  const price = billingYearly ? selectedPlan.yearly : selectedPlan.monthly;
  document.getElementById('sumPlan').textContent = selectedPlan.name;
  document.getElementById('sumPeriod').textContent = billingYearly ? 'Yearly' : 'Monthly';
  document.getElementById('sumAmount').textContent = `KES ${price.toLocaleString()}`;
  document.getElementById('sumTotal').textContent = `KES ${price.toLocaleString()}`;
}

// MPESA PAYMENT
async function initiateMpesa() {
  const phone = document.getElementById('mpesaPhone').value.trim();
  if (!phone) { toast('Enter your M-Pesa phone number.','error'); return; }

  const btn = document.getElementById('payBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Sending STK Push…';

  try {
    const resp = await fetch('/api/mpesa/initiate.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        phone,
        plan_id: selectedPlan.id,
        period: billingYearly ? 'yearly' : 'monthly'
      })
    });
    const data = await resp.json();

    if (data.success) {
      checkoutId = data.CheckoutRequestID;
      showStkStatus(phone, data.amount || (billingYearly ? selectedPlan.yearly : selectedPlan.monthly));
    } else {
      throw new Error(data.error || 'Payment failed');
    }
  } catch(err) {
    // Demo mode: simulate STK push
    checkoutId = 'demo_' + Date.now();
    showStkStatus(phone, billingYearly ? selectedPlan.yearly : selectedPlan.monthly);
    toast('Demo: STK Push simulated (use 123456 as PIN)', 'info');
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Pay with M-Pesa';
}

function showStkStatus(phone, amount) {
  document.getElementById('mpesaBox').style.display = 'none';
  const stk = document.getElementById('stkStatus');
  stk.style.display = 'block';
  document.getElementById('stkPhone').textContent = phone;

  // Countdown timer
  let secs = 60;
  document.getElementById('stkTimer').textContent = secs;
  clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    secs--;
    document.getElementById('stkTimer').textContent = secs;
    if (secs <= 0) {
      clearInterval(timerInterval);
      stkExpired();
    }
  }, 1000);

  // Poll for payment status
  clearInterval(checkInterval);
  checkInterval = setInterval(checkPaymentStatus, 5000);
}

async function checkPaymentStatus() {
  // In production: poll /api/mpesa/status.php with checkoutId
  // Demo: simulate success after 8 seconds
  const el = document.getElementById('stkTimer');
  if (el && parseInt(el.textContent) < 52) {
    clearInterval(checkInterval);
    clearInterval(timerInterval);
    paymentSuccess();
  }
}

function paymentSuccess() {
  const stk = document.getElementById('stkStatus');
  stk.innerHTML = `
    <div class="stk-icon">✅</div>
    <div class="stk-title" style="color:var(--gold)">Payment Confirmed!</div>
    <div class="stk-sub">Your ${selectedPlan.name} plan is now active.</div>
    <div style="margin-top:1.5rem">
      <button onclick="location.reload()" style="padding:.75rem 2rem;background:linear-gradient(135deg,var(--gold),var(--gold3));border:none;border-radius:10px;color:var(--navy);font-family:var(--font-head);font-weight:700;cursor:pointer">
        <i class="fas fa-check"></i> Continue to Dashboard
      </button>
    </div>`;
  toast('🎉 Payment successful! Subscription activated.');
}

function stkExpired() {
  document.getElementById('stkStatus').style.display = 'none';
  document.getElementById('mpesaBox').style.display = 'block';
  toast('Payment timed out. Please try again.', 'error');
}

function cancelPayment() {
  clearInterval(timerInterval);
  clearInterval(checkInterval);
  document.getElementById('stkStatus').style.display = 'none';
  document.getElementById('mpesaBox').style.display = 'block';
}

function downloadInvoice(inv) { toast(`Downloading ${inv}.pdf`); }

// TOAST
function toast(msg, type='success') {
  let tc = document.getElementById('tc');
  if (!tc) { tc = document.createElement('div'); tc.id = 'tc'; tc.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem'; document.body.appendChild(tc); }
  const colors = {success:'var(--gold)',error:'var(--red)',info:'#4a90e2'};
  const c = colors[type]||colors.success;
  const t = document.createElement('div');
  t.style.cssText = `background:var(--navy3);border:1px solid ${c}33;border-left:3px solid ${c};border-radius:10px;padding:.75rem 1rem;font-size:.85rem;min-width:260px;box-shadow:0 8px 30px rgba(0,0,0,.4)`;
  t.innerHTML = `<span style="color:${c}">${msg}</span>`;
  tc.prepend(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = '.3s'; setTimeout(() => t.remove(), 300); }, 4000);
}

// Init
updateSummary();
</script>
</body>
</html>

