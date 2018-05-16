<?php

use \Hcode\Page;
use \Hcode\Model\Products;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\User;
use \Hcode\Model\Address;

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

    $app->get("/checkout", function(){

        User::verifyLogin(false);

        $address = new Address();

        $cart = Cart::getFromSession();

        if (isset($_GET['zipcode'])) {

          $_GET['zipcode'] = $cart->getdeszipcode();
        }

        if (isset($_GET['zipcode'])) {
          
             $address->loadFromCep($_GET['zipcode']);

             $cart->setdeszipcode($_GET['zipcode']);

             $cart->save();

             $cart->getCalculeteTotal();
           }
 if (!$address->getdesaddress()) $address->setdesaddress('');
 if (!$address->getdesnumber()) $address->setdesnumber('');
 if (!$address->getdescomplement()) $address->setdescomplement('');
 if (!$address->getdesdistrict()) $address->setdesdistrict('');
 if (!$address->getdescity()) $address->setdescity('');
 if (!$address->getdesstate()) $address->setdesstate('');
 if (!$address->getdescountry()) $address->setdescountry('');
 if (!$address->getdeszipcode()) $address->setdeszipcode('');
           
        $page = new Page();

        $page->setTpl("checkout", [
             
             'cart'=>$cart->getValues(),
             'address'=>$address->getValues(),
             'products'=>$cart->getProducts(),
             'error'=>Address::getMsgErro()
        ]);   
  });

 $app->post("/checkout", function(){

          User::verifyLogin(false);

          if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
            
            Address::setMsgErro("Informe seu Cep.");
            header("Location: /checkout");
            exit;
          }

          if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
            
            Address::setMsgErro("Informe seu Endereço.");
            header("Location: /checkout");
            exit;
          }

          if (!isset($_POST['desnumber']) || $_POST['desnumber'] === '') {
            
            Address::setMsgErro("Informe o numero do seu endereço.");
            header("Location: /checkout");
            exit;
          }

          if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
            
            Address::setMsgErro("Informe seu Bairro");
            header("Location: /checkout");
            exit;
          }

          if (!isset($_POST['descity']) || $_POST['descity'] === '') {
            
            Address::setMsgErro("Informe sua Cidade.");
            header("Location: /checkout");
            exit;
          }

          if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
            
            Address::setMsgErro("Informe seu Estado.");
            header("Location: /checkout");
            exit;
          }

          $user = User::getFromSession();
         
         $address = new Address();

         $_POST['deszipcode'] = $_POST['zipcode'];
         $_POST['idperson'] = $user->getidperson();

         $address->setData($_POST);

         $address->save();

         header("Location: /order");
         exit;

    });

    $app->get("/login", function(){

        $page = new Page();

        $page->setTpl("login", [
           'error'=>User::getError(),
           'errorRegister'=>User::getRegisterError(),
           'valordoRegistro'=>(isset($_SESSION['valordoRegistro'])) ? $_SESSION['valordoRegistro'] : ['name'=> '', 'email'=> '', 'phone'=>'']

        ]);

  });

    $app->post("/login", function(){

      try {
              User::login($_POST['login'], $_POST['password']);
                
            } catch (Exception $e) {

              User::setError($e->getMessage());
                
            }      

       header("Location: /checkout");

        exit;

  });

  $app->get("/logout", function(){

        User::logout();

        header("Location: /login");
        exit;
  });

  $app->post("/register", function(){

    $_SESSION['valordoRegistro'] = $_POST;

    if(!isset($_POST['name']) || $_POST['name'] == ''){

      User::setRegisterError("Preencha o seu nome!");
      header("Location: /login");
      exit;
    }

    if(!isset($_POST['email']) || $_POST['email'] == ''){

      User::setRegisterError("Preencha o seu e-mail!");
      header("Location: /login");
      exit;
    }

    if(!isset($_POST['phone']) || $_POST['phone'] == ''){

      User::setRegisterError("Preencha o seu telefone!");
      header("Location: /login");
      exit;
    }

    if(!isset($_POST['password']) || $_POST['password'] == ''){

      User::setRegisterError("Preencha sua senha!");
      header("Location: /login");
      exit;
    }

    if (User::checkLoginExiste($_POST['email']) === true) {
      
      User::setRegisterError("Este email esta sendo usuado por outro usuario!");
      header("Location: /login");
      exit;
    }

    $user = new User();

    $user->setData([
      'inadmin'=> 0,
      'deslogin'=>$_POST['email'],
      'desperson'=>$_POST['name'],
      'desemail'=>$_POST['email'],
      'despassword'=>$_POST['password'],
      'nrphone'=>(int)$_POST['phone']
     
    ]);

     $user->save();

      User::login($_POST['email'], $_POST['password']);
      header("Location: /checkout");
      exit;
  });

  //Rota para recuperar senha do usuario
$app->get("/forgot", function(){
    
    $page = new Page();

  $page->setTpl("forgot");

});

$app->post("/forgot", function(){

  $user = User::getForgot($_POST["email"], false);

  header("Location: /forgot/sent");
  exit;
});

$app->get("/forgot/sent", function(){

      $page = new Page();

  $page->setTpl("forgot-sent");

});

$app->get("/forgot/reset", function(){

      $user = User::validForgotDecrypt($_GET["code"]);
      $page = new Page();

  $page->setTpl("forgot-reset", array(
      "name"=>$user["desperson"],
      "code"=>$_GET["code"]
  ));

});

$app->post("/forgot/reset", function(){

      $forgot = User::validForgotDecrypt($_POST["code"]);

      User::setForgotUsed($forgot["idrecovery"]);

      $user = new User();

      $user->get((int)$forgot["iduser"]);

      $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
       "cost"=>16
      ]);

      $user->setPassword($password);

      $page = new Page();

  $page->setTpl("forgot-reset-success");

  });

$app->get("/profile", function(){

      User::verifyLogin(false);

      $user = User::getFromSession();

      $page = new Page();

      $page->setTpl("profile", [

      'user'=>$user->getValues(),
      'profileMsg'=> User::getSucess(),
      'profileError'=> User::getError()

     ]);
  });

$app->post("/profile", function(){

      User::verifyLogin(false);

      if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
        User::setError("Preencha o seu nome.");
        header('Location: /profile');
        exit;
      }

      if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
        User::setError("Preencha o seu e-mail.");
        header('Location: /profile');
        exit;
      }

      $user = User::getFromSession();

      if ($_POST['desemail'] !== $user->getdesemail()) {

        if (User::checkLoginExiste($_POST['desemail']) ==true) {

          User::setError("Esse email já esta sendo utilizado por outro usuário");

          header('Location: /profile');
          exit;

        }
        
      }

      $_POST['inadmin'] = $user->getinadmin(); // previne comand injection 
      $_POST['despassword'] = $user->getdespassword();
      $_POST['deslogin'] = $_POST['desemail'];

      $user->setData($_POST);

      $user->save();

      User::setSucess("Dados Alterados com sucesso!");

      header('Location: /profile');
      exit;

});

?>