<?php
/**
 * Function gets an array ($array), searches for multiple key-value pairs ($search) in $array second dimension 
 * and returns array with $array keys for those $array values, that contain matches with $search.
 * <p>For example:</p>
 * 
 * <pre>
 * $phonesList = [
 *    0 => [
 *        'Manufacturer' => 'Apple',
 *        'Model' => 'iPhone 3G 8GB',
 *        'Carrier' => 'AT&T',
 *        'Cost' => 100000,
 *    ],
 *    1 => [
 *        'Manufacturer' => 'Motorola',
 *        'Model' => 'Droid X2',
 *        'Carrier' => 'Verizon',
 *        'Cost' => 120000,
 *    ],
 *    2 => [
 *        'Manufacturer' => 'Motorola',
 *        'Model' => 'Droid X100',
 *        'Carrier' => 'Verizon',
 *        'Cost' => 150000,
 *    ]
 * ];
 * </pre>
 * 
 * THERE ARE 2 SEARCH MODES:
 * 
 * <p>MODE 1. $search is one-dimensional array. For example:</p>
 * 
 * <pre>
 * // Search WHERE 'Manufacturer' = 'Motorola' AND 'Model' = 'Droid X2'.
 * $search = ['Manufacturer' => 'Motorola', 'Model' => 'Droid X2'];
 * $phoneIDs = getKeysByKeyValPairs($phonesList, $search);
 * </pre>
 * 
 * $phoneIDs output contains $array's key = 1, 'cause exactly value with that key contains matches with $search:
 * <pre>
 * array(1) {
 *      [0]=> int(1)
 * }
 * </pre>
 * 
 * <p>
 * MODE 2. $search is 2-dimensional array. Every array in second dimension contains 3 elements: 
 * first is comparison operator, second and third - key and value respectively. For example:
 * </p>
 * 
 * <pre>
 * // Search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000.
 * $search = [ 
 *      ['==', 'Manufacturer', 'Motorola'], 
 *      ['>', 'Cost', 130000] 
 * ];
 * $phoneIDs = getKeysByKeyValPairs($phonesList, $search);
 * </pre>
 * 
 * $phoneIDs output contains $array's key = 2:
 * <pre>
 * array(1) {
 *      [0]=> int(2)
 * }
 * </pre>
 * 
 * @param array $array Where to search
 * @param array $search What to search. Has 2 modes, see above
 * @param string $logicOperator If 'and', search will be with AND operator:
 * // Search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000
 * $search = [ 
 *      ['==', 'Manufacturer', 'Motorola'], 
 *      ['>', 'Cost', 130000] 
 * ];
 * If 'or', search will be with OR operator (search WHERE 'Manufacturer' = 'Motorola' OR 'Cost' > 130000)
 * @return array List with $array keys or empty array, if nothing found
 * @see https://stackoverflow.com/a/47866650/4695280
 */
function getKeysByKeyValPairs($array, $search, $logicOperator = 'and')
{
    $result = [];
    foreach ($array as $key => $value) { // Iterate over each array element.
        $i = 0; // Detects last element in $search.

        foreach ($search as $k => $v) { // Iterate over each search condition.
            ++$i;

            // CASE 1. Complicated search with diff operators, like:
            // [
            //      ['==', 'client_id', $client_id],
            //      ['>', 'age_from', 15]
            // ];
            // Search condition in this case looks like: 'client_id' = $client_id AND 'age_from' > 15.
            if (is_array($v)) {
                $operator = strtolower($v[0]);
                $searchField = $v[1];
                $searchVal = $v[2];

                // If the wrong search key is passed.
                if (!array_key_exists($searchField, $value)) {
                    continue 2;
                }

                switch ($operator) {
                    case '==':
                    default:
                        $cond = ($value[$searchField] == $searchVal);
                        break;
                    case '===':
                        $cond = ($value[$searchField] === $searchVal);
                        break;
                    case '!==':
                        $cond = ($value[$searchField] !== $searchVal);
                        break;
                    case '!=':
                    case '<>':
                        $cond = ($value[$searchField] != $searchVal);
                        break;
                    case '>':
                        $cond = ($value[$searchField] > $searchVal);
                        break;
                    case '<':
                        $cond = ($value[$searchField] < $searchVal);
                        break;
                    case '>=':
                        $cond = ($value[$searchField] >= $searchVal);
                        break;
                    case '<=':
                        $cond = ($value[$searchField] <= $searchVal);
                        break;
                    case 'in':
                    case 'strict in':
                        if (is_array($searchVal)) {
                            $cond = ($operator === 'in')
                                ? in_array($value[$searchField], $searchVal)
                                : in_array($value[$searchField], $searchVal, true);
                        // If $searchVal is not an array, just compare with $searchVal as like with scalar.
                        } else {
                            $cond = ($operator === 'in')
                                ? ($value[$searchField] == $searchVal)
                                : ($value[$searchField] === $searchVal);
                        }
                        break;
                    case 'not in':
                    case 'strict not in':
                        if (is_array($searchVal)) {
                            $cond = ($operator === 'not in')
                                ? !in_array($value[$searchField], $searchVal)
                                : !in_array($value[$searchField], $searchVal, true);
                        // If $searchVal is not an array, just compare with $searchVal as like with scalar.
                        } else {
                            $cond = ($operator === 'not in')
                                ? ($value[$searchField] != $searchVal)
                                : ($value[$searchField] !== $searchVal);
                        }
                        break;
                    case 'preg':
                        $cond = (preg_match($searchVal, $value[$searchField]) === 1);
                        break;
                }

                // CASE 2. Simple search, like: ['event_id' => 185, 'paid' => 1].
                // In this case operator '==' is default.
                // Search condition looks like: 'event_id' = 185 AND 'paid' = 1.
            } else {
                $searchField = $k;

                // If the wrong search key is passed.
                if (!array_key_exists($searchField, $value)) {
                    continue 2;
                }

                $cond = ($value[$searchField] == $v);
            }

            // Key-value pair in $search matches ($cond is TRUE). 
            // If logic operator is 'OR', just add $array element to $result 
            // (because at least one match is OK) and go to next $array element.
            if ($cond) {

                if ($logicOperator === 'or') {
                    $result[] = $key;
                    continue 2;
                }

            // Key-value pair in $search doesn't match ($cond is FALSE).
            } else {

                // If logic operator is 'AND', go to next element in $array, 'cause ALL PAIRS IN $search MUST MATCH.
                if ($logicOperator === 'and') {
                    continue 2;
                    
                // If logic operator is 'OR', AT LEAST ONE PAIR MUST MATCH.
                } else {
                    // If it's the last key-value pair in $search, no matches. Go to next $array element.
                    if (count($search) == $i) {
                        continue 2;
                    }
                    // No match in current $search key-value pair. Go to next key-value pair.
                    // If match is found in that next pair, the following ELSE STMT will run.
                    continue; 
                }
            }
        }
        // Add $array element's key to the result array.
        $result[] = $key;
    }
    return $result;
}

/**
 * Function gets an array ($array), searches for multiple key-value pairs ($search) in $array second dimension 
 * and returns filtered $array (with those values, that contain matches with $search)
 * <p>For example:</p>
 * 
 * <pre>
 * $phonesList = [
 *    0 => [
 *        'Manufacturer' => 'Apple',
 *        'Model' => 'iPhone 3G 8GB',
 *        'Carrier' => 'AT&T',
 *        'Cost' => 100000,
 *    ],
 *    1 => [
 *        'Manufacturer' => 'Motorola',
 *        'Model' => 'Droid X2',
 *        'Carrier' => 'Verizon',
 *        'Cost' => 120000,
 *    ],
 *    2 => [
 *        'Manufacturer' => 'Motorola',
 *        'Model' => 'Droid X100',
 *        'Carrier' => 'Verizon',
 *        'Cost' => 150000,
 *    ]
 * ];
 * </pre>
 * 
 * THERE ARE 2 SEARCH MODES:
 * 
 * <p>MODE 1. $search is one-dimensional array. For example:</p>
 * 
 * <pre>
 * // Search WHERE 'Manufacturer' = 'Motorola' AND 'Model' = 'Droid X2'.
 * $search = ['Manufacturer' => 'Motorola', 'Model' => 'Droid X2'];
 * $phoneIDs = getElemsByKeyValPairs($phonesList, $search);
 * </pre>
 * 
 * $phoneIDs output contains first $array element.
 * 
 * <p>
 * MODE 2. $search is 2-dimensional array. Every array in second dimension contains 3 elements: 
 * first is comparison operator, second and third - key and value respectively. For example:
 * </p>
 * 
 * <pre>
 * // Search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000.
 * $search = [ 
 *      ['==', 'Manufacturer', 'Motorola'], 
 *      ['>', 'Cost', 130000] 
 * ];
 * $phoneIDs = getElemsByKeyValPairs($phonesList, $search);
 * </pre>
 * 
 * $phoneIDs output contains second $array element.
 * 
 * Available comparison operators: '==', '===', '>', '<', '>=', '<=', '!=', '!==', '<>', 'in' and 'strict in'
 * (in_array() is used; in case of 'strict in' comparison is strict), 'not in' and 'strict not in' (!in_array()
 * is used; in case of 'strict not in' comparison is strict), 'preg' (preg_match() is used).
 * If you input unsupported operator, '==' will be used instead.
 * 
 * @param array $array Where to search
 * @param array $search What to search. Has 2 modes, see above
 * @param string $logicOperator If 'and', search will be with AND operator:
 * // Search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000.
 * $search = [ 
 *      ['==', 'Manufacturer', 'Motorola'], 
 *      ['>', 'Cost', 130000] 
 * ];
 * If 'or', search will be with OR operator (search WHERE 'Manufacturer' = 'Motorola' OR 'Cost' > 130000)
 * @param boolean $reindex If TRUE, result array will be reindexed
 * @return array Filtered $array or empty array, if nothing found
 * @see getKeysByKeyValPairs()
 */
function getElemsByKeyValPairs($array, $search, $logicOperator = 'and', $reindex = true)
{
    $matchedElems = [];
    $matchedKeys = getKeysByKeyValPairs($array, $search, $logicOperator);

    if (!empty($matchedKeys)) {
        // Remain only those elements in $array, which keys are in $matchedArrKeys.
        $matchedElems = arrayFilterByKeys($array, $matchedKeys);

        if ($reindex) {
            // Reindex array.
            $matchedElems = array_values($matchedElems);
        }
    }
    return $matchedElems;
}

/**
 * Filters elements of an array by its keys.
 * @param array $array Array, that is needed to be filtered
 * @param array $allowedKeys $array keys will be filtered by $allowedKeys
 * @return array $array, filtered by $allowedKeys. Empty array is returned, if $array doesn't
 * contain keys, which are present in $allowedKeys, or if $allowedKeys is empty.
 * @see https://stackoverflow.com/a/4260168/4695280
 */
function arrayFilterByKeys($array, $allowedKeys)
{
    if (version_compare(PHP_VERSION, '5.6.0', '<')) {
        return array_intersect_key($array, array_flip($allowedKeys));
    } else {
        return array_filter(
            $array,
            function($k) use ($allowedKeys) {
                return in_array($k, $allowedKeys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
