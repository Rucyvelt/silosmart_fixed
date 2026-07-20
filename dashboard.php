<?php
require_once __DIR__ . '/includes/functions.php';
ss_session_start();
require_login('/login.php');
$user = ss_get_current_user();
$unread = $user ? get_unread_count($user['id']) : 0;
$user_initials = $user ? strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1)) : 'U';
$user_name = $user ? htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : 'User';
$org_name = $user ? htmlspecialchars($user['org_name'] ?? 'My Organisation') : 'My Organisation';
$org_initial = strtoupper(substr($org_name, 0, 2));
$plan_name = $user ? htmlspecialchars($user['plan_name'] ?? 'Professional') : 'Professional';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SiloSmart – Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
/* ═══════════════════════════════════════════════════════════
   SILOSMART DASHBOARD — PROFESSIONAL GOLD + ROYAL BLUE
   ═══════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lato:wght@300;400;600;700&display=swap');

:root{
  --gold:#D4A017;--gold2:#F0C040;--gold3:#B8860B;
  --navy:#0A1F44;--navy2:#1A3A6B;--navy3:#0D1B3E;--navy4:#071230;
  --blue:#2E5EAA;--blue2:#1E90FF;--blue3:#4A7EC7;
  --red:#DC2626;--red2:#FCA5A5;--green:#16A34A;--green2:#4ADE80;
  --orange:#EA580C;--orange2:#FB923C;
  --white:#FFFFFF;--muted:#8BA3CC;
  --card:rgba(13,27,62,0.96);--card2:rgba(10,21,52,0.99);
  --border:rgba(212,160,23,0.18);--border2:rgba(46,94,170,0.25);
  --shadow:0 4px 24px rgba(7,18,48,0.4);
  --shadow-gold:0 4px 24px rgba(212,160,23,0.2);
  --sidebar-w:240px;
  --font-head:'Playfair Display',Georgia,serif;
  --font-body:'Lato','Trebuchet MS',sans-serif;
  --radius:14px;--radius-sm:9px;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--navy3);color:var(--white);display:flex;min-height:100vh;overflow-x:hidden}

/* ─── SIDEBAR ───────────────────────────────────────────── */
.sidebar{
  width:var(--sidebar-w);min-width:var(--sidebar-w);
  background:linear-gradient(180deg,var(--navy4) 0%,#0e2550 60%,var(--navy2) 100%);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;height:100vh;
  z-index:300;overflow-y:auto;overflow-x:hidden;transition:transform .3s;
}
.sidebar-brand{
  padding:1.2rem 1rem;display:flex;align-items:center;gap:.65rem;
  font-family:var(--font-head);font-size:1.1rem;font-weight:800;
  border-bottom:1px solid var(--border);text-decoration:none;color:var(--white);flex-shrink:0;
}
.sidebar-brand-icon{
  width:36px;height:36px;min-width:36px;
  background:linear-gradient(135deg,var(--gold),var(--gold3));
  border-radius:9px;display:grid;place-items:center;
  font-size:.9rem;color:var(--navy);
  box-shadow:0 0 16px rgba(212,160,23,0.45);flex-shrink:0;
}
.sidebar-brand span{color:var(--gold2)}
.sidebar-org{
  padding:.7rem 1rem;display:flex;align-items:center;gap:.65rem;
  background:rgba(212,160,23,0.06);border-bottom:1px solid var(--border);
}
.org-avatar{
  width:32px;height:32px;min-width:32px;
  background:linear-gradient(135deg,var(--gold),var(--navy2));
  border-radius:8px;display:grid;place-items:center;
  font-family:var(--font-head);font-size:.72rem;font-weight:700;color:var(--navy);flex-shrink:0;
}
.org-name{font-size:.8rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--white)}
.org-plan{font-size:.67rem;color:var(--gold2);background:rgba(212,160,23,0.14);padding:.1rem .4rem;border-radius:4px;display:inline-block;margin-top:.1rem}
.sidebar-nav{flex:1;padding:.75rem 0;overflow-y:auto}
.nav-section-label{
  padding:.25rem 1rem;font-size:.6rem;letter-spacing:.12em;
  text-transform:uppercase;color:rgba(139,163,204,0.45);margin-top:.75rem;font-weight:600;
}
.nav-item{
  display:flex;align-items:center;gap:.6rem;
  padding:.6rem 1rem;color:rgba(255,255,255,0.52);
  text-decoration:none;font-size:.82rem;font-weight:500;
  transition:all .2s;position:relative;overflow:hidden;
}
.nav-item:hover{color:var(--white);background:rgba(212,160,23,0.07)}
.nav-item.active{color:var(--gold2);background:rgba(212,160,23,0.1)}
.nav-item.active::before{
  content:'';position:absolute;left:0;top:10%;bottom:10%;
  width:3px;background:linear-gradient(180deg,var(--gold2),var(--gold3));
  border-radius:0 3px 3px 0;
}
.nav-item i{width:16px;text-align:center;font-size:.82rem;flex-shrink:0}
.nav-badge{
  margin-left:auto;background:var(--red);color:#fff;
  font-size:.62rem;font-weight:700;padding:.1rem .38rem;
  border-radius:50px;flex-shrink:0;
}
.sidebar-footer{padding:.9rem 1rem;border-top:1px solid var(--border);flex-shrink:0}
.user-info{display:flex;align-items:center;gap:.6rem}
.user-avatar{
  width:34px;height:34px;min-width:34px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--navy2));
  display:grid;place-items:center;
  font-family:var(--font-head);font-size:.75rem;font-weight:700;color:var(--navy);
  border:2px solid rgba(212,160,23,0.3);
}
.user-name{font-size:.8rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.user-role{font-size:.66rem;color:var(--muted);text-transform:capitalize}
.logout-btn{margin-left:auto;color:var(--muted);background:none;border:none;cursor:pointer;font-size:.85rem;transition:color .2s;flex-shrink:0}
.logout-btn:hover{color:var(--red)}

/* ─── MAIN ──────────────────────────────────────────────── */
.main{
  margin-left:var(--sidebar-w);flex:1;
  display:flex;flex-direction:column;
  min-width:0;width:calc(100% - var(--sidebar-w));overflow-x:hidden;
}
.topbar{
  padding:.85rem 1.5rem;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  background:rgba(7,18,48,0.95);backdrop-filter:blur(20px);
  position:sticky;top:0;z-index:200;gap:1rem;
}
.page-title{font-family:var(--font-head);font-size:1.2rem;font-weight:800;letter-spacing:-.01em}
.topbar-right{display:flex;align-items:center;gap:.6rem;flex-shrink:0}
.search-wrap{position:relative;display:flex;align-items:center}
.search-wrap i{position:absolute;left:9px;color:var(--muted);font-size:.8rem;pointer-events:none}
.search-input{
  background:rgba(255,255,255,0.05);border:1px solid var(--border);
  border-radius:8px;padding:.45rem .8rem .45rem 2rem;
  color:var(--white);font-size:.82rem;width:180px;outline:none;transition:all .3s;
}
.search-input:focus{border-color:var(--gold);width:220px;background:rgba(212,160,23,0.05)}
.search-input::placeholder{color:var(--muted)}
.icon-btn{
  width:34px;height:34px;
  background:rgba(255,255,255,0.04);border:1px solid var(--border);
  border-radius:8px;display:grid;place-items:center;
  cursor:pointer;color:var(--muted);font-size:.85rem;
  transition:all .2s;position:relative;text-decoration:none;flex-shrink:0;
}
.icon-btn:hover{color:var(--gold2);border-color:rgba(212,160,23,0.4);background:rgba(212,160,23,0.06)}
.notif-dot{position:absolute;top:5px;right:5px;width:7px;height:7px;background:var(--red);border-radius:50%;border:2px solid var(--navy3)}

/* ─── CONTENT ───────────────────────────────────────────── */
.content{padding:1.5rem;flex:1;min-width:0}
.dash-section{display:none}.dash-section.active{display:block}

/* ─── GREETING BANNER ───────────────────────────────────── */
.greeting-banner{
  background:linear-gradient(135deg,rgba(13,27,62,.98) 0%,rgba(26,58,107,.9) 100%);
  border:1px solid var(--border);border-radius:var(--radius);
  padding:1.5rem 1.75rem;margin-bottom:1.25rem;
  display:flex;align-items:center;justify-content:space-between;
  position:relative;overflow:hidden;
}
.greeting-banner::before{
  content:'';position:absolute;right:-40px;top:-40px;
  width:200px;height:200px;border-radius:50%;
  background:radial-gradient(circle,rgba(212,160,23,0.1) 0%,transparent 70%);
}
.greeting-banner::after{
  content:'';position:absolute;left:50%;bottom:-60px;
  width:300px;height:120px;
  background:radial-gradient(ellipse,rgba(46,94,170,0.08) 0%,transparent 70%);
}
.gb-left h2{font-family:var(--font-head);font-size:1.4rem;font-weight:800;margin-bottom:.3rem}
.gb-left h2 span{color:var(--gold2)}
.gb-left p{font-size:.85rem;color:var(--muted);display:flex;align-items:center;gap:.4rem}
.gb-right{display:flex;gap:.75rem;position:relative;z-index:1}

/* ─── KPI CARDS ─────────────────────────────────────────── */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.25rem}
.kpi-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--radius);padding:1.25rem;
  display:flex;align-items:center;gap:1rem;
  position:relative;overflow:hidden;transition:all .3s;cursor:default;
}
.kpi-card::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--gold),var(--gold3));
  transform:scaleX(0);transition:.35s;transform-origin:left;
}
.kpi-card:hover{border-color:rgba(212,160,23,0.38);transform:translateY(-2px);box-shadow:var(--shadow-gold)}
.kpi-card:hover::after{transform:scaleX(1)}
.kpi-icon{
  width:52px;height:52px;min-width:52px;border-radius:13px;
  display:grid;place-items:center;font-size:1.2rem;flex-shrink:0;
}
.kpi-icon.gold{background:linear-gradient(135deg,rgba(212,160,23,.2),rgba(184,134,11,.1));color:var(--gold2)}
.kpi-icon.blue{background:linear-gradient(135deg,rgba(46,94,170,.2),rgba(30,144,255,.1));color:var(--blue2)}
.kpi-icon.red{background:linear-gradient(135deg,rgba(220,38,38,.2),rgba(252,165,165,.1));color:var(--red2)}
.kpi-icon.green{background:linear-gradient(135deg,rgba(22,163,74,.2),rgba(74,222,128,.1));color:var(--green2)}
.kpi-icon.teal{background:linear-gradient(135deg,rgba(46,94,170,.2),rgba(30,144,255,.1));color:var(--blue2)}
.kpi-label{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:600;margin-bottom:.2rem}
.kpi-value{font-family:var(--font-head);font-size:1.75rem;font-weight:800;line-height:1;color:var(--white)}
.kpi-change{font-size:.72rem;margin-top:.3rem;display:flex;align-items:center;gap:.25rem}
.kpi-change.up{color:var(--green2)}.kpi-change.down{color:var(--red2)}.kpi-change.neutral{color:var(--muted)}

/* ─── SECTION HEADERS ───────────────────────────────────── */
.section-h{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:1rem;
}
.section-h h2{
  font-family:var(--font-head);font-size:1rem;font-weight:700;
  display:flex;align-items:center;gap:.4rem;color:var(--white);
}
.card-action{
  background:none;border:none;color:var(--muted);
  font-size:.78rem;cursor:pointer;display:flex;align-items:center;gap:.3rem;
  transition:color .2s;padding:.3rem .6rem;border-radius:6px;
}
.card-action:hover{color:var(--gold2);background:rgba(212,160,23,0.07)}

/* ─── LAYOUT MIX ────────────────────────────────────────── */
.grid-mix{display:grid;grid-template-columns:1fr 340px;gap:1.25rem;margin-bottom:1.25rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}

/* ─── CARDS ─────────────────────────────────────────────── */
.card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--radius);padding:1.25rem;
  position:relative;overflow:hidden;min-width:0;
  transition:border-color .3s,box-shadow .3s;
}
.card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,var(--gold),var(--gold3),transparent);
  opacity:0;transition:.3s;
}
.card:hover{border-color:rgba(212,160,23,0.32);box-shadow:0 8px 32px rgba(7,18,48,0.3)}
.card:hover::before{opacity:1}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:.5rem}
.card-title{font-family:var(--font-head);font-size:.92rem;font-weight:700;display:flex;align-items:center;gap:.45rem;color:var(--white)}
.card-title i{color:var(--gold2)}

/* ─── SILO CARDS ────────────────────────────────────────── */
.silos-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.85rem}
.silo-card{
  background:var(--card2);border:1px solid var(--border);
  border-radius:var(--radius);padding:1rem .85rem;
  cursor:pointer;transition:all .3s;text-align:center;position:relative;overflow:hidden;
}
.silo-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--gold),var(--gold3));opacity:0;transition:.3s;
}
.silo-card:hover{transform:translateY(-3px);border-color:rgba(212,160,23,0.4);box-shadow:0 8px 28px rgba(7,18,48,0.4)}
.silo-card:hover::before{opacity:1}
.silo-card.warning{border-color:rgba(245,166,35,0.25);background:rgba(10,21,52,0.98)}
.silo-card.critical{border-color:rgba(220,38,38,0.3);background:rgba(10,21,52,0.98);animation:pulse-red 3s infinite}
.silo-card.maintenance{border-color:rgba(46,94,170,0.3)}
@keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:.5}}
@keyframes pulse-red{0%,100%{border-color:rgba(220,38,38,0.3)}50%{border-color:rgba(220,38,38,0.65)}}
.sc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.65rem}
.sc-code{font-size:.68rem;color:var(--muted);font-weight:700;letter-spacing:.04em;font-family:monospace}
.status-pill{font-size:.62rem;font-weight:700;padding:.18rem .5rem;border-radius:50px;letter-spacing:.03em}
.sp-active{background:rgba(22,163,74,0.15);color:#4ADE80}
.sp-warning{background:rgba(245,166,35,0.15);color:#FCD34D}
.sp-critical{background:rgba(220,38,38,0.15);color:#FCA5A5}
.sp-maintenance{background:rgba(46,94,170,0.15);color:#93C5FD}
.gauge-container{position:relative;width:80px;height:80px;margin:0 auto .65rem}
.gauge-container svg{transform:rotate(-90deg)}
.gauge-track{fill:none;stroke:rgba(255,255,255,0.07);stroke-width:5;stroke-linecap:round}
.gauge-prog{fill:none;stroke-width:5;stroke-linecap:round;transition:stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1)}
.gauge-pct{
  position:absolute;inset:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;
}
.gauge-pct .num{font-family:var(--font-head);font-size:.95rem;font-weight:800;line-height:1}
.gauge-pct .unit{font-size:.52rem;color:var(--muted);letter-spacing:.06em;font-weight:600;margin-top:.1rem}
.sc-name{font-family:var(--font-head);font-size:.88rem;font-weight:700;margin-bottom:.2rem;color:var(--white)}
.sc-commodity{font-size:.7rem;color:var(--muted);margin-bottom:.65rem}
.sc-sensors{display:flex;gap:.4rem;justify-content:center}
.sensor-mini{
  flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);
  border-radius:7px;padding:.3rem .2rem;text-align:center;min-width:0;
}
.sv{font-size:.75rem;font-weight:700;color:var(--white)}
.sl{font-size:.58rem;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-top:.1rem}

/* ─── ALERTS ────────────────────────────────────────────── */
.alert-item{
  display:flex;align-items:flex-start;gap:.75rem;
  padding:.75rem 0;border-bottom:1px solid rgba(212,160,23,0.07);
}
.alert-item:last-child{border-bottom:none}
.alert-severity{width:3px;min-width:3px;height:100%;border-radius:3px;margin-top:.2rem;min-height:36px}
.as-critical{background:var(--red)}
.as-warning{background:var(--gold)}
.as-info{background:var(--blue2)}
.alert-title{font-size:.835rem;font-weight:600;color:var(--white);margin-bottom:.2rem}
.alert-meta{font-size:.73rem;color:var(--muted)}
.alert-ack{
  background:rgba(255,255,255,0.05);border:1px solid var(--border);
  color:var(--muted);font-size:.72rem;padding:.25rem .55rem;
  border-radius:6px;cursor:pointer;transition:all .2s;white-space:nowrap;flex-shrink:0;
}
.alert-ack:hover{border-color:var(--gold);color:var(--gold2)}

/* ─── TASKS ─────────────────────────────────────────────── */
.task-item{
  display:flex;align-items:center;gap:.75rem;
  padding:.7rem 0;border-bottom:1px solid rgba(212,160,23,0.07);
}
.task-item:last-child{border-bottom:none}
.task-priority{width:3px;min-width:3px;height:36px;border-radius:3px;flex-shrink:0}
.tp-critical{background:var(--red)}.tp-high{background:var(--orange)}.tp-medium{background:var(--gold)}.tp-low{background:var(--muted)}
.task-title{font-size:.835rem;font-weight:600;color:var(--white);margin-bottom:.18rem}
.task-meta{font-size:.73rem;color:var(--muted)}
.task-status{font-size:.68rem;font-weight:700;padding:.18rem .5rem;border-radius:50px;white-space:nowrap;flex-shrink:0}
.ts-assigned{background:rgba(46,94,170,0.18);color:#93C5FD}
.ts-in_progress{background:rgba(212,160,23,0.15);color:var(--gold2)}
.ts-pending{background:rgba(139,163,204,0.12);color:var(--muted)}
.ts-completed{background:rgba(22,163,74,0.15);color:#4ADE80}

/* ─── CHARTS ────────────────────────────────────────────── */
.chart-wrap{position:relative;height:200px}
.chart-wrap canvas{max-height:200px}

/* ─── BUTTONS ───────────────────────────────────────────── */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.1rem;border-radius:var(--radius-sm);font-weight:600;font-size:.835rem;cursor:pointer;border:none;transition:all .25s;text-decoration:none;white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--gold),var(--gold3));color:var(--navy);font-weight:700}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(212,160,23,0.4)}
.btn-blue{background:linear-gradient(135deg,var(--blue),var(--navy2));color:var(--white);border:1px solid rgba(46,94,170,0.4)}
.btn-blue:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(46,94,170,0.3)}
.btn-ghost{background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--white)}
.btn-ghost:hover{border-color:var(--gold);color:var(--gold2);background:rgba(212,160,23,0.06)}
.btn-danger{background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);color:var(--red2)}

/* ─── BADGES ────────────────────────────────────────────── */
.badge{display:inline-block;padding:.18rem .55rem;border-radius:50px;font-size:.68rem;font-weight:700}
.badge-ok{background:rgba(22,163,74,0.15);color:#4ADE80}
.badge-gold{background:rgba(212,160,23,0.15);color:var(--gold2)}
.badge-warn{background:rgba(245,166,35,0.15);color:#FCD34D}
.badge-red{background:rgba(220,38,38,0.15);color:var(--red2)}
.badge-blue{background:rgba(46,94,170,0.2);color:#93C5FD}
.badge-muted{background:rgba(139,163,204,0.1);color:var(--muted)}

/* ─── TABLE ─────────────────────────────────────────────── */
table{width:100%;border-collapse:collapse}
thead th{padding:.65rem .9rem;text-align:left;font-size:.7rem;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);font-weight:700}
tbody td{padding:.78rem .9rem;border-bottom:1px solid rgba(212,160,23,0.06);font-size:.835rem;vertical-align:middle}
tbody tr:hover td{background:rgba(212,160,23,0.03)}

/* ─── FORM ──────────────────────────────────────────────── */
.form-group{margin-bottom:1.1rem}
.form-label{display:block;font-size:.78rem;font-weight:600;color:rgba(255,255,255,.68);margin-bottom:.4rem}
.form-control{width:100%;background:rgba(255,255,255,0.04);border:1.5px solid rgba(212,160,23,0.18);border-radius:8px;padding:.6rem .9rem;color:var(--white);font-size:.875rem;outline:none;transition:all .25s;font-family:var(--font-body)}
.form-control:focus{border-color:var(--gold);background:rgba(212,160,23,0.04);box-shadow:0 0 0 3px rgba(212,160,23,0.1)}
.form-control::placeholder{color:rgba(255,255,255,0.2)}
select.form-control option{background:var(--navy2)}

/* ─── MODAL ─────────────────────────────────────────────── */
.modal-overlay{position:fixed;inset:0;background:rgba(7,18,48,0.82);backdrop-filter:blur(8px);z-index:500;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:var(--navy2);border:1px solid var(--border);border-radius:18px;padding:1.75rem;width:100%;max-width:520px;position:relative;box-shadow:0 24px 64px rgba(7,18,48,0.7);max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:1.05rem;font-weight:700;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem}
.modal-title i{color:var(--gold2)}
.modal-close{position:absolute;top:.9rem;right:.9rem;background:none;border:none;color:var(--muted);cursor:pointer;font-size:1rem;transition:color .2s}
.modal-close:hover{color:var(--white)}

/* ─── EMPTY STATE ───────────────────────────────────────── */
.empty-state{text-align:center;padding:3rem 1rem;color:var(--muted)}
.empty-state i{font-size:2.2rem;margin-bottom:.75rem;opacity:.25;display:block;color:var(--gold3)}

/* ─── SCROLLBAR ─────────────────────────────────────────── */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:rgba(212,160,23,0.35);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--gold)}

/* ─── HAMBURGER ─────────────────────────────────────────── */
.hamburger{display:none;background:none;border:none;color:var(--white);font-size:1.1rem;cursor:pointer;margin-right:.5rem;padding:.25rem}

/* ─── RESPONSIVE ────────────────────────────────────────── */
@media(max-width:1280px){
  .silos-grid{grid-template-columns:repeat(3,1fr)}
  .kpi-row{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:1024px){
  :root{--sidebar-w:200px}
  .grid-mix{grid-template-columns:1fr}
  .silos-grid{grid-template-columns:repeat(4,1fr)}
}
@media(max-width:768px){
  :root{--sidebar-w:240px}
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0;width:100%}
  .kpi-row,.grid-2,.grid-3,.grid-4{grid-template-columns:1fr 1fr}
  .silos-grid{grid-template-columns:repeat(2,1fr)}
  .grid-mix{grid-template-columns:1fr}
  .hamburger{display:flex!important}
  .greeting-banner .gb-right{display:none}
}
@media(max-width:480px){
  .kpi-row{grid-template-columns:1fr}
  .silos-grid{grid-template-columns:1fr 1fr}
  .content{padding:.85rem}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon"><i class="fas fa-database"></i></div>
    Silo<span>Smart</span>
  </div>
  <div class="sidebar-org">
    <div class="org-avatar">AG</div>
    <div>
      <div class="org-name">AgriStore Kenya Ltd</div>
      <div class="org-plan">Professional</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item active" href="#" onclick="showSection('overview')" data-section="overview"><i class="fas fa-home"></i> Dashboard</a>
    <a class="nav-item" href="#" onclick="showSection('sitemap')" data-section="sitemap"><i class="fas fa-map-marked-alt"></i> Site Map</a>

    <div class="nav-section-label">Monitoring</div>
    <a class="nav-item" href="#" onclick="showSection('silos')" data-section="silos"><i class="fas fa-database"></i> Silos <span class="nav-badge warn">5</span></a>
    <a class="nav-item" href="#" onclick="showSection('sensors')" data-section="sensors"><i class="fas fa-satellite-dish"></i> Sensors</a>
    <a class="nav-item" href="#" onclick="showSection('alerts')" data-section="alerts"><i class="fas fa-bell"></i> Alerts <span class="nav-badge">3</span></a>

    <div class="nav-section-label">Operations</div>
    <a class="nav-item" href="#" onclick="showSection('tasks')" data-section="tasks"><i class="fas fa-tasks"></i> Tasks</a>
    <a class="nav-item" href="#" onclick="showSection('inventory')" data-section="inventory"><i class="fas fa-boxes"></i> Inventory</a>
    <a class="nav-item" href="#" onclick="showSection('maintenance')" data-section="maintenance"><i class="fas fa-tools"></i> Maintenance</a>

    <div class="nav-section-label">Analytics</div>
    <a class="nav-item" href="#" onclick="showSection('analytics')" data-section="analytics"><i class="fas fa-chart-line"></i> Analytics</a>
    <a class="nav-item" href="#" onclick="showSection('reports')" data-section="reports"><i class="fas fa-file-excel"></i> Reports</a>
    <a class="nav-item" href="#" onclick="showSection('predictions')" data-section="predictions"><i class="fas fa-brain"></i> AI Predictions</a>

    <div class="nav-section-label">Settings</div>
    <?php if (is_super_admin()): ?><a class="nav-item" href="/admin/"><i class="fas fa-shield-alt"></i> Admin Panel</a><?php endif; ?>
    <a class="nav-item" href="#" onclick="showSection('settings')" data-section="settings"><i class="fas fa-cog"></i> Settings</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= $user_initials ?></div>
      <div>
        <div class="user-name"><?= $user_name ?></div>
        <div class="user-role"><?= htmlspecialchars($user['role'] ?? 'operator') ?></div>
      </div>
      <button class="logout-btn" onclick="window.location.href='/logout.php'" title="Logout"><i class="fas fa-sign-out-alt"></i></button>
    </div>
  </div>
</aside>

<div class="menu-overlay" id="menuOverlay" onclick="closeSidebar()"></div>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:.75rem">
      <button class="mobile-menu-btn" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
      <span class="page-title" id="pageTitle">Dashboard</span>
    </div>
    <div class="topbar-right">
      <div class="search-wrap"><i class="fas fa-search"></i><input class="search-input" placeholder="Search silos, alerts…"></div>
      <a href="#" class="icon-btn"><i class="fas fa-bell"></i><?php if ($unread > 0): ?><span class="notif-dot"></span><?php endif; ?></a>
      <a href="#" class="icon-btn"><i class="fas fa-user-circle"></i></a>
    </div>
  </div>

  <!-- CONTENT SECTIONS -->
  <div class="content" id="dashContent">

    <!-- ═══════════════════════════════════════════════ -->
    <!-- OVERVIEW (default) -->
    <!-- ═══════════════════════════════════════════════ -->
    <div id="sec-overview" class="dash-section active">


    <!-- GREETING BANNER -->
    <div class="greeting-banner">
      <div class="gb-left">
        <h2>Good <?php
          $h=date('H');
          echo $h<12?'Morning':($h<17?'Afternoon':'Evening');
        ?>, <span><?= htmlspecialchars($user['first_name']??'there') ?> 👋</span></h2>
        <p>
          <i class="fas fa-clock" style="color:var(--gold2)"></i>
          <?= date('l, d F Y') ?> &nbsp;·&nbsp;
          <i class="fas fa-map-marker-alt" style="color:var(--blue2)"></i>
          <?= htmlspecialchars($user['org_name']??'SiloSmart') ?>
        </p>
      </div>
      <div class="gb-right">
        <a href="/silos.php" class="btn btn-blue"><i class="fas fa-database"></i> Manage Silos</a>
        <a href="/alerts.php" class="btn btn-primary"><i class="fas fa-bell"></i> View Alerts</a>
      </div>
    </div>

    <!-- KPI CARDS -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-database"></i></div>
        <div><div class="kpi-label">Active Silos</div><div class="kpi-value"><?= $silo_count ?? 4 ?><span style="font-size:1rem;color:var(--muted)"> silos</span></div><div class="kpi-change neutral">1 under maintenance</div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon gold"><i class="fas fa-exclamation-triangle"></i></div>
        <div><div class="kpi-label">Active Alerts</div><div class="kpi-value">3</div><div class="kpi-change down"><i class="fas fa-arrow-up"></i> 2 critical</div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-balance-scale"></i></div>
        <div><div class="kpi-label">Total Inventory</div><div class="kpi-value">1,156<span style="font-size:.9rem;color:var(--muted)">t</span></div><div class="kpi-change up"><i class="fas fa-arrow-up"></i> +2.3% this week</div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-tasks"></i></div>
        <div><div class="kpi-label">Open Tasks</div><div class="kpi-value">3</div><div class="kpi-change down">1 overdue</div></div>
      </div>
    </div>

    <!-- SILOS GRID + ALERTS -->
    <div class="grid-mix" style="margin-bottom:1.25rem">
      <div>
        <div class="section-h"><h2><i class="fas fa-database" style="color:var(--gold);margin-right:.4rem"></i>Silo Status</h2><button class="card-action" onclick="showSection('silos')" data-section="silos">View all <i class="fas fa-arrow-right"></i></button></div>
        <div class="silos-grid">
          <!-- Silo Alpha -->
          <div class="silo-card" onclick="openSiloDetail(1)">
            <div class="sc-top"><span class="sc-code">S-001</span><span class="status-pill sp-active">Normal</span></div>
            <div class="gauge-container">
              <svg viewBox="0 0 80 80" width="80" height="80">
                <circle class="gauge-track" cx="40" cy="40" r="32"/>
                <circle class="gauge-prog" cx="40" cy="40" r="32" stroke="var(--gold)" stroke-dasharray="201" stroke-dashoffset="65" id="sg1"/>
              </svg>
              <div class="gauge-pct"><span class="num" style="color:var(--gold)">67%</span><span class="unit">FILL</span></div>
            </div>
            <div class="sc-name">Silo Alpha</div>
            <div class="sc-commodity">Maize — 335t / 500t</div>
            <div class="sc-sensors">
              <div class="sensor-mini"><div class="sv">24.6°C</div><div class="sl">Temp</div></div>
              <div class="sensor-mini"><div class="sv">58%</div><div class="sl">Hum</div></div>
            </div>
          </div>
          <!-- Silo Beta -->
          <div class="silo-card warning" onclick="openSiloDetail(2)">
            <div class="sc-top"><span class="sc-code">S-002</span><span class="status-pill sp-warning">Low Fill</span></div>
            <div class="gauge-container">
              <svg viewBox="0 0 80 80" width="80" height="80">
                <circle class="gauge-track" cx="40" cy="40" r="32"/>
                <circle class="gauge-prog" cx="40" cy="40" r="32" stroke="var(--gold)" stroke-dasharray="201" stroke-dashoffset="109" id="sg2"/>
              </svg>
              <div class="gauge-pct"><span class="num" style="color:var(--gold)">46%</span><span class="unit">FILL</span></div>
            </div>
            <div class="sc-name">Silo Beta</div>
            <div class="sc-commodity">Wheat — 138t / 300t</div>
            <div class="sc-sensors">
              <div class="sensor-mini"><div class="sv">22.1°C</div><div class="sl">Temp</div></div>
              <div class="sensor-mini"><div class="sv">52%</div><div class="sl">Hum</div></div>
            </div>
          </div>
          <!-- Silo Gamma -->
          <div class="silo-card" onclick="openSiloDetail(3)">
            <div class="sc-top"><span class="sc-code">S-003</span><span class="status-pill sp-active">Normal</span></div>
            <div class="gauge-container">
              <svg viewBox="0 0 80 80" width="80" height="80">
                <circle class="gauge-track" cx="40" cy="40" r="32"/>
                <circle class="gauge-prog" cx="40" cy="40" r="32" stroke="var(--gold)" stroke-dasharray="201" stroke-dashoffset="42" id="sg3"/>
              </svg>
              <div class="gauge-pct"><span class="num" style="color:var(--gold)">79%</span><span class="unit">FILL</span></div>
            </div>
            <div class="sc-name">Silo Gamma</div>
            <div class="sc-commodity">Cement — 631t / 800t</div>
            <div class="sc-sensors">
              <div class="sensor-mini"><div class="sv">98kPa</div><div class="sl">Press</div></div>
              <div class="sensor-mini"><div class="sv">OK</div><div class="sl">Status</div></div>
            </div>
          </div>
          <!-- Silo Delta -->
          <div class="silo-card maintenance" onclick="openSiloDetail(4)">
            <div class="sc-top"><span class="sc-code">S-004</span><span class="status-pill sp-maintenance">Maintenance</span></div>
            <div class="gauge-container">
              <svg viewBox="0 0 80 80" width="80" height="80">
                <circle class="gauge-track" cx="40" cy="40" r="32"/>
                <circle class="gauge-prog" cx="40" cy="40" r="32" stroke="#4a90e2" stroke-dasharray="201" stroke-dashoffset="120" id="sg4"/>
              </svg>
              <div class="gauge-pct"><span class="num" style="color:#4a90e2">40%</span><span class="unit">FILL</span></div>
            </div>
            <div class="sc-name">Silo Delta</div>
            <div class="sc-commodity">Maize — 100t / 250t</div>
            <div class="sc-sensors">
              <div class="sensor-mini"><div class="sv">—</div><div class="sl">Offline</div></div>
              <div class="sensor-mini"><div class="sv">—</div><div class="sl">Offline</div></div>
            </div>
          </div>
          <!-- Silo Epsilon -->
          <div class="silo-card critical" onclick="openSiloDetail(5)">
            <div class="sc-top"><span class="sc-code">S-005</span><span class="status-pill sp-critical">Critical</span></div>
            <div class="gauge-container">
              <svg viewBox="0 0 80 80" width="80" height="80">
                <circle class="gauge-track" cx="40" cy="40" r="32"/>
                <circle class="gauge-prog" cx="40" cy="40" r="32" stroke="var(--red)" stroke-dasharray="201" stroke-dashoffset="76" id="sg5"/>
              </svg>
              <div class="gauge-pct"><span class="num" style="color:var(--red)">62%</span><span class="unit">FILL</span></div>
            </div>
            <div class="sc-name">Silo Epsilon</div>
            <div class="sc-commodity">Rice — 248t / 400t</div>
            <div class="sc-sensors">
              <div class="sensor-mini" style="border-color:rgba(255,71,87,.3)"><div class="sv" style="color:var(--red)">38.5°C</div><div class="sl">CRITICAL</div></div>
              <div class="sensor-mini" style="border-color:rgba(245,166,35,.3)"><div class="sv" style="color:var(--gold)">73.8%</div><div class="sl">HIGH HUM</div></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ALERTS COLUMN -->
      <div>
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-bell"></i> Active Alerts</span>
            <button class="card-action" onclick="showSection('alerts')" data-section="alerts">All alerts <i class="fas fa-arrow-right"></i></button>
          </div>
          <div id="alertsList">
            <div class="alert-item">
              <div class="alert-severity as-critical"></div>
              <div style="flex:1">
                <div class="alert-title">High Temperature – Silo Epsilon</div>
                <div class="alert-meta">38.5°C > 32°C • 2h ago</div>
              </div>
              <button class="alert-ack" onclick="ackAlert(this)">Ack</button>
            </div>
            <div class="alert-item">
              <div class="alert-severity as-warning"></div>
              <div style="flex:1">
                <div class="alert-title">High Humidity – Silo Epsilon</div>
                <div class="alert-meta">73.8% > 65% • 1h ago</div>
              </div>
              <button class="alert-ack" onclick="ackAlert(this)">Ack</button>
            </div>
            <div class="alert-item">
              <div class="alert-severity as-info"></div>
              <div style="flex:1">
                <div class="alert-title">AI: Unusual Fill Pattern – Silo Alpha</div>
                <div class="alert-meta">Anomaly detected • 3h ago</div>
              </div>
              <button class="alert-ack" onclick="ackAlert(this)">Ack</button>
            </div>
          </div>
        </div>

        <!-- TASKS -->
        <div class="card" style="margin-top:1.25rem">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-tasks"></i> Tasks</span>
            <button class="card-action" onclick="showSection('tasks')" data-section="tasks">All tasks</button>
          </div>
          <div class="task-item">
            <div class="task-priority tp-critical"></div>
            <div style="flex:1">
              <div class="task-title">Emergency Temp Check – Silo Epsilon</div>
              <div class="task-meta">Assigned to Grace Akinyi • Due in 2h</div>
            </div>
            <span class="task-status ts-assigned">Assigned</span>
          </div>
          <div class="task-item">
            <div class="task-priority tp-high"></div>
            <div style="flex:1">
              <div class="task-title">Monthly Maintenance – Silo Delta</div>
              <div class="task-meta">Grace Akinyi • Due in 3 days</div>
            </div>
            <span class="task-status ts-in_progress">In Progress</span>
          </div>
          <div class="task-item">
            <div class="task-priority tp-medium"></div>
            <div style="flex:1">
              <div class="task-title">Calibration – Level Sensor S-001</div>
              <div class="task-meta">Unassigned • Due in 7 days</div>
            </div>
            <span class="task-status ts-pending">Pending</span>
          </div>
        </div>
      </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="grid-2">
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-chart-area"></i> Fill Level Trends — 7 Days</span>
          <div style="display:flex;align-items:center;gap:.5rem">
            <select id="fillChartFilter" onchange="updateFillChart(this.value)" style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--white);font-size:.75rem;padding:.3rem .65rem;outline:none;cursor:pointer">
              <option value="all">All Silos</option>
              <option value="alpha">Silo Alpha</option>
              <option value="beta">Silo Beta</option>
              <option value="gamma">Silo Gamma</option>
              <option value="epsilon">Silo Epsilon</option>
            </select>
          </div>
        </div>
        <div class="chart-wrap" style="height:220px"><canvas id="fillChart"></canvas></div>
        <div style="display:flex;gap:1.25rem;margin-top:.85rem;padding-top:.75rem;border-top:1px solid rgba(212,160,23,.07)">
          <div style="font-size:.75rem;color:var(--muted)"><span style="display:inline-block;width:8px;height:8px;background:#D4A017;border-radius:50%;margin-right:.3rem"></span>Alpha 67%</div>
          <div style="font-size:.75rem;color:var(--muted)"><span style="display:inline-block;width:8px;height:8px;background:#F0C040;border-radius:50%;margin-right:.3rem"></span>Beta 46%</div>
          <div style="font-size:.75rem;color:var(--muted)"><span style="display:inline-block;width:8px;height:8px;background:#2E5EAA;border-radius:50%;margin-right:.3rem"></span>Gamma 79%</div>
          <div style="font-size:.75rem;color:var(--muted)"><span style="display:inline-block;width:8px;height:8px;background:#DC2626;border-radius:50%;margin-right:.3rem"></span>Epsilon 62%</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-thermometer-half"></i> Temperature Monitor — 24h</span>
          <span class="badge badge-red" style="font-size:.68rem;animation:pulse-badge 2s infinite">● LIVE</span>
        </div>
        <div class="chart-wrap" style="height:220px"><canvas id="tempChart"></canvas></div>
        <div style="display:flex;justify-content:space-between;margin-top:.85rem;padding-top:.75rem;border-top:1px solid rgba(212,160,23,.07)">
          <div style="font-size:.75rem"><span style="color:var(--red);font-weight:700">Epsilon</span> <span style="color:var(--muted)">38.5°C — </span><span class="badge badge-red" style="font-size:.62rem">CRITICAL</span></div>
          <div style="font-size:.75rem;color:var(--muted)">Normal range: 15–32°C</div>
        </div>
      </div>
    </div>
    </div>


    <!-- ═══════════════════════════════════════════ -->
    <!-- SILOS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-silos" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-database" style="color:var(--gold);margin-right:.5rem"></i>Silos</h1><p>Manage and monitor all silos in your fleet</p></div>
        <div style="display:flex;gap:.65rem">
          <button class="btn btn-ghost" onclick="downloadReport('inventory','csv')"><i class="fas fa-file-csv"></i> Export CSV</button>
          <button class="btn btn-primary" onclick="document.getElementById('addSiloModal').classList.add('open')"><i class="fas fa-plus"></i> Add Silo</button>
        </div>
      </div>
      <!-- Stats row -->
      <div class="kpi-row" style="margin-bottom:1.25rem">
        <div class="kpi-card"><div class="kpi-icon gold"><i class="fas fa-database"></i></div><div><div class="kpi-label">Total Silos</div><div class="kpi-value">5</div><div class="kpi-change neutral">2 sites</div></div></div>
        <div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-check-circle"></i></div><div><div class="kpi-label">Active</div><div class="kpi-value" style="color:var(--green2)">3</div><div class="kpi-change up"><i class="fas fa-circle" style="font-size:.5rem"></i> All normal</div></div></div>
        <div class="kpi-card"><div class="kpi-icon gold"><i class="fas fa-tools"></i></div><div><div class="kpi-label">Maintenance</div><div class="kpi-value" style="color:var(--gold2)">1</div><div class="kpi-change neutral">Silo Delta</div></div></div>
        <div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-label">Critical</div><div class="kpi-value" style="color:var(--red2)">1</div><div class="kpi-change down">Action needed</div></div></div>
      </div>
      <!-- Full silo table -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-list"></i> All Silos</span>
          <div style="display:flex;gap:.5rem;align-items:center">
            <input type="text" placeholder="Search silos…" style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;padding:.4rem .75rem;color:var(--white);font-size:.8rem;outline:none;width:160px" oninput="filterSilos(this.value)">
            <select style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--white);font-size:.8rem;padding:.4rem .6rem;outline:none" onchange="filterSiloStatus(this.value)">
              <option value="">All Status</option><option value="Normal">Normal</option><option value="Warning">Warning</option><option value="Critical">Critical</option><option value="Maintenance">Maintenance</option>
            </select>
          </div>
        </div>
        <div style="overflow-x:auto">
        <table id="silosTable">
          <thead><tr><th>Code</th><th>Name</th><th>Site</th><th>Commodity</th><th>Capacity</th><th>Fill Level</th><th>Temp</th><th>Humidity</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php
          $demo_silos = [
            ['S-001','Silo Alpha','Nairobi Central','Grain Maize','500t',67,24.6,58,'Normal'],
            ['S-002','Silo Beta','Nairobi Central','Grain Wheat','300t',46,22.1,52,'Low Fill'],
            ['S-003','Silo Gamma','Mombasa Hub','Cement','800t',79,28.0,35,'Normal'],
            ['S-004','Silo Delta','Mombasa Hub','Grain Maize','250t',40,null,null,'Maintenance'],
            ['S-005','Silo Epsilon','Nairobi Central','Grain Rice','400t',62,38.5,73.8,'Critical'],
          ];
          foreach($demo_silos as $s):
            $sc = match($s[8]){
              'Normal'=>'badge-ok','Low Fill'=>'badge-warn',
              'Critical'=>'badge-red','Maintenance'=>'badge-blue',
              default=>'badge-muted'
            };
            $fill = $s[5];
            $fill_col = $fill>80?'var(--red2)':($fill>60?'var(--gold2)':'var(--blue2)');
          ?>
          <tr>
            <td><code style="background:rgba(255,255,255,.06);padding:.15rem .45rem;border-radius:5px;font-size:.8rem;color:var(--muted)"><?=$s[0]?></code></td>
            <td><strong><?=$s[1]?></strong></td>
            <td><span style="font-size:.8rem;color:var(--muted)"><?=$s[2]?></span></td>
            <td><?=$s[3]?></td>
            <td><?=$s[4]?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem;min-width:120px">
                <div style="flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:3px">
                  <div style="width:<?=$fill?>%;height:100%;background:<?=$fill_col?>;border-radius:3px;transition:width 1s"></div>
                </div>
                <span style="font-size:.8rem;font-weight:600;min-width:32px"><?=$fill?>%</span>
              </div>
            </td>
            <td><?=$s[6]!==null?'<span style="color:'.($s[6]>35?'var(--red2)':($s[6]>30?'var(--gold2)':'var(--white)')).'">'.number_format($s[6],1).'°C</span>':'<span style="color:var(--muted)">—</span>'?></td>
            <td><?=$s[7]!==null?'<span style="color:'.($s[7]>70?'var(--red2)':($s[7]>60?'var(--gold2)':'var(--white)')).'">'.number_format($s[7],1).'%</span>':'<span style="color:var(--muted)">—</span>'?></td>
            <td><span class="badge <?=$sc?>"><?=$s[8]?></span></td>
            <td>
              <div style="display:flex;gap:.3rem">
                <button class="btn btn-ghost" style="padding:.3rem .6rem;font-size:.72rem" onclick="openSiloDetail(<?=array_search($s,$demo_silos)+1?>)" title="View"><i class="fas fa-eye"></i></button>
                <button class="btn btn-ghost" style="padding:.3rem .6rem;font-size:.72rem" onclick="showToast('Edit coming soon')" title="Edit"><i class="fas fa-edit"></i></button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- SENSORS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-sensors" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-satellite-dish" style="color:var(--gold);margin-right:.5rem"></i>Sensors</h1><p>Real-time readings across all silos</p></div>
        <button class="btn btn-primary" onclick="showToast('Refreshing sensor data…')"><i class="fas fa-sync"></i> Refresh</button>
      </div>
      <div class="kpi-row" style="margin-bottom:1.25rem">
        <div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-satellite-dish"></i></div><div><div class="kpi-label">Total Sensors</div><div class="kpi-value">18</div><div class="kpi-change neutral">Across 5 silos</div></div></div>
        <div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-wifi"></i></div><div><div class="kpi-label">Online</div><div class="kpi-value" style="color:var(--green2)">14</div><div class="kpi-change up">77.8% uptime</div></div></div>
        <div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-times-circle"></i></div><div><div class="kpi-label">Offline</div><div class="kpi-value" style="color:var(--red2)">4</div><div class="kpi-change down">Silo Delta</div></div></div>
        <div class="kpi-card"><div class="kpi-icon gold"><i class="fas fa-battery-quarter"></i></div><div><div class="kpi-label">Low Battery</div><div class="kpi-value" style="color:var(--gold2)">2</div><div class="kpi-change neutral">&lt;20% charge</div></div></div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fas fa-table"></i> Sensor Registry</span>
          <span class="badge badge-gold" style="animation:pulse-badge 2s infinite">● LIVE</span>
        </div>
        <div style="overflow-x:auto"><table>
          <thead><tr><th>Sensor ID</th><th>Type</th><th>Silo</th><th>Last Reading</th><th>Alert Range</th><th>Battery</th><th>Last Seen</th><th>Status</th></tr></thead>
          <tbody id="sensorTableBody">
          <?php
          $sensors_demo = [
            ['TEMP-001','Temperature','Silo Alpha','24.6°C','15–32°C',78,'2 min ago','Online'],
            ['HUM-001','Humidity','Silo Alpha','58.2%','30–65%',78,'2 min ago','Online'],
            ['TEMP-002','Temperature','Silo Beta','22.1°C','15–32°C',55,'3 min ago','Online'],
            ['HUM-002','Humidity','Silo Beta','51.6%','30–65%',55,'3 min ago','Online'],
            ['PRES-001','Pressure','Silo Gamma','98 kPa','80–110 kPa',92,'1 min ago','Online'],
            ['TEMP-003','Temperature','Silo Gamma','28.0°C','15–32°C',92,'1 min ago','Online'],
            ['TEMP-004','Temperature','Silo Delta','—','15–32°C',12,'2 hrs ago','Offline'],
            ['HUM-003','Humidity','Silo Delta','—','30–65%',12,'2 hrs ago','Offline'],
            ['TEMP-005','Temperature','Silo Epsilon','38.5°C','15–32°C',61,'1 min ago','⚠ Critical'],
            ['HUM-004','Humidity','Silo Epsilon','73.8%','30–65%',18,'1 min ago','⚠ Warning'],
            ['CO2-001','CO₂','Silo Epsilon','510 ppm','<600 ppm',61,'1 min ago','Online'],
            ['LVL-001','Level Radar','Silo Alpha','67%','—',95,'1 min ago','Online'],
          ];
          foreach($sensors_demo as $s):
            $offline = $s[7]==='Offline';
            $critical = str_contains($s[7],'Critical');
            $warning  = str_contains($s[7],'Warning');
            $bat = $s[5];
            $bat_col = $bat<20?'var(--red2)':($bat<50?'var(--gold2)':'var(--green2)');
            $row_style = $critical?'background:rgba(220,38,38,.03)':($warning?'background:rgba(212,160,23,.03)':'');
          ?>
          <tr style="<?=$row_style?>">
            <td><code style="font-size:.8rem;color:var(--muted)"><?=$s[0]?></code></td>
            <td><?=$s[1]?></td>
            <td><?=$s[2]?></td>
            <td><strong style="color:<?=$critical?'var(--red2)':($warning?'var(--gold2)':'var(--white)')?>"><?=$s[3]?></strong></td>
            <td style="font-size:.78rem;color:var(--muted)"><?=$s[4]?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.4rem">
                <div style="flex:1;height:4px;background:rgba(255,255,255,.07);border-radius:2px;min-width:40px">
                  <div style="width:<?=$bat?>%;height:100%;background:<?=$bat_col?>;border-radius:2px"></div>
                </div>
                <span style="font-size:.75rem;color:<?=$bat_col?>;min-width:28px"><?=$bat?>%</span>
              </div>
            </td>
            <td style="font-size:.78rem;color:var(--muted)"><?=$s[6]?></td>
            <td><span class="badge <?=$offline?'badge-muted':($critical?'badge-red':($warning?'badge-warn':'badge-ok'))?>"><?=$s[7]?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- ALERTS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-alerts" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-bell" style="color:var(--red2);margin-right:.5rem"></i>Alerts</h1><p>Active and historical alerts across your fleet</p></div>
        <div style="display:flex;gap:.65rem">
          <button class="btn btn-ghost" onclick="acknowledgeAll()"><i class="fas fa-check-double"></i> Ack All</button>
          <button class="btn btn-primary" onclick="showToast('Notifications configured','success')"><i class="fas fa-bell"></i> Configure</button>
        </div>
      </div>
      <div class="kpi-row" style="margin-bottom:1.25rem">
        <div class="kpi-card"><div class="kpi-icon red"><i class="fas fa-fire"></i></div><div><div class="kpi-label">Critical</div><div class="kpi-value" style="color:var(--red2)">1</div></div></div>
        <div class="kpi-card"><div class="kpi-icon gold"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-label">Warnings</div><div class="kpi-value" style="color:var(--gold2)">2</div></div></div>
        <div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-info-circle"></i></div><div><div class="kpi-label">Info</div><div class="kpi-value" style="color:var(--blue2)">1</div></div></div>
        <div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-check-circle"></i></div><div><div class="kpi-label">Resolved Today</div><div class="kpi-value" style="color:var(--green2)">5</div></div></div>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-bell"></i> Alert Feed</span>
          <div style="display:flex;gap:.5rem">
            <select id="alertSevFilter" onchange="filterAlertsByLevel()" style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--white);font-size:.78rem;padding:.35rem .6rem;outline:none">
              <option value="">All Severity</option><option value="critical">Critical</option><option value="warning">Warning</option><option value="info">Info</option>
            </select>
            <select id="alertStatusFilter" onchange="filterAlertsByLevel()" style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--white);font-size:.78rem;padding:.35rem .6rem;outline:none">
              <option value="">All Status</option><option value="active">Active</option><option value="acknowledged">Acknowledged</option>
            </select>
          </div>
        </div>
        <div id="fullAlertsList">
          <?php
          $alerts_demo = [
            ['critical','High Temperature','Silo Epsilon temp 38.5°C exceeds threshold of 32°C. Immediate action required.','Silo Epsilon','2 hours ago','active'],
            ['warning','High Humidity','Silo Epsilon humidity 73.8% exceeds threshold of 65%.','Silo Epsilon','1 hour ago','active'],
            ['warning','Low Fill Level','Silo Beta fill level 46% — approaching minimum threshold.','Silo Beta','3 hours ago','acknowledged'],
            ['info','AI Anomaly','Unusual fill rate pattern detected in Silo Alpha. Monitor closely.','Silo Alpha','3 hours ago','active'],
            ['critical','Sensor Offline','Temperature sensor TEMP-004 offline. Silo Delta monitoring degraded.','Silo Delta','2 hours ago','active'],
          ];
          foreach($alerts_demo as $al):
            $sev = $al[0];
            $sev_col = $sev==='critical'?'var(--red2)':($sev==='warning'?'var(--gold2)':'var(--blue2)');
            $sev_bg  = $sev==='critical'?'as-critical':($sev==='warning'?'as-warning':'as-info');
            $is_acked = $al[5]==='acknowledged';
          ?>
          <div class="alert-item-full" data-sev="<?=$sev?>" data-status="<?=$al[5]?>" style="display:flex;align-items:flex-start;gap:1rem;padding:1rem 0;border-bottom:1px solid rgba(212,160,23,.07);<?=$is_acked?'opacity:.55':''?>">
            <div class="alert-severity <?=$sev_bg?>" style="margin-top:.2rem;height:auto;min-height:50px"></div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;flex-wrap:wrap">
                <span style="font-weight:700;font-size:.9rem"><?=$al[1]?></span>
                <span class="badge <?=$sev==='critical'?'badge-red':($sev==='warning'?'badge-warn':'badge-blue')?>" style="font-size:.65rem"><?=strtoupper($sev)?></span>
                <?php if($is_acked): ?><span class="badge badge-muted" style="font-size:.65rem">ACKNOWLEDGED</span><?php endif; ?>
              </div>
              <div style="font-size:.85rem;color:rgba(255,255,255,.75);margin-bottom:.3rem;line-height:1.5"><?=$al[2]?></div>
              <div style="font-size:.75rem;color:var(--muted);display:flex;gap:.85rem;flex-wrap:wrap">
                <span><i class="fas fa-database" style="color:var(--gold3)"></i> <?=$al[3]?></span>
                <span><i class="fas fa-clock" style="color:var(--muted)"></i> <?=$al[4]?></span>
              </div>
            </div>
            <?php if(!$is_acked): ?>
            <div style="display:flex;flex-direction:column;gap:.4rem;flex-shrink:0">
              <button class="btn btn-ghost" style="padding:.3rem .7rem;font-size:.73rem" onclick="ackAlertFull(this)"><i class="fas fa-check"></i> Ack</button>
              <button class="btn btn-danger" style="padding:.3rem .7rem;font-size:.73rem" onclick="showToast('Alert escalated','error')"><i class="fas fa-arrow-up"></i></button>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- TASKS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-tasks" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-tasks" style="color:var(--gold);margin-right:.5rem"></i>Tasks</h1><p>Maintenance and operational work orders</p></div>
        <button class="btn btn-primary" onclick="document.getElementById('newTaskModal').classList.add('open')"><i class="fas fa-plus"></i> New Task</button>
      </div>
      <div class="kpi-row" style="margin-bottom:1.25rem">
        <?php foreach([['fa-tasks','Total','8','muted'],['fa-clock','Pending','3','gold'],['fa-spinner','In Progress','2','blue'],['fa-exclamation','Overdue','1','red']] as [$ic,$lb,$v,$col]): ?>
        <div class="kpi-card"><div class="kpi-icon <?=$col?>"><i class="fas <?=$ic?>"></i></div><div><div class="kpi-label"><?=$lb?></div><div class="kpi-value" style="color:var(--<?=$col=='muted'?'white':$col.'2'?>)"><?=$v?></div></div></div>
        <?php endforeach; ?>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-clipboard-list"></i> Work Orders</span>
          <select onchange="filterTasks(this.value)" style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--white);font-size:.78rem;padding:.35rem .6rem;outline:none">
            <option value="">All Tasks</option><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option>
          </select>
        </div>
        <?php
        $tasks_demo = [
          ['tp-critical','Emergency Temp Check – Silo Epsilon','Check temperature sensors and ventilation. Epsilon at 38.5°C.','Grace Akinyi','Due in 2h','in_progress'],
          ['tp-high','Monthly Maintenance – Silo Delta','Full inspection of level sensors and mechanical components.','Grace Akinyi','Due in 3 days','in_progress'],
          ['tp-medium','Calibrate Level Sensor S-001','Recalibrate fill level radar sensor in Silo Alpha.','Unassigned','Due in 7 days','pending'],
          ['tp-medium','Inspect Silo Beta Grain Quality','Visual and physical inspection of stored wheat.','James Mwangi','Due in 5 days','pending'],
          ['tp-low','Update Sensor Firmware','Push latest firmware to all temperature sensors.','Unassigned','Due in 2 weeks','pending'],
          ['tp-high','Fix CO₂ Sensor Epsilon','CO₂ readings spiking. Check sensor calibration.','Unassigned','Overdue by 1 day','pending'],
          ['tp-low','Generate Monthly Report','Export and send monthly inventory report to management.','James Mwangi','Due in 10 days','pending'],
          ['tp-medium','Silo Gamma Safety Audit','Annual safety inspection for cement storage.','Grace Akinyi','Due in 3 weeks','pending'],
        ];
        foreach($tasks_demo as $t):
          $status_label = ['pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed'][$t[5]]??$t[5];
          $status_cls = ['pending'=>'ts-pending','in_progress'=>'ts-in_progress','completed'=>'ts-completed'][$t[5]]??'ts-pending';
          $overdue = str_contains($t[4],'Overdue');
        ?>
        <div class="task-row-full" data-status="<?=$t[5]?>" style="display:flex;align-items:flex-start;gap:.85rem;padding:.9rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
          <div class="task-priority <?=$t[0]?>" style="height:auto;min-height:40px;margin-top:.2rem"></div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:.88rem;margin-bottom:.2rem;color:<?=$overdue?'var(--red2)':'var(--white)'?>"><?=$t[1]?></div>
            <div style="font-size:.8rem;color:rgba(255,255,255,.6);margin-bottom:.3rem;line-height:1.5"><?=$t[2]?></div>
            <div style="font-size:.75rem;color:var(--muted);display:flex;gap:.85rem;flex-wrap:wrap">
              <span><i class="fas fa-user" style="color:var(--gold3)"></i> <?=$t[3]?></span>
              <span style="color:<?=$overdue?'var(--red2)':'var(--muted)'?>"><i class="fas fa-calendar"></i> <?=$t[4]?></span>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0">
            <span class="task-status <?=$status_cls?>"><?=$status_label?></span>
            <button class="btn btn-ghost" style="padding:.3rem .6rem;font-size:.72rem" onclick="showToast('Task updated')"><i class="fas fa-edit"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- INVENTORY -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-inventory" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-boxes" style="color:var(--gold);margin-right:.5rem"></i>Inventory</h1><p>Current stock levels and commodity breakdown</p></div>
        <button class="btn btn-primary" onclick="showToast('Inventory report generated','success')"><i class="fas fa-file-excel"></i> Export Report</button>
      </div>
      <div class="kpi-row" style="margin-bottom:1.25rem">
        <div class="kpi-card"><div class="kpi-icon gold"><i class="fas fa-weight"></i></div><div><div class="kpi-label">Total Stock</div><div class="kpi-value">1,156<span style="font-size:1rem;color:var(--muted)">t</span></div><div class="kpi-change up"><i class="fas fa-arrow-up"></i> +2.3% this week</div></div></div>
        <div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-warehouse"></i></div><div><div class="kpi-label">Total Capacity</div><div class="kpi-value">2,250<span style="font-size:1rem;color:var(--muted)">t</span></div><div class="kpi-change neutral">51.4% utilised</div></div></div>
        <div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-seedling"></i></div><div><div class="kpi-label">Grain</div><div class="kpi-value">721<span style="font-size:1rem;color:var(--muted)">t</span></div><div class="kpi-change up">3 silos</div></div></div>
        <div class="kpi-card"><div class="kpi-icon gold"><i class="fas fa-industry"></i></div><div><div class="kpi-label">Cement</div><div class="kpi-value">631<span style="font-size:1rem;color:var(--muted)">t</span></div><div class="kpi-change neutral">1 silo</div></div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-chart-bar"></i> Stock by Silo</span></div>
          <div style="height:240px"><canvas id="inventoryChart"></canvas></div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-list"></i> Detailed Breakdown</span></div>
          <?php
          $inv = [
            ['Silo Alpha','Grain Maize',335,500,'#D4A017'],
            ['Silo Beta','Grain Wheat',138,300,'#F0C040'],
            ['Silo Gamma','Cement',631,800,'#2E5EAA'],
            ['Silo Delta','Grain Maize',100,250,'#8BA3CC'],
            ['Silo Epsilon','Grain Rice',248,400,'#DC2626'],
          ];
          foreach($inv as $item):
            $pct = round($item[2]/$item[3]*100);
            $col = $pct>80?'var(--red2)':($pct>60?'var(--gold2)':'var(--blue2)');
          ?>
          <div style="margin-bottom:.85rem">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;font-size:.82rem">
              <span style="font-weight:600"><?=$item[0]?></span>
              <span style="color:var(--muted)"><?=number_format($item[2])?>t / <?=number_format($item[3])?>t <strong style="color:<?=$col?>">(<?=$pct?>%)</strong></span>
            </div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:.3rem"><?=$item[1]?></div>
            <div style="height:7px;background:rgba(255,255,255,.07);border-radius:4px">
              <div style="width:<?=$pct?>%;height:100%;background:<?=$item[4]?>;border-radius:4px;transition:width 1s"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- MAINTENANCE -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-maintenance" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-tools" style="color:var(--gold);margin-right:.5rem"></i>Maintenance</h1><p>Scheduled and completed maintenance records</p></div>
        <button class="btn btn-primary" onclick="showToast('Schedule feature coming soon')"><i class="fas fa-calendar-plus"></i> Schedule</button>
      </div>
      <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-calendar-check"></i> Upcoming Maintenance</span></div>
          <?php
          $upcoming = [
            ['Monthly Inspection – Silo Beta','Grace Akinyi','Jun 12, 2026','Inspection','tp-medium'],
            ['Annual Safety Audit – Silo Gamma','External Team','Jun 20, 2026','Safety Check','tp-high'],
            ['Sensor Calibration – All Silos','James Mwangi','Jun 25, 2026','Calibration','tp-low'],
          ];
          foreach($upcoming as $m): ?>
          <div style="display:flex;align-items:center;gap:.75rem;padding:.8rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
            <div class="task-priority <?=$m[4]?>" style="width:3px;height:42px;flex-shrink:0"></div>
            <div style="flex:1">
              <div style="font-weight:600;font-size:.85rem;margin-bottom:.18rem"><?=$m[0]?></div>
              <div style="font-size:.75rem;color:var(--muted)"><i class="fas fa-user" style="color:var(--gold3)"></i> <?=$m[1]?> &nbsp;·&nbsp; <i class="fas fa-calendar"></i> <?=$m[2]?></div>
            </div>
            <span class="badge badge-blue" style="font-size:.65rem"><?=$m[3]?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-history"></i> Recent Completions</span></div>
          <?php
          $done = [
            ['Emergency Repair – Silo Delta','Grace Akinyi','Jun 1, 2026','Emergency','Fixed sensor array'],
            ['Monthly Inspection – Silo Alpha','James Mwangi','May 28, 2026','Inspection','All clear'],
            ['Level Sensor Swap – S-001','Grace Akinyi','May 22, 2026','Repair','New sensor installed'],
          ];
          foreach($done as $m): ?>
          <div style="display:flex;align-items:center;gap:.75rem;padding:.8rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
            <div style="width:28px;height:28px;border-radius:50%;background:rgba(22,163,74,.15);display:grid;place-items:center;flex-shrink:0"><i class="fas fa-check" style="color:var(--green2);font-size:.75rem"></i></div>
            <div style="flex:1">
              <div style="font-weight:600;font-size:.85rem;margin-bottom:.18rem"><?=$m[0]?></div>
              <div style="font-size:.75rem;color:var(--muted)"><?=$m[1]?> &nbsp;·&nbsp; <?=$m[2]?> &nbsp;·&nbsp; <em><?=$m[4]?></em></div>
            </div>
            <span class="badge badge-ok" style="font-size:.65rem">Done</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Maintenance calendar visual -->
      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fas fa-calendar-alt"></i> June 2026 Schedule</span></div>
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.4rem;text-align:center">
          <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
          <div style="font-size:.7rem;font-weight:700;color:var(--muted);padding:.3rem"><?=$d?></div>
          <?php endforeach; ?>
          <?php for($d=1;$d<=30;$d++):
            $has_task = in_array($d,[1,12,20,22,25,28]);
            $is_today = $d==9;
            $style = $is_today
              ? 'background:linear-gradient(135deg,var(--gold),var(--gold3));color:var(--navy);font-weight:800'
              : ($has_task ? 'background:rgba(46,94,170,.2);color:var(--blue2);font-weight:600' : 'color:var(--muted)');
            if($d==1) echo str_repeat('<div></div>',0); // June 2026 starts on Monday
          ?>
          <div style="padding:.45rem;border-radius:7px;font-size:.82rem;<?=$style?>;cursor:<?=$has_task?'pointer':'default'?>" <?=$has_task?'onclick="showToast(\'Maintenance on Jun '.$d.'\')"':''?>>
            <?=$d?><?=$has_task?'<div style="width:5px;height:5px;background:var(--blue2);border-radius:50%;margin:1px auto 0"></div>':''?>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- ANALYTICS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-analytics" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-chart-line" style="color:var(--gold);margin-right:.5rem"></i>Analytics</h1><p>Trends, patterns and performance insights</p></div>
        <div style="display:flex;gap:.5rem">
          <select style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--white);font-size:.8rem;padding:.4rem .7rem;outline:none">
            <option>Last 30 Days</option><option>Last 7 Days</option><option>Last 90 Days</option><option>This Year</option>
          </select>
        </div>
      </div>
      <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-chart-area"></i> Fill Level Trends — 30 Days</span></div>
          <div style="height:240px"><canvas id="analyticsChart"></canvas></div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-thermometer-half"></i> Temperature Trends — 7 Days</span></div>
          <div style="height:240px"><canvas id="analyticsTempChart"></canvas></div>
        </div>
      </div>
      <div class="grid-3">
        <div class="card" style="text-align:center;padding:1.5rem">
          <div style="width:56px;height:56px;background:rgba(212,160,23,.12);border-radius:14px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.3rem;color:var(--gold2)"><i class="fas fa-arrow-trend-up"></i></div>
          <div style="font-family:var(--font-head);font-size:1.6rem;font-weight:800;color:var(--gold2)">+2.3%</div>
          <div style="font-size:.8rem;color:var(--muted);margin-top:.25rem">Average weekly stock increase</div>
        </div>
        <div class="card" style="text-align:center;padding:1.5rem">
          <div style="width:56px;height:56px;background:rgba(46,94,170,.12);border-radius:14px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.3rem;color:var(--blue2)"><i class="fas fa-clock"></i></div>
          <div style="font-family:var(--font-head);font-size:1.6rem;font-weight:800;color:var(--blue2)">99.2%</div>
          <div style="font-size:.8rem;color:var(--muted);margin-top:.25rem">Sensor uptime this month</div>
        </div>
        <div class="card" style="text-align:center;padding:1.5rem">
          <div style="width:56px;height:56px;background:rgba(220,38,38,.12);border-radius:14px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.3rem;color:var(--red2)"><i class="fas fa-bell"></i></div>
          <div style="font-family:var(--font-head);font-size:1.6rem;font-weight:800;color:var(--red2)">14</div>
          <div style="font-size:.8rem;color:var(--muted);margin-top:.25rem">Total alerts this month</div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- REPORTS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-reports" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-file-excel" style="color:var(--gold);margin-right:.5rem"></i>Reports</h1><p>Generate and download operational reports</p></div>
      </div>
      <!-- Report date filter -->
      <div class="card" style="margin-bottom:1.25rem;padding:1rem 1.25rem">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
          <div style="font-family:var(--font-head);font-size:.9rem;font-weight:700;color:var(--white)"><i class="fas fa-filter" style="color:var(--gold2);margin-right:.4rem"></i>Report Options</div>
          <div style="display:flex;align-items:center;gap:.5rem">
            <label style="font-size:.8rem;color:var(--muted)">From:</label>
            <input type="date" id="rptFrom" class="form-control" style="width:150px;padding:.4rem .7rem;font-size:.82rem" value="<?= date('Y-m-01') ?>">
          </div>
          <div style="display:flex;align-items:center;gap:.5rem">
            <label style="font-size:.8rem;color:var(--muted)">To:</label>
            <input type="date" id="rptTo" class="form-control" style="width:150px;padding:.4rem .7rem;font-size:.82rem" value="<?= date('Y-m-d') ?>">
          </div>
          <div style="display:flex;align-items:center;gap:.5rem">
            <label style="font-size:.8rem;color:var(--muted)">Format:</label>
            <select id="rptFormat" class="form-control" style="width:110px;padding:.4rem .7rem;font-size:.82rem">
              <option value="csv">CSV (Excel)</option>
              <option value="html">Print / PDF</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid-3" style="margin-bottom:1.5rem">
        <?php foreach([
          ['fa-boxes','Inventory Report','Current stock levels across all silos with fill percentages and commodity breakdown.','inventory','#D4A017'],
          ['fa-satellite-dish','Sensor Report','Temperature, humidity & all sensor readings with status and battery levels.','sensor','#2E5EAA'],
          ['fa-bell','Alerts Report','Full alert history with severity, resolution times and silo breakdown.','alerts','#DC2626'],
          ['fa-tasks','Tasks Report','Work order completion rates, priorities and technician performance.','tasks','#16A34A'],
          ['fa-credit-card','Payment Report','Billing history, invoices and subscription details.','payments','#EA580C'],
          ['fa-shield-alt','Audit Log','Full forensic activity log with device and IP tracking.','audit','#8BA3CC'],
        ] as [$ic,$t,$d,$type,$color]): ?>
        <div class="card" style="text-align:center;padding:1.75rem 1.25rem;cursor:pointer;transition:all .3s"
             onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(212,160,23,0.4)'"
             onmouseout="this.style.transform='';this.style.borderColor=''"
             onclick="downloadReport('<?=$type?>')">
          <div style="width:60px;height:60px;background:<?=$color?>18;border:1px solid <?=$color?>30;border-radius:15px;display:grid;place-items:center;margin:0 auto 1rem;font-size:1.4rem;color:<?=$color?>">
            <i class="fas <?=$ic?>"></i>
          </div>
          <div style="font-family:var(--font-head);font-weight:700;font-size:.95rem;margin-bottom:.4rem;color:var(--white)"><?=$t?></div>
          <div style="font-size:.8rem;color:var(--muted);margin-bottom:1.25rem;line-height:1.6"><?=$d?></div>
          <div style="display:flex;gap:.5rem;justify-content:center">
            <button class="btn btn-primary" style="flex:1;justify-content:center;font-size:.8rem"
                    onclick="event.stopPropagation();downloadReport('<?=$type?>','csv')">
              <i class="fas fa-file-csv"></i> CSV
            </button>
            <button class="btn btn-blue" style="flex:1;justify-content:center;font-size:.8rem"
                    onclick="event.stopPropagation();downloadReport('<?=$type?>','html')">
              <i class="fas fa-print"></i> Print
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fas fa-history"></i> Recent Activity Log</span></div>
        <?php
        $logs = [
          ['login','rensonnjehia22@gmail.com logged in','auth','Chrome · Windows · 41.107.x.x','Today, 09:14'],
          ['alert','Critical: Silo Epsilon temp 38.5°C','system','Auto-generated','Today, 07:22'],
          ['task','Task created: Emergency Temp Check','tasks','James Mwangi','Today, 07:25'],
          ['update','Silo Beta commodity updated','silos','James Mwangi','Yesterday, 16:40'],
          ['login','grace@agristore.co.ke logged in','auth','Safari · Android','Yesterday, 08:02'],
        ];
        foreach($logs as $l):
          $icons=['login'=>'fa-sign-in-alt','alert'=>'fa-bell','task'=>'fa-tasks','update'=>'fa-edit'];
          $cols=['login'=>'var(--blue2)','alert'=>'var(--red2)','task'=>'var(--gold2)','update'=>'var(--green2)'];
        ?>
        <div style="display:flex;align-items:center;gap:.85rem;padding:.7rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
          <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.05);display:grid;place-items:center;flex-shrink:0">
            <i class="fas <?=$icons[$l[0]]??'fa-circle'?>" style="font-size:.8rem;color:<?=$cols[$l[0]]??'var(--muted)'?>"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.85rem;font-weight:500"><?=$l[1]?></div>
            <div style="font-size:.75rem;color:var(--muted)"><?=$l[2]?> &nbsp;·&nbsp; <?=$l[3]?></div>
          </div>
          <div style="font-size:.75rem;color:var(--muted);white-space:nowrap"><?=$l[4]?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- AI PREDICTIONS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-predictions" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-brain" style="color:var(--gold);margin-right:.5rem"></i>AI Predictions</h1><p>48-hour advance anomaly detection and forecasting</p></div>
        <span class="badge badge-blue" style="font-size:.75rem;padding:.3rem .75rem">Beta</span>
      </div>
      <!-- Active predictions -->
      <div class="grid-3" style="margin-bottom:1.5rem">
        <div class="card" style="border-color:rgba(220,38,38,.3)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
            <span class="card-title" style="color:var(--red2)"><i class="fas fa-fire"></i> Spoilage Risk</span>
            <span class="badge badge-red">HIGH</span>
          </div>
          <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--red2);margin-bottom:.3rem">78%</div>
          <div style="font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:.75rem">Silo Epsilon at high risk. Temperature 38.5°C and rising humidity create spoilage conditions within <strong>18 hours</strong>.</div>
          <button class="btn btn-danger" style="width:100%;justify-content:center" onclick="showToast('Emergency task created','error')"><i class="fas fa-exclamation-triangle"></i> Take Action</button>
        </div>
        <div class="card" style="border-color:rgba(212,160,23,.3)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
            <span class="card-title"><i class="fas fa-calendar-check"></i> Harvest Window</span>
            <span class="badge badge-gold">OPTIMAL</span>
          </div>
          <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--gold2);margin-bottom:.3rem">Jun 14</div>
          <div style="font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:.75rem">Silo Alpha maize optimal for harvest. Fill level 67%, quality indicators stable. Market prices favourable this window.</div>
          <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="showToast('Harvest report generated','info')"><i class="fas fa-download"></i> View Report</button>
        </div>
        <div class="card" style="border-color:rgba(46,94,170,.3)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
            <span class="card-title" style="color:var(--blue2)"><i class="fas fa-tools"></i> Maintenance Due</span>
            <span class="badge badge-blue">PREDICTED</span>
          </div>
          <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--blue2);margin-bottom:.3rem">Jun 18</div>
          <div style="font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:.75rem">Sensor TEMP-002 degradation pattern detected. Recommend replacement before Jun 18 to avoid data gaps.</div>
          <button class="btn btn-blue" style="width:100%;justify-content:center" onclick="showToast('Maintenance task scheduled','info')"><i class="fas fa-calendar-plus"></i> Schedule Now</button>
        </div>
      </div>
      <!-- AI forecast chart -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-chart-line"></i> 48-Hour Temperature Forecast — Silo Epsilon</span>
          <span style="font-size:.78rem;color:var(--muted)">AI model confidence: <strong style="color:var(--gold2)">87%</strong></span>
        </div>
        <div style="height:220px"><canvas id="forecastChart"></canvas></div>
        <div style="margin-top:.85rem;padding:.75rem;background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.2);border-radius:9px;font-size:.82rem;color:rgba(255,255,255,.75)">
          <i class="fas fa-robot" style="color:var(--gold2)"></i> <strong>AI Recommendation:</strong> If temperature is not reduced within 6 hours, spoilage probability increases to 90%. Check ventilation system and reduce ambient temperature immediately.
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- SETTINGS -->
    <!-- ═══════════════════════════════════════════ -->
    <div id="sec-settings" class="dash-section">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-cog" style="color:var(--gold);margin-right:.5rem"></i>Settings</h1><p>Account, notification and organisation preferences</p></div>
        <button class="btn btn-primary" onclick="saveUserSettings()"><i class="fas fa-save"></i> Save Changes</button>
      </div>
      <div class="grid-2" style="align-items:start">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-user-circle"></i> Profile</span></div>
          <div style="text-align:center;margin-bottom:1.5rem">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--navy2));display:grid;place-items:center;font-family:var(--font-head);font-size:1.6rem;font-weight:800;color:var(--navy);margin:0 auto .85rem;border:3px solid rgba(212,160,23,.3)"><?= strtoupper(substr($user['first_name']??'U',0,1).substr($user['last_name']??'',0,1)) ?></div>
            <div style="font-family:var(--font-head);font-size:1rem;font-weight:700"><?= htmlspecialchars(trim(($user['first_name']??'').' '.($user['last_name']??''))) ?></div>
            <div style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($user['email']??'') ?></div>
          </div>
          <form onsubmit="saveUserSettings();return false">
            <div class="form-group"><label class="form-label">First Name</label><input class="form-control" value="<?= htmlspecialchars($user['first_name']??'') ?>" id="set-fn"></div>
            <div class="form-group"><label class="form-label">Last Name</label><input class="form-control" value="<?= htmlspecialchars($user['last_name']??'') ?>" id="set-ln"></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-control" value="<?= htmlspecialchars($user['email']??'') ?>" disabled style="opacity:.55"></div>
            <div class="form-group"><label class="form-label">Phone</label><input class="form-control" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+254700000000" id="set-phone"></div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fas fa-save"></i> Update Profile</button>
          </form>
        </div>
        <div>
          <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header"><span class="card-title"><i class="fas fa-bell"></i> Notifications</span></div>
            <?php foreach([
              ['Critical alerts via SMS','notif-sms',true],
              ['Critical alerts via Email','notif-email',true],
              ['Daily inventory summary','notif-daily',false],
              ['Task assignment alerts','notif-tasks',true],
              ['Maintenance reminders','notif-maint',true],
              ['AI prediction alerts','notif-ai',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem;color:rgba(255,255,255,.8)"><?=$label?></span>
              <label style="position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0">
                <input type="checkbox" <?=$checked?'checked':''?> style="opacity:0;width:0;height:0" id="<?=$id?>">
                <span onclick="this.previousElementSibling.click();this.style.background=this.previousElementSibling.checked?'linear-gradient(135deg,var(--gold),var(--gold3))':'rgba(255,255,255,0.1)'" style="position:absolute;cursor:pointer;inset:0;background:<?=$checked?'linear-gradient(135deg,var(--gold),var(--gold3))':'rgba(255,255,255,.1)'?>;border-radius:22px;transition:.3s">
                  <span style="position:absolute;height:14px;width:14px;left:<?=$checked?'22px':'4px'?>;bottom:4px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span>
                </span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-lock"></i> Security</span></div>
            <div class="form-group"><label class="form-label">Current Password</label><input class="form-control" type="password" placeholder="Enter current password"></div>
            <div class="form-group"><label class="form-label">New Password</label><input class="form-control" type="password" placeholder="Min 8 characters"></div>
            <div class="form-group"><label class="form-label">Confirm Password</label><input class="form-control" type="password" placeholder="Repeat new password"></div>
            <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="showToast('Password updated successfully','success')"><i class="fas fa-key"></i> Change Password</button>
          </div>
        </div>
      </div>
    </div>


<!-- SILO DETAIL MODAL -->
<div id="siloModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;display:none;align-items:center;justify-content:center;padding:1rem">
  <div style="background:var(--navy2);border:1px solid var(--border);border-radius:20px;padding:2rem;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;position:relative">
    <button onclick="closeSiloModal()" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,.06);border:none;border-radius:8px;color:var(--white);width:32px;height:32px;cursor:pointer;font-size:.9rem"><i class="fas fa-times"></i></button>
    <h3 id="modalTitle" style="font-family:var(--font-head);font-size:1.3rem;font-weight:800;margin-bottom:1.5rem"></h3>
    <div id="modalContent"></div>
  </div>
</div>

<script>
// ─── CHARTS ───────────────────────────────────────────────────
const labels7 = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
const FONT = "'Lato','Trebuchet MS',sans-serif";
const chartCfg = {
  responsive:true, maintainAspectRatio:false,
  animation:{duration:800,easing:'easeInOutQuart'},
  plugins:{
    legend:{display:false},
    tooltip:{
      backgroundColor:'rgba(10,31,68,.97)',
      borderColor:'rgba(212,160,23,.35)',borderWidth:1,
      titleFont:{family:FONT,weight:'700'},
      bodyFont:{family:FONT},
      padding:10,cornerRadius:10,
      callbacks:{label:ctx=>` ${ctx.dataset.label}: ${ctx.parsed.y}`}
    }
  },
  scales:{
    x:{
      grid:{color:'rgba(255,255,255,.03)',drawBorder:false},
      ticks:{color:'rgba(139,163,204,.7)',font:{size:11,family:FONT}},
      border:{display:false}
    },
    y:{
      grid:{color:'rgba(255,255,255,.03)',drawBorder:false},
      ticks:{color:'rgba(139,163,204,.7)',font:{size:11,family:FONT}},
      border:{display:false}
    }
  }
};

const fillDatasets = {
  all: [
    {label:'Alpha',data:[72,70,68,67,65,67,67],borderColor:'#D4A017',backgroundColor:'rgba(212,160,23,.08)',tension:.45,fill:true,pointRadius:4,pointBackgroundColor:'#D4A017',borderWidth:2.5},
    {label:'Beta', data:[55,52,50,48,47,46,46],borderColor:'#F0C040',backgroundColor:'rgba(240,192,64,.06)',tension:.45,fill:true,pointRadius:4,pointBackgroundColor:'#F0C040',borderWidth:2.5},
    {label:'Gamma',data:[74,75,77,78,79,79,79],borderColor:'#2E5EAA',backgroundColor:'rgba(46,94,170,.08)',tension:.45,fill:true,pointRadius:4,pointBackgroundColor:'#2E5EAA',borderWidth:2.5},
    {label:'Epsilon',data:[68,66,65,63,62,61,62],borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.06)',tension:.45,fill:true,pointRadius:4,pointBackgroundColor:'#DC2626',borderWidth:2.5},
  ],
  alpha:[{label:'Alpha',data:[72,70,68,67,65,67,67],borderColor:'#D4A017',backgroundColor:'rgba(212,160,23,.1)',tension:.45,fill:true,pointRadius:5,pointBackgroundColor:'#D4A017',borderWidth:2.5}],
  beta:[{label:'Beta',data:[55,52,50,48,47,46,46],borderColor:'#F0C040',backgroundColor:'rgba(240,192,64,.1)',tension:.45,fill:true,pointRadius:5,borderWidth:2.5}],
  gamma:[{label:'Gamma',data:[74,75,77,78,79,79,79],borderColor:'#2E5EAA',backgroundColor:'rgba(46,94,170,.1)',tension:.45,fill:true,pointRadius:5,borderWidth:2.5}],
  epsilon:[{label:'Epsilon',data:[68,66,65,63,62,61,62],borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.1)',tension:.45,fill:true,pointRadius:5,borderWidth:2.5}],
};

const fillChart = new Chart(document.getElementById('fillChart'),{
  type:'line',
  data:{labels:labels7,datasets:fillDatasets.all},
  options:{...chartCfg,scales:{...chartCfg.scales,y:{...chartCfg.scales.y,min:0,max:100,ticks:{...chartCfg.scales.y.ticks,callback:v=>v+'%'}}}}
});

function updateFillChart(val){
  fillChart.data.datasets = fillDatasets[val]||fillDatasets.all;
  fillChart.update('active');
}

new Chart(document.getElementById('fillChart'),{
  type:'line',
  data:{
    labels:labels7,
    datasets:[
      {label:'Alpha',data:[72,70,68,67,65,67,67],borderColor:'#D4A017',backgroundColor:'rgba(212,160,23,.05)',tension:.4,fill:true,pointRadius:3},
      {label:'Beta',data:[55,52,50,48,47,46,46],borderColor:'#f5a623',backgroundColor:'rgba(245,166,35,.05)',tension:.4,fill:true,pointRadius:3},
      {label:'Gamma',data:[74,75,77,78,79,79,79],borderColor:'#4a90e2',backgroundColor:'rgba(74,144,226,.05)',tension:.4,fill:true,pointRadius:3},
      {label:'Epsilon',data:[68,66,65,63,62,61,62],borderColor:'#ff4757',backgroundColor:'rgba(255,71,87,.05)',tension:.4,fill:true,pointRadius:3},
    ]
  },
  options:{...chartCfg,scales:{...chartCfg.scales,y:{...chartCfg.scales.y,min:0,max:100,ticks:{...chartCfg.scales.y.ticks,callback:v=>v+'%'}}}}
});

const hours24=[...Array(12)].map((_,i)=>`${(i*2).toString().padStart(2,'0')}:00`);
// Add critical threshold line
const tempPlugin = {
  id:'thresholdLine',
  beforeDraw(chart){
    const {ctx,chartArea:{left,right,top,bottom},scales:{y}}=chart;
    const yVal=32;
    const yPx=y.getPixelForValue(yVal);
    ctx.save();ctx.beginPath();
    ctx.setLineDash([6,4]);
    ctx.strokeStyle='rgba(220,38,38,0.4)';ctx.lineWidth=1.5;
    ctx.moveTo(left,yPx);ctx.lineTo(right,yPx);ctx.stroke();
    ctx.setLineDash([]);
    ctx.fillStyle='rgba(220,38,38,0.6)';ctx.font='10px Lato';
    ctx.fillText('⚠ 32°C threshold',left+4,yPx-4);
    ctx.restore();
  }
};
new Chart(document.getElementById('tempChart'),{
  type:'line',
  data:{
    labels:hours24,
    datasets:[
      {label:'Epsilon',data:[32,32.5,33,34,35,36,37,37.5,38,38.5,38.5,38.5],borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.07)',tension:.45,fill:true,borderWidth:2.5,pointRadius:2,pointBackgroundColor:'#DC2626'},
      {label:'Alpha', data:[23,23,23.5,24,24,24.5,24.5,24.6,24.6,24.6,24.6,24.6],borderColor:'#D4A017',backgroundColor:'rgba(212,160,23,.05)',tension:.45,fill:true,borderWidth:2,pointRadius:2},
      {label:'Beta',  data:[22,21.8,21.9,22,22,22.1,22.1,22.1,22,22.1,22.1,22.1],borderColor:'#2E5EAA',backgroundColor:'rgba(46,94,170,.04)',tension:.45,fill:true,borderWidth:2,pointRadius:2},
    ]
  },
  options:{...chartCfg,scales:{...chartCfg.scales,y:{...chartCfg.scales.y,min:18,ticks:{...chartCfg.scales.y.ticks,callback:v=>v+'°C'}}}},
  plugins:[tempPlugin]
});

// ─── SIDEBAR ───────────────────────────────────────────────────
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('menuOverlay').classList.add('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('menuOverlay').classList.remove('show')}


// ─── USER SITE MAP ────────────────────────────────────────────
function initUserMap(){
  const container=document.getElementById('userMapContainer');
  if(!container||container._mapInited)return;
  container._mapInited=true;
  if(typeof L==='undefined'){
    const s=document.createElement('script');s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    s.onload=()=>renderUserMap();document.head.appendChild(s);
  } else renderUserMap();
}
function renderUserMap(){
  const container=document.getElementById('userMapContainer');
  if(!container)return;
  const map=L.map(container,{scrollWheelZoom:false}).setView([-2.5,37.5],6);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CartoDB',maxZoom:18}).addTo(map);
  const mkIcon=(col,n)=>L.divIcon({className:'',html:`<div style="background:${col};color:#0a1628;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;border:2px solid rgba(255,255,255,.25);box-shadow:0 0 16px ${col}88">${n}</div>`,iconSize:[36,36]});
  [
    {latlng:[-1.2869,36.8219],label:'Nairobi Central Store',silos:3,devices:14,col:'#D4A017',alert:false},
    {latlng:[-4.0435,39.6682],label:'Mombasa Grain Hub',silos:2,devices:9,col:'#f5a623',alert:true},
  ].forEach(s=>{
    L.marker(s.latlng,{icon:mkIcon(s.col,s.silos)}).addTo(map)
      .bindPopup(`<div style="font-family:'Lato','Trebuchet MS',sans-serif;min-width:160px"><b>${s.label}</b><br><small style="color:#888">${s.silos} silos · ${s.devices} devices${s.alert?' · ⚠ Alert active':''}</small></div>`);
  });
}

// ─── LIVE SENSOR TICKER ───────────────────────────────────────
function startSensorTicker(){
  const sensorEls={
    '.num[style*="teal"]': ()=>(67+Math.random()*.4-.2).toFixed(1)+'%',
  };
  // Randomly nudge sensor readings every 5 seconds to simulate live data
  setInterval(()=>{
    // Silo overview cards sensor readings
    document.querySelectorAll('.sv').forEach(el=>{
      const txt=el.textContent;
      if(txt.includes('°C')){
        const base=parseFloat(txt);
        if(!isNaN(base)){const nv=(base+(Math.random()-.5)*.3).toFixed(1);el.textContent=nv+'°C';if(nv>35)el.style.color='var(--red)';else if(nv>28)el.style.color='var(--gold)';else el.style.color='';}
      } else if(txt.includes('%')&&!txt.includes('FILL')){
        const base=parseFloat(txt);
        if(!isNaN(base)){const nv=Math.min(99,Math.max(30,(base+(Math.random()-.5)*.5))).toFixed(1);el.textContent=nv+'%';}
      }
    });
    // Live ticker removed
  },5000);
}

window.addEventListener('load',()=>{startSensorTicker();});
// ─── SECTION SWITCHING ─────────────────────────────────────────
function showSection(s){
  const titles={overview:'Dashboard',sitemap:'Site Map',silos:'Silos',sensors:'Sensors',alerts:'Alerts',tasks:'Tasks',inventory:'Inventory',maintenance:'Maintenance',analytics:'Analytics',reports:'Reports',predictions:'AI Predictions',settings:'Settings'};
  // Hide all sections
  document.querySelectorAll('.dash-section').forEach(el=>el.classList.remove('active'));
  // Show target
  const target = document.getElementById('sec-'+s);
  if(target) target.classList.add('active');
  // Update page title
  document.getElementById('pageTitle').textContent = titles[s] || s;
  // Update active nav item
  document.querySelectorAll('.nav-item').forEach(el=>{
    el.classList.toggle('active', el.getAttribute('data-section')===s);
  });
  // Init charts lazily
  if(s==='analytics') initAnalyticsCharts();
  if(s==='sitemap') initUserMap();
  // Close mobile sidebar
  closeSidebar();
  // Scroll to top
  document.querySelector('.content').scrollTop=0;
}

let analyticsChartsInited=false;
function initAnalyticsCharts(){
  if(analyticsChartsInited)return;
  analyticsChartsInited=true;
  const days30=Array.from({length:30},(_,i)=>{const d=new Date();d.setDate(d.getDate()-29+i);return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'});});
  new Chart(document.getElementById('analyticsChart'),{type:'line',data:{labels:days30,datasets:[{label:'Alpha',data:days30.map((_,i)=>70-Math.sin(i/4)*5+Math.random()*2),borderColor:'#D4A017',tension:.4,pointRadius:0,fill:false},{label:'Epsilon',data:days30.map((_,i)=>60+i*0.1+Math.random()*3),borderColor:'#ff4757',tension:.4,pointRadius:0,fill:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'rgba(255,255,255,.55)',boxWidth:10}}},scales:{x:{ticks:{color:'rgba(255,255,255,.4)',maxTicksLimit:6},grid:{color:'rgba(255,255,255,.04)'}},y:{ticks:{color:'rgba(255,255,255,.4)'},grid:{color:'rgba(255,255,255,.04)'}}}}}); 
  new Chart(document.getElementById('analyticsTempChart'),{type:'line',data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],datasets:[{label:'Alpha',data:[24.2,24.4,24.6,24.3,24.6,24.5,24.6],borderColor:'#D4A017',tension:.4,pointRadius:3,fill:false},{label:'Epsilon',data:[33.1,34.5,35.8,36.4,37.1,37.9,38.5],borderColor:'#ff4757',tension:.4,pointRadius:3,fill:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'rgba(255,255,255,.55)',boxWidth:10}}},scales:{x:{ticks:{color:'rgba(255,255,255,.4)'},grid:{color:'rgba(255,255,255,.04)'}},y:{ticks:{color:'rgba(255,255,255,.4)',callback:v=>v+'°C'},grid:{color:'rgba(255,255,255,.04)'}}}}}); 
}

// ─── ALERT ACK ────────────────────────────────────────────────
function ackAlert(btn){
  const item=btn.closest('.alert-item');
  item.style.opacity='.4';
  btn.textContent='✓';
  btn.style.background='rgba(212,160,23,.2)';
  btn.disabled=true;
  showToast('Alert acknowledged');
}

// ─── SILO MODAL ───────────────────────────────────────────────
const siloData={
  1:{name:'Silo Alpha (S-001)',commodity:'Grain Maize',fill:67,temp:24.6,humidity:58,co2:420,status:'Normal',capacity:500,current:335},
  2:{name:'Silo Beta (S-002)',commodity:'Grain Wheat',fill:46,temp:22.1,humidity:52,co2:380,status:'Low Fill',capacity:300,current:138},
  3:{name:'Silo Gamma (S-003)',commodity:'Cement',fill:79,temp:28,humidity:35,co2:null,status:'Normal',capacity:800,current:631},
  4:{name:'Silo Delta (S-004)',commodity:'Grain Maize',fill:40,temp:null,humidity:null,co2:null,status:'Maintenance',capacity:250,current:100},
  5:{name:'Silo Epsilon (S-005)',commodity:'Grain Rice',fill:62,temp:38.5,humidity:73.8,co2:510,status:'Critical',capacity:400,current:248}
};

function openSiloDetail(id){
  const s=siloData[id];
  document.getElementById('modalTitle').textContent=s.name;
  const statusColor=s.status==='Normal'?'var(--gold)':s.status==='Critical'?'var(--red)':'var(--gold)';
  document.getElementById('modalContent').innerHTML=`
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem">
      <div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:1rem;text-align:center">
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem">Fill Level</div>
        <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:${statusColor}">${s.fill}%</div>
        <div style="font-size:.75rem;color:var(--muted)">${s.current}t / ${s.capacity}t</div>
      </div>
      <div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:1rem;text-align:center">
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem">Status</div>
        <div style="font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:${statusColor}">${s.status}</div>
        <div style="font-size:.75rem;color:var(--muted)">${s.commodity}</div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.5rem">
      ${s.temp!==null?`<div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:.75rem;text-align:center"><div style="font-size:.7rem;color:var(--muted)">Temperature</div><div style="font-family:var(--font-head);font-size:1.4rem;font-weight:700;color:${s.temp>35?'var(--red)':'var(--gold)'}">${s.temp}°C</div></div>`:''}
      ${s.humidity!==null?`<div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:.75rem;text-align:center"><div style="font-size:.7rem;color:var(--muted)">Humidity</div><div style="font-family:var(--font-head);font-size:1.4rem;font-weight:700;color:${s.humidity>65?'var(--gold)':'var(--gold)'}">${s.humidity}%</div></div>`:''}
      ${s.co2!==null?`<div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:.75rem;text-align:center"><div style="font-size:.7rem;color:var(--muted)">CO₂</div><div style="font-family:var(--font-head);font-size:1.4rem;font-weight:700;color:var(--gold)">${s.co2}ppm</div></div>`:''}
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
      <button class="card-action" style="padding:.6rem 1.2rem;background:rgba(212,160,23,.08);border:1px solid rgba(212,160,23,.2);border-radius:8px;color:var(--gold);cursor:pointer"><i class="fas fa-chart-line"></i> View History</button>
      <button class="card-action" style="padding:.6rem 1.2rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--white);cursor:pointer"><i class="fas fa-tools"></i> Add Task</button>
      <button class="card-action" style="padding:.6rem 1.2rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--white);cursor:pointer"><i class="fas fa-file-excel"></i> Export Report</button>
    </div>`;
  const modal=document.getElementById('siloModal');
  modal.style.display='flex';
}
function closeSiloModal(){document.getElementById('siloModal').style.display='none'}

// ─── TOAST ────────────────────────────────────────────────────
function showToast(msg,type='success'){
  let tc=document.getElementById('toastCont');
  if(!tc){tc=document.createElement('div');tc.id='toastCont';tc.style.cssText='position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';document.body.appendChild(tc);}
  const t=document.createElement('div');
  const col=type==='success'?'var(--gold)':type==='error'?'var(--red)':'var(--gold)';
  t.style.cssText=`background:var(--navy3);border:1px solid ${col}33;border-left:3px solid ${col};border-radius:10px;padding:.75rem 1rem;font-size:.85rem;min-width:260px;animation:slideIn .3s ease;box-shadow:0 8px 30px rgba(0,0,0,.4)`;
  t.innerHTML=`<span style="color:${col}">${msg}</span>`;
  tc.prepend(t);
  const style=document.createElement('style');style.textContent='@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}';document.head.appendChild(style);
  setTimeout(()=>{t.style.opacity='0';t.style.transform='translateX(10px)';t.style.transition='.3s';setTimeout(()=>t.remove(),300)},3500);
}

// ─── LIVE SENSOR SIMULATION ────────────────────────────────────
setInterval(()=>{
  // Animate silo Epsilon temp rising
  const n=document.querySelector('#sg5');
  if(n){const cur=parseFloat(n.getAttribute('stroke-dashoffset'));n.setAttribute('stroke-dashoffset',Math.max(cur-0.5,30));}
},2000);

// Auto alerts
// Live alerts will appear here when connected to real sensors


// ─── INVENTORY CHART ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded',function(){
  // Inventory bar chart
  const invEl = document.getElementById('inventoryChart');
  if(invEl){
    new Chart(invEl,{
      type:'bar',
      data:{
        labels:['Silo Alpha','Silo Beta','Silo Gamma','Silo Delta','Silo Epsilon'],
        datasets:[
          {label:'Current (t)',data:[335,138,631,100,248],backgroundColor:['rgba(212,160,23,.75)','rgba(240,192,64,.75)','rgba(46,94,170,.75)','rgba(139,163,204,.45)','rgba(220,38,38,.75)'],borderRadius:6,borderWidth:0},
          {label:'Capacity (t)',data:[500,300,800,250,400],backgroundColor:'rgba(255,255,255,.04)',borderRadius:6,borderWidth:1,borderColor:'rgba(255,255,255,.1)'},
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{labels:{color:'rgba(255,255,255,.6)',font:{size:11},boxWidth:10}},
          tooltip:{backgroundColor:'rgba(10,31,68,.97)',borderColor:'rgba(212,160,23,.3)',borderWidth:1,cornerRadius:9,padding:10}
        },
        scales:{
          x:{grid:{display:false},ticks:{color:'rgba(139,163,204,.7)',font:{size:10}},border:{display:false}},
          y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'rgba(139,163,204,.7)',font:{size:10},callback:v=>v+'t'},border:{display:false}}
        }
      }
    });
  }

  // Analytics 30-day chart
  const anlEl = document.getElementById('analyticsChart');
  if(anlEl){
    const days30=[...Array(30)].map((_,i)=>{const d=new Date();d.setDate(d.getDate()-29+i);return d.toLocaleDateString('en',{day:'numeric',month:'short'});});
    new Chart(anlEl,{
      type:'line',
      data:{labels:days30,datasets:[
        {label:'Alpha',data:[...Array(30)].map((_,i)=>65+Math.sin(i*.5)*4+Math.random()*2),borderColor:'#D4A017',backgroundColor:'rgba(212,160,23,.07)',tension:.45,fill:true,borderWidth:2,pointRadius:0},
        {label:'Gamma',data:[...Array(30)].map((_,i)=>74+Math.sin(i*.3)*5+Math.random()*2),borderColor:'#2E5EAA',backgroundColor:'rgba(46,94,170,.07)',tension:.45,fill:true,borderWidth:2,pointRadius:0},
        {label:'Epsilon',data:[...Array(30)].map((_,i)=>60+Math.sin(i*.4)*3+i*.05+Math.random()),borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.05)',tension:.45,fill:true,borderWidth:2,pointRadius:0},
      ]},
      options:{responsive:true,maintainAspectRatio:false,animation:{duration:600},
        plugins:{legend:{labels:{color:'rgba(255,255,255,.6)',font:{size:11},boxWidth:10}},
          tooltip:{backgroundColor:'rgba(10,31,68,.97)',borderColor:'rgba(212,160,23,.3)',borderWidth:1,cornerRadius:9,padding:8}
        },
        scales:{
          x:{grid:{display:false},ticks:{color:'rgba(139,163,204,.6)',font:{size:10},maxTicksLimit:8},border:{display:false}},
          y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'rgba(139,163,204,.7)',font:{size:10},callback:v=>v+'%'},border:{display:false}}
        }
      }
    });
  }

  // Analytics temp chart
  const atEl = document.getElementById('analyticsTempChart');
  if(atEl){
    const days7=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    new Chart(atEl,{
      type:'line',
      data:{labels:days7,datasets:[
        {label:'Epsilon',data:[33,34,35,36,37,38,38.5],borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.08)',tension:.4,fill:true,borderWidth:2.5,pointRadius:4,pointBackgroundColor:'#DC2626'},
        {label:'Alpha', data:[23,23.5,24,24.2,24.4,24.5,24.6],borderColor:'#D4A017',backgroundColor:'rgba(212,160,23,.05)',tension:.4,fill:true,borderWidth:2,pointRadius:3},
        {label:'Gamma', data:[27,27.5,28,28,28,28,28],borderColor:'#2E5EAA',backgroundColor:'rgba(46,94,170,.05)',tension:.4,fill:true,borderWidth:2,pointRadius:3},
      ]},
      options:{responsive:true,maintainAspectRatio:false,animation:{duration:600},
        plugins:{legend:{labels:{color:'rgba(255,255,255,.6)',font:{size:11},boxWidth:10}},
          tooltip:{backgroundColor:'rgba(10,31,68,.97)',borderColor:'rgba(212,160,23,.3)',borderWidth:1,cornerRadius:9,padding:8}
        },
        scales:{
          x:{grid:{display:false},ticks:{color:'rgba(139,163,204,.7)',font:{size:11}},border:{display:false}},
          y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'rgba(139,163,204,.7)',font:{size:11},callback:v=>v+'°C'},min:18,border:{display:false}}
        }
      }
    });
  }

  // AI Forecast chart
  const fcEl = document.getElementById('forecastChart');
  if(fcEl){
    const fcLabels=[...Array(24)].map((_,i)=>{const h=(new Date().getHours()+i)%24;return h+':00';});
    const actual=[38.5,38.7,38.9,39.1,39.4,38.8,38.5,38.2,37.9,37.5,37.2,36.8,...Array(12).fill(null)];
    const forecast=[...Array(12).fill(null),38.5,39.0,39.6,40.1,40.5,40.8,40.9,40.8,40.5,40.0,39.4,38.8];
    new Chart(fcEl,{
      type:'line',
      data:{labels:fcLabels,datasets:[
        {label:'Actual',data:actual,borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.08)',tension:.4,fill:true,borderWidth:2.5,pointRadius:3,spanGaps:false},
        {label:'AI Forecast',data:forecast,borderColor:'rgba(240,192,64,.85)',backgroundColor:'rgba(240,192,64,.06)',tension:.4,fill:true,borderWidth:2.5,borderDash:[6,3],pointRadius:3,spanGaps:false},
      ]},
      options:{responsive:true,maintainAspectRatio:false,animation:{duration:800},
        plugins:{legend:{labels:{color:'rgba(255,255,255,.6)',font:{size:11},boxWidth:10}},
          tooltip:{backgroundColor:'rgba(10,31,68,.97)',borderColor:'rgba(212,160,23,.3)',borderWidth:1,cornerRadius:9,padding:9}
        },
        scales:{
          x:{grid:{display:false},ticks:{color:'rgba(139,163,204,.6)',font:{size:10},maxTicksLimit:12},border:{display:false}},
          y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'rgba(139,163,204,.7)',font:{size:11},callback:v=>v+'°C'},min:35,border:{display:false}}
        }
      }
    });
  }
});

// ─── FILTER FUNCTIONS ───────────────────────────────────────────
function filterSilos(q){
  const rows = document.querySelectorAll('#silosTable tbody tr');
  rows.forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q.toLowerCase())?'':'none';});
}
function filterSiloStatus(st){
  const rows = document.querySelectorAll('#silosTable tbody tr');
  rows.forEach(r=>{r.style.display=(!st||r.textContent.includes(st))?'':'none';});
}
function filterAlertsByLevel(){
  const sv=document.getElementById('alertSevFilter')?.value||'';
  const st=document.getElementById('alertStatusFilter')?.value||'';
  document.querySelectorAll('.alert-item-full').forEach(r=>{
    const ms=!sv||r.dataset.sev===sv;
    const ms2=!st||r.dataset.status===st;
    r.style.display=ms&&ms2?'flex':'none';
  });
}
function filterTasks(st){
  document.querySelectorAll('.task-row-full').forEach(r=>{
    r.style.display=(!st||r.dataset.status===st)?'flex':'none';
  });
}
function ackAlertFull(btn){
  const row=btn.closest('.alert-item-full');
  row.dataset.status='acknowledged';
  row.style.opacity='.55';
  btn.closest('div').remove();
  showToast('Alert acknowledged','success');
}
function acknowledgeAll(){
  document.querySelectorAll('.alert-item-full[data-status="active"]').forEach(r=>{
    r.dataset.status='acknowledged';r.style.opacity='.55';
    const btns=r.querySelector('div:last-child');if(btns)btns.remove();
  });
  showToast('All alerts acknowledged','success');
}
function saveUserSettings(){
  showToast('Profile updated successfully','success');
}


// ─── MODAL SUBMIT FUNCTIONS ─────────────────────────────────────
async function submitNewSilo(){
  const name=document.getElementById('ns-name').value.trim();
  const code=document.getElementById('ns-code').value.trim();
  const errEl=document.getElementById('silo-modal-err');
  errEl.style.display='none';
  if(!name||!code){errEl.textContent='Name and Code are required.';errEl.style.display='block';return;}
  const btn=document.getElementById('addSiloBtn');
  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving…';
  try{
    const res=await fetch('/api/silos/create.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({name,code,site_name:document.getElementById('ns-site').value,commodity_type:document.getElementById('ns-comm').value,capacity_tonnes:document.getElementById('ns-cap').value,latitude:document.getElementById('ns-lat').value,longitude:document.getElementById('ns-lng').value})});
    const d=await res.json();
    if(d.success){document.getElementById('addSiloModal').classList.remove('open');showToast('Silo "'+name+'" added!','success');setTimeout(()=>location.reload(),1200);}
    else{errEl.textContent=d.error||'Failed to add silo.';errEl.style.display='block';btn.disabled=false;btn.innerHTML='<i class="fas fa-plus"></i> Add Silo';}
  }catch(e){errEl.textContent='Connection error — silo not saved.';errEl.style.display='block';btn.disabled=false;btn.innerHTML='<i class="fas fa-plus"></i> Add Silo';}
}
async function submitNewTask(){
  const title=document.getElementById('nt-title').value.trim();
  if(!title){showToast('Task title is required','error');return;}
  const btn=document.getElementById('addTaskBtn');
  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving…';
  try{
    const res=await fetch('/api/tasks/create.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({title,description:document.getElementById('nt-desc').value,type:document.getElementById('nt-type').value,priority:document.getElementById('nt-priority').value,due_date:document.getElementById('nt-due').value})});
    const d=await res.json();
    if(d.success){document.getElementById('newTaskModal').classList.remove('open');showToast('Task "'+title+'" created!','success');}
    else{showToast(d.error||'Failed','error');}
  }catch(e){showToast('Connection error','error');}
  finally{btn.disabled=false;btn.innerHTML='<i class="fas fa-plus"></i> Create Task';}
}


// ─── DOWNLOAD REPORT ──────────────────────────────────────────
function downloadReport(type, format) {
  format = format || document.getElementById('rptFormat')?.value || 'csv';
  const from = document.getElementById('rptFrom')?.value || '';
  const to   = document.getElementById('rptTo')?.value || '';

  if (format === 'csv') {
    // Direct download via hidden link
    const url = `/api/reports/generate.php?type=${type}&format=csv&from=${from}&to=${to}`;
    const a = document.createElement('a');
    a.href = url;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    showToast(`Downloading ${type} report as CSV…`, 'success');
  } else {
    // Open printable HTML in new tab
    const url = `/api/reports/generate.php?type=${type}&format=html&from=${from}&to=${to}`;
    window.open(url, '_blank');
  }
}

// ─── ADD SILO (improved with real-time table update) ──────────
async function submitNewSilo() {
  const name = document.getElementById('ns-name')?.value.trim();
  const code = document.getElementById('ns-code')?.value.trim();
  const errEl = document.getElementById('silo-modal-err');
  if (errEl) errEl.style.display = 'none';

  if (!name || !code) {
    if (errEl) { errEl.textContent = 'Silo name and code are required.'; errEl.style.display = 'block'; }
    return;
  }

  const btn = document.getElementById('addSiloBtn');
  const origHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

  try {
    const res = await fetch('/api/silos/create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        code,
        site_name:       document.getElementById('ns-site')?.value || '',
        commodity_type:  document.getElementById('ns-comm')?.value || 'grain_maize',
        capacity_tonnes: document.getElementById('ns-cap')?.value  || '',
        latitude:        document.getElementById('ns-lat')?.value  || '',
        longitude:       document.getElementById('ns-lng')?.value  || '',
      })
    });

    const data = await res.json();

    if (data.success) {
      // Close modal
      document.getElementById('addSiloModal').classList.remove('open');

      // Clear form
      ['ns-name','ns-code','ns-site','ns-cap','ns-lat','ns-lng'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });

      showToast(`✅ ${data.message || 'Silo added successfully!'}`, 'success');

      // Add new row to silos table immediately (no full reload needed)
      const tbody = document.querySelector('#silosTable tbody');
      if (tbody) {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td><code style="background:rgba(255,255,255,.06);padding:.15rem .45rem;border-radius:5px;font-size:.8rem;color:var(--muted)">${code}</code></td>
          <td><strong>${name}</strong></td>
          <td><span style="font-size:.8rem;color:var(--muted)">${document.getElementById('ns-site')?.value||'—'}</span></td>
          <td>${(document.getElementById('ns-comm')?.value||'').replace('grain_','Grain – ').replace('_',' ')}</td>
          <td>${document.getElementById('ns-cap')?.value||'—'} T</td>
          <td><div style="display:flex;align-items:center;gap:.6rem;min-width:120px"><div style="flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:3px"><div style="width:0%;height:100%;background:var(--blue2);border-radius:3px"></div></div><span style="font-size:.8rem;font-weight:600;min-width:32px">0%</span></div></td>
          <td><span style="color:var(--muted)">—</span></td>
          <td><span style="color:var(--muted)">—</span></td>
          <td><span class="badge badge-ok">Active</span></td>
          <td><div style="display:flex;gap:.3rem"><button class="btn btn-ghost" style="padding:.3rem .6rem;font-size:.72rem"><i class="fas fa-eye"></i></button><button class="btn btn-ghost" style="padding:.3rem .6rem;font-size:.72rem"><i class="fas fa-edit"></i></button></div></td>
        `;
        tbody.prepend(row);

        // Update total count in KPI
        const totalKpi = document.querySelector('#sec-silos .kpi-value');
        if (totalKpi) {
          const cur = parseInt(totalKpi.textContent) || 0;
          totalKpi.textContent = cur + 1;
        }
      }

    } else {
      if (errEl) {
        errEl.textContent = data.error || 'Failed to add silo.';
        errEl.style.display = 'block';
      } else {
        showToast(data.error || 'Failed to add silo.', 'error');
      }
    }

  } catch (e) {
    const msg = 'Connection error — please try again.';
    if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
    else showToast(msg, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = origHtml;
  }
}

// ─── SHOW TOAST (improved) ─────────────────────────────────────
function showToast(msg, type) {
  type = type || 'info';
  const existing = document.getElementById('dash-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.id = 'dash-toast';

  const styles = {
    success: { bg:'linear-gradient(135deg,rgba(13,27,62,.98),rgba(22,60,30,.95))', border:'rgba(22,163,74,.4)', color:'#4ADE80', icon:'fa-check-circle' },
    error:   { bg:'rgba(30,10,10,.97)', border:'rgba(220,38,38,.4)', color:'#FCA5A5', icon:'fa-times-circle' },
    info:    { bg:'linear-gradient(135deg,rgba(13,27,62,.98),rgba(26,58,107,.96))', border:'rgba(212,160,23,.4)', color:'#F0C040', icon:'fa-info-circle' },
  };
  const s = styles[type] || styles.info;

  toast.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.85rem 1.25rem;border-radius:12px;font-size:.875rem;font-weight:600;display:flex;align-items:center;gap:.6rem;border:1px solid ${s.border};color:${s.color};background:${s.bg};box-shadow:0 8px 30px rgba(10,31,68,.5);animation:toastIn .3s ease;max-width:380px;font-family:'Lato',sans-serif`;
  toast.innerHTML = `<i class="fas ${s.icon}" style="flex-shrink:0"></i><span>${msg}</span>`;

  if (!document.getElementById('toast-style')) {
    const st = document.createElement('style');
    st.id = 'toast-style';
    st.textContent = '@keyframes toastIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(st);
  }

  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity .4s'; setTimeout(()=>toast.remove(),400); }, 4500);
}

</script>


<!-- ADD SILO MODAL -->
<div class="modal-overlay" id="addSiloModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('addSiloModal').classList.remove('open')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-plus-circle"></i> Add New Silo</div>
    <div id="silo-modal-err" style="display:none;padding:.7rem;border-radius:8px;margin-bottom:1rem;background:rgba(220,38,38,.1);color:var(--red2);font-size:.85rem"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <div class="form-group"><label class="form-label">Silo Name *</label><input class="form-control" id="ns-name" placeholder="e.g. Silo Zeta"></div>
      <div class="form-group"><label class="form-label">Code *</label><input class="form-control" id="ns-code" placeholder="e.g. S-006"></div>
    </div>
    <div class="form-group"><label class="form-label">Site Location</label><input class="form-control" id="ns-site" placeholder="e.g. Kisumu Depot"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <div class="form-group"><label class="form-label">Commodity</label>
        <select class="form-control" id="ns-comm">
          <option value="grain_maize">Grain – Maize</option>
          <option value="grain_wheat">Grain – Wheat</option>
          <option value="grain_rice">Grain – Rice</option>
          <option value="cement">Cement</option>
          <option value="fly_ash">Fly Ash</option>
          <option value="chemicals">Chemicals</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Capacity (Tonnes)</label><input class="form-control" type="number" id="ns-cap" placeholder="500"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <div class="form-group"><label class="form-label">Latitude</label><input class="form-control" type="number" step="any" id="ns-lat" placeholder="-1.2921"></div>
      <div class="form-group"><label class="form-label">Longitude</label><input class="form-control" type="number" step="any" id="ns-lng" placeholder="36.8219"></div>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <button class="btn btn-ghost" onclick="document.getElementById('addSiloModal').classList.remove('open')">Cancel</button>
      <button class="btn btn-primary" id="addSiloBtn" onclick="submitNewSilo()"><i class="fas fa-plus"></i> Add Silo</button>
    </div>
  </div>
</div>

<!-- NEW TASK MODAL -->
<div class="modal-overlay" id="newTaskModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('newTaskModal').classList.remove('open')"><i class="fas fa-times"></i></button>
    <div class="modal-title"><i class="fas fa-tasks"></i> New Work Order</div>
    <div class="form-group"><label class="form-label">Title *</label><input class="form-control" id="nt-title" placeholder="e.g. Inspect Silo Alpha sensors"></div>
    <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="nt-desc" rows="2" placeholder="Details about this task…"></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <div class="form-group"><label class="form-label">Type</label>
        <select class="form-control" id="nt-type">
          <option value="inspection">Inspection</option>
          <option value="maintenance">Maintenance</option>
          <option value="calibration">Calibration</option>
          <option value="cleaning">Cleaning</option>
          <option value="emergency">Emergency</option>
          <option value="safety_check">Safety Check</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Priority</label>
        <select class="form-control" id="nt-priority">
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Due Date & Time</label><input class="form-control" type="datetime-local" id="nt-due"></div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <button class="btn btn-ghost" onclick="document.getElementById('newTaskModal').classList.remove('open')">Cancel</button>
      <button class="btn btn-primary" id="addTaskBtn" onclick="submitNewTask()"><i class="fas fa-plus"></i> Create Task</button>
    </div>
  </div>
</div>

</body>
</html>

