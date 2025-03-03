<?php
namespace App\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController {
    public function sendVerificationEmail($email, $code) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'hperformanceexhaust@gmail.com';
            $mail->Password = 'wolv wvyy chhl rvvm';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('your-email@example.com', 'Capstone System');
            $mail->addAddress($email);
            $mail->Subject = "Verification Code";
            $mail->Body = "Your verification code is: " . $code;

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}