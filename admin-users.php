<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

//rota para abrir pagina usuarios
$app->get("/admin/users/:iduser/password", function($iduser){
    
     User::verifyLogin();

     $user = new User();

     $user->get((int)$iduser);

     $page = new PageAdmin();

     $page->setTpl("users-password", [
        
        "user"=>$user->getValues(),
        "msgError"=>User::getError(),
        "msgSuccess"=>User::getSucess()

     ]);

});

$app->post("/admin/users/:iduser/password", function($iduser){
User::verifyLogin();

     $user = new User();

     if (!isset($_POST['despassword']) || $_POST['despassword'] === '') {
        User::setError("Digite a nova senha");
        header("Location: /admin/users/$iduser/password");
        exit;
     }

     if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === '') {
        User::setError("Confirme a nova senha");
        header("Location: /admin/users/$iduser/password");
        exit;
     }

     if ($_POST['despassword'] !== $_POST['despassword-confirm']) {
         User::setError("As senhas não são iguais");
        header("Location: /admin/users/$iduser/password");
        exit;
     }

     $user = new User();

     $user->get((int)$iduser);

     $user->setPassword(User::getPasswordHash($_POST['despassword']));

     User::setSucess("Senha alterada com sucesso!");
        header("Location: /admin/users/$iduser/password");
        exit;

     
 });

$app->get("/admin/users", function(){

    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : "";

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] :1;

    if ($search != '') {
       
       $paginacao = User::getPageSearch($search, $page);

     }else {

     $paginacao = User::getPage($page);

    }

    $pages = [];

    for ($x = 0; $x < $paginacao['pages']; $x++)
    {

        array_push($pages, [
            'href'=>'/admin/users?'. http_build_query([
                 'page'=>$x + 1,
                 'search'=>$search
            ]),
            'text'=>$x + 1
        ]);
    }

	$page = new PageAdmin();

	$page->setTpl("users", array(
        "users"=>$paginacao['data'],
        "search"=>$search,
        "pages"=>$pages
	));

});
//rota para criar usuario
$app->get("/admin/users/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");

});
//apaga usuario
$app->get("/admin/users/:iduser/delete", function($iduser){

    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->delete();

    header("Location: /admin/users");
    exit;

    });
//rota para alterar usuario
$app->get("/admin/users/:iduser", function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
"user"=>$user->getValues()
	));

});
//altera usuario
$app->post("/admin/users/create", function(){

    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

    $user->setData($_POST);

    $user->save();
    
    header("Location: /admin/users");
    exit;

});
//cria usuario
$app->post("/admin/users/:iduser", function($iduser){

    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit;

    });


?>