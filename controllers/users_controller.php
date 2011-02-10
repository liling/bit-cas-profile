<?php

class UsersController extends AppController {

    var $name = 'Users';    
    var $scaffold;
    var $uses = array('User', 'Person');
    var $components = array('CasAuth', 'Session', 'RequestHandler');

    /**
     * 主要功能已经由 Auth 组件提供
     */
    function login() {}

    /**
     * 退出系统，同时退出 CAS
     */
    function logout() {
        $this->CasAuth->logout();
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
    }

    function photo() {
        $this->autoRender = false;
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
    }

}
