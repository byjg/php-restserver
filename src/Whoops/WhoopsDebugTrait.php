<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\RestServer\Whoops;

/**
 * Description of WhoopsDebugInterface
 *
 * @author jg
 */
trait WhoopsDebugTrait
{

    protected $extraTables = [];

    /**
     * Adds an entry to the list of tables displayed in the template.
     * The expected data is a simple associative array. Any nested arrays
     * will be flattened with print_r
     * @param string $label
     * @param array $data
     */
    public function addDataTable($label, array $data)
    {
        $this->extraTables[$label] = $data;
    }

    public function getDataTable()
    {
        return $this->extraTables;
    }
}
