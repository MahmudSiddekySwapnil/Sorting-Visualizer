# Progress — Sorting Visualizer

## Status: v2 complete (phases 1 + 2 done in one session)

### Done
- Phase 1: `Tracer` base class and registry; dropdown, info card and `?algo=` URLs; Bubble, Cocktail, Selection, Insertion and Shell.
- Phase 2: call stack panel, aux cells and bucket panels, range dimming; Merge, Quick (Lomuto), Heap, Counting (0–99) and Radix LSD (0–999).

### QA results (PHP 8.3, Chromium)
- All 10 algorithms match `sort()` on these inputs:
  - the default array;
  - an already-sorted array;
  - a reversed array;
  - duplicates;
  - two elements;
  - negatives, where the algorithm allows them;
  - 20-element worst cases.
- Every step's line number was checked by hand against its source line.
- The browser test scrubbed through every step of every algorithm with no JS errors.
- Out-of-range input returns 422, and the dropdown reverts to the previous algorithm.
- Checked on desktop in light mode and mobile (390px) in dark mode.

### Worst-case step counts at n = 20
| Algorithm | Steps |
|---|---|
| bubble | 1220 |
| quick | 1068 |
| heap | 1011 |
| cocktail | 846 |
| insertion | 669 |
| merge | 589 |
| selection | 570 |
| shell | 430 |
| counting | 301 |
| radix | 196 |

### Decisions
- Hand-instrumented tracing, because `declare(ticks)` misses condition lines.
- Counting sort is limited to 0–99 so the count array stays drawable.
- Radix sort is limited to 0–999, giving 3 passes at most.
- Counting sort uses the simple rebuild version, so its stability is shown as "n/a".
- The variables panel shows only the current stack frame's locals, which is true to PHP scoping.

### Possible next steps (not started)
- A tree view for heap sort.
- A side-by-side race between two algorithms.
- Alternative quick sort pivot choices (Hoare partitioning, median-of-three).
- More gap sequences for Shell sort (Knuth, Ciura).
- Preset inputs: nearly sorted, few unique values, reversed.
