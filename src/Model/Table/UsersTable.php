<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
	public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->hasMany('Emails');
        $this->hasMany('UsersLogs');
        
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('password', 'A password is required')
            ->add('email', 'valid', ['rule' => 'email'])
            ->add('email', [ 'unique' => ['rule' => 'validateUnique', 'provider' => 'table', 'message'=>'You already have an account'], ])
            ->add('password_confirm',
			    'compareWith', [
			        'rule' => ['compareWith', 'password'],
			        'message' => 'Passwords not equal.'
			    ]
				)
            ->add('email_confirm',
			    'compareWith', [
			        'rule' => ['compareWith', 'email'],
			        'message' => 'Emails not equal.'
			    ]
				)
            ->requirePresence('termsofservice','create');

            
            
    }
  	public function validationPasswordReset(Validator $validator)
    {
        return $validator
            ->notEmpty('email', 'An email address is required')
            
            ->add('email_confirm',
			    'compareWith', [
			        'rule' => ['compareWith', 'email'],
			        'message' => 'Emails not equal.'
			    ]
			);
    }

}
?>
