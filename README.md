# Sorting Algorithm Visualizer (PHP)

Pick a sorting algorithm and step through the real PHP code line by line, with animated bars, temporary arrays, the call stack and live variables.

## Run
```bash
cd sorting-visualizer
php -S localhost:8000
# open http://localhost:8000          (or ?algo=quick to open a specific one)
```
Requires PHP 8.0+. With XAMPP, copy the folder into `htdocs` and open `http://localhost/sorting-visualizer/`.

## Algorithms
| Group | Algorithm | id | Input range |
|---|---|---|---|
| Simple | Bubble, Cocktail shaker, Selection, Insertion | bubble, cocktail, selection, insertion | −999…999 |
| Efficient | Shell, Merge, Quick (Lomuto), Heap | shell, merge, quick, heap | −999…999 |
| Non-comparison | Counting | counting | 0…99 |
| Non-comparison | Radix (LSD, base 10) | radix | 0…999 |

All accept 2–20 numbers.

## Files
| File | Role |
|---|---|
| `src/Tracer.php` | Base class: `rec()` snapshots, `loop()` for conditions, counters, sorted set, call stack, input validation, self-check against `sort()`. |
| `src/Algorithms.php` | Registry and dropdown order. Add new classes here. |
| `src/algorithms/*.php` | One class per algorithm: `meta()`, `source()` (displayed code), `run()` (instrumented copy). |
| `trace.php` | JSON API: `trace.php?algo=quick&data=5,3,8` returns `{meta, code, input, output, steps[]}`; HTTP 422 on bad input. |
| `index.php` | Page; renders the dropdown and embeds the first trace. |
| `assets/app.js` | Replay engine (algorithm-agnostic). |
| `assets/style.css` | Light/dark, responsive. |

## Step shape
```json
{ "line": 15, "action": "compare", "note": "$arr[2] = 8 <= $pivot (7) is false",
  "arr": [5,3,8,1], "vars": {"lo":0,"hi":3,"p":null,"pivot":7,"i":0,"j":2},
  "focus": [2,3], "ptrs": {"i":0,"j":2,"pivot":3}, "range": [0,3],
  "aux": [{"label":"$left","kind":"cells","values":[3,5],"focus":[0],"ptrs":{"i":0}}],
  "stack": ["quickSort(0, 3)", "partition(0, 3)"], "sorted": [4,5],
  "stats": {"comparisons":5,"swaps":2,"writes":4} }
```
Each step is the state after the highlighted line ran.
- `action` is one of: enter, call, assign, loop, compare, swap, write, check, break, return.
- `aux.kind` is either `cells` or `buckets`.
- `vars` shows only the current stack frame's locals; a dash means the variable isn't defined in that frame.

## Adding an algorithm
1. Create `src/algorithms/FooSort.php`, extending `Tracer`.
2. Write `source()` as the clean code. In `run()`, write the same logic and call `$this->rec(<line>, ...)` after each statement. For conditions, use `$this->loop()`.
3. Add the class to `Algorithms::CLASSES`.
4. `trace()` throws if the result differs from `sort()`, so a broken instrumentation fails loudly.

## Why the code is instrumented instead of using `declare(ticks)`
Ticks were tested. They skip `for` conditions and `if` checks that come out false, so traces would be missing lines. The cost of explicit instrumentation is that `source()` and the line numbers in `run()` must be kept in sync by hand.

## Known limits
- The largest trace is quick sort on already-sorted input with 20 elements: about 1,070 steps and 560 KB of JSON. That's fine locally.
- Heap sort is shown as an array with root/left/right pointers, not as a tree diagram.

## Version log
| Version | Change |
|---|---|
| v1 | Bubble sort only: `BubbleSortTracer`, line highlighting, bars, variables, playback. |
| v2 | Added 9 more algorithms and the dropdown. Extracted the `Tracer` base class and registry. Added the info card, temporary-array and bucket panels, call stack panel, active-range dimming, multi-label pointers, a writes counter, and `?algo=` URLs. Added per-algorithm input ranges, and the dropdown reverts if the input is invalid for the chosen algorithm. |
