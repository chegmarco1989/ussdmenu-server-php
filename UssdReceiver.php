<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});
session_start();
$_SESSION['userSessions']=array();
abstract class UssdReceiver{
	abstract public function getAppShortCode();
	abstract public function getUssdTree($address);
	public function onMessage($request){
		$address=$request['msisdn'];
		$message=$request['requestString'];
		$convId=$request['transactionId'];
		$validationResult=$this->validateRequest($address,$message,$convId);
		if(is_array($validationResult)){
			return $validationResult;
		}
		else if($validationResult===true){
			if($this->isContinuation($address)){
				return $this->handleContinuingRequests($address,$message,$convId);
			}
			else {
				return $this->initUserSession($address,$convId);
			}
		}
	}
	private function initUserSession($address,$convId){
		$sess=new UssdUserSession();
		$myTree=$this->getUssdTree($address);
		if($myTree->isReady()===true){
			$sess->initUser($myTree);
			$sess->addData('-msisdn-',$address);
			$_SESSION['userSessions'][$address]=$sess;
			return sendMessage($_SESSION['userSessions'][$address]->fetchDisplay(),$address,$convId);
		}
		else return endSession("application not ready",$address,$convId);
		
	}
	private function handleContinuingRequests($address,$message,$convId){
		$currentName=$_SESSION['userSessions'][$address]->getCurrentNode()->getName();
		$selectedNode=$_SESSION['userSessions'][$address]->getCurrentNode()->getNameFromIndex(intval($message));
		
		$_SESSION['userSessions'][$address]->addData($currentName,$selectedNode);
		
		$newNode=$_SESSION['userSessions'][$address]->getMyTree()->getNode($selectedNode);
		
		if($newNode->hasChildren()===true){
			return $this->handleChildBearingNode($selectedNode,$address,$convId);
		}else{
			$_SESSION['userSessions'][$address]->setCurrentNode($newNode);
			return $this->handleChildlessNode($address,$convId);
		}
	}
	private function handleChildBearingNode($newNode,$address,$convId){
		$_SESSION['userSessions'][$address]->changeNode($newNode);
		$display=$_SESSION['userSessions'][$address]->fetchDisplay();
		return sendMessage($display,$address,$convId);
	}
	private function handleChildlessNode($address,$convId){
		$sess=$_SESSION['userSessions'][$address];
		return $this->endSession($sess->getCurrentNode()->processNodeEndEvent($sess->getAllUserData()),$address,$convId);
	}
	private function endSession($msg,$address,$convId){
		if(in_array($address,$_SESSION['userSessions']))
			unset($_SESSION['userSessions'][$address]);
		$response[]=array('address'=>$address,'message'=>$msg,'termination'=>true,'conversationId'=>$convId);
		return $response;
		
	}
		private function sendMessage($msg,$address,$convId){
		$response[]=array('address'=>$address,'message'=>$msg,'termination'=>false,'conversationId'=>$convId);
		return $response;
		
	}
	private function validateRequest($address,$message,$convId){
		if($this->isContinuation($address)===false){
			if($this->isUssdCode($message)===false){
				return $this->endSession('invalid short code',$address,$convId);
			}
			if($this->notAppShortCode($message)===true){
				return $this->endSession('unknown application',$address,$convId);
			}
			
		}
		if($message=='0'){
			return $this->endSession('Thank you, good bye',$address,$convId); 
		}
		if($message=='00'){
			$resp=$_SESSION['userSessions'][$address]->fetchDisplay();
			return $this->sendMessage($resp,$address,$convId);
		}
		if($message=='#'){
			$_SESSION['userSessions'][$address]->loadPrevNode();
			$resp=$_SESSION['userSessions'][$address]->fetchDisplay();
			return $this->sendMessage($resp,$address,$convId);
		}
		if($this->isContinuation($address)===true){
			if($this->isUssdCode($message)){
			return $this->endSession('Expecting integer not shortcode',$address,$convId); 
			}
			if($this->isControlSignal($message)===false){
				if(is_int($message)===false){
				return $this->endSession('Expecting integer, non-integer input received',$address,$convId); 
				}
				else if(intval($message)<0 || intval($message)>count(($_SESSION['userSessions'][$address]->getDisplayedLength()))){
				return $this->endSession('Input out of range',$address,$convId); 
				}
				
			}
		}
		return true;
	}
	private function isControlSignal($msg){
		return ($msg=='0' or $msg=='00');
	}
	private function isContinuation($address){
		return in_array($address,$_SESSION['userSessions']);
	}
	
	private function notAppShortCode($msg){
		if($this->getAppShortCode()==$msg)
			return true;
		else return false;
	}
	private function isUssdCode($msg){
		if($msg[0]=='*' && $msg[strlen($msg)-1]=='#')
			return true;
		else return false;
	}
}
?>