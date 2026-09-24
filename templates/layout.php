<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title??'Lebendiger Adventskalender')?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f8f9fa}.app{max-width:760px}.day{aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:clamp(1.35rem,6vw,2rem);font-weight:700;border-radius:1rem;text-decoration:none}.day-free{background:#198754;color:#fff}.day-free:hover{background:#157347;color:#fff}.day-taken{background:#dc3545;color:#fff}.required::after{content:" *";color:#dc3545}</style></head>
<body><main class="container app py-4 py-md-5"><?=$content?></main></body></html>
