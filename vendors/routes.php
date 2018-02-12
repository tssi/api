<?php
	//API Routing
	Router::parseExtensions('json');
	Router::connect(
			"/login",
			array("plugin"=>"api","controller"=>'users',"action" => "login")
		);
	Router::connect(
			"/register",
			array("plugin"=>"api","controller"=>'users',"action" => "add")
		);
	Router::connect(
			"/logout",
			array("plugin"=>"api","controller"=>'users',"action" => "logout")
		);
	Router::connect(
			"/:controller/add",
			array("action" => "add")
		);
		Router::connect(
			"/:controller/edit/:id",
			array("action" => "edit"),
			array("pass"=>array("id"))
		);
	Router::connect(
			"/:controller",
			array("action" => "index", "[method]" => "GET")
		);
	Router::connect(
			"/:controller/:id",
			array("action" => "view", "[method]" => "GET"),
			array("pass"=>array("id"))
		);
	Router::connect(
			"/:controller",
			array("action" => "add", "[method]" => "POST")
		);
	Router::connect(
			"/:controller",
			array("action"=>"delete", "[method]" => array("DELETE","PUT")),array('routeClass' => 'SlugRoute')
		);