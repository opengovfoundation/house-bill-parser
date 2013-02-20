<?php
/**
 * 	Bill class file for bill parse script
 */

	/**
	 * 	Bill class
	 */
	class Bill{
		public $bill;
		public $slug;
		public $id;
		protected $pdo;
		protected $rootNode;
	
		/**
		 * 	Bill Constructor
		 */
		function __construct($bill, $slug, $pdo, $rootNode = null){
			$this->bill = $bill;
			$this->slug = $slug;
			$this->pdo = $pdo;
			$this->rootNode = $rootNode;
		}
	
		/**
		 * 	Save bill to Madison database
		 */
		public function save(){
			$pdo = $this->pdo;
			
			//Slug set as unique key in database
			$stmt = $pdo->prepare("INSERT INTO bills (bill, slug) VALUES (?,?) ON DUPLICATE KEY UPDATE bill = VALUES(bill), slug = VALUES(slug)");
		
			//Execute the mysql transaction
			try{
				$pdo->beginTransaction();
				$stmt->execute(array($this->bill, $this->slug));
				$pdo->commit();
				
				//TODO: This will not return the correct id on update
					//This id is unreliable and should not be used to save the bill content
				$this->id = $pdo->lastInsertId('id');
			}catch(PDOException $e){
				$pdo->rollback();
				printError("Error inserting bill ($this->bill): " . $e->getMessage);
			}
		
			if($this->id == 0){
				printError("Id came back $this->id for bill ($this->bill)");
			}
			
			//This is not a reliable value
			return $this->id;
		}
	
		/**
		 * 	Return Bill id (this is currently unreliable)
		 */
		public function getId(){
			return $this->id;
		}
		
		/**
		 * 	Set bill root element (DOMObject)
		 */
		public function setRoot($rootNode){
			$this->rootNode = $rootNode;
		}
		
		/**
		 * 	Save bill content to Madison database
		 */
		public function saveContent(){
			if(!isset($this->pdo) || !isset($this->rootNode)){
				return false;
			}
			
			
			
		}
	}