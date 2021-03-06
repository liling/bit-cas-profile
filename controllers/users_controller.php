<?php

class UsersController extends AppController {

    var $name = 'Users';    
    var $scaffold;
    var $uses = array('User', 'Person', 'AuditTrail');
    var $components = array('CasAuth', 'Session', 'RequestHandler', 'Captcha');

    function beforeFilter() {
        $this->CasAuth->allow('captcha');
    }

    /**
     * 主要功能已经由 Auth 组件提供
     */
    function login() {
        $this->redirect('/');
    }

    /**
     * 退出系统，同时退出 CAS
     */
    function logout() {
        $this->CasAuth->logout();
    }

    /**
     * 显示 Captcah 图片。
     */
    function captcha() {
        $this->Captcha->render();
        exit();
    }

    /**
     * 显示用户个人资料
     */
    function view() {
        $this->set('title_for_layout', '个人信息');

        $user = $this->CasAuth->user();
        $filter = $this->Person->primaryKey.'='.$user['User']['username'];
        $person = $this->Person->find('first', array('conditions'=>$filter));
        $this->set('person', $person['Person']);

        $trails = $this->AuditTrail->find('all', array(
            'conditions' => array(
                'AuditTrail.AUD_USER' =>
                    array("[username: {$user['User']['username']}]",
                          "[username: {$user['User']['mail']}]"),
                'AuditTrail.AUD_ACTION' =>
                    array('AUTHENTICATION_SUCCESS', 'AUTHENTICATION_FAILED')),
            'order' => 'AuditTrail.AUD_DATE DESC',
            'limit' => 10,
        ));
        $this->set('trails', $trails);
    }

    /**
     * 显示用户的照片
     */
    function photo() {
        $this->RequestHandler->respondAs('image/jpeg');

        $user = $this->CasAuth->user();
        $filter = $this->Person->primaryKey.'='.$user['User']['username'];
        $person = $this->Person->find('first', array('conditions'=>$filter));
        if (!empty($person['Person']['jpegphoto'])) {
            echo $person['Person']['jpegphoto'];
        } else {
            $fp = fopen('img/no-img.jpg', 'rb');
            fpassthru($fp);
            fclose($fp);
        }
        exit();
    }

    /**
     * 对密保手机号码加锁
     */
    function lock_mobile() {
        $user = $this->CasAuth->user();
        $user = $this->User->findById($user['User']['id']);
        if (empty($user['User']['mobile']) ||
            empty($user['User']['unlock_question']) ||
            empty($user['User']['unlock_answer']))
        {
            $this->redirect('/mobile_activates/setmobile');
        }

        $d = array('id' => $user['User']['id'], 'mobile_locked' => true);
        $this->User->save(array('User' => $d), false);
        $this->redirect('/users/view');
    }

    /**
     * 解锁用户的密保手机号码
     */
    function unlock_mobile() {
        $this->set('title_for_layout', '密保手机号解锁');

        if (!empty($this->data)) {
            $errors = array();
            try {
                if (empty($this->data['User']['captcha'])) {
                    throw new Exception();
                }

                if (empty($this->data['User']['unlock_answer'])) {
                    throw new Exception();
                }

                if ($this->Session->read('captcha') != $this->data['User']['captcha']) {
                    $errors['captcha'] = '验证码错误';
                    throw new Exception();
                }

                $user = $this->CasAuth->user();
                $user = $this->User->findById($user['User']['id']);
                if ($this->data['User']['unlock_answer'] != $user['User']['unlock_answer']) {
                    $errors['unlock_answer'] = '您输入的答案与预留答案不符';
                    throw new Exception();
                }

                $d = array('User' => array('id' => $user['User']['id'],
                                           'mobile_locked' => false));
                if (!$this->User->save($d, false)) {
                    throw new Exception('未能正常保存数据');
                }
                $this->flash('已解锁，可以修改密保手机号了', '/mobile_activates/setmobile', 1);
            } catch (Exception $e) {
                $m = $e->getMessage();
                if ($m) $this->Session->setFlash($m);
                $this->set('errors', $errors);
            }
        }

    }

}
