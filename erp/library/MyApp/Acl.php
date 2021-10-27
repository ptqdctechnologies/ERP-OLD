<?php

class MyApp_Acl extends Zend_Acl
{

    public function __construct()
    {
        // Add Resources

        // Resource #1: Default Module
        $this->add(new Zend_Acl_Resource('default'));
        // Resource #2: Admin Module
        $this->add(new Zend_Acl_Resource('admin'));
        // Resource #3: Procurement Module
        $this->add(new Zend_Acl_Resource('procurement'));
        // Resource #4: Project Management Module
        $this->add(new Zend_Acl_Resource('projectmanagement'));
        // Resource #5: Finance Module
        $this->add(new Zend_Acl_Resource('finance'));
        // Resource #6: Human Resource Module
        $this->add(new Zend_Acl_Resource('humanresource'));

        // Add Roles

        // Role #1: Public
        $this->addRole(new Zend_Acl_Role('public'));
        // Role #2: User (inherits from Public)
        $this->addRole(new Zend_Acl_Role('user'), 'public');
        // Role #2: Admin (inherits from User)
        $this->addRole(new Zend_Acl_Role('admin'), 'user');

        // Assign Access Rules

        $this->allow('public', 'default');
        $this->allow('user', 'procurement');
        $this->allow('user', 'projectmanagement');
        $this->allow('user', 'finance');
        $this->allow('user', 'humanresource');
        $this->allow('admin', 'admin');
    }
}

?>