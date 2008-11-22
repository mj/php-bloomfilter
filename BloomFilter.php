<?php
/**
 * Copyright (c) 2008 Martin Jansen
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Implements a Bloom Filter
 *
 * Bloom Filters have been defined by Burton Bloom in 1970 as an efficient
 * data structure to test if an element is part of a set.  The filter works
 * probabilistically, i.e. false positives are possible, while false negatives 
 * do not occur.
 *
 * The implementation uses simple hash functions based on random numbers for
 * distributing elements in the bit set.  This should suffice for most
 * applications, but your mileage may vary.
 *
 * @author Martin Jansen <martin@divbyzero.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @link http://en.wikipedia.org/wiki/Bloom_filter Wikipedia article on Bloom Filters
 * @link http://portal.acm.org/citation.cfm?doid=362686.362692 Space/time trade-offs in hash coding with allowable errors
 */
class BloomFilter {
    /**
     * Size of the bit array
     *
     * @var int
     */
    protected $m;

    /**
     * Number of hash functions
     *
     * @var int
     */
    protected $k;

    /**
     * Number of elements in the filter
     *
     * @var int
     */
    protected $n;

    /**
     * The bitset holding the filter information
     *
     * @var array
     */
    protected $bitset;

    /**
     * Calculates an optimal number of hash functions to use in the filter for
     * a number of items that one wishes to use maximally.
     *
     * The formula for the value is "n/m * ln(2)".  Use this method if you are
     * unsure about the value for the second constructor argument but know how
     * many items you will typically add to the filter.
     *
     * @param int Size of the bit array
     * @param int Typical number of items that will be added to the filter
     * @return int
     */
    public static function getHashCount($m, $n) {
        return ceil(($m / $n) * log(2));
    }

    /**
     * Construct an instance of the Bloom filter
     *
     * @param int Size of the bit array
     * @param int Number of different hash functions to use
     */
    public function __construct($m, $k) {
        $this->m = $m;
        $this->k = $k;
        $this->n = 0;

        /* Initialize the bit set */
        $this->bitset = array_fill(0, $this->m - 1, false);
    }

    /**
     * Returns the probability for a false positive to occur, given the current number of items in the filter
     *
     * @return double
     */
    public function getFalsePositiveProbability() {
        $exp = (-1 * $this->k * $this->n) / $this->m;

        return pow(1 - exp($exp),  $this->k);
    }

    /**
     * Adds a new item to the filter
     *
     * @param mixed Either a string holding a single item or an array of 
     *              string holding multiple items.  In the latter case, all
     *              items are added one by one internally.
     */
    public function add($key) {
        if (is_array($key)) {
            foreach ($key as $k) {
                $this->add($k);
            }
            return;
        }

        $this->n++;

        foreach ($this->getSlots($key) as $slot) {
            $this->bitset[$slot] = true;
        }
    }

    /**
     * Queries the Bloom filter for an element
     *
     * If this method return FALSE, it is 100% certain that the element has
     * not been added to the filter before.  In contrast, if TRUE is returned,
     * the element *may* have been added to the filter previously.  However with
     * a probability indicated by getFalsePositiveProbability() the element has
     * not been added to the filter with contains() still returning TRUE.
     *
     * @param mixed Either a string holding a single item or an array of 
     *              strings holding multiple items.  In the latter case the
     *              method returns TRUE if the filter contains all items.
     * @return boolean
     */
    public function contains($key) {
        if (is_array($key)) {
            foreach ($key as $k) {
                if ($this->contains($k) == false) {
                    return false;
                }
            }

            return true;
        }

        foreach ($this->getSlots($key) as $slot) {
            if ($this->bitset[$slot] == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hashes the argument to a number of positions in the bit set and returns the positions
     *
     * @param string Item
     * @return array Positions
     */
    protected function getSlots($key) {
        $slots = array();
        $hash = self::getHashCode($key);
        mt_srand($hash);

        for ($i = 0; $i < $this->k; $i++) {
            $slots[] = mt_rand(0, $this->m - 1);
        }

        return $slots;
    }

    /**
     * Generates a numeric hash for the given string
     *
     * Right now the CRC-32 algorithm is used.  Alternatively one could e.g.
     * use Adler digests or mimick the behaviour of Java's hashCode() method.
     *
     * @param string Input for which the hash should be created
     * @return int Numeric hash
     */
    protected static function getHashCode($string) {
        return crc32($string);
    }
}
