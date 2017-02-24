<?php

require 'vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

function sendMail($body){

  $mail = new phpmailer(); // create a new object
  $mail->IsSMTP(); // enable SMTP
 // $mail->SMTPDebug = 2; // debugging: 1 = errors and messages, 2 = messages only
  $mail->SMTPAuth = true; // authentication enabled
  $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
  $mail->Host = "smtp.gmail.com";
  $mail->Port = 465; // or 587
  $mail->IsHTML(true);
  $mail->Username = "black202knight@gmail.com";
  $mail->Password = "Bla-22-Kni";
  $mail->SetFrom("black202knight@gmail.com", "Black Knight");
  $mail->Subject = "Daily Trade Rank Update";
  $mail->Body = $body;
  $mail->AddAddress("kalpan.bhatt@gmail.com");
  $mail->addReplyTo('info@example.com', 'Information');
		if(!$mail->Send()) {
				echo "Mailer Error: " . $mail->ErrorInfo;
		 } else {
				echo "Email has been sent";
		}
}
