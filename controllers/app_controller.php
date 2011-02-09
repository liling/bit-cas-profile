<?php

class AppController extends Controller {

    var $components = array('CasAuth', 'Session');
    var $helpers = array('Session', 'Html', 'Js' => array('Jquery'));

    function beforeRender() {
        $this->set('locale', Configure::read('Config.language'));
    }

}
