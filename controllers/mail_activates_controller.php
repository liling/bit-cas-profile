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
                    $result = $this->_send_confirm_mail(
                        $this->data['MailActivate']['mail'],
                        Router::url("/mail_activates/confirm/$id/$code", true));
                    if ($result === true) {
                        $this->set('mail', $this->data['MailActivate']['mail']);
                        return $this->render('confirmation_mail_sent');
                    } else {
                        $this->Session->setFlash('邮件发送失败：'.$result);
                        return $this->render();
                    }
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
        return $this->Mailer->send();
    }

    /**
     * 确认邮件地址修改
     */
    function confirm($id, $code) {
        $this->set('title_for_layout', '邮件地址确认');

        $id = Sanitize::paranoid($id);
        $code = Sanitize::paranoid($code);

        $ma = $this->MailActivate->findById($id);
        try {
            // 检查验证码是否正确
            if (empty($ma) || $ma['MailActivate']['code'] != $code) {
                throw new Exception('验证码错误');
            }

            // 检查验证码是否使用过
            if ($ma['MailActivate']['finished']) {
                throw new Exception('验证码已经使用过');
            }

            // 检查验证码是否过期
            $now = new DateTime('now');
            $created = date_create($ma['MailActivate']['created']);
            $created->add(new DateInterval('P3D'));
            if ($now > $created) {
                throw new Exception('验证码已过期');
            }

            $this->User->read('id', $ma['MailActivate']['user_id']);
            $this->User->set('mail', $ma['MailActivate']['mail']);
            $this->User->save();
            $this->MailActivate->set($ma);
            $this->MailActivate->set('finished', true);
            $this->MailActivate->save();

            return $this->render('succeeded');
        } catch (Exception $e) {
            $this->set('reason', $e->getMessage());
            return $this->render('failed');
        }
    }

}
