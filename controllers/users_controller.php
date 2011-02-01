<?php

class UsersController extends AppController {

    var $name = 'Users';    
    var $scaffold;
    var $uses = array('User', 'MailActivate');
 
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
     * 设定用户密码
     */
    function passwd() {
        
    }

}

?>
