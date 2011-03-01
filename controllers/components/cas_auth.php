<?php  

App::import('Vendor', 'cas', array('file' => 'CAS-1.2.0'.DS.'CAS.php')); 
App::import('Component', 'Auth'); 
App::import('Component', 'LdapSync'); 
App::import('Model', 'Person');

/** 
 * CasAuthComponent by Pietro Brignola. 
 * 
 * Extend CakePHP AuthComponent providing authentication against CAS service. 
 * 
 * PHP versions 4 and 5 
 * 
 * Comments and bug reports welcome at pietro.brignola AT unipa DOT it 
 * 
 * Licensed under The MIT License 
 * 
 * @writtenby      Pietro Brignola 
 * @writtenby      Li Ling
 * @lastmodified   Date: Feb 2, 2011 
 * @license        http://www.opensource.org/licenses/mit-license.php The MIT License 
 */  
class CasAuthComponent extends AuthComponent { 
     
    /** 
     * Main execution method.  Initializes CAS client and force authentication if required before passing user to parent startup method.
     * 
     * @param object $controller A reference to the instantiating controller object 
     * @return boolean 
     * @access public 
     */ 
    function startup(&$controller) { 

        // 从 CakePHP 的 auth.php 中复制出的代码，用于处理各种无须认证的情况
		$isErrorOrTests = (
			strtolower($controller->name) == 'cakeerror' ||
			(strtolower($controller->name) == 'tests' && Configure::read() > 0)
		);
		if ($isErrorOrTests) {
			return true;
		}

		$methods = array_flip($controller->methods);
		$action = strtolower($controller->params['action']);
		$isMissingAction = (
			$controller->scaffold === false &&
			!isset($methods[$action])
		);

		if ($isMissingAction) {
			return true;
		}

		if (!$this->__setDefaults()) {
			return false;
		}

		$url = '';

		if (isset($controller->params['url']['url'])) {
			$url = $controller->params['url']['url'];
		}
		$url = Router::normalize($url);
		$loginAction = Router::normalize($this->loginAction);

		$allowedActions = array_map('strtolower', $this->allowedActions);
		$isAllowed = (
			$this->allowedActions == array('*') ||
			in_array($action, $allowedActions)
		);

		if ($loginAction != $url && $isAllowed) {
			return true;
		}

        // 如果用户尚未登录，则通过 CAS 做用户身份认证
        if (!$this->user()) { 
            $this->log('开始CAS认证', 'debug');
            // Set debug mode 
            phpCAS::setDebug(false); 
            // 初始化 phpCAS 
            phpCAS::client(CAS_VERSION_2_0, Configure::read('CAS.hostname'), Configure::read('CAS.port'), Configure::read('CAS.uri'), false);
            // 不验证 CAS 服务器的证书
            phpCAS::setNoCasServerValidation(); 
            // 强制进行 CAS 认证
            phpCAS::forceAuthentication(); 

            // 认证成功的情况下，从 LDAP 服务器获取相关人员的基本信息
            $username = phpCAS::getUser();
            $this->log("CAS 返回的用户为 $username", 'debug');
            LdapSyncComponent::createUserFromLdap($username);

            $model =& $this->getModel(); 
            $controller->data[$model->alias][$this->fields['username']] = phpCAS::getUser(); 
        } 

        return parent::startup($controller); 
    } 

    /**
     * 由于在本地数据库中并不缓存用户密码，因此把所有用户密码的密文都设置为'*'
     */
    function hashPasswords($data) {
        $model =& $this->getModel();
        $data[$model->alias][$this->fields['password']] = '*';
        return $data;
    }

    /*function login($data = null) {
        $rst = parent::login($data);
        if ($rst) {
            $user = $this->user();
            LdapSyncComponent::syncUserFromLdap($user['User']['username']);
        }
        return $rst;
    }*/

    /** 
     * Logout execution method.  Initializes CAS client and force logout if required before returning to parent logout method.
     * 
     * @param mixed $url Optional URL to redirect the user to after logout 
     * @return string AuthComponent::$loginAction 
     * @see AuthComponent::$loginAction 
     * @access public 
     */ 
    function logout() { 
        // Set debug mode 
        phpCAS::setDebug(false); 
        //Initialize phpCAS 
        phpCAS::client(CAS_VERSION_2_0, Configure::read('CAS.hostname'), Configure::read('CAS.port'), Configure::read('CAS.uri'), false);
        // No SSL validation for the CAS server
        phpCAS::setNoCasServerValidation(); 
        // Force CAS logout if required 
        if (phpCAS::isAuthenticated()) { 
            phpCAS::logout(array('service' => 'https://login.bit.edu.cn/profile/')); // Provide login url for your application 
        } 
        return parent::logout(); 
    } 
     
} 
