<?php

/**
 * @class Utils
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace Flame\PayPal;


final class Utils
{

	public static function translateKeys(array $data, $translationTable = array(), $method = 'strtolower')
	{

        // If there are no translation items in
        // translation table we just return untranslated data
        if (empty($translationTable))
            return $data;

		$translated = array();

		foreach ($data as $key => $value) {
			if (array_key_exists($key, $translationTable)) {
				$translated[$translationTable[$key]] = $value;

			} else {
				$translated[$method($key)] = $value;
			}
		}

		return $translated;
	}



	/**
	 * Finds out if all keys in $keys are included in $arr.
	 *
	 * @param $arr Source array
	 * @param $keys Array of keys
	 *
	 * @return boolean
	 */
	public static function array_keys_exist($arr, $keys)
	{
		if (count(array_intersect($keys, array_keys($arr))) == count($keys)) {
			return true;
		}

		return false;
	}



	/**
	 * Returns subarray from array.
	 * New array is created only from keys which matches the reqular expression.
	 *
	 * @author Otto Sabart
	 *
	 * @param $arr Source array
	 * @param $pattern Regular expression
	 *
	 * @return array Subarray
	 */
	public static function array_keys_by_ereg($arr, $pattern)
	{
		$subArray = array();

		$matches = preg_grep($pattern, array_keys($arr));
		foreach ($matches as $match) {
			$subArray[$match] = $arr[$match];
		}

		return $subArray;
	}

}
