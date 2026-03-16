<?php
// PickleBoard — contact.php
// Handles demo request form submissions from the landing page.
// POST {name, email, facility, courts, plan, message}

header('Content-Type: application/json');
header('Cache-Control: no-store');

define('TO_EMAIL',   'peluchettejoel+pickleboard@gmail.com');
define('FROM_EMAIL', 'noreply@pickleboard.io');
define('FROM_NAME',  'PickleBoard Demo Requests');

function json_out($arr) {
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_out(['ok' => false, 'error' => 'Method not allowed']);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Sanitize inputs ───────────────────────────────────────────────────────────
$name     = trim(strip_tags($body['name']     ?? ''));
$email    = trim(strip_tags($body['email']    ?? ''));
$facility = trim(strip_tags($body['facility'] ?? ''));
$courts   = trim(strip_tags($body['courts']   ?? 'Not specified'));
$plan     = trim(strip_tags($body['plan']     ?? 'Not specified'));
$message  = trim(strip_tags($body['message']  ?? ''));

// ── Validate ──────────────────────────────────────────────────────────────────
if (!$name || !$email || !$facility) {
    json_out(['ok' => false, 'error' => 'Name, email, and facility are required.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['ok' => false, 'error' => 'Invalid email address.']);
}

// ── Build email ───────────────────────────────────────────────────────────────
$subject = "PickleBoard Demo Request — {$name} ({$facility})";

$text = <<<EOT
New demo request from pickleboard.io

────────────────────────────────────
  Name:       {$name}
  Email:      {$email}
  Facility:   {$facility}
  Courts:     {$courts}
  Plan:       {$plan}
────────────────────────────────────

Message:
{$message}

────────────────────────────────────
Submitted: {$_SERVER['REQUEST_TIME']}
IP:        {$_SERVER['REMOTE_ADDR']}
EOT;

$html = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'/></head>
<body style='font-family:monospace;background:#0a0a0a;color:#e0e0e0;padding:32px;'>
  <div style='max-width:560px;margin:0 auto;'>
    <div style='font-size:22px;font-weight:bold;color:#39ff14;letter-spacing:4px;margin-bottom:8px;'>PICKLEBOARD</div>
    <div style='font-size:12px;color:#888;letter-spacing:2px;margin-bottom:32px;border-bottom:1px solid #222;padding-bottom:16px;'>NEW DEMO REQUEST</div>

    <table style='width:100%;border-collapse:collapse;margin-bottom:24px;'>
      <tr><td style='padding:10px 0;border-bottom:1px solid #1a1a1a;color:#888;width:120px;font-size:13px;'>Name</td>
          <td style='padding:10px 0;border-bottom:1px solid #1a1a1a;font-size:13px;'>{$name}</td></tr>
      <tr><td style='padding:10px 0;border-bottom:1px solid #1a1a1a;color:#888;font-size:13px;'>Email</td>
          <td style='padding:10px 0;border-bottom:1px solid #1a1a1a;font-size:13px;'><a href='mailto:{$email}' style='color:#39ff14;'>{$email}</a></td></tr>
      <tr><td style='padding:10px 0;border-bottom:1px solid #1a1a1a;color:#888;font-size:13px;'>Facility</td>
          <td style='padding:10px 0;border-bottom:1px solid #1a1a1a;font-size:13px;'>{$facility}</td></tr>
      <tr><td style='padding:10px 0;border-bottom:1px solid #1a1a1a;color:#888;font-size:13px;'>Courts</td>
          <td style='padding:10px 0;border-bottom:1px solid #1a1a1a;font-size:13px;'>{$courts}</td></tr>
      <tr><td style='padding:10px 0;color:#888;font-size:13px;'>Plan</td>
          <td style='padding:10px 0;font-size:13px;color:#39ff14;'>{$plan}</td></tr>
    </table>

    " . ($message ? "
    <div style='background:#111;border:1px solid #222;border-radius:8px;padding:16px;margin-bottom:24px;'>
      <div style='font-size:11px;color:#888;letter-spacing:2px;margin-bottom:8px;'>MESSAGE</div>
      <div style='font-size:13px;line-height:1.7;color:#ccc;'>" . nl2br(htmlspecialchars($message)) . "</div>
    </div>" : "") . "

    <div style='font-size:11px;color:#444;border-top:1px solid #1a1a1a;padding-top:16px;'>
      Submitted from pickleboard.io &nbsp;·&nbsp; {$_SERVER['REMOTE_ADDR']}
    </div>
  </div>
</body>
</html>";

// ── Send ──────────────────────────────────────────────────────────────────────
$boundary = md5(uniqid());
$headers  = implode("\r\n", [
    "From: " . FROM_NAME . " <" . FROM_EMAIL . ">",
    "Reply-To: {$name} <{$email}>",
    "MIME-Version: 1.0",
    "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
    "X-Mailer: PickleBoard/1.0",
]);

$body_mime = implode("\r\n", [
    "--{$boundary}",
    "Content-Type: text/plain; charset=UTF-8",
    "",
    $text,
    "--{$boundary}",
    "Content-Type: text/html; charset=UTF-8",
    "",
    $html,
    "--{$boundary}--",
]);

$sent = mail(TO_EMAIL, $subject, $body_mime, $headers);

if ($sent) {
    json_out(['ok' => true]);
} else {
    http_response_code(500);
    json_out(['ok' => false, 'error' => 'Mail could not be sent. Check server mail configuration.']);
}
