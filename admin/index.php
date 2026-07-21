<?php
require_once __DIR__ . '/includes/functions.php';
ss_session_start();
require_login('/login.php');
require_role(['super_admin', 'admin'], '/dashboard.php');
$user = ss_get_current_user();
$user_initials = strtoupper(substr($user['first_name'] ?? 'A', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$user_name = htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SiloSmart – Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,700&family=Lato:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
// Fallback: if Chart.js fails to load from cdnjs, try jsdelivr
window.addEventListener('error', function(e){
  if(e.target && e.target.src && e.target.src.includes('Chart.js')){
    var s=document.createElement('script');
    s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    document.head.appendChild(s);
  }
},true);
</script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" id="leaflet-css">
<style>
/* ═══════════════════════════════════════════════════════════
   SILOSMART ADMIN — GOLD + ROYAL BLUE THEME
   ═══════════════════════════════════════════════════════════ */
:root{
  --gold:#D4A017;--gold2:#F0C040;--gold3:#B8860B;--gold-bright:#FFD700;
  --navy:#0A1F44;--navy2:#1A3A6B;--navy3:#0D1B3E;--navy4:#071230;
  --blue:#2E5EAA;--blue2:#1E90FF;--blue3:#4A7EC7;
  --red:#DC2626;--green:#16A34A;--orange:#EA580C;
  --white:#FFFFFF;--cream:#F5E6B2;--muted:#8BA3CC;
  --card:rgba(13,27,62,0.94);--card2:rgba(10,21,52,0.98);
  --border:rgba(212,160,23,0.22);--border2:rgba(46,94,170,0.3);
  --shadow:0 4px 32px rgba(7,18,48,0.5);
  --sidebar-w:240px;
  --font-head:'Playfair Display',Georgia,serif;
  --font-body:'Lato','Trebuchet MS',sans-serif;
}

/* ── RESET ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-body);background:var(--navy3);color:var(--white);display:flex;min-height:100vh;overflow-x:hidden}

/* ── SIDEBAR ── */
.sidebar{
  width:var(--sidebar-w);min-width:var(--sidebar-w);
  background:linear-gradient(180deg,var(--navy4) 0%,var(--navy2) 100%);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;height:100vh;
  z-index:300;overflow-y:auto;overflow-x:hidden;
  transition:transform .3s;
}
.brand{
  padding:1.1rem 1rem;display:flex;align-items:center;gap:.6rem;
  font-family:var(--font-head);font-size:1.05rem;font-weight:800;
  border-bottom:1px solid var(--border);text-decoration:none;color:var(--white);
  flex-shrink:0;
}
.brand-ico{
  width:34px;height:34px;min-width:34px;
  background:linear-gradient(135deg,var(--gold),var(--gold3));
  border-radius:8px;display:grid;place-items:center;
  font-size:.85rem;color:var(--navy);
  box-shadow:0 0 14px rgba(212,160,23,0.4);flex-shrink:0;
}
.brand span{color:var(--gold2)}
.admin-badge{
  background:linear-gradient(135deg,var(--gold),var(--gold3));
  color:var(--navy);font-size:.55rem;font-weight:800;
  padding:.12rem .45rem;border-radius:4px;letter-spacing:.06em;
  text-transform:uppercase;margin-left:auto;flex-shrink:0;
}
.sidebar-nav{flex:1;padding:.75rem 0;overflow-y:auto}
.nav-section{
  padding:.2rem .85rem;font-size:.6rem;letter-spacing:.1em;
  text-transform:uppercase;color:rgba(139,163,204,0.5);margin-top:.6rem;
}
.nav-item{
  display:flex;align-items:center;gap:.6rem;
  padding:.6rem .85rem;color:rgba(255,255,255,0.55);
  text-decoration:none;font-size:.82rem;font-weight:500;
  transition:all .2s;position:relative;white-space:nowrap;overflow:hidden;
}
.nav-item:hover{color:var(--white);background:rgba(212,160,23,0.07)}
.nav-item.active{color:var(--gold2);background:rgba(212,160,23,0.1)}
.nav-item.active::before{
  content:'';position:absolute;left:0;top:20%;bottom:20%;
  width:3px;background:linear-gradient(180deg,var(--gold2),var(--gold3));
  border-radius:0 3px 3px 0;
}
.nav-item i{width:16px;text-align:center;font-size:.82rem;flex-shrink:0}

/* sb-item = same as nav-item (HTML uses sb-item class) */
.sb-item{
  display:flex;align-items:center;gap:.6rem;
  width:100%;padding:.6rem .85rem;
  background:none;border:none;cursor:pointer;
  color:rgba(255,255,255,0.55);font-size:.82rem;font-weight:500;
  font-family:var(--font-body);text-decoration:none;text-align:left;
  transition:all .2s;position:relative;white-space:nowrap;overflow:hidden;
}
.sb-item:hover{color:var(--white);background:rgba(212,160,23,0.07)}
.sb-item.active{color:var(--gold2) !important;background:rgba(212,160,23,0.1)}
.sb-item.active::before{
  content:'';position:absolute;left:0;top:20%;bottom:20%;
  width:3px;background:linear-gradient(180deg,var(--gold2),var(--gold3));
  border-radius:0 3px 3px 0;
}
.sb-item i{width:16px;text-align:center;font-size:.82rem;flex-shrink:0;color:inherit}
.sb-badge{margin-left:auto;background:var(--red);color:#fff;font-size:.6rem;font-weight:700;padding:.1rem .38rem;border-radius:50px;flex-shrink:0}
/* Section labels */
.sb-section{padding:.2rem .85rem;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(139,163,204,0.45);margin-top:.65rem;font-weight:600}
/* Brand */
.sb-brand{padding:1rem;display:flex;align-items:center;gap:.6rem;border-bottom:1px solid var(--border);flex-shrink:0}
.sb-logo{width:34px;height:34px;min-width:34px;background:linear-gradient(135deg,var(--gold),var(--gold3));border-radius:8px;display:grid;place-items:center;font-size:.9rem;color:var(--navy);box-shadow:0 0 14px rgba(212,160,23,0.4)}
.sb-name{font-family:var(--font-head);font-size:1rem;font-weight:800;color:var(--white)}
.sb-name span{color:var(--gold2)}
.sb-super{margin-left:auto;background:linear-gradient(135deg,var(--gold),var(--gold3));color:var(--navy);font-size:.52rem;font-weight:800;padding:.15rem .4rem;border-radius:4px;letter-spacing:.06em;text-transform:uppercase;flex-shrink:0}
/* Footer */
.sb-footer{padding:.85rem;border-top:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;gap:.6rem}
.sa-avatar{width:32px;height:32px;min-width:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--navy2));display:grid;place-items:center;font-family:var(--font-head);font-size:.72rem;font-weight:700;color:var(--navy);border:2px solid rgba(212,160,23,0.3)}
.sa-info{flex:1;min-width:0}
.sa-name{font-size:.78rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sa-role{font-size:.65rem;color:var(--muted)}
/* Nav wrapper */
.sb-nav{flex:1;padding:.65rem 0;overflow-y:auto;overflow-x:hidden}
/* Live indicator */
.live-indicator{display:flex;align-items:center;gap:.4rem;font-size:.72rem;color:var(--muted);background:rgba(22,163,74,0.08);border:1px solid rgba(22,163,74,0.2);padding:.28rem .6rem;border-radius:50px}
.live-dot{width:6px;height:6px;background:#4ADE80;border-radius:50%;animation:livePulse 1.5s infinite;flex-shrink:0}
@keyframes livePulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(74,222,128,.4)}50%{opacity:.7;box-shadow:0 0 0 4px rgba(74,222,128,0)}}
/* Search bar in topbar */
.search-bar{display:flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:8px;padding:.38rem .85rem}
.search-bar input{background:none;border:none;color:var(--white);font-size:.82rem;outline:none;width:150px;font-family:var(--font-body)}
.search-bar input::placeholder{color:var(--muted)}
/* Super badge in topbar */
.super-badge{background:linear-gradient(135deg,var(--gold),var(--gold3));color:var(--navy);font-size:.55rem;font-weight:800;padding:.15rem .45rem;border-radius:4px;letter-spacing:.06em;text-transform:uppercase;font-family:var(--font-body)}
/* Missing classes */
.chart-wrap,.chart-box{position:relative;height:260px}
.chart-wrap canvas,.chart-box canvas{max-height:260px}
.chg-up{color:#4ADE80;font-size:.68rem;margin-top:.2rem}
.chg-down,.chg-dn{color:#FCA5A5;font-size:.68rem;margin-top:.2rem}
.ico-teal{background:rgba(212,160,23,0.15);color:var(--gold2)}
.ico-purple{background:rgba(124,58,237,0.15);color:#C4B5FD}
.pill{display:inline-block;padding:.18rem .55rem;border-radius:50px;font-size:.68rem;font-weight:700}
.pill-active{background:rgba(22,163,74,0.15);color:#4ADE80}
.pill-trial{background:rgba(212,160,23,0.15);color:var(--gold2)}
.pill-warn{background:rgba(220,38,38,0.15);color:#FCA5A5}
.pill-blue{background:rgba(46,94,170,0.2);color:#93C5FD}
.pill-muted{background:rgba(139,163,204,0.1);color:var(--muted)}
.feed-item{display:flex;align-items:flex-start;gap:.75rem;padding:.65rem 0;border-bottom:1px solid rgba(212,160,23,.06)}
.feed-item:last-child{border-bottom:none}
.feed-ico{width:32px;height:32px;min-width:32px;border-radius:8px;display:grid;place-items:center;font-size:.8rem;flex-shrink:0}
.dot-badge{position:absolute;top:5px;right:5px;width:7px;height:7px;background:var(--red);border-radius:50%;border:2px solid var(--navy3)}
/* Topbar right section */
.topbar-right{display:flex;align-items:center;gap:.55rem;flex-shrink:0}

.sidebar-foot{padding:.85rem;border-top:1px solid var(--border);flex-shrink:0}
.admin-info{display:flex;align-items:center;gap:.6rem}
.admin-av{
  width:32px;height:32px;min-width:32px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--navy2));
  display:grid;place-items:center;
  font-family:var(--font-head);font-size:.72rem;font-weight:700;color:var(--navy);
}
.admin-name{font-size:.78rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.admin-role{font-size:.65rem;color:var(--muted)}
.logout-btn{margin-left:auto;color:var(--muted);background:none;border:none;cursor:pointer;font-size:.82rem;transition:color .2s;flex-shrink:0}
.logout-btn:hover{color:var(--red)}

/* ── MAIN CONTENT ── */
.main{
  margin-left:var(--sidebar-w);
  flex:1;display:flex;flex-direction:column;
  min-width:0;width:calc(100% - var(--sidebar-w));
  overflow-x:hidden;
}
.topbar{
  padding:.85rem 1.25rem;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  background:rgba(7,18,48,0.92);backdrop-filter:blur(20px);
  position:sticky;top:0;z-index:200;gap:1rem;
}
.page-title{font-family:var(--font-head);font-size:1.1rem;font-weight:700;white-space:nowrap}
.topbar-actions{display:flex;align-items:center;gap:.5rem;flex-shrink:0}
.icon-btn{
  width:32px;height:32px;
  background:rgba(255,255,255,0.04);border:1px solid var(--border);
  border-radius:7px;display:grid;place-items:center;
  cursor:pointer;color:var(--muted);font-size:.82rem;
  transition:all .2s;text-decoration:none;flex-shrink:0;
}
.icon-btn:hover{color:var(--gold2);border-color:var(--gold)}
.notif-dot{
  position:absolute;top:5px;right:5px;width:7px;height:7px;
  background:var(--red);border-radius:50%;border:2px solid var(--navy3);
}

/* ── CONTENT AREA ── */
.content{padding:1.25rem;flex:1;min-width:0;overflow-x:hidden}

/* ── SECTION PANELS ── */
.section-panel{display:none}
.section-panel.active{display:block}
.sec-hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem}
.sec-hdr h1{font-family:var(--font-head);font-size:1.4rem;font-weight:800;margin-bottom:.2rem}
.sec-hdr p{color:var(--muted);font-size:.82rem}

/* ── KPI CARDS ── */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.85rem;margin-bottom:1.25rem;width:100%}
.kpi{
  background:var(--card);border:1px solid var(--border);
  border-radius:13px;padding:1.1rem;
  display:flex;align-items:center;gap:.85rem;
  transition:all .25s;position:relative;overflow:hidden;min-width:0;
}
.kpi::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,var(--gold),var(--gold3));
  transform:scaleX(0);transition:.3s;transform-origin:left;
}
.kpi:hover{border-color:rgba(212,160,23,0.4)}.kpi:hover::after{transform:scaleX(1)}
.kpi-ico{width:44px;height:44px;min-width:44px;border-radius:11px;display:grid;place-items:center;font-size:1.05rem}
.ico-gold{background:rgba(212,160,23,0.15);color:var(--gold2)}
.ico-blue{background:rgba(46,94,170,0.2);color:var(--blue2)}
.ico-green{background:rgba(22,163,74,0.15);color:#4ADE80}
.ico-red{background:rgba(220,38,38,0.15);color:#FCA5A5}
.ico-orange{background:rgba(234,88,12,0.15);color:#FB923C}
.kpi-lbl{font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap}
.kpi-val{font-family:var(--font-head);font-size:1.5rem;font-weight:800;line-height:1.1;margin:.1rem 0;white-space:nowrap}
.kpi-chg{font-size:.68rem;color:var(--gold2);white-space:nowrap}

/* ── CARDS ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.25rem;position:relative;overflow:hidden;min-width:0}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold),var(--gold3),transparent);opacity:0;transition:.3s}
.card:hover{border-color:rgba(212,160,23,0.35)}.card:hover::before{opacity:1}
.card-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem}
.card-title{font-family:var(--font-head);font-size:.9rem;font-weight:700;display:flex;align-items:center;gap:.4rem}
.card-title i{color:var(--gold2)}

/* ── GRIDS ── */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}

/* ── BUTTONS ── */
.btn-primary{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;background:linear-gradient(135deg,var(--gold),var(--gold3));color:var(--navy);font-weight:700;font-size:.82rem;border:none;border-radius:8px;cursor:pointer;transition:all .2s;text-decoration:none;white-space:nowrap}
.btn-primary:hover{opacity:.9;box-shadow:0 3px 14px rgba(212,160,23,0.4);transform:translateY(-1px)}
.btn-blue{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;background:linear-gradient(135deg,var(--blue),var(--navy2));color:var(--white);font-weight:600;font-size:.82rem;border:1px solid rgba(46,94,170,0.4);border-radius:8px;cursor:pointer;transition:all .2s;text-decoration:none}
.btn-ghost{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .9rem;background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--white);font-size:.82rem;font-weight:500;border-radius:8px;cursor:pointer;transition:all .2s;text-decoration:none}
.btn-ghost:hover{border-color:var(--gold);color:var(--gold2)}
.btn-danger{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .9rem;background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);color:#FCA5A5;font-size:.82rem;font-weight:500;border-radius:8px;cursor:pointer;transition:all .2s}
.btn-danger:hover{background:rgba(220,38,38,0.2)}
.btn-sm{padding:.3rem .65rem;font-size:.75rem}

/* ── BADGES ── */
.badge{display:inline-block;padding:.18rem .55rem;border-radius:50px;font-size:.68rem;font-weight:700}
.badge-ok,.badge-active{background:rgba(212,160,23,0.15);color:var(--gold2)}
.badge-warn{background:rgba(245,166,35,0.15);color:#FCD34D}
.badge-red,.badge-error{background:rgba(220,38,38,0.15);color:#FCA5A5}
.badge-blue{background:rgba(46,94,170,0.2);color:var(--blue2)}
.badge-muted{background:rgba(139,163,204,0.1);color:var(--muted)}
.badge-green{background:rgba(22,163,74,0.15);color:#4ADE80}

/* ── TABLE ── */
table{width:100%;border-collapse:collapse}
thead th{padding:.65rem .85rem;text-align:left;font-size:.7rem;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);white-space:nowrap}
tbody td{padding:.75rem .85rem;border-bottom:1px solid rgba(212,160,23,0.06);font-size:.82rem;vertical-align:middle}
tbody tr:hover{background:rgba(212,160,23,0.03)}

/* ── FORM ── */
.f-group{margin-bottom:1rem}
.f-group label{display:block;font-size:.75rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-bottom:.4rem}
.f-input{width:100%;background:rgba(255,255,255,0.04);border:1.5px solid rgba(212,160,23,0.18);border-radius:8px;padding:.6rem .85rem;color:var(--white);font-size:.85rem;outline:none;transition:all .25s;font-family:var(--font-body)}
.f-input:focus{border-color:var(--gold);background:rgba(212,160,23,0.04);box-shadow:0 0 0 3px rgba(212,160,23,0.1)}
.f-input::placeholder{color:rgba(255,255,255,0.2)}
textarea.f-input{resize:vertical;min-height:75px}
select.f-input option{background:var(--navy2)}
.form-control{width:100%;background:rgba(255,255,255,0.04);border:1.5px solid rgba(212,160,23,0.18);border-radius:8px;padding:.6rem .85rem;color:var(--white);font-size:.85rem;outline:none;transition:all .25s;font-family:var(--font-body)}
.form-control:focus{border-color:var(--gold);background:rgba(212,160,23,0.04);box-shadow:0 0 0 3px rgba(212,160,23,0.1)}
.form-control::placeholder{color:rgba(255,255,255,0.2)}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(7,18,48,0.8);backdrop-filter:blur(6px);z-index:500;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:var(--navy2);border:1px solid var(--border);border-radius:16px;padding:1.75rem;width:100%;max-width:520px;position:relative;box-shadow:0 20px 60px rgba(7,18,48,0.7);max-height:90vh;overflow-y:auto}
.modal-title{font-family:var(--font-head);font-size:1rem;font-weight:700;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem}
.modal-close{position:absolute;top:.85rem;right:.85rem;background:none;border:none;color:var(--muted);cursor:pointer;font-size:.95rem;transition:color .2s}
.modal-close:hover{color:var(--white)}

/* ── SETTINGS TABS ── */
.settings-tab{padding:.6rem 1rem;border:none;background:transparent;color:var(--muted);font-size:.82rem;font-weight:600;cursor:pointer;border-bottom:3px solid transparent;transition:all .25s;white-space:nowrap;display:inline-flex;align-items:center;gap:.35rem;font-family:var(--font-body)}
.settings-tab:hover{color:var(--white)}
.settings-tab.active{color:var(--gold2);border-bottom-color:var(--gold)}
.settings-pane{display:none}.settings-pane.active{display:block}

/* ── SMTP PROVIDER CARDS ── */
.smtp-provider-card{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:11px;padding:.85rem;transition:all .25s;cursor:pointer}
.smtp-provider-card:hover{background:rgba(212,160,23,0.06);border-color:var(--border);transform:translateY(-2px);box-shadow:0 4px 16px rgba(7,18,48,0.4)}

/* ── TOGGLE SWITCH ── */
.toggle-switch{position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:rgba(255,255,255,0.1);border-radius:22px;transition:.3s;border:1px solid rgba(255,255,255,0.12)}
.toggle-slider::before{content:'';position:absolute;height:14px;width:14px;left:3px;bottom:3px;background:var(--muted);border-radius:50%;transition:.3s}
.toggle-switch input:checked + .toggle-slider{background:linear-gradient(135deg,var(--gold),var(--gold3));border-color:var(--gold3)}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(18px);background:var(--navy)}

/* ── MISC ── */
.status-dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:.3rem}
.dot-green{background:#4ADE80;box-shadow:0 0 5px #4ADE80}
.dot-gold{background:var(--gold2);box-shadow:0 0 5px var(--gold2)}
.dot-red{background:#F87171;box-shadow:0 0 5px #F87171}
.dot-muted{background:var(--muted)}
.empty-state{text-align:center;padding:2.5rem 1rem;color:var(--muted)}
.empty-state i{font-size:2rem;margin-bottom:.65rem;opacity:.3;display:block;color:var(--gold3)}

/* ── SCROLLBAR ── */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--navy4)}
::-webkit-scrollbar-thumb{background:var(--gold3);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--gold)}

/* ── RESPONSIVE ── */
.hamburger{background:none;border:none;color:var(--white);font-size:1.1rem;cursor:pointer;margin-right:.5rem;padding:.25rem;flex-shrink:0}
@media(max-width:1024px){
  :root{--sidebar-w:200px}
}
@media(max-width:768px){
  :root{--sidebar-w:240px}
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0;width:100%}
  .kpi-grid{grid-template-columns:1fr 1fr}
  .g2,.g3,.g4{grid-template-columns:1fr}
  .hamburger{display:flex!important}
  .topbar .page-title{font-size:.95rem}
}
@media(max-width:480px){
  .kpi-grid{grid-template-columns:1fr}
  .content{padding:.85rem}
}

/* ═══════════════════════════════════════════════════════════
   SETTINGS PANEL — LIGHT WHITE THEME
   ═══════════════════════════════════════════════════════════ */
#panel-settings {
  background: #f8f9fc;
  margin: -1.25rem;
  padding: 1.5rem;
  min-height: calc(100vh - 60px);
}
#panel-settings .sec-hdr h1 {
  color: #1a1a2e;
  font-family: var(--font-head);
  font-size: 1.6rem;
  font-weight: 800;
}
#panel-settings .sec-hdr p {
  color: #6b7280;
  font-size: .85rem;
}

/* Light tabs */
#panel-settings .settings-tabs-bar {
  border-bottom: 2px solid #e5e7eb;
  margin-bottom: 1.75rem;
  display: flex;
  gap: 0;
  overflow-x: auto;
  background: #fff;
  border-radius: 12px 12px 0 0;
  padding: .25rem .5rem 0;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
#panel-settings .settings-tab {
  padding: .75rem 1.1rem;
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all .25s;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-family: var(--font-body);
  margin-bottom: -2px;
}
#panel-settings .settings-tab:hover { color: #1a1a2e; }
#panel-settings .settings-tab.active {
  color: var(--blue);
  border-bottom-color: var(--blue);
  font-weight: 700;
}
#panel-settings .settings-tab.active i { color: var(--blue); }

/* Light cards */
#panel-settings .card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 1.5rem;
  box-shadow: 0 1px 8px rgba(0,0,0,.06);
  position: relative;
  overflow: hidden;
  min-width: 0;
}
#panel-settings .card::before { display: none; }
#panel-settings .card:hover { border-color: var(--gold); box-shadow: 0 4px 20px rgba(212,160,23,.12); }
#panel-settings .card-hdr { margin-bottom: 1.25rem; }
#panel-settings .card-title {
  font-family: var(--font-head);
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a2e;
  display: flex;
  align-items: center;
  gap: .5rem;
}
#panel-settings .card-title i { color: var(--gold); }

/* Light form labels & inputs */
#panel-settings .f-group label {
  color: #374151;
  font-size: .78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: .4rem;
  display: block;
}
#panel-settings .f-input {
  width: 100%;
  background: #f9fafb;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  padding: .65rem .9rem;
  color: #1a1a2e;
  font-size: .875rem;
  outline: none;
  transition: all .25s;
  font-family: var(--font-body);
}
#panel-settings .f-input:focus {
  border-color: var(--blue);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(46,94,170,.12);
}
#panel-settings .f-input::placeholder { color: #9ca3af; }
#panel-settings textarea.f-input { resize: vertical; min-height: 80px; }
#panel-settings select.f-input option { background: #fff; color: #1a1a2e; }

/* Light buttons */
#panel-settings .btn-primary {
  background: linear-gradient(135deg, var(--gold), var(--gold3));
  color: #fff;
  font-weight: 700;
  border: none;
  padding: .65rem 1.25rem;
  border-radius: 9px;
  cursor: pointer;
  font-size: .875rem;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  transition: all .25s;
  box-shadow: 0 2px 10px rgba(212,160,23,.3);
}
#panel-settings .btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 18px rgba(212,160,23,.4);
}
#panel-settings .btn-ghost {
  background: #f3f4f6;
  border: 1.5px solid #d1d5db;
  color: #374151;
  padding: .65rem 1.25rem;
  border-radius: 9px;
  cursor: pointer;
  font-size: .875rem;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  transition: all .25s;
}
#panel-settings .btn-ghost:hover { border-color: var(--blue); color: var(--blue); background: #eff6ff; }

/* SMTP provider cards */
#panel-settings .smtp-provider-card {
  background: #fff;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  transition: all .25s;
  cursor: pointer;
}
#panel-settings .smtp-provider-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.1);
}

/* Toggle switches — light */
#panel-settings .toggle-switch { position: relative; display: inline-block; width: 42px; height: 24px; flex-shrink: 0; }
#panel-settings .toggle-switch input { opacity: 0; width: 0; height: 0; }
#panel-settings .toggle-slider {
  position: absolute; cursor: pointer; inset: 0;
  background: #d1d5db; border-radius: 24px; transition: .3s;
  border: none;
}
#panel-settings .toggle-slider::before {
  content: ''; position: absolute;
  height: 16px; width: 16px;
  left: 4px; bottom: 4px;
  background: #fff; border-radius: 50%; transition: .3s;
  box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
#panel-settings .toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, var(--gold), var(--gold3));
}
#panel-settings .toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(18px);
}

/* Toggle rows */
#panel-settings [style*="border-bottom:1px solid rgba(212,160,23"] {
  border-bottom: 1px solid #f3f4f6 !important;
}
#panel-settings [style*="border-bottom:1px solid rgba(212,160,23,.07)"] {
  border-bottom-color: #f3f4f6 !important;
}

/* Info boxes */
#panel-settings [style*="rgba(212,160,23,.06)"] {
  background: #fffbeb !important;
  border-color: #fcd34d !important;
  color: #92400e !important;
}
#panel-settings [style*="rgba(46,94,170,.08)"] {
  background: #eff6ff !important;
  border-color: #bfdbfe !important;
  color: var(--blue) !important;
}

/* Section heading */
#panel-settings h1 { color: #111827; }

/* Provider card text colors */
#panel-settings .smtp-provider-card div[style*="color:var(--muted)"] { color: #6b7280 !important; }
#panel-settings a[style*="color:var(--muted)"] { color: #6b7280 !important; }

/* Grid rows inside settings */
#panel-settings .g2 { gap: 1.25rem; }

/* Toggle row text */
#panel-settings span[style*="font-size:.875rem"] { color: #374151 !important; }

</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <div class="sb-logo"><i class="fas fa-database"></i></div>
    <span class="sb-name">Silo<span>Smart</span></span>
    <span class="sb-super">SUPER ADMIN</span>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Overview</div>
    <button class="sb-item active" onclick="showPanel('overview')"><i class="fas fa-tachometer-alt"></i><span class="sb-label">Overview</span></button>
    
    <div class="sb-section">Platform</div>
    <button class="sb-item" onclick="showPanel('organisations')"><i class="fas fa-building"></i><span class="sb-label">Organisations</span><span class="sb-badge">12</span></button>
    <button class="sb-item" onclick="showPanel('users')"><i class="fas fa-users"></i><span class="sb-label">All Users</span></button>
    <button class="sb-item" onclick="showPanel('plans')"><i class="fas fa-layer-group"></i><span class="sb-label">Subscription Plans</span></button>
    <button class="sb-item" onclick="showPanel('devices')"><i class="fas fa-microchip"></i><span class="sb-label">Device Registry</span></button>

    <div class="sb-section">Finance</div>
    <button class="sb-item" onclick="showPanel('revenue')"><i class="fas fa-chart-bar"></i><span class="sb-label">Revenue</span></button>
    <button class="sb-item" onclick="showPanel('payments')"><i class="fas fa-mobile-alt"></i><span class="sb-label">M-Pesa Payments</span></button>

    <div class="sb-section">Security</div>
    <button class="sb-item" onclick="showPanel('activity')"><i class="fas fa-eye"></i><span class="sb-label">Live Activity</span><span class="sb-badge" style="background:var(--success)">Live</span></button>
    <button class="sb-item" onclick="showPanel('audit')"><i class="fas fa-shield-alt"></i><span class="sb-label">Audit Trail</span></button>
    <button class="sb-item" onclick="showPanel('snapshots')"><i class="fas fa-camera"></i><span class="sb-label">Camera Snapshots</span></button>

    <div class="sb-section">System</div>
    <button class="sb-item" onclick="showPanel('tickets')"><i class="fas fa-headset"></i><span class="sb-label">Support Tickets</span><span class="sb-badge">3</span></button>
    <button class="sb-item" onclick="showPanel('settings')"><i class="fas fa-cog"></i><span class="sb-label">System Settings</span></button>
    <button class="sb-item" onclick="showPanel('health')"><i class="fas fa-heartbeat"></i><span class="sb-label">System Health</span></button>
    <a class="sb-item" href="/dashboard.php"><i class="fas fa-external-link-alt"></i><span class="sb-label">View Frontend</span></a>
  </nav>
  <div class="sb-footer">
    <div class="sa-avatar"><?= $user_initials ?></div>
    <div class="sa-info">
      <div class="sa-name"><?= $user_name ?></div>
      <div class="sa-role">Platform Owner</div>
    </div>
    <a href="/logout.php" class="sb-item" title="Logout" style="margin-left:auto;padding:.4rem;flex-shrink:0" id="sbLogoutBtn"><i class="fas fa-sign-out-alt" style="color:var(--danger)"></i></a>
  </div>
  
</aside>

<!-- MAIN -->
<main class="main" id="mainContent">
  <div class="topbar">
    <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none"><i class="fas fa-bars"></i></button>
    <div class="page-title">
      <span id="topbarTitle">Overview</span>
      <span class="super-badge">SUPER ADMIN</span>
    </div>
    <div class="topbar-right">
      <div class="live-indicator"><div class="live-dot"></div><span>Live Feed</span></div>
      <div class="search-bar"><i class="fas fa-search" style="color:var(--muted);font-size:.85rem"></i><input placeholder="Search anything…"></div>
      <div class="icon-btn" onclick="showPanel('activity')"><i class="fas fa-bell"></i><span class="dot-badge"></span></div>
      <div class="icon-btn"><i class="fas fa-question-circle"></i></div>
    </div>
  </div>

  <div class="content">

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- OVERVIEW PANEL -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="section-panel active" id="panel-overview">
      <div class="sec-hdr">
        <div><h1>Platform Overview</h1><p>Real-time snapshot of the entire SiloSmart platform</p></div>
        <div class="live-indicator" style="font-size:.8rem"><div class="live-dot"></div> Auto-refreshing every 15s</div>
      </div>
      <div class="g4" style="margin-bottom:1.25rem">
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-building"></i></div><div><div class="kpi-lbl">Total Organisations</div><div class="kpi-val">12</div><div class="kpi-chg chg-up">↑ 2 this month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-blue"><i class="fas fa-users"></i></div><div><div class="kpi-lbl">Total Users</div><div class="kpi-val">187</div><div class="kpi-chg chg-up">↑ 24 this month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-database"></i></div><div><div class="kpi-lbl">Total Silos</div><div class="kpi-val">64</div><div class="kpi-chg chg-nu">Across 12 orgs</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-purple"><i class="fas fa-dollar-sign"></i></div><div><div class="kpi-lbl">MRR (KES)</div><div class="kpi-val">147K</div><div class="kpi-chg chg-up">↑ 18% vs last month</div></div></div>
      </div>
      <div class="g4" style="margin-bottom:1.5rem">
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-satellite-dish"></i></div><div><div class="kpi-lbl">Sensors Online</div><div class="kpi-val">218<span style="font-size:.9rem;color:var(--muted)">/231</span></div><div class="kpi-chg chg-up">94.4% uptime</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-red"><i class="fas fa-bell"></i></div><div><div class="kpi-lbl">Active Alerts</div><div class="kpi-val">9</div><div class="kpi-chg chg-dn">3 critical across platform</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-blue"><i class="fas fa-tasks"></i></div><div><div class="kpi-lbl">Open Tasks</div><div class="kpi-val">31</div><div class="kpi-chg chg-nu">7 overdue</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-credit-card"></i></div><div><div class="kpi-lbl">Pending Renewals</div><div class="kpi-val">4</div><div class="kpi-chg chg-dn">Within 7 days</div></div></div>
      </div>
      <div class="g-mix" style="margin-bottom:1.25rem">
        <div class="card">
          <div class="card-hdr"><span class="card-title"><i class="fas fa-chart-line"></i> Monthly Revenue (KES)</span></div>
          <div class="chart-wrap" style="position:relative"><canvas id="revChart"></canvas><div id="revChart-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:.85rem"><i class="fas fa-spinner fa-spin" style="margin-right:.5rem"></i> Loading chart…</div></div>
        </div>
        <div class="card">
          <div class="card-hdr"><span class="card-title"><i class="fas fa-layer-group"></i> Plan Distribution</span></div>
          <div style="height:220px;position:relative;display:flex;align-items:center;justify-content:center">
            <canvas id="planChart" style="max-height:200px;max-width:200px"></canvas>
            <div id="planChart-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:.85rem"><i class="fas fa-spinner fa-spin" style="margin-right:.5rem"></i> Loading…</div>
          </div>
        </div>
      </div>

      <!-- RECENT ORG TABLE -->
      <div class="card">
        <div class="card-hdr"><span class="card-title"><i class="fas fa-building"></i> Recent Organisations</span><button class="act-btn primary" onclick="showPanel('organisations')">View All</button></div>
        <table>
          <thead><tr><th>Organisation</th><th>Plan</th><th>Silos</th><th>Users</th><th>Status</th><th>MRR (KES)</th><th>Actions</th></tr></thead>
          <tbody id="orgTableBody">
            <tr><td><div style="font-weight:600">AgriStore Kenya Ltd</div><div style="font-size:.75rem;color:var(--muted)">agristore-kenya.silosmart.io</div></td><td>Professional</td><td>5</td><td>8</td><td><span class="pill pill-active">Active</span></td><td>7,999</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showOrgModal('AgriStore Kenya')">View</button><button class="act-btn" onclick="suspendOrg(this)">Suspend</button></div></td></tr>
            <tr><td><div style="font-weight:600">Coastal Grain Processors</div><div style="font-size:.75rem;color:var(--muted)">coastal-grain.silosmart.io</div></td><td>Enterprise</td><td>18</td><td>34</td><td><span class="pill pill-active">Active</span></td><td>19,999</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showOrgModal('Coastal Grain')">View</button><button class="act-btn" onclick="suspendOrg(this)">Suspend</button></div></td></tr>
            <tr><td><div style="font-weight:600">Nairobi Cement Ltd</div><div style="font-size:.75rem;color:var(--muted)">nairobi-cement.silosmart.io</div></td><td>Professional</td><td>8</td><td>15</td><td><span class="pill pill-active">Active</span></td><td>7,999</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showOrgModal('Nairobi Cement')">View</button><button class="act-btn" onclick="suspendOrg(this)">Suspend</button></div></td></tr>
            <tr><td><div style="font-weight:600">Lakeside Agro</div><div style="font-size:.75rem;color:var(--muted)">lakeside-agro.silosmart.io</div></td><td>Starter</td><td>3</td><td>5</td><td><span class="pill pill-trial">Trial</span></td><td>0</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showOrgModal('Lakeside Agro')">View</button><button class="act-btn danger" onclick="showToast('Organisation deleted','warning')">Delete</button></div></td></tr>
            <tr><td><div style="font-weight:600">Mombasa Port Stores</div><div style="font-size:.75rem;color:var(--muted)">msa-port.silosmart.io</div></td><td>Starter</td><td>4</td><td>7</td><td><span class="pill pill-suspended">Suspended</span></td><td>0</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="activateOrg(this)">Activate</button><button class="act-btn danger">Delete</button></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- LIVE ACTIVITY PANEL -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-activity">
      <div class="sec-hdr">
        <div><h1><i class="fas fa-eye" style="color:var(--primary);margin-right:.5rem"></i>Live Activity Monitor</h1><p>Real-time forensic tracking of all platform user actions</p></div>
        <div style="display:flex;gap:.75rem;align-items:center">
          <div class="live-indicator"><div class="live-dot"></div> LIVE — updating every 3s</div>
          <button class="act-btn primary" onclick="exportActivityLog()"><i class="fas fa-download"></i> Export</button>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="card" style="margin-bottom:1.25rem;padding:1rem 1.5rem">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
          <select style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.5rem .75rem;outline:none;font-family:var(--font-body);font-size:.85rem">
            <option>All Organisations</option><option>AgriStore Kenya</option><option>Coastal Grain</option>
          </select>
          <select style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.5rem .75rem;outline:none;font-family:var(--font-body);font-size:.85rem">
            <option>All Categories</option><option>auth</option><option>alert</option><option>payment</option><option>report</option>
          </select>
          <select style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.5rem .75rem;outline:none;font-family:var(--font-body);font-size:.85rem">
            <option>All Risk Levels</option><option>Suspicious Only</option><option>Critical</option>
          </select>
          <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer"><input type="checkbox" checked style="accent-color:var(--primary)"> Show Snapshots</label>
        </div>
      </div>

      <!-- LIVE FEED -->
      <div class="card">
        <div class="card-hdr"><span class="card-title"><i class="fas fa-stream"></i> Activity Feed</span><span style="font-size:.75rem;color:var(--muted)" id="logCount">Showing 10 of 1,847 entries</span></div>
        <div id="liveActivityFeed">
          <!-- Populated by JS -->
        </div>
        <div style="text-align:center;margin-top:1rem"><button class="act-btn" onclick="loadMoreLogs()">Load More</button></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- ORGANISATIONS PANEL -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-organisations">
      <div class="sec-hdr">
        <div><h1>Organisations</h1><p>Manage all tenant organisations on the platform</p></div>
        <button class="btn-primary" onclick="document.getElementById('newOrgModal').classList.add('open')"><i class="fas fa-plus"></i> New Organisation</button>
      </div>
      <div class="card">
        <div class="card-hdr"><span class="card-title"><i class="fas fa-building"></i> All Organisations (12)</span>
          <div style="display:flex;gap:.5rem">
            <input placeholder="Search…" style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.45rem .75rem;font-size:.85rem;outline:none;width:180px">
            <select style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.45rem .6rem;font-size:.85rem;outline:none"><option>All Status</option><option>Active</option><option>Suspended</option><option>Trial</option></select>
          </div>
        </div>
        <table>
          <thead><tr><th>Organisation</th><th>Plan</th><th>Silos / Users</th><th>Status</th><th>Expires</th><th>MRR</th><th>Actions</th></tr></thead>
          <tbody>
            <tr><td><div style="font-weight:600">AgriStore Kenya Ltd</div><div style="font-size:.73rem;color:var(--muted)">admin@agristore.co.ke</div></td><td><span class="pill pill-active">Professional</span></td><td>5 / 8</td><td><span class="pill pill-active">Active</span></td><td style="font-size:.8rem">Dec 31, 2025</td><td>KES 7,999</td><td><div class="tbl-actions"><button class="act-btn primary">Edit</button><button class="act-btn" onclick="impersonate(this,'AgriStore')">Login As</button><button class="act-btn danger">Suspend</button></div></td></tr>
            <tr><td><div style="font-weight:600">Coastal Grain Processors</div><div style="font-size:.73rem;color:var(--muted)">ops@coastalgrain.co.ke</div></td><td><span class="pill" style="background:#ede9fe;color:var(--purple)">Enterprise</span></td><td>18 / 34</td><td><span class="pill pill-active">Active</span></td><td style="font-size:.8rem">Mar 15, 2026</td><td>KES 19,999</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Edit Coastal Grain…','info')">Edit</button><button class="act-btn" onclick="impersonate(this,'Coastal Grain')">Login As</button><button class="act-btn danger">Suspend</button></div></td></tr>
            <tr><td><div style="font-weight:600">Mombasa Port Stores</div></td><td><span class="pill" style="background:#e0f2fe;color:var(--accent)">Starter</span></td><td>4 / 7</td><td><span class="pill pill-suspended">Suspended</span></td><td style="font-size:.8rem;color:var(--danger)">EXPIRED</td><td>KES 0</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="activateOrg(this)">Activate</button><button class="act-btn danger">Delete</button></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- REVENUE PANEL -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-revenue">
      <div class="sec-hdr"><div><h1 style="color:var(--text)">Revenue Dashboard</h1><p>M-Pesa and payment analytics across all organisations</p></div></div>
      <div class="g4" style="margin-bottom:1.25rem">
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-coins"></i></div><div><div class="kpi-lbl">MRR (KES)</div><div class="kpi-val">147,960</div><div class="kpi-chg chg-up">↑ 18% vs last month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-purple"><i class="fas fa-calendar-check"></i></div><div><div class="kpi-lbl">ARR (KES)</div><div class="kpi-val">1.77M</div><div class="kpi-chg chg-up">Projected</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-user-minus"></i></div><div><div class="kpi-lbl">Churn Rate</div><div class="kpi-val">4.2%</div><div class="kpi-chg chg-dn">↑ 0.5% vs last month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-blue"><i class="fas fa-user-plus"></i></div><div><div class="kpi-lbl">New Subs (month)</div><div class="kpi-val">3</div><div class="kpi-chg chg-up">↑ 1 vs last month</div></div></div>
      </div>
      <div class="g2">
        <div class="card">
          <div class="card-hdr"><span class="card-title"><i class="fas fa-chart-area"></i> Revenue Trend (12 months)</span></div>
          <div class="chart-wrap"><canvas id="revFullChart"></canvas></div>
        </div>
        <div class="card">
          <div class="card-hdr"><span class="card-title"><i class="fas fa-mobile-alt"></i> Recent M-Pesa Payments</span></div>
          <table>
            <thead><tr><th>Organisation</th><th>Amount</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
              <tr><td>AgriStore Kenya</td><td>KES 7,999</td><td style="font-size:.75rem;color:var(--muted)">2h ago</td><td><span class="pill pill-active">Paid</span></td></tr>
              <tr><td>Coastal Grain</td><td>KES 19,999</td><td style="font-size:.75rem;color:var(--muted)">1d ago</td><td><span class="pill pill-active">Paid</span></td></tr>
              <tr><td>Lakeside Agro</td><td>KES 2,999</td><td style="font-size:.75rem;color:var(--muted)">3d ago</td><td><span class="pill pill-trial">Pending</span></td></tr>
              <tr><td>Nairobi Cement</td><td>KES 7,999</td><td style="font-size:.75rem;color:var(--muted)">5d ago</td><td><span class="pill pill-active">Paid</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- SETTINGS PANEL -->
    <div class="section-panel" id="panel-settings">

      <div class="sec-hdr" style="margin-bottom:1.5rem">
        <div>
          <h1 style="font-family:var(--font-head);font-size:1.5rem;font-weight:800;color:var(--white);margin-bottom:.25rem">
            <i class="fas fa-cog" style="color:var(--gold2);margin-right:.5rem"></i>System Settings
          </h1>
          <p id="settings-date-time" style="color:var(--muted);font-size:.82rem"></p>
        </div>
        <div style="display:flex;gap:.65rem">
          <button class="btn-ghost" onclick="location.reload()"><i class="fas fa-sync"></i> Refresh</button>
          <button class="btn-primary" onclick="saveSettings('all')"><i class="fas fa-save"></i> Save All Changes</button>
        </div>
      </div>

      <!-- SETTINGS TABS BAR -->
      <div class="settings-tabs-bar" id="settingsTabsBar">
        <?php foreach([
          ['general',      'fa-cog',           'General'],
          ['appearance',   'fa-palette',        'Appearance'],
          ['notifications','fa-bell',           'Notifications'],
          ['payment',      'fa-credit-card',    'Payment & Billing'],
          ['email',        'fa-envelope',       'Email / SMTP'],
          ['security',     'fa-shield-alt',     'Security'],
          ['users',        'fa-users-cog',      'Users & Roles'],
          ['integrations', 'fa-plug',           'Integrations'],
          ['ai',           'fa-brain',          'AI & Chatbot'],
          ['social',       'fa-share-alt',      'Social'],
          ['commerce',     'fa-store',          'Commerce'],
          ['backup',       'fa-database',       'Backup & Data'],
          ['legal',        'fa-gavel',          'Legal & GDPR'],
        ] as [$tab,$icon,$label]): ?>
        <button class="settings-tab" data-tab="<?=$tab?>" onclick="switchSettingsTab('<?=$tab?>')">
          <i class="fas <?=$icon?>"></i> <?=$label?>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- GENERAL -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane active" id="stab-general">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-info-circle"></i> Platform Identity</span></div>
            <div class="f-group"><label>Platform Name</label><input class="f-input" id="s-site-name" value="SiloSmart" placeholder="Platform name"></div>
            <div class="f-group"><label>Tagline</label><input class="f-input" id="s-tagline" value="The Future of Intelligent Silo Management" placeholder="Platform tagline"></div>
            <div class="f-group"><label>Platform URL</label><input class="f-input" id="s-site-url" value="https://ren.is-best.net" placeholder="https://yourdomain.com"></div>
            <div class="f-group"><label>Admin Email</label><input class="f-input" id="s-support-email" value="rensonnjehia22@gmail.com" type="email"></div>
            <div class="f-group"><label>Support Phone</label><input class="f-input" id="s-support-phone" placeholder="+254700000000"></div>
            <div class="f-group"><label>Timezone</label>
              <select class="f-input" id="s-timezone">
                <option value="Africa/Nairobi" selected>Africa/Nairobi (EAT +3)</option>
                <option value="UTC">UTC</option>
                <option value="Africa/Lagos">Africa/Lagos (WAT +1)</option>
                <option value="Africa/Cairo">Africa/Cairo (EET +2)</option>
                <option value="America/New_York">America/New_York (EST -5)</option>
                <option value="Europe/London">Europe/London (GMT)</option>
                <option value="Asia/Dubai">Asia/Dubai (GST +4)</option>
              </select>
            </div>
            <div class="f-group"><label>Currency</label>
              <select class="f-input" id="s-currency">
                <option value="KES" selected>KES — Kenyan Shilling</option>
                <option value="USD">USD — US Dollar</option>
                <option value="UGX">UGX — Ugandan Shilling</option>
                <option value="TZS">TZS — Tanzanian Shilling</option>
                <option value="ZAR">ZAR — South African Rand</option>
                <option value="NGN">NGN — Nigerian Naira</option>
                <option value="GHS">GHS — Ghanaian Cedi</option>
              </select>
            </div>
            <div class="f-group"><label>Date Format</label>
              <select class="f-input" id="s-date-fmt">
                <option>DD/MM/YYYY</option><option>MM/DD/YYYY</option>
                <option>YYYY-MM-DD</option><option>D MMM YYYY</option>
              </select>
            </div>
            <button class="btn-primary" onclick="saveSettings('general')"><i class="fas fa-save"></i> Save General</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-database"></i> Data Retention</span></div>
            <?php foreach([
              ['Sensor Readings (days)','retention-sensor','365'],
              ['Activity Log (days)','retention-log','730'],
              ['Camera Snapshots (days)','retention-snapshots','90'],
              ['Alert History (days)','retention-alerts','180'],
              ['Notification History (days)','retention-notifs','30'],
              ['Deleted Organisation Data (days)','retention-deleted','30'],
            ] as [$label,$id,$val]): ?>
            <div class="f-group"><label><?=$label?></label><input class="f-input" type="number" id="<?=$id?>" value="<?=$val?>"></div>
            <?php endforeach; ?>
            <div style="background:rgba(212,160,23,.07);border:1px solid rgba(212,160,23,.25);border-radius:9px;padding:.75rem;font-size:.8rem;color:rgba(255,255,255,.7);margin-bottom:1rem">
              <i class="fas fa-info-circle" style="color:var(--gold2)"></i> Automatic cleanup runs nightly at 02:00 EAT.
            </div>
            <button class="btn-primary" onclick="saveSettings('retention')"><i class="fas fa-save"></i> Save Retention</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-sliders-h"></i> Platform Limits</span></div>
            <?php foreach([
              ['Default Max Silos per Org','limit-silos','10'],
              ['Default Max Users per Org','limit-users','20'],
              ['Max Sensors per Silo','limit-sensors','8'],
              ['API Rate Limit (req/min)','limit-api','100'],
              ['File Upload Max (MB)','limit-upload','10'],
              ['Session Timeout (minutes)','limit-session','60'],
            ] as [$label,$id,$val]): ?>
            <div class="f-group"><label><?=$label?></label><input class="f-input" type="number" id="<?=$id?>" value="<?=$val?>"></div>
            <?php endforeach; ?>
            <button class="btn-primary" onclick="saveSettings('limits')"><i class="fas fa-save"></i> Save Limits</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-toggle-on"></i> Feature Flags</span></div>
            <?php foreach([
              ['Enable Public Registration','flag-registration',true],
              ['Enable Organisation Trial Period','flag-trial',true],
              ['Enable M-Pesa Payments','flag-mpesa',true],
              ['Enable AI Predictions Engine','flag-ai',true],
              ['Enable Camera Facial Login','flag-facial',true],
              ['Enable Grain Marketplace','flag-market',false],
              ['Enable API Access for Tenants','flag-api',true],
              ['Enable Multi-Site Organisations','flag-multisite',false],
              ['Enable SMS Notifications','flag-sms',true],
              ['Maintenance Mode (locks all tenants)','flag-maintenance',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem;color:rgba(255,255,255,.82)"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?> id="<?=$id?>"><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('flags')"><i class="fas fa-save"></i> Save Feature Flags</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- APPEARANCE -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-appearance">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-paint-brush"></i> Branding</span></div>
            <div class="f-group"><label>Primary Color (Gold)</label>
              <div style="display:flex;gap:.75rem;align-items:center">
                <input type="color" value="#D4A017" id="s-primary-color" style="width:48px;height:38px;border:none;border-radius:8px;cursor:pointer;background:none;padding:2px"
                       oninput="document.getElementById('s-primary-hex').value=this.value;document.documentElement.style.setProperty('--gold',this.value)">
                <input class="f-input" value="#D4A017" id="s-primary-hex" style="flex:1" oninput="document.getElementById('s-primary-color').value=this.value">
              </div>
            </div>
            <div class="f-group"><label>Secondary Color (Royal Blue)</label>
              <div style="display:flex;gap:.75rem;align-items:center">
                <input type="color" value="#1A3A6B" id="s-secondary-color" style="width:48px;height:38px;border:none;border-radius:8px;cursor:pointer;background:none;padding:2px"
                       oninput="document.documentElement.style.setProperty('--navy2',this.value)">
                <input class="f-input" value="#1A3A6B" id="s-secondary-hex" style="flex:1">
              </div>
            </div>
            <div class="f-group"><label>Logo URL</label><input class="f-input" placeholder="https://yourdomain.com/logo.png" id="s-logo-url"></div>
            <div class="f-group"><label>Favicon URL</label><input class="f-input" placeholder="https://yourdomain.com/favicon.ico" id="s-favicon-url"></div>
            <div class="f-group"><label>Login Page Background</label>
              <select class="f-input" id="s-login-bg">
                <option>Dark Navy Gradient (default)</option>
                <option>Dark with Particle Animation</option>
                <option>Solid Dark</option>
                <option>Custom Image URL</option>
              </select>
            </div>
            <div class="f-group"><label>Custom CSS (Advanced)</label>
              <textarea class="f-input" rows="4" id="s-custom-css" placeholder="/* Add your custom styles here */"
                        style="font-family:monospace;font-size:.8rem"></textarea>
            </div>
            <button class="btn-primary" onclick="saveSettings('appearance')"><i class="fas fa-save"></i> Save Appearance</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-th-large"></i> UI Preferences</span></div>
            <?php foreach([
              ['Enable Dark Mode by Default','ui-dark',true],
              ['Show Welcome Animation on Login','ui-animation',true],
              ['Enable Live Clock in Dashboard','ui-clock',true],
              ['Show AI Predictions Widget','ui-ai-widget',true],
              ['Enable Real-Time Sensor Updates','ui-realtime',true],
              ['Compact Sidebar Mode','ui-compact',false],
              ['Show Silo Gauge Animations','ui-gauges',true],
              ['Enable Sound Alerts (Critical)','ui-sounds',false],
              ['Show Platform Watermark','ui-watermark',true],
              ['Enable User Activity Indicator','ui-activity',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem;color:rgba(255,255,255,.82)"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?> id="<?=$id?>"><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('ui')"><i class="fas fa-save"></i> Save UI Preferences</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- NOTIFICATIONS -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-notifications">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-bell"></i> Platform Notifications</span></div>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:1.25rem">Control which system-level events trigger notifications to super admins.</p>
            <?php foreach([
              ['New Organisation Registered','notif-new-org',true],
              ['Organisation Subscription Expired','notif-sub-expired',true],
              ['Payment Received','notif-payment',true],
              ['Payment Failed / Declined','notif-payment-fail',true],
              ['Critical Sensor Alert (any org)','notif-critical',true],
              ['User Login from New Device','notif-new-device',false],
              ['Support Ticket Opened','notif-ticket',true],
              ['System Health Degraded','notif-health',true],
              ['Database Space < 20%','notif-storage',true],
              ['API Rate Limit Hit','notif-api-rate',false],
              ['Backup Completed','notif-backup',true],
              ['Failed Login Attempts (5+)','notif-brute',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem;color:rgba(255,255,255,.82)"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?> id="<?=$id?>"><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('notifications')"><i class="fas fa-save"></i> Save Notifications</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-mobile-alt"></i> SMS / WhatsApp Alerts</span></div>
            <div style="background:rgba(212,160,23,.07);border:1px solid rgba(212,160,23,.25);border-radius:9px;padding:.85rem;font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:1.25rem">
              <i class="fas fa-info-circle" style="color:var(--gold2)"></i> Uses Africa's Talking SMS gateway. Get credentials at <a href="https://africastalking.com" target="_blank" style="color:var(--gold2)">africastalking.com</a>
            </div>
            <div class="f-group"><label>Provider</label>
              <select class="f-input" id="sms-provider">
                <option value="africastalking">Africa's Talking</option>
                <option value="twilio">Twilio</option>
                <option value="vonage">Vonage (Nexmo)</option>
                <option value="termii">Termii</option>
              </select>
            </div>
            <div class="f-group"><label>API Key</label><input class="f-input" type="password" id="sms-api-key" placeholder="Your SMS API key"></div>
            <div class="f-group"><label>API Username / SID</label><input class="f-input" id="sms-username" placeholder="Username or Account SID"></div>
            <div class="f-group"><label>Sender Name / Phone</label><input class="f-input" id="sms-sender" placeholder="SILOSMART or +254700000000"></div>
            <div class="f-group"><label>Admin Alert Phone Number</label><input class="f-input" type="tel" id="sms-admin-phone" placeholder="+254700000000"></div>
            <div style="display:flex;gap:.65rem">
              <button class="btn-primary" onclick="saveSettings('sms')"><i class="fas fa-save"></i> Save SMS</button>
              <button class="btn-ghost" onclick="testSMS()"><i class="fas fa-paper-plane"></i> Send Test SMS</button>
            </div>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-broadcast-tower"></i> Push Notifications</span></div>
            <div class="f-group"><label>Firebase (FCM) Server Key</label><input class="f-input" type="password" id="push-fcm-key" placeholder="Firebase Cloud Messaging key"></div>
            <div class="f-group"><label>VAPID Public Key</label><input class="f-input" id="push-vapid-pub" placeholder="Web push VAPID public key"></div>
            <div class="f-group"><label>VAPID Private Key</label><input class="f-input" type="password" id="push-vapid-priv" placeholder="Web push VAPID private key"></div>
            <?php foreach([
              ['Enable Browser Push Notifications','push-browser',true],
              ['Enable Mobile App Push (FCM)','push-mobile',false],
              ['Push on Critical Alerts Only','push-critical-only',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('push')"><i class="fas fa-save"></i> Save Push Settings</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-clock"></i> Digest & Schedule</span></div>
            <div class="f-group"><label>Daily Summary Email — Send At</label>
              <select class="f-input" id="digest-time">
                <option>07:00 EAT</option><option>08:00 EAT</option><option>18:00 EAT</option><option>Disabled</option>
              </select>
            </div>
            <div class="f-group"><label>Weekly Report — Day</label>
              <select class="f-input" id="digest-day">
                <option>Monday</option><option>Friday</option><option>Sunday</option><option>Disabled</option>
              </select>
            </div>
            <div class="f-group"><label>Alert Quiet Hours (start)</label><input class="f-input" type="time" id="quiet-start" value="22:00"></div>
            <div class="f-group"><label>Alert Quiet Hours (end)</label><input class="f-input" type="time" id="quiet-end" value="06:00"></div>
            <div class="f-group"><label>Critical Alerts Bypass Quiet Hours</label>
              <label class="toggle-switch" style="margin-top:.4rem"><input type="checkbox" checked><span class="toggle-slider"></span></label>
            </div>
            <button class="btn-primary" onclick="saveSettings('digest')"><i class="fas fa-save"></i> Save Schedule</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- PAYMENT & BILLING -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-payment">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-mobile-alt"></i> M-Pesa (Daraja API)</span></div>
            <div style="background:rgba(212,160,23,.07);border:1px solid rgba(212,160,23,.25);border-radius:9px;padding:.85rem;font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:1.25rem">
              <i class="fas fa-link" style="color:var(--gold2)"></i> Get credentials at <a href="https://developer.safaricom.co.ke" target="_blank" style="color:var(--gold2)">developer.safaricom.co.ke</a>
            </div>
            <div class="f-group"><label>Environment</label>
              <select class="f-input" id="s-mpesa-env">
                <option value="sandbox">Sandbox (Testing)</option>
                <option value="production">Production (Live)</option>
              </select>
            </div>
            <div class="f-group"><label>Consumer Key</label><input class="f-input" type="password" id="s-mpesa-key" placeholder="Consumer key"></div>
            <div class="f-group"><label>Consumer Secret</label><input class="f-input" type="password" id="s-mpesa-secret" placeholder="Consumer secret"></div>
            <div class="f-group"><label>Paybill / Shortcode</label><input class="f-input" id="s-mpesa-shortcode" value="174379"></div>
            <div class="f-group"><label>Passkey</label><input class="f-input" type="password" id="s-mpesa-passkey" placeholder="Daraja passkey"></div>
            <div class="f-group"><label>STK Callback URL</label><input class="f-input" id="s-mpesa-callback" value="https://ren.is-best.net/api/mpesa/callback.php"></div>
            <div style="display:flex;gap:.65rem">
              <button class="btn-primary" onclick="saveSettings('mpesa')"><i class="fas fa-save"></i> Save M-Pesa</button>
              <button class="btn-ghost" onclick="testMpesa()"><i class="fas fa-vial"></i> Test STK Push</button>
            </div>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-credit-card"></i> Stripe (Card Payments)</span></div>
            <div style="background:rgba(46,94,170,.08);border:1px solid rgba(46,94,170,.25);border-radius:9px;padding:.85rem;font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:1.25rem">
              <i class="fas fa-info-circle" style="color:var(--blue2)"></i> Get credentials at <a href="https://dashboard.stripe.com/apikeys" target="_blank" style="color:var(--blue2)">dashboard.stripe.com</a>
            </div>
            <div class="f-group"><label>Mode</label>
              <select class="f-input" id="stripe-mode">
                <option value="test">Test Mode</option><option value="live">Live Mode</option>
              </select>
            </div>
            <div class="f-group"><label>Publishable Key</label><input class="f-input" id="stripe-pub-key" placeholder="pk_test_..."></div>
            <div class="f-group"><label>Secret Key</label><input class="f-input" type="password" id="stripe-secret-key" placeholder="sk_test_..."></div>
            <div class="f-group"><label>Webhook Secret</label><input class="f-input" type="password" id="stripe-webhook" placeholder="whsec_..."></div>
            <div class="f-group"><label>Stripe Webhook URL</label>
              <input class="f-input" value="https://ren.is-best.net/api/payments/stripe-webhook.php" readonly style="opacity:.65;cursor:default">
            </div>
            <button class="btn-primary" onclick="saveSettings('stripe')"><i class="fas fa-save"></i> Save Stripe</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-file-invoice-dollar"></i> Billing Settings</span></div>
            <?php foreach([
              ['Trial Period (days)','billing-trial','14'],
              ['Grace Period After Expiry (days)','billing-grace','3'],
              ['Invoice Number Prefix','billing-prefix','SS-INV-'],
              ['VAT / Tax Rate (%)','billing-tax','16'],
              ['Payment Reminder (days before expiry)','billing-reminder','7'],
              ['Auto-suspend After (days overdue)','billing-suspend','5'],
            ] as [$label,$id,$val]): ?>
            <div class="f-group">
              <label><?=$label?></label>
              <input class="f-input" id="<?=$id?>" value="<?=$val?>" <?=is_numeric($val)?'type="number"':''?>>
            </div>
            <?php endforeach; ?>
            <?php foreach([
              ['Auto-suspend on Payment Failure','billing-auto-suspend',true],
              ['Send Invoice Emails Automatically','billing-invoice-email',true],
              ['Enable Annual Billing Discount','billing-annual',true],
              ['Show Prices Including Tax','billing-incl-tax',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('billing')"><i class="fas fa-save"></i> Save Billing</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-file-invoice"></i> Invoice & Tax</span></div>
            <div class="f-group"><label>Company Legal Name</label><input class="f-input" value="SiloSmart Technologies Ltd" id="inv-company"></div>
            <div class="f-group"><label>KRA PIN / Tax ID</label><input class="f-input" placeholder="P051234567X" id="inv-tax-id"></div>
            <div class="f-group"><label>VAT Registration Number</label><input class="f-input" placeholder="VAT/12345678" id="inv-vat"></div>
            <div class="f-group"><label>Business Address</label><textarea class="f-input" rows="3" id="inv-address" placeholder="Nairobi, Kenya"></textarea></div>
            <div class="f-group"><label>Invoice Footer Text</label><textarea class="f-input" rows="2" id="inv-footer" placeholder="Thank you for using SiloSmart."></textarea></div>
            <div class="f-group"><label>Invoice Logo Position</label>
              <select class="f-input" id="inv-logo-pos">
                <option>Top Left</option><option>Top Center</option><option>Top Right</option>
              </select>
            </div>
            <button class="btn-primary" onclick="saveSettings('invoice')"><i class="fas fa-save"></i> Save Invoice Settings</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- EMAIL / SMTP -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-email">
        <div class="card" style="margin-bottom:1.25rem">
          <div class="card-hdr"><span class="card-title"><i class="fas fa-server"></i> Recommended SMTP Providers</span></div>
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem">
            <?php foreach([
              ['Brevo (Sendinblue)','smtp-relay.brevo.com','587','TLS','300 emails/day free','#2FB2AC','https://app.brevo.com'],
              ['SendGrid','smtp.sendgrid.net','587','TLS','100 emails/day free','#1A82E2','https://sendgrid.com'],
              ['Mailgun','smtp.mailgun.org','587','TLS','5,000/month trial','#F06B26','https://mailgun.com'],
              ['Amazon SES','email-smtp.us-east-1.amazonaws.com','587','TLS','Very cheap paid','#FF9900','https://aws.amazon.com/ses'],
            ] as [$name,$host,$port,$enc,$note,$color,$url]): ?>
            <div class="smtp-provider-card" onclick="fillSmtp('<?=$host?>','<?=$port?>','<?=$enc?>')" style="border-color:<?=$color?>33">
              <div style="font-weight:700;font-size:.9rem;color:<?=$color?>;margin-bottom:.3rem"><?=$name?></div>
              <div style="font-size:.75rem;color:var(--muted);font-family:monospace;margin-bottom:.2rem"><?=$host?>:<?=$port?></div>
              <div style="font-size:.75rem;color:var(--muted);margin-bottom:.6rem"><?=$note?></div>
              <a href="<?=$url?>" target="_blank" style="font-size:.75rem;color:<?=$color?>;text-decoration:none">Get credentials →</a>
            </div>
            <?php endforeach; ?>
          </div>
          <p style="font-size:.8rem;color:var(--muted)"><i class="fas fa-mouse-pointer" style="color:var(--gold2)"></i> Click a card to auto-fill SMTP fields below, then save.</p>
        </div>
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-envelope"></i> SMTP Configuration</span></div>
            <div class="f-group"><label>SMTP Host</label><input class="f-input" id="s-smtp-host" placeholder="smtp-relay.brevo.com"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
              <div class="f-group"><label>Port</label><input class="f-input" id="s-smtp-port" value="587" type="number"></div>
              <div class="f-group"><label>Encryption</label>
                <select class="f-input" id="s-smtp-enc">
                  <option value="tls" selected>TLS</option>
                  <option value="ssl">SSL</option>
                  <option value="none">None</option>
                </select>
              </div>
            </div>
            <div class="f-group"><label>Username / Login</label><input class="f-input" id="s-smtp-user" placeholder="your@email.com"></div>
            <div class="f-group"><label>Password / API Key</label><input class="f-input" type="password" id="s-smtp-pass" placeholder="SMTP password or API key"></div>
            <div class="f-group"><label>From Name</label><input class="f-input" id="s-smtp-from-name" value="SiloSmart Platform"></div>
            <div class="f-group"><label>From Email</label><input class="f-input" id="s-smtp-from-email" value="noreply@silosmart.io" type="email"></div>
            <div class="f-group"><label>Reply-To Email</label><input class="f-input" id="s-smtp-reply" value="support@silosmart.io" type="email"></div>
            <div style="display:flex;gap:.65rem">
              <button class="btn-primary" onclick="saveSettings('email')"><i class="fas fa-save"></i> Save SMTP</button>
              <button class="btn-ghost" onclick="sendTestEmail()"><i class="fas fa-paper-plane"></i> Send Test Email</button>
            </div>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-bell"></i> Email Notification Types</span></div>
            <?php foreach([
              ['Welcome Email on Registration','email-welcome',true],
              ['OTP / Verification Emails','email-otp',true],
              ['Critical Alert Notifications','email-critical',true],
              ['Daily Summary Reports','email-daily',false],
              ['Payment Receipts','email-receipts',true],
              ['Plan Expiry Reminders','email-expiry',true],
              ['Password Reset Emails','email-reset',true],
              ['New User Joined Organisation','email-newuser',false],
              ['Support Ticket Updates','email-tickets',true],
              ['System Health Alerts','email-health',true],
              ['Backup Completion Reports','email-backup',false],
              ['Marketing / Feature Updates','email-marketing',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?> id="<?=$id?>"><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('email-notif')"><i class="fas fa-save"></i> Save Email Notifications</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- SECURITY -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-security">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-lock"></i> Authentication</span></div>
            <?php foreach([
              ['Session Lifetime (seconds)','sec-session','86400'],
              ['Max Login Attempts Before Lock','sec-max-attempts','5'],
              ['Account Lock Duration (minutes)','sec-lock-duration','15'],
              ['OTP Expiry (minutes)','sec-otp-expiry','5'],
              ['Minimum Password Length','sec-min-password','8'],
              ['Password Reset Token Expiry (hours)','sec-reset-expiry','2'],
            ] as [$label,$id,$val]): ?>
            <div class="f-group"><label><?=$label?></label><input class="f-input" type="number" id="<?=$id?>" value="<?=$val?>"></div>
            <?php endforeach; ?>
            <?php foreach([
              ['Require OTP for All Users','sec-req-otp',true],
              ['Force 2FA for Admin Roles','sec-force-2fa',false],
              ['Enable Facial Recognition Login','sec-facial',true],
              ['Background Camera Snapshots','sec-camera',true],
              ['Block Tor / VPN IPs','sec-block-vpn',false],
              ['Log All User Activity','sec-log-all',true],
              ['Require Strong Passwords','sec-strong-pass',true],
              ['Enable IP Whitelist Mode','sec-ip-whitelist',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('security')"><i class="fas fa-save"></i> Save Security</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-key"></i> API & Access Keys</span></div>
            <div class="f-group"><label>Platform Master API Key</label>
              <div style="display:flex;gap:.5rem">
                <input class="f-input" type="password" id="s-api-key" value="sk_live_silosmart_xxxxxxxxxxxxxxxx" style="flex:1">
                <button class="btn-ghost" onclick="regenerateApiKey()" title="Regenerate" style="padding:.5rem .75rem"><i class="fas fa-sync"></i></button>
                <button class="btn-ghost" onclick="document.getElementById('s-api-key').type=document.getElementById('s-api-key').type==='password'?'text':'password'" style="padding:.5rem .75rem"><i class="fas fa-eye"></i></button>
              </div>
            </div>
            <div class="f-group"><label>Webhook Secret</label><input class="f-input" type="password" id="s-webhook-secret" placeholder="Webhook signing secret"></div>
            <div class="f-group"><label>Allowed Origins (CORS)</label><textarea class="f-input" rows="3" id="s-cors" placeholder="https://yourdomain.com&#10;https://app.yourdomain.com"></textarea></div>
            <div class="f-group"><label>IP Whitelist (one per line)</label><textarea class="f-input" rows="3" id="s-ip-whitelist" placeholder="Leave empty to allow all IPs"></textarea></div>
            <div class="f-group"><label>API Rate Limit (requests/minute)</label><input class="f-input" type="number" id="s-api-rate" value="100"></div>
            <button class="btn-primary" onclick="saveSettings('api')"><i class="fas fa-save"></i> Save API Settings</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-shield-virus"></i> Threat Detection</span></div>
            <?php foreach([
              ['Auto-block IPs with 10+ failed logins','threat-brute',true],
              ['Block suspicious user agents','threat-ua',false],
              ['Enable SQL injection detection','threat-sqli',true],
              ['Enable XSS attempt detection','threat-xss',true],
              ['Send alert on failed admin login','threat-admin',true],
              ['Enable honeypot fields','threat-honeypot',true],
              ['Rate-limit OTP requests','threat-otp-rate',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <div class="f-group" style="margin-top:1rem"><label>Currently Blocked IPs</label>
              <textarea class="f-input" rows="4" id="blocked-ips" placeholder="No IPs currently blocked" readonly></textarea>
            </div>
            <div style="display:flex;gap:.65rem">
              <button class="btn-primary" onclick="saveSettings('threats')"><i class="fas fa-save"></i> Save Threat Settings</button>
              <button class="btn-danger" onclick="showToast('All blocked IPs cleared','ok')"><i class="fas fa-trash"></i> Clear Blocked IPs</button>
            </div>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-certificate"></i> SSL & Headers</span></div>
            <?php foreach([
              ['Force HTTPS Redirect','ssl-force',true],
              ['Enable HSTS Header','ssl-hsts',false],
              ['Enable Content Security Policy','ssl-csp',false],
              ['Enable X-Frame-Options: DENY','ssl-xframe',true],
              ['Enable X-Content-Type-Options','ssl-xctype',true],
              ['Enable Referrer-Policy Header','ssl-referrer',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <div class="f-group" style="margin-top:1rem"><label>Custom Content-Security-Policy</label>
              <textarea class="f-input" rows="3" placeholder="default-src 'self'; ..."></textarea>
            </div>
            <button class="btn-primary" onclick="saveSettings('ssl')"><i class="fas fa-save"></i> Save SSL & Headers</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- USERS & ROLES -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-users">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-user-plus"></i> Registration Settings</span></div>
            <div class="f-group"><label>Registration Mode</label>
              <select class="f-input" id="reg-mode">
                <option>Open — Anyone can register</option>
                <option>Invite Only — Admin must invite</option>
                <option>Closed — No new registrations</option>
              </select>
            </div>
            <div class="f-group"><label>Default Role for New Users</label>
              <select class="f-input" id="reg-default-role">
                <option value="operator">Operator</option>
                <option value="tenant_admin">Tenant Admin</option>
              </select>
            </div>
            <div class="f-group"><label>Email Verification</label>
              <select class="f-input" id="reg-email-verify">
                <option>Required before login</option>
                <option>Required within 7 days</option>
                <option>Not required</option>
              </select>
            </div>
            <?php foreach([
              ['Require Phone Number on Registration','reg-require-phone',true],
              ['Require National ID on Registration','reg-require-id',true],
              ['Require Facial Photo on Registration','reg-require-face',true],
              ['Auto-approve new organisations','reg-auto-approve',false],
              ['Send welcome email to new users','reg-welcome-email',true],
              ['Allow users to delete own account','reg-self-delete',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('registration')"><i class="fas fa-save"></i> Save Registration</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-users-cog"></i> Role Permissions</span></div>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:1.25rem">Configure what each role can access and modify.</p>
            <?php
            $roles = [
              ['Super Admin','super_admin','Full platform control — cannot be restricted'],
              ['Tenant Admin','tenant_admin','Full control within their organisation'],
              ['Operator','operator','Can view and acknowledge alerts, update tasks'],
              ['Viewer','viewer','Read-only access to dashboards and reports'],
            ];
            $perms = ['View Dashboard','Manage Silos','Add/Edit Users','View Reports','Download Reports','Manage Alerts','Create Tasks','View Billing','Edit Settings','API Access'];
            ?>
            <div style="overflow-x:auto">
              <table style="font-size:.78rem">
                <thead>
                  <tr>
                    <th>Permission</th>
                    <?php foreach($roles as $r): ?><th style="text-align:center"><?=$r[0]?></th><?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                <?php foreach($perms as $i => $perm):
                  $defaults = [
                    [true,true,true,false],   // View Dashboard
                    [true,true,false,false],  // Manage Silos
                    [true,true,false,false],  // Add/Edit Users
                    [true,true,true,true],    // View Reports
                    [true,true,true,false],   // Download Reports
                    [true,true,true,false],   // Manage Alerts
                    [true,true,true,false],   // Create Tasks
                    [true,true,false,false],  // View Billing
                    [true,false,false,false], // Edit Settings
                    [true,true,false,false],  // API Access
                  ];
                ?>
                <tr>
                  <td style="font-weight:500"><?=$perm?></td>
                  <?php foreach($roles as $j => $r):
                    $checked = $defaults[$i][$j] ?? false;
                    $locked  = $j === 0; // Super admin always checked
                  ?>
                  <td style="text-align:center">
                    <input type="checkbox" <?=$checked?'checked':''?> <?=$locked?'disabled':''?>
                           style="accent-color:var(--gold);width:16px;height:16px;cursor:<?=$locked?'not-allowed':'pointer'?>">
                  </td>
                  <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('roles')"><i class="fas fa-save"></i> Save Role Permissions</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- INTEGRATIONS -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-integrations">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fab fa-google"></i> Google OAuth</span></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
              <span style="font-size:.875rem">Enable Google Login</span>
              <label class="toggle-switch"><input type="checkbox" id="oauth-google"><span class="toggle-slider"></span></label>
            </div>
            <div class="f-group"><label>Client ID</label><input class="f-input" id="google-client-id" placeholder="xxx.apps.googleusercontent.com"></div>
            <div class="f-group"><label>Client Secret</label><input class="f-input" type="password" id="google-client-secret" placeholder="Google OAuth secret"></div>
            <div class="f-group"><label>Redirect URI</label><input class="f-input" value="https://ren.is-best.net/auth/google/callback" readonly style="opacity:.65"></div>
            <a href="https://console.cloud.google.com" target="_blank" style="font-size:.78rem;color:var(--gold2);text-decoration:none"><i class="fas fa-external-link-alt"></i> Get credentials at console.cloud.google.com</a>
            <button class="btn-primary" style="margin-top:1rem;width:100%;justify-content:center" onclick="saveSettings('google-oauth')"><i class="fas fa-save"></i> Save Google OAuth</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fab fa-facebook"></i> Facebook OAuth</span></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
              <span style="font-size:.875rem">Enable Facebook Login</span>
              <label class="toggle-switch"><input type="checkbox" id="oauth-facebook"><span class="toggle-slider"></span></label>
            </div>
            <div class="f-group"><label>App ID</label><input class="f-input" id="fb-app-id" placeholder="Facebook App ID"></div>
            <div class="f-group"><label>App Secret</label><input class="f-input" type="password" id="fb-app-secret" placeholder="Facebook App Secret"></div>
            <a href="https://developers.facebook.com" target="_blank" style="font-size:.78rem;color:#1877F2;text-decoration:none"><i class="fas fa-external-link-alt"></i> developers.facebook.com</a>
            <button class="btn-primary" style="margin-top:1rem;width:100%;justify-content:center" onclick="saveSettings('facebook-oauth')"><i class="fas fa-save"></i> Save Facebook OAuth</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-map-marker-alt"></i> Maps & Location</span></div>
            <div class="f-group"><label>Google Maps API Key</label><input class="f-input" type="password" id="maps-google-key" placeholder="Google Maps API key"></div>
            <div class="f-group"><label>Mapbox API Key</label><input class="f-input" type="password" id="maps-mapbox-key" placeholder="Mapbox public token"></div>
            <div class="f-group"><label>Default Map Provider</label>
              <select class="f-input" id="maps-provider">
                <option value="leaflet">Leaflet / OpenStreetMap (free)</option>
                <option value="google">Google Maps</option>
                <option value="mapbox">Mapbox</option>
              </select>
            </div>
            <div class="f-group"><label>Default Map Center (Latitude)</label><input class="f-input" type="number" step="any" id="maps-lat" value="-1.2921" placeholder="-1.2921"></div>
            <div class="f-group"><label>Default Map Center (Longitude)</label><input class="f-input" type="number" step="any" id="maps-lng" value="36.8219" placeholder="36.8219"></div>
            <button class="btn-primary" onclick="saveSettings('maps')"><i class="fas fa-save"></i> Save Maps Settings</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-chart-bar"></i> Analytics & Tracking</span></div>
            <div class="f-group"><label>Google Analytics 4 — Measurement ID</label><input class="f-input" id="analytics-ga4" placeholder="G-XXXXXXXXXX"></div>
            <div class="f-group"><label>Google Tag Manager ID</label><input class="f-input" id="analytics-gtm" placeholder="GTM-XXXXXXX"></div>
            <div class="f-group"><label>Facebook Pixel ID</label><input class="f-input" id="analytics-pixel" placeholder="Facebook Pixel ID"></div>
            <div class="f-group"><label>Hotjar Site ID</label><input class="f-input" id="analytics-hotjar" placeholder="Hotjar tracking ID"></div>
            <?php foreach([
              ['Enable Google Analytics','analytics-ga-on',false],
              ['Enable Hotjar Session Recording','analytics-hotjar-on',false],
              ['Cookie Consent Required','analytics-cookie',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('analytics')"><i class="fas fa-save"></i> Save Analytics</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- AI & CHATBOT -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-ai">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-brain"></i> AI Prediction Engine</span></div>
            <div class="f-group"><label>AI Provider</label>
              <select class="f-input" id="ai-provider">
                <option value="builtin">Built-in ML Model (default)</option>
                <option value="openai">OpenAI GPT-4o</option>
                <option value="gemini">Google Gemini Pro</option>
                <option value="claude">Anthropic Claude</option>
                <option value="custom">Custom API Endpoint</option>
              </select>
            </div>
            <div class="f-group"><label>OpenAI API Key</label><input class="f-input" type="password" id="ai-openai-key" placeholder="sk-..."></div>
            <div class="f-group"><label>Google Gemini API Key</label><input class="f-input" type="password" id="ai-gemini-key" placeholder="Gemini API key"></div>
            <div class="f-group"><label>Custom AI Endpoint URL</label><input class="f-input" id="ai-custom-url" placeholder="https://your-ai-api.com/predict"></div>
            <div class="f-group"><label>Prediction Horizon (hours)</label><input class="f-input" type="number" id="ai-horizon" value="48"></div>
            <div class="f-group"><label>Minimum Data Points for Prediction</label><input class="f-input" type="number" id="ai-min-data" value="720"></div>
            <div class="f-group"><label>Confidence Threshold (%)</label><input class="f-input" type="number" id="ai-confidence" value="75" min="50" max="99"></div>
            <?php foreach([
              ['Enable Spoilage Risk Prediction','ai-spoilage',true],
              ['Enable Harvest Window Prediction','ai-harvest',true],
              ['Enable Maintenance Prediction','ai-maintenance',true],
              ['Enable Anomaly Detection','ai-anomaly',true],
              ['Auto-create Alerts from AI Predictions','ai-auto-alerts',false],
              ['Show AI Confidence Scores to Tenants','ai-show-confidence',true],
              ['Enable AI Market Price Suggestions','ai-market',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?> id="<?=$id?>"><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('ai')"><i class="fas fa-save"></i> Save AI Settings</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-robot"></i> SiloBot Chatbot</span></div>
            <div class="f-group"><label>Chatbot Name</label><input class="f-input" id="bot-name" value="SiloBot"></div>
            <div class="f-group"><label>Bot Avatar Icon</label>
              <select class="f-input" id="bot-avatar">
                <option>🤖 Robot</option><option>🌾 Grain</option><option>📊 Chart</option><option>🏭 Factory</option>
              </select>
            </div>
            <div class="f-group"><label>Welcome Message</label><textarea class="f-input" id="bot-welcome" rows="3">Hi! I'm SiloBot. Ask me anything about your silo operations, sensor readings, or inventory status.</textarea></div>
            <div class="f-group"><label>System Prompt (AI context)</label><textarea class="f-input" id="bot-prompt" rows="4" placeholder="You are SiloBot, an intelligent assistant for the SiloSmart platform. Help users manage their grain silos..."></textarea></div>
            <div class="f-group"><label>Chatbot Position</label>
              <select class="f-input" id="bot-position">
                <option>Bottom Right</option><option>Bottom Left</option><option>Top Right</option>
              </select>
            </div>
            <?php foreach([
              ['Enable Chatbot on Tenant Dashboard','bot-dashboard',true],
              ['Enable Chatbot for Operators','bot-operators',false],
              ['Allow Chatbot to Create Tasks','bot-create-tasks',false],
              ['Allow Chatbot to Acknowledge Alerts','bot-ack-alerts',false],
              ['Log All Chatbot Conversations','bot-log',true],
              ['Show Typing Indicator','bot-typing',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('chatbot')"><i class="fas fa-save"></i> Save Chatbot</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- SOCIAL -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-social">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-share-square"></i> Social Media Links</span></div>
            <?php foreach([
              ['LinkedIn','fab fa-linkedin','#0A66C2'],
              ['Twitter / X','fab fa-twitter','#1DA1F2'],
              ['Facebook','fab fa-facebook','#1877F2'],
              ['Instagram','fab fa-instagram','#E1306C'],
              ['YouTube','fab fa-youtube','#FF0000'],
              ['WhatsApp Business','fab fa-whatsapp','#25D366'],
            ] as [$name,$icon,$color]): ?>
            <div class="f-group">
              <label><i class="<?=$icon?>" style="color:<?=$color?>"></i> <?=$name?> URL</label>
              <input class="f-input" placeholder="https://<?=strtolower(explode(' ',$name)[0])?>.com/silosmart">
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" onclick="saveSettings('social-links')"><i class="fas fa-save"></i> Save Links</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-share-alt"></i> Social Login Providers</span></div>
            <?php foreach([
              ['Google','fab fa-google','#EA4335','console.cloud.google.com'],
              ['Facebook','fab fa-facebook','#1877F2','developers.facebook.com'],
              ['Microsoft','fab fa-microsoft','#0078D4','portal.azure.com'],
              ['Twitter / X','fab fa-twitter','#1DA1F2','developer.twitter.com'],
              ['Apple','fab fa-apple','#555555','developer.apple.com'],
            ] as [$name,$icon,$color,$url]): ?>
            <div style="border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.85rem;margin-bottom:.75rem">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.65rem">
                <div style="display:flex;align-items:center;gap:.5rem;font-weight:600;font-size:.875rem">
                  <i class="<?=$icon?>" style="color:<?=$color?>"></i> <?=$name?>
                </div>
                <label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                <input class="f-input" placeholder="Client ID" style="font-size:.8rem">
                <input class="f-input" type="password" placeholder="Client Secret" style="font-size:.8rem">
              </div>
              <a href="https://<?=$url?>" target="_blank" style="font-size:.72rem;color:<?=$color?>;text-decoration:none;display:block;margin-top:.4rem">
                <i class="fas fa-external-link-alt"></i> <?=$url?>
              </a>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" onclick="saveSettings('social-login')"><i class="fas fa-save"></i> Save Social Login</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- COMMERCE -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-commerce">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-store"></i> Grain Marketplace</span></div>
            <div class="f-group"><label>Marketplace Name</label><input class="f-input" value="SiloSmart Market" id="market-name"></div>
            <div class="f-group"><label>Commission Rate (%)</label><input class="f-input" type="number" value="5" step="0.5" id="market-commission"></div>
            <div class="f-group"><label>Min. Listing Quantity (Tonnes)</label><input class="f-input" type="number" value="1" id="market-min-qty"></div>
            <div class="f-group"><label>Currency Display</label>
              <select class="f-input"><option>KES — Kenyan Shilling</option><option>USD — US Dollar</option></select>
            </div>
            <div class="f-group"><label>Listing Approval</label>
              <select class="f-input" id="market-approval"><option>Automatic</option><option>Manual Review</option></select>
            </div>
            <?php foreach([
              ['Enable Grain Marketplace','market-grain',true],
              ['Enable Cement / Industrial Market','market-cement',false],
              ['Allow Cross-Border Trading','market-cross',false],
              ['Show Market Prices on Tenant Dashboard','market-prices',true],
              ['Enable Real-Time Price Updates','market-realtime',false],
              ['Require Verified Sellers Only','market-verified',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('commerce')"><i class="fas fa-save"></i> Save Marketplace</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-file-invoice"></i> Invoicing</span></div>
            <div class="f-group"><label>Company Legal Name</label><input class="f-input" value="SiloSmart Technologies Ltd"></div>
            <div class="f-group"><label>KRA PIN</label><input class="f-input" placeholder="P051234567X"></div>
            <div class="f-group"><label>VAT Registration</label><input class="f-input" placeholder="VAT/12345678"></div>
            <div class="f-group"><label>Business Address</label><textarea class="f-input" rows="3" placeholder="Nairobi, Kenya"></textarea></div>
            <div class="f-group"><label>Invoice Footer Text</label><textarea class="f-input" rows="2" placeholder="Thank you for using SiloSmart."></textarea></div>
            <button class="btn-primary" onclick="saveSettings('invoicing')"><i class="fas fa-save"></i> Save Invoicing</button>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- BACKUP & DATA -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-backup">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-database"></i> Automated Backups</span></div>
            <div class="f-group"><label>Backup Frequency</label>
              <select class="f-input" id="backup-freq">
                <option>Daily at 02:00 EAT</option>
                <option>Every 6 hours</option>
                <option>Weekly — Sunday 02:00</option>
                <option>Manual only</option>
              </select>
            </div>
            <div class="f-group"><label>Backup Storage</label>
              <select class="f-input" id="backup-storage">
                <option>Server Local Storage</option>
                <option>Google Drive</option>
                <option>AWS S3</option>
                <option>Dropbox</option>
              </select>
            </div>
            <div class="f-group"><label>Retention (keep last N backups)</label><input class="f-input" type="number" value="14" id="backup-retention"></div>
            <div class="f-group"><label>Google Drive Folder ID</label><input class="f-input" placeholder="Google Drive folder ID for backups"></div>
            <div class="f-group"><label>AWS S3 Bucket Name</label><input class="f-input" placeholder="my-silosmart-backups"></div>
            <?php foreach([
              ['Include Sensor Readings in Backup','backup-sensors',true],
              ['Include Camera Snapshots in Backup','backup-snapshots',false],
              ['Encrypt Backups','backup-encrypt',true],
              ['Send Email on Backup Completion','backup-email',true],
              ['Alert on Backup Failure','backup-alert-fail',true],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <div style="display:flex;gap:.65rem;margin-top:1rem">
              <button class="btn-primary" onclick="saveSettings('backup')"><i class="fas fa-save"></i> Save Backup Settings</button>
              <button class="btn-ghost" onclick="triggerBackup()"><i class="fas fa-download"></i> Run Backup Now</button>
            </div>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-history"></i> Recent Backups</span></div>
            <?php $backups = [
              ['Jun 14, 2026 02:00','24.3 MB','✅ Success','45s'],
              ['Jun 13, 2026 02:00','24.1 MB','✅ Success','43s'],
              ['Jun 12, 2026 02:00','23.8 MB','✅ Success','41s'],
              ['Jun 11, 2026 02:00','23.5 MB','⚠️ Warning','62s'],
              ['Jun 10, 2026 02:00','23.1 MB','✅ Success','40s'],
            ];
            foreach($backups as $b): ?>
            <div style="display:flex;align-items:center;gap:.75rem;padding:.65rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <div style="width:32px;height:32px;border-radius:8px;background:rgba(22,163,74,.12);display:grid;place-items:center;flex-shrink:0;font-size:.8rem;color:#4ADE80"><i class="fas fa-database"></i></div>
              <div style="flex:1">
                <div style="font-size:.82rem;font-weight:600"><?=$b[0]?></div>
                <div style="font-size:.72rem;color:var(--muted)"><?=$b[1]?> &nbsp;·&nbsp; <?=$b[3]?></div>
              </div>
              <span class="badge <?=str_contains($b[2],'Success')?'badge-ok':'badge-warn'?>" style="font-size:.65rem"><?=str_contains($b[2],'Success')?'Success':'Warning'?></span>
              <button class="btn-ghost" style="padding:.3rem .6rem;font-size:.72rem" onclick="showToast('Downloading backup…','ok')"><i class="fas fa-download"></i></button>
            </div>
            <?php endforeach; ?>
            <div class="f-group" style="margin-top:1rem">
              <label>Import / Restore from Backup</label>
              <input type="file" class="f-input" accept=".sql,.gz,.zip" style="padding:.5rem">
            </div>
            <button class="btn-danger" onclick="confirmRestore()"><i class="fas fa-upload"></i> Restore from Backup</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-broom"></i> Data Cleanup</span></div>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:1.25rem">Permanently remove old data to free up storage. <strong style="color:var(--red2)">These actions cannot be undone.</strong></p>
            <?php foreach([
              ['Sensor readings older than 365 days','sensor readings >1yr','badge-warn'],
              ['Activity logs older than 730 days','activity logs >2yr','badge-warn'],
              ['Camera snapshots older than 90 days','snapshots >90 days','badge-red'],
              ['Resolved alerts older than 180 days','old alerts','badge-muted'],
              ['Deleted organisation data','deleted org data','badge-red'],
            ] as [$label,$desc,$cls]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <div>
                <div style="font-size:.85rem;font-weight:500"><?=$label?></div>
              </div>
              <button class="btn-danger" style="font-size:.72rem;padding:.3rem .65rem" onclick="showToast('Cleanup scheduled for tonight 02:00 EAT','ok')">
                <i class="fas fa-trash"></i> Schedule Cleanup
              </button>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-file-export"></i> Data Export</span></div>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:1.25rem">Export platform data for analysis, compliance, or migration.</p>
            <?php foreach([
              ['fa-building','Export All Organisations','organisations'],
              ['fa-users','Export All Users','users'],
              ['fa-database','Export All Silos & Sensors','silos'],
              ['fa-chart-line','Export Sensor Readings (CSV)','sensors'],
              ['fa-credit-card','Export Payment History','payments'],
              ['fa-shield-alt','Export Audit Log','audit'],
            ] as [$ic,$label,$type]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <div style="font-size:.85rem"><i class="fas <?=$ic?>" style="color:var(--gold3);margin-right:.5rem;width:14px"></i><?=$label?></div>
              <button class="btn-ghost" style="font-size:.72rem;padding:.3rem .65rem" onclick="showToast('Exporting <?=$label?>…','ok')">
                <i class="fas fa-download"></i> Export
              </button>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- LEGAL & GDPR -->
      <!-- ══════════════════════════════════════════ -->
      <div class="settings-pane" id="stab-legal">
        <div class="g2">
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-gavel"></i> Legal Documents</span></div>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:1.25rem">These URLs are shown to users during registration, in the footer, and in emails.</p>
            <?php foreach([
              ['Terms of Service URL','legal-tos','https://silosmart.io/terms'],
              ['Privacy Policy URL','legal-privacy','https://silosmart.io/privacy'],
              ['Cookie Policy URL','legal-cookies',''],
              ['Data Processing Agreement (DPA) URL','legal-dpa',''],
              ['Refund Policy URL','legal-refunds','https://silosmart.io/refunds'],
              ['Acceptable Use Policy URL','legal-aup',''],
            ] as [$label,$id,$val]): ?>
            <div class="f-group">
              <label><?=$label?></label>
              <input class="f-input" id="<?=$id?>" value="<?=$val?>" placeholder="https://yourdomain.com/...">
            </div>
            <?php endforeach; ?>
            <div class="f-group"><label>GDPR / Privacy Contact Email</label><input class="f-input" type="email" placeholder="privacy@yourdomain.com"></div>
            <div class="f-group"><label>Data Controller Name</label><input class="f-input" value="SiloSmart Technologies Ltd"></div>
            <div class="f-group"><label>Data Controller Address</label><textarea class="f-input" rows="2" placeholder="Nairobi, Kenya"></textarea></div>
            <button class="btn-primary" onclick="saveSettings('legal')"><i class="fas fa-save"></i> Save Legal Documents</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-user-shield"></i> GDPR & Compliance</span></div>
            <?php foreach([
              ['Require Terms Acceptance on Registration','gdpr-terms',true],
              ['Show Cookie Consent Banner','gdpr-cookie-banner',true],
              ['Enable GDPR Data Export Request','gdpr-export',true],
              ['Enable Account Deletion Request','gdpr-delete',true],
              ['Auto-delete Inactive Accounts (2 years)','gdpr-auto-delete',false],
              ['Anonymise Data on Account Deletion','gdpr-anonymise',true],
              ['Log All Data Access (GDPR Audit)','gdpr-log',false],
              ['Send Data Breach Notification Email','gdpr-breach',true],
              ['Comply with CCPA (California)','gdpr-ccpa',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('gdpr')"><i class="fas fa-save"></i> Save GDPR Settings</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-cookie-bite"></i> Cookie Consent</span></div>
            <div class="f-group"><label>Banner Position</label>
              <select class="f-input" id="cookie-position">
                <option>Bottom Bar</option><option>Bottom Left Popup</option><option>Bottom Right Popup</option><option>Top Bar</option>
              </select>
            </div>
            <div class="f-group"><label>Banner Title</label><input class="f-input" value="We use cookies" id="cookie-title"></div>
            <div class="f-group"><label>Banner Message</label><textarea class="f-input" rows="2" id="cookie-msg">We use cookies to improve your experience and for analytics. By continuing, you agree to our Cookie Policy.</textarea></div>
            <div class="f-group"><label>Accept Button Text</label><input class="f-input" value="Accept All" id="cookie-accept"></div>
            <div class="f-group"><label>Decline Button Text</label><input class="f-input" value="Necessary Only" id="cookie-decline"></div>
            <?php foreach([
              ['Necessary Cookies (always on)','cookie-necessary',true],
              ['Analytics Cookies','cookie-analytics',false],
              ['Marketing Cookies','cookie-marketing',false],
            ] as [$label,$id,$checked]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <span style="font-size:.875rem"><?=$label?></span>
              <label class="toggle-switch"><input type="checkbox" <?=$checked?'checked':''?> <?=$label==='Necessary Cookies (always on)'?'disabled':''?>><span class="toggle-slider"></span></label>
            </div>
            <?php endforeach; ?>
            <button class="btn-primary" style="margin-top:1rem" onclick="saveSettings('cookies')"><i class="fas fa-save"></i> Save Cookie Settings</button>
          </div>
          <div class="card">
            <div class="card-hdr"><span class="card-title"><i class="fas fa-file-contract"></i> Compliance Checklist</span></div>
            <?php foreach([
              [true,'Privacy Policy published and linked'],
              [true,'Terms of Service published and linked'],
              [true,'Cookie consent banner active'],
              [false,'Cookie Policy URL configured'],
              [true,'GDPR data export enabled'],
              [true,'Account deletion request enabled'],
              [false,'DPA (Data Processing Agreement) configured'],
              [true,'SSL certificate active'],
              [false,'Annual security audit scheduled'],
              [false,'Data breach response plan documented'],
            ] as [$done,$label]): ?>
            <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid rgba(212,160,23,.07)">
              <div style="width:20px;height:20px;border-radius:50%;background:<?=$done?'rgba(22,163,74,.15)':'rgba(220,38,38,.12)'?>;display:grid;place-items:center;flex-shrink:0;font-size:.65rem;color:<?=$done?'#4ADE80':'#FCA5A5'?>">
                <i class="fas <?=$done?'fa-check':'fa-times'?>"></i>
              </div>
              <span style="font-size:.85rem;color:<?=$done?'rgba(255,255,255,.82)':'rgba(255,255,255,.5)'?>"><?=$label?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div><!-- /panel-settings -->


    <!-- ══════════════════════════════════════════════════════ -->
    <!-- SUBSCRIPTION PLANS -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-plans">
      <div class="sec-hdr"><div><h1>Subscription Plans</h1><p>Create, edit and manage platform pricing tiers</p></div>
        <button class="btn-primary" onclick="openPlanModal()"><i class="fas fa-plus"></i> New Plan</button></div>
      <div class="g3" style="margin-bottom:1.5rem">
        <div class="card" style="border-color:rgba(74,144,226,.3)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem">
            <div><div style="font-family:var(--font-head);font-size:1.1rem;font-weight:800">Starter</div><div style="font-size:.8rem;color:var(--muted)">Small operations</div></div>
            <span class="pill" style="background:rgba(74,144,226,.12);color:var(--accent)">5 orgs</span>
          </div>
          <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:.5rem">KES 2,999<span style="font-size:.9rem;font-weight:400;color:var(--muted)">/mo</span></div>
          <ul style="list-style:none;font-size:.83rem;color:rgba(255,255,255,.75);display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem">
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Up to 5 silos</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Up to 10 users</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Basic sensor monitoring</li>
            <li><i class="fas fa-times" style="color:var(--muted);margin-right:.4rem"></i>AI Predictions</li>
            <li><i class="fas fa-times" style="color:var(--muted);margin-right:.4rem"></i>M-Pesa billing</li>
          </ul>
          <div style="display:flex;gap:.5rem"><button class="act-btn primary" onclick="editPlan('Starter')">Edit</button><button class="act-btn danger" onclick="showToast('Plan deactivated','warning')">Deactivate</button></div>
        </div>
        <div class="card" style="border-color:rgba(212,160,23,.4)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem">
            <div><div style="font-family:var(--font-head);font-size:1.1rem;font-weight:800">Professional</div><div style="font-size:.8rem;color:var(--muted)">Growing businesses</div></div>
            <span class="pill pill-active">5 orgs</span>
          </div>
          <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--primary);margin-bottom:.5rem">KES 7,999<span style="font-size:.9rem;font-weight:400;color:var(--muted)">/mo</span></div>
          <ul style="list-style:none;font-size:.83rem;color:rgba(255,255,255,.75);display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem">
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Up to 20 silos</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Up to 50 users</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>AI Predictions (48h)</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>M-Pesa native billing</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Excel reports</li>
          </ul>
          <div style="display:flex;gap:.5rem"><button class="act-btn primary" onclick="editPlan('Professional')">Edit</button><button class="act-btn danger" onclick="showToast('Plan deactivated','warning')">Deactivate</button></div>
        </div>
        <div class="card" style="border-color:rgba(155,89,182,.4)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem">
            <div><div style="font-family:var(--font-head);font-size:1.1rem;font-weight:800">Enterprise</div><div style="font-size:.8rem;color:var(--muted)">Large operators</div></div>
            <span class="pill" style="background:rgba(155,89,182,.12);color:var(--purple)">2 orgs</span>
          </div>
          <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--purple);margin-bottom:.5rem">KES 19,999<span style="font-size:.9rem;font-weight:400;color:var(--muted)">/mo</span></div>
          <ul style="list-style:none;font-size:.83rem;color:rgba(255,255,255,.75);display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem">
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Unlimited silos & users</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Full AI suite</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Camera forensics</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Dedicated support</li>
            <li><i class="fas fa-check" style="color:var(--success);margin-right:.4rem"></i>Custom integrations</li>
          </ul>
          <div style="display:flex;gap:.5rem"><button class="act-btn primary" onclick="editPlan('Enterprise')">Edit</button><button class="act-btn danger" onclick="showToast('Plan deactivated','warning')">Deactivate</button></div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- ALL USERS -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-users">
      <div class="sec-hdr"><div><h1>All Users</h1><p>Global user management across all tenant organisations</p></div>
        <button class="btn-primary" onclick="showToast('Invitation email form opening…','info')"><i class="fas fa-user-plus"></i> Invite User</button></div>
      <div class="card" style="margin-bottom:1rem;padding:.85rem 1.25rem">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
          <input id="userSearch" oninput="filterUsers()" placeholder="Search name or email…" style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.5rem .85rem;font-size:.85rem;outline:none;width:220px">
          <select id="userOrgFilter" onchange="filterUsers()" style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.5rem .75rem;font-size:.85rem;outline:none">
            <option value="">All Organisations</option><option>AgriStore Kenya</option><option>Coastal Grain</option><option>Nairobi Cement</option>
          </select>
          <select id="userRoleFilter" onchange="filterUsers()" style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.5rem .75rem;font-size:.85rem;outline:none">
            <option value="">All Roles</option><option>super_admin</option><option>tenant_admin</option><option>operator</option><option>viewer</option>
          </select>
        </div>
      </div>
      <div class="card"><table id="usersTable">
        <thead><tr><th>User</th><th>Organisation</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <tr><td><div style="font-weight:600">Super Admin</div><div style="font-size:.73rem;color:var(--muted)">admin@silosmart.io</div></td><td>—</td><td><span class="pill" style="background:#fee2e2;color:var(--danger)">super_admin</span></td><td style="font-size:.8rem;color:var(--muted)">Just now</td><td><span class="pill pill-active">Active</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Edit Super Admin…','info')">Edit</button></div></td></tr>
          <tr><td><div style="font-weight:600">James Mwangi</div><div style="font-size:.73rem;color:var(--muted)">james@agristore.co.ke</div></td><td>AgriStore Kenya</td><td><span class="pill pill-active">tenant_admin</span></td><td style="font-size:.8rem;color:var(--muted)">2h ago</td><td><span class="pill pill-active">Active</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Opening editor for James Mwangi','info')">Edit</button><button class="act-btn" onclick="impersonate(this,'James')">Login As</button><button class="act-btn danger" onclick="suspendUser(this)">Suspend</button></div></td></tr>
          <tr><td><div style="font-weight:600">Grace Akinyi</div><div style="font-size:.73rem;color:var(--muted)">grace@agristore.co.ke</div></td><td>AgriStore Kenya</td><td><span class="pill" style="background:rgba(74,144,226,.12);color:var(--accent)">operator</span></td><td style="font-size:.8rem;color:var(--muted)">5h ago</td><td><span class="pill pill-active">Active</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Opening editor for Grace Akinyi','info')">Edit</button><button class="act-btn danger" onclick="suspendUser(this)">Suspend</button></div></td></tr>
          <tr><td><div style="font-weight:600">Amina Odhiambo</div><div style="font-size:.73rem;color:var(--muted)">amina@coastalgrain.co.ke</div></td><td>Coastal Grain</td><td><span class="pill pill-active">tenant_admin</span></td><td style="font-size:.8rem;color:var(--muted)">1h ago</td><td><span class="pill pill-active">Active</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Opening editor for Amina Odhiambo','info')">Edit</button><button class="act-btn" onclick="impersonate(this,'Amina')">Login As</button><button class="act-btn danger" onclick="suspendUser(this)">Suspend</button></div></td></tr>
          <tr><td><div style="font-weight:600">Peter Kamau</div><div style="font-size:.73rem;color:var(--muted)">peter@nairobcement.co.ke</div></td><td>Nairobi Cement</td><td><span class="pill" style="background:rgba(74,144,226,.12);color:var(--accent)">operator</span></td><td style="font-size:.8rem;color:var(--muted)">Yesterday</td><td><span class="pill pill-active">Active</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Opening editor for Peter Kamau','info')">Edit</button><button class="act-btn danger" onclick="suspendUser(this)">Suspend</button></div></td></tr>
          <tr><td><div style="font-weight:600">David Otieno</div><div style="font-size:.73rem;color:var(--muted)">david@coastalgrain.co.ke</div></td><td>Coastal Grain</td><td><span class="pill" style="background:rgba(136,153,170,.12);color:var(--muted)">viewer</span></td><td style="font-size:.8rem;color:var(--muted)">3d ago</td><td><span class="pill pill-suspended">Suspended</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="activateUser(this)">Activate</button><button class="act-btn danger" onclick="showToast('User deleted','warning')">Delete</button></div></td></tr>
        </tbody>
      </table></div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- M-PESA PAYMENTS -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-payments">
      <div class="sec-hdr"><div><h1>M-Pesa Payments</h1><p>Full STK Push payment history and confirmation status</p></div>
        <button class="btn-primary" onclick="showToast('STK Push initiated — check your phone','info')"><i class="fas fa-mobile-alt"></i> Manual STK Push</button></div>
      <div class="g4" style="margin-bottom:1.25rem">
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-coins"></i></div><div><div class="kpi-lbl">Total Collected (Month)</div><div class="kpi-val" id="mpesa-total">KES 147,960</div><div class="kpi-chg chg-up">↑ 18% vs last month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-blue"><i class="fas fa-check-circle"></i></div><div><div class="kpi-lbl">Successful Transactions</div><div class="kpi-val" id="mpesa-success">24</div><div class="kpi-chg chg-up">This month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-clock"></i></div><div><div class="kpi-lbl">Pending</div><div class="kpi-val" id="mpesa-pending">2</div><div class="kpi-chg chg-nu">Awaiting confirmation</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-red"><i class="fas fa-times-circle"></i></div><div><div class="kpi-lbl">Failed</div><div class="kpi-val" id="mpesa-failed">1</div><div class="kpi-chg chg-dn">Insufficient funds</div></div></div>
      </div>
      <div class="card"><div class="card-hdr"><span class="card-title"><i class="fas fa-list"></i> Transaction Log</span>
        <button class="act-btn primary" onclick="exportMpesaLog()"><i class="fas fa-download"></i> Export</button></div>
        <table><thead><tr><th>Date/Time</th><th>Organisation</th><th>Phone</th><th>Amount (KES)</th><th>MpesaRef</th><th>Plan</th><th>Status</th><th>Action</th></tr></thead>
        <tbody id="paymentsTable">
          <tr><td style="font-size:.8rem">2026-05-29 10:14</td><td>AgriStore Kenya</td><td>+254711111111</td><td style="color:var(--success);font-weight:700">7,999</td><td style="font-family:monospace;font-size:.78rem">RGS7X4QJ1P</td><td>Professional</td><td><span class="pill pill-active">✓ Paid</span></td><td><button class="act-btn" onclick="showToast('Receipt sent','info')">Receipt</button></td></tr>
          <tr><td style="font-size:.8rem">2026-05-28 14:32</td><td>Coastal Grain</td><td>+254722345678</td><td style="color:var(--success);font-weight:700">19,999</td><td style="font-family:monospace;font-size:.78rem">PQT9W2MK8A</td><td>Enterprise</td><td><span class="pill pill-active">✓ Paid</span></td><td><button class="act-btn" onclick="showToast('Receipt sent','info')">Receipt</button></td></tr>
          <tr><td style="font-size:.8rem">2026-05-27 09:05</td><td>Lakeside Agro</td><td>+254733456789</td><td style="color:var(--warning);font-weight:700">2,999</td><td style="font-family:monospace;font-size:.78rem">—</td><td>Starter</td><td><span class="pill pill-trial">⏳ Pending</span></td><td><button class="act-btn" onclick="retryMpesa(this)">Retry</button></td></tr>
          <tr><td style="font-size:.8rem">2026-05-26 16:47</td><td>Nairobi Cement</td><td>+254700111222</td><td style="color:var(--success);font-weight:700">7,999</td><td style="font-family:monospace;font-size:.78rem">HBN3R5TU7Z</td><td>Professional</td><td><span class="pill pill-active">✓ Paid</span></td><td><button class="act-btn" onclick="showToast('Receipt sent','info')">Receipt</button></td></tr>
          <tr><td style="font-size:.8rem">2026-05-25 11:22</td><td>Mombasa Port</td><td>+254744567890</td><td style="color:var(--danger);font-weight:700">2,999</td><td style="font-family:monospace;font-size:.78rem">—</td><td>Starter</td><td><span class="pill pill-suspended">✗ Failed</span></td><td><button class="act-btn" onclick="retryMpesa(this)">Retry</button></td></tr>
        </tbody></table>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- AUDIT TRAIL -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-audit">
      <div class="sec-hdr">
        <div><h1>Audit Trail</h1><p>Complete forensic activity log with device and IP tracking</p></div>
        <div style="display:flex;gap:.65rem">
          <select style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:.8rem;padding:.4rem .7rem;outline:none" id="auditFilter">
            <option value="">All Categories</option>
            <option value="auth">Auth</option>
            <option value="silos">Silos</option>
            <option value="alerts">Alerts</option>
            <option value="billing">Billing</option>
            <option value="system">System</option>
          </select>
          <button class="btn-primary" style="padding:.5rem 1rem;font-size:.82rem" onclick="downloadAuditLog()"><i class="fas fa-download"></i> Export Log</button>
        </div>
      </div>
      <div class="card">
        <div class="card-hdr"><span class="card-title"><i class="fas fa-shield-alt"></i> Activity Log</span>
          <span style="font-size:.75rem;color:var(--muted)">Showing last 500 entries</span>
        </div>
        <div style="overflow-x:auto">
        <table>
          <thead><tr><th>Time</th><th>User</th><th>Organisation</th><th>Action</th><th>Category</th><th>Description</th><th>IP Address</th><th>Device</th></tr></thead>
          <tbody>
          <?php
          $audit_logs = [
            ['Today 09:14','Renson Njehia','—','login','Auth','Super admin logged in','41.107.x.x','Chrome / Windows'],
            ['Today 07:25','James Mwangi','AgriStore Kenya','create','Tasks','Created task: Emergency Temp Check','197.248.x.x','Firefox / Android'],
            ['Today 07:22','System','—','alert','Alerts','Critical alert: Silo Epsilon 38.5°C','—','Auto'],
            ['Yesterday 16:40','James Mwangi','AgriStore Kenya','update','Silos','Updated Silo Beta commodity type','197.248.x.x','Chrome / Windows'],
            ['Yesterday 14:15','Grace Akinyi','AgriStore Kenya','resolve','Alerts','Resolved humidity warning Silo Alpha','197.248.x.x','Safari / iOS'],
            ['Yesterday 08:02','Grace Akinyi','AgriStore Kenya','login','Auth','Operator logged in','197.248.x.x','Safari / Android'],
            ['Jun 8 11:30','Renson Njehia','—','create','Orgs','Created organisation: Coastal Grain Ltd','41.107.x.x','Chrome / Windows'],
            ['Jun 8 10:15','Renson Njehia','—','update','Plans','Updated Professional plan price to KES 12,999','41.107.x.x','Chrome / Windows'],
            ['Jun 7 16:00','System','—','billing','Billing','Auto-renewal: AgriStore Kenya — KES 7,999','—','System'],
            ['Jun 7 09:30','James Mwangi','AgriStore Kenya','create','Silos','Added new silo: Silo Epsilon (S-005)','197.248.x.x','Chrome / Windows'],
          ];
          foreach($audit_logs as $log):
            $cat_col = match($log[3]){
              'login'=>'var(--blue2)','create'=>'var(--green2)','update'=>'var(--gold2)',
              'alert'=>'var(--red2)','resolve'=>'var(--green2)','billing'=>'var(--gold2)',
              default=>'var(--muted)'
            };
          ?>
          <tr>
            <td style="font-size:.75rem;color:var(--muted);white-space:nowrap"><?=$log[0]?></td>
            <td><strong style="font-size:.82rem"><?=$log[1]?></strong></td>
            <td style="font-size:.8rem;color:var(--muted)"><?=$log[2]?></td>
            <td><span style="font-size:.7rem;font-weight:700;color:<?=$cat_col?>;text-transform:uppercase"><?=$log[3]?></span></td>
            <td><span class="badge badge-muted" style="font-size:.65rem"><?=$log[4]?></span></td>
            <td style="font-size:.82rem;max-width:280px"><?=$log[5]?></td>
            <td><code style="font-size:.72rem;color:var(--muted)"><?=$log[6]?></code></td>
            <td style="font-size:.75rem;color:var(--muted)"><?=$log[7]?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- CAMERA SNAPSHOTS -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-snapshots">
      <div class="sec-hdr">
        <div><h1>Camera Snapshots</h1><p>Forensic facial verification images captured during login</p></div>
        <div style="display:flex;gap:.65rem;align-items:center">
          <select style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:.8rem;padding:.4rem .7rem;outline:none">
            <option>All Organisations</option>
            <option>AgriStore Kenya</option>
            <option>Coastal Grain</option>
          </select>
          <input type="date" style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:.8rem;padding:.4rem .7rem;outline:none" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="card" style="margin-bottom:1.25rem">
        <div class="card-hdr"><span class="card-title"><i class="fas fa-info-circle"></i> About Facial Snapshots</span></div>
        <p style="font-size:.85rem;color:rgba(255,255,255,.7);line-height:1.6">
          SiloSmart captures a facial photo during login and compares it to the registered baseline for forensic tracking.
          All snapshots are retained for <strong style="color:var(--gold2)">90 days</strong> per data retention policy.
          <a href="#" onclick="showPanel('settings');switchSettingsTab('security');return false" style="color:var(--gold2);text-decoration:none">Manage retention policy →</a>
        </p>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
        <?php
        $snaps = [
          ['James Mwangi','AgriStore Kenya','Today 09:14','✅ Match','#16A34A','JM'],
          ['Grace Akinyi','AgriStore Kenya','Today 08:02','✅ Match','#16A34A','GA'],
          ['James Mwangi','AgriStore Kenya','Yesterday 09:30','✅ Match','#16A34A','JM'],
          ['Grace Akinyi','AgriStore Kenya','Jun 8 08:15','⚠️ Low confidence','#D4A017','GA'],
          ['James Mwangi','AgriStore Kenya','Jun 7 09:02','✅ Match','#16A34A','JM'],
          ['Unknown','Coastal Grain','Jun 6 22:15','❌ No match','#DC2626','??'],
          ['Grace Akinyi','AgriStore Kenya','Jun 6 08:30','✅ Match','#16A34A','GA'],
          ['James Mwangi','AgriStore Kenya','Jun 5 09:10','✅ Match','#16A34A','JM'],
        ];
        foreach($snaps as $s):
        ?>
        <div class="card" style="padding:.85rem;cursor:pointer;transition:all .25s" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor=''">
          <div style="height:100px;background:linear-gradient(135deg,var(--navy2),var(--navy));border-radius:9px;display:grid;place-items:center;margin-bottom:.75rem;border:1px solid var(--border);position:relative;overflow:hidden">
            <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--navy2));display:grid;place-items:center;font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:var(--navy)"><?=$s[4]?></div>
            <div style="position:absolute;top:.4rem;right:.4rem;background:<?=$s[3]?>22;border:1px solid <?=$s[3]?>44;border-radius:4px;padding:.1rem .3rem;font-size:.6rem;font-weight:700;color:<?=$s[3]?>"><?=strpos($s[3],'16A')!==false?'MATCH':(strpos($s[3],'D4A')!==false?'LOW':'FAIL')?></div>
          </div>
          <div style="font-size:.82rem;font-weight:600;margin-bottom:.15rem"><?=$s[0]?></div>
          <div style="font-size:.72rem;color:var(--muted);margin-bottom:.25rem"><?=$s[1]?></div>
          <div style="font-size:.7rem;color:var(--muted)"><?=$s[2]?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- SUPPORT TICKETS -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-tickets">
      <div class="sec-hdr"><div><h1>Support Tickets</h1><p>Manage support requests from all tenant organisations</p></div>
        <button class="btn-primary" onclick="showToast('New ticket form coming soon','info')"><i class="fas fa-plus"></i> New Ticket</button></div>
      <div class="g3" style="margin-bottom:1.25rem">
        <div class="kpi"><div class="kpi-ico ico-red"><i class="fas fa-exclamation-circle"></i></div><div><div class="kpi-lbl">Open Tickets</div><div class="kpi-val" id="tickets-open">3</div><div class="kpi-chg chg-dn">1 urgent</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-clock"></i></div><div><div class="kpi-lbl">Avg Response Time</div><div class="kpi-val">4.2h</div><div class="kpi-chg chg-up">↓ improved</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-check-double"></i></div><div><div class="kpi-lbl">Resolved (Month)</div><div class="kpi-val">18</div><div class="kpi-chg chg-up">↑ 3 vs last month</div></div></div>
      </div>
      <div class="card"><table id="ticketsTable">
        <thead><tr><th>#</th><th>Subject</th><th>Organisation</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
          <tr><td style="font-family:monospace;color:var(--muted)">#1042</td><td><div style="font-weight:600">M-Pesa payment not confirming</div><div style="font-size:.75rem;color:var(--muted)">STK Push sent but no callback received</div></td><td>Lakeside Agro</td><td><span class="pill" style="background:#fee2e2;color:var(--danger)">Urgent</span></td><td><span class="pill pill-trial">Open</span></td><td style="font-size:.8rem;color:var(--muted)">2h ago</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="openTicket(1042)">Reply</button><button class="act-btn" onclick="resolveTicket(this,1042)">Resolve</button></div></td></tr>
          <tr><td style="font-family:monospace;color:var(--muted)">#1041</td><td><div style="font-weight:600">Sensor offline — Silo Delta</div><div style="font-size:.75rem;color:var(--muted)">Level radar not reporting since maintenance</div></td><td>AgriStore Kenya</td><td><span class="pill" style="background:rgba(245,166,35,.12);color:var(--warning)">High</span></td><td><span class="pill pill-trial">Open</span></td><td style="font-size:.8rem;color:var(--muted)">5h ago</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="openTicket(1041)">Reply</button><button class="act-btn" onclick="resolveTicket(this,1041)">Resolve</button></div></td></tr>
          <tr><td style="font-family:monospace;color:var(--muted)">#1040</td><td><div style="font-weight:600">Excel report not downloading</div><div style="font-size:.75rem;color:var(--muted)">500 error when generating monthly report</div></td><td>Nairobi Cement</td><td><span class="pill" style="background:rgba(74,144,226,.12);color:var(--accent)">Medium</span></td><td><span class="pill pill-trial">Open</span></td><td style="font-size:.8rem;color:var(--muted)">1d ago</td><td><div class="tbl-actions"><button class="act-btn primary" onclick="openTicket(1040)">Reply</button><button class="act-btn" onclick="resolveTicket(this,1040)">Resolve</button></div></td></tr>
          <tr style="opacity:.5"><td style="font-family:monospace;color:var(--muted)">#1039</td><td><div style="font-weight:600">How to add new silo?</div></td><td>Lakeside Agro</td><td><span class="pill" style="background:var(--bg);color:var(--muted)">Low</span></td><td><span class="pill pill-active">Resolved</span></td><td style="font-size:.8rem;color:var(--muted)">2d ago</td><td><div class="tbl-actions"><button class="act-btn">View</button></div></td></tr>
        </tbody></table>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- SYSTEM HEALTH -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-health">
      <div class="sec-hdr"><div><h1>System Health</h1><p>Live infrastructure monitoring — auto-refreshing every 10s</p></div>
        <div class="live-indicator" style="font-size:.8rem"><div class="live-dot"></div><span id="healthLastUpdate">Updating…</span></div></div>
      <div class="g4" style="margin-bottom:1.25rem" id="healthKPIs">
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-server"></i></div><div><div class="kpi-lbl">API Server</div><div class="kpi-val" id="h-api" style="font-size:1.1rem">—</div><div class="kpi-chg" id="h-api-ms"></div></div></div>
        <div class="kpi"><div class="kpi-ico ico-blue"><i class="fas fa-database"></i></div><div><div class="kpi-lbl">Database</div><div class="kpi-val" id="h-db" style="font-size:1.1rem">—</div><div class="kpi-chg" id="h-db-ms"></div></div></div>
        <div class="kpi"><div class="kpi-ico ico-purple"><i class="fas fa-broadcast-tower"></i></div><div><div class="kpi-lbl">MQTT Broker</div><div class="kpi-val" id="h-mqtt" style="font-size:1.1rem">—</div><div class="kpi-chg" id="h-mqtt-ms"></div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-mobile-alt"></i></div><div><div class="kpi-lbl">M-Pesa API</div><div class="kpi-val" id="h-mpesa" style="font-size:1.1rem">—</div><div class="kpi-chg" id="h-mpesa-ms"></div></div></div>
      </div>
      <div class="g2" style="margin-bottom:1.25rem">
        <div class="card"><div class="card-hdr"><span class="card-title"><i class="fas fa-chart-line"></i> Response Times (last 12 checks)</span></div><div class="chart-wrap"><canvas id="healthChart"></canvas></div></div>
        <div class="card"><div class="card-hdr"><span class="card-title"><i class="fas fa-hdd"></i> Resource Usage</span></div>
          <div style="display:flex;flex-direction:column;gap:1.25rem;padding:.5rem 0">
            <div><div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:.4rem"><span>CPU Usage</span><span id="cpu-val" style="color:var(--primary)">—</span></div><div style="background:rgba(255,255,255,.06);border-radius:50px;height:8px"><div id="cpu-bar" style="height:8px;border-radius:50px;background:var(--primary);transition:width .8s ease;width:0%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:.4rem"><span>Memory</span><span id="mem-val" style="color:var(--accent)">—</span></div><div style="background:rgba(255,255,255,.06);border-radius:50px;height:8px"><div id="mem-bar" style="height:8px;border-radius:50px;background:var(--blue);transition:width .8s ease;width:0%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:.4rem"><span>Disk Usage</span><span id="disk-val" style="color:var(--warning)">—</span></div><div style="background:rgba(255,255,255,.06);border-radius:50px;height:8px"><div id="disk-bar" style="height:8px;border-radius:50px;background:var(--gold);transition:width .8s ease;width:0%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:.4rem"><span>Active Sessions</span><span id="sessions-val" style="color:var(--purple)">—</span></div><div style="background:rgba(255,255,255,.06);border-radius:50px;height:8px"><div id="sessions-bar" style="height:8px;border-radius:50px;background:var(--purple);transition:width .8s ease;width:0%"></div></div></div>
          </div>
        </div>
      </div>
      <div class="card"><div class="card-hdr"><span class="card-title"><i class="fas fa-satellite-dish"></i> Connected Devices & Sensors</span><span style="font-size:.78rem;color:var(--primary)" id="devicesOnline">Loading…</span></div>
        <table><thead><tr><th>Device ID</th><th>Type</th><th>Silo / Location</th><th>Organisation</th><th>Signal</th><th>Last Ping</th><th>Status</th></tr></thead>
        <tbody id="healthDevicesTable">
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-001</td><td>Level Radar</td><td>Silo Alpha</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████ Strong</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-002</td><td>Temperature</td><td>Silo Alpha</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████ Strong</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-003</td><td>Humidity</td><td>Silo Alpha</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">███░ Good</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">2s ago</td><td><span class="pill pill-active">Online</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-007</td><td>Temperature</td><td>Silo Epsilon</td><td>AgriStore Kenya</td><td><span style="color:var(--warning)">██░░ Fair</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">5s ago</td><td><span class="pill" style="background:#fee2e2;color:var(--danger)">⚠ Critical</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-CAM-001</td><td>IP Camera</td><td>Entrance — Nairobi</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████ Strong</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-D04</td><td>Multi-sensor</td><td>Silo Delta</td><td>AgriStore Kenya</td><td><span style="color:var(--muted)">░░░░ None</span></td><td style="font-size:.78rem;color:var(--muted)">2d ago</td><td><span class="pill pill-suspended">Offline</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-C01</td><td>Pressure</td><td>Silo Gamma</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████ Strong</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-M01</td><td>CO₂ Monitor</td><td>Silo Epsilon</td><td>AgriStore Kenya</td><td><span style="color:var(--warning)">██░░ Fair</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">8s ago</td><td><span class="pill pill-active">Online</span></td></tr>
        </tbody></table>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- DEVICE REGISTRY -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="section-panel" id="panel-devices">
      <div class="sec-hdr"><div><h1>Device Registry</h1><p>Platform-wide IoT provisioning, firmware, and connectivity</p></div>
        <button class="btn-primary" onclick="showToast('Device provisioning wizard opening…','info')"><i class="fas fa-plus"></i> Provision Device</button></div>
      <div class="g4" style="margin-bottom:1.25rem">
        <div class="kpi"><div class="kpi-ico ico-teal"><i class="fas fa-microchip"></i></div><div><div class="kpi-lbl">Total Devices</div><div class="kpi-val">231</div><div class="kpi-chg chg-up">↑ 12 this month</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-blue"><i class="fas fa-wifi"></i></div><div><div class="kpi-lbl">Online Now</div><div class="kpi-val" id="dev-online">218</div><div class="kpi-chg chg-up">94.4% uptime</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-red"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-lbl">Offline / Fault</div><div class="kpi-val" id="dev-offline">13</div><div class="kpi-chg chg-dn">3 critical sensors</div></div></div>
        <div class="kpi"><div class="kpi-ico ico-gold"><i class="fas fa-sync"></i></div><div><div class="kpi-lbl">Firmware Pending</div><div class="kpi-val">7</div><div class="kpi-chg chg-nu">v2.4.1 available</div></div></div>
      </div>
      <div id="adminMapContainer" style="border-radius:16px;overflow:hidden;height:340px;margin-bottom:1.25rem;border:1px solid var(--border)"></div>
      <div class="card"><div class="card-hdr"><span class="card-title"><i class="fas fa-list"></i> Device List</span>
        <div style="display:flex;gap:.5rem">
          <input placeholder="Search device ID…" style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.45rem .75rem;font-size:.85rem;outline:none;width:180px" oninput="filterDevices(this.value)">
          <select style="background:var(--white);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.45rem .6rem;font-size:.85rem;outline:none" onchange="filterDeviceType(this.value)"><option value="">All Types</option><option>Level Radar</option><option>Temperature</option><option>Humidity</option><option>IP Camera</option><option>CO₂ Monitor</option><option>Pressure</option></select>
        </div></div>
        <table id="devicesTable"><thead><tr><th>Device ID</th><th>Type</th><th>Firmware</th><th>Silo</th><th>Organisation</th><th>Signal</th><th>Last Seen</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-001</td><td><i class="fas fa-satellite-dish" style="color:var(--primary);margin-right:.3rem"></i>Level Radar</td><td style="font-size:.78rem">v2.4.0</td><td>Silo Alpha</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="updateFirmware(this,'SS-SNS-001')">Update</button><button class="act-btn danger" onclick="decommission(this)">Decommission</button></div></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-002</td><td><i class="fas fa-thermometer-half" style="color:var(--danger);margin-right:.3rem"></i>Temperature</td><td style="font-size:.78rem">v2.4.1 ✓</td><td>Silo Alpha</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td><td><div class="tbl-actions"><button class="act-btn" onclick="showToast('Already latest firmware','info')">Up to date</button><button class="act-btn danger" onclick="decommission(this)">Decommission</button></div></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-007</td><td><i class="fas fa-thermometer-half" style="color:var(--danger);margin-right:.3rem"></i>Temperature</td><td style="font-size:.78rem">v2.3.8</td><td>Silo Epsilon</td><td>AgriStore Kenya</td><td><span style="color:var(--warning)">██░░</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">5s ago</td><td><span class="pill" style="background:#fee2e2;color:var(--danger)">⚠ Fault</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="updateFirmware(this,'SS-SNS-007')">Update</button><button class="act-btn" onclick="rebootDevice(this)">Reboot</button></div></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-CAM-001</td><td><i class="fas fa-camera" style="color:var(--purple);margin-right:.3rem"></i>IP Camera</td><td style="font-size:.78rem">v1.9.2</td><td>Entrance</td><td>AgriStore Kenya</td><td><span style="color:var(--primary)">████</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">Just now</td><td><span class="pill pill-active">Online</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="showToast('Opening camera feed…','info')">View Feed</button><button class="act-btn primary" onclick="updateFirmware(this,'SS-CAM-001')">Update</button></div></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-D04</td><td><i class="fas fa-microchip" style="color:var(--muted);margin-right:.3rem"></i>Multi-sensor</td><td style="font-size:.78rem">v2.3.5</td><td>Silo Delta</td><td>AgriStore Kenya</td><td><span style="color:var(--muted)">░░░░</span></td><td style="font-size:.8rem;color:var(--danger)">2d ago</td><td><span class="pill pill-suspended">Offline</span></td><td><div class="tbl-actions"><button class="act-btn" onclick="rebootDevice(this)">Reboot</button><button class="act-btn danger" onclick="decommission(this)">Decommission</button></div></td></tr>
          <tr><td style="font-family:monospace;font-size:.78rem">SS-SNS-M01</td><td><i class="fas fa-wind" style="color:var(--accent);margin-right:.3rem"></i>CO₂ Monitor</td><td style="font-size:.78rem">v2.4.0</td><td>Silo Epsilon</td><td>AgriStore Kenya</td><td><span style="color:var(--warning)">██░░</span></td><td style="font-size:.78rem;color:var(--muted)" class="ping-time">8s ago</td><td><span class="pill pill-active">Online</span></td><td><div class="tbl-actions"><button class="act-btn primary" onclick="updateFirmware(this,'SS-SNS-M01')">Update</button></div></td></tr>
        </tbody></table>
      </div>
    </div>


  </div>
</main>

<!-- NEW ORG MODAL -->
<div class="modal-overlay" id="newOrgModal">
  <div class="modal-box">
    <button class="modal-close" onclick="document.getElementById('newOrgModal').classList.remove('open')"><i class="fas fa-times"></i></button>
    <h2 style="font-family:var(--font-head);font-size:1.3rem;font-weight:800;margin-bottom:1.5rem"><i class="fas fa-building" style="color:var(--primary);margin-right:.5rem"></i>Create New Organisation</h2>
    <div class="form-row">
      <div class="f-group"><label>Organisation Name</label><input placeholder="e.g. AgriStore Kenya Ltd"></div>
      <div class="f-group"><label>Slug / Subdomain</label><input placeholder="agristore-kenya"></div>
    </div>
    <div class="form-row">
      <div class="f-group"><label>Admin Email</label><input type="email" placeholder="admin@company.co.ke"></div>
      <div class="f-group"><label>M-Pesa Phone</label><input placeholder="+254700000000"></div>
    </div>
    <div class="form-row">
      <div class="f-group"><label>Subscription Plan</label><select><option>Starter</option><option>Professional</option><option>Enterprise</option></select></div>
      <div class="f-group"><label>Country</label><select><option>Kenya</option><option>Uganda</option><option>Tanzania</option><option>Ethiopia</option></select></div>
    </div>
    <div class="form-row">
      <div class="f-group"><label>Max Silos</label><input type="number" value="5"></div>
      <div class="f-group"><label>Max Users</label><input type="number" value="10"></div>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:.5rem">
      <button class="btn-primary" onclick="createOrg()"><i class="fas fa-check"></i> Create Organisation</button>
      <button class="btn-ghost" onclick="document.getElementById('newOrgModal').classList.remove('open')">Cancel</button>
    </div>
  </div>
</div>

<script>
// ─── SECTION SWITCHER ────────────────────────────────────────
const panels=['overview','organisations','users','plans','devices','revenue','payments','activity','audit','snapshots','tickets','settings','health'];
function showPanel(p){
  document.querySelectorAll('.section-panel').forEach(el=>el.classList.remove('active'));
  document.querySelectorAll('.sb-item,.nav-item').forEach(el=>el.classList.remove('active'));
  const el=document.getElementById(`panel-${p}`);
  if(el)el.classList.add('active');
  // Mark the matching sidebar button active
  document.querySelectorAll('.sb-item,.nav-item').forEach(btn=>{
    const oc=btn.getAttribute('onclick')||'';
    if(oc.includes("'"+p+"'") || oc.includes('"'+p+'"'))btn.classList.add('active');
  });
  const titles={overview:'Overview',organisations:'Organisations',users:'All Users',plans:'Subscription Plans',devices:'Device Registry',revenue:'Revenue',payments:'M-Pesa Payments',activity:'Live Activity',audit:'Audit Trail',snapshots:'Camera Snapshots',tickets:'Support Tickets',settings:'System Settings',health:'System Health'};
  const titleEl=document.getElementById('topbarTitle');
  if(titleEl)titleEl.textContent=titles[p]||p;
  if(p==='activity')startLiveFeed();
  if(p==='revenue')renderRevChart();
  if(p==='overview')setTimeout(initOverviewCharts,100);
  if(p==='settings'){initSettingsFromURL();}
  if(p==='devices'){setTimeout(()=>{if(typeof initAdminMap==='function')initAdminMap();},150);}
  // Scroll content to top
  const content=document.querySelector('.content');
  if(content) content.scrollTop=0;
  // Update URL without reload
  try{history.replaceState(null,'',window.location.pathname+'?admin_page='+p);}catch(e){}
}

// ─── CHARTS ──────────────────────────────────────────────────
const chartDefaults={responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'rgba(255,255,255,0.65)',font:{family:"'Lato',sans-serif"},boxWidth:10}},tooltip:{backgroundColor:'rgba(10,31,68,0.97)',borderColor:'#e2e8f0',borderWidth:1}},scales:{x:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'rgba(139,163,204,0.7)',font:{size:11}}},y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'rgba(139,163,204,0.7)',font:{size:11}}}}};
const months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function initOverviewCharts(){
  if(typeof Chart === 'undefined'){
    console.error('Chart.js not loaded');
    document.querySelectorAll('[id$="-loading"]').forEach(el=>{
      el.innerHTML='<i class="fas fa-exclamation-triangle" style="color:var(--gold2);margin-right:.4rem"></i> Chart library failed to load';
    });
    return;
  }
  // revChart - overview bar
  const _rc=document.getElementById('revChart');
  if(_rc && !_rc._done){
    _rc._done=true;
    const ld=document.getElementById('revChart-loading');
    if(ld)ld.remove();
    try{
      new Chart(_rc,{
        type:'bar',
        data:{
          labels:months.slice(5),
          datasets:[{
            label:'Revenue (KES)',
            data:[89000,102000,115000,118000,131000,147000],
            backgroundColor:['rgba(212,160,23,0.75)','rgba(212,160,23,0.75)','rgba(212,160,23,0.75)','rgba(212,160,23,0.75)','rgba(212,160,23,0.75)','rgba(240,192,64,0.9)'],
            borderColor:'transparent',borderWidth:0,borderRadius:8,
          }]
        },
        options:{
          ...chartDefaults,
          plugins:{...chartDefaults.plugins,legend:{display:false}},
          scales:{
            x:{...chartDefaults.scales.x},
            y:{...chartDefaults.scales.y,ticks:{...chartDefaults.scales.y.ticks,callback:v=>'KES '+v.toLocaleString()}}
          }
        }
      });
    }catch(e){console.error('revChart error:',e);}
  }
  // planChart - overview donut
  const _pc=document.getElementById('planChart');
if(_pc){try{new Chart(_pc,{type:'doughnut',data:{labels:['Starter','Professional','Enterprise','Custom'],datasets:[{data:[45,32,15,8],backgroundColor:['rgba(212,160,23,0.85)','rgba(46,94,170,0.85)','rgba(22,163,74,0.75)','rgba(139,163,204,0.5)'],borderColor:'rgba(13,27,62,0.5)',borderWidth:2,hoverOffset:8}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{color:'rgba(255,255,255,0.7)',font:{size:11},boxWidth:10,padding:14}},tooltip:{backgroundColor:'rgba(10,31,68,0.97)',borderColor:'rgba(212,160,23,0.3)',borderWidth:1,cornerRadius:9,padding:10}}}});
  const pld=document.getElementById('planChart-loading');
  if(pld)pld.remove();
  }catch(e){console.error('planChart:',e);}
}
} // end initOverviewCharts

// ─── LIVE ACTIVITY FEED ───────────────────────────────────────
const sampleLogs=[
  {user:'James Mwangi',org:'AgriStore Kenya',avatar:'JM',avatarBg:'#D4A017',action:'Acknowledged alert: High Temperature – Silo Epsilon',category:'alert',ip:'105.163.20.14',city:'Nairobi, KE',device:'Chrome / Android',time:'2s ago',risk:0,hasSnap:true},
  {user:'Grace Akinyi',org:'AgriStore Kenya',avatar:'GA',avatarBg:'#4a90e2',action:'Updated task status: Monthly Maintenance – In Progress',category:'task',ip:'105.163.20.18',city:'Nairobi, KE',device:'Safari / iOS',time:'14s ago',risk:0,hasSnap:true},
  {user:'Admin User',org:'Coastal Grain',avatar:'AU',avatarBg:'#9b59b6',action:'Exported Excel report: Daily Operations Report',category:'report',ip:'197.237.161.8',city:'Mombasa, KE',device:'Chrome / Windows',time:'1m ago',risk:0,hasSnap:true},
  {user:'Unknown',org:'—',avatar:'?',avatarBg:'#ff4757',action:'Failed login attempt (3/5): wrong password',category:'auth',ip:'185.234.219.4',city:'Moscow, RU',device:'Chrome / Linux',time:'3m ago',risk:85,hasSnap:false},
  {user:'Peter Kamau',org:'Nairobi Cement',avatar:'PK',avatarBg:'#f5a623',action:'Added manual sensor reading: Pressure – Silo Gamma',category:'sensor',ip:'41.89.56.12',city:'Nairobi, KE',device:'Firefox / Windows',time:'5m ago',risk:0,hasSnap:true},
  {user:'James Mwangi',org:'AgriStore Kenya',avatar:'JM',avatarBg:'#D4A017',action:'Initiated M-Pesa payment: KES 7,999 – Professional Plan',category:'payment',ip:'105.163.20.14',city:'Nairobi, KE',device:'Chrome / Android',time:'8m ago',risk:0,hasSnap:true},
  {user:'Amina Odhiambo',org:'Coastal Grain',avatar:'AO',avatarBg:'#B8860B',action:'Created new silo: Silo Theta (S-019)',category:'silo',ip:'197.237.161.8',city:'Mombasa, KE',device:'Edge / Windows',time:'12m ago',risk:0,hasSnap:true},
];

function renderLog(log){
  const riskColor=log.risk>70?'var(--danger)':log.risk>40?'var(--warning)':'var(--success)';
  const catColors={alert:'var(--danger)',auth:log.risk>0?'var(--danger)':'var(--muted)',payment:'var(--success)',report:'var(--accent)',task:'var(--warning)',sensor:'var(--muted)',silo:'var(--purple)'};
  return `<div class="log-item">
    <div class="log-avatar" style="background:${log.avatarBg}22;color:${log.avatarBg}">${log.avatar}</div>
    <div class="log-body">
      <div class="log-action">${log.user} <span style="color:var(--muted);font-weight:400">·</span> <span style="font-size:.78rem;color:var(--muted)">${log.org}</span></div>
      <div style="font-size:.83rem;color:rgba(255,255,255,.75);margin-bottom:.3rem">${log.action}</div>
      <div class="log-meta">
        <span class="log-tag" style="background:${catColors[log.category]||'rgba(255,255,255,.06)'}22;color:${catColors[log.category]||'var(--muted)'}">${log.category}</span>
        <span><i class="fas fa-map-marker-alt" style="color:var(--muted);font-size:.65rem"></i> ${log.city}</span>
        <span><i class="fas fa-network-wired" style="color:var(--muted);font-size:.65rem"></i> ${log.ip}</span>
        <span><i class="fas fa-desktop" style="color:var(--muted);font-size:.65rem"></i> ${log.device}</span>
        <span style="color:var(--muted)">${log.time}</span>
        ${log.risk>0?`<span class="log-tag" style="background:#fee2e2;color:var(--danger)">⚠ Risk: ${log.risk}/100</span>`:''}
      </div>
    </div>
    ${log.hasSnap?`<div class="log-snap-placeholder" title="Camera snapshot captured"><i class="fas fa-camera" style="font-size:.65rem"></i></div>`:``}
  </div>`;
}

function startLiveFeed(){
  const feed=document.getElementById('liveActivityFeed');
  feed.innerHTML=sampleLogs.map(renderLog).join('');
  // Add new log every 4s
  let idx=0;
  setInterval(()=>{
    const log={...sampleLogs[idx%sampleLogs.length],time:'Just now'};
    feed.insertAdjacentHTML('afterbegin',renderLog(log));
    feed.children[8]?.remove();
    idx++;
  },4000);
}

// ─── ACTIONS ────────────────────────────────────────────────
function suspendOrg(btn){
  const tr=btn.closest('tr');
  const pill=tr.querySelector('.pill');
  pill.className='pill pill-suspended';pill.textContent='Suspended';
  btn.textContent='Activate';btn.onclick=()=>activateOrg(btn);
  showToast('Organisation suspended','warning');
}
function activateOrg(btn){
  const tr=btn.closest('tr');
  const pill=tr.querySelector('.pill[class*="pill-"]');
  if(pill){pill.className='pill pill-active';pill.textContent='Active';}
  btn.textContent='Suspend';btn.onclick=()=>suspendOrg(btn);
  showToast('Organisation activated');
}
function impersonate(_,name){showToast(`Logging in as ${name} admin…`);}
function createOrg(){document.getElementById('newOrgModal').classList.remove('open');showToast('Organisation created successfully!');}
function showOrgModal(name){showToast('Loading details for '+name+'…','info');}
function exportActivityLog(){
  const rows=[['User','Org','Action','Category','IP','City','Device','Time'],...sampleLogs.map(l=>[l.user,l.org,l.action,l.category,l.ip,l.city,l.device,l.time])];
  const csv=rows.map(r=>r.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');
  const a=document.createElement('a');a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv);a.download='activity_log_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
  showToast('Activity log downloaded');
}
function loadMoreLogs(){showToast('Loading more log entries…','info');}
async function saveSettings(group) {
  const btn = event?.target;
  const origHtml = btn ? btn.innerHTML : '';
  if (btn) { btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving…'; }

  // Collect inputs in the active settings pane
  const pane = btn?.closest('.settings-pane') || btn?.closest('.card') || document.querySelector('.settings-pane.active');
  const data = { group };
  if (pane) {
    pane.querySelectorAll('input[id],select[id],textarea[id]').forEach(el => {
      if (el.type === 'checkbox') data[el.id] = el.checked ? '1' : '0';
      else if (el.type === 'password' && !el.value) return;
      else if (el.value) data[el.id] = el.value;
    });
  }

  try {
    const res = await fetch('/api/settings/save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const d = await res.json();
    showToast(d.message || group.charAt(0).toUpperCase()+group.slice(1)+' settings saved!', d.success ? 'ok' : 'err');
  } catch(e) {
    // Demo mode — no DB
    showToast(group.charAt(0).toUpperCase()+group.slice(1)+' settings saved! (Demo mode)', 'ok');
  } finally {
    if (btn) { btn.disabled=false; btn.innerHTML=origHtml; }
  }
}
// testMpesa defined below
function testEmail(){showToast('Test email sent to admin@silosmart.io','info');}

// ─── TOAST ────────────────────────────────────────────────────
function _legacyToast(msg,type='success'){
  let tc=document.getElementById('tc');
  if(!tc){tc=document.createElement('div');tc.id='tc';tc.style.cssText='position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';document.body.appendChild(tc);}
  const colors={success:'var(--green2)',error:'var(--red2)',warning:'#d97706',info:'#2563eb'};
  const c=colors[type]||colors.success;
  const t=document.createElement('div');
  t.style.cssText=`background:#fff;border:1px solid #e2e8f0;border-left:3px solid ${c};border-radius:8px;padding:.75rem 1rem;font-size:.85rem;min-width:260px;box-shadow:0 4px 20px rgba(0,0,0,.12);animation:slideIn .3s ease;color:var(--white)`;
  t.innerHTML=`<span style="color:${c}">${msg}</span>`;
  const sty=document.createElement('style');sty.textContent='@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}';document.head.appendChild(sty);
  tc.prepend(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transition='.3s';setTimeout(()=>t.remove(),300)},3500);
}

// Auto start overview
window.addEventListener('load',()=>{
  setTimeout(()=>showToast('<?= $user_name ?> session active — activity logging enabled','info'),800);
  initHealthMonitor();
  initSnapshotGrid();
  initAuditFeed();
  initAdminMap();
});

// ─── USERS ────────────────────────────────────────────────────
function filterUsers(){
  const q=(document.getElementById('userSearch').value||'').toLowerCase();
  const org=(document.getElementById('userOrgFilter').value||'').toLowerCase();
  const role=(document.getElementById('userRoleFilter').value||'').toLowerCase();
  document.querySelectorAll('#usersTable tbody tr').forEach(tr=>{
    const txt=tr.textContent.toLowerCase();
    tr.style.display=((!q||txt.includes(q))&&(!org||txt.includes(org))&&(!role||txt.includes(role)))?'':'none';
  });
}
function suspendUser(btn){const tr=btn.closest('tr');tr.querySelector('.pill[class*="pill-active"]').className='pill pill-suspended';tr.querySelector('.pill.pill-suspended').textContent='Suspended';btn.textContent='Activate';btn.onclick=()=>activateUser(btn);showToast('User suspended','warning');}
function activateUser(btn){const tr=btn.closest('tr');const p=tr.querySelector('.pill.pill-suspended');if(p){p.className='pill pill-active';p.textContent='Active';}btn.textContent='Suspend';btn.onclick=()=>suspendUser(btn);showToast('User activated');}

// ─── M-PESA ───────────────────────────────────────────────────
function exportMpesaLog(){
  const rows=[['Date','Organisation','Phone','Amount','MpesaRef','Plan','Status']];
  document.querySelectorAll('#panel-payments table tbody tr').forEach(tr=>{
    const cells=[...tr.querySelectorAll('td')].map(td=>td.textContent.trim());
    if(cells.length)rows.push(cells);
  });
  const csv=rows.map(r=>r.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');
  const a=document.createElement('a');a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv);a.download='mpesa_payments_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
  showToast('M-Pesa payments exported');
}
function retryMpesa(btn){
  btn.textContent='Retrying...';btn.disabled=true;
  setTimeout(function(){showToast('Payment retry sent','ok');btn.textContent='Retry';btn.disabled=false;},2500);
}

// ─── PLANS ────────────────────────────────────────────────────
function editPlan(name){
  showToast('Opening plan editor for '+name+'…','info');
}
function openPlanModal(){
  showToast('Plan creation form coming soon','info');
}

// ─── TICKETS ─────────────────────────────────────────────────
function openTicket(id){showToast('Opening ticket #'+id+' reply view…','info');}
function resolveTicket(btn,id){
  const tr=btn.closest('tr');
  tr.querySelectorAll('.pill').forEach(p=>{if(p.textContent==='Open'){p.className='pill pill-active';p.textContent='Resolved';}});
  btn.remove();
  tr.style.opacity='.5';
  const open=document.querySelectorAll('#ticketsTable tbody tr:not([style*="opacity"])').length;
  document.getElementById('tickets-open').textContent=open;
  showToast('Ticket #'+id+' resolved');
}

// ─── AUDIT ───────────────────────────────────────────────────
const auditData=[
  {ts:'2026-06-04 11:42:03',user:'James Mwangi',org:'AgriStore Kenya',action:'acknowledged_alert',desc:'Acknowledged: High Temperature – Silo Epsilon',ip:'105.163.20.14',cat:'alert',risk:0},
  {ts:'2026-06-04 11:38:17',user:'Super Admin',org:'Platform',action:'admin_settings_changed',desc:'Updated M-Pesa environment to Production',ip:'41.89.12.5',cat:'admin',risk:35},
  {ts:'2026-06-04 11:20:44',user:'Unknown',org:'—',action:'failed_login',desc:'Failed login attempt 4/5 from 185.234.219.4',ip:'185.234.219.4',cat:'auth',risk:88},
  {ts:'2026-06-04 10:55:11',user:'Grace Akinyi',org:'AgriStore Kenya',action:'task_updated',desc:'Task "Monthly Maintenance – Silo Delta" → In Progress',ip:'105.163.20.18',cat:'task',risk:0},
  {ts:'2026-06-04 10:32:55',user:'Amina Odhiambo',org:'Coastal Grain',action:'report_exported',desc:'Exported Monthly Summary – May 2026 to Excel',ip:'197.237.161.8',cat:'report',risk:0},
  {ts:'2026-06-04 10:15:22',user:'James Mwangi',org:'AgriStore Kenya',action:'payment_initiated',desc:'M-Pesa STK Push KES 7,999 – Professional Plan renewal',ip:'105.163.20.14',cat:'payment',risk:0},
  {ts:'2026-06-04 09:48:00',user:'Peter Kamau',org:'Nairobi Cement',action:'silo_reading_added',desc:'Manual sensor reading added: Pressure 98 kPa – Silo Gamma',ip:'41.89.56.12',cat:'sensor',risk:0},
  {ts:'2026-06-04 09:12:34',user:'Super Admin',org:'Platform',action:'org_suspended',desc:'Organisation "Mombasa Port Stores" suspended — non-payment',ip:'41.89.12.5',cat:'admin',risk:20},
];
let auditFiltered=[...auditData];
function renderAuditRow(e){
  const riskColor=e.risk>70?'var(--danger)':e.risk>30?'var(--warning)':'var(--success)';
  const catColor={alert:'var(--danger)',auth:e.risk>0?'var(--danger)':'var(--muted)',payment:'var(--success)',report:'var(--accent)',task:'var(--warning)',sensor:'var(--muted)',admin:'var(--purple)',silo:'var(--purple)'}[e.cat]||'var(--muted)';
  return `<div class="log-item" style="border-bottom:1px solid var(--border)">
    <div class="log-avatar" style="background:${catColor}22;color:${catColor};font-size:.65rem;min-width:34px">${e.cat.toUpperCase().slice(0,3)}</div>
    <div class="log-body" style="flex:1">
      <div style="font-size:.83rem;font-weight:600;margin-bottom:.2rem">${e.desc}</div>
      <div class="log-meta">
        <span style="color:rgba(255,255,255,.6)">${e.user}</span>
        <span style="color:var(--muted)">·</span>
        <span style="color:var(--muted)">${e.org}</span>
        <span style="color:var(--muted)">${e.ts}</span>
        <span><i class="fas fa-network-wired" style="color:var(--muted);font-size:.65rem"></i> ${e.ip}</span>
        ${e.risk>0?`<span class="log-tag" style="background:#fee2e2;color:var(--danger)">⚠ Risk ${e.risk}/100</span>`:''}
      </div>
    </div>
  </div>`;
}
function initAuditFeed(){document.getElementById('auditFeed').innerHTML=auditFiltered.map(renderAuditRow).join('');}
function filterAudit(q){auditFiltered=auditData.filter(e=>e.desc.toLowerCase().includes(q.toLowerCase())||e.user.toLowerCase().includes(q.toLowerCase())||e.ip.includes(q));document.getElementById('auditFeed').innerHTML=auditFiltered.map(renderAuditRow).join('');}
function filterAuditCat(cat){auditFiltered=cat?auditData.filter(e=>e.cat===cat):[...auditData];document.getElementById('auditFeed').innerHTML=auditFiltered.map(renderAuditRow).join('');}
function filterAuditRisk(high){auditFiltered=high?auditData.filter(e=>e.risk>50):[...auditData];document.getElementById('auditFeed').innerHTML=auditFiltered.map(renderAuditRow).join('');}
function loadMoreAudit(){showToast('Loading next page of audit entries…','info');}
function exportAudit(){
  const rows=[['Timestamp','User','Org','Action','Description','IP','Risk'],...auditFiltered.map(e=>[e.ts,e.user,e.org,e.action,e.desc,e.ip,e.risk])];
  const csv=rows.map(r=>r.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');
  const a=document.createElement('a');a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv);a.download='audit_log_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
  showToast('Audit log downloaded as CSV');
}

// ─── CAMERA SNAPSHOTS ─────────────────────────────────────────
const snapUsers=[
  {name:'James Mwangi',event:'Login',time:'11:42',risk:0,initials:'JM',color:'#D4A017'},
  {name:'Grace Akinyi',event:'Report Export',time:'11:20',risk:0,initials:'GA',color:'#4a90e2'},
  {name:'Unknown',event:'Failed Login',time:'11:38',risk:88,initials:'?',color:'#ff4757'},
  {name:'Amina Odhiambo',event:'Login',time:'10:55',risk:0,initials:'AO',color:'#B8860B'},
  {name:'Peter Kamau',event:'Admin Action',time:'10:32',risk:15,initials:'PK',color:'#f5a623'},
  {name:'Super Admin',event:'Settings Change',time:'10:15',risk:35,initials:'SA',color:'#9b59b6'},
];
function initSnapshotGrid(){
  const grid=document.getElementById('snapshotGrid');
  grid.innerHTML=snapUsers.map(s=>{
    const riskCol=s.risk>70?'var(--danger)':s.risk>30?'var(--warning)':'var(--success)';
    const riskLabel=s.risk>70?'HIGH RISK':s.risk>30?'MODERATE':'Verified';
    // SVG simulated face snapshot
    const svg=`<svg viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg" style="width:100%;display:block;border-radius:8px 8px 0 0;background:var(--card)">
      <rect width="200" height="150" fill="#0d1f3c"/>
      <circle cx="100" cy="62" r="32" fill="${s.color}22" stroke="${s.color}44" stroke-width="1.5"/>
      <text x="100" y="70" text-anchor="middle" fill="${s.color}" font-size="22" font-family="sans-serif" font-weight="bold">${s.initials}</text>
      <rect x="0" y="0" width="200" height="150" fill="none" stroke="${riskCol}44" stroke-width="3"/>
      <rect x="30" y="20" width="50" height="50" fill="none" stroke="${riskCol}" stroke-width="1.5" stroke-dasharray="4,4" opacity=".6"/>
      <rect x="120" y="20" width="50" height="50" fill="none" stroke="${riskCol}" stroke-width="1.5" stroke-dasharray="4,4" opacity=".6"/>
      <line x1="0" y1="75" x2="200" y2="75" stroke="${riskCol}22" stroke-width="1"/>
      <text x="8" y="140" fill="${riskCol}99" font-size="8" font-family="monospace">CAM-001 ${new Date().toLocaleDateString('en-GB')} ${s.time}</text>
      <text x="160" y="140" fill="${riskCol}99" font-size="8" font-family="monospace">LIVE</text>
    </svg>`;
    return `<div style="background:var(--white);border:1px solid ${riskCol}33;border-radius:12px;overflow:hidden;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.06)" onclick="showToast('Snapshot detail for ${s.name}','info')">
      ${svg}
      <div style="padding:.65rem .75rem">
        <div style="font-weight:600;font-size:.85rem">${s.name}</div>
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem">${s.event} · Today ${s.time}</div>
        <span style="font-size:.7rem;padding:.2rem .5rem;border-radius:4px;background:${riskCol}18;color:${riskCol}">${riskLabel}</span>
      </div>
    </div>`;
  }).join('');
}

// ─── SYSTEM HEALTH MONITOR ────────────────────────────────────
let healthChart=null, healthHistory={api:[],db:[],mqtt:[],mpesa:[]};
const healthChecks=[
  {id:'api',label:'API Server',url:'https://httpstat.us/200',baseMs:45},
  {id:'db',label:'Database',url:null,baseMs:12},
  {id:'mqtt',label:'MQTT Broker',url:null,baseMs:8},
  {id:'mpesa',label:'M-Pesa API',url:null,baseMs:320},
];
function fakeMs(base){return Math.round(base+(Math.random()-.3)*base*.4);}
function msColor(ms){return ms<100?'var(--success)':ms<500?'var(--warning)':'var(--danger)';}

function runHealthCheck(){
  const now=new Date().toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  document.getElementById('healthLastUpdate').textContent='Updated '+now;
  const statuses={api:null,db:null,mqtt:null,mpesa:null};
  healthChecks.forEach(svc=>{
    const ms=fakeMs(svc.baseMs);
    healthHistory[svc.id].push(ms);
    if(healthHistory[svc.id].length>12)healthHistory[svc.id].shift();
    statuses[svc.id]=ms;
    const el=document.getElementById('h-'+svc.id);
    const elMs=document.getElementById('h-'+svc.id+'-ms');
    if(el){el.textContent='Online';el.style.color='var(--success)';}
    if(elMs){elMs.textContent=ms+'ms response';elMs.className='kpi-chg chg-up';}
  });
  // Resource usage (simulated live)
  const cpu=Math.round(22+Math.random()*18);
  const mem=Math.round(58+Math.random()*12);
  const disk=34;
  const sess=Math.round(12+Math.random()*8);
  document.getElementById('cpu-val').textContent=cpu+'%';document.getElementById('cpu-bar').style.width=cpu+'%';
  document.getElementById('mem-val').textContent=mem+'%';document.getElementById('mem-bar').style.width=mem+'%';
  document.getElementById('disk-val').textContent=disk+'%';document.getElementById('disk-bar').style.width=disk+'%';
  document.getElementById('sessions-val').textContent=sess+' active';document.getElementById('sessions-bar').style.width=(sess/30*100)+'%';
  // Update ping times
  document.querySelectorAll('.ping-time').forEach(el=>{
    if(Math.random()>.7)el.textContent='Just now';
    else if(Math.random()>.5)el.textContent=Math.round(Math.random()*9+1)+'s ago';
  });
  document.getElementById('devicesOnline').textContent='218/231 online · Last check: '+now;
  updateHealthChart();
}

function updateHealthChart(){
  const labels=healthHistory.api.map((_,i)=>'T-'+(healthHistory.api.length-1-i)).reverse();
  const datasets=[
    {label:'API',data:healthHistory.api,borderColor:'var(--primary)',tension:.4,pointRadius:2,fill:false},
    {label:'DB',data:healthHistory.db,borderColor:'#4a90e2',tension:.4,pointRadius:2,fill:false},
    {label:'MQTT',data:healthHistory.mqtt,borderColor:'#9b59b6',tension:.4,pointRadius:2,fill:false},
    {label:'M-Pesa',data:healthHistory.mpesa,borderColor:'var(--gold)',tension:.4,pointRadius:2,fill:false},
  ];
  if(!healthChart){
    const ctx=document.getElementById('healthChart');
    if(!ctx)return;
    healthChart=new Chart(ctx,{type:'line',data:{labels,datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'rgba(255,255,255,0.65)',font:{family:"'Lato',sans-serif"},boxWidth:10}}},scales:{x:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'rgba(139,163,204,0.7)'}},y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'rgba(139,163,204,0.7)',callback:v=>v+'ms'}}}}});
  } else {
    healthChart.data.labels=labels;
    healthChart.data.datasets=datasets;
    healthChart.update();
  }
}

function initHealthMonitor(){
  // Prime with 6 fake history points
  for(let i=0;i<6;i++){healthChecks.forEach(s=>{healthHistory[s.id].push(fakeMs(s.baseMs));});}
  runHealthCheck();
  setInterval(runHealthCheck,10000);
}

// ─── DEVICES FILTER ───────────────────────────────────────────
function filterDevices(q){document.querySelectorAll('#devicesTable tbody tr').forEach(tr=>{tr.style.display=tr.textContent.toLowerCase().includes(q.toLowerCase())?'':'none';});}
function filterDeviceType(t){document.querySelectorAll('#devicesTable tbody tr').forEach(tr=>{tr.style.display=(!t||tr.textContent.includes(t))?'':'none';});}
function updateFirmware(btn,id){btn.textContent='Updating…';btn.disabled=true;setTimeout(()=>{btn.textContent='✓ Updated';btn.style.color='var(--success)';showToast(id+' firmware updated to v2.4.1');},2000);}
function rebootDevice(btn){btn.textContent='Rebooting…';btn.disabled=true;setTimeout(()=>{btn.textContent='Reboot';btn.disabled=false;showToast('Device reboot command sent');},1800);}
function decommission(btn){if(confirm('Decommission this device? This cannot be undone.')){btn.closest('tr').style.opacity='.4';showToast('Device decommissioned','warning');}}

// ─── LEAFLET MAP ──────────────────────────────────────────────
function initAdminMap(){
  if(!document.getElementById('adminMapContainer'))return;
  // Load Leaflet CSS + JS dynamically
  if(!document.getElementById('leaflet-css')){
    const lc=document.createElement('link');lc.id='leaflet-css';lc.rel='stylesheet';lc.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';document.head.appendChild(lc);
  }
  if(typeof L==='undefined'){
    const ls=document.createElement('script');ls.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    ls.onload=()=>renderAdminMap();document.head.appendChild(ls);
  } else {renderAdminMap();}
}
function renderAdminMap(){
  const container=document.getElementById('adminMapContainer');
  if(!container||container._mapInited)return;
  container._mapInited=true;
  const map=L.map(container,{zoomControl:true,scrollWheelZoom:false}).setView([-1.2921,36.8219],6);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CartoDB',maxZoom:18}).addTo(map);
  const tealIcon=(count,col)=>L.divIcon({className:'',html:`<div style="background:${col};color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif;font-weight:700;font-size:.85rem;border:2px solid rgba(255,255,255,.3);box-shadow:0 0 14px ${col}99">${count}</div>`,iconSize:[36,36]});
  const sites=[
    {latlng:[-1.2869,36.8219],label:'Nairobi Central Store',silos:3,devices:14,org:'AgriStore Kenya',status:'Active',col:'#D4A017'},
    {latlng:[-4.0435,39.6682],label:'Mombasa Grain Hub',silos:2,devices:9,org:'AgriStore Kenya',status:'Active',col:'#D4A017'},
    {latlng:[-4.0500,39.6700],label:'Coastal Grain Mombasa',silos:8,devices:31,org:'Coastal Grain Processors',status:'Active',col:'#9b59b6'},
    {latlng:[0.5143,35.2698],label:'Eldoret Agro Complex',silos:6,devices:24,org:'Coastal Grain Processors',status:'Active',col:'#9b59b6'},
    {latlng:[-1.3000,36.8200],label:'Nairobi Cement Plant',silos:8,devices:20,org:'Nairobi Cement Ltd',status:'Active',col:'#4a90e2'},
    {latlng:[-0.1022,34.7617],label:'Kisumu Lakeside Agro',silos:3,devices:8,org:'Lakeside Agro',status:'Trial',col:'#f5a623'},
    {latlng:[-4.0600,39.6600],label:'Mombasa Port Stores',silos:4,devices:11,org:'Mombasa Port Stores',status:'Suspended',col:'#ff4757'},
  ];
  sites.forEach(s=>{
    const marker=L.marker(s.latlng,{icon:tealIcon(s.silos,s.col)}).addTo(map);
    marker.bindPopup(`<div style="font-family:'Inter',sans-serif;min-width:190px"><div style="font-weight:700;margin-bottom:.3rem">${s.label}</div><div style="font-size:.8rem;color:#888;margin-bottom:.4rem">${s.org}</div><div style="display:flex;gap:.5rem;flex-wrap:wrap;font-size:.78rem"><span style="background:${s.col}22;color:${s.col};padding:.15rem .4rem;border-radius:4px">${s.silos} silos</span><span style="background:#4a90e222;color:#4a90e2;padding:.15rem .4rem;border-radius:4px">${s.devices} devices</span><span style="background:#88889922;color:#888;padding:.15rem .4rem;border-radius:4px">${s.status}</span></div></div>`);
  });
}

// (panel init merged into showPanel)

// ── SETTINGS TABS ─────────────────────────────────────────────
function switchSettingsTab(tab) {
  document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.settings-pane').forEach(p => p.classList.remove('active'));
  const btn = document.querySelector(`.settings-tab[data-tab="${tab}"]`);
  const pane = document.getElementById('stab-' + tab);
  if (btn)  btn.classList.add('active');
  if (pane) pane.classList.add('active');
  // Update URL hash silently
  // Update URL to reflect active tab
  const base = window.location.pathname + '?admin_page=settings';
  history.replaceState(null, '', base + '&tab=' + tab);
}

// Auto-open tab from URL param on load
(function(){
  const params = new URLSearchParams(window.location.search);
  const tab = params.get('tab') || 'general';
  switchSettingsTab(tab);
})();

// ── LIVE CLOCK ─────────────────────────────────────────────────
(function clockTick(){
  const el = document.getElementById('settings-date-time');
  if (!el) return;
  const now = new Date();
  const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
  const date = now.toLocaleDateString('en-KE', opts);
  const time = now.toLocaleTimeString('en-KE', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
  el.textContent = date + ' · ' + time + ' ' + tz;
  setTimeout(clockTick, 1000);
})();

// ── SMTP AUTO-FILL ─────────────────────────────────────────────
function fillSmtp(host, port, enc) {
  const h = document.getElementById('s-smtp-host');
  const p = document.getElementById('s-smtp-port');
  const e = document.getElementById('s-smtp-enc');
  if (h) h.value = host;
  if (p) p.value = port;
  if (e) e.value = enc.toLowerCase();
  // Highlight filled fields
  [h, p, e].forEach(el => {
    if (!el) return;
    el.style.borderColor = 'var(--gold)';
    el.style.boxShadow = '0 0 0 3px rgba(212,160,23,.15)';
    setTimeout(() => { el.style.borderColor = ''; el.style.boxShadow = ''; }, 2000);
  });
  showToast('SMTP fields filled — enter your credentials and save.', 'ok');
}

// ── SAVE SETTINGS ──────────────────────────────────────────────
// saveSettings defined above

// ── TEST MPESA ──────────────────────────────────────────────────
async function testMpesa() {
  showToast('Sending STK test to your phone…', 'ok');
  try {
    const res = await fetch('/api/mpesa/test.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ phone: '254700000000', amount: 1 }) });
    const d = await res.json();
    showToast(d.success ? 'STK Push sent! Check your phone.' : (d.error || 'Test failed.'), d.success ? 'ok' : 'err');
  } catch(e) { showToast('Cannot reach M-Pesa API — check credentials.', 'err'); }
}

// ── SEND TEST EMAIL ─────────────────────────────────────────────
async function sendTestEmail() {
  const user = '<?= htmlspecialchars($user["email"] ?? "admin@silosmart.io") ?>';
  showToast('Sending test email to ' + user + '…', 'ok');
  try {
    const res = await fetch('/api/email/test.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ to: user }) });
    const d = await res.json();
    showToast(d.success ? 'Test email sent! Check your inbox.' : (d.error || 'Send failed.'), d.success ? 'ok' : 'err');
  } catch(e) { showToast('Email API not reachable — check SMTP settings.', 'err'); }
}

// ── REGENERATE API KEY ──────────────────────────────────────────
function regenerateApiKey() {
  if (!confirm('Regenerate the API key? Any existing integrations using the old key will break.')) return;
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let key = 'sk_live_silosmart_';
  for (let i = 0; i < 32; i++) key += chars[Math.floor(Math.random() * chars.length)];
  document.getElementById('s-api-key').value = key;
  document.getElementById('s-api-key').type = 'text';
  showToast('New API key generated — click Save API Settings to apply.', 'ok');
  setTimeout(() => { document.getElementById('s-api-key').type = 'password'; }, 5000);
}

// ── TOAST NOTIFICATION ─────────────────────────────────────────
// showToast defined below
function downloadAuditLog() {
  const url = '/api/reports/generate.php?type=audit&format=csv';
  const a = document.createElement('a');
  a.href = url; a.download = '';
  document.body.appendChild(a); a.click();
  document.body.removeChild(a);
  showToast('Downloading audit log…', 'ok');
}

// ─── TOAST (admin version) ────────────────────────────────────
function showToast(msg, type) {
  type = type || 'ok';
  const existing = document.getElementById('admin-toast');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.id = 'admin-toast';
  const isOk = type === 'ok' || type === 'success';
  toast.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.85rem 1.25rem;border-radius:12px;font-size:.875rem;font-weight:600;display:flex;align-items:center;gap:.6rem;border:1px solid ${isOk?'rgba(212,160,23,.4)':'rgba(220,38,38,.4)'};color:${isOk?'#F0C040':'#FCA5A5'};background:${isOk?'linear-gradient(135deg,rgba(13,27,62,.98),rgba(26,58,107,.96))':'rgba(30,10,10,.97)'};box-shadow:0 8px 30px rgba(10,31,68,.5);animation:toastIn .3s ease;max-width:380px;font-family:Lato,sans-serif`;
  toast.innerHTML = `<i class="fas ${isOk?'fa-check-circle':'fa-times-circle'}" style="flex-shrink:0"></i><span>${msg}</span>`;
  if (!document.getElementById('admin-toast-css')) {
    const st = document.createElement('style');
    st.id = 'admin-toast-css';
    st.textContent = '@keyframes toastIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(st);
  }
  document.body.appendChild(toast);
  setTimeout(()=>{toast.style.opacity='0';toast.style.transition='opacity .4s';setTimeout(()=>toast.remove(),400);},4500);
}

// ─── KEYBOARD SHORTCUTS ───────────────────────────────────────
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});

// ─── CLOSE MODAL ON OVERLAY CLICK ────────────────────────────
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ─── INIT OVERVIEW ON LOAD ────────────────────────────────────
window.addEventListener('load', function() {
  setTimeout(initOverviewCharts, 300);
});


// ─── REVENUE FULL CHART ───────────────────────────────────────
function renderRevChart(){
  // Re-init overview charts too
  initOverviewCharts();
  const el = document.getElementById('revFullChart');
  if (!el || el._done) return;
  el._done = true;
  try {
    new Chart(el, {
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
          label: 'Revenue (KES)',
          data: [62000,71000,78000,82000,89000,95000,102000,115000,118000,131000,140000,147960],
          borderColor: '#D4A017',
          backgroundColor: 'rgba(212,160,23,0.08)',
          tension: 0.45, fill: true,
          pointRadius: 4, pointBackgroundColor: '#D4A017',
          borderWidth: 2.5,
        }]
      },
      options: {
        ...chartDefaults,
        plugins: { ...chartDefaults.plugins, legend: { display: false } },
        scales: {
          x: { ...chartDefaults.scales.x },
          y: { ...chartDefaults.scales.y, ticks: { ...chartDefaults.scales.y.ticks, callback: v => 'KES ' + v.toLocaleString() } }
        }
      }
    });
  } catch(e) { console.error('revFullChart error:', e); }
}


// ─── INIT PANEL FROM URL ──────────────────────────────────────

// ─── INIT CHARTS ON PAGE LOAD ────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initOverviewCharts();
});
window.addEventListener('load', function() {
  setTimeout(initOverviewCharts, 400);
});

(function(){
  const p = new URLSearchParams(window.location.search).get('admin_page');
  if(p && p !== 'overview') {
    setTimeout(()=>showPanel(p), 100);
  }
})();



// ─── SETTINGS EXTRA FUNCTIONS ─────────────────────────────────
function testSMS() {
  const phone = document.getElementById('sms-admin-phone')?.value || '';
  if (!phone) { showToast('Enter admin phone number first', 'err'); return; }
  showToast('Sending test SMS to ' + phone + '…', 'ok');
  // In production: POST to /api/sms/test.php
}

function triggerBackup() {
  if (!confirm('Run a manual backup now? This may take 1-2 minutes.')) return;
  showToast('Backup started… you will be notified when complete.', 'ok');
  // In production: POST to /api/backup/run.php
}

function confirmRestore() {
  const f = document.querySelector('input[type="file"][accept*="sql"]');
  if (!f || !f.files.length) { showToast('Select a backup file first', 'err'); return; }
  if (!confirm('⚠️ Restoring will OVERWRITE all current data. This cannot be undone. Continue?')) return;
  showToast('Restore initiated… do not close this page.', 'ok');
}

// ─── SETTINGS LIVE CLOCK ──────────────────────────────────────
(function clockTick() {
  const el = document.getElementById('settings-date-time');
  if (el) {
    const now = new Date();
    const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    el.textContent = now.toLocaleDateString('en-KE', opts) + ' · '
      + now.toLocaleTimeString('en-KE', {hour:'2-digit', minute:'2-digit', second:'2-digit'})
      + ' EAT';
  }
  setTimeout(clockTick, 1000);
})();

// ─── AUTO-OPEN SETTINGS TAB FROM URL ─────────────────────────
function initSettingsFromURL() {
  const params = new URLSearchParams(window.location.search);
  const tab = params.get('tab') || 'general';
  switchSettingsTab(tab);
}

</script>
</body>
</html>