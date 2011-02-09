<?php   
class Person extends AppModel { 

    var $name = 'Person'; 
     
    var $useDbConfig = 'ldap'; 

    // This would be the ldap equivalent to a primary key if your dn is  
    // in the format of uid=username, ou=people, dc=example, dc=com 
    var $primaryKey = 'employeeNumber';

    // The table would be the branch of your basedn that you defined in  
    // the database config 
    var $useTable = 'ou=people';  

    var $validate = array( 
        'cn' => array( 
            'alphaNumeric' => array(
                'rule' => array('custom', '/^[a-zA-Z]*$/'), 
                'required' => true, 
                'on' => 'create', 
                'message' => 'Only Letters and Numbers can be used for Display Name.' 
            ), 
            'between' => array( 
                'rule' => array('between', 5, 15), 
                'on' => 'create', 
                'message' => 'Between 5 to 15 characters' 
            ) 
        ), 
        'sn' => array( 
            'rule' => array('custom', '/^[a-zA-Z]*$/'), 
            'required' => true, 
            'on' => 'create', 
            'message' => 'Only Letters and Numbers can be used for Last Name.' 
        ), 
        'userpassword' => array( 
            'rule' => array('minLength', '8'), 
            'message' => 'Mimimum 8 characters long.' 
        ), 
        'mail' => array( 
            'rule' => 'email', 
            'required' => true, 
            'on' => 'create', 
            'message' => 'Must Contain a Valid Email Address.' 
        ), 
        'uid' => array( 
            'rule' => 'alphaNumeric', 
            'required' => true, 
            'on' => 'create', 
            'message' => 'Only Letters and Numbers can be used for Username.' 
        ), 
    ); 

} 
