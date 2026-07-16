<?php
// ============================================
// EDUCORE - Mailer (PHPMailer via Composer)
// ============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send OTP email to user
 */
function sendOTPEmail($toEmail, $toName, $otp) {
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host        = SMTP_HOST;
        $mail->SMTPAuth    = true;
        $mail->Username    = SMTP_USER;
        $mail->Password    = SMTP_PASS;
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = SMTP_PORT;
        $mail->CharSet     = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = 'Your EDUCORE Verification Code: ' . $otp;
        $mail->Body    = getOTPEmailHTML($toName, $otp);
        $mail->AltBody = "Hi {$toName},\n\nYour EDUCORE verification code is: {$otp}\n\nExpires in 10 minutes.\n\n— EDUCORE Team";

        $mail->send();
        return ['success' => true, 'message' => 'OTP sent to ' . $toEmail];

    } catch (Exception $e) {
        error_log('Mailer Error: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Send Welcome email after successful login/verification
 */
function sendWelcomeEmail($toEmail, $toName, $role = 'student') {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = '🎉 Welcome to EDUCORE, ' . $toName . '!';
        $mail->Body    = getWelcomeEmailHTML($toName, $role);
        $mail->AltBody = "Hi {$toName},\n\nWelcome to EDUCORE! Your account is verified and ready.\nVisit: http://localhost/edu-core\n\n— EDUCORE Team";
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log('Welcome mail error: ' . $e->getMessage());
        return ['success' => false];
    }
}

/**
 * Welcome email HTML template
 */
function getWelcomeEmailHTML($name, $role) {
    $year       = date('Y');
    $dashUrl    = 'http://localhost/edu-core/dashboard/' . $role . '.php';
    $coursesUrl = 'http://localhost/edu-core/courses/index.php';
    $roleLabel  = ucfirst($role);
    $emoji      = $role === 'teacher' ? '👨‍🏫' : ($role === 'admin' ? '🛡️' : '🎓');
    $roleMsg    = $role === 'teacher'
        ? 'Start creating courses and share your knowledge with thousands of students.'
        : 'Browse hundreds of courses and start learning something new today.';

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#0a0f1e;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f1e;padding:40px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#111827;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#22c55e,#3b82f6);padding:36px;text-align:center;">
            <div style="font-size:48px;margin-bottom:12px;">{$emoji}</div>
            <div style="font-size:32px;font-weight:900;color:#fff;letter-spacing:-1px;">
              EDU<span style="color:#bbf7d0;">CORE</span>
            </div>
            <div style="color:rgba(255,255,255,0.9);font-size:16px;margin-top:8px;font-weight:600;">
              Welcome aboard!
            </div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px 36px;">
            <h2 style="color:#f9fafb;font-size:24px;font-weight:800;margin:0 0 14px;">
              Hi {$name}, you're all set! 🚀
            </h2>
            <p style="color:#9ca3af;font-size:15px;line-height:1.8;margin:0 0 28px;">
              Your EDUCORE account is verified and ready to go.
              You are logged in as a <strong style="color:#22c55e;">{$roleLabel}</strong>.
              {$roleMsg}
            </p>

            <!-- Feature highlights -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
              <tr>
                <td style="background:#1a2332;border-radius:10px;padding:16px;width:30%;text-align:center;vertical-align:top;">
                  <div style="font-size:24px;margin-bottom:6px;">📚</div>
                  <div style="font-size:12px;color:#9ca3af;font-weight:600;">200+ Courses</div>
                </td>
                <td style="width:3%;"></td>
                <td style="background:#1a2332;border-radius:10px;padding:16px;width:30%;text-align:center;vertical-align:top;">
                  <div style="font-size:24px;margin-bottom:6px;">🏆</div>
                  <div style="font-size:12px;color:#9ca3af;font-weight:600;">Certificates</div>
                </td>
                <td style="width:3%;"></td>
                <td style="background:#1a2332;border-radius:10px;padding:16px;width:30%;text-align:center;vertical-align:top;">
                  <div style="font-size:24px;margin-bottom:6px;">🧠</div>
                  <div style="font-size:12px;color:#9ca3af;font-weight:600;">AI Quizzes</div>
                </td>
              </tr>
            </table>

            <!-- CTA Buttons -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td style="padding-right:12px;">
                  <a href="{$dashUrl}" style="display:inline-block;background:linear-gradient(135deg,#22c55e,#3b82f6);color:#fff;text-decoration:none;padding:13px 24px;border-radius:8px;font-weight:700;font-size:14px;">
                    Go to Dashboard →
                  </a>
                </td>
                <td>
                  <a href="{$coursesUrl}" style="display:inline-block;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);color:#f9fafb;text-decoration:none;padding:13px 24px;border-radius:8px;font-weight:600;font-size:14px;">
                    Browse Courses
                  </a>
                </td>
              </tr>
            </table>

            <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0;border-top:1px solid rgba(255,255,255,0.05);padding-top:20px;">
              If you didn't create this account, please ignore this email or contact support.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#0d1117;padding:20px 36px;border-top:1px solid rgba(255,255,255,0.05);">
            <p style="color:#4b5563;font-size:12px;margin:0;text-align:center;">
              © {$year} EDUCORE. All rights reserved.<br>
              You're receiving this because you just created an account.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
function getOTPEmailHTML($name, $otp) {
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#0a0f1e;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f1e;padding:40px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#111827;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#22c55e,#3b82f6);padding:32px;text-align:center;">
            <div style="font-size:32px;font-weight:900;color:#fff;letter-spacing:-1px;">
              EDU<span style="color:#bbf7d0;">CORE</span>
            </div>
            <div style="color:rgba(255,255,255,0.85);font-size:14px;margin-top:6px;">
              Secure Login Verification
            </div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px 36px;">
            <h2 style="color:#f9fafb;font-size:22px;font-weight:700;margin:0 0 12px;">
              Hi {$name}, here's your code 👋
            </h2>
            <p style="color:#9ca3af;font-size:15px;line-height:1.7;margin:0 0 32px;">
              Use the verification code below to complete your sign-in.
              This code expires in <strong style="color:#f9fafb;">10 minutes</strong>.
            </p>

            <!-- OTP Box -->
            <div style="background:#1a2332;border:2px solid rgba(34,197,94,0.35);border-radius:14px;padding:32px;text-align:center;margin-bottom:32px;">
              <div style="font-size:11px;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;margin-bottom:14px;">
                Your Verification Code
              </div>
              <div style="font-size:52px;font-weight:900;letter-spacing:16px;color:#22c55e;font-family:monospace;text-shadow:0 0 30px rgba(34,197,94,0.4);">
                {$otp}
              </div>
              <div style="font-size:12px;color:#6b7280;margin-top:14px;">
                ⏱ Expires in 10 minutes &nbsp;·&nbsp; Do not share this code
              </div>
            </div>

            <div style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15);border-radius:10px;padding:14px 16px;margin-bottom:24px;">
              <p style="color:#fca5a5;font-size:13px;margin:0;line-height:1.5;">
                🔒 <strong>Security notice:</strong> EDUCORE will never ask for this code via phone or chat.
                If you didn't request this, change your password immediately.
              </p>
            </div>

            <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0;">
              If you didn't try to sign in, you can safely ignore this email.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#0d1117;padding:20px 36px;border-top:1px solid rgba(255,255,255,0.05);">
            <p style="color:#4b5563;font-size:12px;margin:0;text-align:center;">
              © {$year} EDUCORE. All rights reserved.<br>
              This is an automated message — please do not reply.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
