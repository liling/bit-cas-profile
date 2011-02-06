<?php  

App::import('Vendor', 'phpmailer', array('file' => 'phpmailer'.DS.'class.phpmailer.php')); 

/** 
 * This is a component to send email from CakePHP using PHPMailer 
 * @link http://bakery.cakephp.org/articles/view/94 
 * @see http://bakery.cakephp.org/articles/view/94 
 */ 

class MailerComponent 
{ 
  /** 
   * Send email using SMTP Auth by default. 
   */ 
    var $from = null;
    var $fromName = null;
    var $smtpUserName = null;
    var $smtpPassword = null;
    var $smtpHostNames = null;
    var $text_body = null; 
    var $html_body = null; 
    var $to = null; 
    var $toName = null; 
    var $subject = null; 
    var $cc = null; 
    var $bcc = null;
    var $layout = 'default';
    var $template = null;
    var $attachments = null; 

    var $controller; 

    function startup(&$controller) { 
        $this->controller = &$controller; 

        $this->from = Configure::read('Email.from');
        $this->fromName = Configure::read('Email.fromName');
        $this->smtpUserName = Configure::read('Email.smtpUsername');
        $this->smtpPassword = Configure::read('Email.smtpPassword');
        $this->smtpHostNames = Configure::read('Email.smtpHost');
        $this->bcc = Configure::read('Email.bcc');
    } 

    function bodyText() { 
		$viewClass = $this->controller->view;

		if ($viewClass != 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->controller->view);
		}

		$View = new $viewClass($this->controller);
		$View->layout = $this->layout;

        $content = $View->element('email' . DS . 'text' . DS . $this->template, array(), true);
        $View->layoutPath = 'email' . DS . 'text';
        return str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content));
    } 

    function bodyHTML() { 
		$viewClass = $this->controller->view;

		if ($viewClass != 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->controller->view);
		}

		$View = new $viewClass($this->controller);
		$View->layout = $this->layout;

        $htmlContent = $View->element('email' . DS . 'html' . DS . $this->template, array(), true);
        $View->layoutPath = 'email' . DS . 'html';
        return str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($htmlContent));
    } 

    function attach($filename, $asfile = '') { 
      if (empty($this->attachments)) { 
        $this->attachments = array(); 
        $this->attachments[0]['filename'] = $filename; 
        $this->attachments[0]['asfile'] = $asfile; 
      } else { 
        $count = count($this->attachments); 
        $this->attachments[$count+1]['filename'] = $filename; 
        $this->attachments[$count+1]['asfile'] = $asfile; 
      } 
    } 


    function send() { 
        $mail = new PHPMailer(); 

        $mail->IsSMTP();            // set mailer to use SMTP 
        $mail->SMTPAuth = true;     // turn on SMTP authentication 
        $mail->Host   = $this->smtpHostNames; 
        $mail->Username = $this->smtpUserName; 
        $mail->Password = $this->smtpPassword; 

        $mail->From     = $this->from; 
        $mail->FromName = $this->fromName; 
        $mail->AddAddress($this->to, $this->toName ); 
        $mail->AddReplyTo($this->from, $this->fromName ); 

        $mail->CharSet  = 'UTF-8'; 
        $mail->WordWrap = 50;  // set word wrap to 50 characters 

        if (is_array($this->bcc)) {
            foreach ($this->bcc as $addr) {
                $mail->AddBCC($addr);
            }
        }

        if (!empty($this->attachments)) { 
          foreach ($this->attachments as $attachment) { 
            if (empty($attachment['asfile'])) { 
              $mail->AddAttachment($attachment['filename']); 
            } else { 
              $mail->AddAttachment($attachment['filename'], $attachment['asfile']); 
            } 
          } 
        } 

        $mail->IsHTML(true);  // set email format to HTML 

        $mail->Subject = $this->subject; 
        $mail->AltBody = $this->bodyText(); 
        $mail->MsgHTML($this->bodyHTML()); 

        ob_start();
        $result = $mail->Send(); 
        ob_end_clean();

        if($result == false ) $result = $mail->ErrorInfo; 

        return $result; 
    } 
} 
?>
