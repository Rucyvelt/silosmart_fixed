<?php
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) {
    header('Location: /login.php?next='.urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$type   = $_GET['type'] ?? 'inventory';
$format = $_GET['format'] ?? 'csv';
$user   = ss_get_current_user();
$org_id = $user['organisation_id'] ?? null;

try {
    $pdo = db();
} catch (Exception $e) {
    die("Database not connected. Please configure includes/config.php.");
}

$org_clause = $org_id ? "WHERE organisation_id = $org_id" : "";

// ── COLLECT DATA ──────────────────────────────────────────────
switch ($type) {
    case 'inventory':
        $title   = 'Inventory Report';
        $headers = ['Silo Name','Code','Site','Commodity','Capacity (T)','Fill %','Status'];
        $rows    = [];
        $silos   = $pdo->query("SELECT s.*, (SELECT se.last_reading FROM sensors se WHERE se.silo_id=s.id AND se.sensor_type='level_radar' ORDER BY se.id LIMIT 1) AS fill_pct FROM silos s $org_clause ORDER BY s.name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($silos as $s) {
            $rows[] = [
                $s['name'], $s['code']??'', $s['site_name']??'',
                ucwords(str_replace(['grain_','_'],' ',$s['commodity_type']??'')),
                number_format($s['capacity_tonnes']??0,2),
                round($s['fill_pct']??0,1).'%',
                ucfirst($s['status']??''),
            ];
        }
        break;

    case 'sensor':
        $title   = 'Sensor Readings Report';
        $headers = ['Sensor Name','Type','Silo','Last Reading','Unit','Battery %','Status','Last Seen'];
        $rows    = [];
        $wh = $org_id ? "WHERE se.organisation_id=$org_id" : "";
        $sensors = $pdo->query("SELECT se.*, si.name AS silo_name FROM sensors se LEFT JOIN silos si ON se.silo_id=si.id $wh ORDER BY si.name, se.sensor_type")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sensors as $s) {
            $rows[] = [
                $s['name'], ucwords(str_replace('_',' ',$s['sensor_type']??'')),
                $s['silo_name']??'—',
                $s['last_reading']??'—', $s['unit']??'',
                $s['battery_level']??'—',
                $s['is_active'] ? 'Active' : 'Inactive',
                $s['last_reading_at'] ? date('d M Y H:i', strtotime($s['last_reading_at'])) : '—',
            ];
        }
        break;

    case 'alerts':
        $title   = 'Alerts Report';
        $headers = ['Title','Severity','Silo','Message','Status','Triggered At','Resolved At'];
        $rows    = [];
        $wh = $org_id ? "WHERE a.organisation_id=$org_id" : "";
        $alerts = $pdo->query("SELECT a.*, si.name AS silo_name FROM alerts a LEFT JOIN silos si ON a.silo_id=si.id $wh ORDER BY a.triggered_at DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($alerts as $a) {
            $rows[] = [
                $a['title']??'', ucfirst($a['severity']??''), $a['silo_name']??'—',
                $a['message']??'', ucfirst($a['status']??''),
                $a['triggered_at'] ? date('d M Y H:i', strtotime($a['triggered_at'])) : '—',
                $a['resolved_at']  ? date('d M Y H:i', strtotime($a['resolved_at']))  : '—',
            ];
        }
        break;

    case 'tasks':
        $title   = 'Tasks Report';
        $headers = ['Title','Type','Priority','Assigned To','Silo','Due Date','Status'];
        $rows    = [];
        $wh = $org_id ? "WHERE t.organisation_id=$org_id" : "";
        $tasks = $pdo->query("SELECT t.*, u.first_name, u.last_name, si.name AS silo_name FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id LEFT JOIN silos si ON t.silo_id=si.id $wh ORDER BY t.due_date ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tasks as $t) {
            $rows[] = [
                $t['title']??'', ucfirst($t['type']??''), ucfirst($t['priority']??''),
                trim(($t['first_name']??'').' '.($t['last_name']??'')) ?: 'Unassigned',
                $t['silo_name']??'—',
                $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—',
                ucwords(str_replace('_',' ',$t['status']??'')),
            ];
        }
        break;

    case 'audit':
        $title   = 'Audit Log Report';
        $headers = ['Time','User','Action','Category','Description','IP Address','Browser','OS'];
        $rows    = [];
        $wh = $org_id ? "WHERE al.organisation_id=$org_id" : "";
        $logs = $pdo->query("SELECT al.*, u.first_name, u.last_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id $wh ORDER BY al.logged_at DESC LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($logs as $l) {
            $rows[] = [
                $l['logged_at'] ? date('d M Y H:i:s', strtotime($l['logged_at'])) : '—',
                trim(($l['first_name']??'').' '.($l['last_name']??'')) ?: 'System',
                $l['action']??'', ucfirst($l['category']??''),
                $l['description']??'', $l['ip_address']??'—',
                $l['browser']??'—', $l['os']??'—',
            ];
        }
        break;

    default:
        die("Unknown report type.");
}

$org_name = $org_id ? ($pdo->query("SELECT name FROM organisations WHERE id=$org_id")->fetchColumn() ?: 'SiloSmart') : 'SiloSmart';
$generated = date('d M Y H:i');

// ── OUTPUT ────────────────────────────────────────────────────
if ($format === 'csv') {
    $filename = strtolower(str_replace(' ','_',$title)).'_'.date('Ymd_His').'.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Pragma: no-cache');
    $out = fopen('php://output','w');
    // BOM for Excel UTF-8
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ["SiloSmart — $title"]);
    fputcsv($out, ["Organisation: $org_name", "Generated: $generated", "By: ".(trim(($user['first_name']??'').' '.($user['last_name']??'')) ?: $user['email']??'')]);
    fputcsv($out, []);
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, $row);
    fputcsv($out, []);
    fputcsv($out, ["Total records: ".count($rows)]);
    fclose($out);

} else {
    // HTML printable report
    $rows_html = '';
    foreach ($rows as $r) {
        $cells = implode('', array_map(fn($v) => '<td>'.htmlspecialchars($v).'</td>', $r));
        $rows_html .= "<tr>$cells</tr>\n";
    }
    $th = implode('', array_map(fn($h) => '<th>'.htmlspecialchars($h).'</th>', $headers));
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SiloSmart — {$title}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Lato','Segoe UI',sans-serif;background:#f8f9fc;color:#1a1a2e;padding:2rem}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:3px solid #D4A017}
.logo{font-size:1.5rem;font-weight:800;color:#1a1a2e}.logo span{color:#D4A017}
.meta{text-align:right;font-size:.82rem;color:#6b7280}
h1{font-size:1.25rem;font-weight:800;margin-bottom:.25rem;color:#1a1a2e}
table{width:100%;border-collapse:collapse;margin-top:1rem;font-size:.82rem}
thead th{background:#D4A017;color:#fff;padding:.6rem .85rem;text-align:left;font-weight:700;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase}
tbody tr:nth-child(even){background:#f1f5f9}
tbody tr:hover{background:#fef9e7}
tbody td{padding:.55rem .85rem;border-bottom:1px solid #e5e7eb;vertical-align:middle}
.footer{margin-top:1.5rem;font-size:.78rem;color:#9ca3af;text-align:center;border-top:1px solid #e5e7eb;padding-top:1rem}
.total{margin-top:.75rem;font-size:.85rem;font-weight:600;color:#374151}
@media print{body{padding:.5rem}@page{margin:1cm}}
</style>
</head>
<body>
<div class="header">
  <div><div class="logo">Silo<span>Smart</span></div><div style="font-size:.82rem;color:#6b7280;margin-top:.2rem">{$org_name}</div></div>
  <div class="meta"><div><strong>{$title}</strong></div><div>Generated: {$generated}</div><div>By: {$user['first_name']} {$user['last_name']}</div></div>
</div>
<table>
<thead><tr>{$th}</tr></thead>
<tbody>{$rows_html}</tbody>
</table>
<div class="total">Total records: {count($rows)}</div>
<div class="footer">SiloSmart Platform &mdash; Confidential. For authorised use only.</div>
<script>window.onload=()=>window.print()</script>
</body></html>
HTML;
}
