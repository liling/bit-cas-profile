<?php

class LdapSyncComponent extends Component { 

    /**
     * 如果该用户在本地数据库中不存在，则通过用户名，从 LDAP 服务器获取人员
     * 的基本信息，并插入到本地数据库中。
     */
    function CreateUserFromLdap($username) {
        $UserModel = &ClassRegistry::init('User');
        $user = $UserModel->findByUsername($username);
        if (!$user) {
            $Person = &ClassRegistry::init('Person');
            $filter = $Person->primaryKey."=".$username; 
            $person = $Person->find('first', array( 'conditions'=>$filter)); 

            if ($person) {
                $UserModel->create(array('username' => $username, 'password' => '*', 'fullname' => $person['Person']['cn']));
                $UserModel->save();
                $user = $UserModel->findByUsername($username);
            }
        }
        return $user;
    }

}
