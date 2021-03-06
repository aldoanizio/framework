<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\database\midgard\relations;

use \mako\database\Connection;
use \mako\database\midgard\ORM;

/**
 * Has many polymorphic relation.
 *
 * @author  Frederic G. Østby
 */

class HasManyPolymorphic extends \mako\database\midgard\relations\HasMany
{
	use \mako\database\midgard\relations\HasOneOrManyPolymorphicTrait {
		\mako\database\midgard\relations\HasOneOrManyPolymorphicTrait::__construct as constructor;
	}

	/**
	 * Constructor.
	 * 
	 * @access  public
	 * @param   \mako\database\Connection   $connection       Database connection
	 * @param   \mako\database\midgard\ORM  $parent           Parent model
	 * @param   \mako\database\midgard\ORM  $related          Related model
	 * @param   string                      $polymorphicType  Polymorphic type
	 */

	public function __construct(Connection $connection, ORM $parent, ORM $related, $polymorphicType)
	{
		$this->constructor($connection, $parent, $related, $polymorphicType);
	}
}