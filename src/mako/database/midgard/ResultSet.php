<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\database\midgard;

use \mako\utility\Arr;

/**
 * ORM result set.
 *
 * @author  Frederic G. Østby
 */

class ResultSet extends \mako\utility\Collection
{
	/**
	 * Clones all items when cloning the collection.
	 * 
	 * @access  public
	 */

	public function __clone()
	{
		foreach($this->items as $key => $value)
		{
			$this->items[$key] = clone $value;
		}
	}

	/**
	 * Returns an array containing only the values of chosen column.
	 * 
	 * @access  public
	 * @param   string  $column  Column name
	 * @return  array
	 */

	public function pluck($column)
	{
		return Arr::pluck($this->items, $column);
	}

	/**
	 * Returns an array representation of the result set.
	 * 
	 * @access  public
	 * @param   boolean  $protect  (optional) Protect columns?
	 * @param   boolean  $raw      (optional) Get raw values?
	 * @return  array
	 */

	public function toArray($protect = true, $raw = false)
	{
		$results = [];

		foreach($this->items as $item)
		{
			$results[] = $item->toArray($protect, $raw);
		}

		return $results;
	}

	/**
	 * Returns a json representation of the result set.
	 * 
	 * @access  public
	 * @param   boolean  $protect  (optional) Protect columns?
	 * @param   boolean  $raw      (optional) Get raw values?
	 * @return  string
	 */

	public function toJson($protect = true, $raw = false)
	{
		return json_encode($this->toArray($protect, $raw));
	}

	/**
	 * Returns a json representation of the result set.
	 * 
	 * @access  public
	 * @return  string
	 */

	public function __toString()
	{
		return $this->toJson();
	}
}