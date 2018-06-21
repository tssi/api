<?php
	//API Routing
	$master_routes = Configure::read('Api.MASTER_ROUTES');
	$master_routes = explode('|',$master_routes);
	Router::parseExtensions('json');
	Router::connect(
			"/login",
			array("plugin"=>"api","controller"=>'users',"action" => "login")
		);
	Router::connect(
		"/users/login",
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
		
	foreach($master_routes as $route):
	
		$controller = 'master_'.$route;
		if($route=='system_defaults')
			$controller =  'master_configs';
		else if($route=='educ_levels')
			$controller =  'departments';
		Router::connect(
			"/".$route."/add",
			array("controller"=>$controller,"action" => "add")
		);
		Router::connect(
			"/".$route."/view/:id",
			array("controller"=>$controller,"action" => "view"),
			array("pass"=>array("id"))
		);
		Router::connect(
			"/".$route."/edit/:id",
			array("controller"=>$controller,"action" => "edit"),
			array("pass"=>array("id"))
		);
		Router::connect(
			"/".$route."/delete/:id",
			array("controller"=>$controller,"action" => "delete"),
			array("pass"=>array("id"))
		);
		
		
		Router::connect(
			"/master_".$route."/add",
			array("controller"=>$controller,"action" => "add")
		);
		Router::connect(
			"/master_".$route."/view/:id",
			array("controller"=>$controller,"action" => "view"),
			array("pass"=>array("id"))
		);
		Router::connect(
			"/master_".$route."/edit/:id",
			array("controller"=>$controller,"action" => "edit"),
			array("pass"=>array("id"))
		);
		Router::connect(
			"/master_".$route."/delete/:id",
			array("controller"=>'master_'.$route,"action" => "delete"),
			array("pass"=>array("id"))
		);
		Router::connect(
			"/master_".$route,
			array("controller"=>$controller,"action" => "index")
		);
		
		Router::connect(
			"/".$route,
			array("controller"=>$controller,"action" => "index","[method]" => "GET")
		);
		
		Router::connect(
			"/".$route,
			array("controller"=>$controller,"action" => "add","[method]" => "POST")
		);
		Router::connect(
			"/".$route,
			array("controller"=>$controller,"action"=>"edit", "[method]" => "PUT"),array('routeClass' => 'SlugRoute')
		);
		Router::connect(
			"/".$route,
			array("controller"=>$controller,"action"=>"delete", "[method]" => "DELETE"),array('routeClass' => 'SlugRoute')
		);
	endforeach;
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
			array("action"=>"delete", "[method]" => "DELETE"),array('routeClass' => 'SlugRoute')
		);
	Router::connect(
			"/:controller",
			array("action"=>"edit", "[method]" => "PUT"),array('routeClass' => 'SlugRoute')
		);