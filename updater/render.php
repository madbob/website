<?php

chdir('elements');
$files = array_diff(scandir('.'), ['.', '..']);
usort($files, function($a, $b) {
	return filemtime($b) - filemtime($a);
});

$output = '';

foreach($files as $file) {
	if (substr_compare($file, '.disabled', strlen($file) - 9, 9) === 0)
		continue;

	$data = json_decode(file_get_contents($file));
	
	$output .= sprintf('<div class="col-md-6">' . "\n");
	$output .= sprintf('	<div class="project %s" style="background-image: url(img/%s)">' . "\n", $data->type, $data->image);
	$output .= sprintf('		<div class="overlay">' . "\n");
	$output .= sprintf('			<span class="desc">' . "\n");
	$output .= sprintf('				<h2>%s</h2' . "\n", $data->name);
	$output .= sprintf('				<p>%s</p>' . "\n", $data->description);
	$output .= sprintf('				<p>' . "\n");

	foreach($data->links as $link)
		$output .= sprintf('				<a href="%s">%s</a>' . "\n", $link->url, $link->name);

	$output .= sprintf('				</p>' . "\n");
	$output .= sprintf('			</span>' . "\n");
	$output .= sprintf('		</div>' . "\n");
	$output .= sprintf('	</div>' . "\n");
	$output .= sprintf('</div>' . "\n");
}

echo $output . "\n";

