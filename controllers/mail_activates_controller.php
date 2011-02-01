<?php

App::import('Sanitize');
App::import('Vendor', 'Password');

class MailActivatesController extends AppController {

    var $components = array('CasAuth', 'Session', 'Email');
    var $uses = array('MailActivate', 'User');

    function beforeFilter() {
        $this->CasAuth->allow('confirm');
    }

    /**
     * 设定邮件地址，用户提交请求后，系统发送邮件到用户的邮箱。
     */
    function setmail() {
        $this->set('title_for_layout', '修改邮件地址');

        if (!empty($this->data['MailActivate'])) {
            $user = $this->CasAuth->user();

            if ($this->data['MailActivate']['mail'] != $this->data['MailActivate']['mail2']) {
                $this->set('errors', array('mail' => '您两次输入的邮件地址不一致')); 
                return $this->render();
            }

            if (empty($user['User']['mail']) ||
                $user['User']['mail'] != $this->data['MailActivate']['mail'])
            {
                $this->MailActivate->set($this->data);
                $this->MailActivate->set('user_id', $user['User']['id']);
                $this->MailActivate->set('code', Password::generate(8, 7));
                if ($this->MailActivate->save()) {
                    $this->_send_confirm_mail($this->data['MailActivate']['mail']);
                    $this->set('mail', $this->data['MailActivate']['mail']);
                    return $this->render('confirmation_mail_sent');
                } else {
                    $this->set('errors', $this->MailActivate->invalidFields());
                    return $this->render();
                }
            }
        }
    }

    function _send_confirm_mail($address) {
        $this->Email->from = '系统自动生成邮件.请勿回复 <no-reply@bit.edu.cn>';
        $this->Email->to = $address;
        $this->Email->bcc = 'liling@bit.edu.cn';
        $this->Email->subject = '校园网单点登录服务修改邮箱确认信';
        $this->Email->template = 'mail_activate_confirm';
        $this->Email->sendAs = 'both';

        $this->Email->delivery = Configure::read('Email.delivery');
        if ($this->Email->delivery == 'smtp')
            $this->Email->smtpOptions = Configure::read('SMTP.options');
        $this->Email->send();
    }

    function confirm($id, $code) {
        $this->set('title_for_layout', '邮件地址确认');

        $id = Sanitize::paranoid($id);
        $code = Sanitize::paranoid($code);

        $ma = $this->MailActivate->findById($id);
        if ($ma != null && $ma['MailActivate']['code'] == $code) {
            $this->User->read('id', $ma['MailActivate']['user_id']);
            $this->User->set('mail', $ma['MailActivate']['mail']);
            $this->User->save();
            $this->MailActivate->delete($id, false);

            return $this->render('succeeded');
        } else {
            return $this->render('failed');
        }
    }

}
