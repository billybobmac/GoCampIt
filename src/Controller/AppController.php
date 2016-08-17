<?php
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

class AppController extends Controller
{
    //...

    public function initialize()
    {

        $this->loadComponent('Flash');
        $this->loadComponent('Auth', [
            'loginRedirect' => [
                'controller' => 'Campgrounds',
                'action' => 'index'
            ],
            'logoutRedirect' => [
                'controller' => 'Campgrounds',
                'action' => 'index',
                'home'
            ],
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email']
                ]
            ]
        ]);
    }
	
    public function beforeFilter(Event $event)
    {
        
        $this->Auth->allow(['index', 'view', 'display']);
        $this->set('authUser',$this->Auth->user('id'));
    }
 
 
}
?>