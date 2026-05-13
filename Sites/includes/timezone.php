<?php
/**
 * Timezone Helper
 * Handles timezone conversion between server UTC and user's local timezone
 */

class TimezoneHelper {
    /**
     * Get user's timezone offset in minutes from JavaScript
     * This should be called from client-side and stored in session
     */
    public static function getUserTimezoneOffset() {
        return $_SESSION['user_timezone_offset'] ?? 0;
    }
    
    /**
     * Set user's timezone offset (called from client-side)
     */
    public static function setUserTimezoneOffset($offsetMinutes) {
        $_SESSION['user_timezone_offset'] = (int)$offsetMinutes;
    }
    
    /**
     * Convert UTC datetime to user's local timezone
     * @param string $utcDatetime - datetime string in UTC (from database)
     * @param string $format - output format (default: 'd.m.Y H:i')
     * @return string - formatted datetime in user's timezone
     */
    public static function toUserTime($utcDatetime, $format = 'd.m.Y H:i') {
        if (empty($utcDatetime)) {
            return '';
        }
        
        try {
            $offsetMinutes = self::getUserTimezoneOffset();
            $utc = new DateTime($utcDatetime, new DateTimeZone('UTC'));
            
            // Apply user's offset
            if ($offsetMinutes !== 0) {
                $interval = new DateInterval('PT' . abs($offsetMinutes) . 'M');
                if ($offsetMinutes < 0) {
                    $utc->sub($interval);
                } else {
                    $utc->add($interval);
                }
            }
            
            return $utc->format($format);
        } catch (Exception $e) {
            error_log('Timezone conversion error: ' . $e->getMessage());
            return date($format, strtotime($utcDatetime));
        }
    }
    
    /**
     * Get current UTC datetime for database storage
     * @return string - current datetime in UTC format (Y-m-d H:i:s)
     */
    public static function getCurrentUTC() {
        return gmdate('Y-m-d H:i:s');
    }
    
    /**
     * Convert user's local time to UTC for database storage
     * @param string $userDatetime - datetime string in user's timezone
     * @return string - datetime in UTC format
     */
    public static function toUTC($userDatetime) {
        if (empty($userDatetime)) {
            return null;
        }
        
        try {
            $offsetMinutes = self::getUserTimezoneOffset();
            $userTime = new DateTime($userDatetime);
            
            // Reverse the offset to get UTC
            if ($offsetMinutes !== 0) {
                $interval = new DateInterval('PT' . abs($offsetMinutes) . 'M');
                if ($offsetMinutes < 0) {
                    $userTime->add($interval);
                } else {
                    $userTime->sub($interval);
                }
            }
            
            return $userTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log('Timezone conversion error: ' . $e->getMessage());
            return $userDatetime;
        }
    }
    
    /**
     * Get timezone offset as ISO format string (+03:00, -05:00, etc.)
     * @return string
     */
    public static function getOffsetString() {
        $offsetMinutes = self::getUserTimezoneOffset();
        $sign = $offsetMinutes >= 0 ? '+' : '-';
        $abs = abs($offsetMinutes);
        $hours = str_pad((int)($abs / 60), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($abs % 60, 2, '0', STR_PAD_LEFT);
        return "{$sign}{$hours}:{$minutes}";
    }
    
    /**
     * Format relative time (e.g., "5 минут назад", "2 часа назад")
     * @param string $utcDatetime - datetime in UTC
     * @return string
     */
    public static function getRelativeTime($utcDatetime) {
        if (empty($utcDatetime)) {
            return '';
        }
        
        try {
            $offsetMinutes = self::getUserTimezoneOffset();
            $utc = new DateTime($utcDatetime, new DateTimeZone('UTC'));
            $now = new DateTime('now', new DateTimeZone('UTC'));
            
            $diff = $now->getTimestamp() - $utc->getTimestamp();
            
            if ($diff < 60) {
                return 'только что';
            } elseif ($diff < 3600) {
                $minutes = floor($diff / 60);
                return $minutes . ' ' . self::pluralize($minutes, 'минуту', 'минуты', 'минут') . ' назад';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                return $hours . ' ' . self::pluralize($hours, 'час', 'часа', 'часов') . ' назад';
            } elseif ($diff < 604800) {
                $days = floor($diff / 86400);
                return $days . ' ' . self::pluralize($days, 'день', 'дня', 'дней') . ' назад';
            } else {
                return self::toUserTime($utcDatetime);
            }
        } catch (Exception $e) {
            return self::toUserTime($utcDatetime);
        }
    }
    
    /**
     * Russian pluralization helper
     */
    private static function pluralize($number, $one, $two, $five) {
        $number = abs($number) % 100;
        $n1 = $number % 10;
        
        if ($number > 10 && $number < 20) {
            return $five;
        }
        if ($n1 > 1 && $n1 < 5) {
            return $two;
        }
        if ($n1 == 1) {
            return $one;
        }
        return $five;
    }
}
