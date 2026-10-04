<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title??$app->title())?></title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="<?=h($app->logoImage())?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f8f9fa}.app{max-width:760px}.brand-logo{width:4.5rem;height:4.5rem;object-fit:contain}.day{aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:clamp(1.35rem,6vw,2rem);font-weight:700;border-radius:1rem;text-decoration:none;line-height:1}.day .weekday{font-size:.65em;font-weight:600;margin-top:.35rem}.day-free{background:#198754;color:#fff}.day-free:hover{background:#157347;color:#fff}.day-taken{background:#dc3545;color:#fff}.required::after{content:" *";color:#dc3545}</style></head>
<body><main class="container app py-4 py-md-5"><?=$content?></main><footer class="container app pb-4 text-center text-secondary small">
<?php $legalLinks=[]; foreach(['impressum'=>'IMPRESSUM_MD','datenschutz'=>'DATENSCHUTZ_MD'] as $path=>$key) if(isset($app) && $app->legalMarkdown($key)!==null) $legalLinks[$path]=ucfirst($path); ?>
<?php if($legalLinks): ?><?php foreach($legalLinks as $path=>$label): ?><a class="text-secondary" href="/<?=h($path)?>"><?=h($label)?></a><?= $path === array_key_last($legalLinks) ? '' : ' · ' ?><?php endforeach; ?><?php endif; ?></footer></body></html>
