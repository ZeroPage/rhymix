<?php

namespace Rhymix\Framework;

/**
 * The datetime class.
 */
class DateTime
{
	/**
	 * Time zone objects and settings are cached here.
	 */
	protected static $_timezones = array();
	
	/**
	 * Format a Unix timestamp for the current user's timezone.
	 * 
	 * @param string $format Format used in PHP date() function
	 * @param int $timestamp Unix timestamp (optional, default is now)
	 * @return string
	 */
	public static function formatTimestampForCurrentUser($format, $timestamp = null)
	{
		$timezone = self::getTimezoneForCurrentUser();
		if (!isset(self::$_timezones[$timezone]))
		{
			self::$_timezones[$timezone] = new \DateTimeZone($timezone);
		}
		$datetime = new \DateTime();
		$datetime->setTimestamp($timestamp ?: time());
		$datetime->setTimezone(self::$_timezones[$timezone]);
		return $datetime->format($format);
	}
	
	/**
	 * Get the current user's timezone.
	 * 
	 * @return string
	 */
	public static function getTimezoneForCurrentUser()
	{
		if (isset($_SESSION['timezone']) && $_SESSION['timezone'])
		{
			return $_SESSION['timezone'];
		}
		elseif ($default = Config::get('locale.default_timezone'))
		{
			return $default;
		}
		else
		{
			return @date_default_timezone_get();
		}
	}
	
	/**
	 * Get the list of time zones supported on this server.
	 * 
	 * @return array
	 */
	public static function getTimezoneList()
	{
		$result = array();
		$tzlist = \DateTimeZone::listIdentifiers();
		foreach ($tzlist as $tzid)
		{
			if (!preg_match('/^(?:A|Europe|Indian|Pacific)/', $tzid)) continue;
			$name = str_replace('_', ' ', $tzid);
			$datetime = new \DateTime(null, new \DateTimeZone($tzid));
			$offset = $datetime->getOffset();
			$offset = ($offset >= 0 ? '+' : '-') . sprintf('%02d', floor(abs($offset) / 3600)) . ':' . sprintf('%02d', (abs($offset) % 3600) / 60);
			unset($datetime);
			$result[$tzid] = "$name ($offset)";
		}
		asort($result);
		$result['Etc/UTC'] = 'GMT/UTC (+00:00)';
		return $result;
	}
	
	/**
	 * Get the absolute (UTC) offset of a timezone.
	 * 
	 * @param string $timezone Timezone identifier, e.g. Asia/Seoul
	 * @param int $timestamp Unix timestamp (optional, default is now)
	 * @return int
	 */
	public static function getTimezoneOffset($timezone, $timestamp = null)
	{
		if (!isset(self::$_timezones[$timezone]))
		{
			self::$_timezones[$timezone] = new \DateTimeZone($timezone);
		}
		$datetime = new \DateTime();
		$datetime->setTimestamp($timestamp ?: time());
		$datetime->setTimezone(self::$_timezones[$timezone]);
		return $datetime->getOffset();
	}
	
	/**
	 * Get the relative offset between a timezone and Rhymix's internal timezone.
	 * 
	 * @param string $timezone Timezone identifier, e.g. Asia/Seoul
	 * @param int $timestamp Unix timestamp (optional, default is now)
	 * @return int
	 */
	public static function getTimezoneOffsetFromInternal($timezone, $timestamp = null)
	{
		return self::getTimezoneOffset($timezone, $timestamp) - Config::get('locale.internal_timezone');
	}
	
	/**
	 * Get the absolute (UTC) offset of a timezone written in XE legacy format ('+0900').
	 * 
	 * @param string $timezone
	 * @return int
	 */
	public static function getTimezoneOffsetByLegacyFormat($timezone)
	{
		$multiplier = ($timezone[0] === '-') ? -60 : 60;
		$timezone = preg_replace('/[^0-9]/', '', $timezone);
		list($hours, $minutes) = str_split($timezone, 2);
		return (((int)$hours * 60) + (int)$minutes) * $multiplier;
	}
	
	/**
	 * Get a PHP time zone by UTC offset.
	 * 
	 * Time zones with both (a) fractional offsets and (b) daylight saving time
	 * (such as Iran's +03:30/+04:30) cannot be converted in this way.
	 * However, if Rhymix is installed for the first time in such a time zone,
	 * the internal time zone will be automatically set to UTC,
	 * so this should never be a problem in practice.
	 * 
	 * @param int $offset
	 * @return bool
	 */
	public static function getTimezoneNameByOffset($offset)
	{
		switch ($offset)
		{
			case 0: return 'Etc/UTC';
			case -34200: return 'Pacific/Marquesas';  // -09:30
			case -16200: return 'America/Caracas';    // -04:30
			case 16200: return 'Asia/Kabul';          // +04:30
			case 19800: return 'Asia/Kolkata';        // +05:30
			case 20700: return 'Asia/Kathmandu';      // +05:45
			case 23400: return 'Asia/Rangoon';        // +06:30
			case 30600: return 'Asia/Pyongyang';      // +08:30
			case 31500: return 'Australia/Eucla';     // +08:45
			case 34200: return 'Australia/Darwin';    // +09:30
			default: return 'Etc/GMT' . ($offset > 0 ? '-' : '+') . intval(abs($offset / 3600));
		}
	}
}
