<?php 

namespace Neko\Framework\View;

interface ViewEngineInterface {

    public function render($file, array $data = array(),$returnOnly=false);

}