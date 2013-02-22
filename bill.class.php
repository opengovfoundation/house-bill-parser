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
		public $rootNode;
		public $content;
		public $pdo;
		public $structure = array('section', 'subsection', 'paragraph', 'subparagraph', 'clause', 'subclause', 'item');
		public $text_tags = array('enum', 'header', 'text');
	
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
				
				//This will return id = 0 on update
				$this->id = $pdo->lastInsertId('id');
				
				//If the id = 0, it already exists.  
				//Check if user wants to update meta and append content
				if($this->id == 0){
					$input = trim(getInput("This bill already exists.  Update bill and append content?  Reply y/n."), " \n");
					if($input != 'y'){
						printMessage("Stopping script. ($input)");
						$pdo->rollBack();
						exit;
					}
				}
				
				$pdo->commit();
				
			}catch(PDOException $e){
				$pdo->rollBack();
				printError("Error inserting bill ($this->bill): " . $e->getMessage());
			}
		
			if($this->id == 0){
				printMessage("Warning: Id came back $this->id for bill ($this->bill)");
			}
			
			return $this->id;
		}
	
		/**
		 * 	Return Bill id (this is currently unreliable)
		 */
		public function getId(){
			return $this->id;
		}
		
		/**
		 * 	Set bill id by querying database for slug
		 */
		public function updateBillId(){
			$pdo = $this->pdo;
			
			try{
				$stmt = $pdo->prepare("SELECT id FROM bills WHERE slug = '$this->slug'");
				$stmt->execute();
				
				$result = $stmt->fetchColumn();
				$this->id = $result;
				printMessage("Updating bill id ($this->id)");
				
			}catch(Exception $e){
				printError("Error updating bill id.");
			}
			
		}
		
		/**
		 * 	Update bill init section
		 */
		public function updateBillInit($init_id){
			$pdo = $this->pdo;
			
			try{
				$stmt = $pdo->prepare("UPDATE bills SET init_section = '$init_id' WHERE slug = '$this->slug'");
				$stmt->execute();
				
				printMessage("Updating bill init_section ($init_id)");
				
			}catch(Exception $e){
				printError("Error updating bill init_section.");
			}
			
		}
		
		/**
		 * 	Set bill root element (DOMObject)
		 */
		public function setRoot($rootNode){
			$this->rootNode = $rootNode;
		}
		
		/**
		 * 	Set parent node content
		 */
		public function setFirstContent($content){
			$this->first_content = $content;
		}
		
		/**
		 * 	Save content item to database
		 */
		protected function saveContentItem($bill_id, $parent, $child_priority, $content){
			$pdo = $this->pdo;
			
			//Prepare the insert statement
			$stmt = $pdo->prepare("INSERT INTO bill_content (bill_id, parent, child_priority, content) VALUES (:bill_id, :parent, :child_priority, :content)");
			
			//Bind data to statement
			$stmt->bindParam(':bill_id', $bill_id);
			$stmt->bindParam(':parent', $parent);
			$stmt->bindParam(':child_priority', $child_priority);
			$stmt->bindParam(':content', $content);
			
			//Save item
			$stmt->execute();
			
			//Retrieve item id
			$id = $pdo->lastInsertId('id');
			
			if($id == 0){
				throw new Exception("Error saving content item (parent: $parent | content: $content)");
			}
			
			return $id;
		}
		
		/**
		 * 	Combine child nodes designated as relevant text
		 */
		protected function getNodeContent($node){
			$content = "";
			
			foreach($node->childNodes as $child){
				if(in_array($child->nodeName, $this->text_tags)){
					$content .= " " . preg_replace('/\s+/', ' ', $child->nodeValue);
				}
			}
			
			$content = trim($content);
			return $content;
		}
		
		/**
		 * 	Recursively save children trees
		 */
		protected function saveChildren($node, $parent_id, $child_priority){
			
			if(!isset($parent_id) || $parent_id == null || $parent_id == 0){
				throw new Exception("Error saving children tree (parent_id invalid).  Node: " . print_r($node, true));
			}
			
			//If the node is not in the document structured elements
			@$valid = in_array($node->tagName, $this->structure);
			if(!$valid){
				//If the node has no children, return
				if(!$node->hasChildNodes()){
					return;
				}
				else{//Otherwise save the children, passing on the parent id
					$c = 0;
					foreach($node->childNodes as $child){
						$this->saveChildren($child, $parent_id, $c++);
					}
				
					return;
				}
			}
			
			//Save this item
			$id = $this->saveContentItem($this->id, $parent_id, $child_priority, $this->getNodeContent($node));
			
			//return if no children
			if($node->childNodes->length == 0){
				return;
			}
			
			//Save children trees
			$c = 0;
			foreach($node->childNodes as $child){
				$this->saveChildren($child, $id, $c++);
			}
		}
		
		/**
		 * 	Save bill content to Madison database
		 */
		public function saveContent(){
			//Ensure required attributes are set
			if(!isset($this->pdo) || !isset($this->rootNode) || !isset($this->first_content)){
				printError("Error saving content, required attributes not set.");
			}
			
			$pdo = $this->pdo;
			
			try{
				//pull bill id from database
				$this->updateBillId();
				
				$pdo->beginTransaction();
				
				//Save the first content item
				$id = $this->saveContentItem($this->id, 0, 0, $this->first_content);
				
				//Update the init section in the bill meta table
				$this->updateBillInit($id);
				
				//Save child trees
				$c = 0;
				foreach($this->rootNode->childNodes as $child){
					$this->saveChildren($child, $id, $c++);
				}
				
				$pdo->commit();
			}catch (Exception $e){
				$pdo->rollBack();
				printError("Error saving bill content: " . $e->getMessage());
			}
		}
	}