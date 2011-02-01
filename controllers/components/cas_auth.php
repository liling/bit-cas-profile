<?php  

App::import('Vendor', 'cas', array('file' => 'CAS-1.2.0'.DS.'CAS.php')); 
App::import('Component', 'Auth'); 
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
 * @lastmodified   Date: October 12, 2010 
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

        // Copied from auth.php
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

        // CAS authentication required if user is not logged in  
        if (!$this->user()) { 
            // Set debug mode 
            phpCAS::setDebug(false); 
            //Initialize phpCAS 
            phpCAS::client(CAS_VERSION_2_0, Configure::read('CAS.hostname'), Configure::read('CAS.port'), Configure::read('CAS.uri'), false);
            // No SSL validation for the CAS server 
            phpCAS::setNoCasServerValidation(); 
            // Force CAS authentication if required 
            phpCAS::forceAuthentication(); 

            $this->fetchPersonFromLdap(phpCAS::getUser());

            $model =& $this->getModel(); 
            $controller->data[$model->alias][$this->fields['username']] = phpCAS::getUser(); 
        } 
        return parent::startup($controller); 
    } 

    function fetchPersonFromLdap($username) {
        $UserModel = $this->getModel('User');
        $user = $UserModel->findByUsername($username);
        if (!$user) {
            $Person = $this->getModel('Person');
            $filter = $Person->primaryKey."=".$username; 
            $person = $Person->find('first', array( 'conditions'=>$filter)); 

            $UserModel->create(array('username' => $username, 'password' => '*', 'fullname' => $person['Person']['cn']));
            $UserModel->save();
        }
    }

    function hashPasswords($data) {
        $model =& $this->getModel();
        $data[$model->alias][$this->fields['password']] = '*';
        return $data;
    }

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
            phpCAS::logout(array('url' => 'http://www.cakephp.org')); // Provide login url for your application 
        } 
        return parent::logout(); 
    } 
     
} 

?>
