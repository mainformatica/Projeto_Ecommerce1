<?php

use \Hcode\PageAdmin;
use \Hcode\Page;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Products;

//rota para categorias
$app->get("/admin/categories", function(){

    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : "";

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] :1;

    if ($search != '') {
       
       $paginacao = Category::getPageSearch($search, $page);

     }else {

     $paginacao = Category::getPage($page);

    }

    $pages = [];

    for ($x = 0; $x < $paginacao['pages']; $x++)
    {

        array_push($pages, [
            'href'=>'/admin/categories'. http_build_query([
                 'page'=>$x + 1,
                 'search'=>$search
            ]),
            'text'=>$x + 1
        ]);
    }


  	$page = new PageAdmin();

  	 $page->setTpl("categories", [
       "categories"=>$paginacao['data'],
        "search"=>$search,
        "pages"=>$pages
  	  ]);

  });
//Rota para Criar categorias
  $app->get("/admin/categories/create", function(){

     User::verifyLogin();

     $page = new PageAdmin();

  	 $page->setTpl("categories-create");

  });

 $app->post("/admin/categories/create", function(){

     User::verifyLogin();

     $category = new Category();

  	 $category->setData($_POST);

  	 $category->save();

  	 header('Location: /admin/categories');
  	 exit;

  });

 $app->get("/admin/categories/:idcategory/delete", function($idcategory){

 	User::verifyLogin();

 	$category = new Category();

 	$category->get((int)$idcategory);

 	$category->delete();
    
    header('Location: /admin/categories');
  	 exit;

 });
 
  $app->get("/admin/categories/:idcategory", function($idcategory)
  {

     User::verifyLogin();

     $category = new Category();

 	 $category->get((int)$idcategory); 
     
 	 $page = new PageAdmin();

  	 $page->setTpl("categories-update", [
        'category'=>$category->getValues()
  	 ]);

  	  });

  	$app->post("/admin/categories/:idcategory", function($idcategory)
  {

     User::verifyLogin();

     $category = new Category();

 	 $category->get((int)$idcategory); 
     
 	 $category->setData($_POST);

 	 $category->save();

  	 header('Location: /admin/categories');
  	 exit;

  	  }); 
    
     $app->get("/admin/categories/:idcategory/products", function($idcategory)
      {
            User::verifyLogin();

            $category = new Category();

            $category->get((int)$idcategory);
            
            $page = new PageAdmin();

            $page->setTpl("categories-products", [
            'category'=>$category->getValues(),
            'productsRelated'=> $category->getProducts(),
            'productsNotRelated'=> $category->getProducts(false),
            'products'=>[]
            ]);

});

     $app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct)
      {
            User::verifyLogin();

            $category = new Category();

            $category->get((int)$idcategory);
            
            $products = new Products();

            $products->get((Int)$idproduct);

            $category->addProduct($products);

            header("Location: /admin/categories/".$idcategory."/products");
            exit;

            });

     $app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct)
      {
            User::verifyLogin();

            $category = new Category();

            $category->get((int)$idcategory);
            
            $products = new Products();

            $products->get((Int)$idproduct);

            $category->removeProduct($products);

            header("Location: /admin/categories/".$idcategory."/products");
            exit;


});



?>