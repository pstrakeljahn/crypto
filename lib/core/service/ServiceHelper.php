<?php

namespace PS\Core\Service;

abstract class ServiceHelper
{
    abstract function executeTick();
    abstract function define();

    private const ROW_LENGTH = 100;
    private const TICK_DURRATION_IN_SECONDS = 1;

    private ?int $startTimestamp = null;
    private ?int $currentTimestamp = null;
    private ?string $startTimestampAsString = null;
    private string $callesClass;
    private int $ticks = 0;
    private ?float $tickDuration = null;
    private bool $skipNextBorder = false;
    protected bool $skipLastBorder = false;

    private bool $run = true;

    public function __construct()
    {
        $this->define();
        $this->startTimestamp = time();
        $this->startTimestampAsString = date("Y-m-d H:i:s", $this->startTimestamp);
        $this->callesClass = get_called_class();
    }

    protected final function setTickDuration(float $duration)
    {
        $this->tickDuration = $duration;
    }

    protected final function getStartTime()
    {
        return $this->startTimestamp;
    }

    protected final function getTick()
    {
        return $this->ticks;
    }

    public function start()
    {
        // @todo Improve Shutdown Handler
        // pcntl_signal(SIGINT, [$this, "handleSigint"]);
        // register_shutdown_function(array($this, 'customShutdown'));
        while ($this->run === true) {
            $this->print();
            sleep($this->tickDuration ?? self::TICK_DURRATION_IN_SECONDS);
        }
    }

    private function print()
    {
        $this->clearConsole();
        $this->addBorder();
        $this->addRow("Starttime:       " . $this->startTimestampAsString);
        $this->addRow("ServiceInstance: " . $this->callesClass);
        $this->currentTimestamp = time();
        $this->ticks++;
        $this->addRow(sprintf("Runtime: %s (Ticks: %s // Sleeptime: %s)", $this->formatSeconds($this->currentTimestamp - $this->startTimestamp), $this->ticks, $this->formatSeconds($this->tickDuration ?? self::TICK_DURRATION_IN_SECONDS)));
        $this->addBorder();
        try {
            $this->executeTick();
        } catch (\Exception $e) {
            $this->addRow("");
            $this->addRow("An Error occured:");
            $this->addRow($e->getMessage());
        }
        if (!$this->skipLastBorder) {
            $this->addBorder();
        }
    }

    protected final function addBorder(bool $addNewline = false, bool $skipNext = false)
    {
        if (!$this->skipNextBorder) {
            for ($i = 0; $i < self::ROW_LENGTH; $i++) {
                echo "-";
            }
            if ($addNewline) {
                echo "\n";
            }
            echo "\n";
        }
        $this->skipNextBorder = $skipNext;
    }

    protected final function addRow(string $string)
    {
        $usableChars = self::ROW_LENGTH - 4;
        if (strlen($string) > $usableChars) {
            $substring = substr($string, 0, $usableChars);
        } else {
            $substring = $string;
        }

        echo sprintf("| %s |\n", str_pad($substring, $usableChars, " ", STR_PAD_RIGHT));
    }

    private function clearConsole()
    {
        $os = PHP_OS;
        if (strpos(strtolower($os), 'win') !== false) {
            system('cls');
        } else {
            system('clear');
        }
    }

    private function formatSeconds($seconds)
    {
        if ($seconds === 0) return "-";
        $time = "";
        if ($seconds >= 86400) {
            $days = floor($seconds / 86400);
            $time .= $days . "d ";
            $seconds %= 86400;
        }
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            $time .= $hours . "h ";
            $seconds %= 3600;
        }
        if ($seconds >= 60) {
            $minutes = floor($seconds / 60);
            $time .= $minutes . "min ";
            $seconds %= 60;
        }
        if ($seconds > 0) {
            $time .= $seconds . "s ";
        }
        return $time;
    }
}
