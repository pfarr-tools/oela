<h1 class="h2 mb-4">Lebendiger Adventskalender</h1><p class="text-secondary">Bitte wählen Sie Ihren Ort:</p>
<div class="d-grid gap-3"><?php foreach($app->locations() as $slug=>$name):?><a class="btn btn-success btn-lg" href="/<?=h($slug)?>"><?=h($name)?></a><?php endforeach?></div>
