<?php

App::import('Sanitize');
App::import('Vendor', 'Password');

class MailActivatesController extends AppController {

    var $components = array('CasAuth', 'Session', 'Mailer', 'LdapSync');
    var $uses = array('MailActivate', 'User');

    function beforeFilter() {
        $this->CasAuth->allow('confirm');
    }

    function index() {
        return $this->flash('跳转中', '/mail_activates/setmail', 0);
    }

    /**
     * 设定邮件地址，用户提交请求后，系统发送邮件到用户的邮箱。
     */
    function setmail() {
        $this->set('title_for_layout', '修改密保邮件地址');

        if (!empty($this->data['MailActivate'])) {
            $d = $this->data['MailActivate'];
            $errors = array();
            try {
                if (empty($d['mail'])) {
                    $errors['mail'] = '您必须输入一个邮件地址';
                    throw new Exception();
                }

                if ($d['mail'] != $d['mail2']) {
                    $errors['mail2'] = '您两次输入的邮件地址不一致';
                    throw new Exception();
                }
 
                // 检查用户是否修改了密保邮件地址
                $user = $this->CasAuth->user();
                $user = $this->User->findById($user['User']['id']);
                if ($user['User']['mail'] == $d['mail']) {
                    $errors['mail'] = '此邮件地址就是您原来的密保邮件地址';
                    throw new Exception();
                }

                // 保存数据
                $code = Password::generate(8, 7);
                $this->MailActivate->set($this->data);
                $this->MailActivate->set('user_id', $user['User']['id']);
                $this->MailActivate->set('code', $code);
                if (!$this->MailActivate->save()) {
                    $errors = $this->MailActivate->invalidFields();
                    throw new Exception();
                }

                // 发送确认邮件
                $id = $this->MailActivate->getInsertID();
                $url = Router::url("/mail_activates/confirm/$id/$code", true);
                $result = $this->_send_confirm_mail($d['mail'], $url);
                if ($result !== true) {
                    throw new Exception('邮件发送失败：'.$result);
                }

                $this->set('mail', $this->data['MailActivate']['mail']);
                return $this->render('confirmation_mail_sent');

            } catch (Exception $e) {
                $m = $e->getMessage();
                if (!empty($m)) $this->setFlash($m);
                $this->set('errors', $errors);
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
    function confirm($id = 0, $code = null) {
        $this->set('title_for_layout', '密保邮件地址确认');

        try {
            if ($code == null) {
                throw new Exception('请提供验证码');
            }

            $id = Sanitize::paranoid($id);
            $code = Sanitize::paranoid($code);

            $ma = $this->MailActivate->findById($id);

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

            // 保存邮箱到用户数据
            $d = array('id' => $ma['MailActivate']['user_id'],
                       'mail' => $ma['MailActivate']['mail']);
            if (!$this->User->save('MailActivate' => $d, false)) {
                throw new Exception('保存到数据库时失败');
            }

            // 将邮箱修改记录标志为已完成
            $d = array('id' => $ma['MailActivate']['id'], 'finished' => true);
            if (!$this->MailActivate->save(array('MailActivate'=>$d), false)) {
                throw new Exception('保存到数据库时失败');
            }

            // 保存数据到 LDAP
            $user = $this->User->findById($ma['MailActivate']['user_id']);
            $rst = $this->LdapSync->updateUser(
                $user['User']['username'], 'mail', $ma['MailActivate']['mail']);
            if ($rst !== true) {
                throw new Exception('保存到LDAP时失败：'.$rst);
            }

            return $this->render('succeeded');
        } catch (Exception $e) {
            $this->set('reason', $e->getMessage());
            return $this->render('failed');
        }
    }

}
