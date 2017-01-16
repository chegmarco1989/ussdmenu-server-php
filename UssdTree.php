<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});
class UssdTree{
	private $address;
	private $treeMenu=array();
	private $ready=false;
	
	function isReady(){
		return $ready;
	}
	function setReady($ready){
		$this->ready=$ready;
	}
	function __construct($treeHeader,$address){
		$this->address=$address;
		$this->addNode(new UssdNode($treeHeader,'root','0'));
	}
	function addNode($nodes){
		if(!empty($nodes)){
			if(is_array($nodes)){
				foreach($nodes as $node){
					$node->setAddress($this->address);
					$treeMenu[$node->getName()]=$node;
					$parent=$node->getParent();
					if(in_array($parent,$this->treeMenu)){
						$this->treeMenu[$parent]->addChild($node->getName());
					}
				}
			}
			else{
					$nodes->setAddress($this->address);
					$treeMenu[$nodes->getName()]=$nodes;
					$parent=$nodes->getParent();
					if(in_array($parent,$this->treeMenu)){
						$this->treeMenu[$parent]->addChild($nodes->getName());
					}
			}
		}
	}
	function getNode($name){
		return $this->treeMenu[$name];
	}
}
?>