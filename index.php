<?php
declare(strict_types=1);

require __DIR__ . '/src/Algorithms.php';

$metas = Algorithms::metas();
$algo  = isset($_GET['algo'], $metas[$_GET['algo']]) ? (string) $_GET['algo'] : 'bubble';
$class = Algorithms::get($algo);

$defaultInput = '5, 3, 8, 1, 9, 2, 7'; // valid for every algorithm
$initial = (new $class())->trace($class::parseInput($defaultInput));

$groups = [];
foreach ($metas as $id => $m) {
    $groups[$m['group']][$id] = $m['name'];
}
$h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sorting algorithms, line by line</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <h1>Sorting algorithms, line by line</h1>
  <form id="input-form" class="input-row" novalidate>
    <label for="algo">Algorithm</label>
    <select id="algo" name="algo">
      <?php foreach ($groups as $group => $items): ?>
      <optgroup label="<?= $h($group) ?>">
        <?php foreach ($items as $id => $name): ?>
        <option value="<?= $h($id) ?>"<?= $id === $algo ? ' selected' : '' ?>><?= $h($name) ?></option>
        <?php endforeach; ?>
      </optgroup>
      <?php endforeach; ?>
    </select>
    <label for="data">Array</label>
    <input id="data" name="data" type="text" inputmode="numeric" autocomplete="off"
           value="<?= $h($defaultInput) ?>" aria-describedby="input-hint input-error">
    <button type="submit" class="btn primary">Run in PHP</button>
    <button type="button" id="random" class="btn">Random</button>
    <p id="input-hint" class="hint"></p>
    <p id="input-error" class="error" role="alert"></p>
  </form>
</header>

<main class="layout">
  <div class="col-code">
    <section class="panel code-panel" aria-label="PHP source">
      <div class="panel-head">
        <h2 id="code-title"></h2>
        <span class="legend">Gutter: steps recorded on each line</span>
      </div>
      <ol id="code" class="code"></ol>
    </section>

    <section id="stack-panel" class="panel stack-panel" aria-label="Call stack">
      <div class="panel-head"><h2>Call stack</h2><span class="legend">Newest call on top</span></div>
      <ol id="stack" class="stack"></ol>
    </section>
  </div>

  <section class="panel stage-panel" aria-label="Visualization">
    <div id="info" class="info"></div>

    <div id="bars" class="bars" aria-hidden="true"></div>
    <div id="aux" class="aux"></div>
    <p id="note" class="note" aria-live="polite"></p>

    <div class="controls" role="group" aria-label="Playback">
      <button class="btn" id="first" title="First step (Home)" aria-label="First step">⏮</button>
      <button class="btn" id="prev" title="Previous step (←)">Back</button>
      <button class="btn primary play" id="play" title="Play / pause (Space)">Play</button>
      <button class="btn" id="next" title="Next step (→)">Step</button>
      <button class="btn" id="last" title="Last step (End)" aria-label="Last step">⏭</button>
      <label class="speed">Speed <input id="speed" type="range" min="1" max="10" value="5"></label>
    </div>
    <div class="scrub">
      <input id="scrubber" type="range" min="0" value="0" aria-label="Step">
      <span id="counter" class="counter"></span>
    </div>

    <dl id="vars" class="vars"></dl>

    <ul class="key">
      <li><i class="sw compare"></i>Comparing</li>
      <li><i class="sw swap"></i>Swapping or writing</li>
      <li><i class="sw sorted"></i>In final position</li>
      <li><i class="sw out"></i>Outside active range</li>
    </ul>
  </section>
</main>

<script>
window.ALGOS = <?= json_encode($metas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
window.INITIAL_TRACE = <?= json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/app.js"></script>
</body>
</html>
