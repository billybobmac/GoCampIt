<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Email\Email;
use Cake\Network\Exception\NotFoundException;



class UsersController extends AppController
{
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        // Allow users to register and logout.
        // You should not add the "login" action to allow list. Doing so would
        // cause problems with normal functioning of AuthComponent.
        $this->Auth->allow(['signup', 'logout', 'confirm','forgotpassword','resetpassword']);
    }

    public function login()
    
    {
    	if ($this->Auth->user()) {
    		return $this->redirect(['controller' => 'campgrounds']);
    	}
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if (($user)&&($user['status']==1)) {
                $this->Auth->setUser($user);
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error(__('Invalid username or password, try again'));
        }
    }

    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }
	

  

  
    public function confirm($email=Null,$hash=Null) {
    	if ($this->Auth->user()) {
    		return $this->redirect(['controller' => 'campgrounds']);
    	}
    	if (isset($email) AND isset($hash)) {
    		$query = $this->Users->findByEmail($email);
			$user=$query -> first();
			
			if (($user['verification_hash']==$hash)&&($user['status']==0)){
				$user['email_confirmed'] = TRUE;
				$user['status'] = 1;
				if ($this->Users->save($user)) {
					$userlog=$this->Users->UsersLogs->newEntity();
	            	$userlog['user_id']=$user['id'];
	            	$userlog['action']="confirm";
	            	$userlog['client']=$this->request->header('User-Agent');
	            	$userlog['ip']=$this->request->clientIp();
	            	$this->Users->UsersLogs->save($userlog);
					$this->Flash->success(__('The user has been confirmed! Please login to continue'));
					$this->setAction('login');
					
					
					
				}
				else {
					throw new NotFoundException();
				}
				
			}
			else {
				throw new NotFoundException();
			}
			
    		
    	}
    	else{
    		throw new NotFoundException();
    	}
    }

    public function signup()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            //Creates verification hash to confirm user.
            $user -> verification_hash = bin2hex(openssl_random_pseudo_bytes(16)); 
  			$user -> type = 1;
            if ($this->Users->save($user)) {
            	//Save Userlog of action.
            	$userlog=$this->Users->UsersLogs->newEntity();
            	$userlog['user_id']=$user['id'];
            	$userlog['action']="signup";
            	$userlog['client']=$this->request->header('User-Agent');
            	$userlog['ip']=$this->request->clientIp();
            	$this->Users->UsersLogs->save($userlog);
            	//Send welcome email to user
            	$messagecode=bin2hex(openssl_random_pseudo_bytes(16));
                
                //Create email entry for email.    
                $emailentity=$this->Users->Emails->newEntity();
                $emailentity['user_id']=$user['id'];
                $emailentity['templatename']="welcome";
                $emailentity['to_address']=$user['email'];
                $emailentity['messagecode']=$messagecode;
                $emailentity['content']="meh";
                $this->Users->Emails->save($emailentity);
                //create email sent in email log.
             	$emaillog=$this->Users->Emails->EmailsLogs->newEntity();
             	$emaillog['email_id']=$emailentity['id'];
             	$emaillog['action']="sent";
             	$emaillog['client']=$this->request->header('User-Agent');
             	$emaillog['ip']=$this->request->clientIp();
             	$this->Users->Emails->EmailsLogs->save($emaillog);
             	
             	$email = new Email('default');
                $email ->template('welcome','welcome')
                	->emailFormat('both')
                	->viewVars(['email' => $user['email'],'verification_hash' =>$user['verification_hash'],'messagecode'=>$messagecode])
                	->from(['support@dev.gocampit.com' => 'GoCampIt'])
                    ->to($user->email)
                    ->subject('GoCampIt - Please activate your account')
                    ->addHeaders(['List-Unsubscribe' => '<mailto:unsubscribe+'.$messagecode.'@email.'.$_SERVER['HTTP_HOST'].'>,<>'])
                    ->send();
             	
             	
    			$this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['action' => 'signup']);
            }
            $this->Flash->error(__('Unable to add the user.'));
        }
        $this->set('user', $user);
        
    }
    public function forgotpassword() {
    	//This function is first step in forgot password. Reset Password is second step where user gets link in email.	
    
    	if ($this->Auth->user()) {
    		//If user is logged in already, throw not found.
    		throw new NotFoundException;
    	}
    	else{
    		if ($this->request->is('post')) {
    			$data = $this->request->data();
    			$user =$this->Users->findByEmail($data['email'])->first();
    			$user = $this->Users->patchEntity($user,$this->request->data());
    			$user['verification_hash']=bin2hex(openssl_random_pseudo_bytes(16));
    			$user['status']=0; //set status to 0 like as in new user.
    			$this->Users->save($user);
    			$userlog=$this->Users->UsersLogs->newEntity();
            	$userlog['user_id']=$user['id'];
            	$userlog['action']="resetpassword";
            	$userlog['client']=$this->request->header('User-Agent');
            	$userlog['ip']=$this->request->clientIp();
            	$this->Users->UsersLogs->save($userlog);
            	$messagecode=bin2hex(openssl_random_pseudo_bytes(16));
                
                //Create email entry for email.    
                $emailentity=$this->Users->Emails->newEntity();
                $emailentity['user_id']=$user['id'];
                $emailentity['templatename']="resetpassword";
                $emailentity['to_address']=$user['email'];
                $emailentity['messagecode']=$messagecode;
                $emailentity['content']="meh";
                $this->Users->Emails->save($emailentity);
                //create email sent in email log.
             	$emaillog=$this->Users->Emails->EmailsLogs->newEntity();
             	$emaillog['email_id']=$emailentity['id'];
             	$emaillog['action']="sent";
             	$emaillog['client']=$this->request->header('User-Agent');
             	$emaillog['ip']=$this->request->clientIp();
             	$this->Users->Emails->EmailsLogs->save($emaillog);
             	
             	$email = new Email('default');
                $email ->template('resetpassword','resetpassword')
                	->emailFormat('both')
                	->viewVars(['email' => $user['email'],'verification_hash' =>$user['verification_hash'],'messagecode'=>$messagecode])
                	->from(['support@dev.gocampit.com' => 'GoCampIt'])
                    ->to($user->email)
                    ->subject('GoCampIt - Password Reset')
                    ->addHeaders(['List-Unsubscribe' => '<mailto:unsubscribe+'.$messagecode.'@email.'.$_SERVER['HTTP_HOST'].'>,<>'])
                    ->send();
    			
    			
    		}
    		
    	}
    	
    }
    public function resetpassword($email=Null,$verification_hash=Null) {
    	if ($this->Auth->user()) {
    		//If user is logged in already, throw not found.
    		throw new NotFoundException;
    	}
    	else{
    		if ($this->request->is('post')) {
    			//user submitting new password
    			if (isset($email) AND isset($verification_hash)) {
    				//confirm both email and verification are in URL
		    		$user = $this->Users->findByEmail($email)->first();
					
					if (($user['verification_hash']==$verification_hash)&&($user['status']==0)){
						//this validates the request from the url.
						$user = $this->Users->patchEntity($user, $this->request->data);
						if ($this->Users->save($user)) {
							//Save Userlog of action.
			            	$userlog=$this->Users->UsersLogs->newEntity();
			            	$userlog['user_id']=$user['id'];
			            	$userlog['action']="resetpasswordsteptwo";
			            	$userlog['client']=$this->request->header('User-Agent');
			            	$userlog['ip']=$this->request->clientIp();
			            	$this->Users->UsersLogs->save($userlog);
			            	$this->setAction('login');
						}
						
					}
					else {
						//Does not validate
						throw new NotFoundException;
					}
    			}
    			
    		}
    		else {
    			//user has just clicked link in email.
    			if (isset($email) AND isset($verification_hash)) {
    				//confirm both email and verification are in URL
		    		$user = $this->Users->findByEmail($email)->first();
					
					if (($user['verification_hash']==$verification_hash)&&($user['status']==0)){
						//this validates the request from the url.
						//Display form.
						
					}
					else {
						//Does not validate
						throw new NotFoundException;
					}
    			}
    			else {
    				//Missing requirement.
    				throw new NotFoundException;
    			}
    		}
    		
    	}
    	
    	
    }
    


}
?>