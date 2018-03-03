<?php
    /**
     * Function gets an array ($array), searches for multiple key/value pairs ($search) in $array second dimension 
     * and returns array with $array keys for those $array values, that contain matches with $search
     * <p>For ex.:</p>
     * $phonesList = [
     *                   0 => [
     *                          'Manufacturer' => 'Apple',
     *                          'Model' => 'iPhone 3G 8GB',
     *                          'Carrier' => 'AT&T',
     *                          'Cost' => 100000,
     *                        ],
     *                   1 => [
     *                          'Manufacturer' => 'Motorola',
     *                          'Model' => 'Droid X2',
     *                          'Carrier' => 'Verizon',
     *                          'Cost' => 120000,
     *                        ],
     *                   2 => [
     *                          'Manufacturer' => 'Motorola',
     *                          'Model' => 'Droid X100',
     *                          'Carrier' => 'Verizon',
     *                          'Cost' => 150000,
     *                        ]
     *              ];
     * 
     * THERE ARE 2 SEARCH MODES:
     * 
     * <p>MODE 1. $search is one-dimensional array. For ex.:</p>
     * 
     * <pre>
     *      $search = ['Manufacturer' => 'Motorola', 'Model' => 'Droid X2']; //search WHERE 'Manufacturer' = 'Motorola' AND 'Model' = 'Droid X2'
     *      $phoneIDs = searchElemsByKeyValPair($phonesList, $search);
     * </pre>
     * 
     * $phoneIDs output contains $array key = 1, cause exactly value with that key contains matches with $search:
     * <pre>array(1) { [0]=> int(1) }</pre>
     * 
     * <p>MODE 2. $search is 2-dimensional array. Every array in second dimension contains 3 elements: 
     *    First is comparison operator, 2nd and 3rd - key and value respectively. For ex.:</p>
     * 
     * <pre>
     *      $search = [ 
     *                  ['==', 'Manufacturer', 'Motorola'], 
     *                  ['>', 'Cost', 130000] 
     *                ]; //search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000
     *      $phoneIDs = searchElemsByKeyValPair($phonesList, $search);
     * </pre>
     * 
     * $phoneIDs output contains $array key = 2:
     * <pre>array(1) { [0]=> int(2) }</pre>
     * 
     * @param array $array Where to search
     * @param array $search What to search. Has 2 modes, see above
     * @param string $logicOperator If 'and', search will be with AND operator:
     *                              $search = [ 
     *                                  ['==', 'Manufacturer', 'Motorola'], 
     *                                  ['>', 'Cost', 130000] 
     *                              ]; //search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000
     *                              If 'or', search will be with OR operator (search WHERE 'Manufacturer' = 'Motorola' OR 'Cost' > 130000)
     * 
     * @return array List with $array keys or empty array, if nothing found
     * @see https://stackoverflow.com/a/47866650/4695280
     */
    function searchElemsByKeyValPair ($array, $search, $logicOperator = 'and') {
        $result = [];

        foreach ($array as $key => $value) { //iterate over each array element
            $i = 0; //detects last elem in $search
            
            foreach ($search as $k => $v) { //iterate over each search condition
                ++$i;
                
                //CASE 1. Complicated search with diff operators, like: [ ['==', 'client_id', $client_id],
                //                                                        ['>', 'age_from', 15] ];
                //Search condition in this case looks like: 'client_id' = $client_id AND 'age_from' > 15
                if (is_array($v)) {
                    $operator = $v[0];
                    $searchField = $v[1];
                    $searchVal = $v[2];

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
                            if (is_array($searchVal)) {
                                $cond = in_array($value[$searchField], $searchVal);
                            } else { //if $searchVal is not arr, just compare with $searchVal as like with scalar
                                $cond = ($value[$searchField] == $searchVal);
                            }
                            break;
                            
                        case 'preg':
                            $cond = (preg_match($searchVal, $value[$searchField]) === 1);
                            break;
                    }
                
                //CASE 2. Simple search, like: ['event_id' => 185, 'paid' => 1].
                //In this case operator '==' is default
                //Search condition looks like: 'event_id' = 185 AND 'paid' = 1
                } else {
                    $searchField = $k;
                    $cond = ($value[$k] == $v);
                }

                //if the array element does not meet the search condition then continue to the next element
                if ((!isset($value[$searchField]) && $value[$searchField] !== null)) continue 2;
                    
                //key-val pair in $search doesn't match ($cond is FALSE)
                if (!$cond) {
                    
                    if ($logicOperator === 'and') { //if logic operator is 'AND', go to next elem in $array, cause ALL PAIRS IN $search MUST MATCH
                        continue 2;
                        
                    } else { //if logic operator is 'OR', AT LEAST ONE PAIR MUST MATCH
                           
                        if (count($search) == $i) continue 2; //if it's the last key-val pair in $search, no matches. Go to next $array elem
                       
                        continue; //no match in cur. $search key-val pair. Go to next key-val pair. If match is found in that next pair, the following ELSE STMT will run
                    }
                
                //key-val pair in $search matches ($cond is TRUE). 
                //If logic operator is 'OR', just add $array elem to $result (because at least one match is OK) and go to next $array elem
                } else { 
                    if ($logicOperator === 'or') {
                        $result[] = $key;
                        continue 2;
                    }
                }
            }
            
            $result[] = $key; //add $array element's key to the result array
        }
        return $result;
    }

    /**
     * Function gets an array ($array), searches for multiple key/value pairs ($search) in $array second dimension 
     * and returns filtered $array (with those values, that contain matches with $search)
     * <p>For ex.:</p>
     * $phonesList = [
     *                   0 => [
     *                          'Manufacturer' => 'Apple',
     *                          'Model' => 'iPhone 3G 8GB',
     *                          'Carrier' => 'AT&T',
     *                          'Cost' => 100000,
     *                        ],
     *                   1 => [
     *                          'Manufacturer' => 'Motorola',
     *                          'Model' => 'Droid X2',
     *                          'Carrier' => 'Verizon',
     *                          'Cost' => 120000,
     *                        ],
     *                   2 => [
     *                          'Manufacturer' => 'Motorola',
     *                          'Model' => 'Droid X100',
     *                          'Carrier' => 'Verizon',
     *                          'Cost' => 150000,
     *                        ]
     *              ];
     * 
     * THERE ARE 2 SEARCH MODES:
     * 
     * <p>MODE 1. $search is one-dimensional array. For ex.:</p>
     * 
     * <pre>
     *      $search = ['Manufacturer' => 'Motorola', 'Model' => 'Droid X2']; //search WHERE 'Manufacturer' = 'Motorola' AND 'Model' = 'Droid X2'
     *      $phoneIDs = getElemsByKeyValPairs($phonesList, $search);
     * </pre>
     * 
     * $phoneIDs output contains first $array element.
     * 
     * <p>MODE 2. $search is 2-dimensional array. Every array in second dimension contains 3 elements: 
     *    First is comparison operator, 2nd and 3rd - key and value respectively. For ex.:</p>
     * 
     * <pre>
     *      $search = [ 
     *                  ['==', 'Manufacturer', 'Motorola'], 
     *                  ['>', 'Cost', 130000] 
     *                ]; //search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000
     *      $phoneIDs = getElemsByKeyValPairs($phonesList, $search);
     * </pre>
     * 
     * $phoneIDs output contains second $array element.
     * 
     * Available comparison operators: ==, ===, >, <, >=, <=, !=, !==, <>, in, preg (preg_match() is used).
     * If you input unsupported operator, it will be replaced with ==.
     * 
     * @param array $array Where to search
     * @param array $search What to search. Has 2 modes, see above
     * @param string $logicOperator If 'and', search will be with AND operator:
     *                              $search = [ 
     *                                  ['==', 'Manufacturer', 'Motorola'], 
     *                                  ['>', 'Cost', 130000] 
     *                              ]; //search WHERE 'Manufacturer' = 'Motorola' AND 'Cost' > 130000
     *                              If 'or', search will be with OR operator (search WHERE 'Manufacturer' = 'Motorola' OR 'Cost' > 130000)
     * @param boolean $reindex If TRUE, result array will be reindexed
     * @return array Filtered $array or empty array, if nothing found
     * @see searchElemsByKeyValPair()
     */
    function getElemsByKeyValPairs ($array, $search, $logicOperator = 'and', $reindex = true) {

        $matchedElems = [];

        $matchedKeys = searchElemsByKeyValPair($array, $search, $logicOperator);

        if (!empty($matchedKeys)) {
            //remain only those elems in $array, which keys are in $matchedArrKeys
            //https://stackoverflow.com/a/4260168/4695280
            $matchedElems = array_intersect_key($array, array_flip($matchedKeys));
            
            if ($reindex) {
                $matchedElems = array_values($matchedElems); //reindex arr
            }
        }
        return $matchedElems;
    }
