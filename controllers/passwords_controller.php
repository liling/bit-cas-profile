<?php

App::import('Sanitize');
App::import('Vendor', 'Password');

class PasswordsController extends AppController {

    var $components = array('CasAuth', 'Session', 'Email');
    var $uses = array('PasswordRecovery', 'User', 'Person');

    function beforeFilter() {
        $this->CasAuth->allow('recovery', 'confirm');
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
        $ldap = ConnectionManager::getDataSource('ldap');

        if (!empty($this->data['Password'])) {
            $user = $this->CasAuth->user();
            $pwd = $this->data['Password'];
            if ($pwd['new'] != $pwd['new2']) {
                $this->set('errors',
                           array('new2' => '您两次输入的密码不一致')); 
                return $this->render();
            }

            $filter = $this->Person->primaryKey.'='.$user['User']['username'];
            $person = $this->Person->find('first', array('conditions'=>$filter));
            $r = $ldap->auth($person['Person']['dn'], $pwd['old']);
            if ($r === true) {
                $newinfo['userpassword']='{md5}'.base64_encode(pack('H*',md5($pwd['new'])));
                if (@ldap_modify($ldap->database, $person['Person']['dn'], $newinfo)) {
                    $this->Session->setFlash('密码修改成功');
                    return $this->render();
                } else {
                    $this->Session->setFlash('无法连接LDAP数据库修改密码：'.@ldap_error($ldap->database));
                    return $this->render();
                }
            } else {
                $this->set('errors', array('old' => '旧密码错误')); 
                return $this->render();
            }
        }
    }

    /**
     * 让用户输入自己的用户名（工号/学号）以及电子邮件地址，判断用户的输入是否
     * 正确。如果用户输入的信息匹配，则向用户的电子信箱发送修改密码确认信。
     */
    function recovery() {

    }

    /**
     * 向用户的信箱发送修改密码确认信。
     */
    function _send_recovery_mail($address) {
        $this->Email->from = '系统自动生成邮件.请勿回复 <no-reply@bit.edu.cn>';
        $this->Email->to = $address;
        $this->Email->bcc = 'liling@bit.edu.cn';
        $this->Email->subject = '校园网单点登录服务找回密码确认信';
        $this->Email->template = 'password_recovery';
        $this->Email->sendAs = 'both';

        $this->Email->delivery = Configure::read('Email.delivery');
        if ($this->Email->delivery == 'smtp')
            $this->Email->smtpOptions = Configure::read('SMTP.options');
        $this->Email->send();
    }

    /**
     * 处理用户修改密码确认。用于确认的信息包括一个顺序号和相应的确认码。
     *
     * 如果成功确认用户身份，则自动生成一个新密码显示在屏幕上，同时向用户的邮
     * 箱发送一封邮件以告知用户密码已经修改。
     */
    function confirm($id, $code) {
        $this->set('title_for_layout', '确认密码修改');

        $id = Sanitize::paranoid($id);
        $code = Sanitize::paranoid($code);

        $ma = $this->PasswordRecovery->findById($id);
        if ($ma != null && $ma['PasswordRecovery']['code'] == $code) {
            $this->User->read('id', $ma['PasswordRecovery']['user_id']);
            $this->User->set('mail', $ma['PasswordRecovery']['mail']);
            $this->User->save();
            $this->PasswordRecovery->delete($id, false);

            return $this->render('succeeded');
        } else {
            return $this->render('failed');
        }
    }

    /**
     * 发送新密码到用户的邮箱。
     */
    function _send_new_password_mail($address) {
        $this->Email->from = '系统自动生成邮件.请勿回复 <no-reply@bit.edu.cn>';
        $this->Email->to = $address;
        $this->Email->bcc = 'liling@bit.edu.cn';
        $this->Email->subject = '校园网单点登录服务新密码';
        $this->Email->template = 'new_password';
        $this->Email->sendAs = 'both';

        $this->Email->delivery = Configure::read('Email.delivery');
        if ($this->Email->delivery == 'smtp')
            $this->Email->smtpOptions = Configure::read('SMTP.options');
        $this->Email->send();
    }

}
