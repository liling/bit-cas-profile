<?php

class MobileActivate extends AppModel {

    var $name = 'MobileActivate';

    var $validate = array(
        'mobile' => array(
            'rule' => '/^1[0-9]{10}$/',
            'required' => true,
            'message' => '请输入一个合法的手机号码'
        ),
        'unlock_question' => array(
            'rule' => array('minLength', '1'),
            'required' => true,
            'message' => '请您选择一个问题'
        ),
        'unlock_answer' => array(
            'rule' => array('minLength', '1'),
            'required' => true,
            'message' => '请输入问题的答案'
        ),
    );
}
