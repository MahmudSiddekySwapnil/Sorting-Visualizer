<?php
declare(strict_types=1);

/**
 * Base class for algorithm tracers.
 *
 * Each subclass provides:
 *  - source(): the PHP code shown to the user
 *  - run():    the same algorithm, with rec() calls after each executed line.
 *              Line numbers passed to rec() map 1:1 to source().
 *  - VARS:     local variable names shown in the variables panel
 *
 * rec() receives get_defined_vars(), so the variables panel shows the real
 * locals of the current stack frame.
 */
abstract class Tracer
{
    public const MAX_SIZE = 20;

    /** @var list<string> */
    protected const VARS = [];

    /** @var list<array<string,mixed>> */
    private array $steps = [];
    /** @var array<int,bool> */
    private array $sorted = [];
    /** @var list<string> */
    private array $stack = [];

    protected int $comparisons = 0;
    protected int $swaps = 0;
    protected int $writes = 0;

    /** @return array<string,mixed> */
    abstract public static function meta(): array;

    abstract public static function source(): string;

    /** @param list<int> $arr @return list<int> */
    abstract protected function run(array $arr): array;

    /** @param list<int> $input */
    public function trace(array $input): array
    {
        $this->steps = $this->sorted = $this->stack = [];
        $this->comparisons = $this->swaps = $this->writes = 0;

        $input  = array_values($input);
        $output = array_values($this->run($input));

        // Self-check: the traced run must agree with PHP's own sort().
        $expected = $input;
        sort($expected);
        if ($output !== $expected) {
            throw new LogicException(static::meta()['name'] . ': traced result does not match sort().');
        }

        return [
            'meta'   => static::meta(),
            'code'   => explode("\n", static::source()),
            'input'  => $input,
            'output' => $output,
            'steps'  => $this->steps,
        ];
    }

    /**
     * Record a snapshot after a line has executed.
     *
     * @param array<string,mixed> $scope get_defined_vars() of the caller; must contain 'arr'
     * @param array{focus?:list<int>, ptrs?:array<string,int>, range?:array{int,int}|null, aux?:list<array<string,mixed>>} $opt
     */
    protected function rec(int $line, string $action, string $note, array $scope, array $opt = []): void
    {
        $vars = [];
        foreach (static::VARS as $name) {
            $v = $scope[$name] ?? null;
            $vars[$name] = (is_scalar($v) || $v === null) ? $v : null;
        }
        $sorted = array_keys($this->sorted);
        sort($sorted);

        $this->steps[] = [
            'line'   => $line,
            'action' => $action,
            'note'   => $note,
            'arr'    => array_values($scope['arr']),
            'vars'   => $vars,
            'focus'  => array_values($opt['focus'] ?? []),
            'ptrs'   => (object) ($opt['ptrs'] ?? []),
            'range'  => $opt['range'] ?? null,
            'aux'    => $opt['aux'] ?? [],
            'stack'  => $this->stack,
            'sorted' => $sorted,
            'stats'  => ['comparisons' => $this->comparisons, 'swaps' => $this->swaps, 'writes' => $this->writes],
        ];
    }

    /** Record a loop/condition check and return its result, so it can sit inside for()/while(). */
    protected function loop(int $line, bool $ok, string $cond, array $scope, array $opt = []): bool
    {
        $this->rec($line, 'loop', $cond . ' is ' . ($ok ? 'true' : 'false, exit loop'), $scope, $opt);
        return $ok;
    }

    protected function gt(int $a, int $b): bool { $this->comparisons++; return $a > $b; }
    protected function lt(int $a, int $b): bool { $this->comparisons++; return $a < $b; }
    protected function le(int $a, int $b): bool { $this->comparisons++; return $a <= $b; }

    protected function markSorted(int ...$indices): void
    {
        foreach ($indices as $i) {
            $this->sorted[$i] = true;
        }
    }

    protected function markAll(int $n): void
    {
        for ($k = 0; $k < $n; $k++) {
            $this->sorted[$k] = true;
        }
    }

    protected function push(string $frame): void { $this->stack[] = $frame; }
    protected function pop(): void { array_pop($this->stack); }
    protected function depth(): int { return count($this->stack); }

    protected static function b(bool $v): string { return $v ? 'true' : 'false'; }
    protected static function list(array $a): string { return '[' . implode(', ', $a) . ']'; }

    /**
     * Parse "5, 3, -2 8" into a list of ints within the algorithm's allowed range.
     * @return list<int>
     * @throws InvalidArgumentException with a user-facing message
     */
    public static function parseInput(string $raw): array
    {
        $meta = static::meta();
        $min = $meta['min'];
        $max = $meta['max'];

        $parts = preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            throw new InvalidArgumentException('Enter at least 2 numbers, separated by commas or spaces.');
        }
        if (count($parts) > self::MAX_SIZE) {
            throw new InvalidArgumentException('Enter at most ' . self::MAX_SIZE . ' numbers.');
        }
        $out = [];
        foreach ($parts as $p) {
            if (!preg_match('/^-?\d{1,4}$/', $p)) {
                throw new InvalidArgumentException("\"$p\" is not a whole number in range.");
            }
            $v = (int) $p;
            if ($v < $min || $v > $max) {
                throw new InvalidArgumentException("{$meta['name']} accepts numbers from $min to $max. \"$p\" is out of range.");
            }
            $out[] = $v;
        }
        return $out;
    }
}
