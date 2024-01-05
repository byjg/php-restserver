<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\RestServer\Whoops;

use ReflectionClass;

/**
 * Description of WhoopsDebugInterface
 *
 * @author jg
 */
trait ClassNameBeautifier
{
    public function getClassAsTitle($ex)
    {
        $refClass = new ReflectionClass($ex);

        $title = str_replace("Exception", "",  $refClass->getShortName());
        $title = preg_replace("/([a-z0-9])([A-Z])/", "$1 $2", $title);
        return preg_replace("/([a-z])(\d)/", "$1 $2", $title);
    }

}