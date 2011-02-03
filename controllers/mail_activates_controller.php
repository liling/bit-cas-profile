<?php

App::import('Sanitize');
App::import('Vendor', 'Password');

class MailActivatesController extends AppController {

    var $components = array('CasAuth', 'Session', 'Mailer');
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
            $user = $this->User->findById($user['User']['id']);

            if ($this->data['MailActivate']['mail'] != $this->data['MailActivate']['mail2']) {
                $this->set('errors', array('mail' => '您两次输入的邮件地址不一致')); 
                return $this->render();
            }

            if (empty($user['User']['mail']) ||
                $user['User']['mail'] != $this->data['MailActivate']['mail'])
            {
                $this->MailActivate->set($this->data);
                $this->MailActivate->set('user_id', $user['User']['id']);
                $code = Password::generate(8, 7);
                $this->MailActivate->set('code', $code);
                if ($this->MailActivate->save()) {
                    $id = $this->MailActivate->getInsertID();
                    try {
                        $this->_send_confirm_mail(
                            $this->data['MailActivate']['mail'],
                            Router::url("/mail_activates/confirm/$id/$code", true));
                    } catch (Exception $e) {
                        $this->setFlash('邮件发送失败：'.$e->getMessage());
                        return $this->render();
                    }
                    $this->set('mail', $this->data['MailActivate']['mail']);
                    return $this->render('confirmation_mail_sent');
                } else {
                    $this->set('errors', $this->MailActivate->invalidFields());
                    return $this->render();
                }
            }
        }
    }

    /**
     * 发送确认信
     */
    function _send_confirm_mail($address, $url) {
        $user = $this->CasAuth->user();
        $this->Mailer->toName = $user['User']['fullname'];
        $this->Mailer->to = $address;
        $this->Mailer->subject = '校园网单点登录服务修改邮箱确认信';
        $this->set('user', $user['User']);
        $this->set('mail', $address);
        $this->set('confirm_url', $url);
        $this->Mailer->template = 'mail_activate_confirm';
        $this->Mailer->send();
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
