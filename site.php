<?php

use \Hcode\Page;
use \Hcode\Model\Products;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\User;
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

 $app->get("/products/:desurl", function($desurl){

 $products = new Products();

 $products->getFromURL($desurl);

 $page = new Page();

$page->setTpl("product-detail", [
    'products'=>$products->getValues(),
    'categories'=>$products->getcategories()
]);

 });

    $app->get("/cart", function(){

    $cart = Cart::getFromSession();

    
    $page = new Page();

    $page ->setTpl("/cart", [
     'cart'=>$cart->getValues(),
     'products'=>$cart->getProducts(),
     'error'=>Cart::getMsgErro()
    ]); 

 });

    $app->get("/cart/:idproduct/add", function($idproduct){

        $products = new Products();

        $products->get((int)$idproduct);

        $cart = Cart::getFromSession();

        $qtd= (isset($_GET['qtd'])) ? (int)$_GET['qtd'] :1;

        for ($i=0; $i < $qtd; $i++) { 

          $cart->addProduct($products);
        }     

       header("Location: /cart");
        exit;
    });

    $app->get("/cart/:idproduct/minus", function($idproduct){

        $products = new Products();

        $products->get((int)$idproduct);

        $cart = Cart::getFromSession();

        $cart->removeProduct($products);

        header("Location: /cart");
        exit;
    });

    $app->get("/cart/:idproduct/remove", function($idproduct){

        $products = new Products();

        $products->get((int)$idproduct);

        $cart = Cart::getFromSession();

        $cart->removeProduct($products, true);

        header("Location: /cart");
        exit;
    });

    $app->post("/cart/freight", function(){

        $cart = Cart::getFromSession();

        $cart->setFreight($_POST['zipcode']);

        header("Location: /cart");
         exit;
    });
?>

