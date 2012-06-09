<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter MY_Model class
 *
 * Basic data access functionality for CodeIgniter projects
 *
 * @package 		CodeIgniter
 * @subpackage		Core
 * @category 		Core
 * @author 			Udi Mosayev
 * @license 		MIT License
 * @link			http://udiudi.com
 */

class MY_Model extends CI_Model {

	private $_table;
	private $_index; // usually 'id'

	public function __construct()
	{
		parent::__construct();

		$this->_table = "";
		$this->_index = "id"; // default value
	}


	// getters/setters for private members
	protected function set_table($t) { $this->_table = $t; }
	protected function get_table() 	 { return $this->_table; }
	protected function set_index($i) { $this->_index = $i; }
	protected function get_index() 	 { return $this->_index; }


	/**
	* Inserts data to the table. Shocking.
	* @param mixed $data holds fields->value assoc array.
	* @return int insert_id mysql_insert_id()
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function insert($data)
	{
		if(!is_array($data))
		{
			log_message('error', 'Got non-array paramter for $data in insert method.');
			return false;
		}

		$data['updated_at'] = now_datetime();
		$data['created_at'] = now_datetime();

		$this->db->insert($this->_table, $data);

		$id = $this->db->insert_id();

		return $id;
	}


	/**
	* Update method
	* @param mixed $data field->value assoc array for the structure of the table
	* @param mixed $where kinda obvious. If it's null, I should have the index field.
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function update($data, $where = null)
	{
		if(!is_array($data))
		{
			log_message('error', 'Got non-array paramter for $data in update() method');
			return false;
		}

		if(!is_array($where))
		{
			log_message('error', 'Got non-array paramter for $where in update() method');
			return false;
		}

		// if index field exists, I remove it, and in case no $where array - I use the index field.
		if(isset($data[$this->_index]))
		{
			if(is_null($where))
			{
				$where = array($this->_index => $data[$this->_index]);
			}
			unset($data[$this->_index]); // to make sure no problems with AI key on update.
		}


		if(!is_null($where))
		{
			$data['updated_at'] = now_datetime();
			$this->db->where($where);
			$this->db->update($this->_table, $data);
		}
		else
		{
			log_message('error', 'In update() method got to a point where no $where array exists. LO TOV!');
			return false;
		}
	}


	/**
	* This peace of beauty is a combo of insert and update. In most of the models
	* In most objects, I would like to use only this method and not worry about "edit mode" or "new row mode".
	* @param mixed $data assoc array of field->value
	* @param mixed $where null in case of an update, where the index key included in $data array.
	* @return int id insert_id or existing row id.
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function save($data, $where = null)
	{
		if(!is_array($data))
		{
			log_message('error', 'Got non-array paramter in save() method.');
			return false;
		}

		// @TODO something here smells bad. Maybe better way to do what I think I want to to here :D
		// Basically I want to know if I need to update certain row or insert new one
		if(isset($data[$this->_index]) AND !isset($where[$this->_index]))
		{
			$index 					= $data[$this->_index];
			$where[$this->_index] 	= $index;
			$this->update($data, $where);
		}
		else
		{
			$index = $this->insert($data);
		}

		return $index;
	}


	/**
	* Gets row or rows from the database.
	* @param var $index will be in most cases
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function get($index)
	{
		if(empty($index))
		{
			log_message('error', "Got empty index in get() method.");
			return false;
		}

		return $this->get_where(array($this->_index => $index));
	}


	/**
	* Simple get_by method
	* @param string $field
	* @param string $value
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function get_by($field, $value)
	{
		$where = array($field => $value);

		return $this->get_where($where);
	}


	/**
	* Makes a logical deletion from DB. is_deleted = 1
	* @param var $index value for $_index
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function delete($index)
	{
		if(empty($index))
		{
			log_message('error', "Got emtpy index value for deleting something on delete() method");
			return false;
		}

		$where 	= array($this->_index => $index);
		$data 	= array('is_deleted' => 1);

		$this->update($data, $where);
	}


	/**
	* This method doing the actual selecting of data, used by in this class. wrappers.
	* @param mixed $where
	* @return CI_DB_Query_Result $query
	* @author Udi Mosayev <udi@sheerci.com>
	*/
	public function get_where($where)
	{
		$query = $this->db->get_where($this->_table, $where);

		if($query->num_rows() == 0)
		{
			return null;
		}
		else
		{
			return $query;
		}
	}

}
