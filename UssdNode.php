<?php
class UssdNode{
	private $name='';
	private $parent0='';
	private $title='';
	private $address='';
	private $children=array();
	private $index=0;
	
	function __construct($name,$title,$parent){
		$this->name=$name;
		$this->parent0=$parent;
		$this->title=$title;
	}
	function processNodeEndEvent($userData){
		return 'Thanks, end of session';
	}
	function addChild($childName){
		$this->children[]=$childName;
	}
	function addChildren($children){
		foreach($children as $child){
			$this->children[]=$child;
		}
	}
	function hasChildren(){
		return count($this->children)>0;
	}
	function setAddress($address){
		$this->address=$address;
	}
	function getName(){
		return $this->name;
	}
	function getParent(){
		return $this->parent0;
	}
	function getAddress(){
		return $this->address;
	}
	function getTitle(){
		return $this->title;
	}
	function getChildren(){
		return $this->children;
	}
	function getNameFromIndex($index){
		return $this->children[$index-1]
	}
	function toString(){
		$objectString='';
		$items=$this->children;
		$bufferLimit=(count($items)==0)?1:$this->getBufferLimit()+1;
		do{
			$bufferLimit-=1;
			$objectString=$this->recurseMenu($items,$bufferLimit);
		}while(strlen($objectString>160));
		$this->index=$bufferLimit;
		return $objectString;
	}
	
	function getBufferLimit(){
		$len=count($children);
		$margin=$len-$index;
		if($margin<5)
			return $index+$margin;
		else
			return $index+5;
	}
	function recurseMenu($items,$bufferLimit){
		$objectString=$this->getTitle();
		$lastMenu=false;
		if(count($items)>0){
			for($i=$this->index;$i<$bufferLimit;$++){
				$item=$items[$i];
				$num=$i+1;
				//get node by name
				$userSessions=$_SESSION['userSessions'];
				$currUserSession=$userSessions[$this->address];
				$node=$currUserSession->getNode($item);
				$title=$node->getTitle();
				$objectString=$objectString . PHP_EOL .$num . '.' .$title;
			}
		}
		else{
			$objectString=$objectString.PHP_EOL . 'NO DATA AVAILABLE, TRY AGAIN LATER';
		}
		$lastMenu=$bufferLimit==count($items);
		$objectString=$objectString . PHP_EOL . '0.Exit';
		if($this->getParent()!='0'){
			$objectString=$objectString . PHP_EOL . '#.Back';
			if($lastMenu===false){
				$rem=count($items)-$this->index;
				$objectString=$objectString . PHP_EOL . '00.More('.$rem.')';
			}
		}
		return $objectString;
	}
}
?>