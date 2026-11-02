<?php
namespace Mmi\TestKit;

class TestCase
{
	/** @var string */
	public $suite;

	/** @var bool */
	public $verbose;

	/** @var int */
	public $oks = 0;

	/** @var int */
	public $fails = 0;

	/** @var array<int, array{label: string, got: mixed, want: mixed}> */
	public $failures = array();

	/** @var array<int, array{label: string, message: string}> */
	public $warnings = array();

	/** @var array<int, string> */
	public $steps = array();

	/** @var array<int, array{label: string, ok: bool, got: mixed, want: mixed}> */
	public $checks = array();

	public function __construct($suite, $verbose = false)
	{
		$this->suite = $suite;
		$this->verbose = $verbose;
	}

	public function etape($label)
	{
		$this->steps[] = $label;
		if ($this->verbose) {
			echo "\n== ".$label." ==\n";
		}
	}

	public function check($label, $got, $want)
	{
		$ok = ($got === $want);
		$this->checks[] = array('label' => $label, 'ok' => $ok, 'got' => $got, 'want' => $want);
		if ($ok) {
			$this->oks++;
		} else {
			$this->fails++;
			$this->failures[] = array('label' => $label, 'got' => $got, 'want' => $want);
		}
		if (!$ok || $this->verbose) {
			printf("%s %-70s got=%s want=%s\n", $ok ? '  ok  ' : 'FAIL  ', $label, var_export($got, true), var_export($want, true));
		}
	}

	public function checkClose($label, $got, $want, $epsilon = 0.011)
	{
		$this->check($label, abs((float) $got - (float) $want) < $epsilon, true);
	}

	public function ok($label, $condition)
	{
		$this->check($label, (bool) $condition, true);
	}

	public function warn($label, $message)
	{
		$this->warnings[] = array('label' => $label, 'message' => $message);
		echo "WARN  ".$label." : ".$message."\n";
	}

	public function fail($label, $message)
	{
		$this->fails++;
		$this->failures[] = array('label' => $label, 'got' => $message, 'want' => 'ok');
		$this->checks[] = array('label' => $label, 'ok' => false, 'got' => $message, 'want' => 'ok');
		echo "FAIL  ".$label." : ".$message."\n";
	}

	public function summary()
	{
		return array(
			'suite' => $this->suite,
			'ok' => $this->fails === 0,
			'checks' => $this->checks,
			'checks_count' => $this->oks + $this->fails,
			'passed' => $this->oks,
			'failed' => $this->fails,
			'failures' => $this->failures,
			'warnings' => $this->warnings,
			'steps' => $this->steps,
		);
	}
}
