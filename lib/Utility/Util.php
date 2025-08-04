<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2018 - 2025
 */

namespace OCA\Music\Utility;

/**
 * Miscellaneous static utility functions
 */
class Util {

	const UINT32_MAX = 0xFFFFFFFF;
	const SINT32_MAX = 0x7FFFFFFF;
	const SINT32_MIN = -self::SINT32_MAX - 1;

	/**
	 * Like the built-in \explode(...) function but this one can be safely called with
	 * null string, and no warning will be emitted. Also, this returns an empty array from
	 * null and '' inputs while the built-in alternative returns a 1-item array containing
	 * an empty string.
	 * @param string $delimiter
	 * @param string|null $string
	 * @return string[]
	 */
	public static function explode(string $delimiter, ?string $string) : array {
		if ($delimiter === '') {
			throw new \UnexpectedValueException();
		} elseif ($string === null || $string === '') {
			return [];
		} else {
			return \explode($delimiter, $string);
		}
	}

	/**
	 * Convert file size given in bytes to human-readable format
	 */
	public static function formatFileSize(?int $bytes, int $decimals = 1) : ?string {
		if ($bytes === null) {
			return null;
		} else {
			$units = 'BKMGTP';
			$factor = \floor((\strlen((string)$bytes) - 1) / 3);
			return \sprintf("%.{$decimals}f", $bytes / \pow(1024, $factor)) . @$units[(int)$factor];
		}
	}

	/**
	 * Convert time given as seconds to the HH:MM:SS format
	 */
	public static function formatTime(?int $seconds) : ?string {
		if ($seconds === null) {
			return null;
		} else {
			return \sprintf('%02d:%02d:%02d', ($seconds/3600), ($seconds/60%60), $seconds%60);
		}
	}

	/**
	 * Convert date and time given in the SQL format to the ISO UTC "Zulu format" e.g. "2021-08-19T19:33:15Z"
	 */
	public static function formatZuluDateTime(?string $dbDateString) : ?string {
		if ($dbDateString === null) {
			return null;
		} else {
			$dateTime = new \DateTime($dbDateString);
			return $dateTime->format('Y-m-d\TH:i:s.v\Z');
		}
	}

	/**
	 * Convert date and time given in the SQL format to the ISO UTC "offset format" e.g. "2021-08-19T19:33:15+00:00"
	 */
	public static function formatDateTimeUtcOffset(?string $dbDateString) : ?string {
		if ($dbDateString === null) {
			return null;
		} else {
			$dateTime = new \DateTime($dbDateString);
			return $dateTime->format('c');
		}
	}

	/**
	 * Encode a file path so that it can be used as part of a WebDAV URL
	 */
	public static function urlEncodePath(string $path) : string {
		// URL encode each part of the file path
		return \join('/', \array_map('rawurlencode', \explode('/', $path)));
	}

	/**
	 * Compose URL from parts as returned by the system function parse_url.
	 * Based on https://stackoverflow.com/a/35207936
	 * @param string[] $parts
	 */
	public static function buildUrl(array $parts) : string {
		return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '')
			. ((isset($parts['user']) || isset($parts['host'])) ? '//' : '')
			. ($parts['user'] ?? '')
			. (isset($parts['pass']) ? ":{$parts['pass']}" : '')
			. (isset($parts['user']) ? '@' : '')
			. ($parts['host'] ?? '')
			. (isset($parts['port']) ? ":{$parts['port']}" : '')
			. ($parts['path'] ?? '')
			. (isset($parts['query']) ? "?{$parts['query']}" : '')
			. (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');

	}

	/**
	 * Swap values of two variables in place
	 * @param mixed $a
	 * @param mixed $b
	 */
	public static function swap(&$a, &$b) : void {
		$temp = $a;
		$a = $b;
		$b = $temp;
	}

	/**
	 * Limit an integer value between the specified minimum and maximum.
	 * A null value is a valid input and will produce a null output.
	 * @param int|float|null $input
	 * @param int|float $min
	 * @param int|float $max
	 * @return int|float|null
	 */
	public static function limit($input, $min, $max) {
		if ($input === null) {
			return null;
		} else {
			return \max($min, \min($input, $max));
		}
	}
}
