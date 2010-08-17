<?php
/**
 * OwnedBehavior Class
 * 
 * Creates the ability to set permissions on a model's nodes level.
 * 
 * PHP versions 4 and 5
 * 
 * Foxycoder: Sassy Talk on Nerdy Subjects (http://foxycoder.com)
 * Copyright 2010, W.R. de Vos (wrdevos@gmail.com) 
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @copyright 2010 - W.R. de Vos
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link http://foxycoder.com/cakephp-owned-behavior
 * 
**/
	class OwnedBehavior extends ModelBehavior {
	/**
	 * 
	 * Owner model to use
	 * 
	 * @var String
	 * @access protected
	 * @default 'Owned.Owner'
	**/
		var $_ownerModel = '';
	/**
	 * 
	 * User model to use
	 * 
	 * @var String
	 * @access protected
	 * @default 'User'
	**/
		var $_userModel  = '';
	/**
	 * 
	 * User primary key to use
	 * 
	 * Set this to the name of your user model's primary key name
	 * 
	 * @var String
	 * @access protected
	 * @default 'user_id'
	**/
		var $_userPrimaryKey = '';
	/**
	 * 
	 * Ownership mode to use
	 * 
	 * Set this to either 'crud' (default) or 'owner'.
	 * 		- crud: creates the ability to set different rights for c(reate),
	 * 				r(ead), u(pdate), and (d)elete. This way a user can share
	 * 				objects in the database with other users.
	 * 		- owner: just checks whether the user owns the object. The above is
	 * 				 is obviously more fine grained.
	 * 
	 * @var String
	 * @access protected
	 * @default 'crud'
	**/
		var $_mode = '';
		
	/**
	 * 
	 * Return mode to use
	 * 
	 * Set this to either 'error' (default) or 'quiet'.
	 * 		- error: returns an error if a user tries to access something that
	 * 				 is not theirs.
	 * 		- quiet: act like the inaccessible does not exist.
	 * 
	 * @var String
	 * @access protected
	 * @default 'error'
	**/
		var $_returnMode = '';
		
	/**
	 * 
	 * Error texts if an inaccessible object was tried
	 * 
	 * @var Array
	 * @access protected
	**/
		var $errorText = '';
		
	/**
	 * Errors
	 *
	 * @var array
	 */
		var $errors = array();

	/**
	 * Defaults
	 *
	 * @var array
	 * @access protected
	 */		
		var $_defaults = array(
			'ownerModel' => 'Owned.Ownership',
			'userModel'  => 'User',
			'userPrimaryKey' => 'user_id',
			'mode' => 'crud',
			'returnMode' => 'error'
		);
		
		
	var $_Model;
	var $_User;
		
	/**
	 * Initiate Owned behavior
	 *
	 * @param object $Model instance of model
	 * @param array $config array of configuration settings.
	 * @return void
	 * @access public
	 */
	    function setup(&$Model, $config = array()) {
			if (!is_array($config)) {
	            $config = array('mode' => $config);
	        }
			if (isset($config['mode'])) {
				$config['mode'] = ($config['mode'] == 'owner') ? 'owner' : 'crud';
			}
			$this->errorText = __('You are not allowed to access this object.', true);
	        $settings = array_merge($this->_defaults, $config);
	        $this->settings[$Model->alias] = $settings;
			ClassRegistry::init($this->settings[$Model->alias]['userModel']);
			ClassRegistry::init($this->settings[$Model->alias]['ownerModel']);
			$this->_userModel = new $this->settings[$Model->alias]['userModel'];
			$this->_ownerModel = new $this->settings[$Model->alias]['ownerModel'];
			App::import('Model', 'CakeSession');
			$Session = new CakeSession();
			$this->_User = ($user) ? $user : $Session->read('Auth.User.id');
			$this->_Model = $Model;
		}
		
		function makeCreatable($id=null, $user=null) {
			if ($id) { // user could be null
				$this->_getData($id, $user);
				$data[$this->settings[$Model->alias]['ownerModel']]['c'] = 1; 
				return $this->_ownerModel->save($data);
			}
		}
		function makeReadable($id=null, $user=null) {
			if ($id) { // user could be null
				$this->_getData($id, $user);
				$data[$this->settings[$Model->alias]['ownerModel']]['r'] = 1; 
				return $this->_ownerModel->save($data);
			}
		}
		function makeWritable($id=null, $user=null) {
			if ($id) { // user could be null
				$this->_getData($id, $user);
				$data[$this->settings[$Model->alias]['ownerModel']]['u'] = 1; 
				return $this->_ownerModel->save($data);
			}
		}
		function makeDeletable($id=null, $user=null) {
			if ($id) { // user could be null
				$this->_getData($id, $user);
				$data[$this->settings[$Model->alias]['ownerModel']]['d'] = 1; 
				return $this->_ownerModel->save($data);
			}
		}		
		function _getData($id=null, $user=null) {
			$data = $this->_ownerModel->find('all', array(
				'conditions' => array(
					$this->settings[$Model->alias]['ownerModel'].'.model' => $this->_Model->alias,
					$this->settings[$Model->alias]['ownerModel'].'.'.$this->settings[$this->_Model->alias]['userPrimaryKey'] => $user,
					$this->settings[$Model->alias]['ownerModel'].'.foreign_key' => $id,
				)
			));
			if (!$data) {
				$this->_ownerModel->create();
				$data = array(
					$this->settings[$Model->alias]['ownerModel'] => array(
						'model' => $this->_Model->alias,
						$this->settings[$this->_Model->alias]['userPrimaryKey'] => $user,
						'foreign_key' => $id,
					));
			}
			return $data;
		}
		
	
		function _mine($id=null, $user=null) {
			$user = ($user) ? $user : $this->_User;
			$id = ($id) ? $id : $this->_Model->id;
			if (!$id)
				return true; // node does not extist *shrug*
			
			if ($user && $id) {
				$check = $this->_ownerModel->find('count', array(
					'conditions' => array(
						$this->settings[$Model->alias]['ownerModel'].'.model' => $this->_Model->alias,
						$this->settings[$Model->alias]['ownerModel'].'.'.$this->settings[$this->_Model->alias]['userPrimaryKey'] => $user,
						$this->settings[$Model->alias]['ownerModel'].'.foreign_key' => $id,
					)	
				));
				return (count($check) >= 1);
			}
			return false;
		}
		
		function _crud($id=null, $user=null, $min_access = array(1,1,1,1)) {
			$user = ($user) ? $user : $this->_User;
			$id = ($id) ? $id : $this->_Model->id;
			if ($user) {
				$check = $_ownerModel->find('count', array(
					'conditions' => array(
						$this->settings[$this->_Model->alias]['ownerModel'].'.model' => $this->_Model->alias,
						$this->settings[$this->_Model->alias]['ownerModel'].'.'.$this->settings[$this->_Model->alias]['userPrimaryKey'] => $user,
						$this->settings[$this->_Model->alias]['ownerModel'].'.foreign_key' => $id,
						$this->settings[$this->_Model->alias]['ownerModel'].'.c >=' => $min_access[0],
						$this->settings[$this->_Model->alias]['ownerModel'].'.r >=' => $min_access[1],
						$this->settings[$this->_Model->alias]['ownerModel'].'.u >=' => $min_access[2],
						$this->settings[$this->_Model->alias]['ownerModel'].'.d >=' => $min_access[3],
					)	
				));
				return (count($check) >= 1);
			}
			return false;
		}
		
		function _creatable() {
			if ($this->settings[$this->_Model->alias]['mode'] == 'owner') {
				return true;
			} else {
				return $this->_crud(null, null, array(1,0,0,0));
			}
		}
		
		function _readable($id=null, $user=null) {
			if ($this->settings[$this->_Model->alias]['mode'] == 'owner') {
				return $this->_mine($id, $user);
			} else {
				return $this->_crud($id, $user, array(0,1,0,0));
			}
		}
		
		function _writeable($id=null, $user=null) {
			if ($this->settings[$this->_Model->alias]['mode'] == 'owner') {
				return $this->_mine($id, $user);
			} else {
				return $this->_crud($id, $user, array(0,0,1,0));
			}
		}
		
		function _deletable($id=null, $user=null) {
			if ($this->settings[$this->_Model->alias]['mode'] == 'owner') {
				return $this->_mine($id, $user);
			} else {
				return $this->_crud($id, $user, array(0,0,0,1));
			}
		}
		
		function _throwError() {
			if ($this->settings[$this->_Model->alias]['returnMode'] == 'error') {
				throw new Exception($this->errorText);
			}
		}
		
		function afterFind($results=array(), $primary=true) {
			if ($primary) {
				if (!$this->_readable($this->_Model->id, $this->_User)) {
					$this->throwError();
					return array();
				}
			} else {
				$i=0;
				foreach ($results as $result) {
					if (!$this->_readable($result[$this->_Model->alias][$this->_Model->primaryKey], $this->_User)) {
						$this->throwError();
						unset($results[$i]); // remove it from the results
					}
					$i++;
				}
			}
			return $results;
		}
	
		function beforeSave() {
			return $this->_writable(null, null);
		}
	
		function afterSave($created=false) {
			if ($created) {
				$this->_ownerModel->create();
				$this->_ownerModel->save(array(
					$this->settings[$this->_Model->alias]['ownerModel'] => array(
						'model' => $this->_Model->alias,
						'foreign_key' => $this->_Model->id,
						$this->settings[$this->_Model->alias]['userPrimaryKey'] => $this->_User,
						'c' => 1,
						'r' => 1,
						'u' => 1,
						'd' => 1,
					)
				));
			}
		}

	/**
	 * Check ownership before delete.
	 */
		function beforeDelete() {
			return $this->_deletable($this->_Model->id);
		}

	/**
	 * Cleanup ownership after delete.
	 */
		function afterDelete() {
    		$this->_ownerModel->deleteAll(array(
    				'model' => $this->_Model->alias,
    				'foreign_key' => $this->_Model->id,
    				$this->settings[$this->_Model->alias]['userPrimaryKey'] => $this->_User,
    		));
		}
	}