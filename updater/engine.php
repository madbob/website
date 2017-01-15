<?php

require_once('vendor/autoload.php');

function match_action($source, $time, &$cache) {
	$node = null;

	foreach($cache as $c) {
		if($c->type == $source->type && $c->identifier == $source->identifier) {
			$node = $c;
			break;
		}
	}

	if($node == null) {
		$node = (object)[
			'type' => $source->type,
			'identifier' => $source->identifier,
			'latest' => $time
		];
		
		$cache[] = $node;
	}
	else {
		$node->latest = $time;
	}
	
	return (object)[
		'time' => $time,
		'action' => $source->action
	];
}

if(file_exists('sources.json') == false) {
	file_put_contents('sources.json', '[]');
	exit();
}

if(file_exists('cache.json') == false)
	file_put_contents('cache.json', '[]');

$sources = json_decode(file_get_contents('sources.json'));
$cache = json_decode(file_get_contents('cache.json'));
$actions = [];

foreach($sources as $source) {
	echo "$source->identifier\n";

	switch($source->type) {
		case 'bitbucket':
			list($username, $password) = explode(';', $source->auth);
			list($account, $repository) = explode('/', $source->identifier);

			$commits = new \Bitbucket\API\Repositories\Commits();
			$commits->setCredentials(new \Bitbucket\API\Authentication\Basic($username, $password));
			$data = $commits->all($account, $repository);
			$data = json_decode($data->getContent());

			$latest = $data->values[0]->date;
			$time = strtotime($latest);
			break;

		case 'github':
			list($account, $repository) = explode('/', $source->identifier);
			if(strpos($repository, ':') !== false)
				list($repository, $branch) = explode(':', $repository);
			else
				$branch = 'master';

			$client = new \Github\Client();
			$commits = $client->api('repo')->commits()->all($account, $repository, array('sha' => $branch));

			$latest = $commits[0]['commit']['committer']['date'];
			$time = strtotime($latest);
			break;
	}

	$a = match_action($source, $time, $cache);
	$actions[] = $a;
}

usort($actions, function($a, $b) {
	return $a->time - $b->time;
});

$to_disable = [];

foreach($actions as $action) {
	list($do, $file) = explode(':', $action->action);
	$path = sprintf('elements/' . $file);

	if($action->time > (time() - 60 * 60 * 24 * 365)) {
		if(file_exists($path) == false)
			if(file_exists($path . '.disabled'))
				rename($path . '.disabled', $path);

		$to_disable[$path] = false;

		switch($do) {
			case 'touch':
				touch($path);
				sleep(1);
				break;
		}
	}
	else {
		if(isset($to_disable[$path]) == false)
			$to_disable[$path] = true;
	}
}

foreach($to_disable as $path => $status)
	if($status == true)
		rename($path, $path . '.disabled');

file_put_contents('cache.json', json_encode($cache));

