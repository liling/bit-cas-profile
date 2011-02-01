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

    function recovery() {

    }

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
