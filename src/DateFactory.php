<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Support;

use Carbon\Factory;
use InvalidArgumentException;
use RuntimeException;

/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method \SwooleTW\Hyperf\Carbon create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method \SwooleTW\Hyperf\Carbon createFromDate($year = null, $month = null, $day = null, $tz = null)
 * @method false|\SwooleTW\Hyperf\Carbon createFromFormat($format, $time, $tz = null)
 * @method \SwooleTW\Hyperf\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method \SwooleTW\Hyperf\Carbon createFromTimeString($time, $tz = null)
 * @method \SwooleTW\Hyperf\Carbon createFromTimestamp($timestamp, $tz = null)
 * @method \SwooleTW\Hyperf\Carbon createFromTimestampMs($timestamp, $tz = null)
 * @method \SwooleTW\Hyperf\Carbon createFromTimestampUTC($timestamp)
 * @method \SwooleTW\Hyperf\Carbon createMidnightDate($year = null, $month = null, $day = null, $tz = null)
 * @method false|\SwooleTW\Hyperf\Carbon createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
 * @method void disableHumanDiffOption($humanDiffOption)
 * @method void enableHumanDiffOption($humanDiffOption)
 * @method mixed executeWithLocale($locale, $func)
 * @method \SwooleTW\Hyperf\Carbon fromSerialized($value)
 * @method array getAvailableLocales()
 * @method array getDays()
 * @method int getHumanDiffOptions()
 * @method array getIsoUnits()
 * @method array getLastErrors()
 * @method string getLocale()
 * @method int getMidDayAt()
 * @method null|\SwooleTW\Hyperf\Carbon getTestNow()
 * @method \Symfony\Component\Translation\TranslatorInterface getTranslator()
 * @method int getWeekEndsAt()
 * @method int getWeekStartsAt()
 * @method array getWeekendDays()
 * @method bool hasFormat($date, $format)
 * @method bool hasMacro($name)
 * @method bool hasRelativeKeywords($time)
 * @method bool hasTestNow()
 * @method \SwooleTW\Hyperf\Carbon instance($date)
 * @method bool isImmutable()
 * @method bool isModifiableUnit($unit)
 * @method bool isMutable()
 * @method bool isStrictModeEnabled()
 * @method bool localeHasDiffOneDayWords($locale)
 * @method bool localeHasDiffSyntax($locale)
 * @method bool localeHasDiffTwoDayWords($locale)
 * @method bool localeHasPeriodSyntax($locale)
 * @method bool localeHasShortUnits($locale)
 * @method void macro($name, $macro)
 * @method null|\SwooleTW\Hyperf\Carbon make($var)
 * @method \SwooleTW\Hyperf\Carbon maxValue()
 * @method \SwooleTW\Hyperf\Carbon minValue()
 * @method void mixin($mixin)
 * @method \SwooleTW\Hyperf\Carbon now($tz = null)
 * @method \SwooleTW\Hyperf\Carbon parse($time = null, $tz = null)
 * @method string pluralUnit(string $unit)
 * @method void resetMonthsOverflow()
 * @method void resetToStringFormat()
 * @method void resetYearsOverflow()
 * @method void serializeUsing($callback)
 * @method void setHumanDiffOptions($humanDiffOptions)
 * @method bool setLocale($locale)
 * @method void setMidDayAt($hour)
 * @method void setTestNow($testNow = null)
 * @method void setToStringFormat($format)
 * @method void setTranslator(\Symfony\Component\Translation\TranslatorInterface $translator)
 * @method void setUtf8($utf8)
 * @method void setWeekEndsAt($day)
 * @method void setWeekStartsAt($day)
 * @method void setWeekendDays($days)
 * @method bool shouldOverflowMonths()
 * @method bool shouldOverflowYears()
 * @method string singularUnit(string $unit)
 * @method \SwooleTW\Hyperf\Carbon today($tz = null)
 * @method \SwooleTW\Hyperf\Carbon tomorrow($tz = null)
 * @method void useMonthsOverflow($monthsOverflow = true)
 * @method void useStrictMode($strictModeEnabled = true)
 * @method void useYearsOverflow($yearsOverflow = true)
 * @method \SwooleTW\Hyperf\Carbon yesterday($tz = null)
 */
class DateFactory
{
    /**
     * The default class that will be used for all created dates.
     *
     * @var string
     */
    public const DEFAULT_CLASS_NAME = Carbon::class;

    /**
     * The type (class) of dates that should be created.
     */
    protected static ?string $dateClass = null;

    /**
     * This callable may be used to intercept date creation.
     *
     * @var callable
     */
    protected static $callable;

    /**
     * The Carbon factory that should be used when creating dates.
     */
    protected static ?object $factory = null;

    /**
     * Use the given handler when generating dates (class name, callable, or factory).
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public static function use(mixed $handler)
    {
        if (is_callable($handler) && is_object($handler)) {
            return static::useCallable($handler);
        }
        if (is_string($handler)) {
            return static::useClass($handler);
        }
        if ($handler instanceof Factory) {
            return static::useFactory($handler);
        }

        throw new InvalidArgumentException('Invalid date creation handler. Please provide a class name, callable, or Carbon factory.');
    }

    /**
     * Use the default date class when generating dates.
     */
    public static function useDefault(): void
    {
        static::$dateClass = null;
        static::$callable = null;
        static::$factory = null;
    }

    /**
     * Execute the given callable on each date creation.
     */
    public static function useCallable(callable $callable): void
    {
        static::$callable = $callable;

        static::$dateClass = null;
        static::$factory = null;
    }

    /**
     * Use the given date type (class) when generating dates.
     */
    public static function useClass(string $dateClass): void
    {
        static::$dateClass = $dateClass;

        static::$factory = null;
        static::$callable = null;
    }

    /**
     * Use the given Carbon factory when generating dates.
     */
    public static function useFactory(object $factory): void
    {
        static::$factory = $factory;

        static::$dateClass = null;
        static::$callable = null;
    }

    /**
     * Handle dynamic calls to generate dates.
     *
     * @throws RuntimeException
     */
    public function __call(string $method, array $parameters)
    {
        $defaultClassName = static::DEFAULT_CLASS_NAME;

        // Using callable to generate dates...
        if (static::$callable) {
            return call_user_func(static::$callable, $defaultClassName::$method(...$parameters));
        }

        // Using Carbon factory to generate dates...
        if (static::$factory) {
            return static::$factory->{$method}(...$parameters);
        }

        $dateClass = static::$dateClass ?: $defaultClassName;

        // Check if the date can be created using the public class method...
        if (
            method_exists($dateClass, $method)
            || method_exists($dateClass, 'hasMacro') && $dateClass::hasMacro($method)
        ) {
            return $dateClass::$method(...$parameters);
        }

        // If that fails, create the date with the default class...
        $date = $defaultClassName::$method(...$parameters);

        // If the configured class has an "instance" method, we'll try to pass our date into there...
        if (method_exists($dateClass, 'instance')) {
            return $dateClass::instance($date);
        }

        // Otherwise, assume the configured class has a DateTime compatible constructor...
        return new $dateClass($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
}
