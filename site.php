<?php

use \Hcode\PageAdmin;
use \Hcode\Page;

//Rota da Home do site
$app->get('/', function() {
    
    $page = new Page();

    $page->setTpl("index");
});






?>

