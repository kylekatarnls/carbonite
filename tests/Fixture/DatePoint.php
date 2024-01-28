<?php

/*
 * This file simulates \Symfony\Component\Clock\DatePoint
 */

namespace Symfony\Component\Clock;

final class DatePoint extends \DateTimeImmutable
{
    /**
     * @throws \DateMalformedStringException When $datetime is invalid
     */
    public function __construct(string $datetime = 'now', \DateTimeZone $timezone = null, parent $reference = null)
    {
        $now = $reference ?? Clock::get()->now();

        if ('now' !== $datetime) {
            if (!$now instanceof self) {
                $now = self::createFromInterface($now);
            }

            if (\PHP_VERSION_ID < 80300) {
                try {
                    $timezone = (new parent($datetime, $timezone ?? $now->getTimezone()))->getTimezone();
                } catch (\Exception $e) {
                    throw new \DateMalformedStringException($e->getMessage(), $e->getCode(), $e);
                }
            } else {
                $timezone = (new parent($datetime, $timezone ?? $now->getTimezone()))->getTimezone();
            }

            $now = $now->setTimezone($timezone)->modify($datetime);
        } elseif (null !== $timezone) {
            $now = $now->setTimezone($timezone);
        }

        $this->__unserialize((array) $now);
    }

    /**
     * @throws \DateMalformedStringException When $format or $datetime are invalid
     */
    public static function createFromFormat(string $format, string $datetime, \DateTimeZone $timezone = null): self
    {
        $date = parent::createFromFormat($format, $datetime, $timezone);

        if ($date) {
            return $date;
        }

        throw new \DateMalformedStringException(
            self::getLastErrors()['errors'][0] ?? 'Invalid date string or format.'
        );
    }

    public static function createFromInterface(\DateTimeInterface $object): self
    {
        return self::createFromFormat(
            'Y-m-d H:i:s.u e',
            $object->format('Y-m-d H:i:s.u e')
        );
    }

    #[\ReturnTypeWillChange]
    public static function createFromMutable(\DateTime $object): self
    {
        return parent::createFromMutable($object);
    }

    public function add(\DateInterval $interval): self
    {
        return parent::add($interval);
    }

    public function sub(\DateInterval $interval): self
    {
        return parent::sub($interval);
    }

    /**
     * @throws \DateMalformedStringException When $modifier is invalid
     */
    public function modify(string $modifier): self
    {
        if (\PHP_VERSION_ID < 80300) {
            $date = @parent::modify($modifier);

            if ($date) {
                return $date;
            }

            throw new \DateMalformedStringException(
                error_get_last()['message'] ?? sprintf('Invalid modifier: "%s".', $modifier)
            );
        }

        return parent::modify($modifier);
    }

    public function setTimestamp(int $value): self
    {
        return parent::setTimestamp($value);
    }

    public function setDate(int $year, int $month, int $day): self
    {
        return parent::setDate($year, $month, $day);
    }

    public function setISODate(int $year, int $week, int $day = 1): self
    {
        return parent::setISODate($year, $week, $day);
    }

    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): self
    {
        return parent::setTime($hour, $minute, $second, $microsecond);
    }

    public function setTimezone(\DateTimeZone $timezone): self
    {
        return parent::setTimezone($timezone);
    }

    public function getTimezone(): \DateTimeZone
    {
        $timezone = parent::getTimezone();

        if ($timezone) {
            return $timezone;
        }

        throw new \DateInvalidTimeZoneException('The DatePoint object has no timezone.');
    }
}
