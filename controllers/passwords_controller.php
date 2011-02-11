<?php

App::import('Sanitize');
App::import('Vendor', 'Password');

class PasswordsController extends AppController {

    var $components = array('CasAuth', 'Session', 'Mailer', 'LdapSync', 'ShortMessage');
    var $uses = array('PasswordRecovery', 'User', 'Person', 'SmsLog');

    function beforeFilter() {
        $this->CasAuth->allow('recovery', 'recovery_confirm');
    }

    function index() {
        return $this->flash('跳转到修改密码页面', '/passwords/change', 0);
    }

    /**
     * 修改密码。
     *
     * 首先判断用户两次输入的密码是否一致，然后通过 bind 到 LDAP 服务器的方法，
     * 判断用户输入的旧密码是否正确。如果可以正常连接，则继续用该连接修改用户
     * 的密码。
     *
     * LDAP 服务器上用户必须拥有修改本人密码的权限。
     */
    function change() {
        $this->set('title_for_layout', '修改密码');

        if (!empty($this->data['Password'])) {
            $pwd = $this->data['Password'];
            $errors = array();

            try {
                if (empty($pwd['new'])) {
                    $errors['new'] = '您必须输入新密码';
                    throw new Exception();
                }

                if ($pwd['new'] != $pwd['new2']) {
                    $errors['new2'] = '您两次输入的密码不一致'; 
                    throw new Exception();
                }

                // 检查用户输入的密码是否正确
                $user = $this->CasAuth->user();
                $filter = $this->Person->primaryKey.'='.$user['User']['username'];
                $person = $this->Person->find('first', array('conditions'=>$filter));
                $ldap = ConnectionManager::getDataSource('ldap');
                $r = $ldap->auth($person['Person']['dn'], $pwd['old']);
                if ($r !== true) {
                    $errors['old'] = '旧密码错误';
                    throw new Exception();
                }

                // 用户输入的密码正确
                $newinfo['userpassword']='{md5}'.base64_encode(pack('H*',md5($pwd['new'])));
                if (!@ldap_modify($ldap->database, $person['Person']['dn'], $newinfo)) {
                    throw new Exception('无法连接LDAP数据库修改密码：'.@ldap_error($ldap->database));
                }

                return $this->render('change_succeeded');

            } catch (Exception $e) {
                $m = $e->getMessage();
                if (!empty($m)) $this->Session->setFlash($m);
                $this->set('errors', $errors);
            }
        }

        return $this->render();
    }

    /**
     * 让用户输入自己的用户名（工号/学号）以及电子邮件地址，判断用户的输入是否
     * 正确。如果用户输入的信息匹配，则向用户的电子信箱发送修改密码确认信。
     */
    function recovery() {
        $errors = array();
        $this->set('title_for_layout', '找回密码');

        if ($this->CasAuth->user()) {
            return $this->redirect('/users/view');
        }

        if (!empty($this->data['Password'])) {
            try {
                $rec = $this->data['Password'];

                // 如果输入的手机号码，则将 via 设置为 mobile，否则是 mail
                if (preg_match('/^1[0-9]{10}$/', $rec['mail'])) {
                    $rec['via'] = 'mobile';
                } else {
                    $rec['via'] = 'mail';
                }

                if ($this->Session->read('captcha') != $rec['captcha']) {
                    $errors['captcha'] = '验证码错误，请重试';
                    throw new Exception();
                } else {
                    unset ($_SESSION['captcha']);
                }

                $user = $this->LdapSync->CreateUserFromLdap($rec['username']);
                if ($user == null) {
                    // 未找到用户
                    $errors['username'] = '找不到您的账户';
                    throw new Exception();
                }

                if ($rec['via'] == 'mail') {
                    if (empty($user['User']['mail']))
                        throw new Exception('您的帐号没有设置密保邮件');

                    // 用户的邮件地址不匹配
                    if ($rec['mail'] != $user['User']['mail']) {
                        $errors['mail'] = '此地址不是该账户的密保邮件地址';
                        throw new Exception();
                    }
                }

                if ($rec['via'] == 'mobile') {
                    if (empty($user['User']['mobile']))
                        throw new Exception('您的帐号没有设置密保手机');

                    // 用户的邮件地址不匹配
                    if ($rec['mail'] != $user['User']['mobile']) {
                        $errors['mail'] = '此号码不是该账户的密保手机号码';
                        throw new Exception();
                    }
                }

                // 生成随机验证码，若该验证码与已有的有效验证码重复，则重新生成
                do {
                    if ($rec['via'] == 'mobile') {
                        $code = mt_rand('100000', '999999');
                    } else {
                        $code = Password::generate(16, 7);
                    }
                    $duplicated = $this->PasswordRecovery->find('count',
                        array('conditions' =>
                            "PasswordRecovery.code='$code' AND
                             PasswordRecovery.valid_until>=CURTIME()"));
                } while ($duplicated);

                // 生成找回密码记录
                $this->PasswordRecovery->set('via', $rec['via']);
                $this->PasswordRecovery->set('code', $code);
                $this->PasswordRecovery->set('user_id', $user['User']['id']);
                $valid_until = new DateTime();
                if ($rec['via'] == 'mobile') {
                    $valid_until->add(new DateInterval('PT30M'));
                } else {
                    $valid_until->add(new DateInterval('P3D'));
                }
                $this->PasswordRecovery->set(
                    'valid_until', $valid_until->format('Y-m-d H:i:s'));
                if (!$this->PasswordRecovery->save()) {
                    // 保存数据时发生错误
                    $errors = $this->PasswordRecovery->invalidFields();
                    throw new Exception('保存数据时发生错误');
                }

                // 发送确认邮件
                if ($rec['via'] == 'mail') {
                    $url = Router::url("/passwords/recovery_confirm?code=$code", true);
                    $rst = $this->_send_recovery_mail($user, $url);
                    if ($rst !== true) {
                        throw new Exception('未能正常发送邮件：'.$rst);
                    }
                } else {
                    $rst = $this->_send_recovery_sms($user, $code);
                    if ($rst !== true) {
                        throw new Exception('未能正常发送短信：'.$rst);
                    }
                }

                $this->set('via', $rec['via']);
                return $this->render('confirm_mail_sent');
            } catch (Exception $e) {
                $m = $e->getMessage();
                if ($m) $this->Session->setFlash($m);
                $this->set('errors', $errors);
            }
        }
    }

    /**
     * 向用户的信箱发送修改密码确认信。
     */
    function _send_recovery_mail($user, $url) {
        $this->Mailer->toName = $user['User']['fullname'];
        $this->Mailer->to = $user['User']['mail'];
        $this->Mailer->subject = '校园网单点登录服务找回密码确认信';
        $this->set('fullname',  $user['User']['fullname']);
        $this->set('url', $url);
        $this->Mailer->template = 'password_recovery';
        return $this->Mailer->send();
    }

    function _send_recovery_sms($user, $code) {
        $message = "您申请找回校园网单点登录系统的密码，其验证码为 $code";
        return $this->ShortMessage->send($user['User']['mobile'], $message);
    }

    /**
     * 处理用户修改密码确认。用于确认的信息包括一个顺序号和相应的确认码。
     *
     * 如果成功确认用户身份，则自动生成一个新密码显示在屏幕上，同时向用户的邮
     * 箱发送一封邮件以告知用户密码已经修改。
     */
    function recovery_confirm() {
        $this->set('title_for_layout', '找回密码确认');

        try {
            if (!empty($_GET['code'])) {
                $code = Sanitize::paranoid($_GET['code']);
            } else {
                throw new Exception('请输入验证码');
            }

            $pr = $this->PasswordRecovery->find('first',
                array('conditions' => "PasswordRecovery.code='$code' AND
                                       PasswordRecovery.valid_until>=CURTIME()")
                );
            // 检查验证码是否正确
            if ($pr == null) {
                throw new Exception('验证码错误');
            }

            // 检查验证码是否使用过
            if ($pr['PasswordRecovery']['finished']) {
                throw new Exception('验证码已经使用过');
            }

            // 重新设置用户密码
            $user = $this->User->findById($pr['PasswordRecovery']['user_id']);
            $filter = $this->Person->primaryKey."=".$user['User']['username']; 
            $person = $this->Person->find('first', array( 'conditions'=>$filter)); 
            $newpwd = Password::generate(8, 7);
            $newinfo['userpassword']='{md5}'.base64_encode(pack('H*',md5($newpwd)));
            $dn = $person['Person']['dn'];
            $ldap = ConnectionManager::getDataSource('ldap');
            if (!@ldap_modify($ldap->database, $dn, $newinfo)) {
                $m = '连接LDAP重设密码失败 - '.@ldap_error($ldap->database);
                throw new Exception($m);
            }

            // 将验证码设定为使用过
            $this->PasswordRecovery->set($pr);
            $this->PasswordRecovery->set('finished', true);
            $this->PasswordRecovery->save();

            if ($pr['PasswordRecovery']['via'] == 'mail') {
                $rst = $this->_send_new_password_mail($person, $newpwd);
                if ($rst !== true) {
                    $this->Session->setFlash('通过电子邮件发送密码失败');
                }
            } else if ($pr['PasswordRecovery']['via'] == 'mobile') {
                $rst = $this->_send_new_password_sms($person, $newpwd);
                if ($rst !== true) {
                    $this->Session->setFlash('通过手机短信发送密码失败');
                }
            }

            $this->set('password', $newpwd);
            return $this->render('recovery_succeed');
        } catch (Exception $e) {
            $m = $e->getMessage();
            if ($m) $errors['code'] = $m;
            $this->set('errors', $errors);
        }
    }

    /**
     * 发送新密码到用户的邮箱。
     */
    function _send_new_password_mail($person, $password) {
        $this->Mailer->toName = $person['Person']['cn'];
        $this->Mailer->to = $person['Person']['mail'];
        $this->Mailer->subject = '校园网单点登录服务新密码';
        $this->set('fullname', $person['Person']['cn']);
        $this->set('password', $password);
        $this->Mailer->template = 'new_password';
        return $this->Mailer->send();
    }

    function _send_new_password_sms($person, $password) {
        $message = "您在校园网单点登录系统的密码已经被重置为 $password";
        return $this->ShortMessage->send($person['Person']['mobile'], $message);
    }

}
