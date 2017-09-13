<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 11/09/2017
 * Time: 20:51
 */

namespace AppBundle\Enum;


use AppBundle\Enum\Base\BaseEnum;

class NodikaStatusCode extends BaseEnum
{
    const SUCCESSFUL = 1;
    const NO_MATCHING_MEMBER = 2;
    const UNKNOWN_ERROR = 10;
}