<?php
/**
 * ============================================================
 *  LIBAAS MEHAL — ORDER EMAIL CONFIGURATION
 * ============================================================
 *  Everything you might need to change later lives in this one
 *  file. You do NOT need to touch send-order.php for routine
 *  changes like updating the recipient email address.
 * ============================================================
 */

// 1) RECIPIENT_EMAIL
//    Every "New Order" notification is delivered to this address.
//    Change this any time your order-handling email changes.
define('RECIPIENT_EMAIL', 'support@libaasmehal.com');

// 2) SENDER_EMAIL / SENDER_NAME
//    The "From" address PHP's mail() sends with. For the best
//    inbox delivery on Hostinger, this should be a real mailbox
//    on your own domain — create it once under
//    hPanel > Emails > Email Accounts (e.g. orders@libaasmehal.com).
//    Using an address on a different domain than your website
//    is the most common reason order emails land in spam.
define('SENDER_EMAIL', 'support@libaasmehal.com');
define('SENDER_NAME',  'Libaas Mehal Website');

// 3) SITE_NAME
//    Shown in the email subject line and heading.
define('SITE_NAME', 'Libaas Mehal');
