<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\RestServer;

/**
 *
 * @author jg
 */
interface HandlerInterface
{

    function setOutput($output);

    function setHeader();

    function execute(ServiceAbstract $class);
}
