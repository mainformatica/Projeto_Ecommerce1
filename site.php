<?php

use \Hcode\PageAdmin;
use \Hcode\Page;
use \Hcode\Model\Products;
use \Hcode\model\Category;

//Rota da Home do site
$app->get('/', function() {

	$products = Products::listAll();
    
    $page = new Page();

    $page->setTpl("index", [
      'products'=> Products::checkList($products)
    ]);
});
 
 $app->get("/categories/:idcategory", function($idcategory)
  		{
            $page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;
            
            $category = new Category();

            $category->get((int)$idcategory);

            $pagination = $category->getProductsPage($page);
            
            $pages = [];

            for ($i=1; $i <= $pagination['pages']; $i++) { 
                array_push($pages, [
                     'link'=> '/categories/'.$category->getidcategory(). '?page='. $i,
                     'page'=>$i
                ]);
            }

            $page = new Page();

            $page->setTpl("category", [
            'category'=>$category->getValues(),
            'products'=>$pagination["data"],
            'pages'=>$pages
            ]);

});

?>

