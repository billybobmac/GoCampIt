<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Email\Email;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;



class EmailsController extends AppController
{
	public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        // Allow users to register and logout.
        // You should not add the "login" action to allow list. Doing so would
        // cause problems with normal functioning of AuthComponent.
        $this->Auth->allow();
    }
    
    public function open($messagecode=NULL)
    {
    	if (isset($messagecode)){
	    	$email = $this->Emails->findByMessagecode($messagecode)->first();
			$email['opens']=$email['opens']+1;
			$this->Emails->save($email);
			$emaillog=$this->Emails->EmailsLogs->newEntity();
	     	$emaillog['email_id']=$email['id'];
	     	$emaillog['action']="open";
	     	$emaillog['query'] = "";
	     	$emaillog['client']=$this->request->header('User-Agent');
	     	$emaillog['ip']=$this->request->clientIp();
	     	$this->Emails->EmailsLogs->save($emaillog);
	
			$this->response->file('Template/Email/FFFFFF-0.png');
	    	
			return $this->response;
    	}
    	else {
    		throw new NotFoundException();	
    	}
    }
	public function click($messagecode=NULL)
    {
    	if (isset($messagecode)){
	    	$email = $this->Emails->findByMessagecode($messagecode)->first();
			$email['clicks']=$email['clicks']+1;
			$this->Emails->save($email);
			$emaillog=$this->Emails->EmailsLogs->newEntity();
			$emaillog['action'] = "click";
			$emaillog['email_id']=$email['id'];
			$emaillog['query'] = "";
			$emaillog['ip'] = $this->request->clientIp();
			$emaillog['client']=$this->request->header('User-Agent');
			$this->Emails->EmailsLogs->save($emaillog);
	     	$this->redirect($this->request->query['url']);
    	}
    	else {
    		throw new NotFoundException();
    	}
    }
    
    public function unsubscribe($messagecode=NULL)
    {
    	if (isset($messagecode)) {
    		$email = $this->Emails->findByMessagecode($messagecode)->first();
			$email['unsubscribes']=$email['unsubscribes']+1;
			$this->Emails->save($email);
			$emaillog=$this->Emails->EmailsLogs->newEntity();
			$emaillog['action'] = "unsubscribe";
			$emaillog['email_id']=$email['id'];
			$emaillog['query'] = "";
			$emaillog['ip'] = $this->request->clientIp();
			$emaillog['client']=$this->request->header('User-Agent');
			$this->Emails->EmailsLogs->save($emaillog);
			$user=$this->Emails->Users->get($email['user_id']);
			$user['mailmaster']=FALSE;
			$user['mailpromos']=FALSE;
			$this->Emails->Users->save($user);
    	}
    	else{
    		throw new NotFoundException();
    	}
    	
    	
    }
    public function bounce()
    {
    	if ($this->request->is('post')) {
			$data = $this->request->input('json_decode',true);
			if ($data['Type']=='SubscriptionConfirmation'){
				//Subscribe to feed if it's a confirmation.
				$contents = file_get_contents($data['SubscribeURL']);
				$this->loadModel('EmailsLogs');
				$emaillog=$this->EmailsLogs->newEntity();
				$emaillog['action'] = "bouncesubscribe";
				$emaillog['email_id']=0;
				$emaillog['query'] = serialize($data);
				$emaillog['ip'] = $this->request->clientIp();
				$emaillog['client']=$this->request->header('User-Agent');
				$this->EmailsLogs->save($emaillog);
			}
			else {
				$email=(json_decode($data['Message'],true));
				
				if (isset($email['mail'])) {
					//If not a subscribe, then it's a complaint message.
					if ($this->Emails->findByMessageid($email['mail']['messageId'])->count()!=0) {
						//if messageID is already populated then add to log.
						$row = $this->Emails->findByMessageid($email['mail']['messageId'])->first();
					}
					else {
						$row=$this->Emails->find('all')->where(['to_address'=>$email['mail']['destination'][0]])->andWhere('messageid IS NULL')->first();
						$row['messageid']=$email['mail']['messageId'];
						$this->Emails->save($row);
					}
					if (isset($row)) {
						$row['bounces']=$row['bounces']+1;
						$this->Emails->save($row);
						$emaillog=$this->Emails->EmailsLogs->newEntity();
				     	$emaillog['email_id']=$row['id'];
				     	$emaillog['action']="bounce";
				     	$emaillog['query'] = serialize($data);
				     	$emaillog['client']=$this->request->header('User-Agent');
				     	$emaillog['ip']=$this->request->clientIp();
				     	$this->Emails->EmailsLogs->save($emaillog);
				     	
						$user=$this->Emails->Users->get($row['user_id']);
						$user['mailmaster']=FALSE;
						$user['mailpromos']=FALSE;
						$this->Emails->Users->save($user);
					}	
				}
				else {
					$this->Emails->EmailsLogs->create();
					$this->request->data['EmailLog']['action'] = "bouncemessage";
					$this->request->data['EmailLog']['query'] = serialize($email);
					$this->request->data['EmailLog']['ip'] = $this->request->clientIp();
					$this->request->data['EmailLog']['client']=$this->request->header('User-Agent');
					$this->Email->EmailLog->save($this->request->data);
				}
			}
			
    	}
    }
    public function complaint()
    {
    	if ($this->request->is('post')) {
			$data = $this->request->input('json_decode',true);
			if ($data['Type']=='SubscriptionConfirmation'){
				//Subscribe to feed if it's a confirmation.
				$contents = file_get_contents($data['SubscribeURL']);
				$this->loadModel('EmailsLogs');
				$emaillog=$this->EmailsLogs->newEntity();
				$emaillog['action'] = "complaintsubscribe";
				$emaillog['email_id']=0;
				$emaillog['query'] = serialize($data);
				$emaillog['ip'] = $this->request->clientIp();
				$emaillog['client']=$this->request->header('User-Agent');
				$this->EmailsLogs->save($emaillog);
			}
			else {
				$email=(json_decode($data['Message'],true));
				
				if (isset($email['mail'])) {
					//If not a subscribe, then it's a complaint message.
					if ($this->Emails->findByMessageid($email['mail']['messageId'])->count()!=0) {
						//if messageID is already populated then add to log.
						$row = $this->Emails->findByMessageid($email['mail']['messageId'])->first();
					}
					else {
						$row=$this->Emails->find('all')->where(['to_address'=>$email['mail']['destination'][0]])->andWhere('messageid IS NULL')->first();
						$row['messageid']=$email['mail']['messageId'];
						$this->Emails->save($row);
					}
					if (isset($row)) {
						$row['complaints']=$row['complaints']+1;
						$this->Emails->save($row);
						$emaillog=$this->Emails->EmailsLogs->newEntity();
				     	$emaillog['email_id']=$row['id'];
				     	$emaillog['action']="complaint";
				     	$emaillog['query'] = serialize($data);
				     	$emaillog['client']=$this->request->header('User-Agent');
				     	$emaillog['ip']=$this->request->clientIp();
				     	$this->Emails->EmailsLogs->save($emaillog);
						$user=$this->Emails->Users->get($row['user_id']);
						$user['mailmaster']=FALSE;
						$user['mailpromos']=FALSE;
						$this->Emails->Users->save($user);
					}	
				}
				else {
					$this->Email->EmailLog->create();
					$this->request->data['EmailLog']['action'] = "complaintmessage";
					$this->request->data['EmailLog']['query'] = serialize($email);
					$this->request->data['EmailLog']['ip'] = $this->request->clientIp();
					$this->request->data['EmailLog']['client']=$this->request->header('User-Agent');
					$this->Email->EmailLog->save($this->request->data);
				}
			}
			
    	}
    }
    public function deliver() {
		if ($this->request->is('post')) {
			$data = $this->request->input('json_decode',true);
			if ($data['Type']=='SubscriptionConfirmation'){
				//Subscribe to feed if it's a confirmation.
				$contents = file_get_contents($data['SubscribeURL']);
				$this->loadModel('EmailsLogs');
				$emaillog=$this->EmailsLogs->newEntity();
				$emaillog['action'] = "deliversubscribe";
				$emaillog['email_id']=0;
				$emaillog['query'] = serialize($data);
				$emaillog['ip'] = $this->request->clientIp();
				$emaillog['client']=$this->request->header('User-Agent');
				$this->EmailsLogs->save($emaillog);
			}
	

			else {
				$email=(json_decode($data['Message'],true));
				if (isset($email['mail'])) {
					//If not a subscribe, then it's a delivery message.
					if ($this->Emails->findByMessageid($email['mail']['messageId'])->count()!=0) {
						//if messageID is already populated then add to log.
						$row = $this->Emails->findByMessageid($email['mail']['messageId'])->first();
					}
					else {
						$row=$this->Emails->find('all')->where(['to_address'=>$email['mail']['destination'][0]])->andWhere('messageid IS NULL')->first();
						$row['messageid']=$email['mail']['messageId'];
						$this->Emails->save($row);
					}
					if (isset($row)) {
						$emaillog=$this->Emails->EmailsLogs->newEntity();
						$emaillog['action'] = "deliver";
						$emaillog['email_id']=$row['id'];
						$emaillog['query'] = serialize($data);
						$emaillog['ip'] = $this->request->clientIp();
						$emaillog['client']=$this->request->header('User-Agent');
						$this->Emails->EmailsLogs->save($emaillog);
					}
					else {
						$this->loadModel('EmailsLogs');
						$emaillog=$this->EmailsLogs->newEntity();
						$emaillog['action'] = "deliverother";
						$emaillog['email_id']=0;
						$emaillog['query'] = serialize($data);
						$emaillog['ip'] = $this->request->clientIp();
						$emaillog['client']=$this->request->header('User-Agent');
						$this->EmailsLogs->save($emaillog);
						
					}
				}
			}	
		}
		else{
			throw new NotFoundException();			
						
			
		}
	}
	public function mailto(){
		if ($this->request->is('post')) {
			$data = $this->request->input('json_decode',true);
			if ($data['Type']=='SubscriptionConfirmation'){
				//Subscribe to feed if it's a confirmation.
				$contents = file_get_contents($data['SubscribeURL']);
				$this->loadModel('EmailsLogs');
				$emaillog=$this->EmailsLogs->newEntity();
				$emaillog['action'] = "mailtosubscribe";
				$emaillog['email_id']=0;
				$emaillog['query'] = serialize($data);
				$emaillog['ip'] = $this->request->clientIp();
				$emaillog['client']=$this->request->header('User-Agent');
				$this->EmailsLogs->save($emaillog);
			}
			else {
				$email=(json_decode($data['Message'],true));
				$messagecode=str_ireplace('unsubscribe+','',strstr($email['receipt']['recipients'][0], '@', true));
				if ($this->Emails->findByMessagecode($messagecode)->count()!=0) {
					$row = $this->Emails->findByMessagecode($messagecode)->first();
					$row['unsubscribes']=$row['unsubscribes']+1;
					$this->Emails->save($row);
					$emaillog=$this->Emails->EmailsLogs->newEntity();
					$emaillog['action'] = "mailtounsubscribe";
					$emaillog['email_id']=0;
					$emaillog['query'] = serialize($data);
					$emaillog['ip'] = $this->request->clientIp();
					$emaillog['client']=$this->request->header('User-Agent');
					$this->Emails->EmailsLogs->save($emaillog);
					$user=$this->Emails->Users->get($row['user_id']);
					$user['mailmaster']=FALSE;
					$user['mailpromos']=FALSE;
					$this->Emails->Users->save($user);
				}
				else {
					$this->loadModel('EmailsLogs');
					$emaillog=$this->EmailsLogs->newEntity();
					$emaillog['action'] = "mailtomessage";
					$emaillog['email_id']=0;
					$emaillog['query'] = serialize($data);
					$emaillog['ip'] = $this->request->clientIp();
					$emaillog['client']=$this->request->header('User-Agent');
					$this->EmailsLogs->save($emaillog);
				}
			}
		}
		else{
			throw new NotFoundException();			
						
			
		}
		
	}
    
}