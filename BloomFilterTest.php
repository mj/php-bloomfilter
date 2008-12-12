<?php
require_once "PHPUnit/Framework.php";
require "BloomFilter.php";

class BloomFilterTest extends PHPUnit_Framework_TestCase {
    public function testOverloading() {
        $items = array("first item", "second item", "third item");
        
        /* Add all items with one call to add() and make sure contains() finds
         * them all.
         */
        $filter = new BloomFilter(100, BloomFilter::getHashCount(100, 3));
        $filter->add($items);
        
        foreach ($items as $item) {
            self::assertTrue($filter->contains($item));
        }
        self::assertTrue($filter->contains($items));

        /* Add all items with multiple calls to add() and make sure contains()
         * finds them all.
         */
        $filter = new BloomFilter(100, BloomFilter::getHashCount(100, 3));
        foreach ($items as $item) {
            $filter->add($item);
        }
        self::assertTrue($filter->contains($items));
    }
    
    /**
     * This test case is a port of Ian Clarke's unit tests for his Java Bloom 
     * filter implementation. (http://locut.us/SimpleBloomFilter/)
     */
    public function testBloomFilter() {
        srand(1234567);

        for ($i = 5; $i < 10; $i++) {
            $addCount = 10000 * ($i + 1);
            $bf = new BloomFilter(400000, BloomFilter::getHashCount(400000, $addCount));

            $added = array();
            for ($x = 0; $x < $addCount; $x++) {
                $num = rand();
                $added[$num] = 1;
            }

            foreach (array_keys($added) as $tmp) {
                $bf->add($tmp);
            }

            foreach (array_keys($added) as $tmp) {
                self::assertTrue($bf->contains($tmp), "Assert that there are no false negatives");
            }

            $falsePositives = 0;
            for ($x = 0; $x < $addCount; $x++) {
                $num = rand();

                // Ensure that this random number hasn't been added already
                if (isset($added[$num])) {
                    continue;
                }
                
                // If necessary, record a false positive
                if ($bf->contains($num)) {
                    $falsePositives++;
                }
            }
            
            $expectedFP = $bf->getFalsePositiveProbability();
            $actualFP = $falsePositives / $addCount;

/*
            echo "Got " . $falsePositives
                    . " false positives out of " . $addCount . " added items, rate = "
                    . $actualFP . ", expected = "
                    . $expectedFP . "\n";
*/

            $ratio = $expectedFP / $actualFP;
            $this->assertTrue($ratio > 0.9 && $ratio < 1.1, "Assert that the actual false positive rate doesn't deviate by more than 10% from what was predicted");
        }
    }
}
