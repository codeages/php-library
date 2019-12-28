<?php

namespace Codeages\Library;

use Codeages\Library\Util\Populate;

/**
 * @param array $items
 * @return Populate
 */
function populate(array &$items)
{
    return new Populate($items);
}
