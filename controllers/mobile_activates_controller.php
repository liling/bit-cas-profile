<?php

App::import('Sanitize');

class MobileActivatesController extends AppController {

    var $name = 'MobileActivates';

    var $components = array('CasAuth', 'Session', 'LdapSync', 'ShortMessage');
    var $uses = array('MobileActivate', 'User', 'SmsLog');

    function index() {
        return $this->flash('跳转中', '/mobile_activates/setmobile', 0);
    }

    /**
     * 设定手机号码，设定完成后，系统向用户发送短信。
     */
    function setmobile() {
        $this->set('title_for_layout', '设定密保手机');
        $errors = array();

        $user = $this->CasAuth->user();
        $user = $this->User->findById($user['User']['id']);
        // 如果已经加锁，则直接跳出
        if ($user['User']['mobile_locked']) {
            $this->flash('您的密保手机号处于锁定状态，请先解锁', '/users/view', 3);
        }

        if (!empty($this->data['MobileActivate'])) {
            try {
                $uid = $user['User']['id'];

                // 防止用户不断修改密保手机
                $tc = $this->MobileActivate->find('count',
                    array('conditions' =>
                        "MobileActivate.user_id=$uid AND
                         Date(MobileActivate.created)=CURDATE()"));
                if (Configure::read('debug') < 2 && $tc > 3) {
                    $this->data = array();
                    throw new Exception('您今天已经三次修改密保手机号了，明天再继续吧。');
                }
                
                // 保存
                $code = mt_rand('100000', '999999');
                $this->MobileActivate->set($this->data);
                $this->MobileActivate->set('user_id', $uid);
                $this->MobileActivate->set('code', $code);
                if (!$this->MobileActivate->save()) {
                    $errors = $this->MobileActivate->invalidFields();
                    throw new Exception();
                }

                // 发送手机短信
                $mobile = $this->data['MobileActivate']['mobile'];
                $rst = $this->_send_short_message($mobile, $code);
                if ($rst !== true) {
                    throw new Exception('未能成功发送手机短信：'.$rst);
                }

                // 显示重发号码和输入确认码界面
                $id = $this->MobileActivate->getInsertID();
                $this->data = $this->MobileActivate->findById($id);
                $this->redirect('/mobile_activates/setmobile');
            } catch (Exception $e) {
                $m = $e->getMessage();
                if (!empty($m)) $this->Session->setFlash($m);
                $this->set('errors', $errors);
            }
        } else {
            // 显示设定手机号界面
            $uid = $user['User']['id'];
            $this->data = $this->MobileActivate->find('first',
                array('conditions' => "MobileActivate.user_id=$uid AND 
                                       MobileActivate.finished=0",
                      'order' => 'MobileActivate.created DESC'));
            $this->data['MobileActivate']['mobile'] = $user['User']['mobile'];
            $this->data['MobileActivate']['unlock_question'] = $user['User']['unlock_question'];
            $this->data['MobileActivate']['unlock_answer'] = $user['User']['unlock_answer'];
        }

        // 为视图准备数据
        if (!empty($this->data['MobileActivate']['check_times']) && $this->data['MobileActivate']['check_times'] >= 5) {
            $errors['checkcode'] = '多次输入确认码均错误，请重新设定密保手机号码。';
            $this->set('no_more_check', true);
            $this->set('no_more_send', true);
        }
        if (!empty($this->data['MobileActivate']['send_times']) && $this->data['MobileActivate']['send_times'] >= 5) {
            $errors['mobile'] = '已经多次向该号码发送短信，请检查该号码是否正常。';
            $this->set('no_more_send', true);
        }
        $this->set('unlock_questions', Configure::read('Profile.unlockQuestions'));
        $this->set('errors', $errors);
    }

    /**
     * 用户收到短信后，输入确认码。
     */
    function confirm() {
        $this->set('title_for_layout', '输入确认码');

        try {
            // 检查确认码是否为空
            if (empty($this->data['MobileActivate']['checkcode'])) {
                throw new Exception('请输入确认码');
            }

            // 查找用户最后的密保手机修改记录
            $user = $this->CasAuth->user(); 
            $uid = $user['User']['id'];
            $ma = $this->MobileActivate->find('first',
                    array('conditions' => "MobileActivate.user_id=$uid AND 
                                           MobileActivate.finished=0",
                          'order' => 'MobileActivate.created DESC'));
            if (empty($ma)) {
                throw new Exception('请先填写密保手机号码');
            }

            // 如果用户输入输入确认码错误超过五次，则不允许输入
            if ($ma['MobileActivate']['check_times'] >= 5) {
                throw new Exception();
            }

            // 检查确认码是否正确，如果错误则增加错误次数记录
            if ($ma['MobileActivate']['code'] != $this->data['MobileActivate']['checkcode']) {
                $d = array(
                    'id' => $ma['MobileActivate']['id'],
                    'check_times' => $ma['MobileActivate']['check_times']+1);
                $this->MobileActivate->save(array('MobileActivate'=>$d), false);
                throw new Exception('确认码错误');
            }

            // 检查确认码是否过期
            $now = new DateTime('now');
            $created = date_create($ma['MobileActivate']['created']);
            $created->add(new DateInterval('P1D'));
            if ($now > $created) {
                throw new Exception('确认码已过期');
            }

            // 保存验证信息
            $d = array('MobileActivate' => array('id' => $ma['MobileActivate']['id'], 'finished' => true));
            if (!$this->MobileActivate->save($d, false)) {
                throw new Exception('未能正常保存验证信息');
            }

            // 保存手机号到用户信息
            $d = array('User' => array(
                'id' => $user['User']['id'],
                'mobile' => $ma['MobileActivate']['mobile'],
                'mobile_locked' => true,
                'unlock_question' => $ma['MobileActivate']['unlock_question'],
                'unlock_answer' => $ma['MobileActivate']['unlock_answer']));
            if (!$this->User->save($d, false)) {
                throw new Exception('未能正常保存用户信息');
            }

            // 保存手机号到 LDAP
            $rst = $this->LdapSync->updateUser($user['User']['username'], 'mobile', $ma['MobileActivate']['mobile']);
            if ($rst !== true) {
                throw new Exception('将数据保存到LDAP时失败: '.$rst);
            }

            return $this->render('succeeded');
        } catch (Exception $e) {
            $m = $e->getMessage();
            if ($m) $this->Session->setFlash($m);
        }

        $this->redirect($this->referer());
    }

    /**
     * 重新向用户发送手机短信。
     */
    function resend($id = null) {
        try {
            $id = Sanitize::paranoid($id);
            $ma = $this->MobileActivate->read(null, $id);
            if (!$ma) {
                throw new Exception('找不到密保手机验证记录');
            }

            $user = $this->CasAuth->user();
            if ($user['User']['id'] != $ma['MobileActivate']['user_id']) {
                throw new Exception('该密保手机验证记录与用户不匹配');
            }

            // 如果向该手机发送短信超过五次，则不再发送
            if ($ma['MobileActivate']['send_times'] >= 5) {
                throw new Exception('已经多次向该号码发送短信，请检查该号码是否正常。');
            }

            // 检查距离上一次发送短信有多长时间，未超过五分钟则提示错误
            $now = new DateTime('now');
            $modified = date_create($ma['MobileActivate']['modified']);
            $modified->add(new DateInterval('PT5M'));
            if (Configure::read('debug') < 2 && $now <= $modified) {
                throw new Exception('短信发送需要一定时间，距离上一次发送短信还不足五分钟。');
            }

            // 发送手机短信
            $mobile = $ma['MobileActivate']['mobile'];
            $code = $ma['MobileActivate']['code'];
            $rst = $this->_send_short_message($mobile, $code);
            if ($rst !== true) {
                throw new Exception('未能成功发送手机短信：'.$rst);
            }

            // 保存数据
            $d = array('MobileActivate' => array(
                'id' => $ma['MobileActivate']['id'],
                'send_times' => $ma['MobileActivate']['send_times']+1));
            if (!$this->MobileActivate->save($d, false)) {
                throw new Exception('保存数据失败');
            }
        } catch (Exception $e) {
            $m = $e->getMessage();
            if (!empty($m)) $this->Session->setFlash($m);
        }

        $this->redirect($this->referer());
    }

    /**
     * 允许用户重新设定密保手机号码。
     */
    function clear($id = null) {
        try {
            $id = Sanitize::paranoid($id);
            $ma = $this->MobileActivate->read(null, $id);
            if (!$ma) {
                throw new Exception('找不到密保手机验证记录');
            }

            $user = $this->CasAuth->user();
            if ($user['User']['id'] != $ma['MobileActivate']['user_id']) {
                throw new Exception('该密保手机验证记录与用户不匹配');
            }

            // 将记录标志为已放弃
            $d = array('MobileActivate' => array(
                'id' => $ma['MobileActivate']['id'],
                'finished' => true, 'aborted' => true));
            if (!$this->MobileActivate->save($d, false)) {
                throw new Exception('保存数据失败');
            }
        } catch (Exception $e) {
            $m = $e->getMessage();
            if (!empty($m)) $this->Session->setFlash($m);
        }

        $this->redirect($this->referer());
    }

    /**
     * 向用户发送手机短信。
     *
     * @param $mobile 手机号
     * @param $code 确认码
     */
    function _send_short_message($mobile, $code) {
        $message = sprintf('您在校园网单点登录服务的密保手机确认码为: %s', $code);
        return $this->ShortMessage->send($mobile, $message);
    }

}
