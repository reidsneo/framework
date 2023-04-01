<?php 

namespace Neko\Framework\Dumper;

interface DumperInterface {

    public function render(\Exception $e);

}