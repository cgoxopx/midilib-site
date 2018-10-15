<?php
defined("HERE") or die("mail error");
require_once(HERE.'/class/phpmailer/PHPMailer.php');
require_once(HERE.'/class/phpmailer/Exception.php');
function sendmail($to,$subj,$body){
  global $CONFIG;
  $mail = new PHPMailer(true); // Passing `true` enables exceptions 
  try {
    //Server settings 
    $mail->SMTPDebug = 2;
    // Enable verbose debug output 
    $mail->isSMTP();
    // Set mailer to use SMTP 
    $mail->Host = $CONFIG['mail']['host'];
    // Specify main and backup SMTP servers 
    $mail->SMTPAuth = true;
    // Enable SMTP authentication
    $mail->Username = $CONFIG['mail']['name'];
    // SMTP username
    $mail->Password = $CONFIG['mail']['pwd'];
    // SMTP password
    $mail->SMTPSecure = $CONFIG['mail']['secure'];
    // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $CONFIG['mail']['port'];
    // TCP port to connect to
    // Recipients
    $mail->setFrom($CONFIG['mail']['name']);
    $mail->addAddress($to);
    //Content
    $mail->isHTML(true);
    // Set email format to HTML
    $mail->Subject = $subj;
    $mail->Body = $body;
    $mail->AltBody = $body;
    $mail->send();
    return true;
  }catch (Exception $e) {
    echo $e;
    return false;
  }
}