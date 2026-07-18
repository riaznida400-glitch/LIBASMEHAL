<?php
/**
 * ============================================================
 *  LIBAS MEHAL — SEND ORDER EMAIL
 * ============================================================
 *  Called by checkout.html (fetch/POST) when a customer clicks
 *  "Place Order". This file:
 *    1. Re-validates every field on the server — never trust
 *       client-side validation alone, since it can be bypassed.
 *    2. Rejects empty / spam submissions.
 *    3. Builds a professional HTML order email.
 *    4. Sends it to RECIPIENT_EMAIL (see config.php) using PHP's
 *       built-in mail() function, which works out of the box on
 *       Hostinger shared hosting — no extra libraries needed.
 *    5. Replies with JSON so the page can show a success or
 *       error message to the customer.
 *
 *  To change WHO receives order emails, edit config.php — not
 *  this file.
 * ============================================================
 */

header('Content-Type: application/json; charset=UTF-8');
require __DIR__ . '/config.php';

function respond($success, $message = ''){
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Only accept POST requests — protects against empty/GET/direct hits.
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    respond(false, 'Invalid request method.');
}

// --- Honeypot spam check -------------------------------------------------
// "website" is a hidden field real customers never see or fill in.
// If it has a value, the request almost certainly came from a bot.
if(!empty($_POST['website'])){
    respond(true); // pretend success so bots don't learn the trap worked
}

// --- Collect + sanitize every field --------------------------------------
function clean($value){
    $value = trim((string)($value ?? ''));
    // Strip line breaks so nothing can be used for email header injection.
    $value = str_replace(["\r", "\n"], ' ', $value);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$fullName   = clean($_POST['fullName']    ?? '');
$phone      = clean($_POST['phone']       ?? '');
$email      = clean($_POST['email']       ?? '');
$address    = clean($_POST['address']     ?? '');
$city       = clean($_POST['city']        ?? '');
$postalCode = clean($_POST['postalCode']  ?? '');
$notes      = clean($_POST['notes']       ?? '');
$grandTotal = clean($_POST['grand_total'] ?? '0');
$cartJson   = $_POST['cart_json'] ?? '[]';

// --- Server-side validation — blocks empty / malformed submissions -------
$errors = [];
if(mb_strlen($fullName) < 2)                       $errors[] = 'full name';
if(!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone))   $errors[] = 'phone number';
if(!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'email address';
if(mb_strlen($address) < 5)                         $errors[] = 'delivery address';
if(mb_strlen($city) < 2)                            $errors[] = 'city';
if(mb_strlen($postalCode) < 3)                      $errors[] = 'postal code';

$cartItems = json_decode($cartJson, true);
if(!is_array($cartItems) || count($cartItems) === 0) $errors[] = 'cart items';

if(!empty($errors)){
    respond(false, 'Please check the following field(s): ' . implode(', ', $errors) . '.');
}

// --- Build the order-summary table rows ----------------------------------
$rowsHtml = '';
foreach($cartItems as $item){
    $article   = htmlspecialchars((string)($item['article'] ?? ''), ENT_QUOTES, 'UTF-8');
    $code      = htmlspecialchars((string)($item['code']    ?? ''), ENT_QUOTES, 'UTF-8');
    $qty       = (int)($item['qty'] ?? 0);
    $price     = (float)($item['price'] ?? 0);
    $lineTotal = (float)($item['lineTotal'] ?? ($price * $qty));

    $rowsHtml .= '
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #eee2c9;">' . $article . '<br><span style="font-size:11px;color:#8a7d6d;">' . $code . '</span></td>
            <td style="padding:10px 12px;border-bottom:1px solid #eee2c9;text-align:center;">' . $qty . '</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eee2c9;text-align:right;">PKR ' . number_format($price) . '</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eee2c9;text-align:right;">PKR ' . number_format($lineTotal) . '</td>
        </tr>';
}

$grandTotalFormatted = 'PKR ' . number_format((float)$grandTotal);
$orderDateTime = date('d M Y, h:i A');

// --- Build the professional HTML order email -----------------------------
$subject = 'NEW ORDER RECEIVED - ' . SITE_NAME . ' (' . $fullName . ')';

$htmlBody = '
<div style="font-family:Georgia,\'Times New Roman\',serif;background:#f6efe0;padding:30px;">
  <div style="max-width:640px;margin:0 auto;background:#fbf6ec;border:1px solid #e4d9c2;border-radius:6px;overflow:hidden;">

    <div style="background:#6b1f2a;color:#fbf6ec;padding:22px 28px;">
      <h1 style="margin:0;font-size:20px;letter-spacing:.06em;">NEW ORDER RECEIVED</h1>
      <p style="margin:6px 0 0;font-size:13px;opacity:.9;">' . SITE_NAME . '</p>
    </div>

    <div style="padding:24px 28px;">
      <h2 style="font-size:15px;margin:0 0 14px;color:#241f1c;border-bottom:1px solid #e4d9c2;padding-bottom:8px;">Customer Information</h2>
      <table style="width:100%;font-size:13.5px;color:#241f1c;border-collapse:collapse;">
        <tr><td style="padding:5px 0;width:150px;color:#8a7d6d;">Full Name</td><td style="padding:5px 0;">' . $fullName . '</td></tr>
        <tr><td style="padding:5px 0;color:#8a7d6d;">Phone Number</td><td style="padding:5px 0;">' . $phone . '</td></tr>
        <tr><td style="padding:5px 0;color:#8a7d6d;">Email</td><td style="padding:5px 0;">' . $email . '</td></tr>
        <tr><td style="padding:5px 0;color:#8a7d6d;vertical-align:top;">Delivery Address</td><td style="padding:5px 0;">' . $address . '</td></tr>
        <tr><td style="padding:5px 0;color:#8a7d6d;">City</td><td style="padding:5px 0;">' . $city . '</td></tr>
        <tr><td style="padding:5px 0;color:#8a7d6d;">Postal Code</td><td style="padding:5px 0;">' . $postalCode . '</td></tr>
        <tr><td style="padding:5px 0;color:#8a7d6d;vertical-align:top;">Order Notes</td><td style="padding:5px 0;">' . ($notes !== '' ? $notes : '<em>None provided</em>') . '</td></tr>
      </table>

      <h2 style="font-size:15px;margin:28px 0 14px;color:#241f1c;border-bottom:1px solid #e4d9c2;padding-bottom:8px;">Order Summary</h2>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:#eee2c9;">
            <th style="padding:9px 12px;text-align:left;">Product Name</th>
            <th style="padding:9px 12px;text-align:center;">Quantity</th>
            <th style="padding:9px 12px;text-align:right;">Price</th>
            <th style="padding:9px 12px;text-align:right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>

      <table style="width:100%;margin-top:10px;">
        <tr>
          <td style="text-align:right;font-size:15px;font-weight:bold;color:#6b1f2a;padding:10px 12px;">
            Grand Total: ' . $grandTotalFormatted . '
          </td>
        </tr>
      </table>

      <p style="font-size:12px;color:#8a7d6d;margin-top:20px;">Date &amp; Time: ' . $orderDateTime . '</p>
    </div>
  </div>
</div>';

// --- Send the email --------------------------------------------------------
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
// Replies to this notification go straight to the customer's own email.
$headers .= "Reply-To: " . $fullName . " <" . $email . ">\r\n";

$sent = mail(RECIPIENT_EMAIL, $subject, $htmlBody, $headers);

if($sent){
    respond(true, 'Order email sent.');
}else{
    respond(false, 'The order email could not be sent. Please try again in a moment.');
}
