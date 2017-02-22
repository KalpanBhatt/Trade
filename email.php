<?php

require 'vendor/phpmailer/phpmailer/PHPMailerAutoload.php';




function sendMail(){

  $mail = new phpmailer(); // create a new object
  $mail->IsSMTP(); // enable SMTP
  $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
  $mail->SMTPAuth = true; // authentication enabled
  $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
  $mail->Host = "smtp.gmail.com";
  $mail->Port = 465; // or 587
  $mail->IsHTML(true);
  $mail->Username = "email@gmail.com";
  $mail->Password = "password";
  $mail->SetFrom("example@gmail.com");
  $mail->Subject = "Test";
  $mail->Body = "hello";
  $mail->AddAddress("email@gmail.com");
  
		if(!$mail->Send()) {
				echo "Mailer Error: " . $mail->ErrorInfo;
		 } else {
				echo "Message has been sent";
		}
}
