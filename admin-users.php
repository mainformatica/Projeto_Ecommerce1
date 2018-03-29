<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

//rota para abrir pagina usuarios
$app->get("/admin/users", function(){

    User::verifyLogin();

    $users = User::listAll();

	$page = new PageAdmin();

	$page->setTpl("users", array(
        "users"=>$users
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