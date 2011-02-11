<?php

class AppController extends Controller {

    var $components = array('CasAuth', 'Session');
    var $helpers = array('Session', 'Html', 'Js' => array('Jquery'));
    var $uses = array('User');

    function beforeRender() {
        $this->set('locale', Configure::read('Config.language'));
        $user = $this->CasAuth->user();
        $this->set('user', $this->User->findById($user['User']['id']));
    }

}
