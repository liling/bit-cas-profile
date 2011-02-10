<?php

App::import('Sanitize');

class MobileActivatesController extends AppController {

    var $name = 'MobileActivates';

    var $components = array('CasAuth', 'Session', 'LdapSync');
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
        if (!empty($this->data['MobileActivate'])) {
            try {
                $uid = $user['User']['id'];
                $tc = $this->MobileActivate->find('count',
                    array('conditions' =>
                        "MobileActivate.user_id=$uid AND
                         Date(MobileActivate.created)=CURDATE()"));
                if (Configure::read('debug') < 2 && $tc > 3) {
                    $this->data = array();
                    throw new Exception('您今天已经三次修改密保手机号了，明天再继续吧。');
                }
                
                $code = mt_rand('100000', '999999');
                $this->MobileActivate->set($this->data);
                $this->MobileActivate->set('user_id', $uid);
                $this->MobileActivate->set('code', $code);
                if (!$this->MobileActivate->save()) {
                    $errors = $this->MobileActivate->invalidFields();
                    throw new Exception();
                }

                $mobile = $this->data['MobileActivate']['mobile'];
                $rst = $this->_send_short_message($mobile, $code);
                if ($rst !== true) {
                    throw new Exception('未能成功发送手机短信：'.$rst);
                }

                $id = $this->MobileActivate->getInsertID();
                $this->data = $this->MobileActivate->findById($id);
                return $this->flash('已向您的手机发送验证码，请查收', '/mobile_activates/setmobile', 3);
            } catch (Exception $e) {
                $m = $e->getMessage();
                if (!empty($m)) $this->Session->setFlash($m);
                $this->set('errors', $errors);
            }
        } else {
            $uid = $user['User']['id'];
            $this->data = $this->MobileActivate->find('first',
                array('conditions' => "MobileActivate.user_id=$uid AND 
                                       MobileActivate.finished=0",
                      'order' => 'MobileActivate.created DESC'));
        }

        if (!empty($this->data['MobileActivate']['check_times']) && $this->data['MobileActivate']['check_times'] >= 5) {
            $errors['checkcode'] = '多次输入验证码均错误，请重新设定密保手机号码。';
            $this->set('no_more_check', true);
            $this->set('no_more_send', true);
        }
        if (!empty($this->data['MobileActivate']['send_times']) && $this->data['MobileActivate']['send_times'] >= 5) {
            $errors['mobile'] = '已经多次向该号码发送短信，请检查该号码是否正常。';
            $this->set('no_more_send', true);
        }
        $this->set('errors', $errors);
    }

    /**
     * 用户收到短信后，输入验证码。
     */
    function confirm() {
        $this->set('title_for_layout', '输入验证码');

        try {
            if (empty($this->data['MobileActivate']['checkcode'])) {
                throw new Exception('请输入验证码');
            }

            $user = $this->CasAuth->user(); 
            $uid = $user['User']['id'];
            $ma = $this->MobileActivate->find('first',
                    array('conditions' => "MobileActivate.user_id=$uid AND 
                                           MobileActivate.finished=0",
                          'order' => 'MobileActivate.created DESC'));
            if (empty($ma)) {
                throw new Exception('请先填写密保手机号码');
            }

            if ($ma['MobileActivate']['check_times'] >= 5) {
                throw new Exception();
            }

            if ($ma['MobileActivate']['code'] != $this->data['MobileActivate']['checkcode']) {
                $ma['MobileActivate']['check_times']++;
                unset($ma['MobileActivate']['modified']);
                $this->MobileActivate->set($ma);
                $this->MobileActivate->save();
                throw new Exception('验证码错误');
            }

            // 检查验证码是否过期
            $now = new DateTime('now');
            $created = date_create($ma['MobileActivate']['created']);
            $created->add(new DateInterval('P1D'));
            if ($now > $created) {
                throw new Exception('验证码已过期');
            }

            // 保存验证信息
            $this->MobileActivate->set($ma);
            $this->MobileActivate->set('finished', true);
            $this->MobileActivate->set('modified', null);
            if (!$this->MobileActivate->save()) {
                throw new Exception('未能正常保存验证信息');
            }

            // 保存手机号到用户信息
            $this->User->read($user['User']['id']);
            $this->User->set('mobile', $ma['MobileActivate']['mobile']);
            if (!$this->User->save()) {
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

            $mobile = $ma['MobileActivate']['mobile'];
            $code = $ma['MobileActivate']['code'];
            $rst = $this->_send_short_message($mobile, $code);
            if ($rst !== true) {
                throw new Exception('未能成功发送手机短信：'.$rst);
            }
            $this->MobileActivate->set(
                'send_times', $ma['MobileActivate']['send_times'] + 1);
            $this->MobileActivate->set('modified', null);
            if (!$this->MobileActivate->save()) {
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

            $this->MobileActivate->set('finished', true);
            $this->MobileActivate->set('aborted', true);
            $this->MobileActivate->set('modified', null);
            if (!$this->MobileActivate->save()) {
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
     * @param $code 验证码
     */
    function _send_short_message($mobile, $code) {
        $message = sprintf('您在校园网单点登录服务的密保手机验证码为: %s', $code);

        $username = Configure::read('SMS.username');
        $password = Configure::read('SMS.password');
        $send_url = "http://www.xunsai.net:8000/?user=$username&password=$password&phonenumber=%s&text=%s&charset=utf-8";

        $url = sprintf($send_url, urlencode($mobile), urlencode($message));
        $output = file_get_contents($url);
        if ($output) {
            $output = iconv('GB2312', 'UTF-8', $output);

            $sms = array('phone' => $mobile, 'message' => $message,
                         'url' => $url, 'output' => $output);
            $this->SmsLog->set(array('SmsLog' => $sms));
            $this->SmsLog->save();

            ereg("<TITLE>(.*)</TITLE>", $output, $regs);
            if (!empty($regs)) {
                $result = $regs[1];
            } else {
                $result = '未知错误';
            }
        } else {
            $result = '网络故障，无法连接短信服务器';
        }

        if ($result == '您所发送的信息已经成功提交') {
            $result = true;
        }

        return $result;
    }

}
