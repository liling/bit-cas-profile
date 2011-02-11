<?php

App::import('Vendor', 'phpmailer', array('file' => 'kcaptcha'.DS.'kcaptcha.php')); 

class CaptchaComponent extends Object {

    function startup(&$controller) {
        $this->controller = $controller;
    }

    function render() {
        $kaptcha = new KCAPTCHA();
        $this->controller->Session->write('captcha', $kaptcha->getKeyString());
    }

}
