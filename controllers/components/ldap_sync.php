<?php

class LdapSyncComponent extends Object { 

    /**
     * 如果该用户在本地数据库中不存在，则通过用户名，从 LDAP 服务器获取人员
     * 的基本信息，并插入到本地数据库中。
     */
    function createUserFromLdap($username) {
        $UserModel = &ClassRegistry::init('User');
        $user = $UserModel->findByUsername($username);
        if (empty($user)) {
            $this->log("找不到用户 {$username}，从 LDAP 服务器创建", 'debug');

            $Person = &ClassRegistry::init('Person');
            $filter = $Person->primaryKey."=".$username; 
            $person = $Person->find('first', array( 'conditions'=>$filter)); 

            if ($person) {
                $UserModel->create(array(
					'username' => $username,
					'password' => '*',
					'fullname' => $person['Person']['cn'],
					'mobile_locked' => 0
				));
				if (!empty($person['Person']['mobile'])) {
					$UserModel->set('mobile', $person['Person']['mobile']);
				}
				if (!empty($person['Person']['mail'])) {
					$UserModel->set('mail', $person['Person']['mail']);
				}
                $UserModel->save();
                $user = $UserModel->findByUsername($username);
            } else {
                $this->log("用户 $username 在 LDAP 中不存在", 'debug');
            }
        } else {
            $this->log("用户 $username 在本地数据库已经存在", 'debug');
        }
        return $user;
    }

    /**
     * 用 LDAP 中的信息更新本地数据库中的信息。
     */
    function syncUserFromLdap($username) {
        $UserModel = &ClassRegistry::init('User');
        $user = $UserModel->findByUsername($username);
        if (!empty($user)) {
            $Person = &ClassRegistry::init('Person');
            $filter = $Person->primaryKey."=".$username; 
            $person = $Person->find('first', array( 'conditions'=>$filter)); 
            if (!empty($person)) {
                $UserModel->set($user);
                if (!(empty($person['Person']['mail']))) {
                    $UserModel->set('mail', $person['Person']['mail']);
                }
                if (!(empty($person['Person']['mobile']))) {
                    $UserModel->set('mobile', $person['Person']['mobile']);
                }
                $UserModel->save();
            }
        }
    }

    function updateUser($username, $key, $value) {
        $Person = &ClassRegistry::init('Person');
        $filter = $Person->primaryKey."=".$username; 
        $person = $Person->find('first', array( 'conditions'=>$filter)); 
        $dn = $person['Person']['dn'];

        $newinfo[$key] = $value;
        $ldap = ConnectionManager::getDataSource('ldap');
        if (!@ldap_modify($ldap->database, $dn, $newinfo)) {
            return @ldap_error($ldap->database);
        }

        return true;
    }
}
