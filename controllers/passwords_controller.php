<?php

App::import('Sanitize');
App::import('Vendor', 'Password');

class PasswordsController extends AppController {

    var $components = array('CasAuth', 'Session', 'Mailer', 'LdapSync');
    var $uses = array('PasswordRecovery', 'User', 'Person');

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

                $user = $this->LdapSync->CreateUserFromLdap($rec['username']);
                if ($user == null) {
                    // 未找到用户
                    $errors['username'] = '找不到您的账户';
                    throw new Exception();
                }

                if (!$user['User']['mail']) {
                    throw new Exception('您的帐号没有设置密保邮件');
                }

                if ($rec['mail'] != $user['User']['mail']) {
                    // 用户的邮件地址不匹配
                    $errors['mail'] = '此地址不是该账户的密保邮件地址';
                    throw new Exception();
                }

                // 生成找回密码记录
                $code = Password::generate(8, 7);
                $this->PasswordRecovery->set('code', $code);
                $this->PasswordRecovery->set('user_id', $user['User']['id']);
                if (!$this->PasswordRecovery->save()) {
                    // 保存数据时发生错误
                    $errors = $this->PasswordRecovery->invalidFields();
                    throw new Exception('保存数据时发生错误');
                }

                // 发送确认邮件
                $id = $this->PasswordRecovery->getInsertID();
                $url = Router::url("/passwords/recovery_confirm/$id/$code", true);
                $rst = $this->_send_recovery_mail($user, $url);
                if ($rst !== true) {
                    throw new Exception('未能正常发送邮件：'.$rst);
                }
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

    /**
     * 处理用户修改密码确认。用于确认的信息包括一个顺序号和相应的确认码。
     *
     * 如果成功确认用户身份，则自动生成一个新密码显示在屏幕上，同时向用户的邮
     * 箱发送一封邮件以告知用户密码已经修改。
     */
    function recovery_confirm($id, $code) {
        $this->set('title_for_layout', '找回密码确认');

        $id = Sanitize::paranoid($id);
        $code = Sanitize::paranoid($code);

        try {
            $pr = $this->PasswordRecovery->findById($id);
            // 检查验证码是否正确
            if ($pr == null || $pr['PasswordRecovery']['code'] != $code) {
                throw new Exception('验证码错误');
            }

            // 检查验证码是否使用过
            if ($pr['PasswordRecovery']['finished']) {
                throw new Exception('验证码已经使用过');
            }

            // 检查验证码是否过期
            $now = new DateTime('now');
            $created = date_create($pr['PasswordRecovery']['created']);
            $created->add(new DateInterval('P3D'));
            if ($now > $created) {
                throw new Exception('验证码已经过期，请重新申请验证码');
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

            $rst = $this->_send_new_password_mail($person, $newpwd);
            if ($rst !== true) {
                $this->Session->setFlash('通过电子邮件发送密码失败');
            }

            $this->set('password', $newpwd);
            return $this->render('recovery_succeed');
        } catch (Exception $e) {
            $this->set('reason', $e->getMessage());
            return $this->render('recovery_failed');
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

}
