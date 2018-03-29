<?php
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\model\Products;

//Rota para produtos

$app->get("/admin/products", function()
    {

    User::verifyLogin();

	  $products = Products::listAll();  	

	  $page = new PageAdmin();

	   $page->setTpl("products", [
		       'products'=>$products
		   ]);

	});

$app->get("/admin/products/create", function()
    {

    User::verifyLogin();

	  $page = new PageAdmin();

	   $page->setTpl("products-create");

	});

$app->post("/admin/products/create", function()
    {

    User::verifyLogin();

    $product = new Products();

    $product->setData($_POST);

    $product->save();

    header("Location: /admin/products");
    exit;

	});

$app->get("/admin/products/:idproduct", function($idproduct)
    {

    User::verifyLogin();

      $product = new Products();

      $product->get((int)$idproduct);

	  $page = new PageAdmin();

	  $page->setTpl("products-update", [
        'product'=>$product->getValues()
	  ]);

	});

$app->post("/admin/products/:idproduct", function($idproduct)
    {

    User::verifyLogin();

      $product = new Products();

      $product->get((int)$idproduct);

	  $product->setData($_POST);

	  $product->save();

	  $product->setFoto($_FILES["file"]);

	  header("Location: /admin/products");
	  exit;

	});

 $app->get("/admin/products/:idproduct/delete", function($idproduct){

 	User::verifyLogin();

 	$product = new Products();

 	$product->get((int)$idproduct);

 	$product->delete();
    
    header('Location: /admin/products');
   	 exit;
  });
?>