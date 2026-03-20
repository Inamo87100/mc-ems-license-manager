<?php

class EmailNotifications {
    public function sendNewLicenseNotification($recipientEmail, $licenseDetails) {
        $subject = 'New License Issued';
        $message = "Dear User,\n\nYour new license has been issued.\nDetails: $licenseDetails\n\nThank you!";
        $headers = 'From: noreply@yourdomain.com';
        mail($recipientEmail, $subject, $message, $headers);
    }

    public function sendRenewalReminder($recipientEmail, $licenseDetails) {
        $subject = 'Renewal Reminder';
        $message = "Dear User,\n\nThis is a reminder that your license will expire in 30 days.\nDetails: $licenseDetails\n\nThank you!";
        $headers = 'From: noreply@yourdomain.com';
        mail($recipientEmail, $subject, $message, $headers);
    }

    public function sendRenewalConfirmation($recipientEmail, $licenseDetails) {
        $subject = 'Renewal Confirmation';
        $message = "Dear User,\n\nYour license has been renewed successfully.\nDetails: $licenseDetails\n\nThank you!";
        $headers = 'From: noreply@yourdomain.com';
        mail($recipientEmail, $subject, $message, $headers);
    }

    public function sendExpirationNotice($recipientEmail, $licenseDetails) {
        $subject = 'License Expiration Notice';
        $message = "Dear User,\n\nYour license has expired.\nDetails: $licenseDetails\n\nPlease renew your license.";
        $headers = 'From: noreply@yourdomain.com';
        mail($recipientEmail, $subject, $message, $headers);
    }
}
?>