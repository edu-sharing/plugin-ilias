<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */
 
require_once './Services/Exceptions/classes/class.ilException.php'; 
 
/** 
 * Class for advanced editing exception handling in ILIAS. 
 * 
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$ 
 * 
 */
class ilLfEduSharingResourceException extends ilException
{
        /** 
         * Constructor
         * 
         * A message is not optional as in build in class Exception
         * 
         * @param        string $a_message message
         */
        public function __construct($a_message)
        {
                 parent::__construct($a_message);
        }
}
?>
