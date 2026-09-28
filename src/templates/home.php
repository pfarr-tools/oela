<div class="d-flex align-items-center gap-3 mb-4"><img class="brand-logo" src="/oela_icon.png" alt="OELA"><h1 class="h2 mb-0">Lebendiger Adventskalender</h1></div><p class="text-secondary">Bitte wählen Sie Ihren Ort:</p>
<div class="d-grid gap-3"><?php foreach($app->locations() as $slug=>$name):?><a class="btn btn-success btn-lg" href="/<?=h($slug)?>"><?=h($name)?></a><?php endforeach?></div>
